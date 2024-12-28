<?php require_once "includes.php";   ?>
<!DOCTYPE html>
<?php
 $cart_final = Array();
 $cart_final = $_SESSION['cart_final'];
 //var_dump($cart_final);




// Check the session variable for products in cart
$products_in_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$products = array();
$subtotal = 0.00;
// If there are products in cart
if ($products_in_cart) {

    // There are products in the cart so we need to select those products from the database
    // Products in cart array to question mark string array, we need the SQL statement to include IN (?,?,?,...etc)
    $array_to_question_marks = implode(',', array_keys($products_in_cart));
            //var_dump($array_to_question_marks);
    $r = $result = $mysqli->query('SELECT * FROM inventoryitem WHERE InventoryItemID IN (' . $array_to_question_marks . ')');
    // We only need the array keys, not the values, the keys are the id's of the products
    //$stmt->execute(array_keys($products_in_cart));
    // Fetch the products from the database and return the result as an Array

    // Calculate the subtotal
    while ($product = mysqli_fetch_array($result)) {

        $subtotal += (float)$product['cost'] * (int)$products_in_cart[$product['InventoryItemID']];
    }
}

?>
<html lang="en">


<!-- molla/checkout.html  22 Nov 2019 09:55:06 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Check Out</title>
    <meta name="keywords" content="HTML5 Template">
    <meta name="description" content="Molla - Bootstrap eCommerce Template">
    <meta name="author" content="p-themes">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/icons/favicon-16x16.png">
    <link rel="manifest" href="assets/images/icons/site.html">
    <link rel="mask-icon" href="assets/images/icons/safari-pinned-tab.svg" color="#666666">
    <link rel="shortcut icon" href="assets/images/icons/favicon.ico">
    <meta name="apple-mobile-web-app-title" content="Molla">
    <meta name="application-name" content="Molla">
    <meta name="msapplication-TileColor" content="#cc9966">
    <meta name="msapplication-config" content="assets/images/icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/skins/skin-demo-13.css">
</head>

<body>
    <div class="page-wrapper">
        <br/>
           <?php include "header-for-other-pages.php"; ?>
        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                          <?php  echo breadcrumbs();  ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
            	<div class="checkout">
                    <div class="container">

            			<form action="checkout-process.php" method="post">
                        	<div class="row">
                        		<div class="col-lg-9">
                        			<h2 class="checkout-title">Billing Details</h2><!-- End .checkout-title -->
                        				<div class="row">
                        					<div class="col-sm-6">
                        						<label>First Name *</label>
                        						<input type="text" name="firstname" class="form-control" required>
                        					</div><!-- End .col-sm-6 -->

                        					<div class="col-sm-6">
                        						<label>Last Name *</label>
                        						<input type="text" name="lastname" class="form-control" required>
                        					</div><!-- End .col-sm-6 -->
                        				</div><!-- End .row -->

                						<label>Street address *</label>
                						<input type="text" class="form-control" name="streetaddress1" placeholder="House number and Street name" required>
                						<input type="text" class="form-control" name="streetaddress2" placeholder="Appartments, suite, unit etc ..." required>

                						<div class="row">
                        					<div class="col-sm-6">
                        						<label>Town / City *</label>
                        						<input type="text" name="city" class="form-control" required>
                        					</div><!-- End .col-sm-6 -->

                        					<div class="col-sm-6">
                        						<label>State *</label>
                        						<input type="text" name="state" class="form-control" required>
                        					</div><!-- End .col-sm-6 -->
                        				</div><!-- End .row -->

                        				<div class="row">
                        					<div class="col-sm-6">
                        						<label>Postcode / ZIP *</label>
                        						<input type="text" name="zip" class="form-control">
                        					</div><!-- End .col-sm-6 -->

                        					<div class="col-sm-6">
                        						<label>Phone *</label>
                        						<input type="tel" name="phone" class="form-control" required>
                        					</div><!-- End .col-sm-6 -->
                        				</div><!-- End .row -->

                    					<label>Email address *</label>
            							<input type="email" name="email" class="form-control" required>

            							<div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="create_an_account" class="custom-control-input" id="checkout-create-acc">
                                            <label class="custom-control-label" for="checkout-create-acc">Create an account?</label>
                                        </div><!-- End .custom-checkbox -->

                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" name="is_this_your_Shipping_address" class="custom-control-input" id="checkout-diff-address">
                                            <label class="custom-control-label" for="checkout-diff-address">is this your Shipping address?</label>
                                        </div><!-- End .custom-checkbox -->

                    					<label>Order notes (optional)</label>
            							<textarea class="form-control" name="notes" cols="30" rows="4" placeholder="Notes about your order, e.g. special notes for delivery"></textarea>
                        		</div><!-- End .col-lg-9 -->
                        		<aside class="col-lg-3">
                        			<div class="summary">
                        				<h3 class="summary-title">Your Order</h3><!-- End .summary-title -->

                        				<table class="table table-summary">
                        					<thead>
                        						<tr>
                        							<th>Product</th>
                        							<th>Total</th>
                        						</tr>
                        					</thead>

                        					<tbody>
                        					     <?php
                                                   //var_dump($_SESSION['cart_final']);
                                                   $everything = array();
                                                   $val_total = 0;
                                                   foreach ($_SESSION['cart_final'] as $key => $val){
                                                     if(is_int((int)$key))
                                                     array_push($everything,$key);
                                                   }

                                                   //var_dump($everything);
                                                   $array_to_question_marks = implode(',', array_values($everything));
                                                   //echo $array_to_question_marks;
                                                   $sql =  'SELECT * FROM inventoryitem left join productitem on inventoryitem.`productItemID` = productitem.`productID` WHERE inventoryitem.InventoryItemID IN (' . $array_to_question_marks . ')';
                                                   $result = $mysqli->query($sql);
                                                   //var_dump($result);

                                                    while($row = mysqli_fetch_assoc($result)){ ?>
                        						<tr>
                        							<td><a href="#"><?=$row['description']." ".$row['InventoryItemID'] ?></a></td>
                        							<td>
                        							    <?php
                                                        $val_ = 0;

                                                       // var_dump($cart_final);
                                                        foreach ($cart_final as $key=>$val){
                                                               //   var_dump($cart_final);
                                                           // echo "(".$key." ----".$row['InventoryItemID'];
                                                            if( $key == $row['InventoryItemID'])  {
                                                                 $val_ = $val;
                                                                 $val_total +=  $val;
                                                                 echo $val_;
                                                                 break;
                                                        }
                                                    }


                                                        ?>
                        							</td>
                        						</tr>
                                                  <?php } ?>

                        						<tr class="summary-subtotal">
                        							<td>Subtotal:</td>
                        							<td><?=$val_total ?></td>
                        						</tr><!-- End .summary-subtotal -->
                        						<tr>
                        							<td>Shipping:</td>
                        							<td><?php $shipping = 0; if(isset($_GET['shipping'])){ echo $_GET['shipping']; $shipping = $_GET['shipping'];} elseif (isset($_GET['express_shipping'])) { echo $_GET['express_shipping']; $shipping = $_GET['express_shipping'];  } ?></td>
                        						</tr>
                        						<tr class="summary-total">
                        							<td>Total:</td>
                        							<td><?php echo $shipping+$val_total; $_SESSION['shipping'] = $shipping; //var_dump( $_SESSION); ?></td>
                        						</tr><!-- End .summary-total -->
                        					</tbody>
                        				</table><!-- End .table table-summary -->

                        				<div class="accordion-summary" id="accordion-payment">


                                            <div class="card">
                                                <div class="card-header" id="heading-3">
                                                    <h2 class="card-title">
                                                        <a class="collapsed" role="button" data-toggle="collapse" href="#collapse-3" aria-expanded="false" aria-controls="collapse-3">
                                                            Cash on delivery
                                                        </a>
                                                    </h2>
                                                </div><!-- End .card-header -->
                                                <div id="collapse-3" class="collapse" aria-labelledby="heading-3" data-parent="#accordion-payment">
                                                    <div class="card-body">Make your payments once you receive your order.
                                                    </div><!-- End .card-body -->
                                                </div><!-- End .collapse -->
                                            </div><!-- End .card -->


                                        </div><!-- End .accordion -->

                        				<button type="submit" class="btn btn-outline-primary-2 btn-order btn-block">
                        					<span class="btn-text">Place Order</span>
                        					<span class="btn-hover-text">Proceed to Checkout</span>
                        				</button>
                        			</div><!-- End .summary -->
                        		</aside><!-- End .col-lg-3 -->
                        	</div><!-- End .row -->
            			</form>
                    </div><!-- End .container -->
                </div><!-- End .checkout -->
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
    <div class="modal fade" id="signin-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="icon-close"></i></span>
                    </button>
                    <?php include("login-form-dynamic.php");  ?>

                </div><!-- End .modal-body -->
            </div><!-- End .modal-content -->
        </div><!-- End .modal-dialog -->
    </div><!-- End .modal -->

    <?php include "jsfile.php"; ?>
</body>


<!-- molla/checkout.html  22 Nov 2019 09:55:06 GMT -->
</html>