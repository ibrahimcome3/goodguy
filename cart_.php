<!DOCTYPE html>
<?php require_once "includes.php"; ?>
<?php

$_SESSION['next_url'] = 'shipping-address-selection.php';//$_SERVER['REQUEST_URI'];

unset($_SESSION['cart_final']);
//var_dump($_SESSION['cart_final']);


$promotion = new Promotion();
$cat = new Category();




// Check the session variable for products in cart
$products_in_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$products = array();
$subtotal = 0.00;
// If there are products in cart
if ($products_in_cart) {

    // There are products in the cart so we need to select those products from the database
    // Products in cart array to question mark string array, we need the SQL statement to include IN (?,?,?,...etc)
    $array_to_question_marks = implode(',', array_keys($products_in_cart));
    // var_dump($array_to_question_marks);
    $r = $result = $mysqli->query('SELECT * FROM inventoryitem WHERE InventoryItemID IN (' . $array_to_question_marks . ')');
    // We only need the array keys, not the values, the keys are the id's of the products
    //$stmt->execute(array_keys($products_in_cart));
    // Fetch the products from the database and return the result as an Array

    // Calculate the subtotal
    while ($product = mysqli_fetch_array($result)) {

        if ($promotion->check_if_item_is_in_inventory_promotion($product['InventoryItemID'])) {
            $subtotal += (float) $promotion->get_promoPrice_price($product['InventoryItemID']) * (int) $products_in_cart[$product['InventoryItemID']];
        } else {
            $subtotal += (float) $product['cost'] * (int) $products_in_cart[$product['InventoryItemID']];
        }
    }
}

?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cart</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <br>
        <?php include "header-for-other-pages.php"; ?>
        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->
            <?php
            if ($products_in_cart) {
                //echo "yes";
                $array_to_question_marks = implode(',', array_keys($products_in_cart));
                $sql = 'SELECT * FROM inventoryitem left join productitem on inventoryitem.`productItemID` = productitem.`productID` WHERE inventoryitem.InventoryItemID IN (' . $array_to_question_marks . ')';
                //echo $sql;
                $result = $mysqli->query($sql);

                //var_dump($result);
            
                //while($row = mysqli_fetch_assoc($result)){
                // var_dump($row);
                //}
            }
            ?>
            <div class="page-content">
                <div class="cart">
                    <div class="container">

                        <div class="row">
                            <div class="col-lg-9">
                                <form action="update_cart.php" method="post" class="update_cart_form">

                                    <table class="table table-cart table-mobile">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th>Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if (empty($result)) { ?>
                                                <tr class="summary-shipping-row">
                                                    <td style="text-align:center;">You have no products added in your
                                                        Shopping Cart</td>
                                                </tr>
                                            <?php } else { ?>
                                                <?php
                                               $product_obj = $p = new ProductItem();
                                                while ($row = mysqli_fetch_assoc($result)) { ?>
                                                 
                                                    <?php //var_dump($row);
                                                           $i = $product_id_cost = $row['InventoryItemID'];

                                                            //echo $product_id."<br/>";
                                                            if (isset($_SESSION['cart_final']) && is_array($_SESSION['cart_final'])) {
                                                                //echo "yes";
                                                                if (array_key_exists($product_id_cost, $_SESSION['cart_final'])) {

                                                                    // Product exists in cart so just update the quanity
                                                                    if ($promotion->check_if_item_is_in_inventory_promotion($product_id_cost)) {
                                                                        $_SESSION['cart_final'][$product_id_cost] = $promotion->get_promoPrice_price($row['InventoryItemID']) * $products_in_cart[$row['InventoryItemID']];
                                                                    } else {
                                                                        $_SESSION['cart_final'][$product_id_cost] = $row['cost'] * $products_in_cart[$row['InventoryItemID']];
                                                                    }
                                                                } else {
                                                                    // Product is not in cart so add it
                                                                    if ($promotion->check_if_item_is_in_inventory_promotion($product_id_cost)) {
                                                                        $_SESSION['cart_final'][$product_id_cost] = $promotion->get_promoPrice_price($row['InventoryItemID']) * $products_in_cart[$row['InventoryItemID']];
                                                                    } else {
                                                                        $_SESSION['cart_final'][$product_id_cost] = $row['cost'] * $products_in_cart[$row['InventoryItemID']];
                                                                    }
                                                                }
                                                            } else {

                                                                // There are no products in cart, this will add the first product to cart
                                                                if ($promotion->check_if_item_is_in_inventory_promotion($product_id_cost)) {
                                                                    $_SESSION['cart_final'] = array($product_id_cost => $promotion->get_promoPrice_price($row['InventoryItemID']) * $products_in_cart[$row['InventoryItemID']]);
                                                                } else {
                                                                    $_SESSION['cart_final'] = array($product_id_cost => $row['cost'] * $products_in_cart[$row['InventoryItemID']]);
                                                                }
                                                                //var_dump( $_SESSION);
                                                            }


                                                            ?>
                                                    <tr>
                                                        <td class="product-col">
                                                            <div class="product">
                                                                <figure class="product-media">
                                                                    <a href="#">
                                                                        <?php 
                                                                        $pid = $product_obj->get_product_id($row['InventoryItemID']);
                                                                         if($product_obj->check_dirtory_resized_600($pid,$row['InventoryItemID'])){
                                                                            $i = $row['InventoryItemID'];
                                                                            $pi = glob("products/product-$pid/product-$pid-image/inventory-$pid-$i/resized_600/".'*.{jpg,gif}', GLOB_BRACE);
                                                                            $img = $pi[0];
                                                                         }else{
                                                                            $img = $p->get_image($row['InventoryItemID']);
                                                                        }
                                                            ?>
                                                                        
                                                                        
                                                                        
                                                                        <img src="<?php echo $img; ?>" alt="Product image">
                                                                    </a>
                                                                </figure>

                                                                <h3 class="product-title">
                                                                    <a href="">
                                                                        <?= $row['description'] ?>
                                                                    </a>
                                                                </h3><!-- End .product-title -->
                                                            </div><!-- End .product -->
                                                            <p>
                                                                <?php
                                                                $v = new Variation();
                                                                echo $v->get_all_properties($row['InventoryItemID']);
                                                                ?>

                                                            </p>
                                                        </td>
                                                        <?php if ($promotion->check_if_item_is_in_inventory_promotion($product_id_cost)) { ?>

                                                            <td class="price-col">
                                                                <?= "&#x20A6;" . $promotion->get_promoPrice_price($row['InventoryItemID']) ?>
                                                            </td>
                                                        <?php } else { ?>

                                                            <td class="price-col">
                                                                <?= "&#x20A6;" . $row['cost'] ?>
                                                            </td>
                                                        <?php } ?>
                                                        <td class="quantity-col">
                                                            <div class="cart-product-quantity">
                                                                <input type="number"
                                                                    name="quantity-<?= $row['InventoryItemID'] ?>"
                                                                    class="form-control"
                                                                    value="<?= $products_in_cart[$row['InventoryItemID']] ?>"
                                                                    min="1" max="10" step="1" data-decimals="0" required>
                                                            </div><!-- End .cart-product-quantity -->
                                                        </td>
                                                        <?php if ($promotion->check_if_item_is_in_inventory_promotion($product_id_cost)) { ?>
                                                            <td class="total-col">
                                                                <?= "&#8358; " . $promotion->get_promoPrice_price($row['InventoryItemID']) * $products_in_cart[$row['InventoryItemID']] ?>
                                                            </td>
                                                        <?php } else { ?>
                                                            <td class="total-col">
                                                                <?= "&#8358; " . $row['cost'] * $products_in_cart[$row['InventoryItemID']] ?>
                                                            </td>
                                                        <?php } ?>
                                                        <td class="remove-col"><button class="btn-remove"
                                                                cart-item-id=<?= $row['InventoryItemID'] ?>><i
                                                                    class="icon-close"></i></button></td>
                                                    </tr>
                                                <?php }
                                            } //var_dump($_SESSION['cart_final']);                       ?>
                                        </tbody>

                                    </table><!-- End .table table-wishlist -->
                                </form>


                                <div class="cart-bottom">
                                    <!--
                                <div class="cart-discount">
                                        <form action="#">
                                            <div class="input-group">
                                                <input type="text" class="form-control" required placeholder="coupon code">
                                                <div class="input-group-append">
                                                    <button class="btn btn-outline-primary-2" type="submit"><i class="icon-long-arrow-right"></i></button>
                                                </div><!-- .End .input-group-append -->
                                    <!-- 	</div>--> <!-- End .input-group -->
                                    <!-- 	</form>  -->
                                    <!-- 	</div>--> <!-- End .cart-discount -->

                                    <button class="update_cart btn btn-outline-dark-2"><span>UPDATE CART</span><i
                                            class="icon-refresh"></i></button>
                                </div><!-- End .cart-bottom -->
                            </div><!-- End .col-lg-9 -->
                            <aside class="col-lg-3">
                                <div class="summary summary-cart">
                                    <h3 class="summary-title">Cart Total</h3><!-- End .summary-title -->
                                    <br />
                            
                                    <table class="table table-summary">
                                        <?php $_SESSION['totalcost'] = $subtotal;
                                         ?>

                                        <input type="hidden" id="subtotal" value=<?= $subtotal ?> />
                                       <!-- <input type="text" id="shipping_h" value="" /> -->
                                        <tbody>
                                            <tr class="summary-subtotal">
                                                <td>Subtotal:</td>
                                                <td>
                                                    <?= "&#8358; " . $subtotal ?>
                                                </td>
                                            </tr><!-- End .summary-subtotal -->
                                            <tr class="summary-shipping">
                                                <td>Shipping: </td>
                                                <td id="shipping-cost">N0.00</td>
                                            </tr>
                                            <tr class="summary-total">
                                                <td>Total:</td>
                                                <td id="subtotal" orginal-subtotal=<?= $subtotal ?>><?= $subtotal ?></td>
                                            </tr><!-- End .summary-total -->
                                        </tbody>
                                    </table><!-- End .table table-summary -->

                                    <a href="checkout-process-validation.php" id="proceed"
                                        class="btn btn-outline-primary-2 btn-order btn-block">PROCEED TO CHECKOUT</a>
                                </div><!-- End .summary -->

                                <a href="index.php" class="btn btn-outline-dark-2 btn-block mb-3"><span>CONTINUE
                                        SHOPPING</span><i class="icon-refresh"></i></a>
                            </aside><!-- End .col-lg-3 -->
                        </div><!-- End .row -->
                    </div><!-- End .container -->
                </div><!-- End .cart -->
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
    <?php include "login-module.php"; ?>

    <!-- Plugins JS File -->

    <!-- Main JS File -->
    <?php include "jsfile.php"; ?>

   

    <script>
        $(document).ready(function () {
           /* let shipment = 0;
            var newvalue;
            var subtotal = jQuery(".summary-total").find("td:eq(1)");
            $('#shipment').on('change', function () {
                console.log($("#shipping-cost").text($('#shipment').find(":selected").attr("shipment-price")));
                shipment = $('#shipment').find(":selected").attr("shipment-price");
                //alert(shipment);
                newvalue = parseInt($("#subtotal").val()) + parseInt(shipment);
                subtotal.text(newvalue);
                $("#shipping_h").val(shipment);
                $("#proceed").attr('href', "checkout-process-validation.php?shipping=" + shipment);

            });

            //var shiping_  = 1300;
            //var express_shipping  = 3000;
            $("button.update_cart").click(function (event) {
                $("form.update_cart_form").submit();
                event.preventDefault() // Submit the form
            });

            $("#proceed").click(function (event) {
                if ($('#shipment option').filter(':selected').val() == -1) {
                    alert("Please select a shipping cost associated to your address");
                    return false;
                }

            });



            $("button.btn-remove").click(function (event) {
                event.preventDefault();
                var del_title = $(this).attr('cart-item-id');
                var item_to_removed = $(this).closest('tr');
                $.ajax({
                    type: 'POST',
                    cache: false,
                    url: 'remove_product_from_cart.php',
                    dataType: "json",
                    data: { remove: del_title },
                    success: function (data) {
                        item_to_removed.remove();
                        location.reload(true);
                    }
                });
            });

            // var expressshipping, shipping;
            // expressshipping = $("#expressship");
            //shipping = $("#shipping");
            //     $("#express-shipping").click(function() {
            //          if ($('#express-shipping').is(':checked')) {
            //var newvalue = parseInt($("#subtotal").val()) + parseInt($("#express-shipping").val());
            //$("td#subtotal").text(newvalue);
            //     $("#proceed").attr('href', "checkout-process-validation.php?express_shipping="+express_shipping);

            //  }

            //   });

            //          $("#free-shipping").click(function() {
            //         if ($('#free-shipping').is(':checked')) {
            //var newvalue = parseInt($("#subtotal").val()) + parseInt($("#free-shipping").val());
            //$("td#subtotal").text(newvalue);
            //     $("#proceed").attr('href', "checkout-process-validation.php?shipping="+shiping_);

            //  }
            // });
            /*
              if ($('#free-shipping').is(':checked')) {
      
                      var newvalue = parseInt($("#subtotal").val()) + parseInt($("#free-shipping").val());
      
                      $("td#subtotal").text(newvalue);
                      var href = $("#proceed").attr('href');
      
                      $("#proceed").attr('href', href+"?shipping="+shiping_);
              }
      
              if ($('#express-shipping').is(':checked')) {
                     var newvalue = parseInt($("#subtotal").val()) + parseInt($("#express-shipping").val());
                     var href = $("#proceed").attr('href');
                     $("td#subtotal").text(newvalue);
                     $("#proceed").attr('href', href+"?express_shipping="+express_shipping);
      
              }
      
      
              $('#free-shipping').add('#express-shipping').on('click', function(event){
                         var subtotal =jQuery(".summary-total").find("td:eq(1)");
                      if ($(this).is(':checked')) {
                          subtotal.text(parseInt($("#subtotal").val()) + parseInt($(this).val()));
                          }
                      //event.preventDefault() // Submit the form
                  });
              //$("#free-shipping #express-shipping").click(function(event){
               //       if ($(this).is(':checked')) {alert(1);}
                      //event.preventDefault() // Submit the form
               //   });
      */
        });


    </script>
</body>


<!-- molla/cart.html  22 Nov 2019 09:55:06 GMT -->

</html>