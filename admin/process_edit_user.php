<?php
session_start();
include "../conn.php";
require_once '../class/User.php';
require_once '../class/Seller.php';

$u = new User();
$s = new Seller();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address1 = filter_input(INPUT_POST, 'address1', FILTER_SANITIZE_STRING);
    $address2 = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_STRING);
    $businessName = filter_input(INPUT_POST, 'businessName', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);


    try {
        $u->updateUser($mysqli, $userId, $username, $email, $phone, $address1, $address2);
        if (isset($businessName)) {
            $s->updateSeller($mysqli, $userId, $businessName, $description);
        }
        header("Location: user_profile.php?user_id=" . $userId);
        exit;
    } catch (Exception $e) {
        echo "<p style='color:red;'>An error occurred: " . $e->getMessage() . "</p>";
        exit;
    }
}
?>