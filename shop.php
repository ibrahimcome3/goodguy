<?php
session_start();
require_once "includes.php"; // Provides $pdo, classes, functions

// Instantiate necessary objects
try {
    $Orvi = new Review($pdo);
    $cat = new Category($pdo); // For category filter list
} catch (Exception $e) {
    error_log("Error instantiating classes in shop.php: " . $e->getMessage());
    // Handle error appropriately, maybe show a generic error message
}

// --- Filter & Pagination Settings ---
$category_filter_identifier = isset($_GET['category']) ? trim($_GET['category']) : null; // Can be ID or Name/Slug
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_desc'; // Default sort
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1; // Ensure page is at least 1
$min_price_filter = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float) $_GET['min_price'] : null;
$max_price_filter = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float) $_GET['max_price'] : null;
$stock_status_filter = 'in_stock'; // Hardcode to 'in_stock' for customers
$product_status_filter = 'active'; // Hardcode to 'active' for customers
$products_per_page = 35; // Number of products per page
$offset = ($page - 1) * $products_per_page;

// --- Fetch Categories for Filter Sidebar ---
$all_categories_for_filter = [];
try {
    $stmt_cat_filter = $pdo->query("SELECT category_id, name, parent_id FROM categories ORDER BY name ASC");
    $all_categories_for_filter = $stmt_cat_filter->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories for filter: " . $e->getMessage());
}

// --- Helper Functions for Nested Categories ---

/**
 * Builds a hierarchical tree from a flat array of categories.
 *
 * @param array $elements Array of categories, each with 'category_id', 'name', 'parent_id'.
 * @param int $parentId The ID of the parent to find children for.
 * @return array The nested tree structure.
 */
function buildCategoryTree(array &$elements, $parentId = 0): array
{
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildCategoryTree($elements, $element['category_id']);
            if ($children) {
                $element['children'] = $children;
            }
            $branch[$element['category_id']] = $element; // Use ID as key for easier lookup if needed
            // Optimization: remove the element from the main list to reduce iterations?
            // unset($elements[$key]); // Requires passing by reference and knowing the key
        }
    }
    // Sort branch by name before returning (optional)
    usort($branch, function ($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    return $branch;
}

/**
 * Renders the nested category filter list as HTML.
 *
 * @param array $categories The nested category tree.
 * @param mixed $currentFilterIdentifier The ID or name of the currently active filter.
 * @param array $paramsToKeepForLinks Array of query parameters to preserve in links.
 */
function renderCategoryFilter(array $categories, $currentFilterIdentifier, array $paramsToKeepForLinks): void
{
    if (empty($categories)) {
        return;
    }

    echo '<ul class="filter-items filter-items-count nested-filter">'; // Add nested-filter class

    foreach ($categories as $cat_filter) {
        $is_active_cat = ($currentFilterIdentifier == $cat_filter['category_id'] || $currentFilterIdentifier == $cat_filter['name']);
        // Keep specified params, set category, reset page to 1
        $cat_link = "?" . build_query_string($paramsToKeepForLinks, ['category' => $cat_filter['category_id'], 'page' => 1]);

        echo '<li class="filter-item">';
        echo '<a href="' . htmlspecialchars($cat_link) . '" class="' . ($is_active_cat ? 'active' : '') . '">' . htmlspecialchars($cat_filter['name']) . '</a>';

        // Always render children if they exist, creating a permanent submenu
        if (!empty($cat_filter['children'])) {
            // Recursively call the function to render the next level
            renderCategoryFilter($cat_filter['children'], $currentFilterIdentifier, $paramsToKeepForLinks);
        }

        echo '</li>';
    }

    echo '</ul>';
}

// Function to generate query string preserving filters (moved here for clarity)
function build_query_string(array $current_params, array $new_params): string
{
    return http_build_query(array_merge($current_params, $new_params));
}

/**
 * Gets the ID of a category and all its descendant IDs.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param mixed $identifier The category ID (int) or name/slug (string).
 * @return array An array of category IDs (including the initial one and all descendants), or empty array if not found.
 */
function getCategoryAndDescendantIds(PDO $pdo, $identifier): array
{
    $initialCategoryId = null;

    // 1. Find the initial category ID if identifier is not numeric
    if (!is_numeric($identifier)) {
        try {
            $stmt_find_id = $pdo->prepare("SELECT category_id FROM categories WHERE name = :name LIMIT 1");
            $stmt_find_id->bindParam(':name', $identifier, PDO::PARAM_STR);
            $stmt_find_id->execute();
            $result = $stmt_find_id->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $initialCategoryId = (int) $result['category_id'];
            }
        } catch (PDOException $e) {
            error_log("Error finding category ID by name '{$identifier}': " . $e->getMessage());
            return []; // Return empty on error
        }
    } else {
        $initialCategoryId = (int) $identifier;
    }

    if ($initialCategoryId === null) {
        return []; // Category not found
    }

    // 2. Recursively find all descendant IDs
    $allIds = [$initialCategoryId];
    $idsToSearch = [$initialCategoryId];

    while (!empty($idsToSearch)) {
        $placeholders = implode(',', array_fill(0, count($idsToSearch), '?'));
        $sql_children = "SELECT category_id FROM categories WHERE parent_id IN ($placeholders)";
        $stmt_children = $pdo->prepare($sql_children);
        $stmt_children->execute($idsToSearch);
        $childIds = $stmt_children->fetchAll(PDO::FETCH_COLUMN, 0); // Fetch just the IDs

        if (!empty($childIds)) {
            $childIds = array_map('intval', $childIds); // Ensure they are integers
            $allIds = array_merge($allIds, $childIds);
            $idsToSearch = $childIds; // Search for children of these newly found IDs
        } else {
            $idsToSearch = []; // No more children found
        }
    }

    return array_unique($allIds); // Return unique IDs
}

// --- Build Product Query ---
$params = [];
$join_type = "LEFT JOIN"; // Default to LEFT JOIN
$where_clauses = ["iii.image_path IS NOT NULL AND iii.image_path != ''"];
$category_ids_to_filter = [];

// Category Filtering
if ($category_filter_identifier !== null) {
    $category_ids_to_filter = getCategoryAndDescendantIds($pdo, $category_filter_identifier);
    if (!empty($category_ids_to_filter)) {
        $join_type = "JOIN"; // Change to INNER JOIN when filtering by category
        // Create placeholders for IN clause (?, ?, ?)
        $in_placeholders = implode(',', array_fill(0, count($category_ids_to_filter), '?'));
        $where_clauses[] = "pc.category_id IN ($in_placeholders)";
        $params = array_merge($params, $category_ids_to_filter);
    } else {
        // Category identifier provided but not found, show no results
        $where_clauses[] = "1 = 0"; // Force no results
    }
}

$base_sql = "
    SELECT SQL_CALC_FOUND_ROWS
        ii.InventoryItemID, ii.cost, ii.description, ii.date_added,
        iii.image_path,
        p.productID,
        -- Get category data from categories table joined through product_categories
        cat.name AS cat_name, 
        cat.category_id AS cat_id,
        ii.quantityOnHand,
        COALESCE(po_item.promoPrice, po_prod.promoPrice) AS promoPrice,
        COALESCE(po_item.regularPrice, po_prod.regularPrice) AS regularPrice
    FROM
        inventoryitem ii
    JOIN productitem p ON ii.productItemID = p.productID
    -- Join through product_categories to get category data
    {$join_type} product_categories pc ON p.productID = pc.product_id
    LEFT JOIN categories cat ON pc.category_id = cat.category_id
    LEFT JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1
    LEFT JOIN promooffering po_item ON ii.InventoryItemID = po_item.inventory_item_id
                                    AND po_item.start_date <= NOW() AND po_item.end_date >= NOW()
    LEFT JOIN promooffering po_prod ON p.productID = po_prod.product_id
                                    AND po_prod.inventory_item_id IS NULL
                                    AND po_prod.start_date <= NOW() AND po_prod.end_date >= NOW()
";

// Price Filtering
$price_column_sql = "COALESCE(po_item.promoPrice, po_prod.promoPrice, ii.cost)"; // Use COALESCE for effective price
if ($min_price_filter !== null) {
    $where_clauses[] = "$price_column_sql >= ?";
    $params[] = $min_price_filter;
}
if ($max_price_filter !== null) {
    $where_clauses[] = "$price_column_sql <= ?";
    $params[] = $max_price_filter;
}

// Stock Status Filtering
if ($stock_status_filter === 'in_stock') {
    $where_clauses[] = "ii.quantityOnHand > 0";
} elseif ($stock_status_filter === 'out_of_stock') {
    $where_clauses[] = "ii.quantityOnHand <= 0";
}
// No clause needed for 'all'

// Product Status Filtering
if ($product_status_filter === 'active') {
    $where_clauses[] = "ii.status = 'active'";
} elseif ($product_status_filter === 'inactive') {
    $where_clauses[] = "ii.status = 'inactive'";
}
// No clause needed for 'all'





// Combine WHERE clauses
$sql = $base_sql . " WHERE " . implode(" AND ", $where_clauses);

// Add GROUP BY to ensure each inventory item appears only once
$sql .= " GROUP BY ii.InventoryItemID";

// Sorting
$order_by_sql = " ORDER BY ";
switch ($sort_by) {
    case 'price_asc':
        $order_by_sql .= " $price_column_sql ASC";
        break;
    case 'price_desc':
        $order_by_sql .= " $price_column_sql DESC";
        break;
    case 'name_asc':
        $order_by_sql .= " ii.description ASC";
        break;
    case 'name_desc':
        $order_by_sql .= " ii.description DESC";
        break;
    case 'date_desc':
    default:
        $order_by_sql .= " ii.date_added DESC";
        break;
}
$sql .= $order_by_sql;


// Pagination
$sql .= " LIMIT ? OFFSET ?";

$params[] = $products_per_page;
$params[] = $offset;

// --- Execute Query & Fetch Products ---
$products = [];
$total_products = 0;
try {
    $stmt_products = $pdo->prepare($sql);
    // Bind parameters dynamically based on their type
    $param_index = 1;
    foreach ($params as $param_value) {
        if (is_int($param_value)) {
            $stmt_products->bindValue($param_index++, $param_value, PDO::PARAM_INT);
        } else {
            $stmt_products->bindValue($param_index++, $param_value, PDO::PARAM_STR);
        }
    }

    $stmt_products->execute();
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    // Get total count using FOUND_ROWS()
    $total_products = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();

} catch (PDOException $e) {
    error_log("Error fetching products in shop.php: " . $e->getMessage() . " SQL: " . $sql . " Params: " . print_r($params, true));
    // Display error or message
}

$total_pages = ceil($total_products / $products_per_page);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Shop Products - GoodGuyng.com</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">
    <style>
        /* Add styles from index.php if needed (product grid, etc.) */
        .truncate-2-lines {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            min-height: 2.4em;
        }

        .product-image {
            max-width: 100%;
            height: auto;
            aspect-ratio: 1 / 1;
            object-fit: contain;
        }

        .widget-filter-list a.active {
            font-weight: bold;
            color: #c96;
        }

        /* Basic styling for nested list */
        .nested-filter ul {
            padding-left: 20px;
            list-style: none;
        }

        .nested-filter {
            padding-left: 0;
            list-style: none;
        }

        /* Style for highlighted search term */
        .highlight {
            background-color: yellow;
            font-weight: bold;
        }

        /* Style for category filter scroll */
        #category-filter-list {
            max-height: 300px;
            /* Adjust this height as needed */
            overflow-y: auto;
        }

        /* Remove default padding/bullets */
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include 'header_main.php'; ?>

        <main class="main">

            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Shop</li>

                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-9">
                            <div class="toolbox">
                                <div class="toolbox-left">
                                    <div class="toolbox-info">
                                        Showing <span><?= count($products) ?> of <?= $total_products ?></span> Products
                                    </div><!-- End .toolbox-info -->
                                </div><!-- End .toolbox-left -->

                                <div class="toolbox-right">
                                    <div class="toolbox-sort">
                                        <label for="sortby">Sort by:</label>
                                        <div class="select-custom">
                                            <select name="sortby" id="sortby" class="form-control"
                                                onchange="this.form.submit()">
                                                <option value="date_desc" <?= $sort_by == 'date_desc' ? 'selected' : '' ?>>
                                                    Newest</option>
                                                <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : '' ?>>
                                                    Price: Low to High</option>
                                                <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                                <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>
                                                    Name: A to Z</option>
                                                <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>
                                                    Name: Z to A</option>
                                            </select>
                                        </div>
                                    </div><!-- End .toolbox-sort -->
                                </div><!-- End .toolbox-right -->
                            </div><!-- End .toolbox -->

                            <div class="products mb-3">
                                <div class="row justify-content-center">
                                    <?php if (!empty($products)): ?>
                                        <?php foreach ($products as $row):
                                            $itemId = $row['InventoryItemID'];
                                            $imagePath = $row['image_path'] ?? 'assets/images/products/product-default.jpg'; // Fallback image
                                            $categoryName = $row['cat_name'] ?? 'Uncategorized';
                                            $categoryId = $row['cat_id'];
                                            $isPromo = !empty($row['promoPrice']);
                                            $isNew = (strtotime($row['date_added']) > strtotime('-30 days'));
                                            $isInStock = ($row['quantityOnHand'] ?? 0) > 0;
                                            $ratingWidth = $Orvi->get_rating_($itemId);
                                            $reviewCount = $Orvi->get_rating_review_number($itemId);
                                            ?>
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <?php // Changed col-lg-4 to col-lg-3 to show 4 products per row on large screens ?>
                                                <div class="product product-7 text-center">
                                                    <figure class="product-media">
                                                        <?php if ($isPromo): ?><span
                                                                class="product-label label-sale">Sale</span><?php endif; ?>
                                                        <?php if (!$isInStock): ?><span class="product-label label-out">Out of
                                                                Stock</span><?php endif; ?>
                                                        <?php if ($isNew): ?><span
                                                                class="product-label label-sale">Sale</span><?php endif; ?>
                                                        <?php if ($isNew): ?><span
                                                                class="product-label label-new">New</span><?php endif; ?>
                                                        <a href="product-detail.php?itemid=<?= $itemId ?>">
                                                            <img src="<?= htmlspecialchars($imagePath) ?>"
                                                                alt="<?= htmlspecialchars($row['description']) ?>"
                                                                class="product-image" loading="lazy">
                                                        </a>
                                                        <div class="product-action-vertical">
                                                            <a href="#" data-product-id="<?= $itemId ?>"
                                                                class="btn-product-icon btn-wishlist btn-expandable"
                                                                title="Add to Wishlist"><span>add to wishlist</span></a>
                                                        </div>
                                                        <div class="product-action">
                                                            <?php if ($isInStock): ?>
                                                                <a href="#" product-info="<?= $itemId ?>"
                                                                    class="submit-cart btn-product btn-cart"
                                                                    title="Add to cart"><span>add to cart</span></a>
                                                            <?php else: ?>
                                                                <a href="#" class="btn-product btn-cart disabled"
                                                                    style="cursor: not-allowed;"><span>Out of Stock</span></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </figure>
                                                    <div class="product-body">
                                                        <div class="product-cat"><a
                                                                href="shop.php?category=<?= $categoryId ?>"><?= htmlspecialchars($categoryName) ?></a>
                                                        </div>
                                                        <h3 class="product-title truncate-2-lines"><a
                                                                href="product-detail.php?itemid=<?= $itemId ?>"><?= htmlspecialchars($row['description']) ?></a>
                                                        </h3>
                                                        <?php // --- Price Display Logic --- ?>
                                                        <div class="product-price">
                                                            <?php if ($isPromo && !empty($row['promoPrice'])): ?>
                                                                <span
                                                                    class="new-price">₦<?= number_format($row['promoPrice'], 2) ?></span>
                                                                <span class="old-price">Was
                                                                    ₦<?= number_format($row['regularPrice'] ?? $row['cost'], 2) ?></span>
                                                            <?php else: ?>
                                                                <span
                                                                    class="new-price">₦<?= number_format($row['regularPrice'] ?? $row['cost'], 2) ?></span>
                                                            <?php endif; ?>
                                                        </div><!-- End .product-price -->
                                                        <?php // --- End Price Display Logic --- ?>
                                                        <div class="ratings-container justify-content-center">
                                                            <div class="ratings">
                                                                <div class="ratings-val" style="width: <?= $ratingWidth ?>%;">
                                                                </div>
                                                            </div>
                                                            <span class="ratings-text">( <?= $reviewCount ?> Reviews )</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="col-12 text-center">No products found matching your criteria.</p>
                                    <?php endif; ?>
                                </div><!-- End .row -->
                            </div><!-- End .products -->

                            <?php // --- Pagination ---
                            if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php // Prepare current filters for pagination links
                                            $current_filters_for_pagination = [];
                                            if ($category_filter_identifier)
                                                $current_filters_for_pagination['category'] = $category_filter_identifier;
                                            if ($sort_by)
                                                $current_filters_for_pagination['sort_by'] = $sort_by;
                                            if ($min_price_filter !== null)
                                                $current_filters_for_pagination['min_price'] = $min_price_filter;
                                            if ($max_price_filter !== null)
                                                $current_filters_for_pagination['max_price'] = $max_price_filter;
                                            if ($stock_status_filter)
                                                $current_filters_for_pagination['stock_status'] = $stock_status_filter;
                                            if ($product_status_filter)
                                                $current_filters_for_pagination['product_status'] = $product_status_filter;
                                            if ($max_price_filter !== null)
                                                $current_filters_for_pagination['max_price'] = $max_price_filter;
                                            ?>
                                        <?php if ($page > 1): ?>
                                            <li class="page-item"><a class="page-link page-link-prev"
                                                    href="?<?= build_query_string($current_filters_for_pagination, ['page' => $page - 1]) ?>"
                                                    aria-label="Previous"><span aria-hidden="true"><i
                                                            class="icon-long-arrow-left"></i></span>Prev</a></li>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>" aria-current="page"><a
                                                    class="page-link"
                                                    href="?<?= build_query_string($current_filters_for_pagination, ['page' => $i]) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item"><a class="page-link page-link-next"
                                                    href="?<?= build_query_string($current_filters_for_pagination, ['page' => $page + 1]) ?>"
                                                    aria-label="Next">Next <span aria-hidden="true"><i
                                                            class="icon-long-arrow-right"></i></span></a></li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        </div><!-- End .col-lg-9 -->
                        <aside class="col-lg-3 order-lg-first">
                            <div class="sidebar sidebar-shop">
                                <div class="widget widget-clean">
                                    <label>Filters:</label>
                                    <a href="shop.php" class="sidebar-filter-clear">Clean All</a>
                                </div><!-- End .widget widget-clean -->

                                <div class="widget widget-collapsible">
                                    <h3 class="widget-title">
                                        <a data-toggle="collapse" href="#widget-1" role="button" aria-expanded="true"
                                            aria-controls="widget-1">
                                            Category
                                        </a>
                                    </h3><!-- End .widget-title -->

                                    <div class="collapse show" id="widget-1">
                                        <div class="widget-body">
                                            <div class="mb-2">
                                                <input type="text" class="form-control form-control-sm"
                                                    id="category-search-input" placeholder="Search Categories...">
                                            </div>
                                            <div id="category-filter-list">
                                                <?php // Wrap the list for easier selection ?>

                                                <?php
                                                // Build the tree from the flat list fetched earlier
                                                $category_tree = buildCategoryTree($all_categories_for_filter);
                                                // Prepare current filters for category links
                                                $current_filters_for_category = [];
                                                if ($sort_by)
                                                    $current_filters_for_category['sort_by'] = $sort_by;
                                                if ($min_price_filter !== null)
                                                    $current_filters_for_category['min_price'] = $min_price_filter;
                                                if ($max_price_filter !== null)
                                                    $current_filters_for_category['max_price'] = $max_price_filter;
                                                if ($stock_status_filter)
                                                    $current_filters_for_category['stock_status'] = $stock_status_filter;
                                                if ($product_status_filter)
                                                    $current_filters_for_category['product_status'] = $product_status_filter;
                                                if ($max_price_filter !== null)
                                                    $current_filters_for_category['max_price'] = $max_price_filter;
                                                // Render the nested list
                                                renderCategoryFilter($category_tree, $category_filter_identifier, $current_filters_for_category);
                                                ?>
                                            </div><!-- End .widget-body -->
                                        </div> <?php // End #category-filter-list ?>
                                    </div><!-- End .collapse -->
                                </div><!-- End .widget -->

                                <div class="widget widget-collapsible">
                                    <h3 class="widget-title">
                                        <a data-toggle="collapse" href="#widget-price" role="button"
                                            aria-expanded="true" aria-controls="widget-price">
                                            Price
                                        </a>
                                    </h3><!-- End .widget-title -->

                                    <div class="collapse show" id="widget-price">
                                        <div class="widget-body">
                                            <form action="shop.php" method="GET">
                                                <?php // Hidden fields to preserve other filters ?>
                                                <?php if ($category_filter_identifier): ?>
                                                    <input type="hidden" name="category"
                                                        value="<?= htmlspecialchars($category_filter_identifier) ?>">
                                                <?php endif; ?>
                                                <?php if ($sort_by): ?>
                                                    <input type="hidden" name="sort_by"
                                                        value="<?= htmlspecialchars($sort_by) ?>">
                                                <?php endif; ?>
                                                <?php if ($stock_status_filter): ?>
                                                    <input type="hidden" name="stock_status"
                                                        value="<?= htmlspecialchars($stock_status_filter) ?>">
                                                <?php endif; ?>

                                                <input type="hidden" name="page" value="1">
                                                <?php // Reset page on filter change ?>

                                                <div class="filter-price">
                                                    <div class="filter-price-text mb-2"> Price Range:</div>
                                                    <input type="number" name="min_price" class="form-control mb-1"
                                                        placeholder="Min ₦"
                                                        value="<?= htmlspecialchars($min_price_filter ?? '') ?>"
                                                        min="0">
                                                    <input type="number" name="max_price" class="form-control mb-2"
                                                        placeholder="Max ₦"
                                                        value="<?= htmlspecialchars($max_price_filter ?? '') ?>"
                                                        min="0">
                                                    <button type="submit"
                                                        class="btn btn-primary btn-sm btn-block">Filter</button>
                                                </div><!-- End .filter-price -->
                                            </form>
                                        </div><!-- End .widget-body -->
                                    </div><!-- End .collapse -->
                                </div><!-- End .widget -->

                            </div><!-- End .sidebar sidebar-shop -->
                        </aside><!-- End .col-lg-3 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <footer class="footer footer-2">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div>
    <!-- Sign in / Register Modal -->
    <?php include "login-modal.php"; ?>

    <?php include "jsfile.php"; ?>
    <!-- Add to Cart/Wishlist JS (copy from index.php or include shared file) -->
    <script src="assets/js/nouislider.min.js"></script>
    <script src="assets/js/wNumb.js"></script>
    <script src="js/add-to-cart.js"></script>

    <script>
        // Add Cart/Wishlist handlers (same as index.php)
        $(document).ready(function () {



            // Optional: Auto-submit form when sort dropdown changes
            $('#sortby').closest('form').on('change', '#sortby', function () {
                // If the select is not already inside a form that submits via GET,
                // you might need to manually construct the URL and navigate:
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('sort_by', $(this).val());
                // Reset page to 1 when sorting changes
                currentUrl.searchParams.set('page', '1');
                window.location.href = currentUrl.toString();
            });


            // --- Price Slider ---



            // --- Category Filter Input ---
            $('#category-search-input').on('keyup', function () {
                var searchTerm = $(this).val().toLowerCase().trim();
                var $topLevelList = $('#category-filter-list > ul.nested-filter'); // Target the first UL

                // --- Reset highlights and visibility before filtering ---
                $topLevelList.find('li.filter-item').each(function () {
                    var $item = $(this);
                    var $link = $item.children('a').first();
                    // Restore original text if it was stored
                    if ($link.data('original-text')) {
                        $link.html($link.data('original-text'));
                    }
                    $item.show(); // Ensure all are visible initially before filtering
                });

                // If search term is empty, we're done (already reset)
                if (searchTerm === '') {
                    return;
                }

                // Iterate through each TOP-LEVEL list item (li)
                $topLevelList.children('li.filter-item').each(function () {
                    var $topLevelItem = $(this);
                    var matchFoundInSubtree = false;

                    // Check all links within this top-level item and its children
                    $topLevelItem.find('a').each(function () {
                        var $link = $(this);
                        var linkText = $(this).text().toLowerCase();

                        // Store original text if not already stored
                        if (!$link.data('original-text')) {
                            $link.data('original-text', $link.html());
                        }

                        if (linkText.includes(searchTerm)) {
                            matchFoundInSubtree = true;
                            // Apply highlighting
                            var originalText = $link.data('original-text');
                            var highlightedText = originalText.replace(new RegExp('(' + searchTerm.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')', 'gi'), '<span class="highlight">$1</span>');
                            $link.html(highlightedText);
                            // Don't return false here, continue checking other links in the subtree
                        }
                    });

                    // Show/hide the entire top-level item based on whether a match was found inside it
                    $topLevelItem.toggle(matchFoundInSubtree);
                });
            });
        });
    </script>


</body>

</html>