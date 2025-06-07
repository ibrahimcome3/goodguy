<?php
session_start();
require_once "includes.php"; // Should provide $pdo and load classes

// Check if the user is logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['login_redirect'] = 'my_orders.php'; // Redirect back here after login
    header("Location: login.php");
    exit();
}

$user_orders = [];
$user_name_for_greeting = "User";
$totalOrders = 0;
$totalPages = 0;
$itemsPerPage = 10; // Show 10 orders per page

// Determine current page from GET parameter, default to 1
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

$offset = ($currentPage - 1) * $itemsPerPage;
$selectedStatus = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';

$allowedStatuses = ['pending', 'paid', 'on-hold', 'processing', 'shipped', 'completed', 'cancelled', 'failed', '']; // Empty for 'All'

try {
    // Ensure User and Order objects are instantiated
    if (!isset($user) || !($user instanceof User)) {
        $user = new User($pdo);
    }
    if (!isset($orders) || !($orders instanceof Order)) {
        $orders = new Order($pdo);
    }

    // Fetch user's first name for greeting (optional, but nice)
    $userDetails = $user->getUserById($_SESSION['uid']);
    if ($userDetails && !empty($userDetails['first_name'])) {
        $user_name_for_greeting = htmlspecialchars($userDetails['first_name']);
    } elseif ($userDetails && !empty($userDetails['customer_fname'])) {
        $user_name_for_greeting = htmlspecialchars($userDetails['customer_fname']);
    }

    // Validate selectedStatus
    if (!in_array(strtolower($selectedStatus), $allowedStatuses)) {
        $selectedStatus = ''; // Default to all if invalid status is provided
    }
    // Fetch total number of orders for the user
    $totalOrders = $orders->getTotalOrderCountForUser($_SESSION['uid'], $selectedStatus);

    if ($totalOrders > 0) {
        $totalPages = ceil($totalOrders / $itemsPerPage);

        // Adjust currentPage if it's out of bounds
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
            $offset = ($currentPage - 1) * $itemsPerPage; // Recalculate offset
        }

        // Fetch paginated orders for the user, ordered by latest
        // Assumes Order class has getOrdersForUser($userId, $limit, $offset)
        $user_orders = $orders->getOrdersForUser($_SESSION['uid'], $itemsPerPage, $offset, $selectedStatus);
    } else {
        $totalPages = 0; // No pages if no orders
    }
} catch (Exception $e) {
    error_log("Error in my_orders.php setup: " . $e->getMessage());
    // Handle error, maybe set $user_orders to an empty array or show an error message
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Orders - Goodguyng.com</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <!-- Main CSS File -->
    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <style>
        /* Custom styles for the orders table */
        .custom-orders-table {
            margin-top: 1.5rem;
            /* Adds space above the table */
            margin-bottom: 1.5rem;
            /* Adds space below the table */
            /* You can also add margin-left and margin-right if needed, e.g., margin: 1.5rem auto; for centering with horizontal margins */
        }

        .custom-orders-table th,
        .custom-orders-table td {
            padding: 1rem;
            /* Adjust padding for table headers and cells - e.g., 0.75rem for default Bootstrap, 1rem for more space */
            vertical-align: middle;
            /* Optional: ensures content is vertically centered in cells */
        }

        /* Optional: If you want to adjust padding only for table body cells and not headers */
        /*
.custom-orders-table tbody td {
    padding-top: 1rem;
    padding-bottom: 1rem;
}
.custom-orders-table thead th {
    padding-top: 1rem;
    padding-bottom: 1rem;
}
*/
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-3">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="user_dashboard_overview.php">My Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Orders</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <h2 class="text-center mb-4">My Order History</h2>

                    <form action="my_orders.php" method="GET" class="mb-4">
                        <div class="row justify-content-center">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status_filter">Filter by Status:</label>
                                    <select name="status_filter" id="status_filter" class="form-control"
                                        onchange="this.form.submit()">
                                        <option value="" <?= ($selectedStatus == '') ? 'selected' : '' ?>>All Statuses
                                        </option>
                                        <option value="pending" <?= ($selectedStatus == 'pending') ? 'selected' : '' ?>>
                                            Pending</option>
                                        <option value="paid" <?= ($selectedStatus == 'paid') ? 'selected' : '' ?>>Paid
                                        </option>
                                        <option value="on-hold" <?= ($selectedStatus == 'on-hold') ? 'selected' : '' ?>>On
                                            Hold</option>
                                        <option value="processing" <?= ($selectedStatus == 'processing') ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= ($selectedStatus == 'shipped') ? 'selected' : '' ?>>
                                            Shipped</option>
                                        <option value="completed" <?= ($selectedStatus == 'completed') ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= ($selectedStatus == 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="failed" <?= ($selectedStatus == 'failed') ? 'selected' : '' ?>>
                                            Failed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    <?php if (empty($user_orders)): ?>
                        <p class="text-center">You have not placed any orders yet.</p>
                        <div class="text-center">
                            <a href="index.php" class="btn btn-outline-primary-2"><span>GO SHOP</span><i
                                    class="icon-long-arrow-right"></i></a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Order ID</th>
                                        <th scope="col">Order Date</th>
                                        <th scope="col">Total Amount</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_orders as $order_item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($order_item['order_id']) ?></td>
                                            <td><?= htmlspecialchars(date("M d, Y", strtotime($order_item['order_date_created']))) ?>
                                            </td>
                                            <td>&#8358;<?= htmlspecialchars(number_format($order_item['order_total'], 2)) ?>
                                            </td>
                                            <td><span
                                                    class="badge badge-<?= strtolower(htmlspecialchars($order_item['order_status'])) == 'completed' ? 'success' : (strtolower(htmlspecialchars($order_item['order_status'])) == 'pending' ? 'warning' : 'info') ?>"><?= htmlspecialchars(ucfirst($order_item['order_status'])) ?></span>
                                            </td>
                                            <td>
                                                <a href="order_detail.php?order_id=<?= htmlspecialchars($order_item['order_id']) ?>"
                                                    class="btn btn-outline-primary-2 btn-sm">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Order Pages" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php $filterQueryString = !empty($selectedStatus) ? '&status_filter=' . urlencode($selectedStatus) : ''; ?>
                                    <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $currentPage - 1; ?><?= $filterQueryString ?>" tabindex="-1"
                                            aria-disabled="<?php echo ($currentPage <= 1) ? 'true' : 'false'; ?>">Previous</a>
                                    </li>

                                    <?php
                                    $numLinks = 2; // Number of links to show on each side of the current page
                                    $startPage = max(1, $currentPage - $numLinks);
                                    $endPage = min($totalPages, $currentPage + $numLinks);

                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1' . $filterQueryString . '">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }

                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?><?= $filterQueryString ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor;

                                    if ($endPage < $totalPages) {
                                        if ($endPage < $totalPages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $filterQueryString . '">' . $totalPages . '</a></li>';
                                    } ?>
                                    <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                                        <a class="page-link"
                                            href="?page=<?php echo $currentPage + 1; ?><?= $filterQueryString ?>"
                                            aria-disabled="<?php echo ($currentPage >= $totalPages) ? 'true' : 'false'; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div><!-- End .container -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <?php include "footer.php"; ?>
    </div><!-- End .page-wrapper -->

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-modal.php"; ?>
    <?php include "jsfile.php"; ?>
</body>

</html>