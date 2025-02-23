<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php';

$p = new ProductItem();
$c = new Category();

$file = $_FILES['file'];
var_dump($_POST);
if (isset($file['name'])) {

    $product_name = $_POST['product_name'];
    $vendor = $_POST['vendor'];
    $brand = $_POST['brand'];
    $product_information = $_POST['produt_info'];

    $shipping_and_returns = 2;//$_POST['ship_returns'];
    $cat = $category = $_POST['category'];
    $qonhand = 100;//$_POST['quintity_in_inventory'];
    $cost = $_POST['cost'];

    $sku = $_POST['sku'];
    $barcode = $_POST['barcode'];
    $description = $_POST['description'];
    // Validate POST data
    $validationResults = [];

    $validationResults[] = $p->validatePost('product_name', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('description', 'string', true, 1, 1000);
    $validationResults[] = $p->validatePost('vendor', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('brand', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('produt_info', 'string', true, 1, 1000);  //Example max length
    $validationResults[] = $p->validatePost('category', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('cost', 'float', true, 0, 100000); //Example max cost
    $validationResults[] = $p->validatePost('sku', 'string', true, 1, 50);  // Example max length
    $validationResults[] = $p->validatePost('barcode', 'string', true, 1, 50); // Example max length


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

    $product_name = $validationResults[0]['value'];
    $vendor = $validationResults[1]['value'];
    $brand = $validationResults[2]['value'];
    $description = $validationResults[3]['value'];
    $product_information = $validationResults[3]['value'];
    $category = $validationResults[4]['value'];
    $cost = $validationResults[5]['value'];


    // Prepare Product data for insertion
    $productData = [
        'product_name' => $product_name,
        'vendor' => $vendor,
        'description' => $description,
        'category' => $category,
        'brand' => $brand,
        'product_information' => $product_information,
        'shipping_returns' => $shipping_and_returns,
        'user_id' => 10009, // Replace with actual user ID
    ];


    // Now you can use $lastProductId to link this product to other tables



    if (isset($file['name'])) {
        if (!$p->checkAllowableImage($file)) {
            echo "<b style='color:#CC0000'>Image type not allowed.</b>";
            echo "<a href=" . $_SERVER['HTTP_REFERER'] . ">Back</a>";
            exit();
        }
    }


    if (empty($description) || empty($product_information)) {
        echo "<p><b>Error:</b> Please fill in both the 'Description' and 'Product Information' fields.</p>";
        exit();
    }


    $lastProductId = $p->insertProductItem($mysqli, $productData);
    if ($lastProductId !== false) {
        $last_id = $lastProductId;
        $productName = $productData['product_name'];
        $category = $productData['category'];
        $sku = generateSKUFromCategoryAndName($mysqli, $category, $productName, $lastProductId); // Pass product ID
        $p->makedir_for_product($last_id);

        //    if($result){
        //    $mysqli->commit();


        $reorder_quitity = 600;
        $product_item = $last_id;
        $cat = $category;
        $sku = '{"type":"shirt"}';
        $description = $product_information;
        $sql = "INSERT INTO `inventoryitem`(`InventoryItemID`, `quantityOnHand`, `cost`, `reorderQuantity`, `productItemID`, `date_added`,  `sku`, barcode) VALUES (null,'$qonhand','$cost','$reorder_quitity','$product_item', CURRENT_TIMESTAMP,'$sku', '$barcode')";
        echo $sql;
        $result = $mysqli->query($sql);
        var_dump($result);
        $last_id = mysqli_insert_id($mysqli);

        if ($result) {
            echo $p->makeInventoryItemDirectory($lastProductId, $last_id);
        }
    }
    // } else {
    //   $mysqli->rollback();

    //}
}

// header("Location: confirm-page.php?meg=Product has been iploaded");











function generateSKUFromCategoryAndName($mysqli, $category, $productName, $productId)
{
    // Simpler, MySQL-compatible SKU generation
    $sku = strtoupper(substr($category, 0, 3)) . "-" . strtoupper(substr($productName, 0, 4)) . "-" . $productId;
    return $sku;
}

// Example usage
$sku = generateSKUFromCategoryAndName($mysqli, "Electronics", "Super SmartPhone", 67);
echo $sku;






?>