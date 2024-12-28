<?php
class Share
{
    private $id;
    private $user_id;
    private $track_id;


    function __construct(){
    $this->id= $_SESSION['uid'];
    }

    function getFollower_id(){
        return  $this->id;
    }

    function share($track_id){
         include "conn.php";
         $defaultTimeZone='UTC';
         date_default_timezone_set($defaultTimeZone);
         $timestamp = date('Y-m-d G:i:s');
         $user_id  = $this->id;
         $sql = "INSERT INTO `shared`(`id`, `user_id`, `track_id`, `date`) VALUES (null,$user_id,$track_id,'$timestamp')";
         $result = $mysqli->query($sql);
         if($result){
             return true;
         }
     }

     function isShared($id){
        include "conn.php";
         $user_id  = $this->id;
         $sql = "SELECT * FROM `shared` WHERE `user_id` = $user_id and  `track_id` = $id";
         $result = $mysqli->query($sql);
         $rowcount=mysqli_num_rows($result);

         if((int)$rowcount === (int)0){
             return true;
         } else{
             return false;
         }
     }


     function isFollower($id){
      include "conn.php";
      $uid = $this->id;
      $sql = "SELECT id FROM `follow` WHERE follower_id = $id and user_id = $uid";
      $result = $mysqli->query($sql);
      $rowcount=mysqli_num_rows($result);
      if($rowcount > 0){
             return true;
         }else{
             return false;
         }

     }


      function sharedTracks($cal, $rp){
        include "conn.php";
         $user_id  = $this->id;
        // $sql = "SELECT distinct a.audio_id, u.profile_image, u.fname, a.profile_id, u.lname, u.user_id, s.date, a.audio_name FROM shared s, audio a, user u WHERE s.track_id = a.audio_id and s.`user_id` = u.user_id order by s.date desc  LIMIT {$cal}, {$rp}";
           $sql="select * from (select shared.`track_id`, f.follower_id as shearer, shared.`date` from shared, (select * from follow where user_id = $user_id) as f where f.`follower_id` = shared.`user_id`group by shared.track_id having min(shared.date) order by shared.date) as new left join audio on audio.`audio_id` = new.track_id left join `user` u on u.user_id = new.shearer
";          
		  $result = $mysqli->query($sql);
         return $result;

     }










}

?>
