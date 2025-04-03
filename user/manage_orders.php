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

// Default filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$searchQuery = isset($_GET['search']) ? $_GET['search'] : null;

// Get orders (you'll implement this in User.php)
$orders = $u->getOrdersFromLmOrders($mysqli, $statusFilter, $searchQuery);

?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css"
        integrity="sha512-ELV+xyi8IhEApPS/pYnZtYVi+sI1D/j76w9Ym2j9oY0LWLn+YoZz1VfC13Y9/iYVv2p5fFf+oG4V3j+3hG0jQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Add any custom styles here */
    </style>
</head>

<body>
    <?php include '../seller/navbar.php'; ?>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"
        integrity="sha512-57oZ/vW8ANMjR/15/Q7Uf2V+vV7/wXb1h9+5zD00VvH/9fI/a/9eH29y/1E894/1aT0h8V6R9gqV/gG6w=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        // ...(your javascript code) ...
    </script>
</body>

<div class="container">
    <h1>Manage Orders</h1>

    <!-- Filter and Search -->
    <div class="mb-3">
        <form method="get" class="row">
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Accepted" <?= $statusFilter == 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                    <option value="Processing" <?= $statusFilter == 'Processing' ? 'selected' : '' ?>>Processing
                    </option>
                    <option value="Shipped" <?= $statusFilter == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="Delivered" <?= $statusFilter == 'Delivered' ? 'selected' : '' ?>>Delivered
                    </option>
                    <option value="Cancelled" <?= $statusFilter == 'Cancelled' ? 'selected' : '' ?>>Cancelled
                    </option>
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Search by Order ID or Customer"
                    value="<?= htmlspecialchars($searchQuery) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Apply</button>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer ID</th>
                <th>Order Date</th>
                <th>Total</th>
                <th>Total Items</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Location</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td><?= htmlspecialchars($order['customer_id']) ?></td>
                    <td><?= htmlspecialchars($order['order_date_created']) ?></td>
                    <td><?= htmlspecialchars($order['order_total']) ?></td>
                    <td><?= htmlspecialchars($order['order_total_items']) ?></td>
                    <td><?= htmlspecialchars($order['order_due_date']) ?></td>
                    <td><?= htmlspecialchars($order['order_status']) ?></td>
                    <td><?= htmlspecialchars($order['order_location']) ?></td>
                    <td>
                        <a href="view_order.php?order_id=<?= $order['order_id'] ?>"
                            class="btn btn-info btn-sm view-order-btn">View Details</a>
                        <button class="btn btn-warning btn-sm update-status-btn" data-order-id="<?= $order['order_id'] ?>"
                            data-current-status="<?= $order['order_status'] ?>"
                            data-current-due-date="<?= $order['order_due_date'] ?>">Update Status</button>
                        <button class="btn btn-secondary btn-sm update-location-btn"
                            data-order-id="<?= $order['order_id'] ?>"
                            data-current-location="<?= $order['order_location'] ?>">Update Location</button>
                        <?php if ($order['order_status'] == 'Pending'): ?>
                            <button class="btn btn-success btn-sm accept-order-btn"
                                data-order-id="<?= $order['order_id'] ?>">Accept Order</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">
<link rel="stylesheet" href="/resources/demos/style.css">
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
<script>
    $(document).ready(function () {
        // ... (Your existing code for view-order-btn) ...

        $('.update-status-btn').click(function () {
            let orderId = $(this).data('order-id');
            let currentStatus = $(this).data('current-status');
            let currentDueDate = $(this).data('current-due-date'); // Get current due date

            // Create the status select dropdown (unchanged)
            let statusSelect = $('<select class="form-select"></select>');
            let statuses = ['Pending', 'Accepted', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
            $.each(statuses, function (index, status) {
                let option = $('<option></option>')
                    .val(status)
                    .text(status);
                if (status === currentStatus) {
                    option.attr('selected', 'selected');
                }
                statusSelect.append(option);
            });

            // Create a date input for the due date
            let dueDateInput = $('<div>change order due date</div><input type="date" class="form-control" id="order_due_date" name="order_due_date" value="' + currentDueDate + '">');
            dueDateInput.datepicker({
                dateFormat: "yy-mm-dd" // Set the date format to YYYY-MM-DD
            });

            // Create the dialog
            let dialog = $('<div></div>')
                .append(statusSelect)
                .append('<br>')
                .append(dueDateInput);

            dialog.dialog({
                modal: true,
                title: 'Update Order Status and Due Date',
                buttons: {
                    'Update': function () {
                        let newStatus = statusSelect.val();
                        let newDueDate = dueDateInput.val();

                        $.ajax({
                            url: 'process_manage_orders.php',
                            type: 'POST',
                            data: {
                                action: 'update_order', // Changed action
                                order_id: orderId,
                                new_status: newStatus,
                                new_due_date: newDueDate
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    alert("Order updated successfully!");
                                    location.reload();
                                } else {
                                    alert("Error updating order: " + response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                alert('An error occurred while updating the order. Please try again later.');
                                console.error("AJAX Error:", error, xhr, status);
                            }
                        });
                        $(this).dialog('close');
                    },
                    Cancel: function () {
                        $(this).dialog('close');
                    }
                }
            });
        });
    });

    $('.update-location-btn').click(function () {
        let orderId = $(this).data('order-id');
        let currentLocation = $(this).data('current-location');

        // Create the location input
        let locationInput = $('<input type="text" class="form-control" id="order_location" name="order_location" value="' + currentLocation + '">');

        // Create the dialog
        let dialog = $('<div></div>')
            .append(locationInput);

        dialog.dialog({
            modal: true,
            title: 'Update Order Location',
            buttons: {
                'Update': function () {
                    let newLocation = locationInput.val();

                    $.ajax({
                        url: 'process_manage_orders.php',
                        type: 'POST',
                        data: {
                            action: 'update_location',
                            order_id: orderId,
                            new_location: newLocation
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                alert("Order location updated successfully!");
                                location.reload();
                            } else {
                                alert("Error updating order location: " + response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            alert('An error occurred while updating the order location. Please try again later.');
                            console.error("AJAX Error:", error, xhr, status);
                        }
                    });
                    $(this).dialog('close');
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
    });
    $('.accept-order-btn').click(function () {
        let orderId = $(this).data('order-id');
        $.ajax({
            url: 'process_manage_orders.php',
            type: 'POST',
            data: {
                action: 'accept_order',
                order_id: orderId,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    alert("Order accepted successfully!");
                    location.reload();
                } else {
                    alert("Error accepting order: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                alert('An error occurred while accepting the order. Please try again later.');
                console.error("AJAX Error:", error, xhr, status);
            }
        });
    });


</script>
</body>

</html>