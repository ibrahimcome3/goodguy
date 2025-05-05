<?php
// 1. Start Session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Includes and Class Requires
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';

// --- Function Definitions ---

// Function to fetch parent category name
function getParentCategoryName($mysqli, $parentId)
{
    if ($parentId == 0) {
        return 'Top-Level';
    }
    $parentSql = "SELECT name FROM categories WHERE category_id = ?";
    $parentStmt = $mysqli->prepare($parentSql);
    if (!$parentStmt) {
        // Handle prepare error, maybe log it
        return 'Error';
    }
    $parentStmt->bind_param("i", $parentId);
    $parentStmt->execute();
    $parentResult = $parentStmt->get_result();
    $parentRow = $parentResult->fetch_assoc();
    $parentStmt->close();
    return $parentRow ? $parentRow['name'] : 'Unknown';
}

//Fetch all categories for select options.
function getAllCategories($mysqli)
{
    $categories = [];
    $sql = "SELECT category_id, name, parent_id, level FROM categories ORDER BY name ASC"; // Order for better dropdown display
    $result = $mysqli->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $result->free();
    }
    return $categories;
}

function getCurrentUserId()
{
    // Replace this with your actual user authentication logic
    // This is a placeholder - you'll need to implement this based on your session handling
    if (isset($_SESSION['user_id'])) { // Make sure your login process sets 'user_id'
        return $_SESSION['user_id'];
    } else {
        // Handle appropriately - redirect to login or return a default/error value
        // For now, returning 0 might cause issues if owner_id is required.
        // Consider redirecting if not logged in:
        // header('Location: /login.php'); exit;
        return 0; // Or handle the case where the user is not logged in
    }
}

function addCategory($mysqli, $postData)
{
    $parentId = isset($postData['parent_id']) ? intval($postData['parent_id']) : 0;
    $name = trim($postData['name']); // Trim whitespace
    $ownerId = getCurrentUserId(); // Get the ID of the currently logged-in user

    if (empty($name)) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Category name cannot be empty.'];
        return;
    }

    //Determine the level dynamically
    $level = 0;
    if ($parentId > 0) {
        $parentLevelSql = "SELECT level FROM categories WHERE category_id = ?";
        $parentLevelStmt = $mysqli->prepare($parentLevelSql);
        $parentLevelStmt->bind_param("i", $parentId);
        $parentLevelStmt->execute();
        $parentLevelResult = $parentLevelStmt->get_result();
        if ($parentLevelRow = $parentLevelResult->fetch_assoc()) {
            $level = $parentLevelRow['level'] + 1;
        }
        $parentLevelStmt->close();

        if ($level > 2) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Maximum level exceeded (Level 2 is the maximum).'];
            return;
        }
    }

    $sql = "INSERT INTO categories (name, parent_id, level, owner_id) VALUES (?, ?, ?, ?)"; // Added owner_id
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("siii", $name, $parentId, $level, $ownerId);

    if ($stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Category added successfully!'];
    } else {
        // Provide more specific error if possible (e.g., duplicate name check)
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error adding category: ' . $stmt->error];
    }
    $stmt->close();
    // Redirect after POST to prevent re-submission
    header("Location: manage_categories.php");
    exit;
}

function editCategory($mysqli, $postData)
{
    // Ensure this function is only called via AJAX POST
    header('Content-Type: application/json'); // Set header for JSON response

    $categoryId = isset($postData['category_id']) ? intval($postData['category_id']) : 0;
    $newName = isset($postData['name']) ? trim($postData['name']) : '';
    $newParentId = isset($postData['parent_id']) ? intval($postData['parent_id']) : 0; // Get the new parent ID

    if (empty($newName) || $categoryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
        exit;
    }

    // --- Validation ---
    // 1. Prevent setting category as its own parent
    if ($categoryId === $newParentId) {
        echo json_encode(['success' => false, 'message' => 'A category cannot be its own parent.']);
        exit;
    }

    // 2. Calculate new level and check depth
    $newLevel = 0;
    if ($newParentId > 0) {
        $parentLevelSql = "SELECT level FROM categories WHERE category_id = ?";
        $parentLevelStmt = $mysqli->prepare($parentLevelSql);
        $parentLevelStmt->bind_param("i", $newParentId);
        $parentLevelStmt->execute();
        $parentLevelResult = $parentLevelStmt->get_result();
        if ($parentLevelRow = $parentLevelResult->fetch_assoc()) {
            $newLevel = $parentLevelRow['level'] + 1;
        } else {
            echo json_encode(['success' => false, 'message' => 'Selected parent category does not exist.']);
            exit; // Parent not found
        }
        $parentLevelStmt->close();

        if ($newLevel > 2) {
            echo json_encode(['success' => false, 'message' => 'Maximum category depth (Level 2) would be exceeded.']);
            exit;
        }

        // 3. Prevent making a category a child of one of its own descendants (circular reference)
        $tempParentId = $newParentId;
        while ($tempParentId > 0) {
            if ($tempParentId == $categoryId) {
                echo json_encode(['success' => false, 'message' => 'Cannot move category under one of its own children.']);
                exit;
            }
            // Fetch the next parent up the chain
            $nextParentSql = "SELECT parent_id FROM categories WHERE category_id = ?";
            $nextParentStmt = $mysqli->prepare($nextParentSql);
            $nextParentStmt->bind_param("i", $tempParentId);
            $nextParentStmt->execute();
            $nextParentResult = $nextParentStmt->get_result();
            $nextParentRow = $nextParentResult->fetch_assoc();
            $nextParentStmt->close();
            $tempParentId = $nextParentRow ? $nextParentRow['parent_id'] : 0;
        }
    }

    // --- Update Database ---
    $sql = "UPDATE categories SET name = ?, parent_id = ?, level = ? WHERE category_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error preparing statement: ' . $mysqli->error]);
        exit;
    }
    $stmt->bind_param("siii", $newName, $newParentId, $newLevel, $categoryId);

    if ($stmt->execute()) {
        $newParentName = getParentCategoryName($mysqli, $newParentId); // Get the name for the response
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully!',
            'newName' => $newName,
            'newParentId' => $newParentId,
            'newParentName' => $newParentName,
            'newLevel' => $newLevel
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating category: ' . $stmt->error]);
    }
    $stmt->close();
    exit; // Important: Stop script execution after AJAX response
}

function deleteCategory($mysqli, $postData)
{
    // Ensure this function is only called via AJAX POST
    header('Content-Type: application/json');

    $categoryId = isset($postData['category_id']) ? intval($postData['category_id']) : 0;

    if ($categoryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid category ID.']);
        exit;
    }

    // Check for child categories first
    $sqlCheck = "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?";
    $stmtCheck = $mysqli->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $categoryId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();
    $stmtCheck->close();

    if ($rowCheck['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete category: It has child categories. Please delete or move them first.']);
        exit;
    }

    // Proceed with deletion
    $sqlDelete = "DELETE FROM categories WHERE category_id = ?";
    $stmtDelete = $mysqli->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $categoryId);

    if ($stmtDelete->execute()) {
        if ($stmtDelete->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found or already deleted.']);
        }
    } else {
        // Consider checking for foreign key constraints if products are linked
        echo json_encode(['success' => false, 'message' => 'Error deleting category: ' . $stmtDelete->error]);
    }
    $stmtDelete->close();
    exit; // Important: Stop script execution after AJAX response
}

// --- Handle POST Requests (Add, Edit, Delete) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add_category') {
        addCategory($mysqli, $_POST); // This function now handles redirect/exit
    } elseif ($action == 'edit_category') {
        // Assumes edit is always AJAX. The function handles JSON response and exit.
        editCategory($mysqli, $_POST);
    } elseif ($action == 'delete_category') {
        // Assumes delete is always AJAX. The function handles JSON response and exit.
        deleteCategory($mysqli, $_POST);
    }
    // If it was a non-AJAX POST action that didn't exit/redirect, script continues.
    // But addCategory now redirects, and edit/delete exit after JSON.
}

// --- Prepare Data for Page Display ---
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$resultsPerPage = 20; // Number of categories to display per page
$offset = ($currentPage - 1) * $resultsPerPage;
$filter = isset($_GET['filter']) ? $_GET['filter'] : ''; // Get filter term

// Build the SQL query dynamically based on filter
$sql = "SELECT * FROM categories";
$countSql = "SELECT COUNT(*) AS total FROM categories";
$whereClause = "";
$params = [];
$types = "";

if (!empty($filter)) {
    $whereClause = " WHERE name LIKE ?";
    $params[] = "%" . $filter . "%";
    $types .= "s";
}

$sql .= $whereClause . " ORDER BY category_id LIMIT ? OFFSET ?";
$countSql .= $whereClause;
$params[] = $resultsPerPage;
$params[] = $offset;
$types .= "ii";

// Count the total number of categories (for pagination)
$totalCategoriesStmt = $mysqli->prepare($countSql);

if (!empty($filter)) {
    $totalCategoriesStmt->bind_param(substr($types, 0, strlen($types) - 2), ...array_slice($params, 0, count($params) - 2)); // Use only the params for the WHERE clause
}

$totalCategoriesStmt->execute();
$totalCategoriesResult = $totalCategoriesStmt->get_result();
$totalCategoriesRow = $totalCategoriesResult->fetch_assoc();
$totalCategories = $totalCategoriesRow['total'];

// Calculate the number of pages
$totalPages = ceil($totalCategories / $resultsPerPage);

// Fetch categories with pagination and filtering
$stmt = $mysqli->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$allCategories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close(); // Close statement after fetching
$totalCategoriesStmt->close();

$categoriesForDropdown = getAllCategories($mysqli); // Fetch categories for dropdowns

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_category') {
            addCategory($mysqli, $_POST);
        } elseif ($_POST['action'] == 'edit_category') {
            editCategory($mysqli, $_POST);
        } elseif ($_POST['action'] == 'delete_category') {
            deleteCategory($mysqli, $_POST);
        }
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Add some styling to highlight matched text */
        .highlight {
            background-color: yellow;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1>Manage Categories</h1>
        <?php include('../includes/messages.php'); // Include message display ?>
        <form method="post" action="manage_categories.php">
            <div class="mb-3">
                <label for="parent_id" class="form-label">Parent Category:</label>
                <select class="form-select" name="parent_id" id="parent_id" required>
                    <option value="0">Top-Level Category</option>
                    <?php foreach ($categoriesForDropdown as $category): ?>
                        <option value="<?php echo $category['category_id'] ?>">
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Category Name:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <!-- Level is now determined automatically based on parent -->
            <!-- <div class="mb-3">
                <label for="level" class="form-label">Level:</label>
                <input type="number" class="form-control" id="level" name="level" min="0" max="2" value="1" required>
            </div> -->

            <input type="hidden" name="action" value="add_category">
            <button type="submit" class="btn btn-primary">Add Category</button>
        </form>

    </div>

    <div class="container mt-5">
        <h4>Category List</h4>
        <!-- Add filter input -->
        <div class="mb-3">
            <form method="get" action="manage_categories.php">
                <label for="categoryFilter" class="form-label">Filter Categories:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="categoryFilter" name="filter"
                        placeholder="Enter category name to filter" value="<?= htmlspecialchars($filter) ?>">
                    <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Filter</button>
                </div>
            </form>
        </div>

        <table class="table table-bordered" id="categoryTable">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Parent Category</th>
                    <th>Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <?php foreach ($allCategories as $category): ?>
                    <tr id="category-row-<?= $category['category_id'] ?>">
                        <td><?= $category['name'] ?></td>
                        <td>
                            <?php echo getParentCategoryName($mysqli, $category['parent_id']); ?>
                        </td>
                        <td><?= $category['level'] ?></td>
                        <td>
                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#editCategoryModal" data-category-id="<?= $category['category_id'] ?>"
                                data-category-name="<?= htmlspecialchars($category['name']) ?>"
                                data-parent-id="<?= $category['parent_id'] ?>"
                                data-level="<?= $category['level'] ?>">Edit</button>
                            <button type="button" class="btn btn-danger btn-sm delete-category-button"
                                data-category-id="<?= $category['category_id'] ?>">Delete</button>

                            <form method="post" action="manage_categories.php" style="display: inline;">
                                <input type="hidden" name="parent_id" value="<?= $category['category_id'] ?>">
                                <input type="hidden" name="action" value="add_category">
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#addSubCategoryModal"
                                    onclick="setAddSubCategoryModal(<?= $category['category_id'] ?>,<?= $category['level'] ?>)">Add
                                    Subcategory</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $queryParams = $_GET;
                    $queryParams['page'] = $i;
                    $queryString = http_build_query($queryParams);
                    ?>
                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= $queryString ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <!-- Modal for adding a subcategory -->
        <div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-labelledby="addSubCategoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSubCategoryModalLabel">Add Subcategory</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addSubCategoryForm" method="post" action="manage_categories.php">
                            <input type="hidden" id="parentId" name="parent_id" value="">
                            <input type="hidden" name="action" value="add_category">
                            <div class="mb-3">
                                <label for="subCategoryName" class="form-label">Subcategory Name:</label>
                                <input type="text" class="form-control" id="subCategoryName" name="name" required>
                            </div>
                            <input type="hidden" name="level" id="level" value="">
                            <button type="submit" class="btn btn-primary">Add Subcategory</button>
                        </form>
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
                        <form id="editCategoryForm" method="post" action="manage_categories.php">
                            <input type="hidden" id="editCategoryId" name="category_id" value="">
                            <input type="hidden" name="action" value="edit_category">
                            <div class="mb-3">
                                <label for="editCategoryName" class="form-label">Category Name:</label>
                                <input type="text" class="form-control" id="editCategoryName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="editParentId" class="form-label">Parent Category:</label>
                                <select class="form-select" name="parent_id" id="editParentId" required>
                                    <option value="0">-- Top-Level Category --</option>
                                    <?php foreach ($categoriesForDropdown as $catOption): ?>
                                        <option value="<?php echo $catOption['category_id']; ?>">
                                            <?php echo htmlspecialchars($catOption['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Level is determined by server based on parent -->
                            <button type="submit" class="btn btn-primary" id="saveChangesBtn">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <script>
            function setAddSubCategoryModal(parentId, level) {
                document.getElementById('parentId').value = parentId;
                document.getElementById('subCategoryName').value = '';
                document.getElementById('level').value = level + 1;
            }



            $(document).ready(function () {

                $('#editCategoryModal').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var categoryId = button.data('category-id');
                    var categoryName = button.data('category-name');
                    var parentId = button.data('parent-id');
                    // var level = button.data('level'); // Level is now derived

                    var modal = $(this);
                    modal.find('#editCategoryId').val(categoryId);
                    modal.find('#editCategoryName').val(categoryName);
                    modal.find('#editParentId').val(parentId);

                    // Disable the category itself in the parent dropdown
                    modal.find('#editParentId option').prop('disabled', false); // Re-enable all first
                    modal.find('#editParentId option[value="' + categoryId + '"]').prop('disabled', true);
                });

                $("#editCategoryForm").submit(function (event) {
                    event.preventDefault(); // Prevent the default form submission

                    // Get the category ID and the new name
                    var categoryId = $("#editCategoryId").val();
                    var newName = $("#editCategoryName").val();
                    var newParentId = $("#editParentId").val(); // Get selected parent ID

                    // Send the AJAX request
                    $.ajax({
                        url: "manage_categories.php", // Submit to the same page
                        type: "POST",
                        data: {
                            action: "edit_category",
                            category_id: categoryId,
                            parent_id: newParentId, // Send parent ID
                            name: newName
                        },
                        dataType: "json", // Expect JSON response
                        success: function (response) {
                            if (response.success) {
                                // Update the table row with the new name
                                $("#category-row-" + categoryId + " td:first").text(newName);
                                // Update parent name and level
                                $("#category-row-" + categoryId + " td:nth-child(2)").text(response.newParentName);
                                $("#category-row-" + categoryId + " td:nth-child(3)").text(response.newLevel);

                                // Display a success message
                                alert(response.message);
                                // Close the modal
                                $("#editCategoryModal").modal("hide");
                            } else {
                                // Display an error message
                                alert(response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            alert("Error updating category: " + error);
                        }
                    });
                });
                // Function to highlight matched text
                function highlightText(text, searchTerm) {
                    if (!searchTerm) return text;
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    return text.replace(regex, '<span class="highlight">$1</span>');
                }
                // Remove filter on load
                $("#categoryFilter").on("input", function () {
                    if ($(this).val().trim() === '') {
                        window.location.href = 'manage_categories.php';
                    }

                })
                $("#categoryFilter").on("keyup", function () {
                    var value = $(this).val().toLowerCase();
                    $("#categoryTableBody tr").filter(function () {
                        const categoryName = $(this).find("td:first").text().toLowerCase();
                        const parentCategory = $(this).find("td:nth-child(2)").text().toLowerCase();

                        const isVisible = categoryName.indexOf(value) > -1 || parentCategory.indexOf(value) > -1;
                        $(this).toggle(isVisible);

                        // Highlight matching text
                        if (isVisible) {
                            const newCategoryName = highlightText($(this).find("td:first").text(), value);
                            $(this).find("td:first").html(newCategoryName);
                            const newParentCategory = highlightText($(this).find("td:nth-child(2)").text(), value);
                            $(this).find("td:nth-child(2)").html(newParentCategory);
                        }
                    });
                    // Remove highlight if the filter is empty
                    if (!value) {
                        $("#categoryTableBody tr").find("td:first, td:nth-child(2)").each(function () {
                            $(this).find('.highlight').contents().unwrap();
                        });
                    }
                });

                $(".delete-category-button").click(function (event) {
                    event.preventDefault(); // Prevent the default form submission

                    // Get the category ID
                    var categoryId = $(this).data('category-id');

                    // Send the AJAX request
                    if (confirm("Are you sure you want to delete this category?")) {
                        $.ajax({
                            url: "manage_categories.php", // Submit to the same page
                            type: "POST",
                            data: {
                                action: "delete_category",
                                category_id: categoryId
                            },
                            dataType: "json",
                            success: function (response) {
                                if (response.success) {
                                    // Remove the table row
                                    $("#category-row-" + categoryId).remove();
                                    // Display a success message
                                    alert(response.message);
                                } else {
                                    // Display an error message
                                    alert(response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                alert("Error deleting category: " + error);
                            }
                        });
                    }
                });


            });
        </script>

    </div>

    <!-- Bootstrap JS (required for the modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>

</html>

<?php $mysqli->close(); // Close the database connection at the end ?>