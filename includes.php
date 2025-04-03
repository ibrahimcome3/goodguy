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
$product_obj = new ProductItem(); // Pass PDO connection
$promotion = new Promotion(); // Pass PDO connection
$Orvi = new Review(); // Pass PDO connection
$invt = new InventoryItem($pdo); // Pass PDO connection
$cart = new Cart($pdo, $promotion);


if (isset($_SESSION['uid'])) {
     $wishlist = new WishList($pdo, $_SESSION['uid']); // Pass PDO connection
     $user = new User(); // Pass PDO connection
}
?>