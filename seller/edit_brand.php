<?php
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/Brand.php';

// Get the brand ID from the URL
$brandId = isset($_GET['brand_id']) ? intval($_GET['brand_id']) : 0;

// Initialize the Brand class
$b = new Brand();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $validationResults = [];
    $validationResults[] = $b->validatePost('brand_name', 'string', true, 1, 255);
    $validationResults[] = $b->validatePost('brand_description', 'string', true, 1, 1000);
    $validationResults[] = $b->validatePost('brand_websiteURL', 'string', false, 0, 255);

    // Check for errors
    $errors = [];
    foreach ($validationResults as $result) {
        if (isset($result['error'])) {
            $errors[] = $result['error'];
        }
    }
    //If there are errors display them, otherwise proceed with processing
    if (!empty($errors)) {
        echo "<ul style='color: red;'>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        exit();
    }

    $brandData = [];
    $brandData['name'] = $validationResults[0]['value'];
    $brandData['brand_description'] = $validationResults[1]['value'];
    $brandData['websiteURL'] = $validationResults[2]['value'];

    // Handle the file upload with the moveBrandLogo method
    $brandLogo = $b->moveBrandLogo($_FILES);

    //Get the previous brand info.
    $existingBrand = $b->getBrandById($mysqli, $brandId);

    // Check if a new logo was uploaded
    if ($brandLogo !== null) {
        // If a new logo was uploaded, delete the old one if it exists
        if (!empty($existingBrand['brand_logo'])) {
            $oldLogoPath = "../brand/" . $existingBrand['brand_logo'];
            if (file_exists($oldLogoPath)) {
                unlink($oldLogoPath);
            }
        }
        $brandData['brand_logo'] = $brandLogo;
    } else {
        // If no new logo was uploaded, keep the old one
        $brandData['brand_logo'] = $existingBrand['brand_logo'];
    }

    // Update the brand in the database
    if ($b->updateBrand($mysqli, $brandId, $brandData)) {
        header("Location: manage_brands.php");
        exit;
    } else {
        echo "Error updating brand.";
        exit;
    }
} else {
    // Fetch the brand details if it's not a POST request
    $brand = $b->getBrandById($mysqli, $brandId);
    if (!$brand) {
        echo "Brand not found.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Brand</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <?php include "navbar.php"; ?>
    <div class="container mt-5">
        <h1>Edit Brand</h1>
        <form method="post" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="brand_name" class="form-label">Brand Name:</label>
                <input type="text" class="form-control" id="brand_name" name="brand_name"
                    value="<?= htmlspecialchars($brand['Name']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="brand_description" class="form-label">Brand Description:</label>
                <textarea class="form-control" id="brand_description" name="brand_description"
                    required><?= htmlspecialchars($brand['brand_description']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="brand_websiteURL" class="form-label">Brand Website Address:</label>
                <input type="text" class="form-control" id="brand_websiteURL" name="brand_websiteURL"
                    value="<?= htmlspecialchars($brand['websiteURL']) ?>">
            </div>
            <div class="mb-3">
                <label for="brand_logo" class="form-label">Brand Logo:</label><br>
                <?php if (!empty($brand['brand_logo'])): ?>
                    <img src="../brand/<?= htmlspecialchars($brand['brand_logo']) ?>"
                        alt="<?= htmlspecialchars($brand['Name']) ?> Logo" style="max-width: 200px;"><br>

                    <label for="brand_logo" class="form-label">Change Brand logo:</label>
                <?php endif; ?>
                <input type="file" class="form-control" id="brand_logo" name="brand_logo">
            </div>
            <button type="submit" class="btn btn-primary">Update Brand</button>
        </form>
    </div>
</body>

</html>