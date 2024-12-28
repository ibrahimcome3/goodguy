<?php
session_start();

include 'class/Conn.php';
include 'class/InventoryItem.php';
include 'class/Category.php';
include 'class/Review.php';
include 'class/ProductItem.php';
include 'class/Variation.php';
include "class/Promotion.php";
include "class/WishList.php";
include "class/Cart.php";
include "class/Shipment.php";

include "conn.php";
include 'breadcrumps.php';
$product_obj = new ProductItem();
$promotion = new Promotion();
$Orvi = new Review();

$invt = new InventoryItem();
if (isset($_SESSION['uid'])) {
     $wishlist = new WishList($_SESSION['uid']);
     include "class/User.php";
     include "class/Order.php";
     $user_ = new User();
     $orders = new Order();
}

function getImage($id_of_what_get_image)
{
     include "conn.php";
     $sql = "select * from inventory_item_image where inventory_item_id = $id_of_what_get_image and `is_primary` = 1";
     $result = $mysqli->query($sql);
     $row = mysqli_fetch_array($result);
     if (mysqli_num_rows($result) > 0)
          return $row['image_path'];
     else
          return "e.jpg";
}

$var = array();
$var1 = array();
$options = [
     \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
     \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
     \PDO::ATTR_EMULATE_PREPARES => false,
];


$host = 'localhost';
$db = 'lm_test';
$user = 'root';
$pass = '';
$port = "3306";
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
try {
     $pdo = new \PDO($dsn, $user, $pass, $options);
     $db = $pdo;
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int) $e->getCode());
}

$var = array();
$var1 = array();
?>