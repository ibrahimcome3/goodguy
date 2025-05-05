<?php
session_start();
require_once '../class/User.php';
$u = new User($pdo);
include "../conn.php";

// Super admin check (only super admins can access this page)
if (!$u->isSuperAdmin($mysqli, $_SESSION['uid'])) {
    header("Location: seller-dashboard.php");
    exit;
}

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination functionality
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10; // Number of brands per page
$offset = ($page - 1) * $perPage;

//Get the total number of admins
$sql = "SELECT COUNT(*) AS totalAdmins FROM customer WHERE is_admin = 1";
$params = [];
$types = "";
if (!empty($searchTerm)) {
    $sql .= " AND (username LIKE ? OR customer_email LIKE ?)";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
    $types .= "ss";
}

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalAdmins = $row['totalAdmins'];
$totalPages = ceil($totalAdmins / $perPage);

//Get the admins for the current page

$sql = "SELECT * FROM customer WHERE is_admin = 1";
$params = [];
$types = "";
if (!empty($searchTerm)) {
    $sql .= " AND (username LIKE ? OR customer_email LIKE ?)";
    $params[] = "%{$searchTerm}%";
    $params[] = "%{$searchTerm}%";
    $types .= "ss";
}
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$admins = [];
if ($stmt) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
}
$superAdminsCount = $u->getSuperAdminsCount($mysqli);

// Calculate the range of page numbers to display dynamically
$maxLinks = 5; // Maximum number of page links to display
$startPage = max(1, $page - floor($maxLinks / 2));
$endPage = min($totalPages, $page + floor($maxLinks / 2));

// Adjust start and end pages if they are near the edges
if ($endPage - $startPage + 1 < $maxLinks) {
    if ($startPage === 1) {
        $endPage = min($totalPages, $maxLinks);
    } else {
        $startPage = max(1, $totalPages - $maxLinks + 1);
    }
}

// Handle form submission for adding a new admin
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    // Validate and sanitize user inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    //Check if the user already exist in the customer table
    $customer = $u->getCustomerByUsernameOrEmail($mysqli, $username, $email);
    if (!$customer) {
        echo "<p style='color:red;'>Customer not found.</p>";
        exit;
    }
    $userId = $customer['customer_id'];
    $u->setAdminStatus($mysqli, $userId, true);
    header("Location: manage_admins.php");
    exit;
}


?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Admins</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
</head>

<body>
    <?php include '../seller/navbar.php'; ?>
    <div class="container">
        <h1>Manage Admins</h1>
        <div class="mb-3">
            <form method="GET" action="manage_admins.php" id="searchForm">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" id="search" placeholder="Search admins..."
                        value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>
        </div>
        <div class="mb-3">
            <form method="post" action="manage_admins.php">
                <input type="hidden" name="action" value="add_admin">
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Add Admin</button>
            </form>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Super Admin</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= $admin['customer_id'] ?></td>
                        <td><?= $admin['username'] ?></td>
                        <td><?= $admin['customer_email'] ?></td>
                        <td><?= $admin['super_admin'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <?php if (!$admin['super_admin'] || ($admin['super_admin'] && $superAdminsCount > 2)): ?>
                                <form method="post" action="process_manage_admins.php">
                                    <input type="hidden" name="customer_id" value="<?= $admin['customer_id'] ?>">
                                    <input type="hidden" name="action" value="remove_admin">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to remove admin privileges?')">Remove
                                        Admin</button>
                                </form>
                            <?php else: ?>
                                <p>Cannot remove this super admin. At least two must exist.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pagination -->
        <?php if ($totalAdmins > $perPage): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>">Previous</a>
                    </li>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#search').on('input', function () {
                if ($(this).val() === '') {
                    // Redirect to the page without the search parameter
                    window.location.href = 'manage_admins.php';
                }
            });
        });
    </script>
</body>

</html>