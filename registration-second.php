<!DOCTYPE html>
<?php
// Start session at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'includes.php';
include 'includes.php';

// Instantiate required classes
$shipment = new Shipment($pdo);

// --- 1. Security & Session Check ---
// Redirect if user hasn't completed step 1 or is not supposed to be here.
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 1 || !isset($_SESSION["r_email"])) {
    header("Location: register.php");
    exit();
}

// --- 2. Initialize variables ---
// Pre-populate input from session if user is coming back from a later step, or on form error.
$input = $_SESSION['registration'] ?? [];
// Ensure email from step 1 is always present and readonly
$input['email'] = $_SESSION['r_email'];

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate required fields
    $required_fields = ['firstname', 'lastname', 'phone', 'streetaddress1', 'city', 'state', 'shipment', 'zip'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required";
        }
    }

    // Validate phone format
    // if (!empty($_POST['phone']) && !preg_match("/^\d{11}$/", $_POST['phone'])) {
    //     $errors[] = "Phone number must be exactly 11 digits";
    // }

    $phone = $_POST['phone'] ?? '';
    $user = new User($pdo);
    $phoneValidation = $user->validatePhoneNumber($phone);

    if ($phoneValidation !== true) {
        if (is_string($phoneValidation) && strlen($phoneValidation) === 11) {
            // Auto-correct the format and continue
            $_POST['phone'] = $phoneValidation;
        } else {
            // Add error message
            $errors[] = is_string($phoneValidation) ? $phoneValidation : "Invalid phone number format";
        }
    }

    // If no errors, store in session and proceed
    if (empty($errors)) {
        $_SESSION['registration'] = $_POST;
        $_SESSION['registration_step'] = 2;
        header("Location: registration-third.php");
        exit();
    }

    // If errors, save the input for repopulating the form
    $input = $_POST;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Registration Step 2 - Account Details</title>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <?php include "htlm-includes.php/metadata.php"; ?>
    <style>
        .password-strength {
            margin-top: 5px;
            height: 5px;
            transition: all 0.3s ease;
        }

        .step {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            background-color: #f8f8f8;
            border-radius: 4px;
            position: relative;
        }

        .step::after {
            content: "→";
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
        }

        .step:last-child::after {
            display: none;
        }

        .step.active {
            background-color: #0088cc;
            color: white;
            font-weight: bold;
        }

        .step.completed {
            background-color: #6eb76e;
            color: white;
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 0.85em;
        }

        .requirement {
            color: #6c757d;
        }

        .requirement.met {
            color: #28a745;
        }

        .requirement.met::before {
            content: "✓ ";
        }

        .requirement:not(.met)::before {
            content: "○ ";
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <br />
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="register.php">Register</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Account Details</li>
                    </ol>
                </div>
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <div class="registration-steps text-center mb-4">
                        <div class="step completed">Step 1: Email</div>
                        <div class="step active">Step 2: Account Details</div>
                        <div class="step">Step 3: Password</div>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Please fix the following issues:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="checkout">
                        <div class="container">
                            <form action="registration-second.php" method="post" id="registrationForm">
                                <div class="row justify-content-center">
                                    <div class="col-lg-9">
                                        <h2 class="checkout-title">Personal Information</h2>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>First Name *</label>
                                                <input type="text" name="firstname" class="form-control"
                                                    value="<?= htmlspecialchars($input['firstname'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Last Name *</label>
                                                <input type="text" name="lastname" class="form-control"
                                                    value="<?= htmlspecialchars($input['lastname'] ?? '') ?>" required>
                                            </div>
                                        </div>

                                        <label>Email address *</label>
                                        <input type="email" name="email"
                                            value="<?= htmlspecialchars($input['email'] ?? '') ?>" class="form-control"
                                            readonly>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Phone Number (11 digits) *</label>
                                                <input type="tel" name="phone" id="phone" class="form-control"
                                                    value="<?= htmlspecialchars($input['phone'] ?? '') ?>" required>
                                                <small class="form-text text-muted">Format: 08012345678</small>
                                                <div class="invalid-feedback">Please enter a valid Nigerian phone number
                                                </div>
                                            </div>
                                        </div>

                                        <h2 class="checkout-title mt-4">Delivery Address</h2>
                                        <label>Street address *</label>
                                        <input type="text" class="form-control" name="streetaddress1"
                                            value="<?= htmlspecialchars($input['streetaddress1'] ?? '') ?>"
                                            placeholder="House number and Street name" required>
                                        <input type="text" class="form-control" name="streetaddress2"
                                            value="<?= htmlspecialchars($input['streetaddress2'] ?? '') ?>"
                                            placeholder="Apartments, suite, unit etc ... (optional)">

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label>Town / City *</label>
                                                <input type="text" name="city" class="form-control"
                                                    value="<?= htmlspecialchars($input['city'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>State *</label>
                                                <select name="state" id="state" class="form-control" required>
                                                    <option value="">Select a State...</option>
                                                    <?php
                                                    $states = $shipment->get_shipment_state();
                                                    foreach ($states as $state_row):
                                                        ?>
                                                        <option value="<?= htmlspecialchars($state_row['state_id']) ?>"
                                                            <?= (isset($input['state']) && $input['state'] == $state_row['state_id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($state_row['state_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-sm-6">
                                                <label for="sortby" style="color: blue;">Shipping area *</label>
                                                <select name="shipment" id="shipment" class="form-control" required>
                                                    <option value="">Select a state first</option>
                                                </select>
                                                <small class="form-text text-muted">Shipping cost depends on your
                                                    location</small>
                                            </div>
                                            <div class="col-sm-6">
                                                <label>Postcode / ZIP *</label>
                                                <input type="text" name="zip" class="form-control"
                                                    value="<?= htmlspecialchars($input['zip'] ?? '') ?>" required>
                                            </div>
                                        </div>

                                        <div class="custom-control custom-checkbox mt-3">
                                            <input type="checkbox" name="is_this_your_Shipping_address"
                                                class="custom-control-input" id="checkout-diff-address"
                                                <?= (!isset($input['is_this_your_Shipping_address']) || $input['is_this_your_Shipping_address'] === 'on') ? 'checked' : '' ?>>
                                            <label class="custom-control-label" for="checkout-diff-address">Use this
                                                address for shipping</label>
                                        </div>

                                        <div class="form-footer mt-4">
                                            <div class="row">
                                                <div class="col-6">
                                                    <a href="register.php" class="btn btn-outline-secondary">
                                                        <i class="icon-arrow-left"></i> Back
                                                    </a>
                                                </div>
                                                <div class="col-6 text-right">
                                                    <!-- Changed to submit type so it triggers form validation and submission -->
                                                    <button type="submit" class="btn btn-primary">
                                                        Continue <i class="icon-arrow-right"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer>
    </div>

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div>
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-module.php"; ?>

    <?php include "jsfile.php"; ?>

    <script type="text/javascript">
        $(document).ready(function () {
            function loadShippingAreas(state_id) {
                if (!state_id || state_id === "") {
                    $("#shipment").html("<option value=''>Select a state first</option>");
                    return;
                }

                // Function to load shipping areas via AJAX
                $.ajax({
                    url: 'select-state.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { state_id: state_id },
                    success: function (data) {
                        var options = "<option value=''>Select a shipping area</option>";
                        var selectedShipmentId = "<?= htmlspecialchars($input['shipment'] ?? '') ?>";

                        if (typeof data === 'object' && data !== null) {
                            $.each(data, function (area_id, area_info) {
                                var selected = (area_id == selectedShipmentId) ? "selected" : "";
                                var areaText = area_info.area_name + " (₦" + parseFloat(area_info.area_cost).toFixed(2) + ")";
                                options += "<option value='" + area_id + "' " + selected + ">" + areaText + "</option>";
                            });
                        }
                        $("#shipment").html(options);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error: " + status + " " + error);
                        $("#shipment").html("<option value=''>Error loading areas</option>");
                    }
                });
            }

            // Phone validation
            $('#phone').on('input', function () {
                var phone = $(this).val().replace(/\D/g, ''); // Remove non-digits

                // Auto-format as user types
                if (phone.length > 0) {
                    if (phone.length <= 4) {
                        $(this).val(phone);
                    } else if (phone.length <= 7) {
                        $(this).val(phone.substring(0, 4) + ' ' + phone.substring(4));
                    } else {
                        $(this).val(phone.substring(0, 4) + ' ' + phone.substring(4, 7) + ' ' + phone.substring(7, 11));
                    }
                }

                // Visual validation feedback
                if (phone.length === 11 && phone.startsWith('0')) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });

            // Form validation
            $("#registrationForm").on('submit', function (e) {
                var phone = $('#phone').val().replace(/\D/g, ''); // Remove spaces and any non-digits
                if (phone.length !== 11 || !phone.startsWith('0')) {
                    e.preventDefault();
                    $('#phone').addClass('is-invalid');
                    return false;
                }

                // Ensure a shipping area is selected
                if ($("#shipment").val() === "" || $("#shipment").val() === null) {
                    e.preventDefault();
                    alert("Please select a shipping area");
                    $("#shipment").focus();
                    return false;
                }

                return true;
            });

            // Bind change event to the state dropdown
            $("#state").change(function () {
                loadShippingAreas($(this).val());
            });

            // On page load, if a state is already selected, trigger the loading of areas
            var initialStateId = $("#state").val();
            if (initialStateId) {
                loadShippingAreas(initialStateId);
            }
        });

        /**
 * Validates a Nigerian phone number
 * @param {string} phoneNumber - The phone number to validate
 * @returns {object} - Contains isValid flag and any error message
 */
        function validateNigerianPhone(phoneNumber) {
            // Remove any non-digit characters
            const cleaned = phoneNumber.replace(/\D/g, '');

            // Nigerian numbers are 11 digits, usually starting with 0
            // Or international format with 234 (13 digits)
            if (cleaned.length === 11 && cleaned.startsWith('0')) {
                return { isValid: true };
            } else if (cleaned.length === 13 && cleaned.startsWith('234')) {
                return { isValid: true };
            } else if (cleaned.length === 10 && /^[789]/.test(cleaned)) {
                // Some users might enter without the leading 0
                return { isValid: true };
            }

            return {
                isValid: false,
                message: "Please enter a valid Nigerian phone number (e.g., 08012345678)"
            };
        }
    </script>
</body>

</html>