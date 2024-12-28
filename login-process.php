<?php
session_start();
include "conn.php";
$er = array();
$noError = null;
if (!empty($_POST))
{ //  checkCookie() ;
    if (!$_POST['singinemail'] | !$_POST['singinpassword'])
    {
            //$er["error_one"] = "Enter Email Address or Password.";
        echo 0;
    } else
    {
        $sql = "SELECT * FROM customer WHERE customer_email = '" . $_POST['singinemail'] ."'";
        $result = $mysqli->query($sql);
        if($result)
        $check2 = mysqli_num_rows($result);
        if($check2 == 0)
        {
            echo 1;
        } else{
                $info = mysqli_fetch_array($result);
                if(md5($_POST['singinpassword']) !== $info['password'])
                {
                    echo 2;
                 
                } else
                {
                    $_SESSION["uid"] = $info['customer_id'];
                    $_SESSION["name"] = $info['customer_fname'];
                    $_SESSION['timeout'] = time();
                    if(isset($_SESSION['next_url'])){
                    echo  $_SERVER['HTTP_REFERER'];
                    }else{
                    echo 10;
                    }
                        
              
                }
            }
        }


    

}

?>