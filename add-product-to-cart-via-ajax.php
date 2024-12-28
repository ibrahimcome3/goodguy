<?php
 session_start();
        $options = [
    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    \PDO::ATTR_EMULATE_PREPARES   => false,
    ];
      $host = 'localhost';
      $db   = 'u633821528_goodguyng';
      $user = 'u633821528_goodguyng';
      $pass = 'PPassword12@';
      $charset = 'utf8mb4';
      $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    try {
         $pdo = new \PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
         throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }


 if (isset($_POST['inventory_product_id'], $_POST['qty']) && is_numeric($_POST['inventory_product_id']) && is_numeric($_POST['qty'])) {
    // Set the post variables so we easily identify them, also make sure they are integer
    $product_id = (int)$_POST['inventory_product_id'];
    $quantity = (int)$_POST['qty'];

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
            } else {
                // Product is not in cart so add it
                $_SESSION['cart'][$product_id] = $quantity;
            }
        } else {
            // There are no products in cart, this will add the first product to cart
            $_SESSION['cart'] = array($product_id => $quantity);
        }
    }
 }

  echo  $num_items_in_cart = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

?>