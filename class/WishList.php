<?php
class WishList
{
   private $user_id;
   public $no_of_wish_list_item;
   protected $pdo; // Store the PDO connection here
   public function __construct($pdo, $id)
   {
      // parent::__construct(); // No longer extending Connn
      $this->pdo = $pdo; // Store the PDO connection
      $this->user_id = $id;
      $this->get_wished_list_item_($id);
   }




   function get_wished_list_item($id)
   {
      $stmt = $this->pdo->query("SELECT count(*) as c FROM `wishlist` WHERE `customer_id` = $id;");
      $row = $stmt->fetch();
      return $row['c'];
   }

   public function no_of_wish_list_item()
   {
      $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE customer_id = ?");
      $stmt->execute([$_SESSION['uid']]);
      $result = $stmt->fetch();
      return $result['count'];
   }

   function get_wished_list_item_($id)
   {
      $sql = "SELECT count(*) as c FROM `wishlist` WHERE `customer_id` = :id";
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch();
      $this->no_of_wish_list_item = $row['c'];


   }
}

?>