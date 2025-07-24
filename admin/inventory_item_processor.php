<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';
require_once '../class/ProductItem.php';
require_once '../class/InventoryItem.php';

$p = new ProductItem($pdo);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'];
    $color_val = $_POST['color']; // Assuming this is a single color value
    $size_val = $_POST['size'];   // Assuming this is a single size value
    $description = $_POST['description'];
    // $skus = $_POST['sku']; // This was likely for manual SKU code entry, we will generate it.
    $tax = $_POST['tax'];
    $cost = $_POST['price'];
    $bcode = $_POST['bcode']; // Bar codes
    $quantityOnHand = $_POST['quantity'];

    // Fetch product name for SKU generation
    $productDetails = $p->getProductById($product_id); // Uses PDO now
    $productName = $productDetails['product_name'] ?? 'PROD';

    // Generate the descriptive SKU
    $descriptiveSku = generateDescriptiveSku($product_id, $productName, $color_val, $size_val);

    // Generate JSON SKU for attributes column
    $sku_attributes_json = convertColorAndSizeToSku($color_val, $size_val);

    // Call insertInventoryItem with the generated descriptive SKU for the sku_code field
    // The $sku_attributes_json will go into the 'sku' field (for JSON attributes)
    $result = insertInventoryItem($mysqli, $product_id, $description, $descriptiveSku, $sku_attributes_json, $cost, $tax, $quantityOnHand, $bcode);

    if (isset($result['success']) && $result['success']) {
        $p->makeSubDirectoriesForVarients($product_id, $result['id']);
        $p->imageprocessorforproductInInventory($product_id, $result['id'], $_FILES);
        header("Location: success.php?id=" . $result['id']); // Pass the inserted ID for success page
        exit();
    } else {
        // Handle errors appropriately (e.g., display error messages to user)
        echo "Error: " . $result['error'];
    }
}
// ... redirect after processing all variants

exit();


function insertInventoryItem($mysqli, $product_id, $description, $sku_code, $sku, $cost, $tax, $quantityOnHand, $bcode)
{
    $sql = "INSERT INTO inventoryitem (productItemID, `description`, sku_code, sku, cost, tax, quantityOnHand, barcode) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return ["error" => "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error];
    }

    if (!$stmt->bind_param("isssddis", $product_id, $description, $sku_code, $sku, $cost, $tax, $quantityOnHand, $bcode)) {
        $stmt->close(); // Clean up
        return ["error" => "Bind param failed: " . $stmt->error];
    }

    if ($stmt->execute()) {
        $stmt->close(); // Clean up
        return ["success" => true, "id" => $mysqli->insert_id]; // Return inserted ID
    } else {
        $stmt->close(); // Clean up
        return ["error" => "Execute failed: " . $stmt->error];
    }
}

function generateDescriptiveSku($productId, $productName, $color, $size)
{
    $skuParts = [];
    // Product ID part (e.g., P000)
    $skuParts[] = "P" . str_pad($productId, 3, '0', STR_PAD_LEFT);

    // Product Name part (e.g., first 4 chars, uppercase, alphanumeric)
    $namePart = strtoupper(substr(preg_replace("/[^A-Za-z0-9]/", '', $productName), 0, 4));
    if (!empty($namePart)) {
        $skuParts[] = $namePart;
    }

    // Color part (e.g., first 3 chars of color name, or hex without # if it's a hex code)
    if (!empty($color)) {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color) || preg_match('/^#[0-9A-Fa-f]{3}$/', $color)) { // Basic hex check
            $skuParts[] = strtoupper(substr($color, 1, 3)); // e.g., F00 for #FF0000
        } else {
            $skuParts[] = strtoupper(substr(preg_replace("/[^A-Za-z0-9]/", '', $color), 0, 3));
        }
    }

    // Size part (e.g., uppercase, alphanumeric)
    if (!empty($size)) {
        $skuParts[] = strtoupper(preg_replace("/[^A-Za-z0-9]/", '', $size));
    }
    return implode("-", array_filter($skuParts)); // array_filter to remove any empty parts before imploding
}

function convertColorAndSizeToSku($color, $size)
{
    $skuArray = [];

    if (!empty($color)) {
        $skuArray['color'] = $color;
    }

    if (!empty($size)) {
        $skuArray['size'] = $size;
    }

    return json_encode($skuArray);
}