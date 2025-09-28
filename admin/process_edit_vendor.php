<?php
session_start();
require_once "../includes.php";

// --- Authentication & Authorization ---
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

// --- Input Validation ---
$vendorId = isset($_POST['vendor_id']) ? (int) $_POST['vendor_id'] : 0;
$businessName = trim($_POST['business_name'] ?? '');
$contactName = trim($_POST['contact_name'] ?? '');
$businessPhone = trim($_POST['business_phone'] ?? '');
$businessAddress = trim($_POST['business_address'] ?? '');
$status = trim($_POST['status'] ?? '');

$allowedStatuses = ['active', 'inactive', 'pending', 'suspended'];

if (empty($vendorId) || empty($businessName) || !in_array($status, $allowedStatuses)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Invalid data submitted. Please check all required fields.'];
    header("Location: edit-vendor.php?id=" . $vendorId);
    exit();
}

// --- Database Update ---
try {
    $sql = "UPDATE vendors SET 
                business_name = :business_name,
                contact_name = :contact_name,
                business_phone = :business_phone,
                business_address = :business_address,
                status = :status
            WHERE vendor_id = :vendor_id";

    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':business_name', $businessName, PDO::PARAM_STR);
    $stmt->bindParam(':contact_name', $contactName, PDO::PARAM_STR);
    $stmt->bindParam(':business_phone', $businessPhone, PDO::PARAM_STR);
    $stmt->bindParam(':business_address', $businessAddress, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR);
    $stmt->bindParam(':vendor_id', $vendorId, PDO::PARAM_INT);

    $stmt->execute();

    $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Vendor details updated successfully.'];
    header("Location: view-vendor.php?id=" . $vendorId);

} catch (PDOException $e) {
    error_log("Error updating vendor ID {$vendorId}: " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'An error occurred while updating the vendor. Please try again.'];
    header("Location: edit-vendor.php?id=" . $vendorId);
}
exit();