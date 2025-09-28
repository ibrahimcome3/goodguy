<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Order.php';
require_once __DIR__ . '/../class/User.php';
require_once __DIR__ . '/../class/ProductItem.php';
require_once __DIR__ . '/../class/Vendor.php';

// --- Authentication & Authorization ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Instantiate objects
$orderObj = new Order($pdo);
$userObj = new User($pdo);
$productObj = new ProductItem($pdo);
$vendorObj = new Vendor($pdo);

// --- Fetch Dashboard Data ---
$totalOrders = $orderObj->getTotalOrderCount();
$pendingOrders = $orderObj->getTotalOrderCount('pending');
$totalUsers = $userObj->getTotalUserCount();
$totalProducts = $productObj->getTotalProductCount();
$totalVendors = $vendorObj->getVendorsCount();

// Fetch recent orders
$recentOrders = $orderObj->getRecentOrders(8);

// Helper function for status badge
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

// --- Chart Data Preparation ---
$chartData = [];
// For the sake of example, let's generate some dummy data for the last 30 days
for ($i = 30; $i > 0; $i--) {
    $chartData[] = rand(0, 10); // Random order count between 0 and 10
}

// Fetch daily order counts (last 30 days) for chart
$dailyRows = $pdo->query("
    SELECT DATE(COALESCE( order_date_created)) AS d, COUNT(*) AS c
    FROM lm_orders
    WHERE COALESCE( order_date_created) >= (CURDATE() - INTERVAL 29 DAY)
    GROUP BY DATE(COALESCE(order_date_created))
    ORDER BY d ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chartLabels = [];
$chartData = [];
$dayMap = [];
foreach ($dailyRows as $r) {
    $dayMap[$r['d']] = (int) $r['c'];
}
for ($i = 29; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i day"));
    $chartLabels[] = date('M j', strtotime($day));
    $chartData[] = $dayMap[$day] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <div class="pb-5">
                <div class="row g-4">
                    <div class="col-12 col-xxl-6">
                        <div class="mb-8">
                            <h2 class="mb-2">Admin Dashboard</h2>
                            <h5 class="text-body-tertiary fw-semibold">Here's what's going on at your store right now.
                            </h5>
                        </div>
                        <div class="row align-items-center g-4">
                            <!-- Total Orders Card -->
                            <div class="col-12 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="mb-1">Total Orders</h5>
                                                <h6 class="text-body-tertiary">All time</h6>
                                            </div>
                                            <h4><?= number_format($totalOrders) ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pending Orders Card -->
                            <div class="col-12 col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="mb-1">Pending Orders</h5>
                                                <h6 class="text-body-tertiary">Awaiting processing</h6>
                                            </div>
                                            <h4 class="text-warning"><?= number_format($pendingOrders) ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Users Card -->
                            <div class="col-12 col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="mb-1">Total Users</h5>
                                            </div>
                                            <h4><?= number_format($totalUsers) ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Products Card -->
                            <div class="col-12 col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="mb-1">Total Products</h5>
                                            </div>
                                            <h4><?= number_format($totalProducts) ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Vendors Card -->
                            <div class="col-12 col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h5 class="mb-1">Total Vendors</h5>
                                            </div>
                                            <h4><?= number_format($totalVendors) ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table (centered) -->
            <div class="mx-auto mt-6" style="max-width: 1100px;">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="mb-0">Recent Orders</h3>
                            <a href="manage_orders.php" class="btn btn-link px-0">View all orders
                                <span class="fas fa-chevron-right ms-1 fs-10"></span>
                            </a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover fs-9 mb-0 text-center">
                                <thead>
                                    <tr>
                                        <th class="sort border-top" scope="col" data-sort="order_id">ORDER ID</th>
                                        <th class="sort border-top" scope="col" data-sort="customer">CUSTOMER</th>
                                        <th class="sort border-top" scope="col" data-sort="date">DATE</th>
                                        <th class="sort border-top text-end" scope="col" data-sort="total">TOTAL</th>
                                        <th class="sort border-top text-center" scope="col" data-sort="status">STATUS
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="list">
                                    <?php if (empty($recentOrders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">No recent orders found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td class="order_id align-middle">
                                                    <a class="fw-semi-bold"
                                                        href="view_order_details.php?order_id=<?= $order['order_id'] ?>">#<?= $order['order_id'] ?></a>
                                                </td>
                                                <td class="customer align-middle">
                                                    <a class="text-body-emphasis fw-semi-bold"
                                                        href="view-customer.php?id=<?= $order['customer_id'] ?? '' ?>">
                                                        <?= htmlspecialchars(($order['customer_fname'] ?? '') . ' ' . ($order['customer_lname'] ?? '')) ?>
                                                    </a>
                                                </td>
                                                <td class="date align-middle">
                                                    <?= date('M d, Y, g:i A', strtotime($order['order_date_created'])) ?>
                                                </td>
                                                <td class="total align-middle text-end">
                                                    &#8358;<?= number_format((float) $order['order_total'], 2) ?>
                                                </td>
                                                <td class="status align-middle text-center">
                                                    <span
                                                        class="badge <?= getOrderStatusBadgeClass($order['order_status']) ?>"><?= ucfirst(htmlspecialchars($order['order_status'])) ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Chart (Last 30 Days) -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Orders (Last 30 Days)</h5>
                    <span class="text-body-tertiary small">Total: <?= number_format(array_sum($chartData)) ?></span>
                </div>
                <div class="card-body">
                    <canvas id="ordersChart" height="110"></canvas>
                </div>
            </div>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>
    <!-- ===============================================-->
    <!--    JavaScripts-->
    <!-- ===============================================-->
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const ctx = document.getElementById('ordersChart');
            if (!ctx) return;
            const dataLabels = <?= json_encode($chartLabels) ?>;
            const dataValues = <?= json_encode($chartData) ?>;
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dataLabels,
                    datasets: [{
                        label: 'Orders',
                        data: dataValues,
                        tension: 0.3,
                        fill: true,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78,115,223,0.15)',
                        pointRadius: 3,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#2e59d9',
                        pointHoverBorderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ' ' + ctx.parsed.y + ' orders'; }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        }
                    }
                }
            });
        })();
    </script>
</body>

</html>