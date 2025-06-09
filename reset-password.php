<?php
// Start session if needed elsewhere, but not strictly required for this page's core logic
// session_start();

// Include necessary files - ensure includes.php provides the $pdo connection
require_once "includes.php"; // Provides $pdo, other classes, functions like breadcrumbs()
// require_once "conn.php"; // Remove this if includes.php handles the connection ($pdo)

$token = null;
$userId = null;
$showForm = false; // Flag to control form display
$pageError = null; // Variable to hold errors found on this page load

// --- 1. Validate GET Parameters ---
if (!isset($_GET['token']) || empty(trim($_GET['token']))) {
    $pageError = "Password reset token is missing.";
} else {
    $token = trim($_GET['token']);
}

if (!isset($_GET['user_id']) || !filter_var($_GET['user_id'], FILTER_VALIDATE_INT)) {
    $pageError = "User identifier is missing or invalid.";
} else {
    $userId = (int) $_GET['user_id'];
}

// --- 2. Validate Token and Expiry (Only if parameters are present) ---
if (!$pageError && $token && $userId) {
    try {
        // Ensure $pdo is available from includes.php
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new Exception("Database connection is not available.");
        }

        // Prepared statement to check the token's validity
        // *** IMPORTANT: Verify table ('customer') and column names ('reset_link_token', 'customer_id', 'expiry_date') ***
        $stmt = $pdo->prepare("SELECT expiry_date
                               FROM customer
                               WHERE reset_link_token = :token AND customer_id = :user_id
                               LIMIT 1");
        $stmt->execute(['token' => $token, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if token exists and hasn't expired
        if (!$row) {
            $pageError = "Invalid password reset link."; // Token/User ID combo not found
        } elseif (isset($row['expiry_date']) && $row['expiry_date'] < date("Y-m-d H:i:s")) {
            $pageError = "This password reset link has expired.";
        } else {
            // Token is valid and not expired
            $showForm = true;
        }

    } catch (PDOException $e) {
        error_log("Database error validating reset token: " . $e->getMessage());
        $pageError = "A database error occurred. Please try again later.";
    } catch (Exception $e) {
        error_log("General error validating reset token: " . $e->getMessage());
        $pageError = "An unexpected error occurred. Please try again later.";
    }
}

// --- 3. Prepare Feedback Messages (from form submission redirect) ---
$feedbackMessage = '';
$feedbackType = 'danger'; // Default to error
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'password_mismatch':
            $feedbackMessage = 'Passwords do not match. Please try again.';
            break;
        case 'invalid_token': // This might be redundant if initial check fails, but good fallback
            $feedbackMessage = 'Invalid or expired password reset link used during submission.';
            break;
        case 'database':
            $feedbackMessage = 'There was a database error updating your password. Please try again later.';
            break;
        case 'update_failed':
            $feedbackMessage = 'Failed to update password. Please try again.';
            break;
        default:
            $feedbackMessage = 'An unknown error occurred during password update.';
            break;
    }
} elseif (isset($_GET['success'])) {
    $feedbackMessage = 'Your password has been successfully reset. You can now log in.';
    $feedbackType = 'success';
    $showForm = false; // Don't show form again on success
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    <div class="page-wrapper">

        <?php include "header_main.php"; // Use the consolidated header ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); // Assuming this works ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page pb-8 pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">
                    <div class="form-box">
                        <div class="form-tab">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="reset-password-tab" role="tabpanel"
                                    aria-labelledby="reset-password-link">
                                    <h4 class="text-center mb-3">Reset Your Password</h4>

                                    <?php // Display feedback from form submission (success or error) ?>
                                    <?php if ($feedbackMessage): ?>
                                        <div class="alert alert-<?= htmlspecialchars($feedbackType); ?>" role="alert">
                                            <?= htmlspecialchars($feedbackMessage); ?>
                                            <?php if ($feedbackType == 'danger' && !$showForm && !$pageError): ?>
                                                <br><a href="password-reset.php">Request a new reset link?</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php // Display errors found during initial page load (invalid/expired token etc.) ?>
                                    <?php if ($pageError): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?= htmlspecialchars($pageError); ?>
                                            <br><a href="password-reset.php">Request a new reset link?</a>
                                        </div>
                                    <?php endif; ?>


                                    <?php // Show the form ONLY if the token was valid and not expired, and no success message ?>
                                    <?php if ($showForm && !$pageError && !isset($_GET['success'])): ?>
                                        <form action="process/update-password.php" method="post">
                                            <?php // Pass validated token and user ID securely ?>
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="reset_link_token"
                                                value="<?= htmlspecialchars($token); ?>">

                                            <div class="form-group">
                                                <label for="new-password">New Password *</label>
                                                <input type="password" name="password" class="form-control"
                                                    id="new-password" required minlength="8"
                                                    aria-describedby="passwordHelp">
                                                <small id="passwordHelp" class="form-text text-muted">Minimum 8
                                                    characters.</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="confirm-password">Confirm New Password *</label>
                                                <input type="password" name="confirm_password" class="form-control"
                                                    id="confirm-password" required minlength="8">
                                            </div>
                                            <div class="form-footer">
                                                <button type="submit" name="submit" class="btn btn-outline-primary-2">
                                                    <span>Reset Password</span>
                                                    <i class="icon-long-arrow-right"></i>
                                                </button>
                                            </div>
                                        </form>
                                    <?php elseif (!isset($_GET['success']) && !$pageError): ?>
                                        <?php // Fallback message if form shouldn't show but no specific error was set ?>
                                        <div class="alert alert-warning" role="alert">
                                            Cannot display password reset form at this time.
                                            <br><a href="password-reset.php">Request a new reset link?</a>
                                        </div>
                                    <?php endif; ?>

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
    <?php include "mobile-menue.php"; ?>

    <!-- Login Modal -->
    <?php // include "login-module.php"; // Usually not needed on reset page ?>

    <!-- Plugins JS File -->
    <?php include "jsfile.php"; ?>

</body>

</html>