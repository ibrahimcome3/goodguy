<?php

if (isset($_POST['confirm_password']) && isset($_POST['reset_link_token']) && isset($_POST['user_id'])) { //Added isset checks

    include "../conn.php";
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $token = $_POST['reset_link_token'];
    $id = $_POST['user_id'];

    if ($password !== $confirmPassword) {
        header("Location: ../reset-password.php?user_id=$id&token=$token&error=password_mismatch");
        exit();
    }

    $hashedPassword = password_hash($_POST['confirm_password'], PASSWORD_DEFAULT); //Use password_hash for better security
    $token = $_POST['reset_link_token'];
    $id = $_POST['user_id'];

    $query = mysqli_query($mysqli, "SELECT * FROM `customer` WHERE `reset_link_token`='" . $token . "' and `customer_id`='" . $id . "'");
    if (mysqli_num_rows($query) > 0) {
        mysqli_query($mysqli, "UPDATE customer set password='" . $hashedPassword . "', reset_link_token=NULL, expiry_date=NULL WHERE customer_id='" . $id . "'"); //Use customer_id for update
        header("Location: ../password-reset-success.php?success=1"); // Redirect with success message
        exit(); // Important to stop further execution after redirect
    } else {
        // Handle the error case appropriately, maybe redirect to an error page.
        header("Location: ../reset-password.php?user_id=$id&token=$token&error=invalid_token");
        exit();
    }
}


?>