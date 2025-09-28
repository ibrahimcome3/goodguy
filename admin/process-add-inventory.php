<?php
session_start();
require_once "../includes.php";
require_once __DIR__ . '/../class/ProductItem.php';

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$variantsData = $_POST['variants'] ?? [];
$filesData = $_FILES['variants'] ?? [];

if (!$productId || empty($variantsData)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'No product ID or variant data submitted.'];
    header("Location: add-inventory-item.php?productId=" . $productId);
    exit();
}

function processAndSaveImages($productId, $inventoryItemId, $files)
{
    $uploadedPaths = [];
    $baseDir = realpath(__DIR__ . "/..") . "/products/product-{$productId}/inventory-{$productId}-{$inventoryItemId}";
    $resizedDir = $baseDir . "/resized";

    if (!ProductItem::ensureDirectoryExists($resizedDir)) {
        throw new Exception("Failed to create image directory: " . $resizedDir);
    }

    foreach ($files['name'] as $key => $name) {
        if ($files['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            continue;
        }

        $fname = time() . '_' . uniqid() . '.' . $ext;
        $originalDest = $baseDir . "/$fname";

        if (move_uploaded_file($files['tmp_name'][$key], $originalDest)) {
            $resizedDest = $resizedDir . "/$fname";
            ProductItem::resizeImage($originalDest, $resizedDest, 600);

            // Store the relative path for the database
            $uploadedPaths[] = "products/product-{$productId}/inventory-{$productId}-{$inventoryItemId}/resized/{$fname}";
        }
    }
    return $uploadedPaths;
}



$pdo->beginTransaction();
try {
    foreach ($variantsData as $index => $variant) {
        if (empty($variant['description']) || !isset($variant['cost']) || !isset($variant['price']) || !isset($variant['quantity'])) {
            throw new Exception("Variant #" . ($index + 1) . " is missing required fields (Description, Cost, Price, Quantity).");
        }

        $skuJson = !empty($variant['sku']) ? json_encode($variant['sku']) : null;

        $sql = "INSERT INTO inventoryitem (
                    productItemID, description, cost, price, quantity, discount_percentage, 
                    barcode, tax_rate, status, sku, date_added
                ) VALUES (
                    :productItemID, :description, :cost, :price, :quantity, :discount_percentage,
                    :barcode, :tax_rate,  :status, :sku, NOW()
                )";
        $stmt = $pdo->prepare($sql);

        // Handle delivery_date: set to null if empty
        $deliveryDate = !empty($variant['delivery_date']) ? $variant['delivery_date'] : null;

        $stmt->execute([
            ':productItemID' => $productId,
            ':description' => trim($variant['description']),
            ':cost' => (float) ($variant['cost'] ?? 0.00),
            ':price' => (float) $variant['price'],
            ':quantity' => (int) $variant['quantity'],
            ':discount_percentage' => (float) ($variant['discount_percentage'] ?? 0.00),
            ':barcode' => trim($variant['barcode'] ?? ''),
            ':tax_rate' => (float) ($variant['tax_rate'] ?? 0),
            ':status' => $variant['status'] ?? 'active',
            ':sku' => $skuJson
        ]);

        $inventoryItemId = $pdo->lastInsertId();
        if (!$inventoryItemId) {
            throw new Exception("Failed to create database record for variant #" . ($index + 1) . ".");
        }

        $variantFiles = [];
        if (isset($filesData['name'][$index]['images'])) {
            foreach ($filesData as $key => $values) {
                $variantFiles[$key] = $values[$index]['images'];
            }
        }

        if (!empty($variantFiles['name'][0])) {
            $imagePaths = processAndSaveImages($productId, $inventoryItemId, $variantFiles);
            $imgStmt = $pdo->prepare("INSERT INTO inventory_item_image (inventory_item_id, image_path, is_primary) VALUES (?, ?, ?)");
            foreach ($imagePaths as $i => $path) {
                $imgStmt->execute([$inventoryItemId, $path, ($i === 0) ? 1 : 0]);
            }
        }
    }

    $pdo->commit();
    $_SESSION['flash_message'] = ['type' => 'success', 'text' => count($variantsData) . ' variant(s) added successfully!'];
    header("Location: view-single-product.php?id=" . $productId);
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error adding variants for product ID {$productId}: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Error: ' . $e->getMessage()];
    header("Location: add-inventory-item.php?productId=" . $productId);
    exit();
}