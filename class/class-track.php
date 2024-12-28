<?php
class Track
{
    private $track_id;
    private $track_name;
    private $track_hits;
    private $track_size;
 
    function __construct(){
        
    }

    function get_top_ten_tracks(){
     include "conn.php";
     //$sql = "SELECT * FROM audio AS r1 JOIN (SELECT CEIL(RAND() * (SELECT MAX(audio_id) FROM audio)) AS id) AS r2 WHERE     //    r1.`audio_id` >= r2.id ORDER BY r1.`audio_id` ASC LIMIT 10";

          $sql = "SELECT * FROM audio ORDER BY RAND() LIMIT 10";
     $result = $mysqli->query($sql);

     return $result;
     }

    function add_track($name, $size ,$gr_id="null"){
	$defaultTimeZone='UTC';

    date_default_timezone_set($defaultTimeZone);
    include "conn.php";
    $id = $_SESSION['uid'];
	$timestamp = date('Y-m-d G:i:s');
	$escapedname = mysqli_real_escape_string($mysqli, $name);
	
    $sql = "INSERT INTO `post` (`id`, `posting_date`, `post_type`, `posted_by`) VALUES (NULL, '$timestamp', 'A', $id);";
    $result = $mysqli->query($sql);
    $last_insert_id = $mysqli->insert_id;
    
    $sql = "INSERT INTO `audio`( `audio_name`, `audio_size`, `profile_id`, _date,a_id, post_id) VALUES ('$escapedname',$size, $id,  '$timestamp', $gr_id, $last_insert_id)";
    $result = $mysqli->query($sql);
	
    if($result){ 
     
      return  $mysqli->insert_id;
        
    }
    

    }
	
	  function update_track($d, $aid){
    include "conn.php";
    $sql = "update `audio` set duration = '$d' where audio_id = $aid";
    $result = $mysqli->query($sql);
    if($result) return true;

    }
   	
	function get_audio_group($id){
    include "conn.php";

    $sql = "SELECT * FROM (select * from audio_group, audio where audio_group.u_id = $id and audio.a_id = audio_group.gr_id )as d where _show = 1 group by gr_id having max(_date)
";
    $result = $mysqli->query($sql);
    if($result) return $result;

    }
function get_new_uploads(){
     include "conn.php";
    // $sql = "SELECT * FROM audio WHERE `_date` >= NOW() - INTERVAL 3 MONTH group by `profile_id` ORDER by `_date` DESC //limit 10
//";
       $sql = "SELECT t1.*, user.* FROM audio t1 RIGHT JOIN user ON (user.user_id = t1.profile_id) WHERE t1._date = (SELECT MAX(t2._date) FROM audio t2 WHERE t2.profile_id= t1.profile_id) ORDER BY `t1`.`audio_id` desc LIMIT 15";
     $result = $mysqli->query($sql);

     return $result;
     }

     function get_user_tracks($id, $e){
     include "conn.php";
     $sql = "select * from audio where profile_id = $id and a_id = $e and _show = 1 ORDER BY `audio_id` ASC";
     $result = $mysqli->query($sql);
     return $result;


     }
  function get_user_tracks_no_group($id){
     include "conn.php";
     $sql = "select * from audio where profile_id = $id and a_id is null and _show = 1 ORDER BY `_date` ASC";
     $result = $mysqli->query($sql);
     return $result;


     }
     function get_audio_location($id){
     include "conn.php";
     $sql = "select folder_name from user where user_id = $id";
     $result = $mysqli->query($sql);

     $row = $result->fetch_row();
     return $row[0];


     }
	 
	 function delete_track($id){
     include "conn.php";
     $sql = "UPDATE `audio` SET `_show`= 0 WHERE `audio_id`= $id";
     $result = $mysqli->query($sql);
     if($result) return true;
     }

         function hits_plus_one($id){
     include "conn.php";
     $sql = "UPDATE `audio` SET `hits`= hits + 1 WHERE audio_id = $id";
     $result = $mysqli->query($sql);
   

     }

 

 

 

 
}
?>