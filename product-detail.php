<!DOCTYPE html>
<?php
include "includes.php";

$wished_list_count = 0;
if (!$invt->check_item_in_existance($_GET['itemid'])) {
        echo "Item does not exist <a href='index.php'>click here to go home page</a>";
        exit();
}
if (isset($_SESSION['uid']))
        $wished_list_count = $wishlist->get_wished_list_item($_SESSION["uid"]);

$sql = "SELECT * from productitem left join inventoryitem on productitem.productID = inventoryitem.productItemID WHERE `InventoryItemID` =" . $_GET['itemid'];
$result = $mysqli->query($sql);
$row = mysqli_fetch_array($result);
$category = $row['category'];
$icudrop = $row['productItemID'];
$product_info = $row['product_information'];
$product_name = $row['product_name'];
$shipping_returns = $row['shipping_returns'];

$id_of_what_get_image = $_GET['itemid'];


function getrelatedproducts($category9, $_id_of_what_get_image)
{
        include "conn.php";
        $sql = "SELECT * FROM `inventoryitem` WHERE `category` = " . $category9 . " and InventoryItemID != " . $_id_of_what_get_image . " limit 5";
        $result = $mysqli->query($sql);
        return $result;

}

$num_items_in_cart = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<html lang="en">


<head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Product Page</title>
        <?php include "htlm-includes.php/metadata.php"; ?>
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
                                                                                product-info=<?= $_GET['itemid'] ?>
                                                                                product-cat=<?= $row['category'] ?>>
                                                                                <div class="product-gallery">
                                                                                        <figure
                                                                                                class="product-main-image">
                                                                                                <?php $pid = $product_obj->get_product_id($_GET['itemid']); ?>
                                                                                                <?php if ($promotion->check_if_item_is_in_promotion($product_obj->get_product_id($_GET['itemid'])) != null) { ?>
                                                                                                        <span
                                                                                                                class="product-label label-sale">Sale</span>
                                                                                                <?php } ?>

                                                                                                <?php
                                                                                                //if(in_array($product_obj->get_product_id($_GET['itemid']), $product_obj->get_all_product_items_that_are_less_than_one_month())){ 
                                                                                                if (in_array($_GET['itemid'], $product_obj->get_all_product_items_that_are_less_than_one_month())) { ?>
                                                                                                        <span
                                                                                                                class="product-label label-top">NEW</span>
                                                                                                <?php } ?>
                                                                                                <?php
                                                                                                if ($product_obj->check_dirtory_resized_600($pid, $_GET['itemid'])) {
                                                                                                        $i = $_GET['itemid'];

                                                                                                        $pi = glob("products/product-$pid/product-$pid-image/inventory-$pid-$i/resized_600/" . '*.{jpg,gif}', GLOB_BRACE);

                                                                                                        $p = $pi[0];
                                                                                                } else {
                                                                                                        $p = $product_obj->get_cover_image($id_of_what_get_image);
                                                                                                }
                                                                                                ?>
                                                                                                <img id="product-zoom"
                                                                                                        src="<?= $p; ?>"
                                                                                                        data-image="<?= $p ?>"
                                                                                                        data-zoom-image="<?= $product_obj->get_cover_image($id_of_what_get_image); ?>"
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
                                                                                                <?php $p_obj = new ProductItem();
                                                                                                $stmt = $p_obj->get_other_images_of_item_in_inventory($id_of_what_get_image);

                                                                                                if ($stmt != null) {
                                                                                                        while ($r = $stmt->fetch()) { ?>
                                                                                                                <a class="product-gallery-item"
                                                                                                                        href="#"
                                                                                                                        data-image="<?= $r['image_path'] ?>"
                                                                                                                        data-zoom-image="<?= $r['image_path'] ?>">
                                                                                                                        <?php

                                                                                                                        if ($p_obj->check_dirctory_resized($pid, $_GET['itemid'])) {
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
                                                                                                <?= $row['description'] ?>
                                                                                        </h1><!-- End .product-title -->

                                                                                        <div class="ratings-container">

                                                                                                <div class="ratings">

                                                                                                        <div class="ratings-val"
                                                                                                                style="width: <?= $Orvi->get_rating_($_GET['itemid']) ?>%">
                                                                                                        </div>
                                                                                                        <!-- End .ratings-val -->
                                                                                                </div>
                                                                                                <!-- End .ratings -->
                                                                                                <a class="ratings-text"
                                                                                                        href="#product-review-link"
                                                                                                        id="review-link">(
                                                                                                        <?= $Orvi->get_rating_review_number($_GET['itemid']); ?>
                                                                                                        Reviews )</a>
                                                                                        </div>
                                                                                        <!-- End .rating-container -->
                                                                                        <?php if ($promotion->check_if_item_is_in_inventory_promotion($row['InventoryItemID'])) { ?>
                                                                                                <span class="product-price"
                                                                                                        style="margin-bottom: 0px;">N<?= $promotion->get_promoPrice_price($row['InventoryItemID']) ?></span>
                                                                                                <span class="old-price">Was
                                                                                                        N<?= $promotion->get_regular_price($row['InventoryItemID']) ?></span>
                                                                                        <?php } else { ?>
                                                                                                <div class="product-price">
                                                                                                        &#8358;
                                                                                                        <?= $row['cost'] ?>
                                                                                                </div>
                                                                                                <!-- End .product-price -->
                                                                                        <?php } ?>
                                                                                        <div class="product-content">
                                                                                                <p><?= $row['description'] ?>
                                                                                                </p>
                                                                                        </div>
                                                                                        <!-- End .product-content -->
                                                                                        <form action="cart.php"
                                                                                                method="post">

                                                                                                <div
                                                                                                        class="details-filter-row details-row-size">
                                                                                                        <?php
                                                                                                        $var_obj = new Variation($pdo);
                                                                                                        $product_obj = new ProductItem();
                                                                                                        $colorVariations = $var_obj->get_color_variations_for_product_from_sku($product_obj->get_product_id($_GET['itemid']));

                                                                                                        if (!empty($colorVariations)) {
                                                                                                                ?>
                                                                                                                <label>Color:</label>
                                                                                                                <div
                                                                                                                        class="product-nav product-nav-dots">
                                                                                                                        <?php
                                                                                                                        foreach ($colorVariations as $itemId => $color) {
                                                                                                                                ?>
                                                                                                                                <a href="product-detail.php?itemid=<?= $itemId ?>"
                                                                                                                                        style="background: <?= $color ?>"><span
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
                                                                                                $sizeVariations = $var_obj->get_size_variations_for_product_from_sku($product_obj->get_product_id($_GET['itemid']));
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
                                                                                                        <?php
                                                                                                        $invt_item = new InventoryItem($pdo);
                                                                                                        $color = $invt_item->get_color($_GET['itemid']);
                                                                                                        $arr_color = array();
                                                                                                        $var_obj = new Variation($pdo);
                                                                                                        $var_obj_size = new Variation($pdo);
                                                                                                        $product_obj = new ProductItem();

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
                                                                                                                                        value="<?= $_GET['itemid'] ?>" />
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
                                                                                                                                value="add to cart" />
                                                                                                                        <!--  <a href="#" product-info="//$_GET['itemid']" class="submit-cart btn-product btn-cart submit-cart"><span>add to cart</span></a> -->


                                                                                                                </div>
                                                                                                                <!-- End .details-action-col -->

                                                                                                                <div
                                                                                                                        class="details-action-wrapper">
                                                                                                                        <a href="add-to-watch-list.php?itemid=<?= $_GET['itemid'] ?>"
                                                                                                                                class="btn-product btn-wishlist"
                                                                                                                                title="Wishlist"
                                                                                                                                data-product-id="<?= $_GET['itemid'] ?>"><span>Add
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
                                                                                                        $obj = new Category();
                                                                                                        $stmt = $obj->get_related_categories($_GET['itemid']);
                                                                                                        if ($stmt) {
                                                                                                                $num_count = 1;
                                                                                                                $number_of_rows = $stmt->rowCount() ? $stmt->rowCount() : 0;

                                                                                                                while ($row = $stmt->fetch()) { ?>
                                                                                                                        <a
                                                                                                                                href="#"><?= $row['categoryName'] ?></a>
                                                                                                                        <?php
                                                                                                                        if ($num_count < $number_of_rows) {
                                                                                                                                echo ", ";
                                                                                                                        }
                                                                                                                        ?>

                                                                                                                        <?php $num_count++;
                                                                                                                }
                                                                                                        } else {
                                                                                                                // Handle the case where there are no related categories.  You might choose to display a message like "No related categories found."
                                                                                                                echo "No related categories found.";
                                                                                                        }
                                                                                                        ?>

                                                                                                </div>
                                                                                                <!-- End .product-cat -->


                                                                                                <a href="product-review.php?inventory-item=<?= $_GET['itemid'] ?>&product_id=<?= $icudrop ?>"
                                                                                                        class="btn-product "
                                                                                                        title="Compare"><span>Add
                                                                                                                a
                                                                                                                Review</span></a>


                                                                                        </div>
                                                                                        <!-- End .product-details-footer -->
                                                                                </div><!-- End .product-details -->
                                                                        </div><!-- End .col-md-6 -->
                                                                </div><!-- End .row -->
                                                        </div><!-- End .product-details-top -->

                                                        <div class="product-details-tab">
                                                                <ul class="nav nav-pills justify-content-center"
                                                                        role="tablist">
                                                                        <li class="nav-item">
                                                                                <a class="nav-link active"
                                                                                        id="product-desc-link"
                                                                                        data-toggle="tab"
                                                                                        href="#product-desc-tab"
                                                                                        role="tab"
                                                                                        aria-controls="product-desc-tab"
                                                                                        aria-selected="true">Description</a>
                                                                        </li>

                                                                        <li class="nav-item">
                                                                                <a class="nav-link"
                                                                                        id="product-shipping-link"
                                                                                        data-toggle="tab"
                                                                                        href="#product-shipping-tab"
                                                                                        role="tab"
                                                                                        aria-controls="product-shipping-tab"
                                                                                        aria-selected="false">Shipping &
                                                                                        Returns</a>
                                                                        </li>
                                                                        <li class="nav-item">
                                                                                <a class="nav-link"
                                                                                        id="product-review-link"
                                                                                        data-toggle="tab"
                                                                                        href="#product-review-tab"
                                                                                        role="tab"
                                                                                        aria-controls="product-review-tab"
                                                                                        aria-selected="false">Reviews
                                                                                        (<?= $Orvi->get_rating_review_number($_GET['itemid']) ?>)</a>
                                                                        </li>
                                                                </ul>
                                                                <div class="tab-content">
                                                                        <div class="tab-pane fade show active"
                                                                                id="product-desc-tab" role="tabpanel"
                                                                                aria-labelledby="product-desc-link">
                                                                                <div class="product-desc-content">
                                                                                        <h3>Product Information</h3>
                                                                                        <p> <?= $product_info ?> </p>
                                                                                </div><!-- End .product-desc-content -->
                                                                        </div><!-- .End .tab-pane -->

                                                                        <div class="tab-pane fade"
                                                                                id="product-shipping-tab"
                                                                                role="tabpanel"
                                                                                aria-labelledby="product-shipping-link">
                                                                                <div class="product-desc-content">
                                                                                        <h3>Delivery & returns</h3>
                                                                                        <p><?= $product_obj->shipping_and_re_trun_rule($shipping_returns) ?>
                                                                                        </p>
                                                                                </div><!-- End .product-desc-content -->
                                                                        </div><!-- .End .tab-pane -->
                                                                        <div class="tab-pane fade"
                                                                                id="product-review-tab" role="tabpanel"
                                                                                aria-labelledby="product-review-link">

                                                                                <?php
                                                                                $obj = new Review();
                                                                                ?>
                                                                                <div class="reviews">
                                                                                        <span class="slider-loader"
                                                                                                style="visibility: hidden;"></span>
                                                                                        <h3>Reviews
                                                                                                (<?= $obj->get_total_review_of_product($_GET['itemid']); ?>)
                                                                                        </h3>
                                                                                        <div id="main-review">

                                                                                        </div>


                                                                                </div><!-- End .reviews -->
                                                                                <?php ?>

                                                                        </div><!-- .End .tab-pane -->
                                                                </div><!-- End .tab-content -->
                                                        </div><!-- End .product-details-tab -->

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

                                                                        $obj2 = new Category();
                                                                        $r = $obj2->get_category_by_id($_GET['itemid']);
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