<?php
session_start();

require_once "Auth.php";
require_once "Util.php";

$auth = new Auth();
$db_handle = new DBController();
$util = new Util();


$current_time = time();
$current_date = date("Y-m-d H:i:s", $current_time);

$cookie_expiration_time = $current_time + (30 * 24 * 60 * 60);

require_once "authCookieSessionValidate.php";

if ($isLoggedIn) {
    $util->redirect("dashboard.php");
}

if (!empty($_POST["login"])) {
    $isAuthenticated = false;

    $email = $_POST["email"];
    $password = $_POST["member_password"];

    $user = $auth->getMemberByemail(email: $email);


    if (password_verify(password: $password, hash: $user[0]["password"])) {
        $isAuthenticated = true;
    }

    if ($isAuthenticated) {
        $_SESSION["uid"] = $user[0]["customer_id"];
        $user_id = $user[0]["customer_id"];

        // Set Auth Cookies if 'Remember Me' checked
        if (!empty($_POST["remember"])) {
            setcookie("email", $email, $cookie_expiration_time);

            $random_password = $util->getToken(length: 16);
            setcookie("random_password", $random_password, $cookie_expiration_time);

            $random_selector = $util->getToken(32);
            setcookie("random_selector", $random_selector, $cookie_expiration_time);

            $random_password_hash = password_hash($random_password, PASSWORD_DEFAULT);
            $random_selector_hash = password_hash($random_selector, PASSWORD_DEFAULT);

            $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);

            // mark existing token as expired
            $userToken = $auth->getTokenByemail(email: $email, expired: 0);
            if (!empty($userToken[0]["user_id"])) {
                $auth->markAsExpired($userToken[0]["user_id"]);
            }
            // Insert new token
            echo "dddddd";
            $auth->insertToken(email: $email, random_password_hash: $random_password_hash, random_selector_hash: $random_selector_hash, expiry_date: $expiry_date, cuid: $user_id);
        } else {
            $util->clearAuthCookie();
        }
        $util->redirect("dashboard.php");
    } else {
        $message = "Invalid Login";
    }
}
?>
<style>
    body {
        font-family: Arial;
    }

    #frmLogin {
        padding: 20px 40px 40px 40px;
        background: #d7eeff;
        border: #acd4f1 1px solid;
        color: #333;
        border-radius: 2px;
        width: 300px;
    }

    .field-group {
        margin-top: 15px;
    }

    .input-field {
        padding: 12px 10px;
        width: 100%;
        border: #A3C3E7 1px solid;
        border-radius: 2px;
        margin-top: 5px
    }

    .form-submit-button {
        background: #3a96d6;
        border: 0;
        padding: 10px 0px;
        border-radius: 2px;
        color: #FFF;
        text-transform: uppercase;
        width: 100%;
    }

    .error-message {
        text-align: center;
        color: #FF0000;
    }
</style>

<form action="" method="post" id="frmLogin">
    <div class="error-message"><?php if (isset($message)) {
        echo $message;
    } ?></div>
    <div class="field-group">
        <div>
            <label for="login">Email Address</label>
        </div>
        <div>
            <input name="email" type="text" value="<?php if (isset($_COOKIE["email"])) {
                echo $_COOKIE["email"];
            } ?>" class="input-field">
        </div>
    </div>
    <div class="field-group">
        <div>
            <label for="password">Password</label>
        </div>
        <div>
            <input name="member_password" type="password" value="<?php if (isset($_COOKIE["member_password"])) {
                echo $_COOKIE["member_password"];
            } ?>" class="input-field">
        </div>
    </div>
    <div class="field-group">
        <div>
            <input type="checkbox" name="remember" id="remember" <?php if (isset($_COOKIE["member_login"])) { ?> checked
                <?php } ?> /> <label for="remember-me">Remember me</label>
        </div>
    </div>
    <div class="field-group">
        <div>
            <input type="submit" name="login" value="Login" class="form-submit-button"></span>
        </div>
    </div>
</form>