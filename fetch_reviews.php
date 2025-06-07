<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "includes.php"; // Should provide $pdo and class autoloading

header('Content-Type: application/json');
$response = ['reviews' => [], 'success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['itemid'], $_GET['page'], $_GET['perPage'])) {
    $inventoryItemId = filter_var($_GET['itemid'], FILTER_VALIDATE_INT);
    $page = filter_var($_GET['page'], FILTER_VALIDATE_INT);
    $perPage = filter_var($_GET['perPage'], FILTER_VALIDATE_INT);

    if ($inventoryItemId && $page && $perPage && $page > 0 && $perPage > 0) {
        try {
            if (!isset($pdo)) {
                throw new Exception("PDO connection not available.");
            }
            // Ensure Review class ($Orvi) is available
            if (!isset($Orvi) || !($Orvi instanceof Review)) {
                if (class_exists('Review')) {
                    $Orvi = new Review($pdo);
                } else {
                    throw new Exception("Review class not available.");
                }
            }

            // You'll need to create this method in your Review class
            $reviewsData = $Orvi->getPaginatedReviewsByProduct($inventoryItemId, $page, $perPage);

            $response['reviews'] = $reviewsData['reviews'];
            $response['total_reviews'] = $reviewsData['total_reviews']; // Send total for potential re-init if needed, or just for info
            $response['success'] = true;
            $response['message'] = 'Reviews fetched successfully.';

        } catch (Exception $e) {
            error_log("Error in fetch_reviews.php: " . $e->getMessage());
            $response['message'] = "An error occurred while fetching reviews.";
        }
    } else {
        $response['message'] = "Invalid parameters for fetching reviews.";
    }
}

echo json_encode($response);
exit();
?>