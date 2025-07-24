<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "includes.php"; // Your main include file

$orderId = $_GET['order_id'] ?? null;
$status = $_GET['status'] ?? 'unknown';
$paymentMethod = $_GET['method'] ?? null; // Get payment method if passed

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Goodguy</title>
    <style>
        .confirmation-panel {
            border: 1px solid #d0d7de;
            /* GitHub-like border color */
            border-radius: 6px;
            /* GitHub-like border radius */
            padding: 24px;
            /* More padding */
            margin-bottom: 20px;
            background-color: #f6f8fa;
            /* GitHub-like panel background */
            text-align: left;
            /* Align text to the left for a more standard feel */
        }

        .confirmation-panel h4 {
            font-size: 1.5em;
            /* Larger heading */
            color: #24292f;
            /* GitHub-like heading color */
            margin-top: 0;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .confirmation-panel p {
            font-size: 1.1em;
            color: #57606a;
            /* GitHub-like text color */
            line-height: 1.6;
            margin-bottom: 10px;
        }
    </style>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <?php include "header_main.php"; ?>

    <main class="main">
        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
            <div class="container d-flex align-items-center">

            </div><!-- End .container -->
        </nav><!-- End .breadcrumb-nav -->

        <div class="page-content">
            <div class="container">
                <?php if ($status == 'success' && isset($_SESSION['order_confirmation_message'])): ?>
                    <div class="confirmation-panel">
                        <h4>Thank You!</h4>
                        <p>
                            <?= htmlspecialchars($_SESSION['order_confirmation_message']); ?>
                        </p>
                        <?php if ($orderId): ?>
                            <p>Your Order ID is: <strong>
                                    <?= htmlspecialchars($orderId); ?>
                                </strong></p>
                        <?php endif; ?>
                        <hr>
                        <p class="mb-0">You can view your order details in your <a href="my-account.php?view=orders">account
                                dashboard</a>.</p>
                        <?php // Retained class for margin bottom ?>
                    </div>
                    <?php
                    // Clear the cart from the session after a successful order
                    unset($_SESSION['cart']);
                    ?>
                    <?php unset($_SESSION['order_confirmation_message']); ?>
                <?php elseif ($paymentMethod === 'transfer' && $orderId): ?>
                    <div class="confirmation-panel">
                        <h4>Thank You for Your Order!</h4>
                        <p>Your Order ID is: <strong>
                                <?= htmlspecialchars($orderId); ?>
                            </strong></p>
                        <p>You have selected <strong>Bank Transfer</strong> as your payment method.</p>
                        <p>Your payment will be checked and confirmed within 24 hours. Once confirmed, you will receive
                            a
                            notification, and your order will be processed.</p>
                        <p>Please ensure you have used your Order ID as the payment reference.</p>
                    </div>
                <?php else: ?>
                    <div class="confirmation-panel">
                        <h4>Order Status</h4>
                        <p>Your order status will be updated shortly. If you have any questions, please contact support.
                        </p>
                        <?php if ($orderId): ?>
                            <p>Your Order ID is: <strong>
                                    <?= htmlspecialchars($orderId); ?>
                                </strong></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include "footer.php"; ?>
</body>

</html>