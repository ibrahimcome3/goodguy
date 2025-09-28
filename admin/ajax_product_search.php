<?php
session_start();
require_once "../includes.php";

// --- Authentication ---
if (empty($_SESSION['admin_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Authentication required.']);
    exit();
}

header('Content-Type: application/json');

$searchTerm = isset($_GET['term']) ? trim($_GET['term']) : '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit();
}

$response = [];

try {
    // Search for inventory items
    $sql = "SELECT 
                i.InventoryItemID, 
                i.description, 
                i.cost
            FROM 
                inventoryitem i
            WHERE 
                i.description LIKE :searchTerm AND i.status = 'active'
            LIMIT 15";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $response[] = [
            'id' => $product['InventoryItemID'],
            'label' => $product['description'] . ' (ID: ' . $product['InventoryItemID'] . ')',
            'text' => $product['description'] . ' (ID: ' . $product['InventoryItemID'] . ')',
            'cost' => $product['cost']
        ];
    }
} catch (PDOException $e) {
    error_log("AJAX Product Search Error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Database query failed.']);
    exit();
}

echo json_encode($response);