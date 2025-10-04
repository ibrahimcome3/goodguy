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

// Build the base SQL query
$sql = "SELECT 
            v.vendor_id,
            v.contact_name,
            v.business_name,
            v.business_email,
            v.business_phone,
            v.business_address,
            v.description,
            v.status,
            v.created_at,
            COUNT(DISTINCT p.productID) as total_products,
            COALESCE(SUM(ol.quwantitiyofitem * ol.item_price), 0) as total_sales,
            MAX(o.order_date_created) as last_sale_date
        FROM vendors v
        LEFT JOIN productitem p ON v.vendor_id = p.vendor_id
        LEFT JOIN inventoryitem ii ON p.productID = ii.productItemID
        LEFT JOIN lm_order_line ol ON ii.InventoryItemID = ol.InventoryItemID
        LEFT JOIN lm_orders o ON ol.orderID = o.order_id";

$where_conditions = [];
$params = [];

// Add search filter
if ($search) {
    $where_conditions[] = "(v.business_name LIKE ? OR v.business_email LIKE ? OR v.business_phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

// Add status filter
if ($status_filter) {
    $where_conditions[] = "v.status = ?";
    $params[] = $status_filter;
}

// Add date filter
if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(v.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "v.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "v.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
    }
}

// Combine conditions if any exist
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Add grouping
$sql .= " GROUP BY v.vendor_id";

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT v.vendor_id) as count FROM vendors v";
if (!empty($where_conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$total_pages = ceil($total_records / $records_per_page);

// Add sorting and pagination to main query
$sql .= " ORDER BY v.created_at DESC LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;

// Execute main query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>View All Vendors - Admin Dashboard</title>
    <?php include 'admin-header.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content pt-3">
            <div class="container-fluid">
                <div class="card mb-3">
                    <div class="card-header">
                        <div class="row flex-between-center">
                            <div class="col-4 col-sm-auto d-flex align-items-center pe-0">
                                <h5 class="fs-0 mb-0 text-nowrap py-2 py-xl-0">View All Vendors</h5>
                            </div>
                            <div class="col-8 col-sm-auto ms-auto text-end ps-0">
                                <a href="add-vendor.php" class="btn btn-falcon-default btn-sm">
                                    <span class="fas fa-plus me-1"></span> Add New Vendor
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <form class="row g-3 mb-4" method="get">
                            <div class="col-auto">
                                <input type="search" name="search" class="form-control" placeholder="Search vendors..."
                                    value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-auto">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active
                                    </option>
                                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>
                                        Inactive</option>
                                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
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
                                <a href="view-all-vendors.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>

                        <!-- Vendors Table -->
                        <div class="table-responsive scrollbar">
                            <table class="table table-hover table-bordered">
                                <thead class="bg-200">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Products</th>
                                        <th>Total Sales</th>
                                        <th>Last Sale</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vendors as $vendor): ?>
                                        <tr>
                                            <td><?= $vendor['vendor_id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($vendor['business_name']) ?>
                                                <div class="small text-muted">
                                                    Contact: <?= htmlspecialchars($vendor['contact_name']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($vendor['business_email']) ?></td>
                                            <td><?= htmlspecialchars($vendor['business_phone']) ?></td>
                                            <td><?= $vendor['total_products'] ?></td>
                                            <td>₦<?= number_format($vendor['total_sales'], 2) ?></td>
                                            <td>
                                                <?= $vendor['last_sale_date'] ?
                                                    date('M j, Y', strtotime($vendor['last_sale_date'])) :
                                                    'No sales' ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge rounded-pill bg-<?= getStatusBadgeClass($vendor['status']) ?>">
                                                    <?= ucfirst($vendor['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view-vendor.php?id=<?= $vendor['vendor_id'] ?>"
                                                        class="btn btn-falcon-default" title="View Details">
                                                        <span class="fas fa-eye"></span>
                                                    </a>
                                                    <a href="edit-vendor.php?id=<?= $vendor['vendor_id'] ?>"
                                                        class="btn btn-falcon-default" title="Edit">
                                                        <span class="fas fa-edit"></span>
                                                    </a>
                                                    <button type="button" class="btn btn-falcon-default text-danger"
                                                        onclick="toggleVendorStatus(<?= $vendor['vendor_id'] ?>, '<?= $vendor['status'] ?>')"
                                                        title="<?= $vendor['status'] === 'active' ? 'Deactivate' : 'Activate' ?>">
                                                        <span
                                                            class="fas fa-<?= $vendor['status'] === 'active' ? 'ban' : 'check' ?>"></span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($vendors)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                No vendors found matching your criteria
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

    <!-- Add this helper function somewhere in your PHP code -->
    <?php
    function getStatusBadgeClass($status)
    {
        switch ($status) {
            case 'active':
                return 'success';
            case 'inactive':
                return 'danger';
            case 'pending':
                return 'warning';
            default:
                return 'secondary';
        }
    }
    ?>

    <!-- JavaScript for Status Toggle -->
    <script>
        function toggleVendorStatus(vendorId, currentStatus) {
            if (!confirm('Are you sure you want to ' +
                (currentStatus === 'active' ? 'deactivate' : 'activate') +
                ' this vendor?')) {
                return;
            }

            fetch('ajax/toggle-vendor-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    vendor_id: vendorId,
                    current_status: currentStatus
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error updating vendor status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the vendor status.');
                });
        }
    </script>
</body>

</html>