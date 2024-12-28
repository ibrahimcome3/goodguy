<?php
class Voice
{
    private $first_name;
    private $last_name;
    private $tracks;
    private $followers;
    private $id;
 
    function __construct(){
    
    }

    function getFollower_id(){
        return  $this->id;
    }

    function add_follower($follower_id=1){
         include "conn.php";
         $user_id  = $_SESSION['uid'];
         $sql = "INSERT INTO follow (user_id, follower_id) VALUES ($user_id, $follower_id);";
         $result = $mysqli->query($sql);
         if($result){
             return true;
         }
     }
    function unfollow($follower_id){
         include "conn.php";
         $user_id  = $_SESSION['uid'];
         $sql = "DELETE FROM `voicefol_voicefold`.`follow` WHERE `follow`.`user_id` = $user_id AND `follow`.`follower_id` = $follower_id";
         $result = $mysqli->query($sql);
         if($result){
             return true;
         }
     }

     function isFollower($id){
      include "conn.php";
      $uid = $_SESSION['uid'];
      if(isset($uid)){
      $sql = "SELECT * FROM `follow` WHERE follower_id = $id and user_id = $uid";
      $result = $mysqli->query($sql);
      $rowcount=mysqli_num_rows($result);
      if($rowcount > 0){
             return true;
         }else{
             return false;
         }

      }
   
     }

     function get_number_of_followers($id){
         include "conn.php";
         $sql = "select count(*) as total from follow where follower_id = $id";
         $result = $mysqli->query($sql);
         $row = $result->fetch_row() ;
         return $row[0];
    }

    function get_number_of_tracks($id){
         include "conn.php";
         $sql = "select count(*) as total from audio where profile_id = $id";
         $result = $mysqli->query($sql);
         $row = $result->fetch_row() ;
         return $row[0];
    }

     function getVoices(){
     include "conn.php";
     $sql = "select * from user";
     $result = $mysqli->query($sql);
     return $result;
}



     function load_followers(){
     $id  = $_SESSION['uid'];
     include "conn.php";
     $sql = "SELECT * FROM follow, user WHERE user.user_id = follow.user_id and follow.follower_id = $id";
     $result = $mysqli->query($sql);
     var_dump($result);
     return $result;


    }

    function get_total_followers($id){
         include "conn.php";
         $sql = "select count(*) as total FROM follow, user WHERE user.user_id = follow.follower_id and follow.`user_id` = $id and follow.follower_id != $id";
         $result = $mysqli->query($sql);
         $row = $result->fetch_row() ;
         return $row[0];

    }

    function get_total_following($id){
         include "conn.php";
         $sql = "select count(*) as total FROM follow, user WHERE user.user_id = follow.follower_id and follow.`follower_id` = $id and follow.`user_id` != $id";
         $result = $mysqli->query($sql);
         $row = $result->fetch_row() ;
         return $row[0];

    }
     
 
 



 
}

?>
 