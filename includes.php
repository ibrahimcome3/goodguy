<?php

require_once 'class/Conn.php'; // Make sure Conn.php is included first
require_once 'class/InventoryItem.php'; // Include InventoryItem.php BEFORE instantiation
require_once 'class/Category.php';
require_once 'class/Review.php';
require_once 'class/ProductItem.php';
require_once 'class/Variation.php';
require_once "class/Promotion.php";
require_once "class/WishList.php";
require_once "class/Cart.php";
require_once "class/Shipment.php";
require_once "class/User.php";
require_once "class/Order.php";

require_once(__DIR__ . '/db_connection/conn.php'); // Database connection
require_once 'breadcrumps.php';

//Instantiate classes after database connection
$product_obj = new ProductItem($pdo); // Pass PDO connection
$promotion = new Promotion($pdo); // Pass PDO connection
$Orvi = new Review($pdo); // Pass PDO connection
$invt = new InventoryItem($pdo); // Pass PDO connection
$cart = new Cart($pdo, $promotion);


if (isset($_SESSION['uid'])) {
     $wishlist = new WishList($pdo, $_SESSION['uid']); // Pass PDO connection
     $user = new User($pdo); // Pass PDO connection
}

if (isset($_SESSION['cart_success'])) {
     echo '<div class="container mt-2"><div class="alert alert-success" role="alert">' . htmlspecialchars($_SESSION['cart_success']) . '</div></div>';
     unset($_SESSION['cart_success']); // Clear the message after displaying
}
if (isset($_SESSION['cart_error'])) {
     echo '<div class="container mt-2"><div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['cart_error']) . '</div></div>';
     unset($_SESSION['cart_error']); // Clear the message after displaying
}
?>