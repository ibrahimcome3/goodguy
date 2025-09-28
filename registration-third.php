<?php
// filepath: c:\wamp64\www\goodguy\registration-third.php
// Start session at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'includes.php';

// --- 1. Security & Session Check ---
// Redirect if user hasn't completed steps 1 and 2
if (!isset($_SESSION['registration_step']) || $_SESSION['registration_step'] < 2 || !isset($_SESSION["r_email"]) || !isset($_SESSION['registration'])) {
    header("Location: register.php");
    exit();
}

// Get registration data from session
$registration_data = $_SESSION['registration'];
$success_message = '';
$account_created = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    // Check password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // If validation passes, create the user account
    if (empty($errors)) {
        try {
            // Start a transaction
            $pdo->beginTransaction();

            // Create the customer record
            $insertCustomer = $pdo->prepare("
                INSERT INTO customer
                (customer_fname, customer_lname, customer_email, customer_phone, password, date_created) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insertCustomer->execute([
                $registration_data['firstname'],
                $registration_data['lastname'],
                $registration_data['email'],
                $registration_data['phone'],
                $hashed_password
            ]);

            $customer_id = $pdo->lastInsertId();

            // Create customer address record - CHANGE TO UPDATE
            $updateAddress = $pdo->prepare("
                UPDATE customer 
                SET customer_address1 = ?, 
                    customer_address2 = ?, 
                    customer_city = ?, 
                    customer_state = ?, 
                    customer_zip = ?
                WHERE customer_id = ?
            ");

            $updateAddress->execute([
                $registration_data['streetaddress1'],
                $registration_data['streetaddress2'] ?? '',
                $registration_data['city'],
                $registration_data['state'],
                $registration_data['zip'],
                $customer_id
            ]);

            // Register the customer's phone number in the phonenumber table
            // Set as default and active since this is their first phone number
            $insertPhone = $pdo->prepare("
                INSERT INTO phonenumber (CustomerID, PhoneNumber, default_, is_active)
                VALUES (?, ?, 1, 1)
            ");
            $insertPhone->execute([
                $customer_id,
                $registration_data['phone']
            ]);

            // Check if the user wants to use this as shipping address
            if (isset($registration_data['is_this_your_Shipping_address'])) {
                // Get the shipping cost for the selected area
                $stmt = $pdo->prepare("SELECT area_cost FROM shipping_areas WHERE area_id = ?");
                $stmt->execute([$registration_data['shipment']]);
                $ship_cost = $stmt->fetchColumn() ?: 0;

                // Insert into shipping_address table
                $insertShippingAddress = $pdo->prepare("
                    INSERT INTO shipping_address 
                    (customer_id, address1, address2, zip, shipping_area_id, city, state, ship_cost, is_default_shipping)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");

                $insertShippingAddress->execute([
                    $customer_id,
                    $registration_data['streetaddress1'],
                    $registration_data['streetaddress2'] ?? '',
                    $registration_data['zip'],
                    $registration_data['shipment'],
                    $registration_data['city'],
                    $registration_data['state'],
                    $ship_cost
                ]);
            }

            // Commit the transaction
            $pdo->commit();

            // Clear registration session data
            unset($_SESSION['registration']);
            unset($_SESSION['registration_step']);
            unset($_SESSION['r_email']);

            // Set success flag and message
            $account_created = true;
            $success_message = "Your account has been created successfully! You can now log in.";

            // After successfully creating account
            if ($account_created) {
                // Get the new user's ID
                $getUserQuery = $pdo->prepare("SELECT customer_id FROM customer WHERE customer_email = ?");
                $getUserQuery->execute([$registration_data['email']]);
                $newUserId = $getUserQuery->fetchColumn();

                // Send email verification
                if ($newUserId) {
                    $user = new User($pdo);
                    $verificationCode = $user->generateEmailVerificationCode($registration_data['email']);
                    if ($verificationCode) {
                        $user->sendVerificationEmail(
                            $registration_data['email'],
                            $verificationCode,
                            $registration_data['firstname']
                        );

                        $success_message .= " Please check your email to verify your account.";
                    }
                }
            }
        } catch (PDOException $e) {
            // Roll back the transaction on error
            $pdo->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Registration Step 3 - Create Password</title>
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
                        <li class="breadcrumb-item active" aria-current="page">Create Password</li>
                    </ol>
                </div>
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <div class="registration-steps text-center mb-4">
                        <div class="step completed">Step 1: Email</div>
                        <div class="step completed">Step 2: Account Details</div>
                        <div class="step active">Step 3: Password</div>
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

                    <?php if ($account_created): ?>
                        <div class="alert alert-success" role="alert">
                            <h4 class="alert-heading">Success!</h4>
                            <p><?= htmlspecialchars($success_message) ?></p>
                            <hr>
                            <p class="mb-0">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="icon-user"></i> Login to Your Account
                                </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="checkout">
                            <div class="container">
                                <form action="registration-third.php" method="post" id="passwordForm">
                                    <div class="row justify-content-center">
                                        <div class="col-lg-9">
                                            <h2 class="checkout-title">Create Your Password</h2>
                                            <p class="text-muted">
                                                You're almost done! Please create a secure password to complete your
                                                registration.
                                            </p>

                                            <div class="form-group">
                                                <label for="password">Password *</label>
                                                <input type="password" id="password" name="password" class="form-control"
                                                    required>
                                                <div class="password-strength bg-secondary"></div>
                                                <div class="password-requirements mt-2">
                                                    <div class="requirement length">At least 8 characters</div>
                                                    <div class="requirement uppercase">At least 1 uppercase letter</div>
                                                    <div class="requirement lowercase">At least 1 lowercase letter</div>
                                                    <div class="requirement number">At least 1 number</div>
                                                    <div class="requirement special">At least 1 special character</div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label for="confirm_password">Confirm Password *</label>
                                                <input type="password" id="confirm_password" name="confirm_password"
                                                    class="form-control" required>
                                                <div class="invalid-feedback">Passwords do not match.</div>
                                            </div>

                                            <div class="form-footer mt-4">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <a href="registration-second.php" class="btn btn-outline-secondary">
                                                            <i class="icon-arrow-left"></i> Back
                                                        </a>
                                                    </div>
                                                    <div class="col-6 text-right">
                                                        <button type="submit" class="btn btn-primary">
                                                            Complete Registration <i class="icon-check"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
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

    <script>
        $(document).ready(function () {
            const passwordInput = $('#password');
            const confirmInput = $('#confirm_password');
            const strengthBar = $('.password-strength');
            const requirements = {
                length: $('.requirement.length'),
                uppercase: $('.requirement.uppercase'),
                lowercase: $('.requirement.lowercase'),
                number: $('.requirement.number'),
                special: $('.requirement.special')
            };

            function checkPasswordStrength() {
                const password = passwordInput.val();

                // Check requirements
                const checks = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };

                // Update requirement indicators
                for (const [key, element] of Object.entries(requirements)) {
                    if (checks[key]) {
                        element.addClass('met');
                    } else {
                        element.removeClass('met');
                    }
                }

                // Calculate strength score (0-4)
                const strength = Object.values(checks).filter(Boolean).length;

                // Update strength bar
                strengthBar.removeClass('bg-danger bg-warning bg-info bg-success');
                strengthBar.css('width', `${strength * 25}%`);

                if (strength === 0) {
                    strengthBar.addClass('bg-secondary').css('width', '0%');
                } else if (strength < 3) {
                    strengthBar.addClass('bg-danger');
                } else if (strength < 4) {
                    strengthBar.addClass('bg-warning');
                } else if (strength < 5) {
                    strengthBar.addClass('bg-info');
                } else {
                    strengthBar.addClass('bg-success');
                }
            }

            function checkPasswordMatch() {
                const password = passwordInput.val();
                const confirmPassword = confirmInput.val();

                if (confirmPassword && password !== confirmPassword) {
                    confirmInput.addClass('is-invalid');
                    return false;
                } else {
                    confirmInput.removeClass('is-invalid');
                    return true;
                }
            }

            // Event listeners
            passwordInput.on('input', function () {
                checkPasswordStrength();
                checkPasswordMatch();
            });

            confirmInput.on('input', checkPasswordMatch);

            $('#passwordForm').on('submit', function (e) {
                checkPasswordStrength();
                if (!checkPasswordMatch()) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return false;
                }

                const password = passwordInput.val();
                if (
                    password.length < 8 ||
                    !/[A-Z]/.test(password) ||
                    !/[a-z]/.test(password) ||
                    !/[0-9]/.test(password) ||
                    !/[^A-Za-z0-9]/.test(password)
                ) {
                    e.preventDefault();
                    alert('Please ensure your password meets all the requirements.');
                    return false;
                }

                return true;
            });
        });
    </script>
</body>

</html>