<?php
require_once "login/Auth.php";
require_once "login/Util.php";

$auth = new Auth();
$db_handle = new DBController();
$util = new Util();


//var_dump($_COOKIE);

// Get Current date, time


// Set Cookie expiration for 1 month
// for 1 month

$isLoggedIn = false;

// Check if loggedin session and redirect if session exists
if (!empty($_SESSION["uid"])) {
    $isLoggedIn = true;
}
// Check if loggedin session exists
else if (!empty($_COOKIE["email"]) && !empty($_COOKIE["random_password"]) && !empty($_COOKIE["random_selector"])) {
    // Initiate auth token verification diirective to false
    $isPasswordVerified = false;
    $isSelectorVerified = false;
    $isExpiryDateVerified = false;

    // Get token for username
    $userToken = $auth->getTokenByemail(email: $_COOKIE["email"], expired: 0);

    // Validate random password cookie with database
    if (password_verify(password: $_COOKIE["random_password"], hash: $userToken[0]["password_hash"])) {
        $isPasswordVerified = true;
    }

    // Validate random selector cookie with database
    if (password_verify($_COOKIE["random_selector"], $userToken[0]["selector_hash"])) {
        $isSelectorVerified = true;
    }

    // check cookie expiration by date
    if ($userToken[0]["expiry_date"] >= $current_date) {
        $isExpiryDareVerified = true;
    }

    // Redirect if all cookie based validation retuens true
    // Else, mark the token as expired and clear cookies
    if (!empty($userToken[0]["id"]) && $isPasswordVerified && $isSelectorVerified && $isExpiryDareVerified) {
        $isLoggedIn = true;
    } else {
        if (!empty($userToken[0]["id"])) {
            $auth->markAsExpired($userToken[0]["id"]);
        }
        // clear cookies
        $util->clearAuthCookie();
    }
}
?>