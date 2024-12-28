<?php 
   class User extends Connn
{
   public $user_id;
   public $user_email;
   public $user_address;
   
   
   function  __construct(){
      parent::__construct();
      $this->user_id =  $_SESSION['uid'];
      $pdo = $this->dbc;
      $sql = "SELECT * FROM `customer` WHERE `customer_id` =  ".$this->user_id;
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      $this->user_email =  $row['customer_email'];
      $this->user_address = $row['customer_address1']." ". $row['customer_address2'];
      
   }   
   

    function get_user_records(){
      $pdo = $this->dbc;
      $sql = "SELECT * FROM `customer` WHERE `customer_id` =  ".$this->user_id;
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      return $row;
   }
   
   
    function get_address(){
        return $this->user_address;
        
    }
   function get_email(){
       return $this->user_email;
   }

   function update_user_credentials($fname, $lname, $display_name, $email_address){
   $sql = "UPDATE `customer` SET `customer_fname`='$fname',`customer_lname`='$lname',`customer_email`='$email_address', `customer_display_name`='$display_name'  WHERE `customer_id` = ".$this->user_id;       
   $pdo = $this->dbc;
   $stmt = $pdo->query($sql);
   if($stmt){
      return true;
   }else{
     return false;
   }
   }
   
   function update_user_credentials_password($fname, $lname, $display_name, $email_address, $password_){
   $sql = "UPDATE `customer` SET `customer_fname`='$fname',`customer_lname`='$lname',`customer_email`='$email_address', `customer_display_name`='$display_name' , password = '$password_' WHERE `customer_id` = ".$this->user_id;       
   $pdo = $this->dbc;
   $stmt = $pdo->query($sql);
   if($stmt){
      return true;
   }else{
     return false;
   }
   }
   
   function get_password(){
   $pdo = $this->dbc;
   $sql = "SELECT password FROM `customer` WHERE `customer_id` =  ".$this->user_id;
   $stmt = $pdo->query($sql);
   $row = $stmt->fetch();
   return $row['password'];    
   }
   
   function get_address_(){
   $pdo = $this->dbc;
   $sql = "select * from shipping_address where customer_id =  ".$this->user_id;
   $stmt = $pdo->query($sql);
   return  $stmt; 
   }
   
   function update_phone_number($phone_no, $phone_id ){
   
   $sql = " UPDATE phonenumber SET `PhoneNumber`= '$phone_no' WHERE `phone_id`=$phone_id";       
   $pdo = $this->dbc;
   $stmt = $pdo->query($sql);
   if($stmt){
      return true;
   }else{
     return false;
   } 
   }
   
      
   function make_phone_number_my_default($phone_id ){
      $pdo = $this->dbc;
      $sql = "UPDATE phonenumber SET default_= '0' WHERE CustomerID =  ".$this->user_id;
      echo $sql;
      $stmt = $pdo->query($sql);
      if($stmt){
      $sql = "UPDATE phonenumber SET default_= '1' WHERE `phone_id`=$phone_id";       
      $stmt = $pdo->query($sql);
      if($stmt){
         return true;
      }else{
        return false;
      } 
      }else{
        return false;  
      }
   }
   
   function get_phone_number(){
   $pdo = $this->dbc;
   $sql = "select * from phonenumber where CustomerID =  ".$this->user_id;
   $stmt = $pdo->query($sql);
   return  $stmt;  
   }
   
   function add_phone_number($phone_numner){
         $pdo = $this->dbc;
         $sql = "INSERT INTO `phonenumber` (`phone_id`, `CustomerID`, `PhoneNumber`, `default_`) VALUES (NULL, '".$this->user_id."', '$phone_numner', '0')";
         $stmt = $pdo->query($sql); 
         if($stmt){
            return  true;
         }else{
            return false;
         }
         
   }
   
      function get_all_phone_number($id)
   {
      $pdo = $this->dbc;
      $sql = "select * from phonenumber where CustomerID =  " . $id;
      $stmt = $pdo->query($sql);
      return $stmt;
   }
   function delete_phone_number($id){
       
   $pdo = $this->dbc;
   $sql = "DELETE FROM `phonenumber` WHERE `phone_id` = $id";
   $stmt = $pdo->query($sql);
   
   if($stmt){
      return true;
   }else{
     return false;
   }
   
   }
   
   function count_phone_number(){
   $pdo = $this->dbc;
   $sql = "select count(*) as c from phonenumber where CustomerID =  ".$this->user_id;
   $stmt = $pdo->query($sql); 
   $row = $stmt->fetch();
   if($row['c'] <= 1)
      return false;
      else
      return true;
   }
}

   