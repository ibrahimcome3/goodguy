<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/User.php';

// --- Authentication & Authorization ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// --- Get Vendor ID ---
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Invalid or missing Vendor ID.'];
    header("Location: manage-vendors.php");
    exit();
}
$vendorId = (int) $_GET['id'];

$vendorObj = new Vendor($pdo);
$vendor = $vendorObj->getVendorDetailsById($vendorId) ?: [];

// --- Handle Not Found ---
if (!$vendor) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Vendor not found.'];
    header("Location: manage-vendors.php");
    exit();
}

// --- Helper function for status badge ---
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

// --- Flash Messages ---
$message = '';
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    // Support both new array format and old string format for flash messages
    if (is_array($flash) && isset($flash['type']) && isset($flash['text'])) {
        $message = '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['text']) . '</div>';
    } elseif (is_string($flash) && !empty($flash)) {
        // Fallback for older, string-based flash messages, assuming success type
        $message = '<div class="alert alert-success">' . htmlspecialchars($flash) . '</div>';
    }
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>View Vendor: <?= htmlspecialchars($vendor['business_name'] ?? 'Vendor') ?></title>
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
                    <li class="breadcrumb-item"><a href="manage-vendors.php">Vendors</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($vendor['business_name'] ?? 'Details') ?>
                    </li>
                </ol>
            </nav>

            <?= $message ?>

            <div class="mb-9">
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <h2 class="mb-0">
                            <span
                                class="fas fa-store me-2"></span><?= htmlspecialchars($vendor['business_name'] ?? 'Vendor Details') ?>
                        </h2>
                    </div>
                </div>

                <div class="row g-0">
                    <div class="col-lg-8 pe-lg-2">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Vendor Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="row gx-4 gy-3">
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <h6 class="text-body-tertiary">Business Name</h6>
                                        <p class="text-body-emphasis fw-semibold">
                                            <?= htmlspecialchars($vendor['business_name'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <h6 class="text-body-tertiary">Contact Name</h6>
                                        <p class="text-body-emphasis fw-semibold">
                                            <?= htmlspecialchars($vendor['contact_name'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <h6 class="text-body-tertiary">Status</h6>
                                        <span
                                            class="badge <?= getStatusBadgeClass($vendor['status'] ?? '') ?>"><?= ucfirst(htmlspecialchars($vendor['status'] ?? 'N/A')) ?></span>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <h6 class="text-body-tertiary">Business Phone</h6>
                                        <p class="text-body-emphasis fw-semibold">
                                            <?= htmlspecialchars($vendor['business_phone'] ?? 'N/A') ?>
                                        </p>
                                    </div>
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <h6 class="text-body-tertiary">Date Added</h6>
                                        <p class="text-body-emphasis fw-semibold">
                                            <?= isset($vendor['created_at']) ? date('F j, Y', strtotime($vendor['created_at'])) : 'N/A' ?>
                                        </p>
                                    </div>
                                    <div class="col-12">
                                        <h6 class="text-body-tertiary">Business Address</h6>
                                        <p class="text-body-emphasis fw-semibold">
                                            <?= nl2br(htmlspecialchars($vendor['business_address'] ?? 'N/A')) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 ps-lg-2">
                        <div class="sticky-sidebar">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Associated User</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar avatar-xl me-3">
                                            <div class="avatar-name rounded-circle">
                                                <span><?= !empty($vendor['username']) ? strtoupper(substr($vendor['username'], 0, 1)) : '?' ?></span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">
                                                <a href="view-customer.php?id=<?= (int) ($vendor['user_id'] ?? 0) ?>"
                                                    class="text-body-emphasis">
                                                    <?= htmlspecialchars($vendor['username'] ?? 'N/A') ?>
                                                </a>
                                            </h6>
                                            <p class="fs-9 mb-0">
                                                <?= htmlspecialchars($vendor['customer_email'] ?? 'N/A') ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <a href="edit-vendor.php?id=<?= (int) ($vendor['vendor_id'] ?? 0) ?>"
                                        class="btn btn-primary d-block w-100 mb-2">
                                        <span class="fas fa-pencil-alt me-2"></span>Edit Vendor
                                    </a>
                                    <a href="view-all-products.php?vendor=<?= (int) ($vendor['vendor_id'] ?? 0) ?>"
                                        class="btn btn-info d-block w-100 mb-2">
                                        <span class="fas fa-box me-2"></span>View Products
                                    </a>
                                    <a href="manage-vendors.php" class="btn btn-secondary d-block w-100">
                                        <span class="fas fa-arrow-left me-2"></span>Back to Vendor List
                                    </a>
                                </div>
                            </div>
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
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>
</body>

</html>