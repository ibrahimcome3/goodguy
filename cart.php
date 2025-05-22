<?php
session_start();
require_once "includes.php"; // Include necessary files

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding items to the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['inventory_product_id']) && isset($_POST['qty'])) {
    $inventory_product_id = $_POST['inventory_product_id'];
    $quantity = $_POST['qty'];
    $size = isset($_POST['size']) ? $_POST['size'] : null; // Get size if available
    $color = isset($_POST['color']) ? $_POST['color'] : null; // Get color if available

    // Validate input (you should add more robust validation)
    if (!is_numeric($inventory_product_id) || !is_numeric($quantity) || $quantity <= 0) {
        // Handle invalid input (e.g., display an error message)
        $_SESSION['cart_error'] = "Invalid product ID or quantity.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Check if the item is already in the cart
    $item_found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['inventory_product_id'] == $inventory_product_id && $item['size'] == $size && $item['color'] == $color) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
            $item_found = true;
            break;
        }
    }

    // If the item is not in the cart, add it
    if (!$item_found) {
        $_SESSION['cart'][] = [
            'inventory_product_id' => $inventory_product_id,
            'quantity' => $quantity,
            'size' => $size,
            'color' => $color,
        ];
    }

    // Redirect back to the product page or cart page
    $_SESSION['cart_success'] = "Item added to cart!";
    header("Location: product-detail.php?itemid=" . $inventory_product_id);
    exit();
}

// Handle removing items from the cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_index = $_GET['remove'];
    if (isset($_SESSION['cart'][$remove_index])) {
        unset($_SESSION['cart'][$remove_index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
    }
    header("Location: cart.php");
    exit();
}

// Handle updating cart quantities
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $key => $quantity) {
        if (isset($_SESSION['cart'][$key])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$key]);
            } else {
                $_SESSION['cart'][$key]['quantity'] = $quantity;
            }
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Shopping Cart</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">
        <?php include 'header_main.php'; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="cart">
                    <div class="container">
                        <?php if (isset($_SESSION['cart_error'])) { ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $_SESSION['cart_error']; ?>
                            </div>
                            <?php unset($_SESSION['cart_error']); ?>
                        <?php } ?>
                        <?php if (empty($_SESSION['cart'])) { ?>
                            <p>Your cart is empty.</p>
                            <a href="index.php" class="btn btn-outline-primary-2"><span>GO SHOP</span><i
                                    class="icon-long-arrow-right"></i></a>
                        <?php } else { ?>
                            <div class="row">
                                <div class="col-lg-9">
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
                                            <?php
                                            $total_cost = 0;
                                            foreach ($_SESSION['cart'] as $key => $item) {
                                                $product_id = $item['inventory_product_id'];
                                                $quantity = $item['quantity'];
                                                $size = $item['size'];

                                                // Get product details from the database
                                                $product_details = $invt->get_product_details($pdo, $product_id);
                                                $product_price = $product_details['cost'];
                                                $product_name = $product_details['description'];
                                                $product_image = $invt->get_product_image($pdo, $product_id);

                                                $item_total = $product_price * $quantity;
                                                $total_cost += $item_total;
                                                ?>
                                                <tr>
                                                    <td class="product-col">
                                                        <div class="product">
                                                            <figure class="product-media">
                                                                <a href="product-detail.php?itemid=<?= $product_id ?>">
                                                                    <img src="<?= $product_image ?>" alt="Product image">
                                                                </a>
                                                            </figure>

                                                            <h3 class="product-title">
                                                                <a
                                                                    href="product-detail.php?itemid=<?= $product_id ?>"><?= $product_name ?></a>
                                                                <?php if ($size) { ?>
                                                                    <br>Size: <?= $size ?>
                                                                <?php } ?>
                                                            </h3><!-- End .product-title -->
                                                        </div><!-- End .product -->
                                                    </td>
                                                    <td class="price-col">&#8358;<?= number_format($product_price, 2) ?></td>
                                                    <td class="quantity-col">
                                                        <form action="cart.php" method="post">
                                                            <div class="cart-product-quantity">
                                                                <input type="number" class="form-control"
                                                                    name="quantity[<?= $key ?>]" value="<?= $quantity ?>"
                                                                    min="1" max="10" step="1" data-decimals="0" required>
                                                            </div><!-- End .cart-product-quantity -->
                                                    </td>
                                                    <td class="total-col">&#8358;<?= number_format($item_total, 2) ?></td>
                                                    <td class="remove-col"><a href="cart.php?remove=<?= $key ?>"
                                                            class="btn-remove"><i class="icon-close"></i></a></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table><!-- End .table table-wishlist -->
                                    <button type="submit" name="update_cart" class="btn btn-outline-dark-2"><span>UPDATE
                                            CART</span><i class="icon-refresh"></i></button>
                                    </form>
                                </div><!-- End .col-lg-9 -->
                                <aside class="col-lg-3">
                                    <div class="summary summary-cart">
                                        <h3 class="summary-title">Cart Total</h3><!-- End .summary-title -->

                                        <table class="table table-summary">
                                            <tbody>
                                                <tr class="summary-subtotal">
                                                    <td>Subtotal:</td>
                                                    <td>&#8358;<?= number_format($total_cost, 2) ?></td>
                                                </tr><!-- End .summary-subtotal -->
                                                <tr class="summary-total">
                                                    <td>Total:</td>
                                                    <td>&#8358;<?= number_format($total_cost, 2) ?></td>
                                                </tr><!-- End .summary-total -->
                                            </tbody>
                                        </table><!-- End .table table-summary -->

                                        <a href="checkout-process-validation.php"
                                            class="btn btn-outline-primary-2 btn-order btn-block">PROCEED TO CHECKOUT</a>
                                    </div><!-- End .summary -->
                                </aside><!-- End .col-lg-3 -->
                            </div><!-- End .row -->
                        <?php } ?>
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
    <!-- Sign in / Register Modal -->
    <?php include "login-modal.php"; ?>

    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>
</body>

</html>