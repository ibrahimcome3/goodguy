<?php
session_start();
include "../conn.php";
require_once '../class/Connn.php';
require_once '../class/User.php';
require_once '../class/Seller.php';
require_once "../class/ProductItem.php";


$s = new Seller();
$u = new User();
$p = new ProductItem();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get data
    $userId = $_POST['user_id'];

    // Validate form data
    $validationResults = [];

    $validationResults[] = $p->validatePost('seller_name', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('seller_email', 'string', true, 1, 255);
    $validationResults[] = $p->validatePost('seller_phone', 'string', false, 1, 20); // Not required
    $validationResults[] = $p->validatePost('seller_address', 'string', false, 1, 1000); // Not required
    $validationResults[] = $p->validatePost('seller_business_name', 'string', false, 1, 1000); // Not required
    $validationResults[] = $p->validatePost('seller_description', 'string', false, 1, 1000); // Not required

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
    // Create seller data array
    $sellerData = [
        'user_id' => $userId,
        'seller_name' => $validationResults[0]['value'],
        'seller_email' => $validationResults[1]['value'],
        'seller_phone' => $validationResults[2]['value'],
        'seller_address' => $validationResults[3]['value'],
        'seller_business_name' => $validationResults[4]['value'],
        'seller_description' => $validationResults[5]['value'],
    ];
    $mysqli->begin_transaction();
    try {
        $sellerId = $s->insertSeller($mysqli, $sellerData);
        if ($sellerId === "duplicated") {
            throw new Exception("You are already registered as a seller.");
        }

        if ($sellerId) {
            // Update the user's role to 'seller'
            if ($u->updateUserVendorStatus($mysqli, $userId, 'pending')) {
                $_SESSION['seller_id'] = $userId;
                $mysqli->commit();
                echo "<p style='color: green;'>You have successfully registered as a seller! Your application is pending approval.</p>";
                header("Locarion: seller-dashboard.php");
            } else {
                throw new Exception("Error updating user role.");
            }
        } else {
            throw new Exception("Error registering seller.");
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    }

}
?>