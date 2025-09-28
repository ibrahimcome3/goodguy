<!DOCTYPE html>
<?php
// Start session at the very top to handle redirects and error messages
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "includes.php";

$email = '';
$error = '';

// If user is already logged in, redirect them to the dashboard
if (isset($_SESSION['uid'])) {
    header("Location: user_dashboard_overview.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(filter_input(INPUT_POST, 'register_email', FILTER_SANITIZE_EMAIL));

    // --- Validation ---
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($_POST['agree_policy'])) {
        $error = "You must agree to the privacy policy to continue.";
    } else {
        try {
            // Check if email already exists using the PDO connection from includes.php
            $stmt = $pdo->prepare("SELECT customer_id FROM customer WHERE customer_email = :email");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                $error = "An account with this email address already exists. <a href='login.php'>Please log in</a>.";
            } else {
                // Email is available, store it in the session and proceed to the next step
                $_SESSION["r_email"] = $email;
                $_SESSION['registration_step'] = 1;
                header("Location: registration-second.php");
                exit();
            }
        } catch (PDOException $e) {
            error_log("Email check failed in register.php: " . $e->getMessage());
            $error = "A database error occurred. Please try again later.";
        }
    }
}
?>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Create an Account - Step 1</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
</head>

<body>
    <div class="page-wrapper">
        <?php

        include "header_main.php";
        ?>


        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Register</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page  pb-8 pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">
                    <div class="form-box">
                        <div class="form-tab">
                            <h3 class="text-center">Create Your Account</h3>
                            <p class="text-center">Step 1 of 3: Enter your email to get started.</p>

                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger text-center" role="alert">
                                    <?= $error // HTML is allowed here for the login link ?>
                                </div>
                            <?php endif; ?>

                            <form action="register.php" method="post" class="mt-4">
                                <div class="form-group">
                                    <label for="register-email-2">Your email address *</label>
                                    <input type="email" class="form-control" id="register-email-2" name="register_email"
                                        value="<?= htmlspecialchars($email) ?>" required>
                                </div><!-- End .form-group -->

                                <div class="form-footer">
                                    <button type="submit" class="btn btn-outline-primary-2 btn-block">
                                        <span>CONTINUE</span>
                                        <i class="icon-long-arrow-right"></i>
                                    </button>

                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="agree_policy"
                                            name="agree_policy" required>
                                        <label class="custom-control-label" for="agree_policy">I agree to the
                                            <a href="privacy-policy.php" target="_blank">privacy policy</a> *</label>
                                    </div><!-- End .custom-checkbox -->
                                </div><!-- End .form-footer -->
                            </form>
                            <div class="form-choice">
                                <p class="text-center">Already have an account? <a href="login.php">Log in</a></p>
                            </div>

                        </div><!-- End .form-tab -->
                    </div><!-- End .form-box -->
                </div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->
        <footer class="footer">
            <?php include "footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue.php"; ?>
    <!-- Sign in / Register Modal -->
    <?php include "login-module.php"; ?>
    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>

</html>