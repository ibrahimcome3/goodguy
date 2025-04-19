<?php
// paystack-callback.php

require_once "includes.php"; // Include your core files (DB connection, Order class)
require_once 'vendor/autoload.php'; // Include SDK autoloaders

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    error_log("Error loading .env file in callback: " . $e->getMessage());
    die("Configuration error."); // Keep error minimal on callback
}

use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

session_start();

// --- Get Reference from Paystack Redirect ---
// Paystack typically sends the reference via GET parameter
if (!isset($_GET['reference'])) {
    error_log("Paystack callback missing reference.");
    // Redirect to a generic error page or homepage
    header("Location: index.php?error=payment_failed");
    exit();
}
$reference = filter_input(INPUT_GET, 'reference', FILTER_SANITIZE_STRING);

// --- Verify Transaction ---
$paystackSecretKey = getenv('PAYSTACK_SECRET_KEY');
if (!$paystackSecretKey) {
    error_log("Paystack Secret Key not found in callback.");
    header("Location: index.php?error=payment_config");
    exit();
}

$order = new Order($pdo); // Instantiate your Order class

try {
    $paystack = new Paystack($paystackSecretKey);

    // Verify the transaction using the reference
    $tranx = $paystack->transaction->verify([
        'reference' => $reference
    ]);

    // --- Check Verification Result ---
    if ($tranx->status && $tranx->data->status === 'success') {
        // Payment was successful

        // **CRUCIAL SECURITY STEP:** Verify Amount and Currency
        $orderId = $tranx->data->metadata->order_id ?? null; // Get order ID from metadata
        $amountPaid = $tranx->data->amount; // Amount is in Kobo/cents
        $currencyPaid = $tranx->data->currency;

        if (!$orderId) {
            error_log("Paystack callback successful but missing order_id in metadata for reference: " . $reference);
            header("Location: index.php?error=payment_issue");
            exit();
        }

        $orderDetails = $order->getOrderDetails($orderId);
        if (!$orderDetails) {
            error_log("Paystack callback successful but cannot find order ($orderId) for reference: " . $reference);
            header("Location: index.php?error=payment_issue");
            exit();
        }

        $expectedAmountInKobo = $orderDetails['order_total'] * 100;
        $expectedCurrency = 'NGN'; // Or your store's currency

        if ($amountPaid >= $expectedAmountInKobo && $currencyPaid === $expectedCurrency) {
            // Amount and currency match! Update order status
            $order->updateOrderStatus($orderId, 'processing'); // Or 'paid', 'completed' etc.

            // Clear cart (optional, maybe do this on confirmation page)
            unset($_SESSION['cart']);

            // Redirect to a success page
            header("Location: order-confirmation.php?order_id=" . $orderId . "&status=success");
            exit();

        } else {
            // Amount mismatch - potential fraud or error
            error_log("Paystack callback amount/currency mismatch for reference: $reference. Expected: $expectedAmountInKobo $expectedCurrency, Got: $amountPaid $currencyPaid");
            // Update order status to 'payment_failed' or similar?
            $order->updateOrderStatus($orderId, 'payment_failed'); // Example status
            $_SESSION['payment_error'] = "Payment verification failed (amount mismatch). Please contact support.";
            header("Location: payment.php?order_id=" . $orderId); // Send back to payment page
            exit();
        }

    } else {
        // Payment verification failed or payment was not successful on Paystack
        $errorMessage = $tranx->message ?? 'Payment not completed or verification failed.';
        error_log("Paystack verification failed for reference: $reference. Reason: $errorMessage | Data: " . json_encode($tranx->data ?? null));

        // Try to get order ID from metadata even on failure if possible
        $orderId = $tranx->data->metadata->order_id ?? null;
        if ($orderId) {
            $order->updateOrderStatus($orderId, 'payment_failed'); // Update status
            $_SESSION['payment_error'] = "Payment failed or was cancelled. Please try again.";
            header("Location: payment.php?order_id=" . $orderId);
        } else {
            // Cannot determine order, redirect generally
            header("Location: index.php?error=payment_failed");
        }
        exit();
    }

} catch (ApiException $e) {
    error_log("Paystack API Exception during verification for reference $reference: " . $e->getMessage());
    // Redirect to a generic error page or payment page if possible
    header("Location: index.php?error=payment_issue");
    exit();
} catch (Exception $e) {
    error_log("General Exception during Paystack callback for reference $reference: " . $e->getMessage());
    header("Location: index.php?error=payment_issue");
    exit();
}

?>