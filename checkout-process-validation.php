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

    // Handle payment method selection
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : null;

    // Validate payment method
    if (!$paymentMethod) {
        $_SESSION['checkout_error'] = "Please select a payment method.";
        header("Location: checkout-process-validation.php");
        exit();
    }

    // Create order
    $order = new Order($pdo);
    $orderId = $order->createOrder($_SESSION['uid'], $shippingAddressId, $paymentMethod, $total, $cartItems);
    //var_dump($orderId);

    if ($orderId) {
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
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item) { ?>
                                    <tr>
                                        <td><?= $item['product']['description'] ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>&#8358;<?= number_format($item['cost'], 2) ?></td>
                                        <td>&#8358;<?= number_format($item['cost'] * $item['quantity'], 2) ?></td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td colspan="3">Subtotal:</td>
                                    <td>&#8358;<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">Shipping:</td>
                                    <td>&#8358;<?= number_format($shippingCost, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">Total:</td>
                                    <td>&#8358;<?= number_format($total, 2) ?></td>
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