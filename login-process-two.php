<?php


session_start();
include "conn.php";
$er = null;
$noError = null;
$path = array();
$cookie_name = 'ID_my_site';
$previous_url = $_SERVER['HTTP_REFERER'];
require_once "Auth.php";
require_once "Util.php";

$auth = new Auth();
$db_handle = new DBController();
$util = new Util();




// Get Current date, time
$current_time = time();
$current_date = date("Y-m-d H:i:s", $current_time);


$cookie_expiration_time = $current_time + (30 * 24 * 60 * 60);  // for 1 month
$isLoggedIn = false;
if (! empty($_SESSION["uid"])) {
    $isLoggedIn = true;
}


// Check if loggedin session exists
else if (! empty($_COOKIE["member_login"]) && ! empty($_COOKIE["random_password"]) && ! empty($_COOKIE["random_selector"])) {
    // Initiate auth token verification diirective to false
    $isPasswordVerified = false;
    $isSelectorVerified = false;
    $isExpiryDateVerified = false;
    
    // Get token for username
    $userToken = $auth->getTokenByUsername($_COOKIE["member_login"],0);
    
    // Validate random password cookie with database
    if (password_verify(password: $_COOKIE["random_password"], hash: $userToken[0]["password_hash"])) {
        $isPasswordVerified = true;
    }
    
    // Validate random selector cookie with database
    if (password_verify($_COOKIE["random_selector"], $userToken[0]["selector_hash"])) {
        $isSelectorVerified = true;
    }
    
    // check cookie expiration by date
    if($userToken[0]["expiry_date"] >= $current_date) {
        $isExpiryDareVerified = true;
    }
    
    // Redirect if all cookie based validation retuens true
    // Else, mark the token as expired and clear cookies
    if (!empty($userToken[0]["id"]) && $isPasswordVerified && $isSelectorVerified && $isExpiryDareVerified) {
        $isLoggedIn = true;
    } else {
        if(!empty($userToken[0]["id"])) {
            $auth->markAsExpired($userToken[0]["id"]);
        }
        // clear cookies
        $util->clearAuthCookie();
    }
}



if (!empty($_POST))
{ //  checkCookie() ;
    $isAuthenticated = false;
    if (!$_POST['singinemail'] | !$_POST['singinpassword'])
    {
        $er = "Enter Email Address or Password";
         header("Location:".previous_url."?".$er);
    } else
    {
         $user = $auth->getMemberByUsername($username);
            if (password_verify($password, $user[0]["member_password"])) {
                $isAuthenticated = true;
            }
                
        if ($isAuthenticated) {
        $_SESSION["member_id"] = $user[0]["member_id"];
        
        // Set Auth Cookies if 'Remember Me' checked
        if (! empty($_POST["remember"])) {
            setcookie("member_login", $username, $cookie_expiration_time);
            
            $random_password = $util->getToken(16);
            setcookie("random_password", $random_password, $cookie_expiration_time);
            
            $random_selector = $util->getToken(32);
            setcookie("random_selector", $random_selector, $cookie_expiration_time);
            
            $random_password_hash = password_hash($random_password, PASSWORD_DEFAULT);
            $random_selector_hash = password_hash($random_selector, PASSWORD_DEFAULT);
            
            $expiry_date = date("Y-m-d H:i:s", $cookie_expiration_time);
            
            // mark existing token as expired
            $userToken = $auth->getTokenByUsername($username, 0);
            if (! empty($userToken[0]["id"])) {
                $auth->markAsExpired($userToken[0]["id"]);
            }
            // Insert new token
            $auth->insertToken($username, $random_password_hash, $random_selector_hash, $expiry_date);
        } else {
            $util->clearAuthCookie();
        }
        $util->redirect("login.php");
    } else {
        $message = "Invalid Login";
    }
    
    
        $sql = "SELECT * FROM customer WHERE customer_email = '" . $_POST['singinemail'] ."'";
        $result = $mysqli->query($sql);
        if($result)
        $check2 = mysqli_num_rows($result);
	    $previous_url = $_SERVER['HTTP_REFERER'];
        if ($check2 == 0)
        {
            $er = "<span style='color:red;'>Incorrect email or password</span>";
             header("Location: login.php?er=".$er);
			 exit();

        } else
        {
            while ($info = mysqli_fetch_array($result))
            {
                $_POST['singinpassword'] = stripslashes($_POST['singinpassword']);
                $info['password'] = stripslashes($info['password']);
       
                if (md5($_POST['singinpassword']) != $info['password'])
                {
                    $er = "Incorrect email or password";
                    header("Location: login.php?er=".$er);
			        exit();
                } else
                {
                    if(!empty($_POST["remember"])) {
                    //COOKIES for username
    
                    setcookie ("user_email",$_POST["singinemail"],time()+ (10 * 365 * 24 * 60 * 60));
                    //COOKIES for password
                    
                    setcookie ("userpassword",$_POST["singinpassword"],time()+ (10 * 365 * 24 * 60 * 60));
                    }else {
                    if(isset($_COOKIE["user_email"])) {
                    setcookie ("user_login","");
                    if(isset($_COOKIE["userpassword"])) {
                    setcookie ("userpassword","");
                    				}
                    			}
                    }
                    $_SESSION["uid"] = $info['customer_id'];
                    $_SESSION["name"] = $info['customer_fname'];
                    $_SESSION['timeout'] = time();
                    if(isset($_SESSION['next_url'])){
                    $noError = $_SESSION['next_url'];
                    }else{
                    $noError = "index.php";
                    }

               

                    if (!isset($er))
                    {
                       header("Location: $noError");
					   exit();
                    } else
                    {
                       header("Location: login.php?er=".$er);
			           exit();
                        
                    }

                }
            }
        }


    }

}

?>