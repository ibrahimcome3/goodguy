<?php

$host = "localhost"; // Host name
$username = "root";
$password = ""; // Mysql password
$db_name = "lm_test"; // Database name
$mysqli = mysqli_connect("$host", "$username", "$password", "$db_name") or die("cannot connect");
if (mysqli_connect_errno()) {
     echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$options = [
     \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
     \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
     \PDO::ATTR_EMULATE_PREPARES => false,
];

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
try {
     // $pdo will be available in the scope of files including this one.
     // No need for 'global $pdo;' here if $pdo is used directly after this block.
     $pdo = new \PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
     error_log("PDO Connection Error in includes.php: " . $e->getMessage());
     die("Database connection failed in includes.php. Please check server logs. PDO Error: " . htmlspecialchars($e->getMessage()));
}

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

// REMOVE THIS LINE - $pdo is already established above. This was causing issues.
require_once 'breadcrumps.php';

//Instantiate classes after database connection
$product_obj = new ProductItem($pdo); // Pass PDO connection
$promotion = new Promotion($pdo); // Pass PDO connection
$Orvi = new Review($pdo); // Pass PDO connection
$invt = new InventoryItem($pdo); // Pass PDO connection
$cart = new Cart($pdo, $promotion);
$order = new Order($pdo);


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