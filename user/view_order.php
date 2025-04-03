<?php
session_start();
include "../conn.php";
require_once '../class/User.php';
require_once '../class/Order.php';

$u = new User();
$o = new Order();

// Check if user is logged in.
if (!isset($_SESSION['uid'])) {
    header("Location: ../login.php");
    exit;
}

$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if (!$orderId) {
    header("Location: manage_orders.php");
    exit;
}

$order = $u->getOrderDetailsFromLmOrders($mysqli, $orderId); // Fetch order details

if (!$order) {
    echo "Order not found.";
    exit;
}

$orderItems = $u->getOrderItemsFromLmOrders($mysqli, $orderId);

// Function to calculate order totals (moved here)
function calculateOrderTotals($orderItems)
{
    $totalAmount = 0;
    $totalItems = 0;
    foreach ($orderItems as $item) {
        // Ensure 'cost' and 'quwantitiyofitem' are set and numeric
        $cost = isset($item['cost']) && is_numeric($item['cost']) ? (float) $item['cost'] : 0.00;
        $quantity = isset($item['quwantitiyofitem']) && is_numeric($item['quwantitiyofitem']) ? (int) $item['quwantitiyofitem'] : 0;

        $totalAmount += ($cost * $quantity);
        $totalItems += $quantity;
    }
    return ['totalAmount' => $totalAmount, 'totalItems' => $totalItems];
}

// Calculate initial totals
$orderTotals = calculateOrderTotals($orderItems);
$order['order_total'] = $orderTotals['totalAmount'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>View Order</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container mt-5">
        <h1>Order Details</h1>
        <div class="card">
            <div class="card-body">
                <!-- Existing order details -->
                <h5 class="card-title">Order ID: <?php echo htmlspecialchars($order['order_id']); ?></h5>
                <p class="card-text">Customer ID: <?php echo htmlspecialchars($order['customer_id']); ?></p>
                <p class="card-text">Order Date: <?php echo htmlspecialchars($order['order_date_created']); ?></p>
                <p class="card-text">Total Amount: N<span
                        id="order-total"><?php echo number_format($order['order_total'], 2, '.', ','); ?></span></p>
                <p class="card-text">Total Items: <span
                        id="order-total-items"><?php echo number_format($orderTotals['totalItems'], 0, '.', ','); ?></span>
                </p>
                <p class="card-text">Shipping Address:
                    <?php echo htmlspecialchars($u->getShippingAddressFromTable($mysqli, $order['order_shipping_address'])); ?>
                </p>
                <p class="card-text">Due Date: <?php echo htmlspecialchars($order['order_due_date']); ?></p>
                <p class="card-text">Status: <?php echo htmlspecialchars($order['order_status']); ?></p>

                <!-- Order Items -->
                <h2>Order Items</h2>
                <form id="update-order-form" method="post">
                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                    <table class="table table-striped" id="order-items-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                                <tr data-order-item-id="<?= $item['order_item_id'] ?>">
                                    <td><?= htmlspecialchars($item['description']); ?></td>
                                    <td><input type="number" class="quantity-input"
                                            name="quantity[<?= $item['order_item_id'] ?>]"
                                            value="<?= $item['quwantitiyofitem'] ?>"
                                            data-order-item-id="<?= $item['order_item_id'] ?>" min="0"></td>
                                    <td>N<?= htmlspecialchars(number_format($item['cost'], 2, '.', ',')); ?></td>
                                    <td class="item-total" data-item-price="<?= $item['cost'] ?>">
                                        N<?= number_format($item['cost'] * $item['quwantitiyofitem'], 2, '.', ','); ?></td>
                                    <td><button type="button" class="btn btn-danger delete-item"
                                            data-order-item-id="<?= $item['order_item_id'] ?>">Delete</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Order Total:</strong></td>
                                <td id="table-order-total">
                                    N<?php echo number_format($order['order_total'], 2, '.', ','); ?>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total Items:</strong></td>
                                <td id="table-order-total-items">
                                    <?php echo number_format($orderTotals['totalItems'], 0, '.', ','); ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="submit" class="btn btn-primary" id="submit-update">Update Order</button>
                    <br />
                </form>

                <a href="manage_orders.php" class="btn btn-primary">Back to Orders</a>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            // Function to update item total
            function updateItemTotal(row) {
                const quantity = parseInt(row.find('.quantity-input').val());
                const price = parseFloat(row.find('td:nth-child(3)').text().replace('N', '').replace(',', ''));
                const total = quantity * price;
                row.find('.item-total').text('N' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            // Function to update order totals
            function updateOrderTotals() {
                let orderTotal = 0;
                let orderTotalItems = 0;
                $('#order-items-table tbody tr').each(function () {
                    const quantity = parseInt($(this).find('.quantity-input').val());
                    const price = parseFloat($(this).find('td:nth-child(3)').text().replace('N', '').replace(',', ''));
                    orderTotal += quantity * price;
                    orderTotalItems += quantity;
                });
                $('#order-total').text(orderTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#order-total-items').text(orderTotalItems.toLocaleString('en-US'));
                $('#table-order-total').text('N' + orderTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#table-order-total-items').text(orderTotalItems.toLocaleString('en-US'));
            }

            // Update quantity
            $('#order-items-table').on('change', '.quantity-input', function () {
                const orderItemId = $(this).data('order-item-id');
                let quantity = $(this).val();
                const row = $(this).closest('tr');
                // Ensure quantity is not negative
                if (quantity < 0) {
                    quantity = 0;
                    $(this).val(0);
                }

                $.ajax({
                    url: 'update_order_item.php', // Create this file to handle updates
                    type: 'POST',
                    data: {
                        action: 'update_quantity',
                        order_item_id: orderItemId,
                        quantity: quantity
                    },
                    success: function (response) {
                        if (response == 'success') {
                            updateItemTotal(row);
                            updateOrderTotals(); // Update order totals after quantity change
                        }
                        else {
                            alert('error updating');
                        }
                    },
                    error: function (error) {
                        console.log(error)
                        alert('error on the ajax request');
                    }
                });
            });

            // Delete item
            $('#order-items-table').on('click', '.delete-item', function () {
                const orderItemId = $(this).data('order-item-id');
                const row = $(this).closest('tr');

                if (confirm('Are you sure you want to delete this item?')) {
                    $.ajax({
                        url: 'update_order_item.php', // Create this file to handle deletes
                        type: 'POST',
                        data: {
                            action: 'delete_item',
                            order_item_id: orderItemId
                        },
                        success: function (response) {
                            if (response == 'success') {
                                row.remove();
                                updateOrderTotals(); // Update order totals after item deletion
                            }
                            else {
                                console.log(response);
                                alert('Error while delete the item.');
                            }
                        },
                        error: function (error) {
                            console.log(error)
                            alert('error on the ajax request');
                        }
                    });
                }
            });
            $('#submit-update').hide();
            //update after submit
            $('#update-order-form').on('submit', function (e) {
                e.preventDefault();
                const form = this;
                $.ajax({
                    url: 'update_order_item.php',
                    type: 'POST',
                    data: $(form).serialize() + '&action=submit_form',
                    success: function (response) {
                        alert('Order updated!');
                        location.reload();
                    },
                    error: function (error) {
                        console.log(error)
                        alert('error on the ajax request');
                    }
                });

            });
            // Initial calculation of totals
            updateOrderTotals();
        });
    </script>
</body>

</html>