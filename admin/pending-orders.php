<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Order.php';

// --- Authentication ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$orderObj = new Order($pdo);

// --- Data Fetching & Filtering ---
$statusFilter = 'pending'; // Hardcoded for this page
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// --- Fetch paginated data ---
// Note: getTotalOrderCount doesn't account for the search term, so the total count might be inaccurate when searching.
// This is consistent with order.php but could be improved by enhancing the getTotalOrderCount method.
$totalOrders = $orderObj->getTotalOrderCount($statusFilter);
$totalPages = ceil($totalOrders / $perPage);
$orders = $orderObj->getPaginatedOrders($searchTerm, $statusFilter, $perPage, $offset);

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
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Pending Orders</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Pending Orders</li>
                </ol>
            </nav>

            <div class="mb-9">
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <h2 class="mb-0"><span class="fas fa-clock me-2"></span>Pending Orders <span
                                class="text-body-tertiary fw-normal">(<?= $totalOrders ?>)</span></h2>
                    </div>
                </div>

                <div id="orders"
                    data-list='{"valueNames":["order_id","customer","date","total","status"],"page":<?= $perPage ?>,"pagination":true}'>
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <form method="get" action="pending-orders.php">
                                        <div class="input-group">
                                            <input class="form-control form-control-sm shadow-none search" name="search"
                                                type="search" placeholder="Search by ID, customer, or email..."
                                                aria-label="Search" value="<?= htmlspecialchars($searchTerm) ?>" />
                                            <button class="btn btn-sm btn-outline-secondary" type="submit"><span
                                                    class="fas fa-search fs-10"></span></button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-sm btn-phoenix-secondary"><span
                                            class="fas fa-file-export me-2"></span>Export</button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive scrollbar">
                                <table class="table table-sm table-striped fs-9 mb-0">
                                    <thead>
                                        <tr>
                                            <th class="sort white-space-nowrap align-middle ps-4" scope="col"
                                                style="width:10%;" data-sort="order_id">ORDER ID</th>
                                            <th class="sort white-space-nowrap align-middle ps-4" scope="col"
                                                style="width:25%;" data-sort="customer">CUSTOMER</th>
                                            <th class="sort align-middle ps-4" scope="col" data-sort="date"
                                                style="width:20%;">DATE</th>
                                            <th class="sort align-middle text-end ps-4" scope="col" data-sort="total"
                                                style="width:15%;">TOTAL</th>
                                            <th class="sort align-middle text-center ps-4" scope="col"
                                                data-sort="status" style="width:15%;">STATUS</th>
                                            <th class="sort text-end align-middle pe-0 ps-4" scope="col"
                                                style="width:15%;">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="list" id="orders-table-body">
                                        <?php if (empty($orders)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <div class="text-body-tertiary">No pending orders found.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($orders as $order): ?>
                                                <tr class="position-static">
                                                    <td class="order_id align-middle white-space-nowrap ps-4">
                                                        <a class="fw-semi-bold"
                                                            href="view_order_details.php?order_id=<?= $order['order_id'] ?>">#<?= $order['order_id'] ?></a>
                                                    </td>
                                                    <td class="customer align-middle white-space-nowrap ps-4">
                                                        <a class="text-body-emphasis fw-semi-bold"
                                                            href="view-customer.php?id=<?= $order['customer_id'] ?? '' ?>">
                                                            <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?>
                                                        </a>
                                                    </td>
                                                    <td class="date align-middle ps-4">
                                                        <?= date('M d, Y, g:i A', strtotime($order['order_date_created'])) ?>
                                                    </td>
                                                    <td class="total align-middle text-end fw-semi-bold ps-4">
                                                        &#8358;<?= number_format((float) $order['order_total'], 2) ?>
                                                    </td>
                                                    <td class="status align-middle text-center fw-semi-bold ps-4">
                                                        <span
                                                            class="badge <?= getOrderStatusBadgeClass($order['order_status']) ?>"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
                                                    </td>
                                                    <td class="align-middle white-space-nowrap text-end pe-0 ps-4">
                                                        <a href="view_order_details.php?order_id=<?= $order['order_id'] ?>"
                                                            class="btn btn-sm btn-phoenix-secondary">
                                                            <span class="fas fa-eye"></span> View
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
                            <div class="row align-items-center">
                                <div class="col">
                                    <p class="mb-0 fs-9 text-body-tertiary">
                                        Showing <?= count($orders) > 0 ? $offset + 1 : 0 ?> to
                                        <?= min($offset + $perPage, $totalOrders) ?> of <?= $totalOrders ?> entries
                                    </p>
                                </div>
                                <div class="col-auto d-flex">
                                    <?php include 'includes/pagination.php'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include 'includes/admin_footer.php'; ?>
    </main>

    <!-- JavaScript dependencies -->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
</body>

</html>