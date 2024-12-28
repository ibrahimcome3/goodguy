<?php
class Post
{
    
    function __construct(){
    
    }


    function get_attachment($id){
         include "conn.php";
         $sql = "select * from post p1 left join pic p2 on p1.`id` = p2.post_id left join audio on audio.post_id = p1.id where `attach_to` = $id
";
         $result = $mysqli->query($sql);
         return $result;
     }
     
  function get_followers_post(){
       include "conn.php";
       $sql = "select  post.status, count(comment.`post_id`) as total_comment, count(likes.item_id)
       as total_likes, user.fname, user.lname, user.folder_name, user.profile_image, post.id, post.posting_date, post.post_type, post.posted_by, 
       twit_post.twit, pic.pic_id, pic.pic_name, pic.uploader_id, user.user_id, audio.* FROM post left JOIN twit_post ON post.id = twit_post.post_id 
       left JOIN pic ON post.id = pic.post_id left join user on user.user_id = post.posted_by left join audio on post.id = audio.post_id 
       left JOIN comment on post.id = comment.post_id left JOIN likes on post.id = likes.item_id where post.is_attachment = 0 
       GROUP BY post.id order by post.posting_date desc limit 5";
       $result = $mysqli->query($sql);
       return $result;
      
  }
  
    function get_followers_post_x($cal, $records_per_page, $n){
       include "conn.php";
       
       $sql = "select post.status, count(comment.`post_id`) as total_comment, count(likes.item_id) as total_likes, user.fname, user.lname, user.folder_name, user.profile_image, post.id, post.posting_date, post.post_type, post.posted_by, twit_post.twit, pic.pic_id, pic.pic_name, pic.uploader_id, user.user_id, audio.*, follow.user_id as a, follow.follower_id as b FROM post left JOIN twit_post ON post.id = twit_post.post_id left JOIN pic ON post.id = pic.post_id left join user on user.user_id = post.posted_by left join audio on post.id = audio.post_id left JOIN comment on post.id = comment.post_id left JOIN likes on post.id = likes.item_id inner join follow on follow.follower_id = post.posted_by where post.is_attachment = 0 and follow.user_id = $n GROUP BY post.id desc  limit {$cal}, {$records_per_page}";
       $result = $mysqli->query($sql);
       return $result;
      
  }
  
    function get_user_post_img_x($id ,$cal, $records_per_page){
       include "conn.php";
       $user_id  = (int)($id);
       $sql = "select SQL_CALC_FOUND_ROWS  post.status, count(comment.`post_id`) as total_comment, count(likes.item_id)
       as total_likes, user.fname, user.lname, user.folder_name, user.profile_image, post.id, post.posting_date, post.post_type, post.posted_by, 
       twit_post.twit, pic.pic_id, pic.pic_name, pic.uploader_id, user.user_id, audio.* FROM post left JOIN twit_post ON post.id = twit_post.post_id 
       left JOIN pic ON post.id = pic.post_id left join user on user.user_id = post.posted_by left join audio on post.id = audio.post_id 
       left JOIN comment on post.id = comment.post_id left JOIN likes on post.id = likes.item_id where post.post_type = 'P' and posted_by = $user_id
       GROUP BY post.id order by post.posting_date desc limit {$cal}, {$records_per_page}";
       $result = $mysqli->query($sql);
       return $result;
      
  }
  
      function get_user_post_audio_x($id ,$cal, $records_per_page){
       include "conn.php";
       $user_id  = (int)($id);
       $sql = "select SQL_CALC_FOUND_ROWS  post.status, count(comment.`post_id`) as total_comment, count(likes.item_id)
       as total_likes, user.fname, user.lname, user.folder_name, user.profile_image, post.id, post.posting_date, post.post_type, post.posted_by, 
       twit_post.twit, pic.pic_id, pic.pic_name, pic.uploader_id, user.user_id, audio.* FROM post left JOIN twit_post ON post.id = twit_post.post_id 
       left JOIN pic ON post.id = pic.post_id left join user on user.user_id = post.posted_by left join audio on post.id = audio.post_id 
       left JOIN comment on post.id = comment.post_id left JOIN likes on post.id = likes.item_id where post.post_type = 'A' and posted_by = $user_id
       GROUP BY post.id order by post.posting_date desc limit {$cal}, {$records_per_page}";
       $result = $mysqli->query($sql);
       return $result;
      
  }
      function get_followers_post_img_x(){
       include "conn.php";
       $sql = "select  post.status, count(comment.`post_id`) as total_comment, count(likes.item_id)
       as total_likes, user.fname, user.lname, user.folder_name, user.profile_image, post.id, post.posting_date, post.post_type, post.posted_by, 
       twit_post.twit, pic.pic_id, pic.pic_name, pic.uploader_id, user.user_id, audio.* FROM post left JOIN twit_post ON post.id = twit_post.post_id 
       left JOIN pic ON post.id = pic.post_id left join user on user.user_id = post.posted_by left join audio on post.id = audio.post_id 
       left JOIN comment on post.id = comment.post_id left JOIN likes on post.id = likes.item_id where post.post_type = 'P' 
       GROUP BY post.id order by post.posting_date desc limit 25";
       $result = $mysqli->query($sql);
       return $result;
      
  }

  function get_attachment_id($id){
         include "conn.php";
         $sql = "select id from post p1 inner join pic p2 on p1.`id` = p2.post_id where `attach_to` = $id";
         $result = $mysqli->query($sql);
         $row = $result->fetch_row() ;
         return $row[0];
         

     }
  function get_attachment_details($id){
         include "conn.php";
         $sql = "select * from post p1 inner join pic p2 on p1.`id` = p2.post_id left join `user` on user.user_id = p1.`posted_by` where p1.`attach_to` = $id";
         $result = $mysqli->query($sql);
         return $result;
     }

	
	 
	 
   function time_ago($date) {
 $defaultTimeZone='UTC';
  date_default_timezone_set($defaultTimeZone);
    if (empty($date)) {
        return "No date provided";
    }
    $periods = array("second", "minute", "hr", "day", "week", "month", "year", "decade");
    $lengths = array("60", "60", "24", "7", "4.35", "12", "10");
    $now = time();
    //echo date('l jS \of F Y h:i:s A',$now);
    $unix_date = strtotime($date);
// check validity of date
    if (empty($unix_date)) {
        return "Bad date";
    }
   // echo $now. "---".$unix_date."<br/>";
// is it future date or past date
    if ($now > $unix_date) {
        $difference = $now - $unix_date;
        //$tense = "ago";
		 $tense = "";
    } else {
        $difference = $unix_date - $now;
        $tense = "from now";
    }
    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++) {
        $difference /= $lengths[$j];
    }
    $difference = round($difference);
    if ($difference != 1) {
        $periods[$j].= "s";
    }
    return "$difference $periods[$j] {$tense}";
}
/*
     function isFollower($id){
      include "conn.php";
      $uid = $_SESSION['uid'];
      $sql = "SELECT id FROM `follow` WHERE follower_id = $id and user_id = $uid";
      $result = $mysqli->query($sql);
      $rowcount=mysqli_num_rows($result);
      if($rowcount > 0){
             return true;
         }else{
             return false;
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


     
 
 



 */
}

?>
 