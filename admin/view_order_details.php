<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Order.php';
require_once __DIR__ . '/../class/User.php';

// --- Authentication ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// --- Input Validation ---
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0) {
    header("Location: order.php?error=invalid_id");
    exit();
}

$orderObj = new Order($pdo);
$userObj = new User($pdo);

// --- Handle Form Submission (Status Update, Item Removal, Item Addition) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Fetch current order details to check its status before allowing modifications
    $currentOrder = $orderObj->getOrderDetails($orderId);
    if (in_array(strtolower($currentOrder['order_status']), ['completed', 'cancelled', 'failed'])) {
        // Redirect with an error if trying to modify a locked order
        header("Location: view_order_details.php?order_id=" . $orderId . "&error=locked");
        exit();
    }

    if ($_POST['action'] === 'update_status') {
        $newStatus = $_POST['order_status'] ?? null;
        $allowedStatuses = ['pending', 'paid', 'on-hold', 'processing', 'shipped', 'completed', 'cancelled', 'failed'];
        if ($newStatus && in_array($newStatus, $allowedStatuses)) {
            $orderObj->updateOrderStatus($orderId, $newStatus);
            // If the new status is 'completed', send the receipt email.
            if ($newStatus === 'completed') {
                $orderObj->sendPaymentReceiptEmail($orderId);
            }
            // Redirect to avoid form resubmission
            header("Location: view_order_details.php?order_id=" . $orderId . "&status_updated=1");
            exit();
        }
    } elseif ($_POST['action'] === 'remove_item') {
        $orderItemId = isset($_POST['order_item_id']) ? (int) $_POST['order_item_id'] : 0;
        if ($orderItemId > 0) {
            $orderObj->removeItemFromOrder($orderId, $orderItemId);
            // Redirect to avoid form resubmission and to see updated totals
            header("Location: view_order_details.php?order_id=" . $orderId . "&item_removed=1");
            exit();
        }
    } elseif ($_POST['action'] === 'add_item') {
        $inventoryItemId = isset($_POST['inventory_item_id']) ? (int) $_POST['inventory_item_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;

        if ($inventoryItemId > 0 && $quantity > 0) {
            $result = $orderObj->addItemToOrder($orderId, $inventoryItemId, $quantity);
            if ($result === true) {
                header("Location: view_order_details.php?order_id=" . $orderId . "&item_added=1");
                exit();
            } else {
                header("Location: view_order_details.php?order_id=" . $orderId . "&error_add=" . urlencode($result));
                exit();
            }
        }
    }
}

// --- Data Fetching ---
$order = $orderObj->getOrderDetails($orderId);


if (!$order) {
    // A more graceful "Not Found" page would be better in a real application
    die("Order not found.");
}

// --- Determine if the order is in a state that prevents modification ---
$isOrderLocked = in_array(strtolower($order['order_status']), ['completed', 'cancelled', 'failed']);

$orderItems = $orderObj->getOrderItems($orderId);
$shippingAddressId = $order['order_shipping_address'] ?? null;
$shippingAddress = null;
if ($shippingAddressId) {
    $shippingAddress = $orderObj->getOrderShippingAddress((int) $shippingAddressId);
}
$stateName = $shippingAddress ? $orderObj->getShippingAddressStateName((int) $shippingAddressId) : 'N/A';
$primaryPhoneNumber = $order['customer_id'] ? $userObj->getPrimaryActivePhoneNumber((int) $order['customer_id']) : null;

// Helper function for status badges
function getOrderStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'completed':
        case 'delivered':
        case 'paid':
            return 'badge-phoenix-success';
        case 'processing':
        case 'shipped':
            return 'badge-phoenix-info';
        case 'pending':
            return 'badge-phoenix-warning';
        case 'cancelled':
        case 'failed':
            return 'badge-phoenix-danger';
        default:
            return 'badge-phoenix-secondary';
    }
}

$allStatuses = ['pending', 'paid', 'on-hold', 'processing', 'shipped', 'completed', 'cancelled', 'failed'];

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Order Details #<?= htmlspecialchars($order['order_id']) ?></title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="index.php"><span class="fas fa-home me-1"></span>Dashboard</a>
                    </li>
                    <li class="breadcrumb-item"><a href="order.php">Orders</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Order Details</li>
                </ol>
            </nav>

            <?php if (isset($_GET['status_updated'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Order status updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['item_removed'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Item removed from order successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['item_added'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Item added to order successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error_add'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error:</strong> <?= htmlspecialchars($_GET['error_add']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'locked'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    This order is completed or cancelled and cannot be modified.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-5">
                <div class="col-12 col-lg-8">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 col-lg-4">
                                    <h6 class="text-body-tertiary">Order ID</h6>
                                    <h5 class="mb-0">#<?= htmlspecialchars($order['order_id']) ?></h5>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <h6 class="text-body-tertiary">Order Date</h6>
                                    <h5 class="mb-0">
                                        <?= date('M d, Y, g:i A', strtotime($order['order_date_created'])) ?>
                                    </h5>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <h6 class="text-body-tertiary">Status</h6>
                                    <span
                                        class="badge <?= getOrderStatusBadgeClass($order['order_status']) ?> fs-9"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                            <h5 class="mb-0">Order Items (<?= count($orderItems) ?>)</h5>
                            <?php if (!$isOrderLocked): ?>
                                <button class="btn btn-sm btn-phoenix-primary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#addItemModal">
                                    <span class="fas fa-plus me-1"></span> Add Item
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless fs-9 mb-0">
                                    <thead class="bg-warning-subtle">
                                        <tr>
                                            <th class="text-start ps-4">Product</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Total</th>
                                            <?php if (!$isOrderLocked): ?>
                                                <th class="text-end pe-3">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php
                                            $lineTotal = (float) ($item['item_price'] ?? 0) * (int) ($item['quwantitiyofitem'] ?? 0);
                                            ?>
                                            <tr>
                                                <td class="align-middle ps-4">
                                                    <a
                                                        href="view-single-inventory-item.php?inventoryItemId=<?= $item['InventoryItemID'] ?>">
                                                        <?= htmlspecialchars($item['description']) ?>
                                                    </a>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <?= (int) ($item['quwantitiyofitem'] ?? 0) ?>
                                                </td>
                                                <td class="align-middle text-end">
                                                    &#8358;<?= number_format((float) ($item['item_price'] ?? 0), 2) ?></td>
                                                <td class="align-middle text-end pe-4">
                                                    &#8358;<?= number_format($lineTotal, 2) ?></td>
                                                <?php if (!$isOrderLocked): ?>
                                                    <td class="align-middle text-end pe-3">
                                                        <form method="post"
                                                            action="view_order_details.php?order_id=<?= $orderId ?>"
                                                            onsubmit="return confirm('Are you sure you want to remove this item from the order?');">
                                                            <input type="hidden" name="action" value="remove_item">
                                                            <input type="hidden" name="order_item_id"
                                                                value="<?= $item['order_item_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-phoenix-danger"
                                                                title="Remove Item">
                                                                <span class="fas fa-trash"></span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-warning-subtle">
                            <div class="row justify-content-end">
                                <div class="col-auto">
                                    <table class="table table-sm table-borderless fs-9 text-end">
                                        <tr>
                                            <th class="text-body-tertiary">Subtotal:</th>
                                            <td class="fw-semibold">
                                                &#8358;<?= number_format((float) ($order['order_subtotal'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-body-tertiary">Shipping:</th>
                                            <td class="fw-semibold">
                                                &#8358;<?= number_format((float) ($order['shipping_cost'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                        <tr class="border-top">
                                            <th class="text-body-tertiary">Total:</th>
                                            <td class="fw-bold">
                                                &#8358;<?= number_format((float) ($order['order_total'] ?? 0), 2) ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Customer Details</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-0"><a
                                    href="view-customer.php?id=<?= $order['customer_id'] ?>"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></a>
                            </h6>
                            <a
                                href="mailto:<?= htmlspecialchars($order['customer_email']) ?>"><?= htmlspecialchars($order['customer_email']) ?></a>
                            <?php if ($primaryPhoneNumber): ?>
                                <br><a
                                    href="tel:<?= htmlspecialchars($primaryPhoneNumber) ?>"><?= htmlspecialchars($primaryPhoneNumber) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($shippingAddress): ?>
                                <address>
                                    <?= htmlspecialchars($shippingAddress['first_name'] ?? '') ?>
                                    <?= htmlspecialchars($shippingAddress['last_name'] ?? '') ?><br>
                                    <?= htmlspecialchars($shippingAddress['address1'] ?? '') ?><br>
                                    <?php if (!empty($shippingAddress['address2'])): ?>
                                        <?= htmlspecialchars($shippingAddress['address2']) ?><br>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($shippingAddress['city'] ?? '') ?>,
                                    <?= htmlspecialchars($stateName) ?>
                                    <?= htmlspecialchars($shippingAddress['zip'] ?? '') ?><br>
                                    <?= htmlspecialchars($shippingAddress['country'] ?? '') ?>
                                </address>
                            <?php else: ?>
                                <p class="text-body-tertiary">No shipping address provided.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?= $isOrderLocked ? 'Order Status' : 'Update Status' ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if ($isOrderLocked): ?>
                                <p>This order is <strong><?= htmlspecialchars($order['order_status']) ?></strong> and can no
                                    longer be modified.</p>
                            <?php else: ?>
                                <form method="post" action="view_order_details.php?order_id=<?= $orderId ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <div class="mb-3">
                                        <label class="form-label" for="order_status">Order Status</label>
                                        <select class="form-select" id="order_status" name="order_status">
                                            <?php foreach ($allStatuses as $status): ?>
                                                <option value="<?= $status ?>" <?= ($order['order_status'] == $status) ? 'selected' : '' ?>>
                                                    <?= ucfirst($status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button class="btn btn-primary w-100" type="submit">Update Order</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Item Modal -->
            <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addItemModalLabel">Add Item to Order
                                #<?= htmlspecialchars($order['order_id']) ?></h5>
                            <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <div class="input-group">
                                    <input class="form-control" id="product-search-input" type="text"
                                        placeholder="Search for products by name or ID..." />
                                    <button class="btn btn-outline-secondary" id="product-search-btn"
                                        type="button">Search</button>
                                </div>
                            </div>
                            <div id="product-search-results" style="max-height: 400px; overflow-y: auto;">
                                <div class="text-center text-body-tertiary py-4">Search for a product to begin.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions section - modify this part -->
            <div class="d-flex align-items-center mt-3">
                <a href="edit-order.php?id=<?= $order['order_id'] ?>" class="btn btn-phoenix-primary px-3 me-2">Edit
                    Order</a>

                <!-- Only show delete button if order is not locked -->
                <?php if (!$isOrderLocked): ?>
                    <button type="button" class="btn btn-phoenix-danger px-3 me-2" data-bs-toggle="modal"
                        data-bs-target="#deleteOrderModal">
                        <i class="fas fa-trash me-1"></i> Delete Order
                    </button>
                <?php endif; ?>

                <?php if (strtolower($order['order_status']) === 'completed'): ?>
                    <button type="button" class="btn btn-phoenix-success px-3 me-2" id="sendReceiptBtnTrigger">
                        <i class="fas fa-receipt me-1"></i> Send Receipt
                    </button>
                    <span id="sendReceiptStatus" class="ms-2 align-middle"></span>
                    <a href="print-order.php?id=<?= $order['order_id'] ?>" class="btn btn-phoenix-secondary px-3 me-2"
                        target="_blank">
                        <i class="fas fa-download me-1"></i> Download Receipt
                    </a>
                <?php else: ?>
                    <a href="print-order.php?id=<?= $order['order_id'] ?>" class="btn btn-phoenix-secondary px-3 me-2"
                        target="_blank">
                        <i class="fas fa-print me-1"></i> Print Order
                    </a>
                <?php endif; ?>

                <?php if (!$isOrderLocked): ?>
                    <!-- Invoice sending functionality -->
                    <button type="button" class="btn btn-phoenix-info px-3" data-bs-toggle="modal"
                        data-bs-target="#sendInvoiceModal">
                        <i class="fas fa-envelope me-1"></i> Send Invoice
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!$isOrderLocked): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Send Invoice</h5>
                    </div>
                    <div class="card-body">
                        <form id="sendInvoiceForm">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Customer Email (Default)</label>
                                <input type="text" class="form-control"
                                    value="<?= htmlspecialchars($order['customer_email']) ?>" disabled>
                                <div class="form-text">The invoice will be sent to this email by default.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Additional Recipients (Optional)</label>
                                <input type="text" class="form-control" name="additional_emails" id="additionalEmails"
                                    placeholder="email1@example.com, email2@example.com">
                                <div class="form-text">Separate multiple email addresses with commas.</div>
                            </div>
                            <button type="submit" class="btn btn-primary" id="sendInvoiceBtn">
                                <i class="fas fa-envelope me-1"></i> Send Invoice
                            </button>
                            <span id="sendInvoiceStatus" class="ms-2"></span>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>
    <!-- JavaScript dependencies -->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchBtn = document.getElementById('product-search-btn');
            const searchInput = document.getElementById('product-search-input');
            const searchResultsContainer = document.getElementById('product-search-results');
            const orderId = <?= $orderId ?>;

            const performSearch = () => {
                const searchTerm = searchInput.value.trim();
                if (searchTerm.length < 2) {
                    searchResultsContainer.innerHTML = '<div class="text-center text-body-tertiary py-4">Please enter at least 2 characters to search.</div>';
                    return;
                }

                searchResultsContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Searching...</div>';

                fetch(`ajax_product_search.php?term=${encodeURIComponent(searchTerm)}`)
                    .then(response => {
                        if (!response.ok) { throw new Error('Network response was not ok'); }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            searchResultsContainer.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            return;
                        }
                        if (data.length === 0) {
                            searchResultsContainer.innerHTML = '<div class="text-center text-body-tertiary py-4">No products found.</div>';
                            return;
                        }

                        let resultsHtml = '<ul class="list-group">';
                        data.forEach(product => {
                            const cost = parseFloat(product.cost).toLocaleString('en-NG', { style: 'currency', currency: 'NGN' });
                            resultsHtml += `
                                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
                                    <div class="me-3 mb-2 mb-md-0">
                                        <h6 class="mb-0">${product.label}</h6>
                                        <span class="fs-9 text-body-tertiary">Price: ${cost}</span>
                                    </div>
                                    <form method="post" action="view_order_details.php?order_id=${orderId}" class="d-flex align-items-center">
                                        <input type="hidden" name="action" value="add_item">
                                        <input type="hidden" name="inventory_item_id" value="${product.id}">
                                        <div class="input-group input-group-sm" style="width: 160px;">
                                            <input type="number" name="quantity" class="form-control" placeholder="Qty" value="1" min="1" required>
                                            <button type="submit" class="btn btn-sm btn-phoenix-primary">Add</button>
                                        </div>
                                    </form>
                                </li>`;
                        });
                        resultsHtml += '</ul>';
                        searchResultsContainer.innerHTML = resultsHtml;
                    })
                    .catch(error => {
                        console.error('Search Error:', error);
                        searchResultsContainer.innerHTML = '<div class="alert alert-danger">An error occurred while searching. Please try again.</div>';
                    });
            };

            searchBtn.addEventListener('click', performSearch);
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    performSearch();
                }
            });

            // Invoice sending functionality
            const sendInvoiceForm = document.getElementById('sendInvoiceForm');
            const sendInvoiceBtn = document.getElementById('sendInvoiceBtn');
            const sendInvoiceStatus = document.getElementById('sendInvoiceStatus');

            if (sendInvoiceForm) {
                sendInvoiceForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // Disable button and show loading state
                    sendInvoiceBtn.disabled = true;
                    sendInvoiceBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
                    sendInvoiceStatus.textContent = '';

                    const formData = new FormData(sendInvoiceForm);

                    fetch('ajax_send_invoice.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                sendInvoiceStatus.textContent = 'Invoice sent successfully!';
                                sendInvoiceStatus.className = 'text-success ms-2';
                                document.getElementById('additionalEmails').value = '';
                            } else {
                                sendInvoiceStatus.textContent = data.message || 'Failed to send invoice.';
                                sendInvoiceStatus.className = 'text-danger ms-2';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            sendInvoiceStatus.textContent = 'An error occurred while sending the invoice.';
                            sendInvoiceStatus.className = 'text-danger ms-2';
                        })
                        .finally(() => {
                            // Re-enable button
                            sendInvoiceBtn.disabled = false;
                            sendInvoiceBtn.innerHTML = '<i class="fas fa-envelope me-1"></i> Send Invoice';

                            // Clear status after 5 seconds
                            setTimeout(() => {
                                sendInvoiceStatus.textContent = '';
                            }, 5000);
                        });
                });
            }

            // Send Receipt functionality
            const sendReceiptBtn = document.getElementById('sendReceiptBtnTrigger');
            if (sendReceiptBtn) {
                const sendReceiptStatus = document.getElementById('sendReceiptStatus');

                sendReceiptBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Disable button and show loading state
                    sendReceiptBtn.disabled = true;
                    sendReceiptBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
                    sendReceiptStatus.textContent = '';

                    const formData = new FormData();
                    formData.append('order_id', orderId);

                    fetch('ajax_send_receipt.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                sendReceiptStatus.textContent = 'Receipt sent successfully!';
                                sendReceiptStatus.className = 'text-success ms-2 align-middle';
                            } else {
                                sendReceiptStatus.textContent = data.message || 'Failed to send receipt.';
                                sendReceiptStatus.className = 'text-danger ms-2 align-middle';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            sendReceiptStatus.textContent = 'An error occurred while sending.';
                            sendReceiptStatus.className = 'text-danger ms-2 align-middle';
                        })
                        .finally(() => {
                            // Re-enable button
                            sendReceiptBtn.disabled = false;
                            sendReceiptBtn.innerHTML = '<i class="fas fa-receipt me-1"></i> Send Receipt';

                            // Clear status after 5 seconds
                            setTimeout(() => { sendReceiptStatus.textContent = ''; }, 5000);
                        });
                });
            }
        });
    </script>
</body>

</html>