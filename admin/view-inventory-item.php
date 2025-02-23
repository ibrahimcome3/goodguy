<?php
include "../conn.php"; // Your database connection

// SQL query to fetch inventory items and images
$sql = "SELECT 
            i.inventoryitemID, 
            i.description, 
            i.sku, 
            i.cost, 
            i.tax, 
            i.quantityOnHand, 
            i.barcode,
            ii.image_path 
        FROM 
            inventoryitem i 
        LEFT JOIN 
            inventory_item_image ii ON i.inventoryitemID = ii.inventory_item_id";


$result = $mysqli->query($sql);

if (!$result) {
    die("Query failed: " . $mysqli->error);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>View Inventory Items</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .inventory-item-image {
            max-width: 100px;
            /* Adjust as needed */
            max-height: 100px;
            /* Adjust as needed */
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Inventory Items</h1>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Description</th>
                    <th>SKU</th>
                    <th>Cost</th>
                    <th>Tax</th>
                    <th>Quantity</th>
                    <th>Barcode</th>
                    <th>Images</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><a href="edit_inventory_item.php?id=<?= $row['inventoryitemID'] ?>"><?= $row['inventoryitemID'] ?>
                            </a>
                        </td>
                        <td><a href="edit_inventory_item.php?id=<?= $row['inventoryitemID'] ?>"><?= $row['description'] ?>
                        </td> </a>
                        <td><?= $row['sku'] ?></td>
                        <td><?= $row['cost'] ?></td>
                        <td><?= $row['tax'] ?></td>
                        <td><?= $row['quantityOnHand'] ?></td>
                        <td><?= $row['barcode'] ?></td>
                        <td>
                            <?php
                            // Fetch only images associated with this specific inventory item ID
                            $itemId = $row['inventoryitemID'];
                            $imageSql = "SELECT image_path FROM inventory_item_image WHERE inventory_item_id = '$itemId'";
                            $imageResult = $mysqli->query($imageSql);

                            if ($imageResult) {
                                while ($imageRow = $imageResult->fetch_assoc()):
                                    $imagePath = "../" . $imageRow['image_path']; ?>
                                    <img src="<?= $imagePath ?>" alt="Inventory Item Image" class="inventory-item-image">
                                <?php endwhile;
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>