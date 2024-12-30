<?php
//check-email-exist


include "conn.php"; // Or preferably, the PDO connection
session_start();
$er = array();

// Using prepared statement to prevent SQL injection
$sql = "SELECT * FROM customer WHERE customer_email = ?";
$stmt = $mysqli->prepare($sql); // Assuming $mysqli is a mysqli object

if ($stmt) {
    $stmt->bind_param("s", $_POST['register_email']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $check2 = $result->num_rows;

        if ($check2 == 1) {
            $er["error_one"] = "Someone has this email already";
            // More robust redirect handling:
            $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'registration.php'; // Default redirect if referer is missing.
            header("Location: $redirect?reply=yes&email_error=1"); // Use a parameter for error signaling
            exit();
        } elseif ($check2 == 0) {
            $_SESSION["r_email"] = $_POST['register_email'];
            $_SESSION['registration']['email'] = $_POST['register_email']; //  Might be redundant with $_SESSION["r_email"]
            $_SESSION['registration_step'] = 1;
            header("Location: registration-second.php");
            exit();
        }
    } else {
        // Handle query execution failure. Log the error.
        error_log("MySQL Error: " . $mysqli->error);
        // Redirect with a generic error message, or display an error on the page.
        header("Location: registration.php?error=db_error");  // Example
        exit();
    }
    $stmt->close();
} else {
    // Handle statement preparation failure. Log the error.
    error_log("MySQL Error: " . $mysqli->error);
    header("Location: registration.php?error=db_error");  // Example
    exit();
}


?>