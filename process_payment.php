<?php
include "includes.php";
require_once "class/invoice.php";
require_once "class/Order.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Adjust path as needed


ini_set('SMTP', 'smtp.hostinger.com'); // Replace with your SMTP server
ini_set('smtp_port', '465'); // Replace with your port
ini_set('smtp_ssl', 'ssl'); // Set to 'ssl' if using SSL.
ini_set('smtp_username', 'care@goodguyng.com'); // Your email address
ini_set('smtp_password', 'Password1@'); // Your password

// ... rest of your email sending code
?>


<?php

var_dump($_POST);
$mail = new PHPMailer(true);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $paymentMethod = $_POST["paymentMethod"];
    $orderID = $_POST["orderID"];

    $invoice = new Invoice($orderID);
    $order = new Order($orderID);

    $response = array('success' => false, 'message' => '');

    if ($paymentMethod === 'cod') {

        //$mail->SMTPDebug = 3;                              
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER; //Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; //Replace with your host
        $mail->SMTPAuth = true;
        $mail->Username = 'care@goodguyng.com'; //Replace with your email
        $mail->Password = 'Password1@'; //Replace with your password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; //Enable implicit TLS encryption
        $mail->Port = 465; //TCP port to connect to; use 587 if you have set `smtp_ssl=tls` in your php.ini

        $mail->setFrom('care@goodguyng.com', 'Goodguyng.com'); //Replace with your email
        $mail->addAddress($invoice->getCustomerEmail());     // Add a recipient
        //$mail->addAddress('ellen@example.com');               // Name is optional
        //$mail->addReplyTo('info@example.com', 'Information');
        //$mail->addCC('cc@example.com');
        //$mail->addBCC('bcc@example.com');

        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = 'GoodGuy Invoice';
        echo $orderID;
        $mail->Body = $invoice->generateInvoice($orderID); // Generate the invoice content
        //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        if (!$mail->send()) {
            $response['message'] = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        } else {
            // Update order status to "concluded"
            $order->concludeOrder($orderID); // Add this function to your Order class
            $response['success'] = true;
        }
    } else { //Other Payment methods - update the payment method on the order in the database
        //Update Payment Method
        $order->updatePaymentMethod($orderID, $paymentMethod); // Add this function to your Order class
        $response['success'] = true;
    }

    header('Content-type: application/json');
    echo json_encode($response);
}
