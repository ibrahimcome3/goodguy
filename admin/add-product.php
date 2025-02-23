<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery is required -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>

    <h1>Add New Product</h1>

    <form method="post" action="product_adder.php" enctype="multipart/form-data">
        <label for="product_name">Product Name:</label><br>
        <input type="text" id="product_name" name="product_name" required><br><br>
        <label for="description">description:</label><br>
        <input type="text" id="description" name="description" required /><br>
        <label for="category">Category:</label><br>
        <select name="category" id="category" class="form-control">
            <?php
            $sql = "SELECT * FROM `category_new` ORDER BY `cat_id` ASC";
            $result = $mysqli->query($sql);
            while ($row = mysqli_fetch_array($result)) {
                ?>

                <option value="<?= $row['cat_id'] ?>"><?= $row['cat_id'] . " " ?><?= $row['categoryName'] ?></option>

            <?php } ?>
        </select><br><br>

        <label class="form-label" for="supplier">
            <h6 class="my-0">Vendor Information</h6>
        </label>
        <select name="vendor" class="form-control" id='supplier'>
            <?php
            $sql = "SELECT * FROM `supplier` ORDER BY `supplier`.`sup_company_name` ASC";
            $result = $mysqli->query($sql);
            while ($row = mysqli_fetch_array($result)) {
                ?>

                <option value="<?= $row['sup_id'] ?>"><?= $row['sup_company_name'] ?></option>

            <?php } ?>
        </select>
        <br>

        <label class="form-label" for="category">
            <h6 class="my-0"> Brand</h6>
        </label>
        <select name="brand" class="form-control">
            <?php
            $sql = "SELECT * FROM `brand` ORDER BY `brand`.`Name` ASC";
            $result = $mysqli->query($sql);
            while ($row = mysqli_fetch_array($result)) {
                ?>

                <option value="<?= $row['brandID'] ?>"><?= $row['Name'] ?></option>

            <?php } ?>
        </select>
        <br>

        <label for="product_information">Cost:</label><br>
        <input type="text" id="cost" name="cost" required /><br>

        <label for="product_information">Product Information:</label><br>
        <textarea id="product_information" name="produt_info" required></textarea><br><br>



        <label class="form-label" for="category">
            <h6 class="my-0">Returns policy of a product</h6>
        </label>
        <select name="ship_returns" class="form-control">
            <?php
            $sql = "SELECT * FROM `shipping_policy`";
            $result = $mysqli->query($sql);
            while ($row = mysqli_fetch_array($result)) {
                ?>

                <option value="<?= $row['shipping_policy_id'] ?>"><?= $row['shipping_policy'] ?></option>

            <?php } ?>
        </select>

        <br />

        <label for="sku">sku:</label><br>
        <input type="text" id="sku" name="sku" required /><br>

        <label for="barcode">Barcode:</label><br>
        <input type="text" id="barcode" name="barcode" required /><br>

        <label for="product_image">Product Image:</label><br>
        <input type="file" name="file[]" multiple name="inventory_item_images" id="fileToUpload" multiple />


        <input type="submit" value="Add Product">




        <script>
            $(document).ready(function () {
                $('#category').select2();
            });
        </script>


    </form>
    <form method="post" action="product_adder.php" enctype="multipart/form-data">
        <!-- ... other product fields ... -->
        <div id="variant-attributes">
            <div class="attribute-group">
                <label for="attribute_name_1">Attribute Name:</label>
                <input type="text" id="attribute_name_1" name="attribute_name_1"><br> <!-- Add value inputs -->
                <label for="attribute_value_1">Attribute Value:</label>
                <input type="text" id="attribute_value_1" name="attribute_value_1"><br>
            </div>

            <label for="variants[color][]">Color:</label><br>
            <input type="text" name="variants[color][]"><br> <input type="text" name="variants[color][]"><br><br>
            <label for="variants[size][]">Size:</label><br>
            <input type="text" name="variants[size][]"><br> <input type="text" name="variants[size][]"><br><br>
        </div>
        <button type="button" id="add-attribute">Add Another Attribute</button>
        <!-- ... rest of the form ... -->

    </form>

    <script>
        const addAttributeButton = document.getElementById('add-attribute');
        const variantAttributes = document.getElementById('variant-attributes');
        let attributeCount = 1;

        addAttributeButton.addEventListener('click', () => {
            attributeCount++;
            const newAttributeGroup = document.createElement('div');
            newAttributeGroup.classList.add('attribute-group');
            newAttributeGroup.innerHTML = `
            <label for="attribute_name_${attributeCount}">Attribute Name:</label>
            <input type="text" id="attribute_name_${attributeCount}" name="attribute_name_${attributeCount}"><br>
            <label for="attribute_value_${attributeCount}">Attribute Value:</label>
            <input type="text" id="attribute_value_${attributeCount}" name="attribute_value_${attributeCount}"><br>
        `;
            variantAttributes.appendChild(newAttributeGroup);
        });


    </script>



</body>

</html>