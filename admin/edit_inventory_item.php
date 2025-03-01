<?php

session_start(); // Start the session at the beginning

// Check if the user is logged in
if (!isset($_SESSION['uid'])) {
    // Redirect to login page or display an error
    header("Location: ../login.php"); // Adjust the path if needed
    exit;
}
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php';

$p = new ProductItem();

// Get inventory item ID from the URL parameter
if (!isset($_GET['id'])) {
    die("Inventory item ID not provided.");
}
$inventoryItemId = $_GET['id'];

// Fetch inventory item details (using a parameterized query for security)
$sql = "SELECT * FROM inventoryitem WHERE inventoryitemID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $inventoryItemId);
$stmt->execute();
$result = $stmt->get_result();
$inventoryItem = $result->fetch_assoc();
if (!$inventoryItem) {
    die("Inventory item not found.");
}

// Fetch image paths for this inventory item
$imageSql = "SELECT image_path FROM inventory_item_image WHERE inventory_item_id = ?";
$imageStmt = $mysqli->prepare($imageSql);
$imageStmt->bind_param("i", $inventoryItemId);
$imageStmt->execute();
$imageResult = $imageStmt->get_result();
$imagePaths = [];
while ($imageRow = $imageResult->fetch_assoc()) {
    $imagePaths[] = $imageRow['image_path'];
}

// Convert JSON SKU to comma-separated values
$skuArray = json_decode($inventoryItem['sku'], true);
$skuDisplay = "";
if (is_array($skuArray)) {
    $skuParts = [];
    foreach ($skuArray as $key => $value) {
        $skuParts[] = "$key: $value";
    }
    $skuDisplay = implode(", ", $skuParts);
} else {
    $skuDisplay = "Invalid SKU format";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Inventory Item</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        window.onpageshow = function (event) {
            if (event.persisted) {
                window.location.reload(); // Force a reload from the server
            }
        };
    </script>

</head>

<body>
    <div class="container mt-5">
        <h2>Edit Inventory Item (ID: <?= $inventoryItemId ?>)</h2>
        <form method="post" action="update_inventory_item.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $inventoryItemId ?>">
            <div class="mb-3">
                <label for="product_id" class="form-label">Product ID:</label>
                <input type="number" class="form-control" id="product_id" name="product_id"
                    value="<?= $inventoryItem['productItemID'] ?>" readonly>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control" id="description"
                    name="description"><?= $inventoryItem['description'] ?></textarea>
            </div>

            <div class="mb-3">
                <label for="sku" class="form-label">Product Property:</label>
                <input type="hidden" name="sku" value='<?= $inventoryItem['sku'] ?>'>
                <input type="text" class="form-control" value='<?= $skuDisplay ?>' readonly>
            </div>
            <a href="manage-sku.php?inventory_item_id=<?= $inventoryItemId ?>">add and remove properties</a>



            <div class="mb-3">
                <label for="cost" class="form-label">Cost:</label>
                <input type="number" step="0.01" class="form-control" id="cost" name="cost"
                    value="<?= $inventoryItem['cost'] ?>">
            </div>

            <div class="mb-3">
                <label for="tax" class="form-label">Tax (%):</label>
                <input type="number" step="0.01" class="form-control" id="tax" name="tax"
                    value="<?= $inventoryItem['tax'] ?>">
            </div>

            <div class="mb-3">
                <label for="quantityOnHand" class="form-label">Quantity:</label>
                <input type="number" class="form-control" id="quantityOnHand" name="quantityOnHand"
                    value="<?= $inventoryItem['quantityOnHand'] ?>">
            </div>

            <div class="mb-3">
                <label for="barcode" class="form-label">Barcode:</label>
                <input type="text" class="form-control" id="barcode" name="barcode"
                    value="<?= $inventoryItem['barcode'] ?>">
            </div>

            <div class="mb-3">
                <label for="new_image" class="form-label">New Image:</label>
                <br>
                <input type="file" class="form-control" id="image_1" name="image[]">
            </div>

            <div class="mb-3">
                <label for="image" class="form-label">Images:</label>
                <?php foreach ($imagePaths as $imagePath): ?>
                    <div>
                        <img src="../products/product-<?= $inventoryItem['productItemID'] ?>/inventory-<?= $inventoryItem['productItemID'] ?>-<?= $inventoryItemId ?>/resized/<?= $imagePath ?>"
                            alt="Inventory Item Image" class="inventory-item-image" style="max-width:100px;">
                        <input type="checkbox" name="images_to_delete[]" value="<?= $imagePath ?>"> Delete
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="submit">Update</button>
        </form>
    </div>
</body>

</html>