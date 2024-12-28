<?php
    if(isset($_POST['confirm_password']) && $_POST['reset_link_token'] && $_POST['email']) {
        include "../conn.php";
        $password = md5($_POST['confirm_password']);
        $token = $_POST['reset_link_token'];
        $email = $_POST['email'];

        $query = mysqli_query($mysqli,"SELECT * FROM `customer` WHERE `reset_link_token`='".$token."' and `customer_email`='".$email."'");
        $row = mysqli_num_rows($query);

        if($row){
            mysqli_query($mysqli,"UPDATE customer set  password='" . $password . "', reset_link_token='" . NULL . "', expiry_date='" . NULL . "' WHERE customer_email='" . $email . "'");
            echo '<p>Your password has been updated successfully. <a href="../login.php">Click here to login</a></p>';
        } else {
            echo "<p>Something wrong. Please try again.</p>";
        }
    }
?>