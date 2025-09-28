<?php
// filepath: c:\wamp64\www\goodguy\admin\test_get_image.php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductImage.php';

// Simple check to ensure you're logged in as an admin
if (empty($_SESSION['admin_id'])) {
    die("<h2>Access Denied</h2><p>You must be logged in as an admin to use this test page. Please <a href='admin_login.php'>login here</a>.</p>");
}

$inventory_item_id = null;
$image_path = null;
$explanation = null;
$full_image_url = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inventory_item_id'])) {
    $inventory_item_id = (int) $_POST['inventory_item_id'];

    // Instantiate the class
    $productImage = new ProductImage($pdo);

    // Call the function to get the result
    $image_path = $productImage->get_image($inventory_item_id);

    // --- Logic to explain the result ---
    // 1. Check for the thumbnail in inventoryitem table
    $stmt_thumb = $pdo->prepare("SELECT thumbnail FROM inventoryitem WHERE InventoryItemID = ?");
    $stmt_thumb->execute([$inventory_item_id]);
    $thumbnail = $stmt_thumb->fetchColumn();

    if (!empty($thumbnail)) {
        $explanation = "<strong>Condition Met:</strong> Found a non-empty 'thumbnail' in the <code>inventoryitem</code> table.";
    } else {
        // 2. If no thumbnail, check for the primary image
        $stmt_primary = $pdo->prepare("SELECT image_path FROM inventory_item_image WHERE inventory_item_id = ? AND `is_primary` = 1");
        $stmt_primary->execute([$inventory_item_id]);
        $primaryImage = $stmt_primary->fetchColumn();

        if ($primaryImage) {
            $explanation = "<strong>Condition Met:</strong> The 'thumbnail' was empty, so the function fell back to the primary image in the <code>inventory_item_image</code> table.";
        } else {
            // 3. If neither is found, it's the default
            $explanation = "<strong>Condition Met:</strong> No 'thumbnail' was found in <code>inventoryitem</code> and no primary image was found in <code>inventory_item_image</code>. The function returned the default placeholder.";
        }
    }

    // Construct the full image URL for display
    // This assumes the path is relative to the project root. Adjust if necessary.
    if ($image_path !== 'e.jpg') {
        $full_image_url = $image_path;
    } else {
        $full_image_url = 'assets/img/products/default-product.png'; // A better default
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Test get_image() Function</title>
    <?php include 'admin-header.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <div class="container-fluid" data-layout="container">
            <?php include 'includes/admin_navbar.php'; ?>
            <div class="content">
                <div class="card">
                    <div class="card-header">
                        <h3>Test <code>get_image()</code> Function</h3>
                    </div>
                    <div class="card-body">
                        <p>This page tests the logic of the <code>get_image()</code> function in the
                            <code>ProductImage</code> class.
                        </p>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="inventory_item_id" class="form-label">Enter Inventory Item ID:</label>
                                <input type="number" class="form-control" id="inventory_item_id"
                                    name="inventory_item_id" required
                                    value="<?= htmlspecialchars($inventory_item_id ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">Test ID</button>
                        </form>

                        <?php if ($image_path !== null): ?>
                            <hr class="my-4">
                            <h4>Test Results for ID: <?= htmlspecialchars($inventory_item_id) ?></h4>
                            <div class="mt-3 p-3 border rounded bg-body-tertiary">
                                <p><strong>Returned Path:</strong> <code><?= htmlspecialchars($image_path) ?></code></p>
                                <p><?= $explanation ?></p>
                                <div>
                                    <strong>Image Preview:</strong><br>
                                    <img src="../<?= htmlspecialchars($full_image_url) ?>" alt="Image Preview"
                                        style="max-width: 200px; max-height: 200px; margin-top: 10px; border: 1px solid #ccc;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'includes/admin_footer.php'; ?>
</body>

</html>