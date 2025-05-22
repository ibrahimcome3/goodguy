<?php
session_start();
require_once "includes.php"; // ensure session_start() is called

// Check if user is logged in
if (!isset($_SESSION['uid'])) {
    // Store the intended destination if possible, or just redirect to login
    // $_SESSION['redirect_url'] = 'product-detail.php?itemid=' . (isset($_POST['inventory-item']) ? $_POST['inventory-item'] : ''); // Example
    header("Location: login.php?message=You must be logged in to submit a review.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $inventory_item_id = filter_input(INPUT_POST, 'inventory-item', FILTER_VALIDATE_INT);
    $customer_id = (int) $_SESSION['uid'];
    $rating_index = filter_input(INPUT_POST, 'rate', FILTER_VALIDATE_INT); // This is the index from rateit.js
    $review_title = trim(filter_input(INPUT_POST, 'review_title', FILTER_SANITIZE_STRING));
    $comment = trim(filter_input(INPUT_POST, 'reply-message', FILTER_SANITIZE_STRING));

    // For redirecting back to product page
    $redirect_url = "product-detail.php?itemid=" . $inventory_item_id;

    // Validate inputs
    if (!$inventory_item_id) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Invalid product ID.'];
        header("Location: shop.php"); // Or some other appropriate page
        exit();
    }

    // The rateit.js with data-rateit-valuesrc="index" submits the 0-based index.
    // <option value="">none</option> <!-- index 0 -->
    // <option value="bad">Bad</option> <!-- index 1 -->
    // <option value="ok">OK</option> <!-- index 2 -->
    // <option value="great">Great</option> <!-- index 3 -->
    // <option value="good">Good</option> <!-- index 4 -->
    // <option value="excellent">Excellent</option> <!-- index 5 -->
    // So, submitted index 1 means 1 star, index 5 means 5 stars.

    if ($rating_index === null || $rating_index < 1 || $rating_index > 5) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Please select a valid rating (1 to 5 stars).'];


        header("Location: " . $redirect_url);
        exit();
    }
    $actual_rating = $rating_index; // The index directly corresponds to the 1-5 rating

    if (empty($comment)) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Review comment cannot be empty.'];
        var_dump($_SESSION);
        var_dump($_POST);

        exit;
        header("Location: " . $redirect_url);
        exit();
    }
    if (empty($review_title)) {
        // Make title optional or provide a default
        $review_title = "Review for product"; // Or keep it empty if your DB allows NULL
    }


    // Instantiate Review class (ensure $pdo is available from includes.php)
    try {

        $review_handler = new Review($pdo); // Assuming Review class constructor takes PDO
        if ($review_handler->addReview($inventory_item_id, $customer_id, $actual_rating, $comment, $review_title)) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Thank you! Your review has been submitted.'];
        } else {
            // The addReview method might set its own flash message if user already reviewed
            if (!isset($_SESSION['flash_message'])) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Could not submit your review. Please try again.'];
            }
        }
    } catch (Exception $e) {
        error_log("Error in product-review-submitter.php: " . $e->getMessage());
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'An unexpected error occurred. Please try again.'];
    }

    header("Location: " . $redirect_url);
    exit();

} else {
    // Not a POST request, redirect to homepage or show error
    header("Location: index.php");
    exit();
}