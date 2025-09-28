<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Vendor.php';

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
$vendor = $vendorObj->getVendorDetailsById($vendorId);

// --- Handle Not Found ---
if (!$vendor) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Vendor not found.'];
    header("Location: manage-vendors.php");
    exit();
}

// --- Flash Messages ---
$message = '';
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    $message = '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['text']) . '</div>';
    unset($_SESSION['flash_message']);
}

$statuses = ['active', 'inactive', 'pending', 'suspended'];

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Edit Vendor: <?= htmlspecialchars($vendor['business_name']) ?></title>
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
                    <li class="breadcrumb-item"><a
                            href="view-vendor.php?id=<?= $vendorId ?>"><?= htmlspecialchars($vendor['business_name']) ?></a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>

            <?= $message ?>

            <form action="process_edit_vendor.php" method="post">
                <input type="hidden" name="vendor_id" value="<?= $vendorId ?>">
                <div class="mb-9">
                    <div class="row g-3 mb-4">
                        <div class="col-auto">
                            <h2 class="mb-0">
                                Edit Vendor: <?= htmlspecialchars($vendor['business_name']) ?>
                            </h2>
                        </div>
                    </div>

                    <div class="row g-5">
                        <div class="col-12 col-xl-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Vendor Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row gx-4 gy-3">
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label" for="business_name">Business Name</label>
                                            <input class="form-control" id="business_name" name="business_name"
                                                type="text" value="<?= htmlspecialchars($vendor['business_name']) ?>"
                                                required>
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label" for="contact_name">Contact Name</label>
                                            <input class="form-control" id="contact_name" name="contact_name"
                                                type="text"
                                                value="<?= htmlspecialchars($vendor['contact_name'] ?? '') ?>">
                                        </div>
                                        <div class="col-12 col-sm-6">
                                            <label class="form-label" for="business_phone">Business Phone</label>
                                            <input class="form-control" id="business_phone" name="business_phone"
                                                type="tel"
                                                value="<?= htmlspecialchars($vendor['business_phone'] ?? '') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="business_address">Business Address</label>
                                            <textarea class="form-control" id="business_address" name="business_address"
                                                rows="4"><?= htmlspecialchars($vendor['business_address'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-xl-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Properties</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label" for="status">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status ?>" <?= ($vendor['status'] == $status) ? 'selected' : '' ?>>
                                                    <?= ucfirst($status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-footer text-end">
                                    <a href="view-vendor.php?id=<?= $vendorId ?>" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

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