<?php
include "../conn.php";

if (!isset($_GET['inventory_item_id'])) {
    die("Inventory item ID not provided.");
}

$inventoryItemId = $_GET['inventory_item_id'];

// Fetch SKU from the database
$sql = "SELECT sku FROM inventoryitem WHERE inventoryitemID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $inventoryItemId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$skuJson = $row['sku'];
$skuArray = json_decode($skuJson, true);

if ($skuArray === null && json_last_error() !== JSON_ERROR_NONE) {
    echo "Error decoding JSON: " . json_last_error_msg();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $updatedSku = $skuArray;
    if (isset($_POST['delete_properties'])) {
        foreach ($_POST['delete_properties'] as $propertyToDelete) {
            unset($updatedSku[$propertyToDelete]);
        }
    }
    if (isset($_POST['new_property_key']) && isset($_POST['new_property_value'])) {
        $newKey = $_POST['new_property_key'];
        $newValue = $_POST['new_property_value'];
        $updatedSku[$newKey] = $newValue;
    }
    $updatedSkuJson = json_encode($updatedSku);


    //Update the database
    $updateSql = "UPDATE inventoryitem SET sku = ? WHERE inventoryitemID = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param("si", $updatedSkuJson, $inventoryItemId);
    $updateStmt->execute();


    header("Location: manage_sku.php?inventory_item_id=" . $inventoryItemId);
    exit();


}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage SKU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Manage SKU for Inventory Item ID: <?= $inventoryItemId ?></h2>
        <form method="post" action="manage-sku.php?inventory_item_id=<?= $inventoryItemId ?>">
            <table class="table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Value</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skuArray as $key => $value): ?>
                        <tr>
                            <td><?= $key ?></td>
                            <td><input type="text" name="properties[<?= $key ?>]" value="<?= $value ?>"></td>
                            <td><input type="checkbox" name="delete_properties[]" value="<?= $key ?>"> Delete</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Add New Property</h3>
            <div class="mb-3">
                <label for="new_property_key">Property Key:</label>
                <input type="text" class="form-control" id="new_property_key" name="new_property_key">
            </div>
            <div class="mb-3">
                <label for="new_property_value">Property Value:</label>
                <input type="text" class="form-control" id="new_property_value" name="new_property_value">
            </div>
            <button type="submit" class="btn btn-primary">Update SKU</button>
        </form>
    </div>
</body>

</html>