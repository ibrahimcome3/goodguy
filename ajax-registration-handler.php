<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Use the main includes file which should provide the PDO connection
require_once 'includes.php';
require_once 'class/Outuser.php';

header('Content-Type: application/json');

// --- Security & Session Check ---
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 1 || !isset($_SESSION["r_email"])) {
    echo json_encode(['success' => false, 'error' => 'Invalid session. Please start registration again.', 'redirect' => 'register.php']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'validate_details') {
    $errors = [];
    $input = [];

    // Sanitize and store input
    $input['firstname'] = trim(filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING));
    $input['lastname'] = trim(filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING));
    $input['streetaddress1'] = trim(filter_input(INPUT_POST, 'streetaddress1', FILTER_SANITIZE_STRING));
    $input['streetaddress2'] = trim(filter_input(INPUT_POST, 'streetaddress2', FILTER_SANITIZE_STRING));
    $input['city'] = trim(filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING));
    $input['state'] = filter_input(INPUT_POST, 'state', FILTER_VALIDATE_INT);
    $input['shipment'] = filter_input(INPUT_POST, 'shipment', FILTER_VALIDATE_INT);
    $input['zip'] = trim(filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING));
    $input['phone'] = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
    $input['is_this_your_Shipping_address'] = isset($_POST['is_this_your_Shipping_address']) ? 'on' : 'off';
    $input['email'] = $_SESSION['r_email']; // Keep email from session

    // --- Validation ---
    if (empty($input['firstname']))
        $errors[] = "First Name is required.";
    if (empty($input['lastname']))
        $errors[] = "Last Name is required.";
    if (empty($input['streetaddress1']))
        $errors[] = "Street Address is required.";
    if (empty($input['city']))
        $errors[] = "Town / City is required.";
    if (empty($input['zip']))
        $errors[] = "Postcode / ZIP is required.";
    if (empty($input['phone']))
        $errors[] = "Phone number is required.";
    if (!preg_match("/^[0-9]{11}$/", $input['phone']))
        $errors[] = "Please enter a valid 11-digit phone number.";
    if (empty($input['state']) || $input['state'] == -1)
        $errors[] = "Please select a state.";
    if (empty($input['shipment']) || $input['shipment'] == -1)
        $errors[] = "Please select a shipping area.";

    if (empty($errors)) {
        $_SESSION['registration'] = $input;
        $_SESSION['registration_step'] = 2;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    exit();
}

if ($action === 'complete_registration') {
    if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2 || !isset($_SESSION['registration'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid step. Please complete your details first.']);
        exit();
    }

    $password = $_POST['p1'] ?? '';
    $password2 = $_POST['p2'] ?? '';

    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
        exit();
    }
    if ($password !== $password2) {
        echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
        exit();
    }

    // All good, proceed with user creation
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $new = new Outuser();
    $last_id = $new->new_user($pdo, $hashed_password);

    if (is_numeric($last_id) && $last_id > 0) {
        if ($_SESSION['registration']['is_this_your_Shipping_address'] === 'on') {
            if (!$new->add_shipping_address($pdo, $last_id)) {
                error_log("Failed to add shipping address for new user ID: " . $last_id);
            }
        }
        $_SESSION["uid"] = $last_id;
        $new->unset_session();
        echo json_encode(['success' => true, 'redirect' => 'user_dashboard_overview.php']);
    } else {
        error_log("User creation failed in ajax-registration-handler.php. Error: " . $last_id);
        echo json_encode(['success' => false, 'error' => 'There was a problem creating your account. Please try again.']);
    }
    exit();
}

// Default response for invalid action
echo json_encode(['success' => false, 'error' => 'Invalid action.']);