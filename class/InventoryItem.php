<?php
require_once "Connn.php";
class InventoryItem extends Connn
{
    private $timestamp;


    function __construct()
    {
        parent::__construct();
        $defaultTimeZone = 'UTC';
        date_default_timezone_set($defaultTimeZone);
        $this->timestamp = date('Y-m-d');
    }

    function add_inventory_item()
    {
        $pdo = $this->dbc;
        $data = ['description_' => 'Heinz', 'date_added_' => $this->timestamp, 'vendor_' => 'lm', 'Brand_' => 'Heinz',];
        $sql = "INSERT INTO `productitem`(`description`, `date_added`, `vendor`, `Brand`) VALUES (:description_, :date_added_, :vendor_, :Brand_)";
        var_dump($data);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    }

    function get_products()
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from productitem");
        while ($row = $stmt->fetch()) {
            echo $row['description'] . "<br />\n";
        }
    }

    function get_all_drinks()
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from inventoryitem where category in (select `cat_id` from category_new where `cat_path` like '%1/%')");
        return $stmt;
    }

    function get_all_drinks_count()
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select count(*) as count from inventoryitem where category in (select `cat_id` from category_new where `cat_id` in (SELECT `cat_id` from category_new WHERE `cat_path` like '%1/%'))");
        $row = $stmt->fetch();
        return $row['count'];

    }


    function get_inventory_items_product($category_id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `ParentID` = $category_id) ");
        if ($stmt->rowCount() === 0)
            $stmt = $pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `categoryID` = $category_id) ");
        if ($stmt->rowCount() === 0)
            print ("<center><b>No items in this category</b></center>");
        return $stmt;
    }

    function get_multiple_inventory_items_product($category_id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `ParentID` = $category_id) ");
        if ($stmt->rowCount() === 0)
            $stmt = $pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `categoryID` = $category_id) ");
        if ($stmt->rowCount() === 0)
            print ("<center><b>No items in this category</b></center>");
        return $stmt;
    }

    function get_multiple_inventory_items_product2($sql, $starting_limit, $limit)
    {
        $sql = $sql . ' LIMIT ' . $starting_limit . ',' . $limit;
        $pdo = $this->dbc;

        $stmt = $pdo->query($sql);
        return $stmt;

        /* $stmt = $pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `ParentID` in ($category_id))");
         if($stmt->rowCount() === 0)
             $stmt = $pdo->query("select * from inventoryitem where category in (SELECT `categoryID` FROM category WHERE `categoryID` in ($category_id) )");
               if($stmt->rowCount() === 0)
                   print("<center><b>No items in this category</b></center>");
        */
        return $stmt;
    }


    function get_product_inventory($product_id = 1)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->prepare("SELECT * FROM inventoryitem as i WHERE i.productItemID = ?");

        $stmt->execute([$product_id]);
        while ($row = $stmt->fetch()) {
            echo $row['description'] . "<br />\n";
        }


    }

    function get_product_image($id)
    {
        $pdo = $this->dbc;
        $sql = "select * from inventory_item_image where inventory_item_id = $id and `is_primary` = 1";
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        if ($stmt->rowCount() > 0)
            return $row['image_path'];
        else
            return "e.jpg";

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
    function get_description($id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from inventoryitem where InventoryItemID = $id");
        $row = $stmt->fetch();
        return $row['small_description'];

    }
    function check_item_in_existance($id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from inventoryitem where InventoryItemID = $id");
        $row = $stmt->fetch();
        $row_count = $stmt->rowCount();
        if ($row_count > 0) {
            return true;
        } else {
            return false;
        }
    }

    function get_color($c)
    {
        $sql = "select JSON_VALUE(sku, '$.color') as color from inventoryitem where `InventoryItemID` = $c";
        $pdo = $this->dbc;
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        $row_count = $stmt->rowCount();
        echo $row['color'];
        if (strlen($row['color']) > 0) {
            return strtoupper($row['color']);
        } else {
            return null;
        }
        //return strtoupper($row['color']);



    }

}




?>