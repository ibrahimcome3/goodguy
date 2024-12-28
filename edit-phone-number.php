<?php  
require_once "includes.php"; 
       if(!isset($_SESSION['uid'])){
			header("Location: login-page.php");
			exit();
	    }

  // if(preg_match("/^[0-9]{3}-[0-9]{4}-[0-9]{4}$/", $_POST['phone'])) {
	     if(preg_match("/^[0-9]{11}$/", $_POST['phone'])) {
   if($user__ = $user_->update_phone_number($_POST['phone'],$_POST['phone_id'])){
		header("Location: dashboard.php");
		exit();
   }else{
		header("Location: dashboard.php?error='unable to update phone number pls try again later'");
		exit();
       }
	 echo false;
   }else{
	   header("Location: dashboard.php?error='Invalid phone number ex (08022222222) (eleven digits) '");
		exit();
   }

	 
 ?>