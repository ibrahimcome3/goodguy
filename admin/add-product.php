<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h3>Register a product</h3>
                <form method="post" action="product_adder.php" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label for="product_name" class="form-label">Product Name:</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category:</label>
                        <select name="category" id="category" class="form-select">
                            <?php
                            $sql = "SELECT * FROM `category_new` ORDER BY `cat_id` ASC";
                            $result = $mysqli->query($sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<option value='{$row['cat_id']}'>{$row['cat_id']} {$row['categoryName']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="supplier" class="form-label">Vendor Information:</label>
                        <select name="vendor" class="form-select" id='supplier'>
                            <?php
                            $sql = "SELECT * FROM `supplier` ORDER BY `supplier`.`sup_company_name` ASC";
                            $result = $mysqli->query($sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<option value='{$row['sup_id']}'>{$row['sup_company_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="brand" class="form-label">Brand:</label>
                        <select name="brand" class="form-select">
                            <?php
                            $sql = "SELECT * FROM `brand` ORDER BY `brand`.`Name` ASC";
                            $result = $mysqli->query($sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<option value='{$row['brandID']}'>{$row['Name']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="product_information" class="form-label">Product Information:</label>
                        <textarea class="form-control" id="product_information" name="produt_info" rows="3"
                            required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ship_returns" class="form-label">Returns policy of a product:</label>
                        <select name="ship_returns" class="form-select">
                            <?php
                            $sql = "SELECT * FROM `shipping_policy`";
                            $result = $mysqli->query($sql);
                            while ($row = mysqli_fetch_array($result)) {
                                echo "<option value='{$row['shipping_policy_id']}'>{$row['shipping_policy']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="product_image" class="form-label">Product Image:</label>
                        <input type="file" class="form-control" id="product_image" name="file[]" multiple
                            accept="image/*">
                    </div>
                    <div class="text-center"> <button type="submit" class="btn btn-primary">Add Product</button> </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#category').select2(); // Initialize Select2 on the category dropdown.
            $('#supplier').select2(); // Initialize Select2 on the supplier dropdown.
            $('select[name="brand"]').select2(); // Initialize Select2 on the brand dropdown.

            $('select[name="ship_returns"]').select2(); // Initialize Select2 on the shipping returns dropdown.
        });
    </script>

</body>

</html>