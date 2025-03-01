<?php
include "../conn.php";

if (!isset($_GET['inventory_item_id'])) {
    die("Inventory item ID not provided.");
}

$inventoryItemId = $_GET['inventory_item_id'];

// Fetch SKU and product ID from the database
$sql = "SELECT sku, productItemID FROM inventoryitem WHERE inventoryitemID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $inventoryItemId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("Inventory item not found.");
}

$skuJson = $row['sku'];
$productId = $row['productItemID'];
$skuArray = json_decode($skuJson, true);

if ($skuArray === null && json_last_error() !== JSON_ERROR_NONE) {
    echo "Error decoding JSON: " . json_last_error_msg();
    exit;
}

// Ensure color and size are present in the array
if (!isset($skuArray['color'])) {
    $skuArray['color'] = '';
}
if (!isset($skuArray['size'])) {
    $skuArray['size'] = '';
}

// Get other inventory items for the same product with image
$otherInventoryItemsSql = "SELECT i.inventoryitemID, i.sku, ii.image_path 
                           FROM inventoryitem i
                           LEFT JOIN inventory_item_image ii ON i.inventoryitemID = ii.inventory_item_id
                           WHERE i.productItemID = ? AND i.inventoryitemID != ?";
$otherInventoryItemsStmt = $mysqli->prepare($otherInventoryItemsSql);
$otherInventoryItemsStmt->bind_param("ii", $productId, $inventoryItemId);
$otherInventoryItemsStmt->execute();
$otherInventoryItemsResult = $otherInventoryItemsStmt->get_result();
$otherInventoryItemDetails = [];
while ($otherRow = $otherInventoryItemsResult->fetch_assoc()) {
    $otherInventoryItemDetails[$otherRow['inventoryitemID']] = [
        'sku' => json_decode($otherRow['sku'], true),
        'image_path' => $otherRow['image_path']
    ];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $updatedSku = [];

    // Handle color and size separately to make them mandatory and non-deletable
    if (isset($_POST['properties']['color'])) {
        $updatedSku['color'] = $_POST['properties']['color'];
    }
    if (isset($_POST['properties']['size'])) {
        $updatedSku['size'] = $_POST['properties']['size'];
    }
    // Handle other properties, excluding color and size
    if (isset($_POST['properties'])) {
        foreach ($_POST['properties'] as $key => $value) {
            if ($key !== 'color' && $key !== 'size') {
                if (!empty($value)) {
                    $updatedSku[$key] = $value;
                }
            }
        }
    }

    //Delete logic
    if (isset($_POST['delete_properties'])) {
        $propertiesToDelete = $_POST['delete_properties'];
        foreach ($propertiesToDelete as $propertyToDelete) {
            // Prevent deleting color and size
            if ($propertyToDelete !== 'color' && $propertyToDelete !== 'size') {
                unset($updatedSku[$propertyToDelete]);

                // Delete from others logic
                if (isset($_POST['delete_from_others']) && in_array($propertyToDelete, $_POST['delete_from_others'])) {
                    foreach ($otherInventoryItemDetails as $otherItemId => $otherDetails) {
                        $otherSku = $otherDetails['sku'];
                        if (isset($otherSku[$propertyToDelete])) {
                            unset($otherSku[$propertyToDelete]);
                            $otherSkuJson = json_encode($otherSku);
                            $updateOtherSql = "UPDATE inventoryitem SET sku = ? WHERE inventoryitemID = ?";
                            $updateOtherStmt = $mysqli->prepare($updateOtherSql);
                            $updateOtherStmt->bind_param("si", $otherSkuJson, $otherItemId);
                            $updateOtherStmt->execute();
                        }
                    }
                }
            }
        }
    }
    if (isset($_POST['new_property_key']) && isset($_POST['new_property_value'])) {
        $newKey = trim($_POST['new_property_key']);
        $newValue = $_POST['new_property_value'];
        // Prevent adding color and size through new key
        if (!empty($newKey) && !isset($updatedSku[$newKey]) && $newKey !== 'color' && $newKey !== 'size') { //Check for empty key and duplicates
            $updatedSku[$newKey] = $newValue;
        }
    }
    // Share property logic
    if (isset($_POST['share_property']) && isset($_POST['share_property_value']) && isset($_POST['share_with_items'])) {
        $propertyToShare = trim($_POST['share_property']);
        $propertyValue = $_POST['share_property_value'];
        $itemsToShareWith = $_POST['share_with_items'];

        //prevent to share color or size.
        if (!empty($propertyToShare) && $propertyToShare !== 'color' && $propertyToShare !== 'size') {
            $sharedSku = json_encode([$propertyToShare => $propertyValue]);
            foreach ($itemsToShareWith as $itemToShareWith) {
                $shareSql = "UPDATE inventoryitem SET sku = JSON_MERGE_PATCH(sku, ?) WHERE inventoryitemID = ?";
                $shareStmt = $mysqli->prepare($shareSql);
                $shareStmt->bind_param("si", $sharedSku, $itemToShareWith);
                $shareStmt->execute();
            }
        }
    }

    $updatedSkuJson = json_encode($updatedSku);

    // Update the database for the current item
    $updateSql = "UPDATE inventoryitem SET sku = ? WHERE inventoryitemID = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param("si", $updatedSkuJson, $inventoryItemId);
    $updateStmt->execute();

    header("Location: manage-sku.php?inventory_item_id=" . $inventoryItemId);
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage SKU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .inventory-item-image {
            max-width: 100px;
            max-height: 100px;
        }
    </style>

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
                            <td><input type="<?php echo ($key == 'color') ? 'color' : 'text'; ?>"
                                    name="properties[<?= $key ?>]" value="<?= $value ?>"
                                    <?php echo ($key == 'color' || $key == 'size') ? 'required' : ''; ?>>
                            </td>
                            <td>
                                <?php if ($key !== 'color' && $key !== 'size'): ?>
                                    <input type="checkbox" name="delete_properties[]" value="<?= $key ?>"> Delete
                                    <input type="checkbox" name="delete_from_others[]" value="<?= $key ?>"> Delete from
                                    others
                                <?php endif; ?>
                            </td>
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

            <h3 class="mt-4">Share Property with Other Items</h3>
            <div class="mb-3">
                <label for="share_property">Property to Share:</label>
                <select class="form-control" id="share_property" name="share_property">
                    <option value="">Select a property</option>
                    <?php foreach ($skuArray as $key => $value): ?>
                        <?php if ($key !== 'color' && $key !== 'size'): ?>
                            <option value="<?= $key ?>" data-value="<?= $value ?>"><?= $key ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="share_property_value">Value to Share:</label>
                <input type="text" class="form-control" id="share_property_value" name="share_property_value">
            </div>
            <div class="mb-3">
                <p>Share with these items:</p>
                <?php if (!empty($otherInventoryItemDetails)): ?>
                    <div class="row">
                        <?php foreach ($otherInventoryItemDetails as $otherItemId => $otherDetails): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card">
                                    <?php
                                    $imagePath = $otherDetails['image_path'] ? "../products/product-{$productId}/inventory-{$productId}-{$otherItemId}/resized/{$otherDetails['image_path']}" : 'logo.svg'; // Replace with your default image path
                                    echo "<img src='{$imagePath}' alt='Inventory Item Image' class='card-img-top inventory-item-image'>";
                                    ?>
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="share_with_items[]"
                                                id="share_with_<?= $otherItemId ?>" value="<?= $otherItemId ?>">
                                            <label class="form-check-label" for="share_with_<?= $otherItemId ?>">
                                                Item ID: <?= $otherItemId ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No other inventory items found for this product.</p>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-secondary">Share Property</button>
        </form>
    </div>
    <script>
        $(document).ready(function () {
            $('#share_property').change(function () {
                const selectedValue = $(this).val();
                const valueToShare = $(this).find('option:selected').data('value');
                $('#share_property_value').val(valueToShare);
            });
        });
    </script>
</body>

</html>
