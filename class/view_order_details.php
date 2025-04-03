<?php
session_start();
include "../conn.php";
require_once '../class/User.php';

$u = new User();

// Ensure user is logged in and is an admin or seller
if (!isset($_SESSION['uid']) || (!($u->isAdmin($mysqli, $_SESSION['uid']) || $u->getVendorStatus($mysqli, $_SESSION['uid']) == 'active'))) {
    header("Location: ../login.php");
    exit;
}

// Get order ID from the URL
$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$orderId) {
    header("Location: manage_orders.php");
    exit;
}

// Fetch order details from the database
$order = $u->getOrderDetailsFromLmOrders($mysqli, $orderId);

if (!$order) {
    // Order not found
    echo "Order not found.";
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Order Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container mt-5">
        <h2>Order Details</h2>
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order ID: <?php echo htmlspecialchars($order['order_id']); ?></h5>
                <p class="card-text">Customer: <?php echo htmlspecialchars($order['customer_id']); ?></p>
                <p class="card-text">Order Date: <?php echo htmlspecialchars($order['order_date_created']); ?></p>
                <p class="card-text">Total Amount: $<?php echo htmlspecialchars($order['order_total']); ?></p>
                <p class="card-text">Total items: <?php echo htmlspecialchars($order['order_total_items']); ?></p>
                <p class="card-text">Shipping Address: <?php echo htmlspecialchars($order['order_shipping_address']); ?>
                </p>
                <p class="card-text">Due Date: <?php echo htmlspecialchars($order['order_due_date']); ?></p>
                <p class="card-text">Status: <?php echo htmlspecialchars($order['order_status']); ?></p>
                <a href="manage_orders.php" class="btn btn-primary">Back to Orders</a>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>