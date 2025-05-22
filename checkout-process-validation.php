<?php
require_once "includes.php";
session_start();

// Check if the user is logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['login_redirect'] = 'checkout-process-validation.php';
    header("Location: login.php");
    exit();
}

// Check if the cart is empty
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
    $_SESSION['cart_error'] = "Your cart is empty. Please add items to your cart before proceeding to checkout.";
    header("Location: cart.php");
    exit();
}

// Get cart items
$cartItems = $cart->getCartItems();

// Calculate subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['cost'] * $item['quantity'];
}

// Get shipping cost
$shippingCost = 0; // You'll need to implement logic to calculate shipping cost

// Calculate total
$total = $subtotal + $shippingCost;
$order = new Order($pdo);
// Handle form submission (if any)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle shipping address selection
    $shippingAddressId = isset($_POST['shipping_address']) ? $_POST['shipping_address'] : null;

    // Validate shipping address
    if (!$shippingAddressId) {
        $_SESSION['checkout_error'] = "Please select a shipping address.";
        header("Location: checkout-process-validation.php");
        exit();
    }

    if ($shippingAddressId) {
        $shippingAreaId = $order->getShippingAreaIdFromAddress($shippingAddressId);
        $shippingCost = $order->getShippingAreaCost($shippingAreaId);
        $total = $subtotal + $shippingCost;
    }

    // Handle payment method selection
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;

    // Validate payment method
    if (!$paymentMethod) {
        $_SESSION['checkout_error'] = "Please select a payment method.";
        header("Location: checkout-process-validation.php");
        exit();
    }

    // Create order

    $orderId = $order->createOrder_($_SESSION['uid'], $shippingAddressId, $paymentMethod, $subtotal, $shippingCost, $total, $cartItems);

    if ($orderId) {

        // Order created successfully, now attempt to send invoice email
        // The Order class instance ($order) should already be available
        if ($order->sendInvoiceEmail($orderId)) {
            // Email sent successfully (optional: log or set a success flash message for admin/logs)
            // For the user, a general success message for order placement is usually enough here.
            // You might set a session variable if you want to display a specific "invoice sent" message on the next page.
            $_SESSION['order_success_message'] = "Your order #{$orderId} has been placed successfully! An invoice has been sent to your email.";
        } else {
            // Email sending failed (optional: log this failure for admin attention)
            // The order is still placed, so this is not a critical failure for the order itself.
            // You might inform the user that the order is placed but to check their order history if email isn't received.
            $_SESSION['order_success_message'] = "Your order #{$orderId} has been placed successfully! If you don't receive an invoice email shortly, please check your order history or contact support.";
            error_log("Failed to send invoice email for order ID: $orderId after successful creation.");
        }

        // Redirect to payment page
        header("Location:  payment.php?order_id=" . $orderId);
        exit();
    } else {
        $_SESSION['checkout_error'] = "An error occurred while creating your order. Please try again.";
        header("Location: checkout-process-validation.php");
        exit();
    }


    // Redirect to payment page

}

// Function to get cart items (consider moving this to a Cart class)

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <?php include "header-for-other-pages.php"; ?>
    <main class="main">
        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
            <div class="container d-flex align-items-center">
                <ol class="breadcrumb">
                    <?php echo breadcrumbs(); ?>
                </ol>
            </div><!-- End .container -->
        </nav><!-- End .breadcrumb-nav -->
        <div class="page-content">
            <div class="container">
                <h4>Checkout</h4>

                <?php if (isset($_SESSION['checkout_error'])) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['checkout_error']; ?>
                    </div>
                    <?php unset($_SESSION['checkout_error']); ?>
                <?php } ?>

                <div class="row">
                    <div class="col-md-8">

                        <form action="checkout-process-validation.php" method="post">
                            <!-- Shipping Address Selection -->
                            <div class="form-group">
                                <label for="shipping_address">Select Shipping Address:</label>
                                <select name="shipping_address" id="shipping_address" class="form-control">
                                    <option value="">Select Address</option>
                                    <?php
                                    // Get user's addresses from the database
                                    $addresses = $user->getUserAddresses($_SESSION['uid']);
                                    foreach ($addresses as $address) {
                                        echo "<option value='" . $address['shipping_address_no'] . "'>" . $address['address1'] . ", " . $address['address2'] . ", " . $address['city'] . ", " . $address['State'] . ", " . $address['Country'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>


                            <!-- Payment Method Selection -->
                            <div class="form-group">
                                <label for="payment_method">Select Payment Method:</label>
                                <select name="payment_method" id="payment_method" class="form-control">
                                    <option value="">Select Payment Method</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="pay_on_delivery">Pay on delivery</option>
                                    <!-- Add more payment methods as needed -->
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Confirm Order</button>
                        </form>
                    </div>
                </div>
                <div class="row  mt-5">
                    <div class="col">
                        <h4>Order Summary</h4>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 100px; padding: 8px;">Image</th>
                                    <th style="padding: 8px;">Product Name</th>
                                    <th style="width: 80px; text-align: center; padding: 8px;">Quantity</th>
                                    <th style="width: 120px; text-align: right; padding: 8px;">Price</th>
                                    <th style="width: 120px; text-align: right; padding: 8px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item) { ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($invt->get_product_image($pdo, $item['product']['InventoryItemID']))): ?>
                                                <img src="<?= $invt->get_product_image($pdo, $item['product']['InventoryItemID']) ?>"
                                                    alt="<?= htmlspecialchars($item['product']['description']) ?>"
                                                    style="width: 60px; height: 60px; object-fit: cover; display: block;">
                                                <!-- Added display: block for better centering if padding is on td -->
                                            <?php else: ?>
                                                <img src="assets/images/no-image-placeholder.png" alt="No image available"
                                                    style="width: 60px; height: 60px; object-fit: cover; display: block;">
                                                <!-- Optional: Placeholder image -->
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 8px; vertical-align: middle;">
                                            <?= htmlspecialchars($item['product']['description']) ?>
                                        </td>
                                        <td style="text-align: center; padding: 8px; vertical-align: middle;">
                                            <?= htmlspecialchars($item['quantity']) ?>
                                        </td>
                                        <td style="text-align: right; padding: 8px; vertical-align: middle;">
                                            &#8358;<?= number_format($item['cost'], 2) ?></td>
                                        <td style="text-align: right; padding: 8px; vertical-align: middle;">
                                            &#8358;<?= number_format($item['cost'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="4" class="text-right" style="padding: 8px; vertical-align: middle;">
                                        <strong>Subtotal:</strong>
                                    </td>
                                    <td style="text-align: right; padding: 8px; vertical-align: middle;">
                                        &#8358;<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right" style="padding: 8px; vertical-align: middle;">
                                        <strong>Shipping:</strong>
                                    </td>
                                    <td style="text-align: right; padding: 8px; vertical-align: middle;">
                                        &#8358;<?= number_format($shippingCost, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right" style="padding: 8px; vertical-align: middle;">
                                        <strong>Total:</strong>
                                    </td>
                                    <td style="text-align: right; padding: 8px; vertical-align: middle;">
                                        <strong>&#8358;<?= number_format($total, 2) ?></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>
    </main>
    <?php include "footer.php"; ?>
</body>

</html>