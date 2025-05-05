<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Use Composer autoloader
require '../vendor/autoload.php';
// Use consistent includes providing $pdo
require_once "../includes.php"; // Provides $pdo (still needed for DB operations)

// --- Hardcoded SMTP Configuration ---
// !!! SECURITY RISK: Avoid hardcoding credentials in production environments !!!
$smtpHost = 'smtp.hostinger.com';
$smtpPort = 465; // Common port for SMTPS/SSL
$smtpSecure = PHPMailer::ENCRYPTION_SMTPS; // Use ENCRYPTION_STARTTLS for port 587
$smtpUsername = 'care@goodguyng.com';
$smtpPassword = 'Password1@'; // <-- VERY SENSITIVE - HIGH RISK! Replace with your actual password.
$fromEmail = 'care@goodguyng.com';
$fromName = 'Goodguyng.com';
// --- End Hardcoded Configuration ---


if (isset($_POST['submit']) && isset($_POST['email'])) {

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../password-reset.php?error=invalid_email"); // Redirect back
        exit();
    }

    try {
        // Ensure $pdo is available from includes.php
        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new Exception("Database connection is not available.");
        }

        // 1. Check if email exists using PDO prepared statement
        // *** IMPORTANT: Verify table ('customer') and column names ('customer_email', 'customer_id') ***
        $stmt = $pdo->prepare("SELECT customer_id FROM customer WHERE customer_email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {

            $user_id = $user["customer_id"];
            $token = bin2hex(random_bytes(32)); // Secure token
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expiry in 1 hour

            // 2. Update database with token and expiry using PDO prepared statement
            // *** IMPORTANT: Verify table ('customer') and column names ('reset_link_token', 'expiry_date', 'customer_id') ***
            $update_stmt = $pdo->prepare("UPDATE customer SET reset_link_token = :token, expiry_date = :expiry WHERE customer_id = :user_id");
            $updateSuccess = $update_stmt->execute([
                'token' => $token,
                'expiry' => $expiry_time,
                'user_id' => $user_id
            ]);

            if ($updateSuccess) {
                // Construct the reset link
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $domain = $_SERVER['HTTP_HOST'];
                $link = $protocol . $domain . "/goodguy/reset-password.php?user_id=" . urlencode((string) $user_id) . "&token=" . urlencode($token);

                // 3. Send Email using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // --- Server settings ---
                    // Use the hardcoded variables defined at the top
                    // $mail->SMTPDebug = SMTP::DEBUG_OFF; // Use DEBUG_OFF for production
                    $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable debug output for production/redirects
                    $mail->isSMTP();
                    $mail->Host = $smtpHost;
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpUsername;
                    $mail->Password = $smtpPassword; // Using hardcoded password
                    $mail->SMTPSecure = $smtpSecure;
                    $mail->Port = (int) $smtpPort;

                    // --- Recipients ---
                    $mail->setFrom($fromEmail, $fromName); // Using hardcoded from details
                    $mail->addAddress($email); // Send to the user requesting reset
                    $mail->addReplyTo($fromEmail, $fromName);

                    // --- Content ---
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request - Goodguyng.com';

                    // Prepare icon data (ensure path is correct)
                    $icon_path = '../assets/images/logo-new.png'; // Adjust path relative to _recovery.php
                    $icon_data = '';
                    $icon_mime_type = '';
                    if (file_exists($icon_path)) {
                        $icon_data = base64_encode(file_get_contents($icon_path));
                        $icon_mime_type = mime_content_type($icon_path);
                    }

                    // Construct HTML Body (remains the same)
                    $mailBody = '<html><head><style>body{font-family: sans-serif;}</style></head><body>';
                    if ($icon_data && $icon_mime_type) {
                        $mailBody .= '<p><img src="data:' . $icon_mime_type . ';base64,' . $icon_data . '" alt="Goodguyng.com icon" style="max-width: 150px; height: auto;"></p>';
                    }
                    $mailBody .= '<p>Hello,</p>';
                    $mailBody .= '<p>We received a request to reset the password for your account. Please click the link below to proceed. This link is valid for 1 hour.</p>';
                    $mailBody .= '<p style="margin: 20px 0;"><a href="' . htmlspecialchars($link) . '" style="background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">Reset Password</a></p>';
                    $mailBody .= '<p>If the button above doesn\'t work, copy and paste this link into your browser:</p>';
                    $mailBody .= '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>';
                    $mailBody .= '<p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>';
                    $mailBody .= '<p>Thanks,<br>The Goodguy Team</p>';
                    $mailBody .= '</body></html>';
                    $mail->Body = $mailBody;

                    // Simple text version (remains the same)
                    $mail->AltBody = "Hello,\nPlease use the following link to reset your password (valid for 1 hour):\n" . $link . "\n\nIf you did not request this, please ignore this email.";

                    $mail->send();

                    // Redirect to success confirmation page
                    header("Location: ../password-reset-email-sent.php");
                    exit();

                } catch (Exception $e) {
                    // Log PHPMailer error and redirect with generic mail error
                    error_log("Mailer Error sending reset email to {$email}: {$mail->ErrorInfo}");
                    header("Location: ../password-reset.php?error=mail_failed");
                    exit();
                }

            } else {
                // Database update failed
                throw new Exception("Failed to update reset token in database.");
            }

        } else {
            // Email not found
            header("Location: ../password-reset.php?error=email_not_found");
            exit();
        }

    } catch (PDOException $e) {
        // Catch PDO database errors
        error_log("Database error during password recovery for {$email}: " . $e->getMessage());
        header("Location: ../password-reset.php?error=database");
        exit();
    } catch (Exception $e) {
        // Catch other general errors (DB connection, etc.)
        error_log("General error during password recovery for {$email}: " . $e->getMessage());
        header("Location: ../password-reset.php?error=server"); // Generic server error
        exit();
    }

} else {
    // Redirect if accessed directly or without required POST data
    header("Location: ../password-reset.php");
    exit();
}
?>