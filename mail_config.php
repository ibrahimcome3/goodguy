<?php
// Mail configuration constants
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_USERNAME', 'care@goodguyng.com');
define('SMTP_PASSWORD', 'Password1@');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_FROM_EMAIL', 'care@goodguyng.com');
define('SMTP_FROM_NAME', 'GoodGuy Shop');
define('SMTP_REPLY_TO', 'care@goodguyng.com');



ini_set('SMTP', 'smtp.hostinger.com'); // Replace with your SMTP server
ini_set('smtp_port', '465'); // Replace with your port
ini_set('smtp_ssl', 'ssl'); // Set to 'ssl' if using SSL.
ini_set('smtp_username', 'care@goodguyng.com'); // Your email address
ini_set('smtp_password', 'Password1@'); // Your password