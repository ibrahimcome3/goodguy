<?php

class Category
{
      protected $pdo; // Property to hold the PDO connection
      private $timestamp;
      private $parentIDS;

      function __construct(PDO $pdo) // Accept PDO connection in constructor
      {
            $this->pdo = $pdo; // Assign the passed PDO connection
            $defaultTimeZone = 'UTC';
            date_default_timezone_set($defaultTimeZone);
            $this->timestamp = date('Y-m-d');
            $this->get_all_parentsid();
            //var_dump( $this->parentIDS);
      }




      function get_all_parentsid()
      {
            $pdo = $this->pdo;
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
            $pdo = $this->pdo;
            $sql = "SELECT DISTINCT(TRIM(BOTH '\"' FROM JSON_EXTRACT(`json_`, '$.roots[0]'))) as cat FROM `category_new` where JSON_EXTRACT(`json_`, '$.roots[0]') is NOT NULL;"; //
            //echo  $sql;
            $stmt = $pdo->query($sql);
            return $stmt;
      }

      function get_subcategories($cat_id)
      {
            $stmt = $this->pdo->query("SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') in ('$cat_id');");
            return $stmt;
      }
      public function getDirectSubcategoriesByParentId(int $parentId)
      {
            try {
                  $sql = "SELECT * FROM categories WHERE parent_id = :parent_id";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
                  $stmt->execute();
                  return $stmt;
            } catch (PDOException $e) {
                  error_log("Error fetching direct subcategories for parent ID {$parentId}: " . $e->getMessage());
                  return false;
            }
      }

      function get_categorie_name($category_id)
      {
            $stmt = $this->pdo->query("select categoryName from category_new where cat_id = $category_id");
            $row = $stmt->fetch();
            return $row['categoryName'];
      }

      function get_parent_category_name($id = 1)
      {
            $pdo = $this->pdo;
            $id = $id ?? 1; // Use null coalescing operator for cleaner default
            $sql = "select * from inventoryitem left join productitem on inventoryitem.productItemID = productitem.productID left join category_new on category_new.cat_id = productitem.category where inventoryitemid = :id";
            $stmt = $pdo->query($sql);
            if ($row = $stmt->fetch()) { // Check if a row was fetched
                  return $row['categoryName'];
            } else {
                  return "Unknown Category";  // Or handle it differently, like returning null or an empty string
            }
      }


      /**
       * Fetches direct subcategories for a given parent category ID from the category_new table.
       * Uses the ParentID column.
       *
       * @param int $parentId The ID of the parent category.
       * @return PDOStatement|false The PDOStatement object on success, or false on failure.
       */


      function get_related_categories($product_id)
      {

            $pdo = $this->pdo;
            $sql = "select cat_path from category_new where cat_id = (select `category` from productitem where `productID` = ?)"; // Using ? placeholder
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





      function get_cat_specific($cat)
      {

            $pdo = $this->pdo;
            $sql = "SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = ('$cat') and depth != 1";
            $stmt = $pdo->query($sql);
            return $stmt;

      }

      function get_cat_specific_count($cat)
      {


            $pdo = $this->pdo;
            $sql = "SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = ('$cat')";
            $stmt = $pdo->query($sql);
            return $stmt->rowCount();

      }


      function count_inventory_items_by_category($categoryID)
      {
            $stmt = $this->pdo->query("select count(*) as number_of_items from inventoryitem where category = $categoryID");
            $row = $stmt->fetch();
            return $row['number_of_items'];
      }

      //SELECT * FROM `variation` left join variation_option on variation.`vid` = variation_option.`variation_id` where category_id = 1



      function get_category_by_id($id)
      {

            $pdo = $this->pdo;
            // Assuming you want the category JSON for a specific cat_id, not hardcoded to 2
            $sql_ = "SELECT json_ from category_new where cat_id = :cat_id";
            $stmt = $pdo->query($sql_);
            $row = $stmt->fetch();
            $decoded_jason_array = json_decode($row['json_']);
            $blog = get_object_vars($decoded_jason_array);

            return $blog['roots'][0];

      }


      /**
       * Fetches top-level parent categories.
       * These are categories where level is 1 and parent_id is NULL.
       *
       * @return PDOStatement|false The PDOStatement object on success, or false on failure.
       */
      public function getTopLevelParentCategories()
      {
            try {
                  $sql = "SELECT `category_id`, `name`, `parent_id`, `level`, `owner_id` FROM `categories` WHERE `level` = 0 AND `parent_id` IS NULL";
                  $stmt = $this->pdo->query($sql);
                  return $stmt;
            } catch (PDOException $e) {
                  error_log("Error fetching top-level parent categories: " . $e->getMessage());
                  return false;
            }
      }


}

?>