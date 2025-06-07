<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "includes.php"; // Your main include file

$orderId = $_GET['order_id'] ?? null;
$status = $_GET['status'] ?? 'unknown';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <?php include "header_main.php"; ?>

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
                <?php if ($status == 'success' && isset($_SESSION['order_confirmation_message'])): ?>
                    <div class="alert alert-success text-center" role="alert">
                        <h4 class="alert-heading">Thank You!</h4>
                        <p><?= htmlspecialchars($_SESSION['order_confirmation_message']); ?></p>
                        <?php if ($orderId): ?>
                            <p>Your Order ID is: <strong><?= htmlspecialchars($orderId); ?></strong></p>
                        <?php endif; ?>
                        <hr>
                        <p class="mb-0">You can view your order details in your <a href="my-account.php?view=orders">account
                                dashboard</a>.</p>
                    </div>
                    <?php unset($_SESSION['order_confirmation_message']); ?>
                <?php else: ?>
                    <div class="alert alert-info text-center" role="alert">
                        <h4 class="alert-heading">Order Status</h4>
                        <p>Your order status will be updated shortly. If you have any questions, please contact support.</p>
                        <?php if ($orderId): ?>
                            <p>Your Order ID is: <strong><?= htmlspecialchars($orderId); ?></strong></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include "footer.php"; ?>
</body>

</html>