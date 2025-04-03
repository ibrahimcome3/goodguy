<?php
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/Brand.php';

$b = new Brand();

// Check if the form is submitted
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
    $brandName = $validationResults[0]['value'];
    $brandData['name'] = $brandName;
    $brandData['brand_description'] = $validationResults[1]['value'];
    $brandData['websiteURL'] = $validationResults[2]['value'];

    // Check if a brand with this name already exists
    $existingBrand = $b->getBrandByName($mysqli, $brandName);
    if ($existingBrand) {
        echo "<p style='color:red;'>A brand with this name already exists.</p>";
        exit;
    }

    // Handle the file upload
    $brandLogo = $b->moveBrandLogo($_FILES);
    if ($brandLogo) {
        $brandData['brand_logo'] = $brandLogo;
    }

    if ($b->addBrand($mysqli, $brandData)) {
        header("Location: manage_brands.php");
        exit;
    } else {
        echo "Error adding brand.";
        exit;
    }
}
?>