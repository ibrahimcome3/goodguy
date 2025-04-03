<?php
// order-confirmation.php
require_once "includes.php";
session_start();

// Check if the order ID is set
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: index.php"); // Redirect to home page if order ID is invalid
    exit();
}

$orderId = $_GET['order_id'];

// Get order details (you'll need to implement this method in the Order class)
$order = new Order();
$orderDetails = $order->getOrderDetails($orderId);

// Check if the order exists
if (!$orderDetails) {
    header("Location: index.php"); // Redirect to home page if order doesn't exist
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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
                <h1>Order Confirmation</h1>

                <p>Thank you for your order! Your order has been placed successfully.</p>
                <p>Your Order ID is: <?= $orderId ?></p>
                <!-- Display other order details here -->
            </div>
        </div>
    </main>
    <?php include "footer.php"; ?>
</body>

</html>