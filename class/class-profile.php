<?php
 require_once "class/class-track.php";
 class Profile extends Track
{
    protected $_email;    // using protected so they can be accessed
    public $first_name;
    public $second_name;
    protected $_user;
    public $folder_name;
    public $email ;   // stores the user data

    public function __construct()
    {
    
	
    }

      function get_folder_name($id)
    {

     include "conn.php";
     $sql = "SELECT folder_name FROM user WHERE user_id = '$id'";
     $result = $mysqli->query($sql);
     $row = mysqli_fetch_array($result);
     $this->folder_name =  $row[0];
     return   $row[0] ;
     }

	 function get_email($id){
		 include "conn.php";
     $sql = "SELECT email FROM user WHERE user_id = '$id'";
     $result = $mysqli->query($sql);
     $row = mysqli_fetch_array($result);
     return   $row[0] ;
	 }
    function ge_who_i_Follow(){
     include "conn.php";
     $id  = $_SESSION['uid'];
     $sql = "SELECT DISTINCT(user_id) FROM `follow` WHERE follower_id = $id";
     $result = $mysqli->query($sql);
     $column = array();
     while($row = mysqli_fetch_array($result)){
     $column[] = $row[0];
     //Edited - added semicolon at the End of line.1st and 4th(prev) line

     }
     return $column;

    }

    public function get_name(){
    include "conn.php";
     $id  = $_SESSION['uid'];
     $sql = "select concat(CONCAT(UCASE(LEFT(fname, 1)), LCASE(SUBSTRING(fname, 2))),' ', CONCAT(UCASE(LEFT(lname, 1)), LCASE(SUBSTRING(lname, 2)))) as name from user where user_id = $id";
     $result = $mysqli->query($sql);
     $row = mysqli_fetch_array($result);
     return $row[0];

    }
    public function get_fname(){
      return   $this->first_name;
    }

     public function get_profile_picture($id){
     include "conn.php";
	
     $sql = "select profile_image from user where user_id = $id";
     $result = $mysqli->query($sql);
     $row = mysqli_fetch_array($result);
     return $row[0];
    }

    public function get_lname(){
      return   $this->second_name;
    }

    public function get_shared_tracks(){
    include "conn.php";
    $id  = $_SESSION['uid'];
    $sql= "SELECT * FROM shared, audio, user where shared.track_id = audio.audio_id and shared.user_id = user.user_id and shared.user_id IN (SELECT user_id FROM `follow` WHERE follower_id = $id) order by shared.date";
    $result = $mysqli->query($sql);
    return  $result;

    }
         public function get_namex($id){
    include "conn.php";

     $sql = "select concat(CONCAT(UCASE(LEFT(fname, 1)), LCASE(SUBSTRING(fname, 2))),' ', CONCAT(UCASE(LEFT(lname, 1)), LCASE(SUBSTRING(lname, 2)))) as name from user where user_id = '$id'";
     $result = $mysqli->query($sql);
     $row = mysqli_fetch_array($result);
     return $row[0];

    }
    public function update_name($f, $l){
    include "conn.php";
    $id  = $_SESSION['uid'];
    $sql= "UPDATE user SET fname= '$f',lname= '$l' WHERE user_id = $id";
    $result = $mysqli->query($sql);
    if($result){
    return  1;
    }
    }
    public function add_new_email($emil){
    include "conn.php";
    $id  = $_SESSION['uid'];
     $sql= "INSERT INTO `email` (`id`, `user_id`, `primary`, `email`) VALUES (NULL, $id, '0', '$emil');";
    $result = $mysqli->query($sql);
    if($result){
    return  1;
    }
    }


    public function set_profile_image($image_name){
    include "conn.php";
    $id  = $_SESSION['uid'];
    $sql= "UPDATE `user` SET `profile_image`= '$image_name' WHERE user_id = $id";
    $result = $mysqli->query($sql);
    if($result){
    return  1;
    }
    }
    public function get_profile_image(){
    include "conn.php";
    $id  = $_SESSION['uid'];
    $sql= "SELECT concat('voices/',`folder_name`,'/photo/profile_pic/', `profile_image`) as 'profileimage' FROM `user` WHERE user_id = $id";
    $result = $mysqli->query($sql);
    if($result){
     $row = mysqli_fetch_array($result);
     return $row[0];
    }
    }

    public function select_primary_email($id){
     include "conn.php";
     $uid  = $_SESSION['uid'];
    $sql= "UPDATE `email` SET `primary`=0 where user_id = $uid";
    $result = $mysqli->query($sql);
    $sql= "UPDATE `email` SET `primary`=1 where id = $id";
    $result = $mysqli->query($sql);

    if($result){
    $sql= "UPDATE user SET `email`= (select email from email where id = $id )  where user_id = $uid";
    $result = $mysqli->query($sql);
    return  1;
    }

    }

      public function all_mail(){
     include "conn.php";
    $id  = $_SESSION['uid'];
     $sql= "SELECT * FROM `email` WHERE user_id = $id";
    $result = $mysqli->query($sql);

    return  $result;


    }
    
    public function get_account_type($id){
    include "conn.php";
    $sql= "SELECT account_type FROM `user` WHERE user_id = $id";
    $result = $mysqli->query($sql);
    if($result){
    $row = mysqli_fetch_array($result);
    return $row[0];
    }


    }
    public function get_profile_imagex($id){
    include "conn.php";
    $sql= "SELECT concat('voices/',`folder_name`,'/photo/profile_pic/', `profile_image`) as 'profileimage' FROM `user` WHERE user_id = $id";
    $result = $mysqli->query($sql);
    if($result){
     $row = mysqli_fetch_array($result);
     return $row[0];
    }
    }

    public function update_password($oldpassword, $newPassword, $verificationPassword){
    include "conn.php";

    $id  = $_SESSION['uid'];
    $sql= "SELECT password FROM user WHERE user_id = $id";
    $result = $mysqli->query($sql);
    $row = mysqli_fetch_array($result);
    $r = array();
    if($row['password'] !== $oldpassword) { $err =  "incorrect old password";}
    if($verificationPassword !== $newPassword) { $err = "password did not match"; }
    if(strlen($verificationPassword) < 6 ) { $err = "new password should be six characters or more"; }

    if(isset($err) ){
         $r["err"] = $err;

        }else{
      $query= "UPDATE user SET password= '$verificationPassword' where user_id = $id";
     $result = $mysqli->query($query);
     $r["updated"] = "updated";
        }
        echo json_encode($r);

    }

         public function change_password($newPassword){
    include "conn.php";

    $id  = $_SESSION['uid'];
    $sql= "SELECT password FROM user WHERE user_id = $id";
    $result = $mysqli->query($sql);
    $row = mysqli_fetch_array($result);
    $r = array();

    if(strlen($newPassword) < 6 ) { $err = "new password should be six characters or more"; }

    if(isset($err) ){
         $r["err"] = $err;

        }else{
      $query= "UPDATE user SET password= '$newPassword' where user_id = $id";
     $result = $mysqli->query($query);
     $r["updated"] = "updated";
        }
        echo json_encode($r);

    }


    }



?>