<?php
// admin/edit-order.php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Order.php';
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/User.php';

// --- Authentication ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// --- Input Validation ---
$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($orderId <= 0) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Invalid Order ID.'];
    header("Location: order.php"); // Assuming there's an order list page
    exit();
}

// --- Object Instantiation ---
$orderObj = new Order($pdo);
$userObj = new User($pdo);

// --- Handle Form Submission (POST) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A CSRF token check would be a good security addition here
    $newStatus = $_POST['order_status'] ?? null;
    $allowedStatuses = ['pending', 'paid', 'on-hold', 'processing', 'shipped', 'completed', 'cancelled', 'failed'];

    if ($newStatus && in_array($newStatus, $allowedStatuses)) {
        if ($orderObj->updateOrderStatus($orderId, $newStatus)) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Order status updated successfully!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to update order status.'];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Invalid status selected.'];
    }
    header("Location: edit-order.php?id=" . $orderId);
    exit();
}

// --- Data Fetching for Display ---
$order = $orderObj->getOrderDetails($orderId);
if (!$order) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => "Order with ID #{$orderId} not found."];
    header("Location: order.php");
    exit();
}

$orderItems = $orderObj->getOrderItems($orderId);
$shippingAddress = $orderObj->getOrderShippingAddress($order['order_shipping_address']);
$stateName = $shippingAddress ? $orderObj->getShippingAddressStateName($order['order_shipping_address']) : 'N/A';

// --- Flash Message Display ---
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    $message = '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($flash['text']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['flash_message']);
}

$allStatuses = ['pending', 'paid', 'on-hold', 'processing', 'shipped', 'completed', 'cancelled', 'failed'];

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Edit Order #<?= $orderId ?></title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem .75rem;
            border: 1px solid #d8e2ef;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: .25rem;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="order.php">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit Order</li>
                </ol>
            </nav>

            <?= $message ?>

            <div class="mb-9">
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <h2 class="mb-0">Order #<?= $orderId ?></h2>
                    </div>
                </div>

                <div class="row g-5">
                    <div class="col-12 col-xl-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Order Items</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm" id="order-items-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th class="text-end">Price</th>
                                                <th class="text-center">Quantity</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderItems as $item): ?>
                                                <tr data-item-id="<?= $item['order_item_id'] ?>">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <img src="../<?= htmlspecialchars($item['image_path'] ?? 'assets/images/products/default-product.png') ?>"
                                                                alt="" class="item-image me-2">
                                                            <div>
                                                                <a
                                                                    href="edit-product.php?id=<?= $item['productItemID'] ?>"><?= htmlspecialchars($item['description']) ?></a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">&#8358;<?= number_format($item['item_price'], 2) ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <input type="number"
                                                            class="form-control form-control-sm quantity-input"
                                                            value="<?= (int) $item['quwantitiyofitem'] ?>" min="0"
                                                            style="width: 70px; margin: auto;">
                                                    </td>
                                                    <td class="text-end item-total">
                                                        &#8358;<?= number_format($item['item_price'] * $item['quwantitiyofitem'], 2) ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-phoenix-danger remove-item-btn"><span
                                                                class="fas fa-trash"></span></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-8">
                                        <label for="add-product-search" class="form-label">Add Product</label>
                                        <select id="add-product-search" class="form-control"
                                            style="width: 100%;"></select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="add-product-quantity" class="form-label">Qty</label>
                                        <input type="number" id="add-product-quantity" class="form-control" value="1"
                                            min="1">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button id="add-product-btn" class="btn btn-primary w-100">Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Totals</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless fs-9">
                                    <tbody>
                                        <tr>
                                            <td class="text-body-tertiary">Subtotal:</td>
                                            <td class="text-end" id="order-subtotal">
                                                &#8358;<?= number_format((float) ($order['order_subtotal'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-body-tertiary">Shipping:</td>
                                            <td class="text-end" id="order-shipping">
                                                &#8358;<?= number_format((float) ($order['shipping_cost'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                        <tr class="border-top">
                                            <td class="text-body-tertiary fw-bold">Total:</td>
                                            <td class="text-end fw-bold" id="order-total">
                                                &#8358;<?= number_format((float) ($order['order_total'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-4">
                        <form method="post" action="edit-order.php?id=<?= $orderId ?>">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="order_status" class="form-label">Order Status</label>
                                        <select name="order_status" id="order_status" class="form-select">
                                            <?php foreach ($allStatuses as $status): ?>
                                                <option value="<?= $status ?>" <?= ($order['order_status'] == $status) ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <p class="mb-1"><strong>Date:</strong>
                                        <?= date('M d, Y, g:i A', strtotime($order['order_date_created'])) ?></p>
                                    <p class="mb-0"><strong>Payment:</strong>
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))) ?>
                                    </p>
                                </div>
                                <div class="card-footer text-end">
                                    <button type="button" class="btn btn-phoenix-secondary" id="send-invoice-btn">Send
                                        Invoice</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </form>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Customer</h5>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><a
                                        href="view-customer.php?id=<?= $order['customer_id'] ?>"><?= htmlspecialchars($order['customer_name']) ?></a>
                                </p>
                                <p class="mb-1"><a
                                        href="mailto:<?= htmlspecialchars($order['customer_email']) ?>"><?= htmlspecialchars($order['customer_email']) ?></a>
                                </p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Shipping Address</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($shippingAddress): ?>
                                    <address>
                                        <?= htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']) ?><br>
                                        <?= htmlspecialchars($shippingAddress['address1']) ?><br>
                                        <?php if (!empty($shippingAddress['address2'])): ?>
                                            <?= htmlspecialchars($shippingAddress['address2']) ?><br>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($shippingAddress['city']) ?>,
                                        <?= htmlspecialchars($stateName) ?>
                                        <?= htmlspecialchars($shippingAddress['zip'] ?? '') ?><br>
                                        <?= htmlspecialchars($shippingAddress['country']) ?>
                                    </address>
                                <?php else: ?>
                                    <p class="text-danger">No shipping address found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/admin_footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
            const orderId = <?= $orderId ?>;

            function formatProduct(product) {
                if (product.loading) {
                    return product.text;
                }

                var cost = parseFloat(product.cost);
                // Using text-body-tertiary to make it less prominent, and fs-9 for smaller font
                var costHtml = !isNaN(cost) ? '<span style="float: right; color: #6c757d; font-size: 0.8rem;">&#8358;' + cost.toFixed(2) + '</span>' : '';

                var $container = $(
                    '<div>' +
                    costHtml +
                    '<div>' + product.text + '</div>' +
                    '</div>'
                );

                return $container;
            }


            // Initialize Select2 for product search
            $('#add-product-search').select2({
                placeholder: 'Search for a product...',
                ajax: {
                    url: 'ajax_product_search.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term // search term
                        };
                    },
                    processResults: function (data) {

                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                templateResult: formatProduct,
                templateSelection: function (product) {
                    // The text to display when an item is selected
                    return product.text;
                },
                escapeMarkup: function (markup) { return markup; } // Allows HTML in results
            });

            // Add item to order
            $('#add-product-btn').on('click', function () {
                const inventoryItemId = $('#add-product-search').val();
                const quantity = $('#add-product-quantity').val();

                if (!inventoryItemId || quantity <= 0) {
                    Swal.fire('Error', 'Please select a product and specify a valid quantity.', 'error');
                    return;
                }

                $.ajax({
                    url: 'ajax_add_order_item.php',
                    type: 'POST',
                    data: {
                        order_id: orderId,
                        inventory_item_id: inventoryItemId,
                        quantity: quantity
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Success', 'Item added successfully.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message || 'Failed to add item.', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'An AJAX error occurred.', 'error');
                    }
                });
            });

            // Remove item from order
            $('#order-items-table').on('click', '.remove-item-btn', function () {
                const row = $(this).closest('tr');
                const orderItemId = row.data('item-id');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'ajax_remove_order_item.php',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                order_id: orderId,
                                order_item_id: orderItemId
                            }),
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    row.remove();
                                    updateTotals(response.order);
                                    Swal.fire('Deleted!', 'Item has been removed.', 'success');
                                } else {
                                    Swal.fire('Error', response.message || 'Failed to remove item.', 'error');
                                }
                            },
                            error: function () {
                                Swal.fire('Error', 'An AJAX error occurred.', 'error');
                            }
                        });
                    }
                });
            });

            // Update quantity
            let debounceTimer;
            $('#order-items-table').on('change', '.quantity-input', function () {
                clearTimeout(debounceTimer);
                const input = $(this);
                const row = input.closest('tr');
                const orderItemId = row.data('item-id');
                const quantity = parseInt(input.val());

                debounceTimer = setTimeout(() => {
                    $.ajax({
                        url: 'ajax_update_order_item.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            order_id: orderId,
                            order_item_id: orderItemId,
                            quantity: quantity
                        }),
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                if (quantity <= 0) {
                                    row.remove();
                                }
                                updateTotals(response.order);
                            } else {
                                Swal.fire('Error', response.message || 'Failed to update quantity.', 'error');
                            }
                        },
                        error: function () {
                            Swal.fire('Error', 'An AJAX error occurred.', 'error');
                        }
                    });
                }, 500);
            });

            // Send Invoice
            $('#send-invoice-btn').on('click', function () {
                Swal.fire({
                    title: 'Send Invoice',
                    html: `<p class="text-start">Send invoice to customer and/or additional recipients.</p>
                           <input id="swal-input2" class="swal2-input" placeholder="Additional emails (comma-separated)">`,
                    focusConfirm: false,
                    preConfirm: () => {
                        return document.getElementById('swal-input2').value;
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Send',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'ajax_send_invoice.php',
                            type: 'POST',
                            data: {
                                order_id: orderId,
                                additional_emails: result.value
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    Swal.fire('Sent!', 'The invoice has been sent.', 'success');
                                } else {
                                    Swal.fire('Error', response.message || 'Failed to send invoice.', 'error');
                                }
                            },
                            error: function () {
                                Swal.fire('Error', 'An AJAX error occurred.', 'error');
                            }
                        });
                    }
                });
            });

            function updateTotals(orderData) {
                const formatter = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN', minimumFractionDigits: 2 });
                $('#order-subtotal').text(formatter.format(orderData.order_subtotal));
                $('#order-shipping').text(formatter.format(orderData.shipping_cost));
                $('#order-total').text(formatter.format(orderData.order_total));

                // Also update individual item totals
                $('#order-items-table tbody tr').each(function () {
                    const row = $(this);
                    const priceText = row.find('td').eq(1).text().replace(/[^0-9.-]+/g, "");
                    const price = parseFloat(priceText);
                    const qty = parseInt(row.find('.quantity-input').val());
                    if (!isNaN(price) && !isNaN(qty)) {
                        const total = price * qty;
                        row.find('.item-total').text(formatter.format(total));
                    }
                });
            }
        });
    </script>
</body>

</html>