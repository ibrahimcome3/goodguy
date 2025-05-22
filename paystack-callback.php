<?php
// paystack-callback.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "includes.php"; // Your main include file (defines $pdo, classes, etc.)
require_once 'vendor/autoload.php'; // Composer autoloader for Paystack SDK

// Load environment variables if you're using them for Paystack Secret Key
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    error_log("Error loading .env file in paystack-callback: " . $e->getMessage());
}

use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

// --- 1. Get Transaction Reference ---
$reference = $_GET['reference'] ?? null;

if (empty($reference)) {
    $_SESSION['payment_error'] = "Payment reference missing. Transaction cannot be verified.";
    error_log("Paystack Callback: Missing reference parameter.");
    header("Location: checkout.php"); // Or a generic error page
    exit();
}

// --- 2. Initialize Paystack and Order Class ---
try {
    // IMPORTANT: Ensure your Paystack Secret Key is correctly loaded.
    // Replace 'YOUR_PAYSTACK_SECRET_KEY' if not using .env or if it's defined elsewhere.
    $paystackSecretKey = $_ENV['PAYSTACK_SECRET_KEY'] ?? 'sk_test_ee47a4a7d600efa600bffa8161353e428989243d'; // Fallback to test key if .env fails
    if (empty($paystackSecretKey)) {
        throw new Exception("Paystack Secret Key is not configured.");
    }
    $paystack = new Paystack($paystackSecretKey);
    $order = new Order($pdo); // Assuming Order class is loaded via includes.php
} catch (Exception $e) {
    $_SESSION['payment_error'] = "Payment system configuration error. Please contact support. [PSC01]";
    error_log("Paystack Callback Error: " . $e->getMessage());
    header("Location: checkout.php");
    exit();
}

// --- 3. Verify Transaction with Paystack ---
try {
    $transaction = $paystack->transaction->verify(['reference' => $reference]);

    // --- 4. Process Transaction Status ---
    if ($transaction->status && isset($transaction->data->status) && $transaction->data->status == 'success') {
        // Payment was successful

        // a. Get Order ID from metadata (safer than session)
        $orderId = null;
        if (isset($transaction->data->metadata->order_id)) {
            $orderId = (int) $transaction->data->metadata->order_id;
        } else {
            // Fallback or error if order_id is not in metadata
            // This part depends on how you stored it during initialization.
            // For now, let's assume it's in metadata as per payment.php setup.
            $_SESSION['payment_error'] = "Could not retrieve order details for successful payment. Please contact support. [PSC02]";
            error_log("Paystack Callback Success: Missing order_id in metadata for reference " . $reference);
            header("Location: index.php"); // Or user's order history
            exit();
        }

        // b. Update Order Status in Database
        // Ensure you have an updateOrderStatus method in your Order class
        // This method should ideally also check if the order isn't already marked as paid to prevent duplicate processing.
        $order->updateOrderStatus($orderId, 'paid'); // Or 'completed', 'processing' depending on your workflow

        // c. Send Payment Receipt Email
        if ($order->sendPaymentReceiptEmail($orderId)) {
            // Email sent successfully
            $_SESSION['order_confirmation_message'] = "Your payment for order #{$orderId} was successful! A receipt has been sent to your email.";
        } else {
            // Email sending failed, but payment is still successful
            $_SESSION['order_confirmation_message'] = "Your payment for order #{$orderId} was successful! We had trouble sending a receipt, but your order is confirmed. Please check your order history or contact support.";
            error_log("Paystack Callback: Failed to send payment receipt for order ID {$orderId} after successful payment (Ref: {$reference}).");
        }

        // d. Clear the user's cart
        unset($_SESSION['cart']);

        // e. Redirect to a success/confirmation page
        header("Location: order-confirmation.php?order_id=" . $orderId . "&status=success");
        exit();

    } else {
        // Payment was not successful (failed, abandoned, etc.)
        $errorMessage = $transaction->data->gateway_response ?? 'Payment was not successful.';
        $_SESSION['payment_error'] = "Payment failed or was cancelled. Reason: " . htmlspecialchars($errorMessage);

        // Optionally, retrieve order_id from metadata if available to update status to 'failed'
        $orderId = $transaction->data->metadata->order_id ?? null;
        if ($orderId) {
            $order->updateOrderStatus((int) $orderId, 'failed');
        }
        error_log("Paystack Callback: Payment not successful for reference {$reference}. Gateway Response: " . ($transaction->data->gateway_response ?? 'N/A'));
        header("Location: payment.php?order_id=" . ($orderId ?? '') . "&status=failed"); // Redirect back to payment page
        exit();
    }

} catch (ApiException $e) {
    $_SESSION['payment_error'] = "Error verifying payment: " . htmlspecialchars($e->getMessage()) . ". Please contact support. [PSC03]";
    error_log("Paystack Callback API Exception for reference {$reference}: " . $e->getMessage() . " | Response: " . ($e->getResponseObject() ? json_encode($e->getResponseObject()) : 'N/A'));
    header("Location: checkout.php");
    exit();
} catch (Exception $e) {
    $_SESSION['payment_error'] = "A system error occurred while verifying your payment. Please contact support. [PSC04]";
    error_log("Paystack Callback General Exception for reference {$reference}: " . $e->getMessage());
    header("Location: checkout.php");
    exit();
}

?>