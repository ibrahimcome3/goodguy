<?php

include "includes.php";
include "conn.php";


//if (!isLoggedIn()) {
//header("Location: login.php?error=Please log in to add to wishlist.");
//  exit();
//} else {

//}

// User is logged in; proceed with adding the product to the wishlist
// ... (rest of your add-product-to-wish-list.php code) ...
?>


<?php
// Assuming you have a database connection established ($db)

//$productId = $_POST['product_id'];
$data = json_decode(file_get_contents('php://input'), true); // Decode JSON data
$productId = $data['product_id'];
$_SESSION['last_viewed_product'] = $data['product_id'];

//Sanitize and validate $productId

try {
    //Check if user is logged in
    //Get the currently logged-in user's ID
    $userId = $_SESSION['uid']; // Replace with your user authentication logic


    // Check if the product exists
    $stmt = $db->prepare("SELECT * FROM inventoryitem WHERE InventoryItemID = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception("Product not found.");
    }


    // Check if the product is already in the wishlist
    $stmt = $db->prepare("SELECT * FROM wishlist WHERE customer_id = ? AND inventory_item_id = ?");
    $stmt->execute([$userId, $productId]);
    $wishlist_item = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    if ($wishlist_item) {
        if (isset($_SESSION['last_viewed_product'])) {
            unset($_SESSION['last_viewed_product']);
        }
        echo json_encode(['success' => false, 'message' => 'Product already in wishlist.']);

    } else {
        // Add the product to the wishlist
        $stmt = $db->prepare("INSERT INTO wishlist (customer_id, inventory_item_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        if (isset($_SESSION['last_viewed_product'])) {
            unset($_SESSION['last_viewed_product']);
        }
        echo json_encode(['success' => true]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}




function isLoggedIn()
{
    // Your authentication logic here (e.g., checking database, tokens, etc.)
    // ... (Replace with your actual authentication mechanism) ...

    //Return true if user is logged in, false otherwise.  This will need to be tailored to your exact authentication implementation
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null;
}

?>