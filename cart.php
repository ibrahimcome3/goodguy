<?php

include "includes.php";
$options = [
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_EMULATE_PREPARES => false,
];
$host = 'localhost';
$db = 'lm_test';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
    $pdo = new \PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}
$arr = array();
foreach ($_POST as $key => $val) {
    if ($key === 'color')
        $arr[$key] = $val;
    if ($key === 'size')
        $arr[$key] = $val;
}

//var_dump($_POST);

//var_dump($arr);

// If the user clicked the add to cart button on the product page we can check for the form data
if (isset($_POST['inventory_product_id'], $_POST['qty']) && is_numeric($_POST['inventory_product_id']) && is_numeric($_POST['qty'])) {
    // Set the post variables so we easily identify them, also make sure they are integer
    $cart = new Cart();
    $arryofproperties = array();

    $product_id = (int) $_POST['inventory_product_id'];
    $product_with_key = array();
    $product_with_key['product'] = $product_id;
    $product_array = array();
    $product_array = array($product_with_key, $arr);
    $JSON_product_order = json_encode($product_array);
    //echo $JSON_product_order;


    //$product_id = (int) $cart->get_right_inventroy_item_neede($arr); 
    $quantity = (int) $_POST['qty'];

    $stmt = $pdo->prepare('SELECT * FROM inventoryitem WHERE InventoryItemID = ?');
    $stmt->execute([$_POST['inventory_product_id']]);
    // Fetch the product from the database and return the result as an Array
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    // Check if the product exists (array is not empty)
    if ($product && $quantity > 0) {
        // Product exists in database, now we can create/update the session variable for the cart
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            if (array_key_exists($product_id, $_SESSION['cart'])) {
                // Product exists in cart so just update the quanity
                $_SESSION['cart'][$product_id] += $quantity;
                $_SESSION['detail-item'][$product_id] = $JSON_product_order;
            } else {
                // Product is not in cart so add it
                $_SESSION['cart'][$product_id] = $quantity;
                $_SESSION['detail-item'][$product_id] = $JSON_product_order;
            }
        } else {
            // There are no products in cart, this will add the first product to cart
            $_SESSION['cart'] = array($product_id => $quantity);
            $_SESSION['detail-item'][$product_id] = $JSON_product_order;
        }
    }
    // Prevent form resubmission...
    var_dump($_SESSION['detail-item']);
    header('location: product-detail.php?itemid=' . $_POST['inventory_product_id']);
    exit();
}