<?php
require_once "includes.php";
/************************************************************************************************/
if (isset($_GET['pageno'])) {
    $pageno = $_GET['pageno'];
} else {
    $pageno = 1;
}

$no_of_records_per_page = 28;
$offset = ($pageno - 1) * $no_of_records_per_page;

/************************************************************************************************/

$n = sizeof($_GET);

//var_dump($_GET);
if (sizeof($_GET) === 1) {
    $sql = "SELECT * FROM `inventoryitem`INNER join productitem on inventoryitem.productItemID = productitem.productID left join brand on productitem.brand = brand.brandID where inventoryitem.category in (SELECT `cat_id` FROM category_new WHERE `cat_path` LIKE '%" . $_GET['catid'] . "%')";
    //$stmt = $pdo->query($sql);
    //$row = $stmt->fetch();
}

if (isset($_GET['catid'])) {
    if (isset($_GET['category'])) {
        $sql = "SELECT * FROM `inventoryitem`INNER join productitem on inventoryitem.productItemID = productitem.productID left join brand on productitem.brand = brand.brandID where inventoryitem.category in (" . implode(", ", $_GET['category']) . ")";
        $stmt = $pdo->query($sql);

        $sql_ = "SELECT count(*) FROM `inventoryitem`INNER join productitem on inventoryitem.productItemID = productitem.productID left join brand on productitem.brand = brand.brandID where inventoryitem.category in (" . implode(", ", $_GET['category']) . ")";
    } else {
        $sql = "select * from inventoryitem left join productitem on productitem.productID = inventoryitem.productItemID where inventoryitem.category in (SELECT cat_id FROM `category_new` WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = '" . $_GET['catid'] . "')";
        $stmt = $pdo->query($sql);
        $sql_ = "select count(*) from inventoryitem left join productitem on productitem.productID = inventoryitem.productItemID where inventoryitem.category in (SELECT cat_id FROM `category_new` WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = '" . $_GET['catid'] . "')";
    }
}
$total_pages_sql = $sql_;

$result = mysqli_query($mysqli, $total_pages_sql);
$total_rows = mysqli_fetch_array($result)[0];
$total_pages = ceil($total_rows / $no_of_records_per_page);


$limit = 28;
$paginationPrev = "";
$paginationnext = "";
$prev = $pageno - 1;                          //previous page is page - 1
$next = $pageno + 1;
$initial_page = ($pageno - 1) * $limit;
$lastpage = ceil($total_pages / $limit);

//next button




if (isset($_GET['catid'])) {
    if (isset($_GET['category'])) {
        $sql = "SELECT * FROM `inventoryitem`INNER join productitem on inventoryitem.productItemID = productitem.productID left join brand on productitem.brand = brand.brandID where inventoryitem.category in (" . implode(", ", $_GET['category']) . ") LIMIT " . $initial_page . ',' . $limit;
        $stmt = $pdo->query($sql);
        $sql_ = "SELECT count(*) FROM `inventoryitem`INNER join productitem on inventoryitem.productItemID = productitem.productID left join brand on productitem.brand = brand.brandID where inventoryitem.category in (" . implode(", ", $_GET['category']) . ")";
    } else {
        $sql = "select * from inventoryitem left join productitem on productitem.productID = inventoryitem.productItemID where inventoryitem.category in (SELECT cat_id FROM `category_new` WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = '" . $_GET['catid'] . "') LIMIT " . $initial_page . ',' . $limit;
        $stmt = $pdo->query($sql);
        $sql_ = "select count(*) from inventoryitem left join productitem on productitem.productID = inventoryitem.productItemID where inventoryitem.category in (SELECT cat_id FROM `category_new` WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = '" . $_GET['catid'] . "')";
    }
}
// echo $sql;

$pic_array = array();
?>


<!DOCTYPE html>
<html lang="en">



<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Category</title>
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
                    <div class="row">
                        <div class="col-lg-9">
                            <div class="toolbox">
                                <div class="toolbox-left">
                                    <div class="toolbox-info">

                                        <?php
                                        $items_per_page = $no_of_records_per_page;
                                        $displayed_items = ($total_rows - $items_per_page * ($pageno - 1)) > $items_per_page ? $items_per_page * $pageno : $total_rows;
                                        //echo "Display results " . $displayed_items . "/" . $total_rows;
                                        ?>
                                        Showing <span><?= $displayed_items ?> of <?= $total_rows ?></span> Products
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
                            <?php $inventory_items = new InventoryItem();
                            $p = new ProductItem();
                            $category = new Category();
                            //$selected_category_for_selection = implode(",", $inventory_items->decript_string($_GET['arr']));
                            //$stmt = $inventory_items->get_multiple_inventory_items_product2(implode(",", $inventory_items->decript_string($_GET['arr'])));
                            if (isset($_GET['arr']) && $_GET['arr'] == '0') {
                                $stmt = $inventory_items->get_all_drinks();
                            } else {
                                //  $stmt = $inventory_items->get_multiple_inventory_items_product2($sql, $starting_limit, $limit);
                            }
                            ?>
                            <div class="products mb-3">
                                <div class="row justify-content-center">
                                    <?php
                                    while ($row = $stmt->fetch()) {
                                        $i = $row['InventoryItemID'];

                                        ?>
                                        <div class="col-6 col-md-4 col-lg-4 col-xl-3">
                                            <div class="product product-7">
                                                <figure class="product-media">
                                                    <?php if ($promotion->check_if_item_is_in_promotion($product_obj->get_product_id($row['InventoryItemID'])) != null) { ?>
                                                        <span class="product-label label-sale">Sale</span>
                                                    <?php } ?>

                                                    <?php if (in_array($product_obj->get_product_id($row['InventoryItemID']), $product_obj->get_all_product_items_that_are_less_than_one_month())) { ?>
                                                        <span class="product-label label-top">NEW</span>
                                                    <?php } ?>
                                                    <a href="product-detail.php?itemid=<?= $row['InventoryItemID'] ?>">
                                                        <?php
                                                        $pid = $product_obj->get_product_id($row['InventoryItemID']);
                                                        if ($product_obj->check_dirtory_resized_600($pid, $row['InventoryItemID'])) {

                                                            $pi = glob("products/product-$pid/product-$pid-image/inventory-$pid-$i/resized_600/" . '*.{jpg,gif}', GLOB_BRACE);
                                                            foreach ($pi as $key => $value) {
                                                                $pic_array[] = explode("/", $value)[count(explode("/", $value)) - 1];

                                                            }

                                                            $sql_ = "SELECT * FROM `inventory_item_image` WHERE `inventory_item_id` = $i and is_primary = 1";
                                                            $stmt_ = $pdo->query($sql_);
                                                            if ($stmt_ && $stmt_->rowCount() > 0) {
                                                                $row_ = $stmt_->fetch();
                                                                $img = "products/product-" . $pid . "/product-" . $pid . "-image/inventory-" . $pid . "-" . $i . "/resized_600/" . $row_['image_name'];
                                                            } else {
                                                                $img = "products/product-" . $pid . "/product-" . $pid . "-image/inventory-" . $pid . "-" . $i . "/resized_600/" . $pic_array[0];
                                                            }
                                                        } else {
                                                            $img = $p->get_image($row['InventoryItemID']);
                                                        }

                                                        ?>
                                                        <img src="<?= $img ?>" alt="product image for product <?= $i ?>"
                                                            class="product-image">
                                                    </a>

                                                    <div class="product-action-vertical">
                                                        <a href="#"
                                                            class="btn-product-icon btn-wishlist btn-expandable"><span>add
                                                                to wishlist</span></a>
                                                        <a href="<?= $p->get_image($row['InventoryItemID']) ?>"
                                                            class="btn-product-icon btn-quickview"
                                                            title="Quick view"><span>Quick view</span></a>
                                                    </div><!-- End .product-action-vertical -->

                                                    <div class="product-action">
                                                        <a href="#" class="submit-cart btn-product btn-cart"
                                                            product-info="<?= $row['InventoryItemID'] ?>"><span>add to
                                                                cart</span></a>
                                                    </div><!-- End .product-action -->
                                                </figure><!-- End .product-media -->

                                                <div class="product-body">
                                                    <div class="product-cat text-center">
                                                        <a
                                                            href="#"><?= $category->get_categorie_name($row['category']); ?></a>
                                                    </div><!-- End .product-cat -->
                                                    <h3 class="product-title truncate"><a
                                                            href="product-detail.php?itemid=<?= $row['InventoryItemID'] ?>"><?= $row['small_description'] ?></a>
                                                    </h3><!-- End .product-title -->
                                                    <div class="product-price">
                                                        <span
                                                            class="new-price">&#8358;<?= " " . number_format($row['cost'], 2) ?></span>
                                                    </div><!-- End .product-price -->
                                                    <?php
                                                    $obj = new Review();
                                                    ?>
                                                    <div class="ratings-container">
                                                        <div class="ratings">
                                                            <div class="ratings-val"
                                                                style="width: <?= $obj->get_rating_($row['InventoryItemID']) ?>%;">
                                                            </div><!-- End .ratings-val -->
                                                        </div><!-- End .ratings -->

                                                        <span class="ratings-text">(
                                                            <?= $obj->get_total_review_of_product($row['InventoryItemID']) ?>
                                                            Reviews )</span>
                                                    </div><!-- End .rating-container -->
                                                    <!--
                                                <div class="product-nav product-nav-thumbs">
                                                    <a href="#" class="active">
                                                        <img src="assets/images/products/product-4-thumb.jpg" alt="product desc">
                                                    </a>
                                                    <a href="#">
                                                        <img src="assets/images/products/product-4-2-thumb.jpg" alt="product desc">
                                                    </a>

                                                    <a href="#">
                                                        <img src="assets/images/products/product-4-3-thumb.jpg" alt="product desc">
                                                    </a>
                                                </div><!-- End .product-nav -->
                                                </div><!-- End .product-body -->
                                            </div><!-- End .product -->
                                        </div><!-- End .col-sm-6 col-lg-4 col-xl-3 -->
                                        <?php
                                    }
                                    ?>


                                </div><!-- End .row -->



                            </div><!-- End .products -->


                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php
                                    $paginationPrev .= "";
                                    $paginationnext .= "";
                                    if ($pageno > 1) {
                                        $paginationPrev .= "<li class='page-item active' aria-current='page'><a class='page-link' href='{$_SERVER['PHP_SELF']}?catid=DRINKS&pageno={$prev}'>prev</a></li>";
                                    } else {
                                        $paginationPrev .= "<span>prev</span>";
                                    }
                                    if ($pageno < $total_pages) {
                                        $paginationnext .= "<li class='page-item active' aria-current='page'><a class='page-link' href='category.php?catid=DRINKS&pageno={$next}'>Next</a></li>";
                                    } else {
                                        $paginationnext .= "<span>Next</span>";
                                    }
                                    $skipped = false;
                                    echo $paginationPrev;
                                    ?>
                                    <?php for ($page_number = 1; $page_number <= $total_pages; $page_number++) { ?>
                                        <?php
                                        if ($page_number < 3 || $total_pages - $page_number < 5 || abs($pageno - $page_number) < 3) {
                                            if ($skipped)
                                                echo '<li class="page-item active" aria-current="page"><span> ... </span></li>';
                                            $skipped = false;
                                            // echo "<a href='test2.php?pageno=" . $page_number . "'>" . $page_number . "</a>";
                                            ?>
                                            <li class="page-item active" aria-current="page"><a class='page-link'
                                                    href="<?= $_SERVER['REQUEST_URI'] ?>&pageno=<?= $page_number ?>"><?= $page_number ?></a>
                                            </li>
                                            <?php
                                        } else {
                                            $skipped = true;
                                        }
                                    }
                                    ?>


                                    <li class="page-item-total" style="margin-right: 10px">of <?= $total_pages . " " ?>
                                    </li>
                                    <?php echo $paginationnext; ?>
                                </ul>
                            </nav>
                        </div><!-- End .col-lg-9 -->
                        <aside class="col-lg-3 order-lg-first">
                            <div class="sidebar sidebar-shop">
                                <div class="widget widget-clean">
                                    <label>Filters:</label>
                                    <a href="#" class="sidebar-filter-clear">Clean All</a>
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
                                            <div class="filter-items filter-items-count">

                                                <?php
                                                $cat = new Category();
                                                $stmt = $cat->get_cat_specific($_GET['catid']);

                                                $i = 1;
                                                if ($cat->get_cat_specific_count($_GET['catid']) > 0) {

                                                }
                                                while ($row = $stmt->fetch()) {
                                                    if (isset($_GET['category']))
                                                        $selected_category_for_selection = explode(',', implode(", ", $_GET['category']));
                                                    else
                                                        $selected_category_for_selection = explode(',', "-1, -2");


                                                    ?>
                                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"
                                                        method="get">


                                                        <div class="filter-item">
                                                            <?php if ($cat->count_inventory_items_by_category($row['cat_id']) !== 0) { ?>
                                                                <div class="custom-control custom-checkbox">

                                                                    <input type="checkbox" name="category[]"
                                                                        value="<?= $row['cat_id']; ?>"
                                                                        class="custom-control-input" id="cat-<?= $i ?>" <?php if ((in_array($row['cat_id'], $selected_category_for_selection)))
                                                                            echo "checked" ?>>

                                                                        <label class="custom-control-label" for="cat-<?= $i ?>">
                                                                        <?= $row['categoryName'] ?></label>
                                                                </div><!-- End .custom-checkbox -->

                                                                <span
                                                                    class="item-count"><?= $cat->count_inventory_items_by_category($row['cat_id']); ?></span>
                                                            <?php } ?>
                                                        </div><!-- End .filter-item -->
                                                        <?php $i++;
                                                } ?>

                                                    <input hidden="text" name="catid" value="<?= $_GET['catid'] ?>">

                                                    <p style="text-align: left; margin-top: 12px;"><input
                                                            style="width: 95%;" value="Filter"
                                                            class="btn btn-outline-dark" type="submit"> </p>
                                                </form>

                                            </div><!-- End .filter-items -->
                                        </div><!-- End .widget-body -->
                                    </div><!-- End .collapse -->
                                </div><!-- End .widget -->


                                <!--
                                </div><!-- End .widget -->

                                <!--    <div class="widget widget-collapsible">
                                    <h3 class="widget-title">
                                        <a data-toggle="collapse" href="#widget-5" role="button" aria-expanded="true" aria-controls="widget-5">
                                            Price
                                        </a>
                                    </h3><!-- End .widget-title -->
                                <!--
                                    <div class="collapse show" id="widget-5">
                                        <div class="widget-body">
                                            <div class="filter-price">
                                                <div class="filter-price-text">
                                                    Price Range:
                                                    <span id="filter-price-range"></span>
                                                </div><!-- End .filter-price-text -->

                                <!--    <div id="price-slider"></div><!-- End #price-slider -->
                                <!--     </div><!-- End .filter-price -->
                                <!--    </div><!-- End .widget-body -->
                                <!-- </div><!-- End .collapse -->


                            </div><!-- End .sidebar sidebar-shop -->
                        </aside><!-- End .col-lg-3 -->
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
    <?php include "jsfile.php"; ?>

    <!-- Main JS File -->
</body>



</html>

<?php
function decript_string($string)
{
    $string2 = explode(",", $string);
    foreach ($string2 as $key => $value) {
        if (strlen($value) === 0) {
            unset($string2[$key]);
        }
    }
    return (array_unique($string2));
}
?>