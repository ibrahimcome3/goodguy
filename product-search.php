<?php
session_start(); // Ensure session is started for cart and other functionalities
require_once "includes.php";

// --- Input and Pagination Setup ---
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$pageno = isset($_GET['pageno']) ? (int) $_GET['pageno'] : 1;
$no_of_records_per_page = 20; // Adjusted for potentially more items
$offset = ($pageno - 1) * $no_of_records_per_page;

$total_rows = 0;
$total_pages = 0;
$products_rs = null;

if (!empty($searchQuery)) {
    $searchTerm = "%" . strtolower($searchQuery) . "%";

    // --- Count Total Matching Products (PDO Prepared Statement) ---
    $sql_count = "SELECT COUNT(DISTINCT ii.InventoryItemID) as c
                  FROM inventoryitem ii
                  JOIN productitem pi ON ii.productItemID = pi.productID
                  LEFT JOIN categories cat ON pi.category = cat.category_id
                  LEFT JOIN brand b ON pi.brand = b.brandID
                  WHERE (LOWER(ii.barcode) LIKE :term_bc
                         OR LOWER(ii.description) LIKE :term_desc
                         OR LOWER(pi.product_name) LIKE :term_pn
                         OR LOWER(cat.name) LIKE :term_cat
                         OR LOWER(b.Name) LIKE :term_brand)";

    try {
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute([
            ':term_bc' => $searchTerm,
            ':term_desc' => $searchTerm,
            ':term_pn' => $searchTerm,
            ':term_cat' => $searchTerm,
            ':term_brand' => $searchTerm,
        ]);
        $total_rows_result = $stmt_count->fetch(PDO::FETCH_ASSOC);

        // Corrected debug output (optional, can be removed)
        // echo "<h1>Count: " . ($total_rows_result['c'] ?? 0) . "</h1>";
        // exit;

        if ($total_rows_result) {
            $total_rows = (int) $total_rows_result['c'];
        }
    } catch (PDOException $e) {
        error_log("Error counting search results: " . $e->getMessage());
        // Handle error appropriately, maybe show a message to the user
    }
    $total_pages = ceil($total_rows / $no_of_records_per_page);

    // --- Fetch Products for the Current Page (PDO Prepared Statement) ---
    if ($total_rows > 0) {
        $sql_results = "SELECT ii.*, pi.product_name, pi.productID as baseProductID, 
                               b.Name, cat.name as category_name, cat.category_id as cat_id_for_link,
                               img.image_path as primary_image_path
                        FROM inventoryitem ii
                        JOIN productitem pi ON ii.productItemID = pi.productID
                        LEFT JOIN categories cat ON pi.category = cat.category_id
                        LEFT JOIN brand b ON pi.brand = b.brandID
                        LEFT JOIN inventory_item_image img ON ii.InventoryItemID = img.inventory_item_id AND img.is_primary = 1
                        WHERE (LOWER(ii.barcode) LIKE :term_bc_res
                               OR LOWER(ii.description) LIKE :term_desc_res
                               OR LOWER(pi.product_name) LIKE :term_pn_res
                               OR LOWER(cat.name) LIKE :term_cat_res
                               OR LOWER(b.Name) LIKE :term_brand_res)
                        ORDER BY ii.date_added DESC -- Or a relevance score if using full-text search
                        LIMIT :limit_val OFFSET :offset_val"; // Use unique names for limit/offset too
        try {
            $stmt_results = $pdo->prepare($sql_results);
            $stmt_results->execute([
                ':term_bc_res' => $searchTerm,
                ':term_desc_res' => $searchTerm,
                ':term_pn_res' => $searchTerm,
                ':term_cat_res' => $searchTerm,
                ':term_brand_res' => $searchTerm,
                ':offset_val' => $offset,
                ':limit_val' => $no_of_records_per_page,
            ]);
            $products_rs = $stmt_results; // Keep as PDOStatement to fetch in loop
        } catch (PDOException $e) {
            error_log("Error fetching search results: " . $e->getMessage());
            // Handle error
        }
    }
}

// Pagination variables
$prev_page = $pageno - 1;
$next_page = $pageno + 1;

if (isset($_GET['pageno'])) {
    $pageno = $_GET['pageno'];
} else {
    $pageno = 1;
}
?>
<!DOCTYPE html>
<html lang="en">



<head>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Search</title>
    <?php include "htlm-includes.php/metadata.php"; ?>

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <style>
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            /* number of lines to show */
            line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .flexbox {
            display: flex;
            flex-wrap: wrap;
            min-height: 200px;
            flex-grow: 1;
            flex-shrink: 0;
            flex-basis: 220px;
        }
    </style>
</head>


<body>
    <div class="page-wrapper">
        <?php include "header_main.php" ?>

        <main class="main">
            <!-- End .page-header -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <?php if (empty($searchQuery)) {
                        echo "<div class='text-center my-5'><p>Please enter a search term.</p> <a href='index.php' class='btn btn-primary'>Go to Homepage</a></div>";
                    } elseif ($total_rows == 0) {
                        echo "<div class='text-center my-5'><p>No results found for your search term: <b>" . htmlspecialchars($searchQuery) . "</b></p> <a href='index.php' class='btn btn-primary'>Try another search or Go to Homepage</a></div>";
                    } ?>
                    <div class="row">
                        <div class="col-lg-9">
                            <div class="toolbox">
                                <div class="toolbox-left">
                                    <div class="toolbox-info">

                                        <?php
                                        $displayed_items = min($total_rows, $pageno * $no_of_records_per_page);
                                        //echo "Display results " . $displayed_items . "/" . $total_rows;
                                        ?>
                                        Showing <span><?= $displayed_items ?> of <?= $total_rows ?></span> search
                                        results
                                    </div><!-- End .toolbox-info -->
                                </div><!-- End .toolbox-left -->
                                <!--
                                <div class="toolbox-right">
                                    <div class="toolbox-sort">
                                        <label for="sortby">Sort by:</label>
                                        <div class="select-custom">
                                            <select name="sortby" id="sortby" class="form-control">
                                                <option value="popularity" selected="selected">Most Popular</option>
                                                <option value="rating">Most Rated</option>
                                                <option value="date">Date</option>
                                            </select>
                                        </div>
                                    </div><!-- End .toolbox-sort -->

                                <!-- </div><!-- End .toolbox-right -->

                            </div><!-- End .toolbox -->
                            <?php
                            // Objects likely already instantiated in includes.php or at the top
                            // $product_obj = new ProductItem($pdo);
                            // $promotion = new Promotion($pdo);
                            // $Orvi = new Review($pdo); // For reviews
                            ?>
                            <div class="products mb-3">
                                <div class="row justify-content-center">
                                    <?php
                                    if ($products_rs) { // Check if $products_rs is not null
                                        while ($row = $products_rs->fetch(PDO::FETCH_ASSOC)) {
                                            $itemId = $row['InventoryItemID'];
                                            //echo $row['primary_image_path'];
                                            $imagePath = !empty($row['primary_image_path']) && file_exists(ltrim($row['primary_image_path'], './')) ? htmlspecialchars(ltrim($row['primary_image_path'], './')) : 'assets/images/products/default-product.png';
                                            $productName = htmlspecialchars($row['product_name'] ?? $row['description']); // Fallback to description
                                            $categoryName = htmlspecialchars($row['category_name'] ?? 'Uncategorized');
                                            $categoryIdForLink = $row['cat_id_for_link'] ?? '#';
                                            $isNew = (strtotime($row['date_added']) > strtotime('-30 days'));

                                            // Promotion check (assuming $promotion object is available from includes.php)
                                            $isPromo = $promotion->check_if_item_is_in_inventory_promotion($itemId);
                                            $displayPrice = $row['cost'];
                                            $oldPrice = null;
                                            if ($isPromo) {
                                                $displayPrice = $promotion->get_promoPrice_price($itemId);
                                                $oldPrice = $promotion->get_regular_price($itemId);
                                            }
                                            ?>
                                            <div class="col-6 col-md-4 col-lg-4 col-xl-3">
                                                <div class="product product-7 text-center">
                                                    <figure class="product-media">
                                                        <?php if ($isPromo) { ?>
                                                            <span class="product-label label-sale">Sale</span>
                                                        <?php } ?>
                                                        <?php if ($isNew) { // Assuming $isNew is determined earlier ?>
                                                            <span class="product-label label-top">NEW</span>
                                                        <?php } ?>
                                                        <a href="product-detail.php?itemid=<?= $itemId ?>">
                                                            <img src="<?= $imagePath ?>" alt="<?= $productName ?>"
                                                                class="product-image">
                                                        </a>

                                                        <div class="product-action-vertical">
                                                            <a href="#" data-product-id="<?= $itemId ?>"
                                                                class="btn-product-icon btn-wishlist btn-expandable"><span>add
                                                                    to wishlist</span></a>
                                                            <a href="<?= $imagePath ?>" class="btn-product-icon btn-quickview"
                                                                title="Quick view"><span>Quick view</span></a>
                                                        </div><!-- End .product-action-vertical -->

                                                        <div class="product-action">
                                                            <a href="#" class="submit-cart btn-product btn-cart"
                                                                product-info="<?= $row['InventoryItemID'] ?>"><span>add to
                                                                    cart</span></a>
                                                            <!-- Ensure product-info has the correct ID -->
                                                        </div><!-- End .product-action -->
                                                    </figure><!-- End .product-media -->

                                                    <div class="product-body">
                                                        <div class="product-cat text-center">
                                                            <a
                                                                href="category.php?id=<?= $categoryIdForLink ?>"><?= $categoryName ?></a>
                                                        </div><!-- End .product-cat -->
                                                        <h3 class="product-title truncate"><a
                                                                href="product-detail.php?itemid=<?= $itemId ?>"><?= $productName ?></a>
                                                        </h3>
                                                        <!-- End .product-title -->
                                                        <div class="product-price">
                                                            <?php if ($isPromo && $oldPrice): ?>
                                                                <span
                                                                    class="new-price">&#8358;<?= number_format($displayPrice, 2) ?></span>
                                                                <span class="old-price">Was
                                                                    &#8358;<?= number_format($oldPrice, 2) ?></span>
                                                            <?php else: ?>
                                                                <span
                                                                    class="price">&#8358;<?= number_format($displayPrice, 2) ?></span>
                                                            <?php endif; ?>
                                                        </div><!-- End .product-price -->
                                                        <?php
                                                        // $Orvi should be available from includes.php
                                                        ?>
                                                        <div class="ratings-container">
                                                            <div class="ratings">
                                                                <div class="ratings-val"
                                                                    style="width: <?= $Orvi->get_rating_($itemId) ?>%;">
                                                                </div><!-- End .ratings-val -->
                                                            </div><!-- End .ratings -->

                                                            <span class="ratings-text">(
                                                                <?= $Orvi->get_rating_review_number($itemId) ?>
                                                                Reviews )</span>
                                                        </div><!-- End .rating-container -->
                                                    </div><!-- End .product-body -->
                                                </div><!-- End .product -->
                                            </div><!-- End .col-sm-6 col-lg-4 col-xl-3 -->
                                            <?php
                                        } // end while
                                    } // end if ($products_rs)
                                    ?>


                                </div><!-- End .row -->



                            </div><!-- End .products -->


                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    if ($total_pages > 1) { // Only show pagination if there's more than one page
                                        $queryString = "?q=" . urlencode($searchQuery);

                                        // Previous button
                                        if ($pageno > 1) {
                                            echo "<li class='page-item'><a class='page-link' href='product-search.php{$queryString}&pageno={$prev_page}'>Prev</a></li>";
                                        } else {
                                            echo "<li class='page-item disabled'><span class='page-link'>Prev</span></li>";
                                        }

                                        // Page numbers
                                        $skipped = false;
                                        for ($page_number = 1; $page_number <= $total_pages; $page_number++) {
                                            if ($page_number < 3 || $total_pages - $page_number < 2 || abs($pageno - $page_number) < 2 || $total_pages <= 5) {
                                                if ($skipped) {
                                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                                    $skipped = false;
                                                }
                                                $activeClass = ($page_number == $pageno) ? "active" : "";
                                                echo "<li class='page-item {$activeClass}'><a class='page-link' href='product-search.php{$queryString}&pageno={$page_number}'>{$page_number}</a></li>";
                                            } else {
                                                $skipped = true;
                                            }
                                        }
                                        if ($skipped) { // If the last pages were skipped and we need a final ellipsis
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            // Optionally show the last page if not already shown
                                            if ($pageno < $total_pages - 2 && $total_pages > 5) {
                                                echo "<li class='page-item'><a class='page-link' href='product-search.php{$queryString}&pageno={$total_pages}'>{$total_pages}</a></li>";
                                            }
                                        }

                                        // Next button
                                        if ($pageno < $total_pages) {
                                            echo "<li class='page-item'><a class='page-link' href='product-search.php{$queryString}&pageno={$next_page}'>Next</a></li>";
                                        } else {
                                            echo "<li class='page-item disabled'><span class='page-link'>Next</span></li>";
                                        }
                                    }
                                    ?>
                                </ul>
                            </nav>
                        </div><!-- End .col-lg-9 -->

                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div>

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

    <?php include "mobile-menue-index-page.php"; ?>
    <!-- Sign in / Register Modal -->
    <?php include "login-modal.php"; ?>





    <!-- Main JS File -->

    <!-- Plugins JS File -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/wNumb.js"></script>
    <script src="assets/js/bootstrap-input-spinner.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/nouislider.min.js"></script>
    <script src="assets/js/jquery.plugin.min.js"></script>
    <script src="assets/js/jquery.countdown.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/demos/demo-13.js"></script>
    <script src="js/add-to-cart.js"></script>
    <?php // Added add-to-cart.js ?>
    <?php // Assuming login.js is still needed ?>

    <!-- Main JS File -->

</body>



</html>