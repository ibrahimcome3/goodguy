<?php
session_start();
require_once "conn.php"; // Your database connection file
require_once "includes.php"; // Include any necessary files
require_once "login/Auth.php"; // Your authentication class
require_once "login/Util.php"; // Your utility functions

// Instantiate classes
$auth = new Auth();
$db_handle = new DBController(); // Assuming this is your database class
$util = new Util();

$current_time = time();
$cookie_expiration_time = $current_time + (30 * 24 * 60 * 60); // 30 days

// Validate login from cookies (if any)
require_once "login/authCookieSessionValidate.php";

if ($isLoggedIn) {
    // Redirect to appropriate page after successful cookie validation

    var_dump($_SESSION);
    echo "you are logged in pleace <a href='logout.php'>click</a> here to log out";
    // if (isset($_SESSION['last_viewed_product'])) {
    //     header("Location: product-detail.php?id=" . $_SESSION['last_viewed_product']);
    // } else {
    //     header("Location: index.php");
    // }
    exit;
}

$message = ""; // Initialize message variable

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $email = filter_input(INPUT_POST, "singinemail", FILTER_SANITIZE_EMAIL); // Sanitize email input
    $password = $_POST["singinpassword"];

    try {
        $user = $auth->getMemberByemail(email: $email);

        if ($user && password_verify($password, $user[0]["password"])) {
            $_SESSION["uid"] = $user[0]["customer_id"];
            $user_id = $user[0]["customer_id"];

            // Set Auth Cookies if 'Remember Me' checked
            if (isset($_POST["remember"]) && $_POST["remember"] == "on") {
                $random_password = $util->getToken(length: 16);
                $random_selector = $util->getToken(32);

                // Securely hash the random values BEFORE storing them as cookies
                $random_password_hash = password_hash($random_password, PASSWORD_DEFAULT);
                $random_selector_hash = password_hash($random_selector, PASSWORD_DEFAULT);

                $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);

                //Handle existing tokens
                $userToken = $auth->getTokenByemail(email: $email, expired: 0);
                if (!empty($userToken)) {
                    $auth->markAsExpired($userToken[0]["user_id"]);
                }

                $auth->insertToken(email: $email, random_password_hash: $random_password_hash, random_selector_hash: $random_selector_hash, expiry_date: $expiry_date, cuid: $user_id);

                //Set cookies - use secure and httponly flags for better security
                setcookie("email", $email, $cookie_expiration_time, "/", "", true, true);
                setcookie("random_password", $random_password, $cookie_expiration_time, "/", "", true, true);
                setcookie("random_selector", $random_selector, $cookie_expiration_time, "/", "", true, true);
            } else {
                $util->clearAuthCookie();
            }

            // Redirect to appropriate page after successful login
            if (isset($_SESSION['last_viewed_product'])) {
                header("Location: product-detail.php?id=" . $_SESSION['last_viewed_product']);
            } else {
                header("Location: index.php");
            }
            exit; // Important: Add exit to prevent further code execution
        } else {
            $message = "Invalid email or password.";
        }
    } catch (Exception $e) {
        $message = "An error occurred: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>GoodGuyng login Page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>

    <style>
        .error-message {
            text-align: center;
            color: #FF0000;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php
        include "header-for-other-pages.php";

        ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php //echo breadcrumbs(); ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page pb-8  pb-md-12 pt-lg-17 pb-lg-17">
                <div class="container">
                    <div class="form-box">
                        <div class="form-tab">
                            <div class="">
                                <div class="error-message"><?php if (isset($message)) {
                                    echo $message;
                                } ?></div>

                                <p><b>Sign In</b></p>

                                <form action="" method="post">
                                    <div class="form-group">
                                        <label for="singin-email-2">email address *</label>
                                        <input type="text" class="form-control" id="singinemail" name="singinemail"
                                            value="<?php if (isset($_COOKIE["email"])) {
                                                echo $_COOKIE["email"];
                                            } ?>" required>
                                    </div><!-- End .form-group -->

                                    <div class="form-group">
                                        <label for="singin-password-2">Password *</label>
                                        <input type="password" class="form-control" id="singinpassword"
                                            name="singinpassword" value="<?php if (isset($_COOKIE["random_password"])) {
                                                echo $_COOKIE["random_password"];
                                            } ?>" required>
                                    </div><!-- End .form-group -->

                                    <div class="form-footer" style="margin-bottom: 0px;">
                                        <button type="submit" value="Login" name="login"
                                            class="for-logging-in btn btn-outline-primary-2">
                                            <span>LOG IN</span>
                                            <i class="icon-long-arrow-right"></i>
                                        </button>

                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" name="remember"
                                                id="signin-remember-2" <?php if (isset($_COOKIE["email"])) { ?> checked
                                                <?php } ?> />
                                            <label class="custom-control-label" for="signin-remember-2">Remember
                                                Me</label>
                                        </div><!-- End .custom-checkbox -->

                                        <a href="password-reset.php" class="forgot-link">Forgot Your Password?</a>
                                        <a href="register.php" class="forgot-link"><span style="color: blue;">Dont have
                                                an account with us, sign up</span></a>
                                    </div><!-- End .form-footer -->
                                </form>
                            </div><!-- .End .tab-pane -->

                        </div><!-- End .form-tab -->
                    </div><!-- End .form-box -->
                </div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->


        <footer class="footer">

            <?php include "footer.php"; ?>

        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
    <?php include "mobile-menue.php"; ?>

    <!-- Sign in / Register Modal -->
    <?php include "login-module.php"; ?>

    <!-- Sign in / Register Modal -->

    <!-- Sign in / Register Modal -->
    <!-- Plugins JS File -->

    <?php include "jsfile.php"; ?>

    <script>
        $(document).ready(function () {
            /*
            $("button.for-logging-in").click(function(event){
            
             event.preventDefault();
                  $("#err").empty();
                  var email = $('#singinemail').val();
                  var password  = $('#singinpassword').val();
                  var err = "";
                   
                $.ajax({
                   type: "POST",
                   url: 'login-process.php',
                   dataType: "json",
                   data:  { singinemail : email, singinpassword : password },
                   success: function(data)
                   {
                      if (data.path) {
                        window.location = data.path;
                      }
                      else if(data.error_three === 'Incorrect Password')
                      {
                        alert('Incorrect password');
                      }else if(data.error_two === 'Incorrect Email')
                      {
                        alert('Email id not found');
                      }else{
                            window.location = data.path;
                      }
                   }
               });
             });
            
            */

        });
    </script>
</body>


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

</html>