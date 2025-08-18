<?php
require_once "../../includes.php";
session_start();

// Check if user is logged in
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['product_id']) || !isset($data['filename'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$productId = (int) $data['product_id'];
$filename = basename($data['filename']); // Sanitize filename

try {
    // Update product's primary image in database
    $stmt = $pdo->prepare("UPDATE productitem SET primary_image = :primary_image WHERE productID  = :product_id");
    $success = $stmt->execute([
        ':primary_image' => $filename,
        ':product_id' => $productId
    ]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update primary image']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}