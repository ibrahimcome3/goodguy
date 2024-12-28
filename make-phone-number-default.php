<?php  

include "includes.php"; 
       if(!isset($_SESSION['uid'])){
			header("Location: login-page.php");
			exit();
	    }
 
		 if($user__ = $user_->make_phone_number_my_default($_GET['phone_id'])){
			header("Location: dashboard.php"); 
		 }else{
			header("Location: dashboard.php?error='unable to make changes to phone number'");  
		 }
      
	 
 ?>