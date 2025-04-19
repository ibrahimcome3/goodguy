<?php
$ch = curl_init('https://api.flutterwave.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Ensure peer verification is enabled
$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    echo 'cURL connection successful!';
}

curl_close($ch);
?>