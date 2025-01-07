<?php
class WishList extends Connn
{
   private $user_id;
   public $no_of_wish_list_item;

   function __construct($id)
   {
      parent::__construct();
      $pdo = $this->dbc;
      $this->user_id = $id;
      $this->get_wished_list_item_($id);
   }




   function get_wished_list_item($id)
   {
      $pdo = $this->dbc;
      $stmt = $pdo->query("SELECT count(*) as c FROM `wishlist` WHERE `customer_id` = $id;");
      $row = $stmt->fetch();
      return $row['c'];
   }

   public function no_of_wish_list_item()
   {
      $pdo = $this->dbc;
      $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?");
      $stmt->execute([$_SESSION['uid']]);
      $result = $stmt->fetch();
      return $result['count'];

   }

   function get_wished_list_item_($id)
   {
      $pdo = $this->dbc;
      $sql = "SELECT count(*) as c FROM `wishlist` WHERE `customer_id` = $id";
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      $this->no_of_wish_list_item = $row['c'];


   }
}

?>