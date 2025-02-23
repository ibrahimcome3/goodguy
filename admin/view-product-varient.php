<?php
include "../conn.php"; // Your database connection

// Get the product ID from the URL parameter
if (!isset($_GET['product_id'])) {
    die("Product ID not provided.");
}
$product_id = $_GET['product_id'];

// SQL query to fetch inventory items for the specific product
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
            inventory_item_image ii ON i.inventoryitemID = ii.inventory_item_id
        WHERE i.productItemID = ?"; // Use a parameterized query for security

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt->bind_param("i", $product_id); // Bind the product ID
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $stmt->error);
}

// Get the product name (you'll need a products table and query)
//For example,  assuming a 'products' table with a 'productName' column:
$productNameSql = "SELECT productID,product_name FROM productitem WHERE productID = ?";
$productNameStmt = $mysqli->prepare($productNameSql);
$productNameStmt->bind_param("i", $product_id);
$productNameStmt->execute();
$productNameResult = $productNameStmt->get_result();
$productName = $productNameResult->fetch_assoc()['product_name'];


?>

<!DOCTYPE html>
<html>

<head>
    <title>Inventory for Product: <?= $productName ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .inventory-item-image {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Inventory for Product: <?= $productName ?> (ID: <?= $product_id ?>)</h1>
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
                        <td><?= $row['inventoryitemID'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td><?= $row['sku'] ?></td>
                        <td><?= $row['cost'] ?></td>
                        <td><?= $row['tax'] ?></td>
                        <td><?= $row['quantityOnHand'] ?></td>
                        <td><?= $row['barcode'] ?></td>
                        <td>
                            <?php
                            $imagePath = "../products/product-{$product_id}/inventory-{$product_id}-{$row['inventoryitemID']}/resized/{$row['image_path']}";
                            if (!empty($imagePath)) {
                                echo "<img src='{$imagePath}' alt='Inventory Item Image' class='inventory-item-image'>";
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