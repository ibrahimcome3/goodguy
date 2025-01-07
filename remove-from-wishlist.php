<?php
include "includes.php";
include "conn.php";

//Data is received as JSON, decode the JSON
$data = json_decode(file_get_contents('php://input'), true);
$wishlistItemId = $data['wishlist_id']; //Get wishlist ID
$userId = $_SESSION['uid']; //Get logged in user ID


try {
    //Validate the wishlist item ID and ensure it's an integer. Prevent SQL injection.
    $wishlistItemId = filter_var($wishlistItemId, FILTER_SANITIZE_NUMBER_INT);
    if (!is_numeric($wishlistItemId)) {
        throw new Exception("Invalid wishlist item ID.");
    }

    //Check if wishlist item exists and belongs to the current user. Prevent unauthorized deletion.
    $stmt = $db->prepare("SELECT * FROM wishlist WHERE wishlistid = ? AND customer_id = ?");
    $stmt->execute([$wishlistItemId, $userId]);
    $wishlist_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wishlist_item) {
        throw new Exception("Wishlist item not found or does not belong to you.");
    }

    //Remove from wishlist.
    $stmt = $db->prepare("DELETE FROM wishlist WHERE wishlistid = ?");
    $stmt->execute([$wishlistItemId]);

    //Send success response.
    echo json_encode(['success' => true, 'message' => 'Product removed from wishlist.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>