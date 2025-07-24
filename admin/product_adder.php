<?php
include "../includes.php";
include "../class/Admin.php";

session_start(); // Ensure session is started

// Redirect to login if user is not logged in
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$p = new ProductItem($pdo);
$c = new Category($pdo);

// Use the correct file input name from add-product.php
$file = $_FILES['product_image'] ?? null;

// Debugging line to check the file input

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Use correct POST field names from add-product.php
    $product_name = $_POST['product_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $product_information = $_POST['product_information'] ?? '';
    $shipping_and_returns = (int) ($_POST['shipping_returns'] ?? 0);
    $vendor = $_POST['vendor'] ?? null;
    //$shipping_type = $_POST['shipping_type'] ?? '';
    //$attribute = $_POST['attribute'] ?? '';
    $adminObj = new Admin($pdo);
    $admin_id = $adminObj->getAdminIdByUserId($_SESSION['admin_id']) ?? null;



    // Validate POST data
    $validationResults = [];
    $validationResults[] = $p->validatePost('product_name', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('category', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('brand', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('product_information', 'string', true, 1, 1000);
    $validationResults[] = $p->validatePost('shipping_returns', 'int', true, 1, 255);

    // $validationResults[] = $p->validatePost('shipping_type', 'int', true, 1, 255);
    //$validationResults[] = $p->validatePost('attribute', 'string', true, 1, 255);

    // Check for errors
    $errors = [];
    foreach ($validationResults as $result) {
        if (isset($result['error'])) {
            $errors[] = $result['error'];
        }
    }

    if (!empty($errors)) {
        echo "<ul style='color: red;'>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
        exit();
    }

    $product_name = $validationResults[0]['value'];
    $category = $validationResults[1]['value'];
    $brand = $validationResults[2]['value'];
    $product_information = $validationResults[3]['value'];
    $shipping_and_returns = $validationResults[4]['value'];
    $vendor = $_POST['vendor'] ?? null; // Get vendor from POST data
    //$shipping_type = $validationResults[5]['value'];
    //$attribute = $validationResults[6]['value'];

    // Prepare Product data for insertion
    $currentUserId = $_SESSION['admin_id'] ?? null; // Get current user ID from session
    $productData = [
        'product_name' => $product_name,
        'category' => $category,
        'brand' => $brand,
        'product_information' => $product_information,
        'shipping_returns' => $shipping_and_returns,
        'vendor_id' => $vendor, // Assuming vendor is passed correctly
        //'attribute' => $attribute,
        'admin_id' => $admin_id
    ];


    // Validate and move image


    if (!$p->checkAllowableImage($file)) {


        echo "<b style='color:#CC0000'>Image type not allowed.</b>";
        echo "<a href=" . $_SERVER['HTTP_REFERER'] . ">Back</a>";
        exit();
    }




    // Insert product
    $lastProductId = $p->insertProductItemAsAdmin($mysqli, $productData);

    if ($lastProductId !== false) {
        // Handle tags
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            $tags = $_POST['tags'];

            // Prepare statements for tag handling
            $stmt_find_tag = $pdo->prepare("SELECT tag_id FROM tags WHERE tag_name = ?");
            $stmt_insert_tag = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (?)");
            $stmt_link_product_tag = $pdo->prepare("INSERT INTO product_tags (product_id, tag_id) VALUES (?, ?)");

            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if (empty($tagName)) {
                    continue;
                }

                // 1. Find or create the tag
                $stmt_find_tag->execute([$tagName]);
                $tag = $stmt_find_tag->fetch();

                if ($tag) {
                    $tag_id = $tag['tag_id'];
                } else {
                    // Tag doesn't exist, create it
                    $stmt_insert_tag->execute([$tagName]);
                    $tag_id = $pdo->lastInsertId();
                }

                // 2. Link the tag to the product
                $stmt_link_product_tag->execute([$lastProductId, $tag_id]);
            }
        }

        $sku = generateSKUFromCategoryAndName($mysqli, $category, $product_name, $lastProductId);
        $p->makedir_for_product($lastProductId);
        $p->moveProductImage($lastProductId, $file);
        header("Location: add-product-varient.php?product_id=$lastProductId");
        exit();
    } else {
        echo "<b style='color:#CC0000'>Failed to add product.</b>";
    }
} else {
    echo "Invalid request.";
}

function generateSKUFromCategoryAndName($mysqli, $category, $productName, $productId)
{
    $sku = strtoupper(substr($category, 0, 3)) . "-" . strtoupper(substr($productName, 0, 4)) . "-" . $productId;
    return $sku;
}
?>