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
    <title>Update Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container mt-5">
        <h2>Update Order</h2>
        <form id="update-order-form">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']); ?>">
            <div class="mb-3">
                <label for="customer_id" class="form-label">Customer ID</label>
                <input type="text" class="form-control" id="customer_id" name="customer_id"
                    value="<?= htmlspecialchars($order['customer_id']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="order_date_created" class="form-label">Order Date</label>
                <input type="text" class="form-control" id="order_date_created" name="order_date_created"
                    value="<?= htmlspecialchars($order['order_date_created']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="order_total" class="form-label">Order Total</label>
                <input type="text" class="form-control" id="order_total" name="order_total"
                    value="<?= htmlspecialchars($order['order_total']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="order_total_items" class="form-label">Total items</label>
                <input type="text" class="form-control" id="order_total_items" name="order_total_items"
                    value="<?= htmlspecialchars($order['order_total_items']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="order_shipping_address" class="form-label">Shipping adress</label>
                <input type="text" class="form-control" id="order_shipping_address" name="order_shipping_address"
                    value="<?= htmlspecialchars($order['order_shipping_address']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="order_due_date" class="form-label">Due Date</label>
                <input type="date" class="form-control" id="order_due_date" name="order_due_date"
                    value="<?= htmlspecialchars($order['order_due_date']); ?>">
            </div>
            <div class="mb-3">
                <label for="order_status" class="form-label">Order Status</label>
                <input type="text" class="form-control" id="order_status" name="order_status"
                    value="<?= htmlspecialchars($order['order_status']); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Update Order</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#update-order-form').submit(function (event) {
                event.preventDefault();
                let formData = $(this).serialize();

                $.ajax({
                    url: 'process_update_order.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            alert("Order updated successfully!");
                            // You can redirect or update the page here
                            window.location.href = "manage_orders.php";
                        } else {
                            alert("Error updating order: " + response.message);
                        }
                    },
                    error: function () {
                        alert("An error occurred while updating the order.");
                    }
                });
            });
        });
    </script>
</body>

</html>