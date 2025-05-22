<?php
include "conn.php";

require_once 'class/Order.php';

require_once 'class/User.php';
$o = new Order($pdo);

echo $o->sendInvoiceEmail(128);

$logoPath = 'assets/images/goodguy.svg';
// Assumes logo is in goodguy/assets/images/


echo $logoPath;


if (file_exists($logoPath)) {

    // You can set either width, height, or both.
    // Setting only width will let the browser calculate height to maintain aspect ratio.
    echo "<img src='$logoPath' width='150' alt='Goodguy Logo' />"; // Added width and alt

    //$mail->addEmbededImage($logoPath, 'goodguyLogo', 'logo.png');
}


?>