<!DOCTYPE html>
<?php
session_start();
include "../conn.php";
require_once '../class/Conn.php'; // Include necessary classes here
require_once '../class/InventoryItem.php';
require_once '../class/ProductItem.php';

// Check if product_id is provided
if (!isset($_GET['product_id'])) {
    echo "Product ID not provided. <a href='product.php'>Go back to Products</a>";
    exit();
}
$product_id = $_GET['product_id'];
?>
<html>

<head>
    <title>Add Product Variants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include other necessary CSS/JS -->
</head>

<body>
    <div class="container mt-5">
        <h3>Add Variants for Product ID: <?= $product_id ?></h3>
        <form action="inventory_item_processor.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">

            <div id="variants-container">
                <div class="variant-group"> </div>
            </div>
            <div class="variant-group border p-3 mb-3">
                <div class="mb-3">
                    <label for="color_1">Color:</label>
                    <input type="color" class="form-control form-control-color" id="color" name="color">
                </div>

                <div class="mb-3">
                    <label for="size_1">Size:</label>
                    <input type="text" class="form-control" id="size_1" name="size">
                </div>
                <div class="mb-3">
                    <label for="size_1">Description:</label>
                    <textarea type="text" class="form-control" id="description" name="description"></textarea>
                </div>

                <div class="mb-3">
                    <label for="tax">Tax (%):</label>
                    <input type="number" step="0.01" class="form-control" id="tax" name="tax" required="">
                </div>
                <div class="mb-3">
                    <label for="price">Price:</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required="">
                </div>

                <div class="mb-3">
                    <label for="bcode">Bar Code:</label>
                    <input type="text" class="form-control" id="bcode" name="bcode" required="">
                </div>
                <div class="mb-3">
                    <label for="quantity_1">Quantity:</label>
                    <input type="number" class="form-control" id="quantity_1" name="quantity" required="">
                </div>


                <div class="mb-3">
                    <label for="image_1">Image:</label>
                    <input type="file" class="form-control" id="image_1" name="image[]"> <img style="width:300px;"
                        src="logo.svg" id="preview_1" alt="Preview">

                </div>

                <hr>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save Variants</button>
        </form>
    </div>





</body>

</html>