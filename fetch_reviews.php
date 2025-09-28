<?php
// Ensure session is started, as includes.php might depend on it.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header to JSON and initialize a standard response structure.
header('Content-Type: application/json');
$response = ['success' => false, 'reviews' => [], 'total_reviews' => 0, 'message' => 'Invalid request.'];

// 1. Check for correct request method and required parameters.
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['itemid'], $_GET['page'], $_GET['perPage'])) {
    // Message is already set, just output and exit.
    echo json_encode($response);
    exit();
}

// 2. Include dependencies.
// Use require_once to prevent multiple inclusions and ensure the script stops if files are missing.
require_once "includes.php";

// 3. Sanitize and validate input parameters.
$inventoryItemId = filter_var($_GET['itemid'], FILTER_VALIDATE_INT);
$page = filter_var($_GET['page'], FILTER_VALIDATE_INT);
$perPage = filter_var($_GET['perPage'], FILTER_VALIDATE_INT);

// Check if inputs are valid integers and within a reasonable range.
if ($inventoryItemId === false || $page === false || $perPage === false || $inventoryItemId <= 0 || $page <= 0 || $perPage <= 0) {
    $response['message'] = "Invalid parameters for fetching reviews.";
    echo json_encode($response);
    exit();
}

// 4. Fetch data within a try-catch block for robust error handling.
try {
    // The $Orvi object (Review class) is instantiated in includes.php.
    // We just need to check if it's available.
    if (!isset($Orvi) || !($Orvi instanceof Review)) {
        // If not, try to instantiate it. This is a fallback.
        // Ideally, includes.php should handle all object instantiations.
        if (class_exists('Review') && isset($pdo)) {
            $Orvi = new Review($pdo);
        } else {
            throw new Exception("Review service is not available.");
        }
    }

    // Fetch paginated reviews.
    // The method should return an array with 'reviews' and 'total_reviews'.
    $reviewsData = $Orvi->getPaginatedReviewsByProduct($inventoryItemId, $page, $perPage);

    // 5. Populate the response with the fetched data.
    $response['success'] = true;
    $response['reviews'] = $reviewsData['reviews'];
    $response['total_reviews'] = $reviewsData['total_reviews'];
    $response['message'] = 'Reviews fetched successfully.';

} catch (Exception $e) {
    // Log the detailed error for the developer.
    error_log("Error in fetch_reviews.php: " . $e->getMessage());
    // Provide a generic error message to the user.
    $response['message'] = "An error occurred while fetching reviews.";
}

// 6. Output the final JSON response and terminate the script.
echo json_encode($response);
exit();
?>