<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Adjust path as needed
include "../conn.php"; // Consider switching to PDO for better security and consistency

if (isset($_POST['submit']) && isset($_POST['email'])) {  // Check both POST variables

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL); // Sanitize email

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Validate email format
        echo "Invalid Email Address. Go back";
        exit();
    }

    // Use prepared statement to prevent SQL injection
    $stmt = $mysqli->prepare("SELECT customer_id FROM customer WHERE customer_email = ?"); // Select only needed data

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc(); // fetch the user id
            $user_id = $row["customer_id"];
            $token = bin2hex(random_bytes(32)); // Generate a more secure token (cryptographically secure)
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour')); // Simpler expiry time calculation

            // Update database with prepared statement
            $update_stmt = $mysqli->prepare("UPDATE customer SET reset_link_token=?, expiry_date=? WHERE customer_id=?");
            if ($update_stmt) {
                $update_stmt->bind_param("ssi", $token, $expiry_time, $user_id);
                $update_stmt->execute();
                $update_stmt->close();

                $link = "localhost/goodguy/reset-password.php?user_id=$user_id&token=$token"; // Include token in link

                // ... (Inside the if ($result->num_rows > 0) block, after updating the database)


                // Use HTML email format with the link embedded:


                $mail = new PHPMailer(true);

                try {
                    //Server settings
                    $mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
                    $mail->isSMTP();
                    $mail->Host = 'smtp.hostinger.com'; //Replace with your host
                    $mail->SMTPAuth = true;
                    $mail->Username = 'care@goodguyng.com'; //Replace with your email
                    $mail->Password = 'Password1@'; //Replace with your password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
                    $mail->Port = 465; //TCP port to connect to; use 587 if you have set `smtp_ssl=tls` in your php.ini

                    //Recipients
                    $mail->setFrom('care@goodguyng.com', 'Goodguyng.com'); //Replace with your email
                    $mail->addAddress('ibrahimcome3@gmail.com'); //Replace with recipient email
                    $mail->addReplyTo('care@goodguyng.com'); //Replace with your email


                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Link';
                    $icon_path = '../assets/images/logo-new.png'; // Path to your icon
                    $icon_data = base64_encode(file_get_contents($icon_path));
                    $icon_mime_type = mime_content_type($icon_path); // Get the MIME type

                    //$mail->Body = '<html><body><p>Click the icon below to reset your password:</p><a href="' . $link . '"><img src="data:' . $icon_mime_type . ';base64,' . $icon_data . '" alt="Reset Password Icon"></a></body></html>';

                    // ... (rest of your PHPMailer code - no need for addEmbeddedImage)

                    $mail->Body = '<img src="data:' . $icon_mime_type . ';base64,' . $icon_data . '" alt="Goodguyng.com icom"><br/>';
                    $mail->Body = $link;
                    $mail->AltBody = "$link";

                    $mail->send();
                    echo 'Password reset link has been send to your email.';
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            } else {
                // Handle update statement preparation failure
                echo "Error preparing SQL update statement: " . $mysqli->error;
            }


        } else {
            echo "Invalid Email Address. Go back";

        }

        $stmt->close();
    } else {
        echo "Error preparing SQL select statement: " . $mysqli->error;
    }

}
?>