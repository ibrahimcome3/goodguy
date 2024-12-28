<?php 
require_once "includes.php"; 

if(user->update_user_credentials($_POST['fname'], $_POST['lname'], $_POST['dname'], $_POST['cemail])){
   echo "Update Sccessfully";
	
}else{
	echo "error";
}


?>