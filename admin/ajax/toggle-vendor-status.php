<?php

require_once "../../includes.php";

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$vendorId = $data['vendor_id'] ?? null;
$currentStatus = $data['current_status'] ?? null;

if (!$vendorId || !$currentStatus) {
    die(json_encode(['success' => false, 'message' => 'Invalid parameters']));
}

try {
    // Toggle the status
    $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

    $stmt = $pdo->prepare("UPDATE vendor SET status = ? WHERE vendor_id = ?");
    $success = $stmt->execute([$newStatus, $vendorId]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
} catch (PDOException $e) {
    error_log("Error toggling vendor status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}