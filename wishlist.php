<!DOCTYPE html>
<?php
require_once "includes.php";
$options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_EMULATE_PREPARES => false,
];
$host = 'localhost';
$db = 'lm_test';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new \PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
if (isset($_SESSION['uid'])) {
    require_once 'conn.php';
    $sql = "SELECT * FROM `wishlist` LEFT JOIN inventoryitem ON wishlist.`inventory_item_id` = inventoryitem.`InventoryItemID` WHERE customer_id = " . $_SESSION['uid'];
    $result = $mysqli->query($sql);
} else {
    header("Location: login.php");
    exit();
}

?>
<html lang="en">



<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Wish list</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header-for-other-pages.php";
        ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <table class="table table-wishlist table-mobile">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock Status</th>
                                <th></th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while ($row = mysqli_fetch_array($result)) {
                               
                                $old_cost = null;
                                if ($promotion->check_if_item_is_in_inventory_promotion($row['inventory_item_id'])) {
                                    $row['cost'] = $promotion->get_promoPrice_price($row['inventory_item_id']);
                                    $old_cost = $promotion->get_regular_price($row['inventory_item_id']);
                                }
                                ?>
                                <tr>
                                    <td class="product-col">
                                        <div class="product">
                                            <figure class="product-media">
                                                <a href="product-detail.php?itemid=<?= $row['inventory_item_id'] ?>">
                                                    <img src="<?php echo getImage($row['inventory_item_id']); ?>"
                                                        alt="Product image">
                                                </a>
                                            </figure>

                                            <h3 class="product-title">
                                                <a href="product-detail.php?itemid=<?= $row['inventory_item_id'] ?>">
                                                    <?= $row["description"] ?></a>
                                            </h3><!-- End .product-title -->
                                        </div><!-- End .product -->
                                    </td>
                                    <td class="price-col"><?= $row["cost"] ?>
                                        <?php if (isset($old_cost) and ($old_cost !== $row["cost"]))
                                            echo "<small><span style=\"text-decoration: line-through\">" . $old_cost . "</span></small>"; ?>
                                    </td>
                                    <td class="stock-col"><span class="in-stock">In stock</span></td>
                                    <!--	<td class="stock-col"><span class="out-of-stock">In stock</span></td>
                               <!--	<td class="action-col">
                                    <button class="btn btn-block btn-outline-primary-2 disabled">Out of Stock</button>
                                </td>
                                -->
                                    <td class="action-col">

                                        <form class="cart_form" action="cart.php" method="post">

                                            <div>
                                                <input type="hidden" name="inventory_product_id"
                                                    value="<?= $row['inventory_item_id'] ?>">
                                                <div class="product-details-quantity"
                                                    style="margin-bottom: 5px; width: 100%;">
                                                    <input name="qty" type="number" id="qty-<?= $row['inventory_item_id'] ?>"
                                                        class="form-control" value="1" min="1" max="10" step="1"
                                                        data-decimals="0" required />
                                                </div>




                                                <button class="submit-cart  btn btn-block btn-outline-primary-2"><i
                                                        class="icon-cart-plus"></i>Add to Cart</button>
                                            </div>

                                        </form>
                                    </td>
                                    <td class="remove-col">
                                    <a href="#" class="remove-from-wishlist" data-wishlist-id="<?= $row['wishlistid'] ?>">Remove</a>
    
                                    <button class="btn-remove"
                                            cart-item-id=<?= $row['inventory_item_id'] ?>><i class="icon-close"></i></button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table><!-- End .table table-wishlist -->
                    <!--	<div class="wishlist-share">
                        <div class="social-icons social-icons-sm mb-2">
                            <label class="social-label">Share on:</label>
                            <a href="#" class="social-icon" title="Facebook" target="_blank"><i class="icon-facebook-f"></i></a>
                            <a href="#" class="social-icon" title="Twitter" target="_blank"><i class="icon-twitter"></i></a>
                            <a href="#" class="social-icon" title="Instagram" target="_blank"><i class="icon-instagram"></i></a>
                            <a href="#" class="social-icon" title="Youtube" target="_blank"><i class="icon-youtube"></i></a>
                            <a href="#" class="social-icon" title="Pinterest" target="_blank"><i class="icon-pinterest"></i></a>
                        </div>
                    
                    </div>
                -->
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

    <div class="mobile-menu-container">
        <div class="mobile-menu-wrapper">
            <span class="mobile-menu-close"><i class="icon-close"></i></span>

            <form action="#" method="get" class="mobile-search">
                <label for="mobile-search" class="sr-only">Search</label>
                <input type="search" class="form-control" name="mobile-search" id="mobile-search"
                    placeholder="Search in..." required>
                <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
            </form>
        </div><!-- End .mobile-menu-wrapper -->
    </div><!-- End .mobile-menu-container -->

    <!-- Sign in / Register Modal -->
    <div class="modal fade" id="signin-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="icon-close"></i></span>
                    </button>

                    <div class="form-box">
                        <div class="form-tab">
                            <ul class="nav nav-pills nav-fill" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="signin-tab" data-toggle="tab" href="#signin"
                                        role="tab" aria-controls="signin" aria-selected="true">Sign In</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab"
                                        aria-controls="register" aria-selected="false">Register</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="tab-content-5">
                                <div class="tab-pane fade show active" id="signin" role="tabpanel"
                                    aria-labelledby="signin-tab">
                                    <form action="#">
                                        <div class="form-group">
                                            <label for="singin-email">Username or email address *</label>
                                            <input type="text" class="form-control" id="singin-email"
                                                name="singin-email" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-group">
                                            <label for="singin-password">Password *</label>
                                            <input type="password" class="form-control" id="singin-password"
                                                name="singin-password" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-footer">
                                            <button type="submit" class="btn btn-outline-primary-2">
                                                <span>LOG IN</span>
                                                <i class="icon-long-arrow-right"></i>
                                            </button>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="signin-remember">
                                                <label class="custom-control-label" for="signin-remember">Remember
                                                    Me</label>
                                            </div><!-- End .custom-checkbox -->

                                            <a href="#" class="forgot-link">Forgot Your Password?</a>
                                        </div><!-- End .form-footer -->
                                    </form>
                                    <div class="form-choice">
                                        <p class="text-center">or sign in with</p>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login btn-g">
                                                    <i class="icon-google"></i>
                                                    Login With Google
                                                </a>
                                            </div><!-- End .col-6 -->
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login btn-f">
                                                    <i class="icon-facebook-f"></i>
                                                    Login With Facebook
                                                </a>
                                            </div><!-- End .col-6 -->
                                        </div><!-- End .row -->
                                    </div><!-- End .form-choice -->
                                </div><!-- .End .tab-pane -->
                                <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                                    <form action="#">
                                        <div class="form-group">
                                            <label for="register-email">Your email address *</label>
                                            <input type="email" class="form-control" id="register-email"
                                                name="register-email" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-group">
                                            <label for="register-password">Password *</label>
                                            <input type="password" class="form-control" id="register-password"
                                                name="register-password" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-footer">
                                            <button type="submit" class="btn btn-outline-primary-2">
                                                <span>SIGN UP</span>
                                                <i class="icon-long-arrow-right"></i>
                                            </button>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="register-policy"
                                                    required>
                                                <label class="custom-control-label" for="register-policy">I agree to the
                                                    <a href="#">privacy policy</a> *</label>
                                            </div><!-- End .custom-checkbox -->
                                        </div><!-- End .form-footer -->
                                    </form>
                                    <div class="form-choice">
                                        <p class="text-center">or sign in with</p>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login btn-g">
                                                    <i class="icon-google"></i>
                                                    Login With Google
                                                </a>
                                            </div><!-- End .col-6 -->
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login  btn-f">
                                                    <i class="icon-facebook-f"></i>
                                                    Login With Facebook
                                                </a>
                                            </div><!-- End .col-6 -->
                                        </div><!-- End .row -->
                                    </div><!-- End .form-choice -->
                                </div><!-- .End .tab-pane -->
                            </div><!-- End .tab-content -->
                        </div><!-- End .form-tab -->
                    </div><!-- End .form-box -->
                </div><!-- End .modal-body -->
            </div><!-- End .modal-content -->
        </div><!-- End .modal-dialog -->
    </div><!-- End .modal -->

    <!-- Plugins JS File -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="login.js"></script>
    <script>
        $(document).ready(function () {
            $("button.submit-cart").click(function () {
                $(".cart_form").submit(); // Submit the form
                alert("Item added to cart");
            });

         /*   $("button.btn-remove").click(function (event) {
                event.preventDefault();
                var del_title = $(this).attr('cart-item-id');
                var item_to_removed = $(this).closest('tr');
                console.log("About to delete itme : " + del_title);
                $.ajax({
                    type: 'POST',
                    cache: false,
                    url: 'https://goodguyng.com/wishlist.php/remove_product_from_watch_list.php',
                    dataType: "json",
                    data: { remove: del_title },
                    success: function (data) {
                        alert(data);
                        item_to_removed.remove();
                        location.reload(true);
                    }
                });
            });
        */

        $('.remove-from-wishlist').click(function (event) {
    event.preventDefault(); // Prevent the default link behavior

    const wishlistItemId = $(this).data('wishlist-id');
    const itemRow = $(this).closest('tr'); // Store the row for easy removal later

    // Confirmation dialog
    if (confirm("Are you sure you want to remove this item from your wishlist?")) {
        $.ajax({
            type: "POST",
            url: "remove-from-wishlist.php",
            data: JSON.stringify({ wishlist_id: wishlistItemId }),
            contentType: "application/json",
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    itemRow.remove();  
                    $.ajax({
                        url: "get_wishlist_count.php", //This file needs to be created
                        type: "GET",
                        success: function(data) {
                            $('.wishlist-count').text(data);
                            
                        },
                        error: function(error) {
                            console.error("Error updating wishlist count:", error);
                            alert("Error updating wishlist count. Please try again.");
                        }
                    });

                } else {
                    alert("Error removing from wishlist: " + response.message);
                }
            },
            error: function (error) {
                alert("An error occurred during removal.");
                console.error(error); // Log the error for debugging
            }
        });
    } 
});
        });
    </script>
</body>



<!-- molla/wishlist.html  22 Nov 2019 09:55:06 GMT -->

</html>