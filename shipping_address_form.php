<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "includes.php"; // Main include file

// Ensure user is logged in
if (!isset($_SESSION['uid'])) { // Assuming 'uid' is your session variable for user ID
    $_SESSION['error_message'] = "You must be logged in to manage your shipping addresses.";
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['uid'];
$error_messages = [];
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']); // Clear success message after retrieving

$address_data = [
    'shipping_address_no' => null,
    'address1' => '',
    'address2' => '',
    'city' => '',
    'state' => '', // This will store state_id
    'zip' => '',
    'country' => 'NIGERIA', // Default country if applicable
    'shipping_area_id' => '', // This will store area_id
    'ship_cost' => '' // Or 0.00
];
$page_title = "Add New Shipping Address";
$form_action = "shipping_address_form.php";

// Ensure User and Shipment classes are available and instantiated
try {
    if (!isset($pdo)) {
        throw new Exception("PDO connection not available.");
    }
    if (!isset($user) || !($user instanceof User)) {
        $user = new User($pdo); // Assumes User constructor takes PDO
    }
    if (!isset($shipment) || !($shipment instanceof Shipment)) {
        $shipment = new Shipment($pdo); // Assumes Shipment constructor takes PDO
    }
} catch (Exception $e) {
    error_log("Error setting up objects in shipping_address_form.php: " . $e->getMessage());
    die("A critical error occurred. Please try again later or contact support.");
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if editing an existing address
$shipping_address_no_edit = isset($_GET['shipping_address_no']) ? filter_var($_GET['shipping_address_no'], FILTER_VALIDATE_INT) : null;

if ($shipping_address_no_edit) {
    // Use the PDO-based method from the User class
    $existing_address = $user->getShippingAddressById($shipping_address_no_edit, $customer_id);
    if ($existing_address) {
        $address_data = $existing_address;
        // Ensure state and shipping_area_id are correctly populated from DB
        $address_data['state'] = $existing_address['state'] ?? ''; // Assuming 'state' column stores state_id
        $address_data['shipping_area_id'] = $existing_address['shipping_area_id'] ?? ''; // Assuming 'shipping_area_id' column stores area_id
        $page_title = "Edit Shipping Address";
        $form_action = "shipping_address_form.php?shipping_address_no=" . $shipping_address_no_edit;
    } else {
        $_SESSION['error_message'] = "Shipping address not found or you do not have permission to edit it.";
        header("Location: manage_shipping_addresses.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_messages[] = "Invalid request. Please try again.";
    } else {
        // Sanitize and assign POST data
        $address_data['address1'] = filter_input(INPUT_POST, 'address1', FILTER_SANITIZE_STRING);
        $address_data['address2'] = filter_input(INPUT_POST, 'address2', FILTER_SANITIZE_STRING);
        $address_data['city'] = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
        // State is now selected from a dropdown with state_id as value
        $address_data['state'] = filter_input(INPUT_POST, 'state', FILTER_VALIDATE_INT);
        $address_data['zip'] = filter_input(INPUT_POST, 'zip', FILTER_SANITIZE_STRING);
        $address_data['country'] = filter_input(INPUT_POST, 'country', FILTER_SANITIZE_STRING);
        // Shipping area is now selected from a dropdown with area_id as value
        $address_data['shipping_area_id'] = filter_input(INPUT_POST, 'shipment', FILTER_VALIDATE_INT);
        // Ship cost might be derived from the selected area, or manually entered if applicable
        // For now, let's assume it's manually entered or set to 0 if not provided
        $address_data['ship_cost'] = filter_input(INPUT_POST, 'ship_cost', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) ?? 0.00;

        // Basic Validation
        if (empty($address_data['address1']))
            $error_messages[] = "Address Line 1 is required.";
        if (empty($address_data['city']))
            $error_messages[] = "City is required.";
        if (empty($address_data['zip']))
            $error_messages[] = "ZIP/Postal Code is required.";
        if (empty($address_data['country']))
            $error_messages[] = "Country is required.";
        if (!$address_data['state'] || $address_data['state'] <= 0)
            $error_messages[] = "Please select a valid State.";
        if (!$address_data['shipping_area_id'] || $address_data['shipping_area_id'] <= 0)
            $error_messages[] = "Please select a valid Shipping Area.";

        if (empty($error_messages)) {
            // Use the PDO-based methods from the User class
            if ($shipping_address_no_edit) { // Update existing address
                if ($user->updateShippingAddress($shipping_address_no_edit, $customer_id, $address_data)) {
                    $_SESSION['success_message'] = "Shipping address updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to update shipping address.";
                }
            } else { // Add new address
                if ($user->addShippingAddress($customer_id, $address_data)) {
                    $_SESSION['success_message'] = "New shipping address added successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to add new shipping address.";
                }
            }
            // Regenerate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: manage_shipping_addresses.php");
            exit();
        }
    }
    // Regenerate CSRF token on failed POST too
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($page_title) ?> - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>
        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">

                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <h2 class="text-center mb-4"><?= htmlspecialchars($page_title) ?></h2>

                    <?php if (!empty($error_messages)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($error_messages as $msg): ?>
                                    <li><?= htmlspecialchars($msg); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= htmlspecialchars($form_action); ?>" method="POST" class="form-card">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-group">
                            <label for="address1">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address1" name="address1"
                                value="<?= htmlspecialchars($address_data['address1']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="address2">Address Line 2</label>
                            <input type="text" class="form-control" id="address2" name="address2"
                                value="<?= htmlspecialchars($address_data['address2']); ?>">
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="city">Town / City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="city" name="city"
                                    value="<?= htmlspecialchars($address_data['city']); ?>" required>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="state">State <span class="text-danger">*</span></label>
                                <select name="state" id="state" class="form-control" required>
                                    <option value="">select state </option>
                                    <?php
                                    // Assuming Shipment class and get_shipment_state method exist and use PDO
                                    if (isset($shipment)) {
                                        try {
                                            $states = $shipment->get_shipment_state(); // Assumes this returns an array of ['state_id' => ..., 'state_name' => ...]
                                            foreach ($states as $state_row) {
                                                $selected = ($address_data['state'] == $state_row['state_id']) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($state_row['state_id']) . "' " . $selected . ">" . htmlspecialchars($state_row['state_name']) . "</option>";
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error fetching states for dropdown: " . $e->getMessage());
                                            echo "<option value=''>Error loading states</option>";
                                        }
                                    } else {
                                        echo "<option value=''>Shipment class not available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="shipment" style="color: blue;">Select shipping area <span
                                        class="text-danger">*</span></label>
                                <select name="shipment" id="shipment" class="form-control" required>
                                    <!-- Options will be loaded by JavaScript based on state selection -->
                                    <option value="">select shipping area</option>
                                    <?php
                                    // If editing and state/area are already set, load initial areas
                                    if ($shipping_address_no_edit && !empty($address_data['state']) && isset($shipment)) {
                                        try {
                                            $areas = $shipment->get_shipping_area_by_state($address_data['state']); // Assumes this returns array of ['area_id' => ..., 'area_name' => ..., 'area_cost' => ...]
                                            foreach ($areas as $area_row) {
                                                $selected = ($address_data['shipping_area_id'] == $area_row['area_id']) ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($area_row['area_id']) . "' shipment-price='" . htmlspecialchars($area_row['area_cost']) . "' " . $selected . ">" . htmlspecialchars($area_row['area_name']) . " (N" . htmlspecialchars(number_format($area_row['area_cost'], 2)) . ")</option>";
                                            }
                                        } catch (Exception $e) {
                                            error_log("Error fetching initial areas for dropdown: " . $e->getMessage());
                                            echo "<option value=''>Error loading areas</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-sm-6 form-group">
                                <label for="zip">Postcode / ZIP <span class="text-danger">*</span></label>
                                <input type="text" name="zip" value="<?= htmlspecialchars($address_data['zip']); ?>"
                                    class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 form-group">
                                <label for="country">Country <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="country" name="country"
                                    value="<?= htmlspecialchars($address_data['country']); ?>" required>
                                <!-- Or use a select dropdown for countries -->
                            </div>
                            <div class="col-sm-6 form-group" style="display: none;">
                                <!-- This field is hidden, assuming ship_cost is determined by the selected area -->
                                <label for="ship_cost">Ship Cost (Numeric)</label>
                                <input type="text" class="form-control" id="ship_cost" name="ship_cost"
                                    value="<?= htmlspecialchars($address_data['ship_cost']); ?>"
                                    placeholder="e.g., 10.50">
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit"
                                class="btn btn-primary"><?= $shipping_address_no_edit ? 'Save Changes' : 'Add Address'; ?></button>
                            <a href="manage_shipping_addresses.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
        <?php include "footer.php"; ?>
    </div>

    <?php include "jsfile.php"; ?>


</body>
<script>


    $(document).ready(function () {

        var initial_state_id = $('#state').val();

        var initial_shipping_area_id = '<?= $address_data['shipping_area_id'] ?? '' ?>'; // Get initial area ID if editing

        // Function to load shipping areas via AJAX
        function loadShippingAreas(state_id, selected_area_id = '') {
            if (state_id === "" || state_id === "-1") { // Check if a valid state is selected
                $("#shipment").html("<option value=''>select shipping area</option>"); // Reset shipment dropdown
                return;
            }

            $.ajax({
                url: 'select-state.php', // The PHP script that fetches areas for the given state
                type: 'POST',
                dataType: 'json', // Expecting a JSON response from the server
                data: { state_id: state_id }, // Data to send to the server (the selected state ID)
                success: function (data) { // Function to execute if the AJAX call is successful
                    console.log(data);
                    var options = "<option value=''>select shipping area</option>";
                    console.log('AJAX success. Data received:', data); // Changed to log the actual received data
                    // 'data' is the JSON object returned from 'select-state.php'
                    // It's expected to be in a format like: { "area_id1": {"area_name": "Name1", "area_cost": "10.00"}, "area_id2": {"area_name": "Name2", "area_cost": "12.50"}, ... }
                    $.each(data, function (area_id, area_info) {
                        var selected = (area_id == selected_area_id) ? 'selected' : ''; // Check if this area should be pre-selected (for editing)
                        var area_text = area_info.area_name + " (N" + parseFloat(area_info.area_cost).toFixed(2) + ")";
                        options += "<option value='" + area_id + "' shipment-price='" + area_info.area_cost + "' " + selected + ">" + area_text + "</option>";
                    });
                    $("#shipment").html(options); // Populate the shipment (area) dropdown with the new options

                    // If editing and an area was pre-selected, trigger change to potentially set ship_cost
                    if (selected_area_id) {
                        $("#shipment").trigger('change');
                    }
                },
                error: function (xhr, status, error) { // Function to execute if the AJAX call fails
                    console.error("Error loading shipping areas:", status, error);
                    $("#shipment").html("<option value=''>Error loading areas</option>");
                }
            });
        }

        // Event listener for when the state dropdown changes
        $("#state").change(function () {
            var state_id = $(this).val(); // Get the newly selected state ID

            loadShippingAreas(state_id); // Call the function to load shipping areas for this state
        });

        // Initial load for edit mode:
        // If a state is already selected (e.g., when editing an address), load its shipping areas
        if (initial_state_id && initial_state_id !== "" && initial_state_id !== "-1") {
            loadShippingAreas(initial_state_id, initial_shipping_area_id);
        }

        // Update hidden ship_cost field based on selected area
        $("#shipment").change(function () {
            var selectedOption = $(this).find('option:selected');
            var shipCost = selectedOption.attr('shipment-price');
            $('#ship_cost').val(shipCost || 0.00); // Set to 0.00 if no price attribute
        });

        // Trigger change on shipment dropdown on page load if an area was pre-selected
        // This ensures the hidden ship_cost field is populated when editing
        if (initial_shipping_area_id) {
            $("#shipment").trigger('change');
        }
    });

</script>

</html>