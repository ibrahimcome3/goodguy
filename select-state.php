<?php
require_once "includes.php"; // Use require_once for critical files

header('Content-Type: application/json'); // Set the content type to JSON

$response = []; // Initialize an empty array for the response

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['state_id'])) {
    $stateId = filter_var($_POST['state_id'], FILTER_VALIDATE_INT);

    if ($stateId && $stateId > 0) {
        try {
            // Ensure PDO and Shipment class are available
            if (!isset($pdo)) {
                throw new Exception("PDO connection not available in select-state.php");
            }
            if (!isset($shipment) || !($shipment instanceof Shipment)) {
                // Attempt to instantiate if not already done by includes.php
                if (class_exists('Shipment')) {
                    $shipment = new Shipment($pdo);
                } else {
                    throw new Exception("Shipment class not available in select-state.php");
                }
            }

            // Fetch shipping areas using the method from your Shipment class
            // This method should return an array of associative arrays: [['area_id' => ..., 'area_name' => ..., 'area_cost' => ...], ...]
            $areas = $shipment->get_shipping_area_by_state($stateId);

            //var_dump($areas);
            // exit;
            // Format the data for the AJAX response as expected by the JavaScript
            foreach ($areas as $area) {
                $response[$area['area_id']] = [
                    'area_name' => $area['area_name'],
                    'area_cost' => $area['area_cost']
                ];
            }
        } catch (Exception $e) {
            error_log("Error in select-state.php: " . $e->getMessage());
            // Optionally, you could add an error field to the JSON response
            // $response['error'] = 'Could not load shipping areas.';
        }
    }
}

echo json_encode($response); // Output the JSON response