<?php
class Promotion
{
   public $promotion_items = array();
   protected $pdo; // Property to hold the PDO connection
   private $timestamp;

   function __construct(PDO $pdo) // Accept PDO connection in constructor
   {
      $this->pdo = $pdo; // Assign the passed PDO connection
      $defaultTimeZone = 'UTC';
      date_default_timezone_set($defaultTimeZone);
      $this->timestamp = date('Y-m-d');
      $this->populate_promotion_items();
      //var_dump( $this->parentIDS);
   }



   function check_if_item_is_in_promotion($id)
   {
      $stmt = $this->pdo->query("select * from promooffering where `product_id` = $id");
      $row_count = $stmt->rowCount();
      if ($row_count > 0)
         return true;
      else
         return false;

   }

   function populate_promotion_items()
   {
      $data = array();
      $a = 0;
      $stmt = $this->pdo->query("SELECT * FROM `promotion_items`");
      $row_count = $stmt->rowCount();
      while ($row = $stmt->fetch()) {
         $parent_key = $row['InventoryItemID'];
         unset($row['parent_key']);
         $data[$parent_key][] = $row;
      }
   }

   function check_if_item_is_in_inventory_promotion($id)
   {
      if (isset($id)) {
         $stmt = $this->pdo->query("SELECT * FROM `promotion_items` WHERE `InventoryItemID` =  $id");
         $row_count = $stmt->rowCount();
         if ($row_count > 0)
            return true;
         else
            return false;
      } else {
         return false;
      }

   }

   function get_regular_price($id)
   {
      $sql = "SELECT * FROM inventoryitem WHERE InventoryItemID = $id";
      $stmt = $this->pdo->query($sql);
      $row_count = $stmt->rowCount();
      if ($row_count > 0) {
         $row = $stmt->fetch();
         return $row['cost'];
      } else {
         return false;
      }

   }
   function get_promoPrice_price($id)
   {
      $stmt = $this->pdo->query("SELECT * FROM `promotion_items` WHERE `InventoryItemID` =  $id");
      $row_count = $stmt->rowCount();
      if ($row_count > 0) {
         $row = $stmt->fetch();
         return $row['promoPrice'];
      } else {
         return false;
      }

   }

}



?>