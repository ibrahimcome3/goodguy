<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "includes.php";
// include "conn.php"; // Remove this line, $pdo from includes.php should be used

//Data is received as JSON, decode the JSON
$data = json_decode(file_get_contents('php://input'), true);
$wishlistItemId = $data['wishlist_id']; //Get wishlist ID
$userId = $_SESSION['uid'] ?? null; //Get logged in user ID, default to null if not set


try {
    //Validate the wishlist item ID and ensure it's an integer. Prevent SQL injection.
    $wishlistItemId = filter_var($wishlistItemId, FILTER_SANITIZE_NUMBER_INT);
    if (!is_numeric($wishlistItemId)) {
        throw new Exception("Invalid wishlist item ID.");
    }

    if (!$userId) {
        throw new Exception("User not logged in.");
    }

    //Check if wishlist item exists and belongs to the current user. Prevent unauthorized deletion.
    // Use $pdo instead of $db
    $stmt = $pdo->prepare("SELECT wishlistid FROM wishlist WHERE wishlistid = :wishlist_id AND customer_id = :customer_id");
    $stmt->bindParam(':wishlist_id', $wishlistItemId, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $userId, PDO::PARAM_INT);
    $stmt->execute([$wishlistItemId, $userId]);
    $wishlist_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wishlist_item) {
        throw new Exception("Wishlist item not found or does not belong to you.");
    }
    //Remove from wishlist.
    $deleteStmt = $pdo->prepare("DELETE FROM wishlist WHERE wishlistid = :wishlist_id AND customer_id = :customer_id");
    $deleteStmt->bindParam(':wishlist_id', $wishlistItemId, PDO::PARAM_INT);
    $deleteStmt->bindParam(':customer_id', $userId, PDO::PARAM_INT);
    $deleteStmt->execute();

    //Send success response.
    echo json_encode(['success' => true, 'message' => 'Product removed from wishlist.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>