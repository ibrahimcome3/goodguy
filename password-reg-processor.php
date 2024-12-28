<?php
include 'includes.php';
include 'class/Outuser.php';

    $password1 = $_POST['p1'];
    $password2 = $_POST['p2'];
   if($password1 === $password2){
     $_SESSION['registration']['password'] = $password1;
   }else{
    echo "Password did not match <a href='password-registration.php'>click here to go back</a>";
    exit(0);
   }
 
   if(isset(  $_SESSION['registration']['password'])){
  
   
      $new = new Outuser();
      $last_id = $new->new_user();

      if(is_numeric($last_id)){
         switch ($_SESSION['registration']['is_this_your_Shipping_address']) {
         case "on":
           if($new->add_shipping_address($last_id)){
           $new->unset_session();
           header("Location: login.php");
           }else echo "an error please try again later";
         break;
         
         case "off":
           header("Location: add-a-shipping-address.php");  
         }
      }
   }

?>