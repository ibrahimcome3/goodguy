<?php
ini_set('SMTP', 'smtp.hostinger.com'); // Replace with your SMTP server
ini_set('smtp_port', '465'); // Replace with your port
ini_set('smtp_ssl', 'ssl'); // Set to 'ssl' if using SSL.
ini_set('smtp_username', 'care@goodguyng.com'); // Your email address
ini_set('smtp_password', 'Password1@'); // Your password

// ... rest of your email sending code
?>


<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path as needed

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
    $mail->Subject = 'Here is the subject';
    $mail->Body = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>