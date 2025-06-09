<?php
// No session needed usually just for the request form, unless header requires it.
// If header_main.php requires session, start it here:
// session_start();

// Include necessary files - provides $pdo, classes, functions like breadcrumbs()
require_once "includes.php";

// --- Prepare Feedback Messages (from _recovery.php redirect) ---
$feedbackMessage = '';
$feedbackType = 'danger'; // Default to error

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_email':
            $feedbackMessage = 'The email address provided is not valid. Please check and try again.';
            break;
        case 'email_not_found':
            // Security consideration: Avoid confirming if an email exists.
            // Use a generic message for both not found and mail success.
            // $feedbackMessage = 'No account found with that email address.';
            // Generic message approach:
            // If using generic message, you'd redirect to a success page even if email not found.
            // For now, let's keep it specific for easier debugging, but consider changing later.
            $feedbackMessage = 'If an account exists for this email, a password reset link has been sent (if not found, no email is sent).';
            $feedbackType = 'warning'; // Use warning as it might not be an error
            break;
        case 'database':
            $feedbackMessage = 'A database error occurred. Please try again later or contact support.';
            break;
        case 'mail_failed':
            $feedbackMessage = 'We could not send the password reset email at this time. Please try again later or contact support.';
            break;
        case 'server':
            $feedbackMessage = 'A server error occurred. Please try again later or contact support.';
            break;
        default:
            $feedbackMessage = 'An unknown error occurred.';
            break;
    }
}
// Note: _recovery.php redirects to password-reset-email-sent.php on success,
// so we don't need to handle a success message here.

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Request Password Reset - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">

        <?php include "header_main.php"; // Or header_main.php if consolidated ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php // echo breadcrumbs(); // Uncomment if function exists and is needed ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page pb-8 pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">
                    <div class="form-box"> <?php /* Removed inline style="border: 1px solid blue" */ ?>
                        <div class="form-tab">
                            <ul class="nav nav-pills nav-fill" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active">Reset Your Password</a>
                                    <?php // Made active for display ?>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active"> <?php // Ensure this pane shows ?>

                                    <p class="text-center mt-3">Enter your email address below, and we'll send you a
                                        link to reset your password if an account exists.</p>

                                    <?php // Display feedback messages using Bootstrap alerts ?>
                                    <?php if ($feedbackMessage): ?>
                                        <div class="alert alert-<?= htmlspecialchars($feedbackType); ?> mt-3" role="alert">
                                            <?= htmlspecialchars($feedbackMessage); ?>
                                        </div>
                                    <?php endif; ?>

                                    <form action="process/_recovery.php" method="post" class="mt-3">
                                        <div class="form-group">
                                            <label for="reset-email">Email address *</label>
                                            <input type="email" class="form-control" id="reset-email" name="email"
                                                required> <?php // Changed type to email ?>
                                        </div><!-- End .form-group -->
                                        <div class="form-footer">
                                            <button type="submit" name="submit" class="btn btn-outline-primary-2">
                                                <span>Send Reset Link</span>
                                                <i class="icon-long-arrow-right"></i>
                                            </button>
                                        </div><!-- End .form-footer -->
                                    </form>

                                </div><!-- End .tab-pane -->
                            </div><!-- End .tab-content -->
                        </div><!-- End .form-tab -->
                    </div><!-- End .form-box -->
                </div><!-- End .container -->
            </div><!-- End .login-page -->
        </main><!-- End .main -->

        <footer class="footer">
            <?php include "footer.php" ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div>
    <?php include "mobile-menue.php"; // Assuming this is the standard mobile menu ?>

    <!-- Sign in / Register Modal -->
    <?php // include "login-modal.php"; // Likely not needed here ?>

    <!-- JS Files -->
    <?php include "jsfile.php"; ?>
</body>

</html>