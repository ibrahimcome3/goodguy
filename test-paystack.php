<?php
require_once 'vendor/autoload.php'; // Include SDK autoloaders

$paystack = new Yabacon\Paystack("sk_test_88a210da5a656b9fb1b72c7204022f69d7e0f8ba");
try {
    $tranx = $paystack->transaction->initialize([
        'amount' => 100,       // in kobo
        'email' => "ibrahimcome3@gmail.com",         // unique to customers
        'reference' => "12334vv33", // unique to transactions
    ]);
} catch (\Yabacon\Paystack\Exception\ApiException $e) {
    print_r($e->getResponseObject());
    die($e->getMessage());
}

// store transaction reference so we can query in case user never comes back
// perhaps due to network issue
echo ($tranx->data->reference);

// redirect to page so User can pay
header('Location: ' . $tranx->data->authorization_url);


?>