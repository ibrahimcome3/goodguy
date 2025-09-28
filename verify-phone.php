<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes.php';
require_once 'class/Sms.php'; // Make sure this path is correct
use PHPMailer\PHPMailer\Sms;

// Redirect if not logged in
if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit();
}

// Ensure User class is available
if (!isset($user) || !($user instanceof User)) {
    $user = new User($pdo);
}
$sms = new \PHPMailer\PHPMailer\Sms();

$success = false;
$error = '';
$message = '';
$phoneId = isset($_GET['phone_id']) ? filter_var($_GET['phone_id'], FILTER_VALIDATE_INT) : null;
$phoneNumber = '';

if (!$phoneId) {
    $error = "No phone number specified for verification.";
} else {
    // Get phone number details to display
    $phoneData = $user->getPhoneNumberById($phoneId, $_SESSION['uid']);
    if ($phoneData) {
        $phoneNumber = $phoneData['PhoneNumber'];
        if ($phoneData['is_verified']) {
            $success = true;
            $message = "This phone number is already verified.";
        }
    } else {
        $error = "Phone number not found or does not belong to you.";
    }
}

// Process verification code submission or resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && !$success) {
    if (isset($_POST['verify_code'])) {
        $code = $_POST['verify_code'];
        $verificationResult = $user->verifyPhoneNumber($phoneId, $code);

        if ($verificationResult === true) {
            $success = true;
            $message = "Your phone number has been successfully verified!";
        } else {
            $error = $verificationResult; // This will contain the error message from the method
        }
    } elseif (isset($_POST['resend_code'])) {
        $code = $user->generatePhoneVerificationCode($phoneId);
        if (!$code) {
            $error = "Failed to generate verification code. Please try again later.";
        } else {
            // Send SMS
            if ($sms->send($phoneNumber, "Your GoodGuy verification code is: " . $code)) {
                $message = "A new verification code has been sent to your phone.";

                // Also send verification via email as backup
                $userData = $user->getUserById($_SESSION['uid']);
                if ($userData && !empty($userData['customer_email'])) {
                    $user->sendPhoneVerificationEmail(
                        $userData['customer_email'],
                        $phoneNumber,
                        $code
                    );
                }
            } else {
                // SMS failed, but still tell user the code is sent
                // (we'll use email as fallback)
                $message = "A new verification code has been generated. Check your email for the code.";

                // Send email with code
                $userData = $user->getUserById($_SESSION['uid']);
                if ($userData && !empty($userData['customer_email'])) {
                    $user->sendPhoneVerificationEmail(
                        $userData['customer_email'],
                        $phoneNumber,
                        $code
                    );
                } else {
                    $error = "Failed to send verification code. Please try again.";
                }
            }
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
    <title>Verify Your Phone Number - GoodGuy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <style>
        .verification-container {
            max-width: 500px;
            margin: 2rem auto;
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

        .phone-display {
            font-weight: bold;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="edit_profile.php">My Account</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Verify Phone</li>
                    </ol>
                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <div class="verification-container">
                        <h2 class="title text-center mb-3">Verify Your Phone Number</h2>

                        <?php if ($success): ?>
                            <div class="text-center">
                                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                                <i class="icon-check-circle" style="font-size: 48px; color: #28a745;"></i>
                                <p class="mt-3">You can now use this phone number on your account.</p>
                                <a href="edit_profile.php" class="btn btn-outline-primary mt-3">Return to My Account</a>
                            </div>
                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <?php if ($message): ?>
                                <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                            <?php endif; ?>

                            <?php if ($phoneNumber): ?>
                                <div class="text-center mb-4">
                                    <p>We've sent a verification code to:</p>
                                    <p class="phone-display"><?= htmlspecialchars($phoneNumber) ?></p>
                                    <p>Please enter the code below to activate this number.</p>
                                </div>

                                <form action="verify-phone.php?phone_id=<?= htmlspecialchars($phoneId) ?>" method="post">
                                    <div class="form-group">
                                        <label for="verify_code">Verification Code</label>
                                        <input type="text" name="verify_code" class="form-control code-input" maxlength="6"
                                            placeholder="------" required>
                                    </div>

                                    <div class="form-footer d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary">Verify Phone</button>
                                    </div>
                                </form>

                                <form action="verify-phone.php?phone_id=<?= htmlspecialchars($phoneId) ?>" method="post"
                                    class="mt-3 text-center">
                                    <button type="submit" name="resend_code" value="1" class="btn btn-link">Didn't get a code?
                                        Resend</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    Could not load phone number details.
                                </div>
                                <a href="edit_profile.php" class="btn btn-outline-primary">Return to My Account</a>
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
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-module.php"; ?>
    <?php include "jsfile.php"; ?>

    <script>
        $(document).ready(function () {
            $('input[name="verify_code"]').on('input', function () {
                $(this).val($(this).val().replace(/[^0-9]/g, '').substring(0, 6));
            });
        });
    </script>
</body>

</html>