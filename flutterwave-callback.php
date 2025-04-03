<?php
// flutterwave-callback.php
require_once "includes.php";
require_once 'vendor/autoload.php'; // Include the Flutterwave SDK

use Flutterwave\Flutterwave;

session_start();

// Initialize Flutterwave
$flutterwave = new Flutterwave(
    getenv('FLWPUBK-9c8eac6138da6b4d700f7778647eaf45-X'), // Replace with your public key
    getenv('FLWSECK-cfb1972f9ec720ed3b045ec3e85cb44f-195fbe495afvt-X'), // Replace with your secret key
    getenv('cfb1972f9ec7aa766e57ac30') // Replace with your encryption key
);

// Get the transaction reference and order ID from the URL
$txRef = $_GET['tx_ref'];
$orderId = $_GET['order_id'];

// Verify the payment
try {
    $payment = $flutterwave->payment->verify($txRef);

    if ($payment['data']['status'] == 'successful') {
        // Payment is successful
        $order = new Order($pdo);
        $order->updateOrderStatus($orderId, 'completed');

        // Clear the cart
        unset($_SESSION['cart']);

        // Redirect to order confirmation page
        header("Location: order-confirmation.php?order_id=" . $orderId);
        exit();
    } else {
        // Payment failed
        $order = new Order($pdo);
        $order->updateOrderStatus($orderId, 'failed');
        $_SESSION['payment_error'] = "Payment failed.";
        header("Location: payment.php?order_id=" . $orderId);
        exit();
    }
} catch (Exception $e) {
    // Verification failed
    $_SESSION['payment_error'] = "Payment verification failed: " . $e->getMessage();
    header("Location: payment.php?order_id=" . $orderId);
    exit();
}
?>