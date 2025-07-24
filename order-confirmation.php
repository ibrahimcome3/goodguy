<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Get parameters from URL
$orderId = isset($_GET['order_id']) ? filter_var($_GET['order_id'], FILTER_VALIDATE_INT) : null;
$statusFromUrl = isset($_GET['status']) ? trim($_GET['status']) : null;
$paymentMethodFromUrl = isset($_GET['method']) ? trim($_GET['method']) : null;

// 2. Include main configuration and class loader
// This should provide $pdo, and instantiate $order, $cart, etc.
require_once "includes.php";

// 3. Initialize variables for display and logic
$displayMessage = '';
$displayTitle = 'Order Confirmation';
$effectiveStatus = $statusFromUrl; // Start with URL status, will be refined
$dbPaymentMethod = $paymentMethodFromUrl; // Start with URL method
$dbOrderStatus = 'unknown';

// Retrieve any pre-set confirmation message (e.g., from Paystack callback)
if (isset($_SESSION['order_confirmation_message'])) {
    $displayMessage = $_SESSION['order_confirmation_message'];
    unset($_SESSION['order_confirmation_message']); // Consume the message
}

// 4. Process order details and determine effective status and message
if ($orderId && $orderId > 0) {
    if (isset($order) && ($order instanceof Order)) {


        $orderDetailsFromDb = $order->getOrderDetails($orderId);
        if ($orderDetailsFromDb) {
            $dbPaymentMethod = $orderDetailsFromDb['payment_method'] ?? $dbPaymentMethod;
            $dbOrderStatus = strtolower($orderDetailsFromDb['order_status'] ?? 'unknown');
            $paidStatuses = ['paid', 'processing', 'payment_confirmed', 'completed']; // Define what counts as a "paid" or "progressing" state

            // Scenario A: Success explicitly passed in URL (e.g., from Paystack or initial Bank Transfer/COD confirm)
            if ($statusFromUrl === 'success') {
                $effectiveStatus = 'success'; // Confirm effective status for cart clearing
                if (empty($displayMessage)) { // If no specific message was set by the payment processor
                    if ($dbPaymentMethod === 'bank_transfer') {
                        $displayTitle = "Order Placed - Awaiting Payment Confirmation";
                        $displayMessage = "Your order #{$orderId} has been placed successfully! Please make your payment via bank transfer using your Order ID (<strong>" . htmlspecialchars($orderId) . "</strong>) as the payment reference. We will confirm your payment (typically within 24 business hours) and process your order. Thank you! 😊";
                    } elseif ($dbPaymentMethod === 'cod') { // 'cod' for Pay on Delivery
                        $displayTitle = "Order Placed Successfully!";
                        $displayMessage = "Your order #{$orderId} (Pay on Delivery) has been placed and will be processed shortly. 😊";
                    } else { // card or other direct success
                        $displayTitle = "Payment Successful!";
                        $displayMessage = "Your payment for order #{$orderId} was successful! Your order is being processed. 😊";
                    }
                }
            }
            // Scenario B: User revisits a Bank Transfer order that is now confirmed in DB
            // This handles the case where admin confirms payment and user checks status.
            elseif ($dbPaymentMethod === 'bank_transfer' && in_array($dbOrderStatus, $paidStatuses)) {
                $effectiveStatus = 'success'; // Treat as success for cart clearing and display
                $displayTitle = "Bank Transfer Confirmed!";
                if (empty($displayMessage)) {
                    $displayMessage = "Your bank transfer for order #{$orderId} has been confirmed! Your order is now being processed. 😊";
                }
            }
            // Scenario C: Bank Transfer still genuinely pending (not yet confirmed by admin, and no 'status=success' in URL)
            elseif ($dbPaymentMethod === 'bank_transfer' && !in_array($dbOrderStatus, $paidStatuses)) {
                $displayTitle = "Order Awaiting Payment";
                if (empty($displayMessage)) {
                    $displayMessage = "Your order #{$orderId} is awaiting bank transfer confirmation. Please make your payment using your Order ID (<strong>" . htmlspecialchars($orderId) . "</strong>) as the reference. We will process your order once payment is confirmed (typically within 24 business hours).";
                }
                // Cart should NOT be cleared here. $effectiveStatus is not 'success'.
            }
            // Scenario D: COD order (cart already cleared by payment.php, but user might revisit)
            elseif ($dbPaymentMethod === 'cod' && ($dbOrderStatus === 'processing' || $dbOrderStatus === 'pending')) {
                $displayTitle = "Order Placed (Pay on Delivery)";
                if (empty($displayMessage)) {
                    $displayMessage = "Your order #{$orderId} has been placed and will be processed shortly. Payment will be collected upon delivery. 😊";
                }
                // Cart should have been cleared by payment.php.
            }
            // Scenario E: Other statuses (e.g., shipped, completed - cart should already be clear if process was followed)
            elseif (in_array($dbOrderStatus, ['shipped', 'completed', 'delivered'])) {
                $displayTitle = "Order " . ucfirst($dbOrderStatus);
                if (empty($displayMessage)) {
                    $displayMessage = "Your order #{$orderId} is currently " . htmlspecialchars($dbOrderStatus) . ". 😊";
                }
                // If statusFromUrl was 'success' for these, cart would be cleared by logic below.
            }
            // Default message if no specific condition met but order exists
            else {
                if (empty($displayMessage)) {
                    $displayTitle = "Order #" . htmlspecialchars($orderId);
                    $displayMessage = "Current status: " . htmlspecialchars(ucfirst($dbOrderStatus)) . ". 😊";
                }
            }
        } else { // Order ID valid format, but not found in DB
            $displayTitle = "Order Not Found";
            $displayMessage = "Sorry, order #{$orderId} could not be found. Please check the Order ID or contact support.";
            $effectiveStatus = 'error';
            $orderId = null; // Invalidate orderId for display consistency
        }
    } else { // $order object not available from includes.php
        $displayTitle = "System Error";
        $displayMessage = "There was an issue retrieving order details. Please contact support.";
        $effectiveStatus = 'error';
        error_log("Order object not available in order-confirmation.php. Check includes.php.");
    }
} else { // No valid order_id in URL
    $displayTitle = "Invalid Request";
    $displayMessage = "No order ID was specified, or the ID is invalid.";
    $effectiveStatus = 'error';
}

// 5. Clear cart if the order is effectively successful
// This happens BEFORE header_main.php is included.
if ($effectiveStatus === 'success') {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
        // Optional: Log this action for debugging
        // error_log("Cart cleared in order-confirmation.php for Order ID: " . ($orderId ?? 'N/A') . " due to effectiveStatus=success");
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($displayTitle) ?> - Goodguy</title>
    <style>
        .confirmation-panel {
            border: 1px solid #d0d7de;
            border-radius: 6px;
            padding: 24px;
            margin-bottom: 20px;
            background-color: #f6f8fa;
            text-align: left;
        }

        .confirmation-panel h4 {
            font-size: 1.5em;
            color: #24292f;
            margin-top: 0;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .confirmation-panel p {
            font-size: 1.1em;
            color: #57606a;
            line-height: 1.6;
            margin-bottom: 10px;
        }
    </style>
    <?php include "htlm-includes.php/metadata.php"; // Standard meta, CSS ?>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css"> <?php // Theme specific CSS ?>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; // Header will now reflect cleared cart if applicable ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container d-flex align-items-center">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Order Confirmation</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <div class="confirmation-panel">
                        <h4><?= htmlspecialchars($displayTitle); ?></h4>
                        <p><?= $displayMessage; // Message is already HTML-safe if constructed with htmlspecialchars where needed ?>
                        </p>
                        <?php if ($orderId && $effectiveStatus !== 'error'): // Show Order ID and link if order is valid ?>
                            <p>Your Order ID is: <strong><?= htmlspecialchars($orderId); ?></strong></p>
                            <hr>
                            <p class="mb-0">You can view your order details in your <a href="my_orders.php">account
                                    dashboard</a>.</p>
                        <?php elseif ($effectiveStatus === 'error' && $orderId): // Order ID was provided but not found ?>
                            <p>If you believe this is an error, please contact support with your Order ID:
                                <strong><?= htmlspecialchars($orderId); ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                </div><!-- End .container -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <?php include "footer.php"; ?>
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-modal.php"; ?>
    <?php include "jsfile.php"; ?>
</body>

</html>