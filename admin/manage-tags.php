<?php
// 1. BOOTSTRAP
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/Tag.php';

// 2. AUTHENTICATION
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// 3. OBJECT INSTANTIATION
$tagObj = new Tag($pdo);
$adminId = $_SESSION['admin_id'];

// 4. AJAX HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An unknown error occurred.'];

    switch ($_POST['action']) {
        case 'add_tag':
            $tagName = trim($_POST['tag_name'] ?? '');
            if (!empty($tagName)) {
                $newTagId = $tagObj->addTag($tagName, $adminId);
                if ($newTagId) {
                    $response = ['success' => true, 'message' => 'Tag added successfully.'];
                } else {
                    $response['message'] = 'Failed to add tag. It might already exist.';
                }
            } else {
                $response['message'] = 'Tag name cannot be empty.';
            }
            break;

        case 'update_tag':
            $tagId = (int) ($_POST['tag_id'] ?? 0);
            $tagName = trim($_POST['tag_name'] ?? '');
            if ($tagId > 0 && !empty($tagName)) {
                if ($tagObj->updateTag($tagId, $tagName)) {
                    $response = ['success' => true, 'message' => 'Tag updated successfully.'];
                } else {
                    $response['message'] = 'Failed to update tag. The name might already be in use.';
                }
            } else {
                $response['message'] = 'Invalid data provided for update.';
            }
            break;

        case 'delete_tag':
            $tagId = (int) ($_POST['tag_id'] ?? 0);
            if ($tagId > 0) {
                $sqlCheck = "SELECT COUNT(*) FROM product_tags WHERE tag_id = :tag_id";
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute([':tag_id' => $tagId]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $response['message'] = 'Cannot delete tag as it is currently associated with products.';
                } else {
                    $sqlDelete = "DELETE FROM tags WHERE tag_id = :tag_id";
                    $stmtDelete = $pdo->prepare($sqlDelete);
                    if ($stmtDelete->execute([':tag_id' => $tagId])) {
                        $response = ['success' => true, 'message' => 'Tag deleted successfully.'];
                    } else {
                        $response['message'] = 'Failed to delete tag.';
                    }
                }
            } else {
                $response['message'] = 'Invalid Tag ID.';
            }
            break;

        case 'approve_tag':
            $tagId = (int) ($_POST['tag_id'] ?? 0);
            if ($tagId > 0 && $tagObj->approveTag($tagId, $adminId)) {
                $response = ['success' => true, 'message' => 'Tag approved.'];
            } else {
                $response['message'] = 'Failed to approve tag.';
            }
            break;

        case 'reject_tag':
            $tagId = (int) ($_POST['tag_id'] ?? 0);
            if ($tagId > 0 && $tagObj->rejectTag($tagId)) {
                $response = ['success' => true, 'message' => 'Tag rejected and deleted.'];
            } else {
                $response['message'] = 'Failed to reject tag.';
            }
            break;
    }
    echo json_encode($response);
    exit;
}

// 5. DATA FETCHING FOR PAGE RENDER
// --- Server-side Pagination & Search for Approved Tags ---
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalApprovedTags = $tagObj->getTagsCount($searchTerm);
$totalPages = ceil($totalApprovedTags / $perPage);
$approvedTags = $tagObj->getPaginatedTags($searchTerm, $perPage, $offset);

// --- Fetch Pending Tags (usually not many, so no pagination needed) ---
$pendingTags = $tagObj->getPendingTags();

// Fetch product counts for approved tags
$approvedTagIds = array_column($approvedTags, 'tag_id');
$productCounts = [];
if (!empty($approvedTagIds)) {
    $placeholders = implode(',', array_fill(0, count($approvedTagIds), '?')); // Creates ?,?,?
    $sql = "SELECT tag_id, COUNT(product_id) as count FROM product_tags WHERE tag_id IN ($placeholders) GROUP BY tag_id"; // Use IN clause
    $stmt = $pdo->prepare($sql); // Prepare the statement
    $stmt->execute($approvedTagIds); // Execute with the array of IDs
    $productCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch into an associative array
}

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Manage Tags</title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <div class="pb-5">
                <div class="row g-4">
                    <div class="col-12 col-xxl-8">
                        <div class="mb-8">
                            <h2 class="mb-2">Manage Tags</h2>
                            <h5 class="text-body-tertiary fw-semibold">Organize products with relevant tags</h5>
                        </div>

                        <!-- Search and Add Button Row -->
                        <div class="row align-items-center justify-content-between pb-4 g-3">
                            <div class="col-auto">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#addTagModal">
                                    <span class="fas fa-plus me-2"></span>Add New Tag
                                </button>
                            </div>
                            <div class="col-12 col-md-auto">
                                <div class="search-box">
                                    <form class="position-relative" method="get" action="manage-tags.php">
                                        <input class="form-control search-input" name="search" type="search"
                                            placeholder="Search tags" aria-label="Search"
                                            value="<?= htmlspecialchars($searchTerm) ?>" />
                                        <button type="submit" class="btn p-0 border-0"
                                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent;"><span
                                                class="fas fa-search search-box-icon"></span></button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Approved Tags -->
                        <div class="card mb-5">
                            <div class="card-header">
                                <h5 class="mb-0">Approved Tags</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tag Name</th>
                                                <th>Products</th>
                                                <th>Created By</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($approvedTags)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">No approved tags found.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($approvedTags as $tag): ?>
                                                    <tr id="tag-row-<?= $tag['tag_id'] ?>">
                                                        <td class="align-middle fw-semibold">
                                                            <?= htmlspecialchars($tag['name']) ?>
                                                        </td>
                                                        <td class="align-middle">
                                                            <span class="badge bg-primary">
                                                                <?= $productCounts[$tag['tag_id']] ?? 0 ?>
                                                            </span>
                                                        </td>
                                                        <td class="align-middle text-body-tertiary">
                                                            <?= htmlspecialchars($tag['admin_username'] ?? 'N/A') ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button"
                                                                    class="btn btn-phoenix-secondary edit-tag-btn"
                                                                    data-id="<?= $tag['tag_id'] ?>"
                                                                    data-name="<?= htmlspecialchars($tag['name']) ?>"
                                                                    data-bs-toggle="modal" data-bs-target="#editTagModal">
                                                                    <span class="fas fa-edit"></span>
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-phoenix-secondary delete-tag-btn"
                                                                    data-id="<?= $tag['tag_id'] ?>"
                                                                    data-name="<?= htmlspecialchars($tag['name']) ?>">
                                                                    <span class="fas fa-trash-alt"></span>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <p class="mb-0 fs-9 text-body-tertiary">
                                            Showing <?= $offset + 1 ?> to
                                            <?= min($offset + $perPage, $totalApprovedTags) ?> of
                                            <?= $totalApprovedTags ?> entries
                                        </p>
                                    </div>
                                    <div class="col-auto d-flex">
                                        <?php include 'includes/pagination.php'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Tags -->
                        <?php if (!empty($pendingTags)): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Tags Pending Approval</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Tag Name</th>
                                                    <th>Suggested By</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingTags as $tag): ?>
                                                    <tr id="pending-tag-row-<?= $tag['tag_id'] ?>">
                                                        <td class="align-middle fw-semibold">
                                                            <?= htmlspecialchars($tag['name']) ?>
                                                        </td>
                                                        <td class="align-middle text-body-tertiary">
                                                            <?= htmlspecialchars($tag['vendor_name'] ?? 'Vendor') ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button"
                                                                    class="btn btn-phoenix-success approve-tag-btn"
                                                                    data-id="<?= $tag['tag_id'] ?>">
                                                                    <span class="fas fa-check"></span> Approve
                                                                </button>
                                                                <button type="button"
                                                                    class="btn btn-phoenix-danger reject-tag-btn"
                                                                    data-id="<?= $tag['tag_id'] ?>"
                                                                    data-name="<?= htmlspecialchars($tag['name']) ?>">
                                                                    <span class="fas fa-times"></span> Reject
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php include 'includes/admin_footer.php'; ?>
            </div>
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
    <script src="phoenix-v1.20.1/public/vendors/list.js/list.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>

    <!-- Add Tag Modal -->
    <div class="modal fade" id="addTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addTagForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_tag">
                        <div class="mb-3">
                            <label for="addTagName" class="form-label">Tag Name</label>
                            <input type="text" class="form-control" id="addTagName" name="tag_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Tag</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Tag Modal -->
    <div class="modal fade" id="editTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editTagForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Tag</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_tag">
                        <input type="hidden" id="editTagId" name="tag_id">
                        <div class="mb-3">
                            <label for="editTagName" class="form-label">Tag Name</label>
                            <input type="text" class="form-control" id="editTagName" name="tag_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addTagModal = new bootstrap.Modal(document.getElementById('addTagModal'));
            const editTagModal = new bootstrap.Modal(document.getElementById('editTagModal'));

            // Handle Add Tag Form Submission
            document.getElementById('addTagForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('manage-tags.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            addTagModal.hide();
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            });

            // Handle Edit Tag Button Click
            document.querySelectorAll('.edit-tag-btn').forEach(button => {
                button.addEventListener('click', function () {
                    document.getElementById('editTagId').value = this.dataset.id;
                    document.getElementById('editTagName').value = this.dataset.name;
                });
            });

            // Handle Edit Tag Form Submission
            document.getElementById('editTagForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('manage-tags.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            editTagModal.hide();
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            });

            // Handle Delete Tag Button Click
            document.querySelectorAll('.delete-tag-btn').forEach(button => {
                button.addEventListener('click', function () {
                    if (confirm(`Are you sure you want to delete the tag "${this.dataset.name}"?`)) {
                        const formData = new FormData();
                        formData.append('action', 'delete_tag');
                        formData.append('tag_id', this.dataset.id);
                        fetch('manage-tags.php', { method: 'POST', body: formData })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('tag-row-' + this.dataset.id).remove();
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            });
                    }
                });
            });

            // Handle Approve Tag Button Click
            document.querySelectorAll('.approve-tag-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const formData = new FormData();
                    formData.append('action', 'approve_tag');
                    formData.append('tag_id', this.dataset.id);
                    fetch('manage-tags.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Error: ' + data.message);
                            }
                        });
                });
            });

            // Handle Reject Tag Button Click
            document.querySelectorAll('.reject-tag-btn').forEach(button => {
                button.addEventListener('click', function () {
                    if (confirm(`Are you sure you want to reject and delete the tag "${this.dataset.name}"?`)) {
                        const formData = new FormData();
                        formData.append('action', 'reject_tag');
                        formData.append('tag_id', this.dataset.id);
                        fetch('manage-tags.php', { method: 'POST', body: formData })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('pending-tag-row-' + this.dataset.id).remove();
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            });
                    }
                });
            });
        });
    </script>
</body>

</html>