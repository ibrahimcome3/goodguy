<?php
// 1. Start Session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Check admin authentication
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once "../includes.php";
require_once __DIR__ . '/../class/Category.php';

// Instantiate Category object
$categoryObj = new Category($pdo);

// --- Server-side Pagination & Search ---
$searchTerm = $_GET['search'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Base CTE for hierarchical data. This creates a sortable path to maintain hierarchy.
$baseCte = "
    WITH RECURSIVE category_path (category_id, name, description, parent_id, active, depth, path_sort) AS (
      SELECT 
        category_id, name, description, parent_id, active, 0, LPAD(CONVERT(category_id, CHAR), 10, '0')
      FROM 
        categories
      WHERE 
        parent_id IS NULL
      UNION ALL
      SELECT 
        c.category_id, c.name, c.description, c.parent_id, c.active, cp.depth + 1, CONCAT(cp.path_sort, ':', LPAD(CONVERT(c.category_id, CHAR), 10, '0'))
      FROM 
        category_path AS cp JOIN categories AS c
        ON cp.category_id = c.parent_id
    )
";

// Count total matching categories for pagination
$countSql = $baseCte . "SELECT COUNT(*) FROM category_path";
$countParams = [];
if (!empty($searchTerm)) {
    $countSql .= " WHERE name LIKE :searchTerm";
    $countParams[':searchTerm'] = "%$searchTerm%";
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalCategories = $countStmt->fetchColumn();
$totalPages = ceil($totalCategories / $perPage);

// Fetch paginated categories
$dataSql = $baseCte . "SELECT * FROM category_path";
$dataParams = [];
if (!empty($searchTerm)) {
    $dataSql .= " WHERE name LIKE :searchTerm";
    $dataParams[':searchTerm'] = "%$searchTerm%";
}
$dataSql .= " ORDER BY path_sort LIMIT :limit OFFSET :offset";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
if (!empty($searchTerm)) {
    $dataStmt->bindValue(':searchTerm', "%$searchTerm%");
}
$dataStmt->execute();
$categories = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Handle AJAX requests for immediate actions
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];

    switch ($_POST['action']) {
        case 'add':
            if (!empty($_POST['name'])) {
                $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
                $description = $_POST['description'] ?? '';
                $success = $categoryObj->addCategory($_POST['name'], $description, $parentId);

                if ($success) {
                    $response = [
                        'success' => true,
                        'message' => 'Category added successfully',
                        'category_id' => $success,
                        'name' => $_POST['name'],
                        'parent_id' => $parentId
                    ];
                } else {
                    $response['message'] = 'Failed to add category';
                }
            } else {
                $response['message'] = 'Category name is required';
            }
            break;

        case 'edit':
            if (!empty($_POST['category_id']) && !empty($_POST['name'])) {
                $categoryId = (int) $_POST['category_id'];
                $name = $_POST['name'];
                $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
                $description = $_POST['description'] ?? '';

                $success = $categoryObj->updateCategory($categoryId, $name, $description, $parentId);

                if ($success) {
                    $response = [
                        'success' => true,
                        'message' => 'Category updated successfully'
                    ];
                } else {
                    $response['message'] = 'Failed to update category';
                }
            } else {
                $response['message'] = 'Category ID and name are required';
            }
            break;

        case 'delete':
            if (!empty($_POST['category_id'])) {
                $categoryId = (int) $_POST['category_id'];

                // First check if category has any products
                $productCount = $categoryObj->getProductCount($categoryId);
                if ($productCount > 0) {
                    $response['message'] = "Cannot delete category with $productCount products. Reassign products first.";
                    echo json_encode($response);
                    exit;
                }

                // Then check for subcategories
                $subCategoryCount = $categoryObj->getSubcategoryCount($categoryId);
                if ($subCategoryCount > 0) {
                    $response['message'] = "Cannot delete category with subcategories. Delete or reassign subcategories first.";
                    echo json_encode($response);
                    exit;
                }

                // If no products and subcategories, proceed with deletion
                $success = $categoryObj->deleteCategory($categoryId);

                if ($success) {
                    $response = [
                        'success' => true,
                        'message' => 'Category deleted successfully'
                    ];
                } else {
                    $response['message'] = 'Failed to delete category';
                }
            } else {
                $response['message'] = 'Category ID is required';
            }
            break;

        case 'toggle_status':
            if (!empty($_POST['category_id'])) {
                $categoryId = (int) $_POST['category_id'];
                $newStatus = $categoryObj->toggleCategoryStatus($categoryId);

                if ($newStatus !== false) {
                    $response = [
                        'success' => true,
                        'message' => 'Category status updated successfully.',
                        'new_status' => $newStatus // 0 or 1
                    ];
                } else {
                    $response['message'] = 'Failed to update category status.';
                }
            } else {
                $response['message'] = 'Category ID is required.';
            }
            break;

        default:
            $response['message'] = 'Invalid action';
    }

    echo json_encode($response);
    exit;
}

// 5. Handle any status messages
$message = '';
if (isset($_SESSION['category_message'])) {
    $message = $_SESSION['category_message'];
    unset($_SESSION['category_message']);
}
?>

<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Categories</title>
    <?php include 'admin-header.php'; ?>
    <style>
        .category-row:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .subcategory {
            margin-left: 20px;
            border-left: 1px solid #dee2e6;
            padding-left: 15px;
        }

        .subcategory-indicator {
            color: #7e8299;
        }

        .badge-count {
            min-width: 22px;
        }

        .actions-column {
            width: 180px;
        }

        .nested-category-level-0 {
            margin-left: 0;
        }

        .nested-category-level-1 {
            margin-left: 20px;
            border-left: 1px solid #dee2e6;
            padding-left: 15px;
        }

        .nested-category-level-2 {
            margin-left: 40px;
            border-left: 1px solid #dee2e6;
            padding-left: 15px;
        }

        .nested-category-level-3 {
            margin-left: 60px;
            border-left: 1px solid #dee2e6;
            padding-left: 15px;
        }
    </style>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <div class="pb-5">
                <div class="row g-4">
                    <div class="col-12 col-xxl-6">
                        <div class="mb-8">
                            <h2 class="mb-2">Manage Categories</h2>
                            <h5 class="text-700 fw-semi-bold">Create and organize product categories</h5>

                            <?php if (!empty($message)): ?>
                                <div class="alert alert-success alert-dismissible fade show mt-3">
                                    <button class="btn-close" type="button" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                    <?= $message ?>
                                </div>
                            <?php endif; ?>
                            <div id="categoriesList">
                                <div class="row align-items-end justify-content-between pb-5 g-3">
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#addCategoryModal" id="addNewCategoryBtn">
                                            <span class="fas fa-plus me-2"></span>Add New Category
                                        </button>
                                    </div>
                                    <div class="col-12 col-md-auto">
                                        <div class="search-box">
                                            <form class="position-relative" method="get" action="manage_categories.php">
                                                <input class="form-control search-input" name="search" type="search"
                                                    placeholder="Search categories" aria-label="Search"
                                                    value="<?= htmlspecialchars($searchTerm) ?>" />
                                                <button type="submit" class="btn p-0 border-0"
                                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent;"><span
                                                        class="fas fa-search search-box-icon"></span></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-striped mb-0" id="categoriesTable">
                                                <thead>
                                                    <tr>
                                                        <th>Category Name</th>
                                                        <th>Products</th>
                                                        <th>Status</th>
                                                        <th class="actions-column">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($categories)): ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center py-5">
                                                                <div class="text-700">No categories found</div>
                                                                <button type="button" class="btn btn-sm btn-primary mt-3"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#addCategoryModal">
                                                                    <span class="fas fa-plus me-2"></span>Add First
                                                                    Category
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php else:
                                                        $categoryIds = array_column($categories, 'category_id');
                                                        $productCounts = $categoryObj->getProductCountsForCategories($categoryIds);
                                                        ?>

                                                        <?php foreach ($categories as $category): ?>
                                                            <tr class="category-row" data-id="<?= $category['category_id'] ?>">
                                                                <td class="category-name">
                                                                    <div
                                                                        class="nested-category-level-<?= $category['depth'] ?>">
                                                                        <?php if ($category['depth'] > 0): ?>
                                                                            <span class="subcategory-indicator me-1">└</span>
                                                                        <?php endif; ?>
                                                                        <?= htmlspecialchars($category['name']) ?>
                                                                    </div>
                                                                </td>
                                                                <td class="product-count">
                                                                    <span class="badge bg-primary badge-count">
                                                                        <?= $productCounts[$category['category_id']] ?? 0 ?>
                                                                    </span>
                                                                </td>
                                                                <td class="status">
                                                                    <?php if (!empty($category['active'])): ?>
                                                                        <span class="badge bg-success">Active</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-secondary">Inactive</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm">
                                                                        <button type="button"
                                                                            class="btn btn-phoenix-secondary toggle-status-btn"
                                                                            data-id="<?= $category['category_id'] ?>"
                                                                            title="Toggle Status">
                                                                            <span class="fas fa-power-off"></span>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-phoenix-secondary edit-btn"
                                                                            data-id="<?= $category['category_id'] ?>"
                                                                            data-name="<?= htmlspecialchars($category['name']) ?>"
                                                                            data-description="<?= htmlspecialchars($category['description'] ?? '') ?>"
                                                                            data-parent="<?= $category['parent_id'] ?? '' ?>"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#editCategoryModal">
                                                                            <span class="fas fa-edit"></span>
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-phoenix-secondary delete-btn"
                                                                            data-id="<?= $category['category_id'] ?>"
                                                                            data-name="<?= htmlspecialchars($category['name']) ?>">
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
                                                    <?= min($offset + $perPage, $totalCategories) ?> of
                                                    <?= $totalCategories ?> entries
                                                </p>
                                            </div>
                                            <div class="col-auto d-flex">
                                                <?php if ($totalPages > 1): ?>
                                                    <nav aria-label="Categories Page Navigation">
                                                        <ul class="pagination mb-0">
                                                            <!-- Previous Button -->
                                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                                <a class="page-link"
                                                                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>"
                                                                    aria-label="Previous">
                                                                    <span aria-hidden="true">&laquo;</span>
                                                                </a>
                                                            </li>

                                                            <!-- Page Numbers -->
                                                            <?php
                                                            $maxLinks = 5;
                                                            $startPage = max(1, $page - floor($maxLinks / 2));
                                                            $endPage = min($totalPages, $startPage + $maxLinks - 1);
                                                            if ($endPage - $startPage + 1 < $maxLinks) {
                                                                $startPage = max(1, $endPage - $maxLinks + 1);
                                                            }

                                                            for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                                    <a class="page-link"
                                                                        href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                                                </li>
                                                            <?php endfor; ?>

                                                            <!-- Next Button -->
                                                            <li
                                                                class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                                                <a class="page-link"
                                                                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>"
                                                                    aria-label="Next">
                                                                    <span aria-hidden="true">&raquo;</span>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </nav>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Hierarchy Visualization -->
                    <div class="col-12 col-xxl-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Category Hierarchy</h5>
                            </div>
                            <div class="card-body">
                                <div id="categoryTree" class="mb-3">
                                    <!-- Tree visualization will be rendered here -->
                                </div>
                                <hr>
                                <div class="mt-3">
                                    <h6>Best Practices for Categories</h6>
                                    <ul class="fs--1 text-600">
                                        <li>Keep category names concise and descriptive</li>
                                        <li>Limit the depth of categories to 3 levels for better user experience</li>
                                        <li>Ensure categories are logical and easy to navigate</li>
                                        <li>Avoid creating duplicate or similar categories</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="categoryName" required>
                        </div>
                        <div class="mb-3">
                            <label for="categoryDescription" class="form-label">Description (optional)</label>
                            <textarea class="form-control" id="categoryDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="parentCategory" class="form-label">Parent Category (optional)</label>
                            <input type="text" id="addParentSearch" class="form-control mb-2"
                                placeholder="Search for parent...">
                            <select class="form-select" id="parentCategory" size="5">
                                <option value="">None (Top Level Category)</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>">
                                        <?= str_repeat('— ', $category['depth']) . htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="addCategoryMessage"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveNewCategory">Create Category</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm">
                        <input type="hidden" id="editCategoryId">
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="editCategoryName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategoryDescription" class="form-label">Description (optional)</label>
                            <textarea class="form-control" id="editCategoryDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editParentCategory" class="form-label">Parent Category (optional)</label>
                            <input type="text" id="editParentSearch" class="form-control mb-2"
                                placeholder="Search for parent...">
                            <select class="form-select" id="editParentCategory" size="5">
                                <option value="">None (Top Level Category)</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>">
                                        <?= str_repeat('— ', $category['depth']) . htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-danger" id="parentCycleWarning" style="display: none;">
                                Cannot select a subcategory as parent (would create a cycle)
                            </div>
                        </div>
                        <div id="editCategoryMessage"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditCategory">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="deleteCategoryName"></strong>?</p>
                    <p class="mb-0 text-danger fs--1">This action cannot be undone.</p>
                    <div id="deleteCategoryMessage" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="deleteCategoryId">
                    <button type="button" class="btn btn-sm btn-phoenix-secondary"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript libraries -->
    <script src="phoenix-v1.20.1/public/vendors/popper/popper.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/anchorjs/anchor.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/is/is.min.js"></script>
    <script src="phoenix-v1.20.1/public/vendors/fontawesome/all.min.js"></script>
    <script src="phoenix-v1.20.1/public/assets/js/phoenix.js"></script>

    <!-- Category Tree Visualization -->
    <script src="https://d3js.org/d3.v7.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize modals and tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(tooltip => {
                new bootstrap.Tooltip(tooltip);
            });

            // --- Searchable Parent Category Dropdowns ---
            function setupSearchableDropdown(inputId, selectId) {
                const searchInput = document.getElementById(inputId);
                const selectDropdown = document.getElementById(selectId);
                // Clone the original options to avoid issues with live NodeLists
                const originalOptions = Array.from(selectDropdown.options).map(opt => opt.cloneNode(true));

                searchInput.addEventListener('input', function () {
                    const searchTerm = this.value.toLowerCase();
                    const selectedValue = selectDropdown.value; // Preserve selection

                    // Clear current options
                    selectDropdown.innerHTML = '';

                    // Filter and append options from the original, cloned list
                    originalOptions.forEach(option => {
                        if (option.value === "" || option.text.toLowerCase().includes(searchTerm)) {
                            selectDropdown.add(option.cloneNode(true));
                        }
                    });

                    // Restore selection if possible
                    selectDropdown.value = selectedValue;
                });
            }
            setupSearchableDropdown('addParentSearch', 'parentCategory');
            setupSearchableDropdown('editParentSearch', 'editParentCategory');

            // Add New Category
            document.getElementById('saveNewCategory').addEventListener('click', function () {
                const name = document.getElementById('categoryName').value.trim();
                const description = document.getElementById('categoryDescription').value.trim();
                const parentId = document.getElementById('parentCategory').value;
                const messageContainer = document.getElementById('addCategoryMessage');

                if (!name) {
                    messageContainer.innerHTML = `
                        <div class="alert alert-danger mt-3">
                            <span class="fas fa-exclamation-circle me-2"></span>
                            Category name is required
                        </div>
                    `;
                    return;
                }

                // Show loading message
                messageContainer.innerHTML = `
                    <div class="alert alert-info mt-3">
                        <span class="fas fa-spinner fa-spin me-2"></span>
                        Adding category...
                    </div>
                `;

                // Create FormData object
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('name', name);
                formData.append('description', description);
                if (parentId) {
                    formData.append('parent_id', parentId);
                }

                // Send AJAX request
                fetch('manage_categories.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageContainer.innerHTML = `
                            <div class="alert alert-success mt-3">
                                <span class="fas fa-check-circle me-2"></span>
                                ${data.message}
                            </div>
                        `;

                            setTimeout(() => {
                                window.location.reload(); // Refresh to show new category
                            }, 1000);
                        } else {
                            messageContainer.innerHTML = `
                            <div class="alert alert-danger mt-3">
                                <span class="fas fa-exclamation-circle me-2"></span>
                                ${data.message || 'Failed to add category'}
                            </div>
                        `;
                        }
                    })
                    .catch(error => {
                        messageContainer.innerHTML = `
                        <div class="alert alert-danger mt-3">
                            <span class="fas fa-exclamation-circle me-2"></span>
                            An error occurred: ${error.message}
                        </div>
                    `;
                    });
            });

            // Edit Category
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const description = this.getAttribute('data-description');
                    const parent = this.getAttribute('data-parent');

                    document.getElementById('editCategoryId').value = id;
                    document.getElementById('editCategoryName').value = name;
                    document.getElementById('editCategoryDescription').value = description;

                    // Set parent dropdown
                    const parentSelect = document.getElementById('editParentCategory');
                    if (parent) {
                        parentSelect.value = parent;
                    } else {
                        parentSelect.value = '';
                    }

                    // Disable selecting self or subcategories as parent
                    Array.from(parentSelect.options).forEach(option => {
                        if (option.value === id) {
                            option.disabled = true;
                        } else {
                            option.disabled = false;
                        }
                    });

                    document.getElementById('parentCycleWarning').style.display = 'none';
                    document.getElementById('editCategoryMessage').innerHTML = '';
                });
            });

            document.getElementById('saveEditCategory').addEventListener('click', function () {
                const id = document.getElementById('editCategoryId').value;
                const name = document.getElementById('editCategoryName').value.trim();
                const description = document.getElementById('editCategoryDescription').value.trim();
                const parentId = document.getElementById('editParentCategory').value;
                const messageContainer = document.getElementById('editCategoryMessage');

                if (!name) {
                    messageContainer.innerHTML = `
                        <div class="alert alert-danger mt-3">
                            <span class="fas fa-exclamation-circle me-2"></span>
                            Category name is required
                        </div>
                    `;
                    return;
                }

                // Show loading message
                messageContainer.innerHTML = `
                    <div class="alert alert-info mt-3">
                        <span class="fas fa-spinner fa-spin me-2"></span>
                        Updating category...
                    </div>
                `;

                // Create FormData object
                const formData = new FormData();
                formData.append('action', 'edit');
                formData.append('category_id', id);
                formData.append('name', name);
                formData.append('description', description);
                if (parentId) {
                    formData.append('parent_id', parentId);
                }

                // Send AJAX request
                fetch('manage_categories.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageContainer.innerHTML = `
                            <div class="alert alert-success mt-3">
                                <span class="fas fa-check-circle me-2"></span>
                                ${data.message}
                            </div>
                        `;

                            setTimeout(() => {
                                window.location.reload(); // Refresh to show updated category
                            }, 1000);
                        } else {
                            messageContainer.innerHTML = `
                            <div class="alert alert-danger mt-3">
                                <span class="fas fa-exclamation-circle me-2"></span>
                                ${data.message || 'Failed to update category'}
                            </div>
                        `;
                        }
                    })
                    .catch(error => {
                        messageContainer.innerHTML = `
                        <div class="alert alert-danger mt-3">
                            <span class="fas fa-exclamation-circle me-2"></span>
                            An error occurred: ${error.message}
                        </div>
                    `;
                    });
            });

            // Delete Category
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    document.getElementById('deleteCategoryId').value = id;
                    document.getElementById('deleteCategoryName').textContent = name;
                    document.getElementById('deleteCategoryMessage').innerHTML = '';

                    // Show the delete confirmation modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                    deleteModal.show();
                });
            });

            document.getElementById('confirmDelete').addEventListener('click', function () {
                const id = document.getElementById('deleteCategoryId').value;
                const messageContainer = document.getElementById('deleteCategoryMessage');

                // Show loading message
                messageContainer.innerHTML = `
                    <div class="alert alert-info mb-0">
                        <span class="fas fa-spinner fa-spin me-2"></span>
                        Deleting category...
                    </div>
                `;

                // Create FormData object
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('category_id', id);

                // Send AJAX request
                fetch('manage_categories.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            messageContainer.innerHTML = `
                            <div class="alert alert-success mb-0">
                                <span class="fas fa-check-circle me-2"></span>
                                ${data.message}
                            </div>
                        `;

                            setTimeout(() => {
                                window.location.reload(); // Refresh to show updated categories
                            }, 1000);
                        } else {
                            messageContainer.innerHTML = `
                            <div class="alert alert-danger mb-0">
                                <span class="fas fa-exclamation-circle me-2"></span>
                                ${data.message || 'Failed to delete category'}
                            </div>
                        `;
                            document.getElementById('confirmDelete').disabled = true;
                        }
                    })
                    .catch(error => {
                        messageContainer.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <span class="fas fa-exclamation-circle me-2"></span>
                            An error occurred: ${error.message}
                        </div>
                    `;
                    });
            });

            // Toggle Category Status
            document.querySelectorAll('.toggle-status-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');

                    const formData = new FormData();
                    formData.append('action', 'toggle_status');
                    formData.append('category_id', id);

                    fetch('manage_categories.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update the UI without reloading the page
                                const statusCell = document.querySelector(`.category-row[data-id='${id}'] td:nth-child(3)`);
                                if (data.new_status == 1) {
                                    statusCell.innerHTML = `<span class="badge bg-success">Active</span>`;
                                } else {
                                    statusCell.innerHTML = `<span class="badge bg-secondary">Inactive</span>`;
                                }
                                // You can add a small success toast here if you like
                            } else {
                                // Show an error message
                                const errorModalBody = document.getElementById('deleteCategoryMessage');
                                errorModalBody.innerHTML = `
                                <div class="alert alert-danger mb-0">
                                    <span class="fas fa-exclamation-circle me-2"></span>
                                    ${data.message || 'Failed to toggle status'}
                                </div>`;
                                const deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
                                document.getElementById('deleteCategoryName').textContent = 'Error';
                                deleteModal.show();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while toggling the status.');
                        });
                });
            });

            // Build category tree visualization
            buildCategoryTree();
        });

        function buildCategoryTree() {
            // Get categories data (already fetched for the select options)
            const categories = <?= json_encode($categories) ?>;

            if (!categories.length) {
                document.getElementById('categoryTree').innerHTML = '<div class="text-center py-5 text-700">No categories to display</div>';
                return;
            }

            // Transform data into hierarchical format
            const createHierarchy = (categories) => {
                const map = {};
                const roots = [];

                // Create nodes map
                categories.forEach(category => {
                    map[category.category_id] = { ...category, children: [] };
                });

                // Build tree structure
                categories.forEach(category => {
                    const node = map[category.category_id];
                    if (category.parent_id && map[category.parent_id]) {
                        map[category.parent_id].children.push(node);
                    } else {
                        roots.push(node);
                    }
                });

                return roots;
            };

            const hierarchy = createHierarchy(categories);

            // Render tree using D3.js if available, otherwise use simple HTML rendering
            if (typeof d3 !== 'undefined') {
                renderD3Tree(hierarchy);
            } else {
                renderSimpleTree(hierarchy);
            }
        }

        function renderD3Tree(data) {
            // D3.js tree visualization code
            const width = document.getElementById('categoryTree').clientWidth;
            const margin = { top: 20, right: 30, bottom: 30, left: 40 };
            const innerWidth = width - margin.left - margin.right;

            // Create tree layout
            const root = d3.hierarchy({ children: data })
                .sum(d => 1)
                .sort((a, b) => b.value - a.value);

            // Create tree with appropriate size
            const treeHeight = root.height * 60 + 40;
            const height = treeHeight < 300 ? 300 : treeHeight;

            // Clear previous content
            d3.select('#categoryTree').html('');

            // Create SVG
            const svg = d3.select('#categoryTree')
                .append('svg')
                .attr('width', width)
                .attr('height', height)
                .append('g')
                .attr('transform', `translate(${margin.left}, ${margin.top})`);

            // Create tree layout
            const tree = d3.tree().size([innerWidth, height - margin.top - margin.bottom]);
            tree(root);

            // Create links
            const links = svg.selectAll('.link')
                .data(root.links())
                .enter()
                .append('path')
                .attr('class', 'link')
                .attr('fill', 'none')
                .attr('stroke', '#ddd')
                .attr('stroke-width', 1.5)
                .attr('d', d3.linkHorizontal()
                    .x(d => d.y * 0.5)
                    .y(d => d.x));

            // Create nodes
            const nodes = svg.selectAll('.node')
                .data(root.descendants().slice(1)) // Skip the artificial root
                .enter()
                .append('g')
                .attr('class', 'node')
                .attr('transform', d => `translate(${d.y * 0.5}, ${d.x})`);

            // Add node circles
            nodes.append('circle')
                .attr('r', 5)
                .attr('fill', d => d.children ? '#5e6e82' : '#2c7be5');

            // Add node labels
            nodes.append('text')
                .attr('dy', '0.31em')
                .attr('x', d => d.children ? -10 : 10)
                .attr('text-anchor', d => d.children ? 'end' : 'start')
                .text(d => d.data.name)
                .attr('font-size', '12px')
                .attr('fill', '#5e6e82');
        }

        function renderSimpleTree(data) {
            // Simple HTML tree rendering (fallback if D3 not available)
            const container = document.getElementById('categoryTree');
            container.innerHTML = '';

            const renderNode = (node, container, level = 0) => {
                const item = document.createElement('div');
                item.className = `py-2 ${level > 0 ? 'ps-' + (level * 4) : ''}`;

                const content = document.createElement('div');
                content.className = 'd-flex align-items-center';

                // Add indentation and tree lines
                if (level > 0) {
                    const indent = document.createElement('span');
                    indent.className = 'me-2 text-700';
                    indent.textContent = level > 1 ? '└─ ' : '─ ';
                    content.appendChild(indent);
                }

                // Add category icon
                const icon = document.createElement('span');
                icon.className = node.children.length ? 'me-2 fas fa-folder text-warning' : 'me-2 fas fa-tag text-primary';
                content.appendChild(icon);

                // Add category name
                const name = document.createElement('span');
                name.textContent = node.name;
                name.className = 'fw-semi-bold';
                content.appendChild(name);

                // Add product count badge if available
                if (node.product_count !== undefined) {
                    const badge = document.createElement('span');
                    badge.className = 'ms-2 badge bg-primary rounded-pill fs--2';
                    badge.textContent = node.product_count;
                    content.appendChild(badge);
                }

                item.appendChild(content);
                container.appendChild(item);

                // Render children
                if (node.children && node.children.length > 0) {
                    node.children.forEach(child => {
                        renderNode(child, container, level + 1);
                    });
                }
            };

            data.forEach(node => {
                renderNode(node, container);
            });
        }
    </script>
</body>

</html>