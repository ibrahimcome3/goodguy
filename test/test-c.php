<?php
session_start(); // Start the session at the very beginning
$_SESSION['uid'] = 41;
// Check if the user is logged in and $_SESSION['uid'] is set
if (!isset($_SESSION['uid'])) {
    // Redirect to login page or display an error message
    echo "You must be logged in to view this page.";
    exit; // Stop further execution
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
</head>

<body>
    <?php
    // Database connection (replace with your credentials)
    $mysqli = new mysqli("localhost", "root", "", "lm_test");

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Get the current user's ID from the session
    $currentUserId = $_SESSION['uid'];

    // Fetch categories owned by owner_id = 0 OR the current user
    $sql = "SELECT category_id, name, owner_id FROM categories WHERE owner_id = 0 OR owner_id = ?";
    $stmt = $mysqli->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("i", $currentUserId);
    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }

    $stmt->close();
    $mysqli->close();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['choices']) && is_array($_POST['choices'])) {
            echo "<h3>Selected Categories:</h3>";
            echo "<ul>";
            foreach ($_POST['choices'] as $categoryId) {
                // Find the category name from the fetched categories
                $categoryName = "Unknown Category";
                foreach ($categories as $category) {
                    if ($category['category_id'] == $categoryId) {
                        $categoryName = $category['name'];
                        break;
                    }
                }
                echo "<li>" . htmlspecialchars($categoryName) . " (ID: " . htmlspecialchars($categoryId) . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No categories selected.</p>";
        }
    }

    ?>
    <form action="" method="post">
        <label for="categories">Choose Categories:</label>
        <select name="choices[]" id="categories" multiple>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['category_id'] ?>">
                    <?= htmlspecialchars($category['name']) ?>
                    <?php if ($category['owner_id'] == 0): ?>
                        (Owner: System)
                    <?php elseif ($category['owner_id'] == $currentUserId): ?>
                        (Owner: You)
                    <?php endif; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="mb-3">

            <input class="form-check-input" type="checkbox" name="filter_by_user" id="filter_by_user">
            <label class="form-check-label" for="filter_by_user">
                My Categories
            </label>

            <input class="form-check-input" type="checkbox" name="filter_by_system" id="filter_by_system">
            <label class="form-check-label" for="filter_by_system">
                System Categories
            </label>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/scripts/choices.min.js"></script>
        <script>
            const choices = new Choices('#categories', {
                removeItemButton: true,
                searchEnabled: true,
                shouldSort: true,
                maxItemCount: null, // Allow multiple selections
            });
        </script>
        <button type="submit">Submit</button>
    </form>
</body>

</html>