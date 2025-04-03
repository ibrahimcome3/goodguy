<?php
include "../conn.php";
require_once '../class/Connn.php';

?>
<!DOCTYPE html>
<html>

<head>
    <title>Add Brand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include "navbar.php"; ?>
    <div class="container mt-5">
        <h1>Add New Brand</h1>
        <form method="post" action="process_add_brand.php" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="brand_name" class="form-label">Brand Name:</label>
                <input type="text" class="form-control" id="brand_name" name="brand_name" required>
            </div>
            <div class="mb-3">
                <label for="brand_description" class="form-label">Brand Description:</label>
                <textarea class="form-control" id="brand_description" name="brand_description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="brand_websiteURL" class="form-label">Brand Website Address:</label>
                <input type="text" class="form-control" id="brand_websiteURL" name="brand_websiteURL">
            </div>
            <div class="mb-3">
                <label for="brand_logo" class="form-label">Brand logo:</label>
                <input type="file" class="form-control" id="brand_logo" name="brand_logo">
            </div>
            <button type="submit" class="btn btn-primary">Add Brand</button>
        </form>
    </div>
</body>

</html>