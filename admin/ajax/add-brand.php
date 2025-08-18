<?php

require_once "../../includes.php";
require_once "../../class/Brand.php";
session_start();

// Check if user is logged in as admin
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || empty($data['name'])) {
    echo json_encode(['success' => false, 'error' => 'Brand name is required']);
    exit;
}

try {
    $brandObj = new Brand($pdo);
    $brandName = trim($data['name']);
    $brandDescription = trim($data['description'] ?? '');

    // Check if brand already exists
    $existingBrand = $brandObj->getBrandByName($brandName);
    if ($existingBrand) {
        echo json_encode(['success' => false, 'error' => 'A brand with this name already exists']);
        exit;
    }

    // Add new brand
    $brandId = $brandObj->addBrand($brandName, $brandDescription);

    if ($brandId) {
        echo json_encode([
            'success' => true,
            'brand_id' => $brandId,
            'message' => 'Brand added successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add brand']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}