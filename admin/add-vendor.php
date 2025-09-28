<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/User.php';

// Check if admin is logged in
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$vendorObj = new Vendor($pdo);
$userObj = new User($pdo);
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A CSRF token validation should be added here for security

    $data = [
        'user_id' => (int) ($_POST['user_id'] ?? 0),
        'business_name' => trim($_POST['business_name'] ?? ''),
        'contact_name' => trim($_POST['contact_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'status' => 'active' // Default status for new vendors
    ];

    if (empty($data['user_id']) || empty($data['business_name'])) {
        $message = '<div class="alert alert-danger">User and Business Name are required.</div>';
    } else {
        // Check if user is already a vendor
        if ($vendorObj->getVendorByUserId($data['user_id'])) {
            $message = '<div class="alert alert-danger">This user is already registered as a vendor.</div>';
        } else {
            if ($vendorObj->addVendor($data)) {
                $_SESSION['flash_message'] = 'Vendor added successfully!';
                header("Location: manage-vendors.php"); // Redirect to a vendor management page
                exit();
            } else {
                $message = '<div class="alert alert-danger">Failed to add vendor. Please try again.</div>';
            }
        }
    }
}

// Fetch users who are not yet vendors to populate the dropdown
// Note: This assumes a method `getUsersNotVendors()` exists in your User class.
$users = $userObj->getUsersNotVendors();

// Display session flash message if it exists
if (isset($_SESSION['flash_message'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['flash_message'] . '</div>';
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Add New Vendor</title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="manage-vendors.php">Vendors</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Vendor</li>
                </ol>
            </nav>
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10 col-xl-8">
                    <h2 class="mb-4">Add a New Vendor</h2>
                    <div class="card">
                        <div class="card-body">
                            <?= $message ?>
                            <form method="post" action="add-vendor.php">
                                <div class="mb-3">
                                    <label class="form-label fw-semi-bold" for="user_id">Select User *</label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">Choose a user to make a vendor...</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= (int) $user['customer_id'] ?>">
                                                <?= htmlspecialchars($user['username'] . ' (' . $user['customer_email'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select an existing user to grant vendor privileges. If the
                                        user
                                        doesn't exist, create a user account first.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semi-bold" for="business_name">Business Name *</label>
                                    <input class="form-control" id="business_name" name="business_name" type="text"
                                        placeholder="e.g., Acme Electronics" required />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semi-bold" for="contact_name">Contact Name</label>
                                    <input class="form-control" id="contact_name" name="contact_name" type="text"
                                        placeholder="e.g., John Doe" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semi-bold" for="phone">Phone</label>
                                    <input class="form-control" id="phone" name="phone" type="tel"
                                        placeholder="Vendor's contact phone" />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semi-bold" for="address">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"
                                        placeholder="Vendor's business address"></textarea>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="manage-vendors.php" class="btn btn-phoenix-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fas fa-plus me-2"></span>Add Vendor
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>

    <!-- JavaScript dependencies -->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/choices/choices.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('user_id')) {
                new Choices(document.getElementById('user_id'), {
                    searchEnabled: true,
                    itemSelectText: 'Press to select',
                });
            }
        });
    </script>
</body>

</html>