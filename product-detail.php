<!DOCTYPE html>
<?php
// SECTION 1: INITIALIZATION & SECURITY
// -----------------------------------------------------------------------------
if (session_status() == PHP_SESSION_NONE) {
        session_start();
} // Ensure session is started for flash messages

$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

include "includes.php"; // Should provide $mysqli, $pdo, and class autoloading/definitions

// SECTION 2: INPUT PROCESSING & VALIDATION
// -----------------------------------------------------------------------------
$currentInventoryItemId = isset($_GET['itemid']) ? (int) $_GET['itemid'] : 0;

if ($currentInventoryItemId <= 0 || !$invt->check_item_in_existance($currentInventoryItemId)) {
        // Consider a more user-friendly error page or a dedicated error display mechanism
        echo "Item does not exist or has an invalid ID. <a href='index.php'>Click here to go to the home page.</a>";
        exit();
}

// SECTION 3: CORE DATA FETCHING
// -----------------------------------------------------------------------------
// Fetch main product and inventory details using prepared statements
$sql_main_product = "SELECT pi.*, ii.* 
                     FROM productitem pi 
                     LEFT JOIN inventoryitem ii ON pi.productID = ii.productItemID 
                     WHERE ii.InventoryItemID = ?";
$stmt_main_product = $mysqli->prepare($sql_main_product);

if (!$stmt_main_product) {
        // Log error and show user-friendly message
        error_log("Prepare failed for main product query: " . $mysqli->error);
        echo "An error occurred while fetching product details. Please try again later. <a href='index.php'>Return to homepage.</a>";
        exit();
}

$stmt_main_product->bind_param("i", $currentInventoryItemId);
$stmt_main_product->execute();
$result_main_product = $stmt_main_product->get_result();

if ($result_main_product->num_rows === 0) {
        echo "Product details not found. <a href='index.php'>Click here to go to the home page.</a>";
        exit();
}
$row = $result_main_product->fetch_assoc(); // $row will contain all fetched data
$stmt_main_product->close();

// Extract key data into variables for easier use in the view (consistent with old $row usage)
$category_id_from_inventory = $row['category']; // This is likely the category_id from inventoryitem
$main_product_id_for_item = $row['productID'];   // This is productitem.productID (used as $pid and $icudrop previously)
$product_info_text = $row['product_information'];
$product_name_text = $row['product_name'];
$shipping_returns_text = $row['shipping_returns'];
$description_text = $row['description']; // Assuming 'description' from inventoryitem is the primary one
$cost_price = $row['cost'];

// SECTION 4: SECONDARY DATA PREPARATION
// -----------------------------------------------------------------------------

// Wishlist
$wished_list_count = 0;
if (isset($_SESSION['uid'])) {
        $wished_list_count = $wishlist->get_wished_list_item((int) $_SESSION["uid"]);
}

// Reviews
$Orvi = new Review($pdo);

// Toast Messages (for cart/wishlist actions)
$toast_message_for_js = null;
$toast_icon_class_for_js = null;
if (isset($_GET['toast_action'])) {
        switch ($_GET['toast_action']) {
                case 'cart_add_success':
                        $toast_message_for_js = "Item successfully added to your cart!";
                        $toast_icon_class_for_js = "fas fa-shopping-cart";
                        break;
                case 'wishlist_add_success':
                        $toast_message_for_js = "Item added to your wishlist!";
                        $toast_icon_class_for_js = "fas fa-heart";
                        break;
        }
}

// Cart
$num_items_in_cart = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Promotions
$is_on_promotion = $promotion->check_if_item_is_in_inventory_promotion($currentInventoryItemId);
$display_price = $cost_price;
$old_price = null;
if ($is_on_promotion) {
        $display_price = $promotion->get_promoPrice_price($currentInventoryItemId);
        $old_price = $promotion->get_regular_price($currentInventoryItemId); // Original price before promo
}

// Variations
$var_obj = new Variation($pdo);
$colorVariations = $var_obj->get_color_variations_for_product_from_sku($main_product_id_for_item);
$sizeVariations = $var_obj->get_size_variations_for_product_from_sku($main_product_id_for_item);

// "New" Product Label
$is_new_product = in_array($currentInventoryItemId, $product_obj->get_all_product_items_that_are_less_than_one_month());

// Related Categories for Display
$cat_obj = new Category($pdo); // Changed $pdp to $pdo
$related_categories_stmt = $cat_obj->get_related_categories($currentInventoryItemId);

// Review Permissions
$can_add_review = false;
$has_reviewed_message = '';
if (isset($_SESSION['uid']) && $currentInventoryItemId > 0) {
        if ($Orvi->hasUserReviewedProduct($currentInventoryItemId, (int) $_SESSION['uid'])) {
                $has_reviewed_message = '<p class="text-muted small">You have already reviewed this product.</p>';
        } else {
                $can_add_review = true;
        }
} elseif ($currentInventoryItemId > 0) {
        $review_product_id_for_link = $main_product_id_for_item;
        $redirect_url_for_review = urlencode("product-review.php?inventory-item=$currentInventoryItemId&product_id=$review_product_id_for_link");
        $has_reviewed_message = '<p class="text-muted small"><a href="login.php?redirect_url=' . $redirect_url_for_review . '">Login</a> to write a review.</p>';
}

// SECTION 5: HELPER FUNCTIONS (If specific to this page)
// -----------------------------------------------------------------------------
/**
 * Fetches related products.
 * Note: It's generally better to pass DB connection as a parameter.
 * This function might be for AJAX or a different part of the site if not directly used in this page's render.
 */
function getrelatedproducts($category_id, $exclude_item_id, $db_connection)
{
        // include "conn.php"; // Avoid include inside function
        $sql = "SELECT * FROM `inventoryitem` WHERE `category` = ? AND InventoryItemID != ? LIMIT 5";
        $stmt = $db_connection->prepare($sql);
        if (!$stmt) {
                error_log("Prepare failed for getrelatedproducts: " . $db_connection->error);
                return null;
        }
        $stmt->bind_param("ii", $category_id, $exclude_item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        // $stmt->close(); // Closing here would prevent fetching results if $result is returned directly
        return $result; // The caller should close the statement after fetching if needed, or fetch all data here.
}

?>

<html lang="en">


<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Product Page</title>
        <?php include "htlm-includes.php/metadata.php"; ?>
        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <!-- Font Awesome for icons (if you use them in toasts) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <style>
                /* --- Theme's SweetAlert2 Toast Styles (Example) --- */
                .themed-toast-popup {
                        background-color: #f0f0f0 !important;
                        border-left: 3px solid #007bff !important;
                        border-radius: 2px !important;
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
                        padding: 0.55em 0.5em !important;
                }

                .themed-toast-html-icon i {
                        font-size: 1.2em;
                        /* Adjust icon size in toast */
                        color: #007bff;
                        /* Match border or use another theme color */
                        margin-right: 8px;
                }

                .themed-toast-html-icon {
                        font-size: 0.95em;
                        /* Adjust text size in toast */
                }
        </style>
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
                <?php

                include "header-for-other-pages.php";
                ?>
                <main class="main">
                        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                                <div class="container d-flex align-items-center">
                                        <ol class="breadcrumb">
                                                <?php echo breadcrumbs(); ?>
                                        </ol>
                                        <!-- Product Pager (Example, if needed)
                                        <nav class="product-pager ml-auto" aria-label="Product">
                                                <a class="product-pager-link product-pager-prev" href="#"
                                                        aria-label="Previous" tabindex="-1">
                                                        <i class="icon-angle-left"></i>
                                                        <span>Prev</span>
                                                </a>

                                                <a class="product-pager-link product-pager-next"
                                                        href="#" aria-label="Next"
                                                        tabindex="-1">
                                                        <span>Next</span>
                                                        <i class="icon-angle-right"></i>
                                                </a>
                                        </nav> -->
                                </div><!-- End .container -->
                        </nav><!-- End .breadcrumb-nav -->

                        <div class="page-content">
                                <div class="container">
                                        <div class="row">
                                                <div class="col-lg-9">
                                                        <div class="product-details-top">
                                                                <div class="row">
                                                                        <div class="col-md-6 main-product-cover"
                                                                                product-info="<?= $currentInventoryItemId ?>"
                                                                                product-cat="<?= $category_id_from_inventory ?>">
                                                                                <div class="product-gallery">
                                                                                        <figure
                                                                                                class="product-main-image">
                                                                                                <?php if ($promotion->check_if_item_is_in_promotion($main_product_id_for_item) != null) { ?>
                                                                                                        <span
                                                                                                                class="product-label label-sale">Sale</span>
                                                                                                <?php } ?>

                                                                                                <?php
                                                                                                if ($is_new_product) { ?>
                                                                                                        <span
                                                                                                                class="product-label label-top">NEW</span>
                                                                                                <?php } ?>
                                                                                                <?php
                                                                                                if ($product_obj->check_dirtory_resized_600($main_product_id_for_item, $currentInventoryItemId)) {
                                                                                                        $pi = glob("products/product-$main_product_id_for_item/product-$main_product_id_for_item-image/inventory-$main_product_id_for_item-$currentInventoryItemId/resized_600/" . '*.{jpg,gif,png,jpeg}', GLOB_BRACE);

                                                                                                        $p = $pi[0];
                                                                                                } else {
                                                                                                        $p = $product_obj->get_cover_image($currentInventoryItemId);
                                                                                                }
                                                                                                ?>
                                                                                                <img id="product-zoom"
                                                                                                        src="<?= $p; ?>"
                                                                                                        data-image="<?= $p ?>"
                                                                                                        data-zoom-image="<?= $product_obj->get_cover_image($currentInventoryItemId); ?>"
                                                                                                        alt="product image">

                                                                                                <a href="#"
                                                                                                        id="btn-product-gallery"
                                                                                                        class="btn-product-gallery">
                                                                                                        <i
                                                                                                                class="icon-arrows"></i>
                                                                                                </a>
                                                                                        </figure>
                                                                                        <!-- End .product-main-image -->

                                                                                        <div id="product-zoom-gallery"
                                                                                                class="product-image-gallery">
                                                                                                <?php $p_obj = new ProductItem($pdo); // Pass the $pdo connection
                                                                                                $stmt = $p_obj->get_other_images_of_item_in_inventory($currentInventoryItemId);

                                                                                                if ($stmt != null) {
                                                                                                        while ($r = $stmt->fetch()) { ?>
                                                                                                                <a class="product-gallery-item"
                                                                                                                        href="#"
                                                                                                                        data-image="<?= $r['image_path'] ?>"
                                                                                                                        data-zoom-image="<?= $r['image_path'] ?>">
                                                                                                                        <?php

                                                                                                                        if ($p_obj->check_dirctory_resized($main_product_id_for_item, $currentInventoryItemId)) {
                                                                                                                                $explode = explode('/', $r['image_path']);
                                                                                                                                $exp = explode('/', $r['image_path'], -1);
                                                                                                                                $p = "products/" . $exp[1] . "/" . $exp[2] . "/" . $exp[3] . "/resized/" . $explode[count($explode) - 1];
                                                                                                                        } else {
                                                                                                                                $p = $r['image_path'];
                                                                                                                        }


                                                                                                                        ?>
                                                                                                                        <img src="<?= $p ?>"
                                                                                                                                alt="product side">
                                                                                                                </a>
                                                                                                        <?php }
                                                                                                }
                                                                                                ?>

                                                                                        </div>
                                                                                        <!-- End .product-image-gallery -->
                                                                                </div><!-- End .product-gallery -->
                                                                        </div><!-- End .col-md-6 -->

                                                                        <div class="col-md-6">
                                                                                <div
                                                                                        class="product-details product-details-sidebar">
                                                                                        <h1 class="product-title">
                                                                                                <?= htmlspecialchars($description_text) ?>
                                                                                        </h1><!-- End .product-title -->

                                                                                        <div class="ratings-container">

                                                                                                <div class="ratings">
                                                                                                        <div class="ratings-val"
                                                                                                                style="width: <?= $Orvi->get_rating_($currentInventoryItemId) ?>%">
                                                                                                        </div>
                                                                                                        <!-- End .ratings-val -->
                                                                                                </div>
                                                                                                <!-- End .ratings -->
                                                                                                <a class="ratings-text"
                                                                                                        href="#product-review-link"
                                                                                                        id="review-link">(
                                                                                                        <?= $Orvi->get_rating_review_number($currentInventoryItemId); ?>
                                                                                                        Reviews )</a>
                                                                                        </div>
                                                                                        <!-- End .rating-container -->
                                                                                        <?php if ($is_on_promotion) { ?>
                                                                                                <span class="product-price"
                                                                                                        style="margin-bottom: 0px;">N<?= htmlspecialchars($display_price) ?></span>
                                                                                                <span class="old-price">Was
                                                                                                        N<?= htmlspecialchars($old_price) ?></span>
                                                                                        <?php } else { ?>
                                                                                                <div class="product-price">
                                                                                                        &#8358;
                                                                                                        <?= htmlspecialchars($display_price) ?>
                                                                                                </div>
                                                                                                <!-- End .product-price -->
                                                                                        <?php } ?>
                                                                                        <div class="product-content">
                                                                                                <p><?= htmlspecialchars($description_text) ?>
                                                                                                </p>
                                                                                        </div>
                                                                                        <!-- End .product-content -->
                                                                                        <form action="cart.php"
                                                                                                method="post">

                                                                                                <div
                                                                                                        class="details-filter-row details-row-size">
                                                                                                        <?php
                                                                                                        if (!empty($colorVariations)) {
                                                                                                                ?>
                                                                                                                <label>Color:</label>
                                                                                                                <div
                                                                                                                        class="product-nav product-nav-dots">
                                                                                                                        <?php
                                                                                                                        foreach ($colorVariations as $itemId => $color) {
                                                                                                                                ?>
                                                                                                                                <a href="product-detail.php?itemid=<?= $itemId ?>"
                                                                                                                                        style="background: <?= htmlspecialchars($color) ?>"><span
                                                                                                                                                class="sr-only">Color
                                                                                                                                                name</span></a>
                                                                                                                                <?php
                                                                                                                        }
                                                                                                                        ?>
                                                                                                                </div>
                                                                                                                <?php
                                                                                                        }
                                                                                                        ?>
                                                                                                </div>


                                                                                                <?php
                                                                                                if (!empty($sizeVariations)) {
                                                                                                        ?>
                                                                                                        <div
                                                                                                                class="details-filter-row details-row-size">
                                                                                                                <label
                                                                                                                        for="size">Size:</label>
                                                                                                                <div
                                                                                                                        class="select-custom">
                                                                                                                        <select name="size"
                                                                                                                                id="size"
                                                                                                                                class="size form-control">
                                                                                                                                <option value="#"
                                                                                                                                        selected="selected">
                                                                                                                                        Select
                                                                                                                                        a
                                                                                                                                        size
                                                                                                                                </option>
                                                                                                                                <?php
                                                                                                                                foreach ($sizeVariations as $itemId => $size) {
                                                                                                                                        ?>
                                                                                                                                        <option
                                                                                                                                                value="<?= htmlspecialchars($size) ?>">
                                                                                                                                                <?= $size ?>
                                                                                                                                        </option>
                                                                                                                                        <?php
                                                                                                                                }
                                                                                                                                ?>
                                                                                                                        </select>
                                                                                                                </div>
                                                                                                                <!-- End .select-custom -->
                                                                                                                <a href="#"
                                                                                                                        class="size-guide"><i
                                                                                                                                class="icon-th-list"></i>size
                                                                                                                        guide</a>
                                                                                                        </div>
                                                                                                        <!-- End .details-filter-row -->
                                                                                                        <?php
                                                                                                }
                                                                                                ?>
                                                                                                <div
                                                                                                        class="details-filter-row details-row-size">
                                                                                                        <?php
                                                                                                        ?>



                                                                                                        <!-- End .details-filter-row -->




                                                                                                        <div
                                                                                                                class="product-details-action">
                                                                                                                <div
                                                                                                                        class="details-action-col">
                                                                                                                        <label
                                                                                                                                for="qty">Qty:</label>
                                                                                                                        <div
                                                                                                                                class="product-details-quantity">

                                                                                                                                <input type="hidden"
                                                                                                                                        name="inventory_product_id"
                                                                                                                                        value="<?= $currentInventoryItemId ?>" />
                                                                                                                                <input type="number"
                                                                                                                                        name="qty"
                                                                                                                                        id="qty"
                                                                                                                                        class="form-control"
                                                                                                                                        value="1"
                                                                                                                                        min="1"
                                                                                                                                        max="20"
                                                                                                                                        step="1"
                                                                                                                                        data-decimals="0"
                                                                                                                                        required>


                                                                                                                        </div>
                                                                                                                        <!-- End .product-details-quantity -->

                                                                                                                        <input type="submit"
                                                                                                                                class="submit ubmit-cart btn-product btn-cart submit-cart"
                                                                                                                                value="Add to Cart" />


                                                                                                                </div>
                                                                                                                <!-- End .details-action-col -->

                                                                                                                <div
                                                                                                                        class="details-action-wrapper">
                                                                                                                        <a href="add-to-watch-list.php?itemid=<?= $currentInventoryItemId ?>"
                                                                                                                                class="btn-product btn-wishlist"
                                                                                                                                title="Wishlist"
                                                                                                                                data-product-id="<?= $currentInventoryItemId ?>"><span>Add
                                                                                                                                        to
                                                                                                                                        Wishlist</span></a>


                                                                                                                </div>
                                                                                                                <!-- End .details-action-wrapper -->
                                                                                                                <!-- End .details-action-wrapper -->
                                                                                                        </div>
                                                                                                        <!-- End .product-details-action -->
                                                                                                </div>

                                                                                        </form>

                                                                                        <div
                                                                                                class="product-details-footer details-footer-col">
                                                                                                <div
                                                                                                        class="product-cat">
                                                                                                        <span>Category:</span>
                                                                                                        <?php
                                                                                                        if ($related_categories_stmt && $related_categories_stmt->rowCount() > 0) {
                                                                                                                $num_count = 1;
                                                                                                                $number_of_rows = $related_categories_stmt->rowCount();

                                                                                                                while ($row_cat = $related_categories_stmt->fetch(PDO::FETCH_ASSOC)) { ?>
                                                                                                                        <a
                                                                                                                                href="category.php?id=<?= htmlspecialchars($row_cat['category_id']) // Assuming you have a category page ?>"><?= htmlspecialchars($row_cat['categoryName']) ?></a>
                                                                                                                        <?php
                                                                                                                        if ($num_count < $number_of_rows) {
                                                                                                                                echo ", ";
                                                                                                                        }
                                                                                                                        ?>

                                                                                                                        <?php $num_count++;
                                                                                                                }
                                                                                                        } else {
                                                                                                                // Handle the case where there are no related categories.
                                                                                                                echo "N/A";
                                                                                                        }
                                                                                                        ?>
                                                                                                </div>
                                                                                                <!-- End .product-cat -->

                                                                                                <?php
                                                                                                if ($can_add_review):
                                                                                                        ?>
                                                                                                        <a href="product-review.php?inventory-item=<?= $currentInventoryItemId ?>&product_id=<?= $main_product_id_for_item ?>"
                                                                                                                class="btn btn-outline-primary-2 btn-sm"><span>Write
                                                                                                                        a
                                                                                                                        Review</span><i
                                                                                                                        class="icon-edit"></i></a>
                                                                                                <?php else: ?>
                                                                                                        <?= $has_reviewed_message ?>
                                                                                                <?php endif; ?>


                                                                                        </div>
                                                                                        <!-- End .product-details-footer -->

                                                                                </div><!-- End .product-details -->
                                                                        </div><!-- End .col-md-6 -->
                                                                </div><!-- End .row -->
                                                        </div><!-- End .product-details-top -->

                                                        <!-- Combined Product Info, Shipping, and Reviews Section -->
                                                        <div class="product-additional-details mt-5">

                                                                <div class="product-description-section mb-4">
                                                                        <h3 class="mb-3">Product Information</h3>
                                                                        <div class="product-desc-content">
                                                                                <?= nl2br($product_info_text) // Removed htmlspecialchars to allow HTML rendering ?>
                                                                        </div>
                                                                </div>

                                                                <div class="product-shipping-section mb-4">
                                                                        <h3 class="mb-3">Delivery & returns</h3>
                                                                        <div class="product-desc-content">
                                                                                <p><?= nl2br(htmlspecialchars($product_obj->shipping_and_re_trun_rule($shipping_returns_text))) ?>
                                                                                </p>
                                                                        </div>
                                                                </div>

                                                                <div class="product-reviews-section" id="product-review-link"> <!-- Added id for anchor link -->
                                                                        <?php
                                                                        // Display flash messages (from review submission)
                                                                        if (isset($_SESSION['flash_message'])) {
                                                                                echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . ' alert-dismissible fade show" role="alert">';
                                                                                echo htmlspecialchars($_SESSION['flash_message']['text']);
                                                                                echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
                                                                                echo '</div>';
                                                                                unset($_SESSION['flash_message']); // Clear the message after displaying
                                                                        }

                                                                        $product_reviews = $Orvi->getReviewsByProduct($currentInventoryItemId);
                                                                        $total_reviews_count = $Orvi->get_rating_review_number($currentInventoryItemId);
                                                                        ?>
                                                                        <div class="reviews">
                                                                                <h3 class="mb-3">Reviews
                                                                                        (<?= $total_reviews_count ?>)</h3>
                                                                                <div>
                                                                                        <?php if (!empty($product_reviews)): ?>
                                                                                                <?php foreach ($product_reviews as $review_item): ?>
                                                                                                        <div class="review">
                                                                                                                <div class="row no-gutters">
                                                                                                                        <div class="col-auto">
                                                                                                                                <h4><a href="#"><?= htmlspecialchars(($review_item['customer_fname'] ?? 'User') . ' ' . ($review_item['customer_lname'] ?? '')) ?></a>
                                                                                                                                </h4>
                                                                                                                                <div
                                                                                                                                        class="ratings-container">
                                                                                                                                        <div
                                                                                                                                                class="ratings">
                                                                                                                                                <div class="ratings-val"
                                                                                                                                                        style="width: <?= (($review_item['rating'] ?? 0) / 5) * 100 ?>%;">
                                                                                                                                                </div>
                                                                                                                                        </div>
                                                                                                                                </div>
                                                                                                                                <span
                                                                                                                                        class="review-date"><?= isset($review_item['review_date']) ? date("M d, Y", strtotime($review_item['review_date'])) : 'N/A' ?></span>
                                                                                                                        </div>
                                                                                                                        <div class="col">
                                                                                                                                <h4><?= htmlspecialchars($review_item['review_title'] ?? 'Review') ?>
                                                                                                                                </h4>
                                                                                                                                <div
                                                                                                                                        class="review-content">
                                                                                                                                        <p><?= nl2br(htmlspecialchars($review_item['comment'] ?? 'No comment provided.')) ?>
                                                                                                                                        </p>
                                                                                                                                </div>
                                                                                                                        </div>
                                                                                                                </div>
                                                                                                        </div>
                                                                                                <?php endforeach; ?>
                                                                                        <?php else: ?>
                                                                                                <p>Be the first to review this product!
                                                                                                </p>
                                                                                        <?php endif; ?>
                                                                                </div>
                                                                        </div><!-- End .reviews -->
                                                                </div>
                                                        </div>
                                                </div><!-- End .col-lg-9 -->

                                                <aside class="col-lg-3">
                                                        <div class="sidebar sidebar-product">
                                                                <div class="widget widget-products">
                                                                        <h4 class="widget-title">Related Product</h4>
                                                                        <!-- End .widget-title -->

                                                                        <div id="products" class="products">




                                                                        </div><!-- End .products -->


                                                                        <?php
                                                                        // $related_products_result = getrelatedproducts($category_id_from_inventory, $currentInventoryItemId, $mysqli);
                                                                        // Loop through $related_products_result if needed here, or handle via JS
                                                                        ?>
                                                                        <!--  
                                                                      <a href="" class="btn btn-outline-dark-3"><span>View More Products</span><i class="icon-long-arrow-right"></i></a> 
                                                                      -->
                                                                </div><!-- End .widget widget-products -->


                                                        </div><!-- End .sidebar sidebar-product -->
                                                </aside><!-- End .col-lg-3 -->
                                        </div><!-- End .row -->

                                </div><!-- End .container -->
                        </div><!-- End .page-content -->
                </main><!-- End .main -->


                <footer class="footer">
                        <?php include "footer.php"; ?>
                </footer><!-- End .footer -->
        </div><!-- End .page-wrapper -->
        <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

        <!-- Mobile Menu -->
        <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

        <?php include "mobile-menue-index-page.php"; ?>
        <!-- Sign in / Register Modal -->
        <?php include "login-modal.php"; ?>

        <!-- Plugins JS File -->
        <?php include "jsfile.php"; ?>

        <!-- SweetAlert2 JS (ensure it's loaded after jQuery if jQuery is used by other scripts, but SweetAlert2 itself is standalone) -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

        <script>
                document.addEventListener('DOMContentLoaded', function () {
                        <?php
                        // Prepare HTML content for the toast
                        $toast_html_content = '';
                        if (isset($toast_message_for_js) && !empty($toast_message_for_js)) {
                                if (isset($toast_icon_class_for_js) && !empty($toast_icon_class_for_js)) {
                                        $toast_html_content .= '<i class="' . htmlspecialchars($toast_icon_class_for_js, ENT_QUOTES) . '"></i>&nbsp;';
                                }
                                $toast_html_content .= htmlspecialchars($toast_message_for_js, ENT_QUOTES);
                        }

                        if (!empty($toast_html_content)):
                                ?>
                                Swal.fire({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 5000, // <<< Alert will disappear after 5 seconds
                                        timerProgressBar: true,
                                        html: '<?= addslashes($toast_html_content) ?>',
                                        title: false, // Set to false if 'html' provides all content
                                        customClass: {
                                                popup: 'themed-toast-popup',
                                                htmlContainer: 'themed-toast-html-icon'
                                        }
                                });
                        <?php endif; ?>
                });
        </script>



</body>
<script src="assets/js/loadrelateditems.js"></script>
<script type="text/javascript">
        $(document).ready(function () {
                $(".submit").click(function () {

                        if ($('.size').length > 0) {
                                var size = $('.size option:selected').val();
                                if (size == "" || size == "#") {
                                        alert("Please select a a size");
                                        return false;
                                }
                        }
                });
        });
</script>



<!-- molla/product-sidebar.html  22 Nov 2019 10:03:37 GMT -->

</html>
