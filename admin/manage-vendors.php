<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Vendor.php';
require_once __DIR__ . '/../class/User.php'; // For admin checks

// --- Authentication & Authorization ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$userObj = new User($pdo);
// Optional: Add a check for super admin or specific permissions if needed
// if (!$userObj->isSuperAdmin($mysqli, $_SESSION['admin_id'])) {
//     die("Access Denied.");
// }

$vendorObj = new Vendor($pdo);

// --- Handle AJAX Requests ---
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    switch ($_POST['action']) {
        case 'toggle_status':
            if (!empty($_POST['vendor_id'])) {
                $vendorId = (int) $_POST['vendor_id'];
                $newStatus = $_POST['status'] ?? 'inactive';
                if ($vendorObj->updateVendorStatus($vendorId, $newStatus)) {
                    $response = [
                        'success' => true,
                        'message' => 'Vendor status updated successfully.',
                        'new_status' => $newStatus
                    ];
                } else {
                    $response['message'] = 'Failed to update vendor status.';
                }
            } else {
                $response['message'] = 'Vendor ID is required.';
            }
            break;

        case 'delete_vendor':
            if (!empty($_POST['vendor_id'])) {
                $vendorId = (int) $_POST['vendor_id'];
                // Optional: Add checks here, e.g., cannot delete vendor with active products.
                if ($vendorObj->deleteVendor($vendorId)) {
                    $response = ['success' => true, 'message' => 'Vendor deleted successfully.'];
                } else {
                    $response['message'] = 'Failed to delete vendor. It might be associated with existing products.';
                }
            } else {
                $response['message'] = 'Vendor ID is required.';
            }
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            break;
    }

    echo json_encode($response);
    exit;
}

// --- Server-side Pagination & Search ---
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalVendors = $vendorObj->getVendorsCount($searchTerm);
$totalPages = ceil($totalVendors / $perPage);
$vendors = $vendorObj->getPaginatedVendors($searchTerm, $perPage, $offset);

// --- Flash Messages ---
$message = '';
if (isset($_SESSION['flash_message'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['flash_message'] . '</div>';
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <title>Manage Vendors</title>
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
                    <li class="breadcrumb-item active" aria-current="page">Vendors</li>
                </ol>
            </nav>

            <div class="mb-5">
                <div class="row g-3 mb-4">
                    <div class="col-auto">
                        <h2 class="mb-0"><span class="fas fa-store me-2"></span>Vendors <span
                                class="text-body-tertiary fw-normal">(<?= $totalVendors ?>)</span></h2>
                    </div>
                </div>

                <?= $message ?>

                <div id="vendors"
                    data-list='{"valueNames":["business_name","contact_name","email_phone","status","date_added"],"page":<?= $perPage ?>,"pagination":true}'>
                    <div class="mb-4">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="search-box">
                                <form class="position-relative" method="get" action="manage-vendors.php">
                                    <input class="form-control search-input" name="search" type="search"
                                        placeholder="Search vendors..." aria-label="Search"
                                        value="<?= htmlspecialchars($searchTerm) ?>" />
                                    <button type="submit" class="btn p-0 border-0"
                                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent;"><span
                                            class="fas fa-search search-box-icon"></span></button>
                                </form>
                            </div>
                            <div class="ms-xxl-auto">
                                <a href="add-vendor.php" class="btn btn-primary">
                                    <span class="fas fa-plus me-2"></span>Add New Vendor
                                </a>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mx-n4 px-4 mx-lg-n6 px-lg-6 bg-body-emphasis border-top border-bottom border-translucent position-relative top-1">
                        <div class="table-responsive scrollbar mx-n1 px-1">
                            <table class="table fs-9 mb-0">
                                <thead>
                                    <tr>
                                        <th class="sort white-space-nowrap align-middle ps-4" scope="col"
                                            style="width:20%;" data-sort="business_name">BUSINESS NAME</th>
                                        <th class="sort white-space-nowrap align-middle ps-4" scope="col"
                                            style="width:20%;" data-sort="contact_name">CONTACT NAME</th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="email_phone"
                                            style="width:25%;">EMAIL / PHONE</th>
                                        <th class="sort align-middle text-center ps-4" scope="col" data-sort="status"
                                            style="width:10%;">STATUS</th>
                                        <th class="sort align-middle ps-4" scope="col" data-sort="date_added"
                                            style="width:15%;">DATE ADDED</th>
                                        <th class="sort text-end align-middle pe-0 ps-4" scope="col" style="width:10%;">
                                            ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody class="list" id="vendors-table-body">
                                    <?php if (empty($vendors)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="text-700">No vendors found.</div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <tr class="position-static" data-vendor-id="<?= $vendor['vendor_id'] ?>">
                                                <td class="business_name align-middle white-space-nowrap ps-4">
                                                    <a class="fw-semi-bold"
                                                        href="view-vendor.php?id=<?= $vendor['vendor_id'] ?>">
                                                        <?= htmlspecialchars($vendor['business_name']) ?>
                                                    </a>
                                                </td>
                                                <td class="contact_name align-middle white-space-nowrap ps-4">
                                                    <?= htmlspecialchars($vendor['contact_name'] ?? 'N/A') ?>
                                                </td>
                                                <td class="email_phone align-middle ps-4">
                                                    <div><?= htmlspecialchars($vendor['customer_email']) ?></div>
                                                    <div class="text-body-tertiary">
                                                        <?= htmlspecialchars($vendor['business_phone'] ?? '') ?>
                                                    </div>
                                                </td>
                                                <td class="status align-middle text-center fw-semi-bold ps-4">
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input class="form-check-input toggle-status-btn" type="checkbox"
                                                            role="switch" id="vendorStatus-<?= $vendor['vendor_id'] ?>"
                                                            data-id="<?= $vendor['vendor_id'] ?>"
                                                            <?= $vendor['status'] === 'active' ? 'checked' : '' ?>>
                                                        <label class="form-check-label"
                                                            for="vendorStatus-<?= $vendor['vendor_id'] ?>"></label>
                                                    </div>
                                                    <span class="status-text ms-2"><?= ucfirst($vendor['status']) ?></span>
                                                </td>
                                                <td class="date_added align-middle white-space-nowrap text-body-tertiary ps-4">
                                                    <?= date('M j, Y', strtotime($vendor['created_at'])) ?>
                                                </td>
                                                <td class="align-middle white-space-nowrap text-end pe-0 ps-4">
                                                    <div class="btn-reveal-trigger position-static">
                                                        <button
                                                            class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs-10"
                                                            type="button" data-bs-toggle="dropdown" data-boundary="window"
                                                            aria-haspopup="true" aria-expanded="false"
                                                            data-bs-reference="parent">
                                                            <span class="fas fa-ellipsis-h fs-10"></span>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end py-2"><a
                                                                class="dropdown-item"
                                                                href="view-vendor.php?id=<?= $vendor['vendor_id'] ?>"><span
                                                                    class="fas fa-eye me-2"></span>View</a><a
                                                                class="dropdown-item"
                                                                href="edit-vendor.php?id=<?= $vendor['vendor_id'] ?>"><span
                                                                    class="fas fa-pencil-alt me-2"></span>Edit</a>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item text-danger delete-btn" href="#!"
                                                                data-id="<?= $vendor['vendor_id'] ?>"
                                                                data-name="<?= htmlspecialchars($vendor['business_name']) ?>">
                                                                <span class="fas fa-trash-alt me-2"></span><span>Delete</span>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row align-items-center justify-content-between py-2 pe-0 fs-9">
                            <div class="col-auto d-flex">
                                <p class="mb-0 d-none d-sm-block me-3 fw-semibold text-body"
                                    data-list-info="data-list-info"></p>
                            </div>
                            <div class="col-auto d-flex">
                                <button class="page-link" data-list-pagination="prev"><span
                                        class="fas fa-chevron-left"></span></button>
                                <ul class="mb-0 pagination"></ul>
                                <button class="page-link pe-0" data-list-pagination="next"><span
                                        class="fas fa-chevron-right"></span></button>
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
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // AJAX for status toggle
            document.querySelectorAll('.toggle-status-btn').forEach(button => {
                button.addEventListener('change', function () {
                    const vendorId = this.dataset.id;
                    const newStatus = this.checked ? 'active' : 'inactive';
                    const statusTextElement = this.closest('td').querySelector('.status-text');

                    const formData = new FormData();
                    formData.append('action', 'toggle_status');
                    formData.append('vendor_id', vendorId);
                    formData.append('status', newStatus);

                    fetch('manage-vendors.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                statusTextElement.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                                // You can add a success toast notification here
                            } else {
                                // Revert the checkbox and show an error
                                this.checked = !this.checked;
                                statusTextElement.textContent = this.checked ? 'Active' : 'Inactive';
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.checked = !this.checked; // Revert on error
                            statusTextElement.textContent = this.checked ? 'Active' : 'Inactive';
                            alert('An error occurred while updating the status.');
                        });
                });
            });

            // AJAX for delete
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const vendorId = this.dataset.id;
                    const vendorName = this.dataset.name;

                    if (confirm(`Are you sure you want to delete the vendor "${vendorName}"? This action cannot be undone.`)) {
                        const formData = new FormData();
                        formData.append('action', 'delete_vendor');
                        formData.append('vendor_id', vendorId);

                        fetch('manage-vendors.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.querySelector(`tr[data-vendor-id='${vendorId}']`).remove();
                                    // Add a success toast notification here
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while deleting the vendor.');
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>