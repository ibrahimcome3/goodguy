<?php
include 'includes.php';
include 'class/Outuser.php';

$password1 = $_POST['p1'];
$password2 = $_POST['p2'];

if ($password1 !== $password2) {
  // Use session variable for error message
  $_SESSION['password_error'] = "Passwords do not match.";
  header("Location: password-registration.php");
  exit();
}

if (!isset($_SESSION['registration']) || !is_array($_SESSION['registration'])) {  //Important Check
  // Handle the case where registration data is missing from the session
  error_log("Registration data missing from session in password-reg-processor.php"); // Log the error
  header("Location: registration.php"); // Or an appropriate error page
  exit();
}

$hashed_password = password_hash($password1, PASSWORD_DEFAULT); // Hash the password


$new = new Outuser();

// Pass the hashed password to new_user (Modify new_user method accordingly).
$last_id = $new->new_user($hashed_password);


if (is_numeric($last_id)) {
  switch ($_SESSION['registration']['is_this_your_Shipping_address']) {
    case "on":
      if ($new->add_shipping_address($last_id)) {
        $_SESSION["uid"] = $last_id;
        $new->unset_session(); //Unset registration session variables
        header("Location: dashboard.php");
        exit();
      } else {
        // Handle shipping address addition failure.  Provide more specific feedback
        $_SESSION['error_message'] = "Error adding shipping address. Please try again later.";
        header("Location: password-registration.php"); // Redirect back with error message
        exit();
      }
      break;

    case "off":

      $_SESSION["uid"] = $last_id;//Set session id if the customer choose to register shipping address later.
      $new->unset_session();//Unset session variables responsible for registration.
      header("Location: dashboard.php");
      exit();
      break;
    default:
      // Handle invalid input for "is_this_your_shipping_address"
      $_SESSION['error_message'] = "Invalid shipping address selection.";
      header("Location: password-registration.php"); // Redirect back with an error message
      exit();

  }
} else {
  // Handle user creation failure. Provide more specific feedback. Log the error.
  error_log("Error creating new user in password-reg-processor.php: " . $last_id); // Assuming $last_id might contain an error message.
  $_SESSION['error_message'] = "Error creating account. Please try again later."; // Or a more specific error message if possible.
  header("Location: password-registration.php");
  exit();
}

?>