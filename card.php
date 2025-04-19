<?php


# if vendor file is not present, notify developer to run composer install.
require __DIR__ . '/vendor/autoload.php';

use Flutterwave\Controller\PaymentController;
use Flutterwave\EventHandlers\ModalEventHandler as PaymentHandler;
use Flutterwave\Flutterwave;
use Flutterwave\Library\Modal;



# start a session.
session_start();
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // Handle error if .env file is missing or unreadable
    error_log("Error loading .env file: " . $e->getMessage());
    die("Configuration error. Please contact support."); // Or handle more gracefully
}
exit;
try {

    Flutterwave::bootstrap();
    exit;
    $customHandler = new PaymentHandler();
    $client = new Flutterwave();
    $modalType = Modal::POPUP; // Modal::POPUP or Modal::STANDARD
    $controller = new PaymentController($client, $customHandler, $modalType);
    exit;
} catch (\Exception $e) {
    echo $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $request = $_REQUEST;
    $request['redirect_url'] = $_SERVER['HTTP_ORIGIN'] . $_SERVER['REQUEST_URI'];
    try {
        $controller->process($request);
    } catch (\Exception $e) {
        echo $e->getMessage();
    }
}

$request = $_GET;
# Confirming Payment.
if (isset($request['tx_ref'])) {
    $controller->callback($request);
} else {

}
exit();