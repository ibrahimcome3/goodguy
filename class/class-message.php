<?php
 class Message
{
    protected $msg_id;
    protected $msg_contents;
    protected $msg_date;
    protected $author_id;



    public function __construct()
    {


    }

    public function update_read_message($m){
     include "conn.php";
	 $integerIDs = array_map('intval',  $m);

	 foreach($integerIDs as $x => $x_value) {	 
     $sql="UPDATE msg_track SET read_s = 1 WHERE msg_id = $x_value";
     $result = $mysqli->query($sql);

     }

    }

    public function add_message($content, $author, $v_id)
    {
		$defaultTimeZone='UTC';
        date_default_timezone_set($defaultTimeZone);
      include "conn.php";
	   $timestamp = date('Y-m-d G:i:s');
         $sql = "INSERT INTO `message` (`id`, `content`, `_date`, `author_id`) VALUES (NULL, '$content', '$timestamp', $author);";
         $result = $mysqli->query($sql);
		 $last_id =$mysqli->insert_id;
         if($result){

             $this->add_viewer($last_id  , $v_id);
			 $this->register_status($last_id , $v_id);
             return true;
         }

    }

        public function add_viewer($message_id, $viewer_id)
    {
         include "conn.php";
         $sql = "INSERT INTO `message_viewer`(`message_id`, `viewer_id`) VALUES ($message_id, $viewer_id)";
         $result = $mysqli->query($sql);
         if($result){
             return true;
         }

    }
	
	       public function register_status($message_id, $viewer_id)
    {
      include "conn.php";
         $sql = "INSERT INTO `msg_track`(`msg_id`, `user_id`)  VALUES ($message_id, $viewer_id)";
         $result = $mysqli->query($sql);
         if($result){
             return true;
         }

    }

    public function get_number_of_message(){

         include "conn.php";
         if(isset($_SESSION['uid'])){
         $id = $_SESSION['uid'];
         $sql = "SELECT count(*) FROM `msg_track` WHERE user_id = $id and read_s = 0 ";
         $result = $mysqli->query($sql);
         $row = mysqli_fetch_array($result);
         return $row[0];
         }

    }
	
	public function get_inbox_message(){

         include "conn.php";
         $id = $_SESSION['uid'];
         $sql = "select * from message_viewer v, message m, user u where (v.viewer_id = 103 or v.viewer_id = 102) and v.message_id = m.id and (m.author_id = 102 or m.author_id = 103) and m.author_id = u.user_id  order by _date desc";
         $result = $mysqli->query($sql);
         return $result;

    }

		public function delete_message($id){
     $defaultTimeZone='UTC';
    date_default_timezone_set($defaultTimeZone);
     include "conn.php";
	 $uid = $_SESSION['uid'];
	 $timestamp = date('Y-m-d G:i:s');

     $sql = "INSERT INTO `deleted_msg` (`d_by`, `m_id`,  `_date`) VALUES ($uid, $id, '$timestamp')";
     $result = $mysqli->query($sql);
     if($result) return true;
     }

    public function get_user_messages($p){
     include "conn.php";
         $id = $_SESSION['uid'];
         $sql = "select * from message_viewer v, message m, user u where v.viewer_id = $id and v.message_id = m.id and u.user_id = $id LIMIT" . (($p->get_page() - 1) * 1) . ", " . 1 ."";
         $result = $mysqli->query($sql);
         return $result;
    }


    public function get_message($id){
          include "conn.php";

         $sql = "SELECT content FROM `message` WHERE id = $id";
         $result = $mysqli->query($sql);

         $row = mysqli_fetch_array($result);
         return $row[0];
    }

    

        public function reply($id, $content, $sender){
          include "conn.php";

         $sql = "INSERT INTO `reply` (`id`, `content`, `message_id`, `_time`, sender) VALUES (NULL, '$content', $id, CURRENT_TIMESTAMP, $sender)";
         $result = $mysqli->query($sql);
         if($result){
             return 1;
         }else{
             return 0;
         }
         }

         public function get_user_reply(){
         include "conn.php";
         $id = $_SESSION['uid'];
         $sql = "select * from reply r , message m where r.message_id in (SELECT message_id FROM `message_viewer` WHERE viewer_id = $id ) and m.id = r.message_id";
         $result = $mysqli->query($sql);
         return $result;
    }



























}






?>