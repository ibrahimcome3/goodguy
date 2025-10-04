<?php
require_once "../includes.php";

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Initialize pagination variables
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Initialize search/filter variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Build the SQL query with filters
$sql = "SELECT 
            c.*, 
            COUNT(DISTINCT o.order_id) as total_orders,
            COALESCE(SUM(o.order_total), 0) as total_spent,
            MAX(o.order_date_created) as last_order_date
        FROM customer c
        LEFT JOIN lm_orders o ON c.customer_id = o.customer_id";

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.customer_fname LIKE ? OR c.customer_lname LIKE ? OR c.customer_email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(c.date_created) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "c.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "c.date_created >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
    }
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " GROUP BY c.customer_id";

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT c.customer_id) as count FROM customer c";
if (!empty($where_conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to the main query
$sql .= " ORDER BY c.date_created DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
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
?>


<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View All Customers - Admin Dashboard</title>
    <?php include 'admin-header.php'; ?>
    <style>
        /* ...existing styles... */

        /* Logo Styling */
        .logo {
            display: block;
            text-decoration: none;
        }

        .logo img {
            transition: transform 0.2s ease;
        }

        .logo:hover img {
            transform: scale(1.05);
        }

        .header-left {
            min-width: 200px;
        }

        @media (max-width: 767px) {
            .logo img {
                height: 40px !important;
            }

            .logo span {
                font-size: 1.2rem !important;
            }
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content pt-3">
            <div class="container-fluid">
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="row flex-between-end">
                            <div class="col-auto align-self-center">
                                <h5 class="mb-0">View All Customers</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body bg-light">
                        <!-- Filters -->
                        <form class="row g-3 mb-4" method="get">
                            <div class="col-auto">
                                <input type="search" name="search" class="form-control"
                                    placeholder="Search customers..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-auto">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active
                                    </option>
                                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>
                                        Inactive
                                    </option>
                                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>
                                        Pending
                                    </option>
                                    <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>
                                        Suspended</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="date_filter" class="form-select">
                                    <option value="">All Time</option>
                                    <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                                    <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>This Week
                                    </option>
                                    <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>This Month
                                    </option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="view-all-customers.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Customers Table -->
                        <div class="table-responsive scrollbar">
                            <table class="table table-hover table-bordered">
                                <thead class="bg-200">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                        <th>Last Order</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?= $customer['customer_id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($customer['customer_fname'] . ' ' . $customer['customer_lname']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($customer['customer_email']) ?></td>
                                            <td><?= htmlspecialchars($customer['customer_phone']) ?></td>
                                            <td><?= $customer['total_orders'] ?></td>
                                            <td>₦<?= number_format($customer['total_spent'], 2) ?></td>
                                            <td>
                                                <?= $customer['last_order_date'] ?
                                                    date('M j, Y', strtotime($customer['last_order_date'])) :
                                                    'Never' ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill <?= getStatusBadgeClass($customer['status']) ?>">
                                                    <?= ucfirst($customer['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view-customer.php?id=<?= $customer['customer_id'] ?>"
                                                        class="btn btn-falcon-default" title="View Details">
                                                        <span class="fas fa-eye"></span>
                                                    </a>
                                                    <a href="edit-customer.php?id=<?= $customer['customer_id'] ?>"
                                                        class="btn btn-falcon-default" title="Edit">
                                                        <span class="fas fa-edit"></span>
                                                    </a>
                                                    <button type="button" class="btn btn-falcon-default text-danger"
                                                        onclick="toggleCustomerStatus(<?= $customer['customer_id'] ?>, '<?= $customer['status'] ?>')"
                                                        title="<?= $customer['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                        <span
                                                            class="fas fa-<?= $customer['status'] === 'active' ? 'ban' : 'check' ?>"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                No customers found matching your criteria
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date_filter=<?= urlencode($date_filter) ?>">
                                            Previous
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link"
                                                href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date_filter=<?= urlencode($date_filter) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link"
                                            href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>&date_filter=<?= urlencode($date_filter) ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript for Status Toggle -->
    <script>
        function toggleCustomerStatus(customerId, currentStatus) {
            if (!confirm('Are you sure you want to ' +
                (currentStatus === 'active' ? 'deactivate' : 'activate') +
                ' this customer?')) {
                return;
            }

            fetch('ajax/toggle-customer-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    current_status: currentStatus
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error updating customer status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the customer status.');
                });
        }
    </script>
</body>

</html>