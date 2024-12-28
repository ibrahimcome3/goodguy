<?php

include "includes.php";

$Orvi = new Review();
$var_obj = new Variation();
$p = new ProductItem();
$product_obj = new ProductItem();
$promotion = new Promotion();
$cat = new Category();
?>

<!DOCTYPE html>
<html lang="en">


<!-- molla/index-13.html  22 Nov 2019 09:59:06 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>GG - GoodGuyng.com</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
        
    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">

<style>
    .flexbox {
    display: flex;
    flex-wrap: wrap;
    min-height: 200px;
    }
    
           .truncate {
   overflow: hidden;
   text-overflow: ellipsis;
   display: -webkit-box;
   -webkit-line-clamp: 2; /* number of lines to show */
           line-clamp: 2;
   -webkit-box-orient: vertical;
}

#flexbox .box {

    -webkit-flex: 130px 1;

    }

#flexbox .box.bigger {

    -webkit-flex: 330px 1;

    }
</style>
</head>

<body>
    <div class="page-wrapper">

         <?php include 'header.php'; ?>

        <main class="main">
            <div class="intro-slider-container">
                <div class="intro-slider owl-carousel owl-simple owl-nav-inside" data-toggle="owl" data-owl-options='{
                        "nav": false,
                        "responsive": {
                            "992": {
                                "nav": true
                            }
                        }
                    }'>
                    <div class="intro-slide" style="background-image: url(index_pics/slide-23.png);">
                        <div class="container intro-content">
                            <div class="row">
                                <div class="col-auto offset-lg-3 intro-col">
                                    <h3 class="intro-subtitle">Trade-In Offer</h3><!-- End .h3 intro-subtitle -->
                                    <h1 class="intro-title">Pure heaven Grape Juice <br>Can
                                        <span>
                                            <sup class="font-weight-light">from</sup>
                                            <span class="text-primary">N14,900<sup></sup></span>
                                        </span>
                                    </h1><!-- End .intro-title -->

                                    <a href="" class="btn btn-outline-primary-2">
                                        <span>Shop Now</span>
                                        <i class="icon-long-arrow-right"></i>
                                    </a>
                                </div><!-- End .col-auto offset-lg-3 -->
                            </div><!-- End .row -->
                        </div><!-- End .container intro-content -->
                    </div><!-- End .intro-slide -->

                    <div class="intro-slide" style="background-image: url(index_pics/slide-2-cloves.png);">
                        <div class="container intro-content">
                            <div class="row">
                                <div class="col-auto offset-lg-3 intro-col">
                                    <h3 class="intro-subtitle">Spices in Lagos Market</h3><!-- End .h3 intro-subtitle -->
                                    <h1 class="intro-title">Original Cloves <br>
                                        <span>
                                            <sup class="font-weight-light line-through">N5000</sup>
                                            <span class="text-primary">N4500<sup></sup></span> <span class="intro-subtitle">per kg</span>
                                        </span>
                                    </h1><!-- End .intro-title -->

                                    <a href="category.html" class="btn btn-outline-primary-2">
                                        <span>Shop Now</span>
                                        <i class="icon-long-arrow-right"></i>
                                    </a>
                                </div><!-- End .col-auto offset-lg-3 -->
                            </div><!-- End .row -->
                        </div><!-- End .container intro-content -->
                    </div><!-- End .intro-slide -->

                    <div class="intro-slide" style="background-image: url(index_pics/slide-2-oak-perfume.png);">
                        <div class="container intro-content">
                            <div class="row">
                                <div class="col-auto offset-lg-3 intro-col">
                                    <h3 class="intro-subtitle">Perfumes</h3><!-- End .h3 intro-subtitle -->
                                    <h1 class="intro-title">Original Oak <br>
                                        <span>
                                            <sup class="font-weight-light line-through">N35,000</sup>
                                            <span class="text-primary">N29,000<sup></sup></span>
                                        </span>
                                    </h1><!-- End .intro-title -->

                                    <a href="category.html" class="btn btn-outline-primary-2">
                                        <span>Shop Now</span>
                                        <i class="icon-long-arrow-right"></i>
                                    </a>
                                </div><!-- End .col-auto offset-lg-3 -->
                            </div><!-- End .row -->
                        </div><!-- End .container intro-content -->
                    </div><!-- End .intro-slide -->
                </div><!-- End .owl-carousel owl-simple -->

                <span class="slider-loader"></span><!-- End .slider-loader -->
            </div><!-- End .intro-slider-container -->

            <div class="mb-4"></div><!-- End .mb-2 -->

           

            <div class="container">    
            <div class="heading heading-flex heading-border mb-3">
                        <div class="heading-left">
                            <h2 class="title">Hot Deals Products</h2><!-- End .title -->
                        </div><!-- End .heading-left -->

                     
            </div>
            <br/>
            <div class="products mb-3">
                                <div class="row justify-content-center">
                     
                   



                                  
                            <?php
                            $sql = "SELECT * from productitem left join inventoryitem on productitem.productID = inventoryitem.productItemID where inventoryitem.category in (select `cat_id` from category_new  where JSON_UNQUOTE(JSON_EXTRACT(json_, '$.roots')) = 'ELECTRONICS') ORDER BY RAND() limit 30";
                            $result = $mysqli->query($sql);
                            $getallproduct = 4;

                            while($row = mysqli_fetch_array($result)){  ?>
                              <div class="col-6 col-md-4 col-lg-4 col-xl-3">
                            <div class="product">
                                <figure class="product-media">
                                       <?php if($promotion->check_if_item_is_in_promotion($product_obj->get_product_id($row['InventoryItemID'])) != null){  ?>
                                                    <span class="product-label label-sale">Sale</span>
                                                    <?php
                                                    }
                                                    if(in_array($product_obj->get_product_id($row['InventoryItemID']), $product_obj->get_all_product_items_that_are_less_than_one_month())){ ?>
                                                    <span class="product-label label-new">New</span>
                                                    <?php } ?>
                                    <a href="product-detail.php?itemid=<?=$row['InventoryItemID'] ?>">
                                        <img src="<?= $p->get_image($row['InventoryItemID']) ?>" alt="Product image" class="product-image">
                                    </a>

                                    <div class="product-action-vertical">
                                        <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>

                                        <a href="<?= $p->get_image($row['InventoryItemID']) ?>" class="btn-product-icon btn-quickview" title="Quick view"><span>Quick view</span></a>
                                    </div><!-- End .product-action-vertical -->

                                    <div class="product-action">
                                        <a href="#" product-info="<?=$row['InventoryItemID']?>" class="submit-cart btn-product btn-cart" title="Add to cart"><span>add to cart</span></a>
                                    </div><!-- End .product-action -->
                                </figure><!-- End .product-media -->

                                <div class="product-body">
                                    <div class="product-cat">
                                            <a href="category.php?catid=<?=$cat->get_category_by_id($row['category'])?>"><?= $cat->get_parent_category_name($row['InventoryItemID']) ?></a>
                                    </div><!-- End .product-cat -->
                                    <h3 class="product-title"><a href="product-detail.php?itemid=<?=$row['InventoryItemID'] ?>"><?= $row['small_description'] ?></a></h3><!-- End .product-title -->
                                    <?php if($promotion->check_if_item_is_in_inventory_promotion($row['InventoryItemID'])){ ?>
                                        <div class="product-price">
                                            <span class="new-price">N<?=" ".number_format($promotion->get_promoPrice_price($row['InventoryItemID']), 2) ?></span>
                                            <span class="old-price">Was N<?=" ".number_format($promotion->get_regular_price($row['InventoryItemID']), 2) ?></span>
                                        </div><!-- End .product-price -->
                                        <?php }else{ ?>
                                        <div class="product-price">
                                        <span class="new-price">N<?=" ".number_format($row['cost'], 2) ?></span>
                                        </div><!-- End .product-price -->
                                        <?php } ?>
                                    <div class="ratings-container">
                                        <div class="ratings">
                                            <div class="ratings-val" style="width: <?=$Orvi->get_rating_($row['InventoryItemID'])?>%"></div><!-- End .ratings-val -->
                                        </div><!-- End .ratings -->
                                        <span class="ratings-text">( <?= $Orvi->get_rating_review_number($row['InventoryItemID']); ?> Reviews )</span>
                                    </div><!-- End .rating-container -->
                                </div><!-- End .product-body -->
                                       <div class="product-image-gallery">
                                                <?php  $p_obj  = new ProductItem();
                                                $stmt = $p_obj->get_other_images_of_item_in_inventory_not_1($row['InventoryItemID']);

                                                if($stmt != null){
                                                while($r = $stmt->fetch()) { ?>
                                                <a class="product-gallery-item" href="#" data-image="<?= $r['image_path'] ?>" data-zoom-image="<?= $r['image_path'] ?>">
                                                        <img src="<?= $r['image_path'] ?>" alt="product side">
                                                </a>
                                                <?php }
                                                                                                 }
                                                ?>

                                            </div>
                            </div><!-- End .product -->
                                    </div><!-- End .col-sm-6 col-lg-4 col-xl-3 -->
                            <?php }  ?>  
                                   
                                </div><!-- End .row -->
                            </div>
        </div> 
         
            


            <div class="mb-3"></div><!-- End .mb-3 -->

            <div class="container electronics">
                <div class="heading heading-flex heading-border mb-3" style="padding-bottom: 15px;">
                    <div class="heading-left">
                        <h2 class="title">Electronics</h2><!-- End .title -->
                    </div><!-- End .heading-left -->

                </div><!-- End .heading -->


                    <div class="flexbox" style="width: 100%;" id="elec-new-tab" role="tabpanel" aria-labelledby="elec-new-link">
                       

                            <?php
                            $sql = "SELECT * from productitem left join inventoryitem on productitem.productID = inventoryitem.productItemID where inventoryitem.category in (select `cat_id` from category_new  where JSON_UNQUOTE(JSON_EXTRACT(json_, '$.roots')) = 'ELECTRONICS') ORDER BY RAND() limit 12";
                            $result = $mysqli->query($sql);
                            $getallproduct = 4;

                            while($row = mysqli_fetch_array($result)){  ?>
                            <div class="product" style="max-width: 220px; margin: auto;">
                                <figure class="product-media">
                                       <?php if($promotion->check_if_item_is_in_promotion($product_obj->get_product_id($row['InventoryItemID'])) != null){  ?>
                                                    <span class="product-label label-sale">Sale</span>
                                                    <?php
                                                    }
                                                    if(in_array($product_obj->get_product_id($row['InventoryItemID']), $product_obj->get_all_product_items_that_are_less_than_one_month())){ ?>
                                                    <span class="product-label label-new">New</span>
                                                    <?php } ?>
                                    <a href="product-detail.php?itemid=<?=$row['InventoryItemID'] ?>">
                                        <?php                $pid = $product_obj->get_product_id($row['InventoryItemID']);
                                                             if($product_obj->check_dirtory_resized_600($pid,$row['InventoryItemID'])){
                                                             
                                                                $i = $row['InventoryItemID'];
                                                                $pi = glob("products/product-$pid/product-$pid-image/inventory-$pid-$i/resized_600/".'*.{jpg,gif}', GLOB_BRACE);
                                                                $img = $pi[0];
                                                             }else{
                                                                $img = $p->get_image($row['InventoryItemID']);
                                                            }
                                        ?>
                                        <img src="<?= $img ?>" alt="Product image" class="product-image">
                                    </a>


                                    <div class="product-action-vertical">
                                        <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>

                                        <a href="<?= $p->get_image($row['InventoryItemID']) ?>" class="btn-product-icon btn-quickview" title="Quick view"><span>Quick view</span></a>
                                    </div><!-- End .product-action-vertical -->

                                    <div class="product-action">
                                        <a href="#" product-info="<?=$row['InventoryItemID']?>" class="submit-cart btn-product btn-cart" title="Add to cart"><span>add to cart</span></a>
                                    </div><!-- End .product-action -->
                                </figure><!-- End .product-media -->

                           <div class="product-body">
                                    <div class="product-cat">
                                            <a href="category.php?catid=<?=$cat->get_category_by_id($row['category'])?>"><?= $cat->get_parent_category_name($row['InventoryItemID']) ?></a>
                                    </div><!-- End .product-cat -->
                                    <h3 class="product-title truncate"><a href="product-detail.php?itemid=<?=$row['InventoryItemID'] ?>"><?= $row['small_description'] ?></a></h3><!-- End .product-title -->
                                    <?php if($promotion->check_if_item_is_in_inventory_promotion($row['InventoryItemID'])){ ?>
                                        <div class="product-price">
                                            <span class="new-price">N<?=" ".number_format($promotion->get_promoPrice_price($row['InventoryItemID']), 2) ?></span>
                                            <span class="old-price">Was N<?=" ".number_format($promotion->get_regular_price($row['InventoryItemID']), 2) ?></span>
                                        </div><!-- End .product-price -->
                                        <?php }else{ ?>
                                        <div class="product-price">
                                        <span class="new-price">N<?=" ".number_format($row['cost'], 2) ?></span>
                                        </div><!-- End .product-price -->
                                        <?php } ?>
                                    <div class="ratings-container">
                                        <div class="ratings">
                                            <div class="ratings-val" style="width: <?=$Orvi->get_rating_($row['InventoryItemID'])?>%"></div><!-- End .ratings-val -->
                                        </div><!-- End .ratings -->
                                        <span class="ratings-text">( <?= $Orvi->get_rating_review_number($row['InventoryItemID']); ?> Reviews )</span>
                                    </div><!-- End .rating-container -->
                                </div><!-- End .product-body -->
                                       <div class="product-image-gallery">
                                                <?php  $p_obj  = new ProductItem();
                                                $stmt = $p_obj->get_other_images_of_item_in_inventory_not_1($row['InventoryItemID']);

                                                if($stmt != null){
                                                $c = 1;        
                                                $i = $row['InventoryItemID'];
                                                while($r = $stmt->fetch()) { if($c == 4) break; $c++;?>
                                            
                                                <a class="product-gallery-item" href="#" data-image="<?= $r['image_path'] ?>" data-zoom-image="<?= $r['image_path'] ?>">
                                                    <?php $img = "products/product-$pid/product-$pid-image/inventory-$pid-$i/resized/".$r['image_name']; ?>
                                                        <img src="<?= $img ?>" alt="product side" />
                                           
                                                </a>
                                                <?php }
                                                                                                 }
                                                ?>

                                            </div>
                            </div><!-- End .product -->

                            <?php }  ?>



                    
                    </div><!-- .End .tab-pane -->
                  

                        </div><!-- End .owl-carousel -->
                    </div><!-- .End .tab-pane -->

   
            </div><!-- End .container -->

            <div class="mb-3"></div><!-- End .mb-3 -->

            


            <div class="mb-3"></div><!-- End .mb-3 -->

            <div class="container">
                <h2 class="title title-border mb-5">Shop by Brands</h2><!-- End .title -->
                <div class="owl-carousel mb-5 owl-simple" data-toggle="owl"
                    data-owl-options='{
                        "nav": false, 
                        "dots": true,
                        "margin": 30,
                        "loop": false,
                        "responsive": {
                            "0": {
                                "items":2
                            },
                            "420": {
                                "items":3
                            },
                            "600": {
                                "items":4
                            },
                            "900": {
                                "items":5
                            },
                            "1024": {
                                "items":6
                            },
                            "1280": {
                                "items":6,
                                "nav": true,
                                "dots": false
                            }
                        }
                    }'>
                    
                    <?php
                    $sql = "SELECT * FROM `brand` WHERE CHAR_LENGTH(`image`) > 0;";
                    $result = $mysqli->query($sql);
                    while($row = $result->fetch_assoc()){
                
                    ?>
                    <a href="#" class="brand">
                        <img src="<?= $row['image'] ?>" alt="<?= $row['Name']?>">
                    </a>
                    <?php } ?>
                
                </div><!-- End .owl-carousel -->
            </div><!-- End .container -->

            <div class="cta cta-horizontal cta-horizontal-box bg-primary">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-2xl-5col">
                            <h3 class="cta-title text-white">Join Our Newsletter</h3><!-- End .cta-title -->
                            <p class="cta-desc text-white">Subcribe to get information about products and coupons</p><!-- End .cta-desc -->
                        </div><!-- End .col-lg-5 -->

                        <div class="col-3xl-5col">
                            <form action="newslatter.php" method="POST">
                                <div class="input-group">
                                    <input type="email" name="newsletter" class="form-control form-control-white" placeholder="Enter your Email Address" aria-label="Email Adress" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-white-2" type="submit"><span>Subscribe</span><i class="icon-long-arrow-right"></i></button>
                                    </div><!-- .End .input-group-append -->
                                </div><!-- .End .input-group -->
                            </form>
                        </div><!-- End .col-lg-7 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .cta -->


        </main><!-- End .main -->

        <footer class="footer footer-2">
            <div class="icon-boxes-container">
                <div class="container">
                    <div class="row">
                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side">
                                <span class="icon-box-icon">
                                    <i class="icon-rocket"></i>
                                </span>

                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">Free Shipping</h3><!-- End .icon-box-title -->
                                    <p>Orders 100K or more</p>
                                </div><!-- End .icon-box-content -->
                            </div><!-- End .icon-box -->
                        </div><!-- End .col-sm-6 col-lg-3 -->
                        
                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side">
                                <span class="icon-box-icon">
                                    <i class="icon-rotate-left"></i>
                                </span>

                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">Free Returns</h3><!-- End .icon-box-title -->
                                    <p>Within 10 days</p>
                                </div><!-- End .icon-box-content -->
                            </div><!-- End .icon-box -->
                        </div><!-- End .col-sm-6 col-lg-3 -->

                     

                        <div class="col-sm-6 col-lg-3">
                            <div class="icon-box icon-box-side">
                                <span class="icon-box-icon">
                                    <i class="icon-life-ring"></i>
                                </span>

                                <div class="icon-box-content">
                                    <h3 class="icon-box-title">We Support</h3><!-- End .icon-box-title -->
                                    <p>24/7 amazing services</p>
                                </div><!-- End .icon-box-content -->
                            </div><!-- End .icon-box -->
                        </div><!-- End .col-sm-6 col-lg-3 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .icon-boxes-container -->

        <?php include "footer.php"; ?>


        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

     <?php include "mobile-menue-index-page.php"; ?>
        <!-- Sign in / Register Modal -->
     <?php include "login-modal.php"; ?>

   

    
 


</body>
<?php include "jsfile.php"; ?>

<!-- molla/index-13.html  22 Nov 2019 09:59:31 GMT -->
</html>