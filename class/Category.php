<?php
class Category extends Connn
{
      private $timestamp;
      private $parentIDS;


      function __construct()
      {
            parent::__construct();
            $defaultTimeZone = 'UTC';
            date_default_timezone_set($defaultTimeZone);
            $this->timestamp = date('Y-m-d');
            $this->get_all_parentsid();
            //var_dump( $this->parentIDS);
      }



      function get_categories_by_parentid($parent_id)
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("select * from category_new where ParentID = $parent_id");
            return $stmt;
      }
      function get_all_parentsid()
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("select categoryName from category_new where depth = 1");
            while ($row = $stmt->fetch()) {
                  $jsonArray[] = $row['categoryName'];
            }

            $this->parentIDS = $jsonArray;


            //return '[' . implode(',', $jsonArray) . ']';
            //return implode(',', array_unique($jsonArray));
      }
      function get_parent_IDS()
      {
            return $this->parentIDS;
      }
      function get_parent_category()
      {
            $pdo = $this->dbc;   //
            $sql = "SELECT DISTINCT(TRIM(BOTH '\"' FROM JSON_EXTRACT(`json_`, '$.roots[0]'))) as cat FROM `category_new` where JSON_EXTRACT(`json_`, '$.roots[0]') is NOT NULL;";
            //echo  $sql;
            $stmt = $pdo->query($sql);
            return $stmt;
      }

      function get_subcategories($cat_id)
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') in ('$cat_id');");
            return $stmt;
      }
      function get_categories($cat = '1/%')
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("SELECT * FROM `category_new` WHERE `cat_path` like '$cat'");
            return $stmt;
      }

      function get_categorie_name($category_id)
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("select categoryName from category_new where cat_id = $category_id");
            $row = $stmt->fetch();
            return $row['categoryName'];
      }

      function get_parent_category_name($id = 1)
      {
            $pdo = $this->dbc;
            if (!isset($id))
                  $id = 1;
            $sql = "select * from inventoryitem left join productitem on inventoryitem.productItemID = productitem.productID left join category_new on category_new.cat_id = productitem.category where inventoryitemid =  $id";
            $stmt = $pdo->query($sql);
            if ($row = $stmt->fetch()) { // Check if a row was fetched
                  return $row['categoryName'];
            } else {
                  return "Unknown Category";  // Or handle it differently, like returning null or an empty string
            }
      }

      function get_categorie_id($id = 1)
      {
            $pdo = $this->dbc;
            $sql = "select category from inventoryitem where InventoryItemID = $id";
            //echo $sql;
            $stmt = $pdo->query($sql);
            if ($stmt) {
                  $row = $stmt->fetch();
                  return $row['category'];
            } else {
                  return 2;
            }
      }

      function get_related_categories($product_id)
      {
            $pdo = $this->dbc;
            $sql = "select cat_path from category_new where cat_id = (select `category` from productitem where `productID` = ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();

            if ($row = $stmt->fetch()) {
                  $string_to_be_sa = "\\" . $product_id;
                  $search_string = str_replace($string_to_be_sa, '', $row['cat_path']);
                  //echo $row['cat_path'];
                  $sql = "select * from category_new where cat_path like '\\" . $search_string . "%'";
                  //echo $sql;
                  $stmt1 = $pdo->query($sql);
                  return $stmt1;
            } else {
                  return null;

            }


      }

      function get_subcategorieslevel1($cat_id)
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("SELECT cat_id, categoryName, `cat_path` FROM category_new WHERE `cat_path` REGEXP '" . $cat_id . "/'" . "and depth = 2");
            return $stmt;
      }


      function get_cat()
      {

            $pdo = $this->dbc;
            $stmt = $pdo->query("SELECT * FROM category_new WHERE  depth = '1'");
            return $stmt;

      }

      function get_cat_specific($cat)
      {

            $pdo = $this->dbc;
            $sql = "SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = ('$cat') and depth != 1";
            $stmt = $pdo->query($sql);
            return $stmt;

      }

      function get_cat_specific_count($cat)
      {

            $pdo = $this->dbc;
            $sql = "SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = ('$cat')";
            $stmt = $pdo->query($sql);
            return $stmt->rowCount();

      }


      function sub_get_cat($id)
      {

            $pdo = $this->dbc;
            $id = strtolower($id);
            $sql = "SELECT * FROM category_new where LOWER(JSON_EXTRACT(json_ ,'$.roots')) = '\"$id\"';";
            echo $sql;
            $stmt = $pdo->query($sql);
            return $stmt;

      }

      function count_inventory_items_by_category($categoryID)
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("select count(*) as number_of_items from inventoryitem where category = $categoryID");
            $row = $stmt->fetch();
            return $row['number_of_items'];
      }

      function get_category_by_size()
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("SELECT DISTINCT `size` FROM inventoryitem");
            return $stmt;
      }
      //SELECT * FROM `variation` left join variation_option on variation.`vid` = variation_option.`variation_id` where category_id = 1

      function get_variation_by_category($cat)
      {
            $pdo = $this->dbc;
            $stmt = $pdo->query("SELECT * FROM `variation` left join variation_option on variation.`vid` = variation_option.`variation_id` where category_id = 1 and lower(name) like '%size'");
            return $stmt;
      }
      function decript_string($string)
      {
            $string2 = explode(",", $string);
            foreach ($string2 as $key => $value) {
                  if (strlen($value) === 0) {
                        unset($string2[$key]);
                  }
            }
            return (array_unique($string2));
      }

      function get_category_by_id($id)
      {
            $pdo = $this->dbc;
            //$sql_ ="SELECT json_ from category_new where cat_id =".$this->get_categorie_id($id);
            // echo $sql_;
            $sql_ = "SELECT json_ from category_new where cat_id = 2";
            $stmt = $pdo->query($sql_);
            $row = $stmt->fetch();
            $decoded_jason_array = json_decode($row['json_']);
            $blog = get_object_vars($decoded_jason_array);

            return $blog['roots'][0];

      }


      function get_items_by_category_by_count()
      {

      }



}

?>