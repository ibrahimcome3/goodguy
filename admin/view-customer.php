<?php
require_once "../includes.php";

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once __DIR__ . '/../class/Order.php';
require_once __DIR__ . '/../class/User.php';

$customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($customerId <= 0) {
    header("Location: view-all-customers.php");
    exit();
}

// Instantiate objects
$userObj = new User($pdo);
$orderObj = new Order($pdo);

// --- Fetch Customer Details ---
$sql_customer = "SELECT 
                    c.*, 
                    COUNT(DISTINCT o.order_id) as total_orders,
                    COALESCE(SUM(o.order_total), 0) as total_spent,
                    MAX(o.order_date_created) as last_order_date
                FROM customer c
                LEFT JOIN lm_orders o ON c.customer_id = o.customer_id
                WHERE c.customer_id = :customer_id
                GROUP BY c.customer_id";

$stmt_customer = $pdo->prepare($sql_customer);
$stmt_customer->execute([':customer_id' => $customerId]);
$customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    // Customer not found, redirect
    header("Location: view-all-customers.php");
    exit();
}

// --- Fetch Customer Orders with Pagination ---
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$customer_orders = $orderObj->getOrdersForUser($customerId, $records_per_page, $offset);
$total_order_records = $orderObj->getTotalOrderCountForUser($customerId);
$total_pages = ceil($total_order_records / $records_per_page);


// Helper function for status badges
function getStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'active':
            return 'badge-phoenix-success';
        case 'inactive':
            return 'badge-phoenix-secondary';
        case 'pending':
            return 'badge-phoenix-warning';
        case 'suspended':
            return 'badge-phoenix-danger';
        default:
            return 'badge-phoenix-light';
    }
}

function getOrderStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'completed':
        case 'delivered':
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

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>View Customer - <?= htmlspecialchars($customer['customer_fname'] . ' ' . $customer['customer_lname']) ?>
    </title>
    <?php include 'admin-header.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content pt-3">
            <nav class="mb-3" aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="view-all-customers.php">Customers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customer Details</li>
                </ol>
            </nav>

            <div class="row g-3">
                <!-- Customer Details Card -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Customer Details</h5>
                                <a href="edit-customer.php?id=<?= $customer['customer_id'] ?>"
                                    class="btn btn-sm btn-falcon-default">
                                    <span class="fas fa-edit"></span> Edit
                                </a>
                            </div>
                        </div>
                        <div class="card-body bg-light">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar avatar-xl me-3">
                                    <img class="rounded-circle" src="../assets/img/team/avatar.webp" alt="" />
                                </div>
                                <div>
                                    <h5 class="mb-0">
                                        <?= htmlspecialchars($customer['customer_fname'] . ' ' . $customer['customer_lname']) ?>
                                    </h5>
                                    <span
                                        class="badge <?= getStatusBadgeClass($customer['status']) ?>"><?= ucfirst($customer['status']) ?></span>
                                </div>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><strong>Email:</strong>
                                    <?= htmlspecialchars($customer['customer_email']) ?></li>
                                <li class="mb-2"><strong>Phone:</strong>
                                    <?= htmlspecialchars($customer['customer_phone'] ?? 'N/A') ?></li>
                                <li class="mb-2"><strong>Joined:</strong>
                                    <?= date('M j, Y', strtotime($customer['date_created'])) ?></li>
                                <li class="mb-2"><strong>Address:</strong><br>
                                    <?= htmlspecialchars($customer['customer_address1'] ?? 'No address on file') ?><br>
                                    <?= htmlspecialchars($customer['customer_address2'] ?? '') ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Card -->
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h4 class="mb-1"><?= $customer['total_orders'] ?></h4>
                                    <p class="text-body-tertiary fs-9 mb-0">Total Orders</p>
                                </div>
                                <div class="col-4">
                                    <h4 class="mb-1">₦<?= number_format($customer['total_spent'], 2) ?></h4>
                                    <p class="text-body-tertiary fs-9 mb-0">Total Spent</p>
                                </div>
                                <div class="col-4">
                                    <h4 class="mb-1">
                                        <?= $customer['last_order_date'] ? date('M j, Y', strtotime($customer['last_order_date'])) : 'Never' ?>
                                    </h4>
                                    <p class="text-body-tertiary fs-9 mb-0">Last Order</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Orders Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Order History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive scrollbar">
                        <table class="table table-hover table-striped fs-9 mb-0">
                            <thead class="bg-200">
                                <tr>
                                    <th class="sort" data-sort="order_id">Order ID</th>
                                    <th class="sort" data-sort="date">Date</th>
                                    <th class="sort text-center" data-sort="status">Status</th>
                                    <th class="sort text-end" data-sort="total">Total</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                <?php if (empty($customer_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">This customer has not placed any orders.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customer_orders as $order): ?>
                                        <tr>
                                            <td class="order_id align-middle">
                                                <a class="fw-semi-bold"
                                                    href="view_order_details.php?order_id=<?= $order['order_id'] ?>">#<?= $order['order_id'] ?></a>
                                            </td>
                                            <td class="date align-middle">
                                                <?= date('M d, Y, g:i A', strtotime($order['order_date_created'])) ?>
                                            </td>
                                            <td class="status align-middle text-center">
                                                <span
                                                    class="badge <?= getOrderStatusBadgeClass($order['order_status']) ?>"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
                                            </td>
                                            <td class="total align-middle text-end">
                                                &#8358;<?= number_format((float) $order['order_total'], 2) ?>
                                            </td>
                                            <td class="align-middle text-end">
                                                <a href="view_order_details.php?order_id=<?= $order['order_id'] ?>"
                                                    class="btn btn-sm btn-falcon-default">
                                                    <span class="fas fa-eye"></span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <!-- Pagination for Orders -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Order page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?id=<?= $customerId ?>&page=<?= $page - 1 ?>">Previous</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?id=<?= $customerId ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?id=<?= $customerId ?>&page=<?= $page + 1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>

    <!-- Include necessary JavaScript files -->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/lodash/lodash.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/feather-icons/feather.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/dayjs/dayjs.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
</body>

</html>