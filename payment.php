<?php
// payment.php
require_once "includes.php";
require_once 'vendor/autoload.php'; // Include the Flutterwave SDK

use Flutterwave\Flutterwave;

session_start();

// Check if the order ID is set
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php"); // Redirect to home page if order ID is invalid
    exit();
}

$orderId = $_GET['order_id'];

// Get order details
$order = new Order($pdo);
$orderDetails = $order->getOrderDetails($orderId);

// Check if the order exists
if (!$orderDetails) {
    header("Location: index.php"); // Redirect to home page if order doesn't exist
    exit();
}

// Get shipping address details
$user = new User();
$shippingAddress = $order->getOrderShippingAddress($orderDetails['order_shipping_address']);

// Initialize Flutterwave
$flutterwave = new Flutterwave(
    getenv('FLWPUBK-9c8eac6138da6b4d700f7778647eaf45-X'), // Replace with your public key
    getenv('FLWSECK-cfb1972f9ec720ed3b045ec3e85cb44f-195fbe495afvt-X'), // Replace with your secret key
    getenv('cfb1972f9ec7aa766e57ac30') // Replace with y
);

// Handle payment processing (if form is submitted)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get payment details from the form
    $paymentMethod = $_POST['payment_method']; // e.g., 'card', 'bank_transfer', 'pay_on_delivery'

    if ($paymentMethod == 'card') {
        // Initialize Flutterwave payment
        $paymentData = [
            'tx_ref' => 'GG-' . time(), // Unique transaction reference
            'amount' => $orderDetails['order_total'],
            'currency' => 'NGN', // Change to your currency
            'redirect_url' => 'flutterwave-callback.php?order_id=' . $orderId, // Your callback URL
            'payment_options' => 'card',
            'customer' => [
                'email' => $_SESSION['email'], // Get customer email from session or database
                'name' => $_SESSION['name'], // Get customer name from session or database
            ],
            'customizations' => [
                'title' => 'Goodguy',
                'description' => 'Payment for order ' . $orderId,
            ],
        ];

        try {
            $payment = $flutterwave->payment->initialize($paymentData);
            // Redirect to Flutterwave payment page
            header("Location: " . $payment['data']['link']);
            exit();
        } catch (Exception $e) {
            $_SESSION['payment_error'] = "Payment initialization failed: " . $e->getMessage();
        }
    } elseif ($paymentMethod == 'bank_transfer') {
        // Process bank transfer (you'll need to implement your bank transfer logic here)
        // For this example, we'll just simulate a successful payment
        $paymentSuccessful = true;
        if ($paymentSuccessful) {
            $order->updateOrderStatus($orderId, 'processing');
            unset($_SESSION['cart']);
            header("Location: order-confirmation.php?order_id=" . $orderId);
            exit();
        } else {
            $_SESSION['payment_error'] = "Payment failed. Please try again.";
        }
    } elseif ($paymentMethod == 'pay_on_delivery') {
        // No payment processing needed for pay on delivery
        $paymentSuccessful = true;
        if ($paymentSuccessful) {
            $order->updateOrderStatus($orderId, 'processing');
            unset($_SESSION['cart']);
            header("Location: order-confirmation.php?order_id=" . $orderId);
            exit();
        } else {
            $_SESSION['payment_error'] = "Payment failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
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
                <h3>Payment</h3>

                <?php if (isset($_SESSION['payment_error'])) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['payment_error']; ?>
                    </div>
                    <?php unset($_SESSION['payment_error']); ?>
                <?php } ?>

                <h5>Order Details</h5>
                <p>Order ID: <?= $orderId ?></p>
                <p>Order Total: <?= $orderDetails['order_total'] ?></p>
                <!-- Display other order details here -->

                <h5>Shipping Address</h5>
                <?php if ($shippingAddress) { ?>
                    <p>
                        <?= $shippingAddress['address1'] ?><br>
                        <?= $shippingAddress['address2'] ?><br>
                        <?= $shippingAddress['city'] ?>, <?= $user->getStateName($shippingAddress['State']) ?><br>
                        <?= $shippingAddress['country'] ?>
                    </p>
                <?php } else { ?>
                    <p>Shipping address not found.</p>
                <?php } ?>

                <h5>Payment Method</h5>
                <form action="payment.php?order_id=<?= $orderId ?>" method="post">
                    <?php if ($orderDetails['payment_method'] == 'card') { ?>
                        <!-- Card Payment Form -->
                        <p>You will be redirected to Flutterwave to complete your payment.</p>
                    <?php } elseif ($orderDetails['payment_method'] == 'bank_transfer') { ?>
                        <!-- Bank Transfer Instructions -->
                        <p>Please make a bank transfer to the following account:</p>
                        <!-- Display bank account details -->
                        <p>Account Name: Goodguy</p>
                        <p>Account Number: 1234567890</p>
                        <p>Bank Name: First Bank</p>
                    <?php } elseif ($orderDetails['payment_method'] == 'pay_on_delivery') { ?>
                        <!-- Pay on delivery instructions -->
                        <p>You will pay on delivery.</p>
                    <?php } ?>

                    <input type="hidden" name="payment_method" value="<?= $orderDetails['payment_method'] ?>">
                    <button type="submit" class="btn btn-primary">Complete Payment</button>
                </form>
            </div>
        </div>
    </main>
    <?php include "footer.php"; ?>
</body>

</html>