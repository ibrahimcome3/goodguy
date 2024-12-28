<?php
//check-email-exist

include "conn.php";
session_start();
$er = Array();
        $sql = "SELECT * FROM customer WHERE customer_email = '" . $_POST['register_email'] ."'";
        $result = $mysqli->query($sql);
        if($result){
        $check2 = mysqli_num_rows($result);
        if ($check2 == 1)
        {
            $er["error_one"] = "Someone has this email aready";
            $bits = explode('?',$_SERVER['HTTP_REFERER']);
            $redirect = $bits[0];
            header("Location:". $redirect."?reply=yes");
            exit();
        }elseif($check2 == 0){
            $_SESSION["r_email"] = $_POST['register_email'];
            $_SESSION['registration']['email'] = $_POST['register_email'];
            header("Location: registration-second.php");
        }

}

?>