<?php
session_start();
include "includes.php"; // Should provide $pdo or $mysqli connection

// Redirect if user is not logged in
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

$error = null; // Initialize error variable

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['uid']; // Already checked if logged in

    // Retrieve and trim POST data
    $streetaddress1 = trim($_POST['streetaddress1'] ?? '');
    $streetaddress2 = trim($_POST['streetaddress2'] ?? '');
    $country = 'NIGERIA'; // Hardcoded
    $state_id = filter_input(INPUT_POST, 'state', FILTER_VALIDATE_INT); // Get state ID
    $city = trim($_POST['city'] ?? '');
    $zip = trim($_POST['zip'] ?? '');
    $shipping_area_id = filter_input(INPUT_POST, 'shipment', FILTER_VALIDATE_INT); // Get selected area ID

    // --- Validation ---
    if (empty($streetaddress1)) {
        $error = "Address line one is empty";
    } elseif (empty($city)) {
        $error = "City field is empty";
    } elseif (empty($state_id) || $state_id <= 0) { // Check if a valid state was selected
        $error = "Please select a valid State";
    } elseif (empty($zip)) {
        $error = "Zip field is empty";
    } elseif (empty($shipping_area_id) || $shipping_area_id <= 0) { // Check if a valid shipping area was selected
        $error = "Please select a shipping area";
    } else {
        // --- Database Insertion (using Prepared Statements with mysqli) ---
        // Check if $mysqli is available from includes.php
        if (isset($mysqli) && $mysqli instanceof mysqli) {
            // *** IMPORTANT: Ensure your table has the 'shipping_area_id' column ***
            // *** ALSO: Verify the 'state' column name below is correct (should likely be 'state_id') ***
            $sql = "INSERT INTO `shipping_address`
                        (`customer_id`, `address1`, `address2`, `state`, `city`, `zip`, `country`, `shipping_area_id`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // Changed `state` to `state_id` here

            $stmt = $mysqli->prepare($sql);

            if ($stmt) {
                // Bind parameters (i=integer, s=string)
                // customer_id(i), address1(s), address2(s), state_id(i), city(s), zip(s), country(s), shipping_area_id(i)
                $stmt->bind_param(
                    "ississsi", // Ensure this matches the columns in the SQL above
                    $customer_id,
                    $streetaddress1,
                    $streetaddress2,
                    $state_id, // Use the state ID
                    $city,
                    $zip,
                    $country,
                    $shipping_area_id // Use the selected shipping area ID
                );

                if ($stmt->execute()) {
                    // --- Success: Redirect back to previous page or default ---
                    $redirect_url = 'dashboard.php'; // Default fallback page

                    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                        // Use the referring page URL if available
                        $redirect_url = $_SERVER['HTTP_REFERER'];
                        // Optional: Add validation here if you want to ensure it's not an external URL
                        // e.g., check if parse_url($redirect_url, PHP_URL_HOST) === $_SERVER['HTTP_HOST']
                    }

                    header("Location: " . $redirect_url);
                    exit(); // IMPORTANT: Always exit after a header redirect
                    // --- End Redirect Logic ---

                } else {
                    // Execution failed
                    $error = "Database error: Could not save address. Please try again. (" . $stmt->error . ")";
                    error_log("MySQLi execute error: " . $stmt->error . " SQL: " . $sql); // Log detailed error
                }
                $stmt->close();
            } else {
                // Preparation failed
                $error = "Database error: Could not prepare statement. (" . $mysqli->error . ")";
                error_log("MySQLi prepare error: " . $mysqli->error . " SQL: " . $sql); // Log detailed error
            }
        } else {
            // $mysqli connection not found
            $error = "Database connection error. Please contact support.";
            error_log("MySQLi connection object not found in add-a-shipping-address.php");
        }
        // --- End Database Insertion ---
    }
} // End of POST proc processing

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Add Shipping Address - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <style>
        /* Optional: Style for error messages */
        .form-error-message {
            color: #dc3545;
            /* Bootstrap danger color */
            background-color: #f8d7da;
            border-color: #f5c6cb;
            padding: .75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: .25rem;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page pb-8 pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">

                    <?php if ($error): // Display error if set ?>
                        <div class="form-error-message" role="alert">
                            <?= htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Removed static cost display: <div><p>Shipping Cost: N3000 </p><p>Total Cost: 63000</p></div> -->

                    <div class="row justify-content-center">
                        <div class="col-lg-9">
                            <form action="add-a-shipping-address.php" method="post" id="shipping-address-form">
                                <h4>Add New Shipping Address</h4>

                                <label for="streetaddress1">Street address *</label>
                                <input type="text" id="streetaddress1" class="form-control" name="streetaddress1"
                                    placeholder="House number and Street name" required
                                    value="<?= htmlspecialchars($_POST['streetaddress1'] ?? '') ?>">
                                <input type="text" id="streetaddress2" class="form-control" name="streetaddress2"
                                    placeholder="Apartment, suite, unit etc (Optional)"
                                    value="<?= htmlspecialchars($_POST['streetaddress2'] ?? '') ?>">

                                <div class="row">
                                    <div class="col-sm-6">
                                        <label for="city">Town / City *</label>
                                        <input type="text" id="city" name="city" class="form-control" required
                                            value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                                    </div><!-- End .col-sm-6 -->

                                    <div class="col-sm-6">
                                        <label for="state">State *</label>
                                        <select name="state" id="state" class="form-control" required>
                                            <option value="">-- Select State --</option>
                                            <?php
                                            // Assuming Shipment class and get_shipment_state() are available via includes.php
                                            // And assuming get_shipment_state() uses the same DB connection ($mysqli or $pdo)
                                            try {
                                                $ship = new Shipment(); // Ensure Shipment class uses the correct DB connection
                                                $statesResult = $ship->get_shipment_state(); // Should return mysqli_result or PDOStatement
                                            
                                                // Adapt based on whether $statesResult is mysqli_result or PDOStatement
                                                if ($statesResult instanceof mysqli_result) {
                                                    while ($row = $statesResult->fetch_assoc()) {
                                                        $selected = (isset($_POST['state']) && $_POST['state'] == $row['state_id']) ? 'selected' : '';
                                                        echo "<option value=\"" . htmlspecialchars($row['state_id']) . "\" $selected>" . htmlspecialchars($row['state_name']) . "</option>";
                                                    }
                                                } elseif ($statesResult instanceof PDOStatement) {
                                                    while ($row = $statesResult->fetch(PDO::FETCH_ASSOC)) {
                                                        $selected = (isset($_POST['state']) && $_POST['state'] == $row['state_id']) ? 'selected' : '';
                                                        echo "<option value=\"" . htmlspecialchars($row['state_id']) . "\" $selected>" . htmlspecialchars($row['state_name']) . "</option>";
                                                    }
                                                }

                                            } catch (Exception $e) {
                                                echo "<option value=''>Error loading states</option>";
                                                error_log("Error fetching states: " . $e->getMessage());
                                            }
                                            ?>
                                        </select>
                                    </div><!-- End .col-sm-6 -->
                                </div><!-- End .row -->

                                <div class="row">
                                    <div class="col-sm-6">
                                        <label for="zip">Postcode / ZIP *</label>
                                        <input type="text" id="zip" name="zip" class="form-control" required
                                            value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>">
                                    </div><!-- End .col-sm-6 -->

                                    <div class="col-sm-6">
                                        <label for="shipment">Shipping Area *</label>
                                        <select name="shipment" id="shipment" class="form-control" required>
                                            <option value="">-- Select Shipping Area --</option>
                                            <?php
                                            // Pre-populate if state was already selected and form failed validation
                                            $selected_state_id = filter_input(INPUT_POST, 'state', FILTER_VALIDATE_INT);
                                            if ($selected_state_id && $selected_state_id > 0) {
                                                try {
                                                    // Assuming get_shipment_area_by_state exists or is adapted
                                                    // $areasResult = $ship->get_shipment_area_by_state($selected_state_id);
                                                    // For now, let's assume the AJAX will handle repopulation correctly on error
                                                    // Or, if AJAX isn't used on error, fetch areas for the selected state here.
                                                    // Example (if get_shipment_area_by_state exists):
                                                    /*
                                                    $areasResult = $ship->get_shipment_area_by_state($selected_state_id);
                                                    if ($areasResult instanceof mysqli_result) {
                                                        while ($row = $areasResult->fetch_assoc()) {
                                                            $selected = (isset($_POST['shipment']) && $_POST['shipment'] == $row['area_id']) ? 'selected' : '';
                                                            echo "<option value=\"" . htmlspecialchars($row['area_id']) . "\" shipment-price=\"" . htmlspecialchars($row['area_cost']) . "\" $selected>"
                                                                 . htmlspecialchars($row['area_name']) . " (₦" . htmlspecialchars(number_format($row['area_cost'], 2)) . ")"
                                                                 . "</option>";
                                                        }
                                                    } // Add PDO equivalent if needed
                                                    */
                                                } catch (Exception $e) {
                                                    echo "<option value=''>Error loading areas</option>";
                                                    error_log("Error fetching areas for state $selected_state_id: " . $e->getMessage());
                                                }
                                            } else {
                                                // If no state selected yet, or initial load
                                                echo '<option value="">-- Select State First --</option>';
                                            }
                                            ?>
                                        </select>
                                    </div><!-- End .col-sm-6 -->
                                </div><!-- End .row -->

                                <button type="submit" class="btn btn-primary btn-round mt-3">
                                    <span>Save Address</span><i class="icon-long-arrow-right"></i>
                                </button>
                            </form>
                        </div><!-- End .col-lg-9 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div>
    <?php include "mobile-menue.php"; ?>

    <!-- Login Modal -->
    <?php include "login-module.php"; ?>

    <!-- Plugins JS File -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            // --- AJAX to load shipping areas based on state selection ---
            $("#state").change(function () {
                var state_id = $(this).val(); // Get selected state ID
                var shipmentSelect = $("#shipment"); // Target the shipment dropdown

                // Clear current shipment options and show loading message
                shipmentSelect.html('<option value="">Loading areas...</option>');
                shipmentSelect.prop('disabled', true); // Disable while loading

                if (!state_id || state_id === "") {
                    shipmentSelect.html('<option value="">-- Select State First --</option>');
                    shipmentSelect.prop('disabled', false);
                    return; // Exit if no valid state selected
                }

                $.ajax({
                    url: 'select-state.php', // Your endpoint to get areas for a state
                    type: 'POST',
                    dataType: 'json', // Expect JSON response
                    data: { state_id: state_id },
                    success: function (data) {
                        var optionsHtml = '<option value="">-- Select Shipping Area --</option>';
                        if (data && Object.keys(data).length > 0) {
                            // Assuming data is an object like: {"area_id": {"name": "Area Name", "cost": 1000}, ...}
                            // OR {"area_id": "Area Name (N1000)"} -> Adapt $.each accordingly
                            $.each(data, function (areaId, areaDetails) {
                                // Adapt this based on the actual JSON structure from select-state.php
                                var areaName = '';
                                var areaCost = 0;
                                var displayText = '';

                                if (typeof areaDetails === 'object' && areaDetails !== null) {
                                    areaName = areaDetails.name || 'Unknown Area';
                                    areaCost = parseFloat(areaDetails.cost || 0);
                                    displayText = `${areaName} (₦${areaCost.toFixed(2)})`;
                                } else if (typeof areaDetails === 'string') {
                                    // If select-state.php returns pre-formatted string
                                    displayText = areaDetails;
                                    // Try to extract cost for the attribute (optional)
                                    var costMatch = areaDetails.match(/\(₦([\d,.]+)\)/);
                                    if (costMatch && costMatch[1]) {
                                        areaCost = parseFloat(costMatch[1].replace(/,/g, '')) || 0;
                                    }
                                } else {
                                    displayText = 'Invalid Area Data';
                                }

                                optionsHtml += `<option value="${areaId}" shipment-price="${areaCost.toFixed(2)}">${displayText}</option>`;
                            });
                        } else {
                            optionsHtml = '<option value="">-- No areas found for this state --</option>';
                        }
                        shipmentSelect.html(optionsHtml);
                        shipmentSelect.prop('disabled', false); // Re-enable dropdown
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        shipmentSelect.html('<option value="">-- Error loading areas --</option>');
                        shipmentSelect.prop('disabled', false); // Re-enable dropdown
                    }
                });
            });

            // Trigger change event on page load if a state is pre-selected (e.g., due to validation error)
            if ($("#state").val() && $("#state").val() !== "") {
                // Only trigger if the shipment dropdown hasn't been populated by PHP server-side
                if ($("#shipment option").length <= 1 || $("#shipment").val() === "") {
                    $("#state").trigger('change');
                }
            }
        });
    </script>
</body>

</html>