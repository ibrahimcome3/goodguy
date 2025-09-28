<?php
// filepath: c:\wamp64\www\goodguy\verify_email.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes.php';

$user = new User($pdo);
$success = false;
$error = '';
$message = ''; // Initialize $message variable here
$email = '';

// Handle verification for logged-in user
if (isset($_SESSION['uid'])) {
    $userData = $user->getUserById($_SESSION['uid']);
    if ($userData) {
        $email = $userData['customer_email'];

        // Check if already verified
        if ($user->isEmailVerified($_SESSION['uid'])) {
            $success = true;
            $message = "Your email is already verified.";
        }
    }
}
// Handle verification from email link
elseif (isset($_GET['email'])) {
    $email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
}

// Process verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code']) && !empty($_POST['verify_code']) && !empty($_POST['email'])) {
        $code = $_POST['verify_code'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        $verificationResult = $user->verifyEmail($email, $code);

        if ($verificationResult === true) {
            $success = true;
            $message = "Your email has been successfully verified!";
        } else {
            $error = $verificationResult;
        }
    } else if (isset($_POST['resend_code']) && !empty($_POST['email'])) {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

        // Get user's first name for personalized email
        $stmt = $pdo->prepare("SELECT customer_fname FROM customer WHERE customer_email = ?");
        $stmt->execute([$email]);
        $firstName = $stmt->fetchColumn() ?: '';

        // Generate and send verification code
        $code = $user->generateEmailVerificationCode($email);

        if ($code && $user->sendVerificationEmail($email, $code, $firstName)) {
            $message = "A new verification code has been sent to your email.";
        } else {
            $error = "Failed to send verification code. Please try again.";
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
    <title>Verify Your Email - GoodGuy</title>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <?php include "htlm-includes.php/metadata.php"; ?>
    <style>
        .verification-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .code-input {
            letter-spacing: 0.5em;
            font-size: 1.5em;
            text-align: center;
        }

        .email-display {
            font-weight: bold;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <div class="page-header text-center" style="background-image: url('assets/images/page-header-bg.jpg')">
                <div class="container">
                    <h1 class="page-title">Email Verification</h1>
                </div>
            </div>

            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <?php if (isset($_SESSION['uid'])): ?>
                            <li class="breadcrumb-item"><a href="edit_profile.php">My Account</a></li>
                        <?php endif; ?>
                        <li class="breadcrumb-item active" aria-current="page">Verify Email</li>
                    </ol>
                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <div class="verification-container">
                        <h2 class="title text-center mb-3">Verify Your Email</h2>

                        <?php if ($success): ?>
                            <div class="text-center">
                                <div class="alert alert-success">
                                    <?= $message ?>
                                </div>
                                <i class="icon-check-circle" style="font-size: 48px; color: #28a745;"></i>
                                <p class="mt-3">Your email address has been verified successfully.</p>
                                <?php if (isset($_SESSION['uid'])): ?>
                                    <a href="edit_profile.php" class="btn btn-outline-primary mt-3">Return to My Account</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary mt-3">Log In</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($message): ?>
                                <div class="alert alert-info">
                                    <?= htmlspecialchars($message) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($email): ?>
                                <div class="text-center mb-4">
                                    <p>We've sent a verification code to:</p>
                                    <p class="email-display"><?= htmlspecialchars($email) ?></p>
                                    <p>Please check your inbox (and spam folder) and enter the code below.</p>
                                </div>

                                <form action="verify_email.php" method="post">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                                    <div class="form-group">
                                        <label for="verify_code">Verification Code</label>
                                        <input type="text" name="verify_code" class="form-control code-input" maxlength="6"
                                            placeholder="------" required>
                                    </div>

                                    <div class="form-footer d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary">
                                            Verify Email
                                        </button>
                                        <button type="button" id="resend-btn" class="btn btn-outline-secondary">
                                            Resend Code
                                        </button>
                                    </div>
                                </form>

                                <!-- Separate form for resending code -->
                                <form id="resend-form" action="verify_email.php" method="post" style="display: none;">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                                    <input type="hidden" name="resend_code" value="1">
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    No email address provided for verification.
                                </div>
                                <?php if (isset($_SESSION['uid'])): ?>
                                    <a href="edit_profile.php" class="btn btn-outline-primary">Return to My Account</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-primary">Log In</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
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

    <script>
        $(document).ready(function () {
            // Format verification code input
            $('input[name="verify_code"]').on('input', function () {
                $(this).val($(this).val().replace(/[^0-9]/g, '').substring(0, 6));
            });

            // Resend code button click
            $('#resend-btn').on('click', function () {
                // Submit the hidden resend form
                $('#resend-form').submit();
            });
        });
    </script>
</body>

</html>