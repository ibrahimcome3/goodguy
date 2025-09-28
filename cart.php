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
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
        exit();
    }

    // Check if the item is already in the cart
    $item_found = false;
    if (is_array($_SESSION['cart'])) { // Ensure cart is an array before looping
        foreach ($_SESSION['cart'] as $key => $item) {
            // Ensure $item is an array and has the necessary keys before comparison
            if (is_array($item) && isset($item['inventory_product_id']) &&
                $item['inventory_product_id'] == $inventory_product_id &&
                ($item['size'] ?? null) == $size && // Use null coalescing for comparison with null
                ($item['color'] ?? null) == $color) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
                $item_found = true;
                break;
            }
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
    // Prefer redirecting to the product page from where it was added, or cart page.
    // Using HTTP_REFERER can be less reliable if the user came from an unexpected page.
    $redirect_url = "product-detail.php?itemid=" . urlencode($inventory_product_id) . "&toast_action=cart_add_success";
    if (isset($_POST['return_url']) && !empty($_POST['return_url'])) { // Allow explicit return URL
        $redirect_url = $_POST['return_url'];
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
        // Fallback to HTTP_REFERER if it seems safe (e.g., same domain)
        // For simplicity here, we'll use it, but in production, validate it.
        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if ($referer_host == $_SERVER['HTTP_HOST']) {
             // Add a query param to show a success message if desired
            $query_char = (strpos($_SERVER['HTTP_REFERER'], '?') === false) ? '?' : '&';
            $redirect_url = $_SERVER['HTTP_REFERER'] . $query_char . 'toast_action=cart_add_success';
        }
    }
    header("Location: " . $redirect_url);
    exit();
}

// Handle removing items from the cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_index = (int)$_GET['remove']; // Cast to int
    if (isset($_SESSION['cart'][$remove_index])) {
        unset($_SESSION['cart'][$remove_index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index the array
    }
    header("Location: cart.php");
    exit();
}

// Handle updating cart quantities
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $key => $quantity_value) {
            $quantity_value = (int)$quantity_value; // Sanitize
            if (isset($_SESSION['cart'][(int)$key])) { // Ensure key is treated as int
                if ($quantity_value <= 0) {
                    unset($_SESSION['cart'][(int)$key]);
                } else {
                    $_SESSION['cart'][(int)$key]['quantity'] = $quantity_value;
                }
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
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">
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
                                <?php echo htmlspecialchars($_SESSION['cart_error']); ?>
                            </div>
                            <?php unset($_SESSION['cart_error']); ?>
                        <?php } ?>
                        <?php if (isset($_SESSION['cart_success'])) { ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($_SESSION['cart_success']); ?>
                            </div>
                            <?php unset($_SESSION['cart_success']); ?>
                        <?php } ?>

                        <?php if (empty($_SESSION['cart'])) { ?>
                            <div class="text-center">
                                <i class="icon-shopping-cart" style="font-size: 5em; color: #ccc;"></i>
                                <p class="mt-2">Your cart is empty.</p>
                                <a href="index.php" class="btn btn-outline-primary-2"><span>GO SHOP</span><i
                                        class="icon-long-arrow-right"></i></a>
                            </div>
                        <?php } else { ?>
                            <div class="row">
                                <div class="col-lg-9">
                                    <form action="cart.php" method="post">
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
                                                if (is_array($_SESSION['cart'])) { // Ensure cart is an array
                                                    foreach ($_SESSION['cart'] as $key => $item) {
                                                        // Defensive check to ensure $item is an array and has the required keys
                                                        if (!is_array($item) || !isset($item['inventory_product_id']) || !isset($item['quantity'])) {
                                                            // Log this malformed item and skip it
                                                            error_log("Malformed cart item at key {$key}: " . print_r($item, true));
                                                            // Optionally, remove the malformed item
                                                            // unset($_SESSION['cart'][$key]);
                                                            continue;
                                                        }

                                                        $product_id = $item['inventory_product_id'];
                                                        $quantity = $item['quantity'];
                                                        $size = $item['size'] ?? null; // Use null coalescing for safety
                                                        $color = $item['color'] ?? null; // Use null coalescing for safety

                                                        // Get product details from the database
                                                        // Ensure $invt and $pdo are available from includes.php
                                                        if (!isset($invt) || !isset($pdo)) {
                                                            echo "<tr><td colspan='5' class='text-danger'>Error: System configuration issue.</td></tr>";
                                                            error_log("Error: \$invt or \$pdo not available in cart.php");
                                                            break; // Exit loop if essential objects are missing
                                                        }
                                                        
                                                        try {
                                                            $product_details = $invt->get_product_details($product_id);
                                                        } catch (Exception $e) {
                                                            error_log("Error fetching product details for ID {$product_id}: " . $e->getMessage());
                                                            echo "<tr><td colspan='5' class='text-danger'>Error loading product details for an item. It might have been removed.</td></tr>";
                                                            unset($_SESSION['cart'][$key]); // Remove problematic item
                                                            continue;
                                                        }
 
                                                        if (!$product_details) {
                                                            echo "<tr><td colspan='5' class='text-danger'>Product with ID {$product_id} not found. It may have been removed from the store.</td></tr>";
                                                            unset($_SESSION['cart'][$key]); // Remove item if product details not found
                                                            continue;
                                                        }
                                                        
                                                        $product_price = $product_details['cost'];
                                                        $product_name = $product_details['description'];
                                                        $product_image = $invt->get_product_image($product_id); // Assuming this returns a valid path or placeholder

                                                        $item_total = $product_price * $quantity;
                                                        $total_cost += $item_total;
                                                        ?>
                                                        <tr>
                                                            <td class="product-col">
                                                                <div class="product">
                                                                    <figure class="product-media">
                                                                        <a href="product-detail.php?itemid=<?= htmlspecialchars($product_id) ?>">
                                                                            <img src="<?= htmlspecialchars($product_image ?: 'assets/images/placeholder.jpg') ?>"
                                                                                alt="<?= htmlspecialchars($product_name) ?>">
                                                                        </a>
                                                                    </figure>

                                                                    <h3 class="product-title">
                                                                        <a
                                                                            href="product-detail.php?itemid=<?= htmlspecialchars($product_id) ?>"><?= htmlspecialchars($product_name) ?></a>
                                                                        <?php if ($size) { ?>
                                                                            <br>Size: <?= htmlspecialchars($size) ?>
                                                                        <?php } ?>
                                                                        <?php if ($color) { ?>
                                                                            <br>Color: <?= htmlspecialchars($color) ?>
                                                                        <?php } ?>
                                                                    </h3><!-- End .product-title -->
                                                                </div><!-- End .product -->
                                                            </td>
                                                            <td class="price-col">&#8358;<?= number_format($product_price, 2) ?></td>
                                                            <td class="quantity-col">
                                                                <div class="cart-product-quantity">
                                                                    <input type="number" class="form-control"
                                                                        name="quantity[<?= $key ?>]" value="<?= htmlspecialchars($quantity) ?>"
                                                                        min="1" max="20" step="1" data-decimals="0"
                                                                        required>
                                                                </div><!-- End .cart-product-quantity -->
                                                            </td>
                                                            <td class="total-col">&#8358;<?= number_format($item_total, 2) ?></td>
                                                            <td class="remove-col"><a href="cart.php?remove=<?= $key ?>"
                                                                    class="btn-remove" title="Remove item"><i
                                                                        class="icon-close"></i></a></td>
                                                        </tr>
                                                    <?php 
                                                    } // end foreach
                                                    // Re-index cart if any items were removed due to errors inside the loop
                                                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                                                } // end if is_array($_SESSION['cart'])
                                                ?>
                                            </tbody>
                                        </table><!-- End .table table-wishlist -->
                                        <div class="cart-bottom">
                                            <button type="submit" name="update_cart"
                                                class="btn btn-outline-dark-2"><span>UPDATE
                                                    CART</span><i class="icon-refresh"></i></button>
                                        </div>
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
                                                <!-- Shipping can be added here if calculated -->
                                                <tr class="summary-total">
                                                    <td>Total:</td>
                                                    <td>&#8358;<?= number_format($total_cost, 2) ?></td>
                                                </tr><!-- End .summary-total -->
                                            </tbody>
                                        </table><!-- End .table table-summary -->

                                        <a href="checkout-process-validation.php"
                                            class="btn btn-outline-primary-2 btn-order btn-block">PROCEED TO CHECKOUT</a>
                                    </div><!-- End .summary -->
                                    <a href="index.php" class="btn btn-outline-dark-2 btn-block mb-3"><span>CONTINUE
                                            SHOPPING</span><i class="icon-refresh"></i></a>
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
