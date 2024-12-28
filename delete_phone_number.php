
<?php  

require_once "includes.php"; 
       if(!isset($_SESSION['uid'])){
			header("Location: login-page.php");
			exit();
	    }
 if($user_->count_phone_number()){ 
		 if($user__ = $user_->delete_phone_number($_GET['phone_id'])){
			header("Location: dashboard.php"); 
		 }else{
			header("Location: dashboard.php?error='unable to delete phone number, pls try again later'");  
		 }
 }else{
    header("Location: dashboard.php?error='You must have one phone number registred'"); 
 }
	 
 ?>