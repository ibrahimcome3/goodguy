<!DOCTYPE html>
<?php
// SECTION 1: INITIALIZATION & SECURITY
// -----------------------------------------------------------------------------
if (session_status() == PHP_SESSION_NONE) {
        session_start();
} // Ensure session is started for flash messages


$_SESSION['return_to'] = $_SERVER['REQUEST_URI'];

include "includes.php";

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
// Fetch main product and inventory details using a secure PDO prepared statement.
$sql_main_product = "SELECT pi.*, ii.*
                     FROM inventoryitem ii
                     INNER JOIN productitem pi ON ii.productItemID = pi.productID
                     WHERE ii.InventoryItemID = :itemid";
$stmt_main_product = $pdo->prepare($sql_main_product);
$stmt_main_product->execute([':itemid' => $currentInventoryItemId]);
$row = $stmt_main_product->fetch(PDO::FETCH_ASSOC);

if (!$row) {
        echo "Product details not found. <a href='index.php'>Click here to go to the home page.</a>";
        exit();
}

// Extract key data into variables for easier use in the view
$main_product_id_for_item = $row['productID'];
$product_info_text = $row['product_information'];
$product_name_text = $row['product_name'];
$shipping_returns_text = $row['shipping_returns'];
$description_text = $row['description'];
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

// Alert Messages (for cart/wishlist actions)
$alert_message_for_js = null;
$alert_type_for_js = 'info'; // Default type
if (isset($_GET['toast_action'])) {
        switch ($_GET['toast_action']) {
                case 'cart_add_success':
                        $alert_message_for_js = "Item successfully added to your cart!";
                        $alert_type_for_js = 'success';
                        break;
                case 'wishlist_add_success':
                        $alert_message_for_js = "Item added to your wishlist!";
                        $alert_type_for_js = 'success';
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
$cat_obj = new Category($pdo);
// The function now returns the final array directly.
$related_categories_array = $cat_obj->get_related_categories($currentInventoryItemId);

// For elements that might need a single category ID (like related products JS)
$primary_category_id = 0;
if (!empty($related_categories_array)) {
        $primary_category_id = $related_categories_array[0]['category_id'];
}

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

        <!-- Plugins CSS File -->
        <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
        <!-- Main CSS File -->
        <!-- simplePagination.js CSS (CDN example) -->
        <link rel="stylesheet"
                href="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/simplePagination.css">

        <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
        <link rel="stylesheet" href="assets/css/demos/demo-13.css">

        <style>
                /* --- GitHub-like Toast Styles --- */
                .github-toast-popup {
                        background-color: #f6f8fa !important;
                        color: #24292e !important;
                        border-radius: 6px !important;
                        border: 1px solid rgba(27, 31, 35, .15) !important;
                        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1) !important;
                        padding: 1rem !important;
                        display: flex !important;
                        align-items: center !important;
                        font-size: 14px !important;
                }

                .github-toast-html-icon {
                        margin: 0 !important;
                        padding: 0 !important;
                        font-size: 1em !important;
                        display: flex;
                        align-items: center;
                }

                .github-toast-html-icon i.fas {
                        font-size: 16px;
                        /* Match font size */
                        margin-right: 8px;
                        line-height: 1;
                }

                .github-toast-success i.fas {
                        color: #28a745;
                }

                .github-toast-error i.fas {
                        color: #d73a49;
                }

                .github-toast-warning i.fas {
                        color: #f9c513;
                }

                .github-toast-info i.fas {
                        color: #0366d6;
                }

                /* --- End of GitHub-like Toast Styles --- */

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

                include "header_main.php";
                ?>
                <main class="main">
                        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                                <div class="container d-flex align-items-center">
                                        <ol class="breadcrumb">
                                                <?php echo breadcrumbs(); ?>
                                        </ol>
                                        <!--
                                        <nav class="product-pager ml-auto" aria-label="Product">
                                                <a class="product-pager-link product-pager-prev" href="#"
                                                        aria-label="Previous" tabindex="-1">
                                                        <i class="icon-angle-left"></i>
                                                        <span>Prev</span>
                                                </a>

                                                <a class="product-pager-link product-pager-next"
                                                        href="product-detail.php?itemid=<?php +1 ?>" aria-label="Next"
                                                        tabindex="-1">
                                                        <span>Next</span>
                                                        <i class="icon-angle-right"></i>
                                                </a>
                                        </nav>
        -->
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
                                                                                product-cat="<?= $primary_category_id ?>">
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
                                                                                                        $i = $currentInventoryItemId;

                                                                                                        $pi = glob("products/product-$main_product_id_for_item/product-$main_product_id_for_item-image/inventory-$main_product_id_for_item-$i/resized_600/" . '*.{jpg,gif,png,jpeg}', GLOB_BRACE);

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
                                                                                                        style="margin-bottom: 0px;">
                                                                                                        &#8358;<?= number_format(htmlspecialchars($display_price), 2) ?>
                                                                                                </span>
                                                                                                <span class="old-price">Was
                                                                                                        &#8358;<?= number_format(htmlspecialchars($old_price), 2) ?>
                                                                                                </span>
                                                                                        <?php } else { ?>
                                                                                                <div class="product-price">
                                                                                                        &#8358;
                                                                                                        <?= number_format(htmlspecialchars($display_price), 2) ?>
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
                                                                                                                                                value="<?= $size ?>">
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
                                                                                                                        <!--  <a href="#" product-info="//$_GET['itemid']" class="submit-cart btn-product btn-cart submit-cart"><span>add to cart</span></a> -->


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
                                                                                                        // Check if the returned array is not empty.
                                                                                                        if (!empty($related_categories_array)) {
                                                                                                                $number_of_rows = count($related_categories_array);
                                                                                                                $num_count = 1;

                                                                                                                foreach ($related_categories_array as $cat_row) {
                                                                                                                        // Link to shop.php instead of products.php
                                                                                                                        echo '<a href="shop.php?category=' . htmlspecialchars($cat_row['category_id']) . '">' . htmlspecialchars($cat_row['name']) . '</a>';
                                                                                                                        if ($num_count < $number_of_rows) {
                                                                                                                                echo ", ";
                                                                                                                        }
                                                                                                                        $num_count++;
                                                                                                                }
                                                                                                        } else {
                                                                                                                echo "Uncategorized";
                                                                                                        }
                                                                                                        ?>

                                                                                                </div>
                                                                                                <!-- End .product-cat -->

                                                                                                <?php
                                                                                                // This logic is already handled at the top of the file.
                                                                                                // We can directly use the variables $can_add_review and $has_reviewed_message.
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

                                                                <div class="product-reviews-section"
                                                                        id="product-review-link">
                                                                        <!-- Added id for anchor link -->
                                                                        <?php
                                                                        // Display flash messages (from review submission)
                                                                        if (isset($_SESSION['flash_message'])) {
                                                                                echo '<div class="alert alert-' . htmlspecialchars($_SESSION['flash_message']['type']) . ' alert-dismissible fade show" role="alert">';
                                                                                echo htmlspecialchars($_SESSION['flash_message']['text']); // Make sure this is properly escaped if it contains HTML
                                                                                echo '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
                                                                                echo '</div>';
                                                                                unset($_SESSION['flash_message']); // Clear the message after displaying
                                                                        }
                                                                        // We still need the total count for pagination initialization
                                                                        $total_reviews_count = $Orvi->get_rating_review_number($currentInventoryItemId);
                                                                        ?>
                                                                        <div class="reviews" id="reviews-container">
                                                                                <!-- Added ID for easier targeting -->
                                                                                <h3 class="mb-3">Reviews
                                                                                        (<?= $total_reviews_count ?>)
                                                                                </h3>
                                                                                <div id="review-list" class="mb-3">
                                                                                        <!-- Reviews will be loaded here by AJAX -->
                                                                                        <p id="no-reviews-message"
                                                                                                style="display: <?= $total_reviews_count > 0 ? 'none' : 'block' ?>;">
                                                                                                Be the first to review
                                                                                                this product!</p>
                                                                                        <div class="text-center"
                                                                                                id="reviews-loading"
                                                                                                style="display:none;">
                                                                                                <p>Loading reviews...
                                                                                                </p>
                                                                                        </div>
                                                                                </div>
                                                                                <?php if ($total_reviews_count > 0): ?>
                                                                                        <div id="reviews-pagination-container"
                                                                                                class="mt-4 text-center"></div>
                                                                                        <!-- Pagination controls will go here -->
                                                                                <?php endif; ?>
                                                                        </div><!-- End .reviews -->
                                                                </div>
                                                        </div>

                                                        <!--<h2 class="title text-center mb-4">You May Also Like</h2><!-- End .title text-center -->
                                                        <?php //include "also-like.php" ?>
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
        <!-- simplePagination.js (CDN example - load AFTER jQuery) -->
        <script
                src="https://cdnjs.cloudflare.com/ajax/libs/simplePagination.js/1.6/jquery.simplePagination.min.js"></script>

        <script>
                document.addEventListener('DOMContentLoaded', function () {
                        <?php if (isset($alert_message_for_js) && !empty($alert_message_for_js)):
                                // Prepare variables for JavaScript
                                $alertType = addslashes($alert_type_for_js);
                                $alertMessage = addslashes($alert_message_for_js);
                                ?>
                                const alertType = '<?= $alertType ?>';
                                const alertMessage = '<?= $alertMessage ?>';

                                let iconClass = 'fas fa-info-circle'; // Default icon
                                let toastClass = 'github-toast-info';

                                if (alertType === 'success') {
                                        iconClass = 'fas fa-check-circle';
                                        toastClass = 'github-toast-success';
                                } else if (alertType === 'error') {
                                        iconClass = 'fas fa-times-circle';
                                        toastClass = 'github-toast-error';
                                } else if (alertType === 'warning') {
                                        iconClass = 'fas fa-exclamation-triangle';
                                        toastClass = 'github-toast-warning';
                                }

                                Swal.fire({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 5000,
                                        timerProgressBar: true,
                                        html: `<i class="${iconClass}"></i>&nbsp;&nbsp;${alertMessage}`,
                                        customClass: {
                                                popup: `github-toast-popup ${toastClass}`,
                                                htmlContainer: 'github-toast-html-icon'
                                        },
                                        showCloseButton: true,
                                        didOpen: (toast) => {
                                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                                        }
                                });
                        <?php endif; ?>
                });

                // Pagination for Reviews
                $(document).ready(function () {
                        var totalReviews = <?= (int) $total_reviews_count ?>;
                        var inventoryItemId = <?= (int) $currentInventoryItemId ?>;
                        var itemsPerPage = 3; // Number of reviews to show per page

                        function renderReviews(reviews) {
                                var reviewList = $('#review-list');
                                reviewList.empty(); // Clear previous reviews

                                if (reviews && reviews.length > 0) {
                                        $('#no-reviews-message').hide();
                                        $.each(reviews, function (index, review_item) {
                                                var ratingPercent = ((review_item.rating || 0) / 5) * 100;
                                                var reviewDate = review_item.review_date ? new Date(review_item.review_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
                                                var reviewerName = (review_item.customer_fname || 'User') + ' ' + (review_item.customer_lname || '');
                                                var reviewTitle = review_item.review_title || 'Review';
                                                var reviewComment = review_item.comment || 'No comment provided.';

                                                var reviewHtml = `
                                    <div class="review review-item" style='border-bottom: 1px solid #f0f0f0;'>
                                        <div class="row no-gutters">
                                            <div class="col-auto">
                                                <h4><a href="#">${escapeHtml(reviewerName.trim())}</a></h4>
                                                <div class="ratings-container">
                                                    <div class="ratings">
                                                        <div class="ratings-val" style="width: ${ratingPercent}%;"></div>
                                                    </div>
                                                </div>
                                                <span class="review-date">${reviewDate}</span>
                                            </div>
                                            <div class="col">
                                                <h4>${escapeHtml(reviewTitle)}</h4>
                                                <div class="review-content">
                                                    <p>${nl2br(escapeHtml(reviewComment))}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>`;
                                                reviewList.append(reviewHtml);
                                        });
                                } else {
                                        $('#no-reviews-message').show();
                                }
                        }

                        function loadReviewsPage(pageNumber) {
                                $('#reviews-loading').show();
                                $('#review-list').empty(); // Clear before loading
                                $('#no-reviews-message').hide();

                                $.ajax({
                                        url: 'fetch_reviews.php',
                                        type: 'GET',
                                        dataType: 'json',
                                        data: {
                                                itemid: inventoryItemId,
                                                page: pageNumber,
                                                perPage: itemsPerPage
                                        },
                                        success: function (response) {
                                                $('#reviews-loading').hide();
                                                if (response.success && response.reviews) {
                                                        renderReviews(response.reviews);
                                                } else {
                                                        $('#review-list').html('<p class="text-danger">Could not load reviews. ' + (response.message || '') + '</p>');
                                                        $('#no-reviews-message').hide();
                                                }
                                        },
                                        error: function (xhr, status, error) {
                                                $('#reviews-loading').hide();
                                                $('#review-list').html('<p class="text-danger">Error loading reviews. Please try again.</p>');
                                                $('#no-reviews-message').hide();
                                                console.error("AJAX error loading reviews:", status, error, xhr.responseText);
                                        }
                                });
                        }

                        if (totalReviews > 0) {
                                if (totalReviews > itemsPerPage) { // Only show pagination if needed
                                        $('#reviews-pagination-container').pagination({
                                                items: totalReviews,
                                                itemsOnPage: itemsPerPage,
                                                cssStyle: 'light-theme', // or 'dark-theme' or your custom theme
                                                prevText: 'Prev',
                                                nextText: 'Next',
                                                onPageClick: function (pageNumber) {
                                                        loadReviewsPage(pageNumber);
                                                        // Optional: Scroll to top of reviews
                                                        // $('html, body').animate({ scrollTop: $("#reviews-container").offset().top - 70 }, 500);
                                                }
                                        });
                                } else {
                                        $('#reviews-pagination-container').hide();
                                }
                                // Load the first page of reviews initially
                                loadReviewsPage(1);
                        } else {
                                $('#reviews-pagination-container').hide(); // Hide pagination if no reviews
                                $('#no-reviews-message').show();
                        }

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

                // Helper function to escape HTML (simple version)
                function escapeHtml(unsafe) {
                        return unsafe
                                .replace(/&/g, "&amp;")
                                .replace(/</g, "&lt;")
                                .replace(/>/g, "&gt;")
                                .replace(/"/g, "&quot;")
                                .replace(/'/g, "&#039;");
                }
                // Helper function for nl2br in JS
                function nl2br(str) {
                        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
                }


        </script>



</body>
<script src="assets/js/loadrelateditems.js"></script>
<script type="text/javascript">
        $(document).ready(function () {
                $(".submit").click(function () {

                        if ($('.size').length > 0) {
                                var size = $('.size option:selected').val();
                                if (size == "" || size == "#") {
                                        Swal.fire('Error', 'Please select a size.', 'error');
                                        return false;
                                }
                        }
                });

                // Auto-dismiss success alerts (like for review submissions) after 5 seconds.
                $(".alert-success").delay(5000).slideUp(500, function () {
                        $(this).remove();
                });
        });
</script>




<!-- molla/product-sidebar.html  22 Nov 2019 10:03:37 GMT -->

</html>