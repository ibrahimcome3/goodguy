<?php
session_start();
require_once "../includes.php";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$userObj = new User($pdo);

// --- Authorization: Only Super Admins can manage users ---
// Note: The User class methods for admin management use mysqli.
// We'll use the $mysqli connection from includes.php for compatibility.
if (!$userObj->isSuperAdmin($mysqli, $_SESSION['admin_id'])) {
    // A more graceful "access denied" page would be better.
    die("Access Denied: You must be a Super Admin to manage users.");
}

$success_message = '';
$error_message = '';

// --- Handle POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;

    try {
        switch ($_POST['action']) {
            case 'add_admin':
                $emailOrUsername = trim($_POST['user_search'] ?? '');
                if (empty($emailOrUsername)) {
                    throw new Exception("Please enter a username or email to find a user.");
                }

                $userToAdd = $userObj->getCustomerByUsernameOrEmail($mysqli, $emailOrUsername, $emailOrUsername);

                if (!$userToAdd) {
                    throw new Exception("User not found with the specified username or email.");
                }

                if ($userObj->isAdmin($mysqli, $userToAdd['customer_id'])) {
                    throw new Exception("This user is already an admin.");
                }

                if ($userObj->setAdminStatus($mysqli, $userToAdd['customer_id'], true)) {
                    $success_message = "User '" . htmlspecialchars($userToAdd['customer_fname']) . "' has been promoted to Admin.";
                } else {
                    throw new Exception("Failed to promote user to admin.");
                }
                break;

            case 'remove_admin':
                if ($userId === $_SESSION['admin_id']) {
                    throw new Exception("You cannot remove your own admin privileges.");
                }

                // Prevent removing the last super admin
                if ($userObj->isSuperAdmin($mysqli, $userId) && $userObj->getSuperAdminsCount($mysqli) <= 1) {
                    throw new Exception("Cannot remove the last Super Admin.");
                }

                if ($userObj->removeAdmin($mysqli, $userId)) {
                    $success_message = "Admin privileges removed successfully.";
                } else {
                    throw new Exception("Failed to remove admin privileges.");
                }
                break;

            case 'toggle_super_admin':
                if ($userId === $_SESSION['admin_id'] && $userObj->getSuperAdminsCount($mysqli) <= 1) {
                    throw new Exception("You cannot remove your own Super Admin status as you are the only one.");
                }

                $isCurrentlySuperAdmin = $userObj->isSuperAdmin($mysqli, $userId);
                $newStatus = !$isCurrentlySuperAdmin;

                if ($userObj->setSuperAdminStatus($mysqli, $userId, $newStatus)) {
                    $success_message = "User status updated to " . ($newStatus ? "Super Admin." : "Regular Admin.");
                } else {
                    throw new Exception("Failed to update Super Admin status.");
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// --- Data Fetching for Display ---
$searchTerm = $_GET['search'] ?? '';
$admins = $userObj->searchAdmins($mysqli, $searchTerm);

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Admin Users Management</title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="index.php"><span class="fas fa-home me-1"></span>Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="site-settings.php">Settings</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Admin Users</li>
                </ol>
            </nav>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <!-- Add New Admin Card -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Add New Admin</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="admin-users.php" class="row g-2">
                                <input type="hidden" name="action" value="add_admin">
                                <div class="col-md">
                                    <label class="form-label" for="user_search">Find User by Username or Email</label>
                                    <input class="form-control" id="user_search" name="user_search" type="text"
                                        placeholder="e.g., johndoe or john.doe@example.com" required>
                                </div>
                                <div class="col-md-auto d-flex align-items-end">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-user-plus me-1"></i> Promote to Admin
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Admins List Card -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row justify-content-between">
                                <div class="col-md-auto">
                                    <h5 class="mb-0">Current Administrators</h5>
                                </div>
                                <div class="col-md-auto">
                                    <form>
                                        <div class="input-group">
                                            <input type="search" name="search" class="form-control form-control-sm"
                                                placeholder="Search admins..." value="<?= htmlspecialchars($searchTerm) ?>">
                                            <button class="btn btn-sm btn-outline-secondary" type="submit"><i
                                                    class="fas fa-search"></i></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="bg-body-tertiary">
                                        <tr>
                                            <th>User ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($admins)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">No administrators found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($admins as $admin):
                                                $isSuperAdmin = $userObj->isSuperAdmin($mysqli, $admin['customer_id']);
                                                ?>
                                                <tr>
                                                    <td><?= $admin['customer_id'] ?></td>
                                                    <td>
                                                        <a
                                                            href="view-customer.php?id=<?= $admin['customer_id'] ?>"><?= htmlspecialchars($admin['customer_fname'] . ' ' . $admin['customer_lname']) ?></a>
                                                    </td>
                                                    <td><?= htmlspecialchars($admin['customer_email']) ?></td>
                                                    <td>
                                                        <?php if ($isSuperAdmin): ?>
                                                            <span class="badge badge-phoenix-primary">
                                                                <i class="fas fa-crown me-1"></i>Super Admin
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-phoenix-secondary">Admin</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <!-- Toggle Super Admin Form -->
                                                            <form method="post" action="admin-users.php" class="d-inline">
                                                                <input type="hidden" name="action" value="toggle_super_admin">
                                                                <input type="hidden" name="user_id"
                                                                    value="<?= $admin['customer_id'] ?>">
                                                                <button type="submit"
                                                                    class="btn btn-sm btn-phoenix-primary"
                                                                    title="<?= $isSuperAdmin ? 'Demote to Admin' : 'Promote to Super Admin' ?>"
                                                                    <?= ($admin['customer_id'] === $_SESSION['admin_id'] && $userObj->getSuperAdminsCount($mysqli) <= 1) ? 'disabled' : '' ?>>
                                                                    <i class="fas fa-crown"></i>
                                                                </button>
                                                            </form>

                                                            <!-- Remove Admin Form -->
                                                            <?php if ($admin['customer_id'] !== $_SESSION['admin_id']): ?>
                                                                <button type="button" class="btn btn-sm btn-phoenix-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#removeAdminModal"
                                                                    data-user-id="<?= $admin['customer_id'] ?>"
                                                                    data-user-name="<?= htmlspecialchars($admin['customer_fname'] . ' ' . $admin['customer_lname']) ?>"
                                                                    title="Remove Admin Privileges">
                                                                    <i class="fas fa-user-slash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
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
            </div>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>

    <!-- Remove Admin Modal -->
    <div class="modal fade" id="removeAdminModal" tabindex="-1" aria-labelledby="removeAdminModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white" id="removeAdminModalLabel">Confirm Removal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove admin privileges for <strong id="userNameToRemove"></strong>?
                    <br><br>
                    This action cannot be undone. The user will revert to a standard customer account.
                </div>
                <div class="modal-footer">
                    <form method="post" action="admin-users.php">
                        <input type="hidden" name="action" value="remove_admin">
                        <input type="hidden" name="user_id" id="userIdToRemove">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Remove Admin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var removeAdminModal = document.getElementById('removeAdminModal');
            if (removeAdminModal) {
                removeAdminModal.addEventListener('show.bs.modal', function (event) {
                    // Button that triggered the modal
                    var button = event.relatedTarget;

                    // Extract info from data-bs-* attributes
                    var userId = button.getAttribute('data-user-id');
                    var userName = button.getAttribute('data-user-name');

                    // Update the modal's content.
                    var modalBodyStrong = removeAdminModal.querySelector('#userNameToRemove');
                    var modalInputUserId = removeAdminModal.querySelector('#userIdToRemove');

                    modalBodyStrong.textContent = userName;
                    modalInputUserId.value = userId;
                });
            }
        });
    </script>

</body>

</html>