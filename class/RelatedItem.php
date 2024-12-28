<?php

      class RelatedItem {
         public int $records_per_page;
         public int $page;
         public int $category;
         public int $id_of_what_to_process;
         public int $total_records;
         public int $total_pages;

        public function RelatedItem($records_per_page, $page=1 , $category = 13, $id_of_what){
             $this->records_per_page = $records_per_page;
             $this->page = $page;
             $this->category = $category;
             $this->id_of_what_to_process =  $id_of_what;

        }

        public function  set_records_per_page($id){
            $this->records_per_page = $id;
         }

        public function  check_page($post_item){
           if(isset($post_item))
             {
                  $this->page = $post_item;
             }
             else
             {
                  $this->page = 1;
             }
         }
        public function get_number_of_records_in_query(){
                 include "conn.php";
                 $category9 = $this->category;
                 $_id_of_what_get_image = $this->id_of_what_to_process;
                 $sql = "SELECT count(*)  as counts FROM `inventoryitem` WHERE `category` = ".$category9. " and InventoryItemID != ". $_id_of_what_get_image;
                 $rows = mysqli_fetch_array($mysqli->query($sql));
                 $this->total_records = $rows['counts'];
        }

        public function total_pages(){
             $this->total_pages = ceil($this->total_records/$this->records_per_page);
        }

        public function related_query(){
             include "conn.php";
             $sql = "SELECT * FROM `inventoryitem` WHERE `category` = ".$category9. " and InventoryItemID != ". $_id_of_what_get_image." limit ".(($page - 1) * $records_per_page) . ', ' . $records_per_page;
             $result = $mysqli->query($sql);
             return $result;
        }

        public function getImage($id_of_what_get_image){
        include "conn.php";
        $sql = "select * from inventory_item_image where inventory_item_id = $id_of_what_get_image and `is_primary` = 1";
        $result = $mysqli->query($sql);
        $row = mysqli_fetch_array($result);
          if(mysqli_num_rows($result) > 0)
          return $row['image_path'];
          else return "e.jpg";
        }

      }

?>