<?php
class Variation  extends Connn
{
    private   $timestamp;
    private   $parentIDS;


    function __construct(){
       parent::__construct();
       $defaultTimeZone='UTC';
       date_default_timezone_set($defaultTimeZone);
       $this->timestamp = date('Y-m-d');
       //var_dump( $this->parentIDS);
    }



    function get_variation_for_a_PRODUCT($produt_id){
          $pdo = $this->dbc;
          $stmt = $pdo->query("SELECT DISTINCT(variation.name) FROM `product_configuration`, variation_option left join variation on variation_option.variation_id = variation.vid WHERE product_configuration.`variation_option_id` = variation_option.vopid and variation.`product_id` = $category_id");
          return $stmt;
    }

    function get_color_variation($item_id){
          $pdo = $this->dbc;
          $stmt = $pdo->query("SELECT JSON_VALUE(`sku` , '$.color') as color, InventoryItemID FROM inventoryitem WHERE `productItemID` = $item_id;");
          return $stmt;
    }
    
    function check_for_size_variation($id){
         $pdo = $this->dbc;
         $stmt = $pdo->query("SELECT JSON_CONTAINS_PATH(sku, 'one', '$.size') as path from inventoryitem where InventoryItemID = $id;");
         $row = $stmt->fetch();
         if($row['path'] == '1'){
             return true;
         }else
            return false;
        
    }
    
    function get_all_properties($id){
         $str = "";
         $pdo = $this->dbc;
         $stmt = $pdo->query("SELECT sku from inventoryitem where InventoryItemID = $id;");
         $row = $stmt->fetch();
         $sku = json_decode($row['sku'], true);
         $c = 0;
         foreach($sku as $key => $val) 
            {  
               if($key === 'color') 
               $str .= "<span style='margin-left: 8px;'>" .$key. " : "."<span style='background-color: $val; font-size: 10px; color: $val; border-radius: 25px; width: 15px; height: 5px;'>color</span>";   
               else
               $str .= "<span style='margin-left: 8px;'>" .$key. " : ".$val;   
               if( $c != count( $sku ) - 1) {
                    $str .=",";      
              }
            
              $str .= "</span>";
              $c++;
             
            }
            
            
         return $str;    
    }
    
    
     function get_color_variation_($item_id){
          $pdo = $this->dbc;
          $stmt = $pdo->query("SELECT JSON_VALUE(`sku` , '$.color') as color, InventoryItemID FROM inventoryitem WHERE `InventoryItemID` = $item_id;");
          $number_of_rows = $stmt->rowCount();
          if($number_of_rows > 0){
          $row = $stmt->fetch();
          return  $row['color'];
          }else{
          return "red";  
          }
          
          
    }
   

    function get_variation_values($variation = "color" , $product = 32){
          $pdo = $this->dbc;
          $stmt = $pdo->query("select * from variation left join variation_option on variation_option.variation_id = variation.vid where product_id = {$product} and variation.name like 'color%'");
          return $stmt;
    }

    function get_incentory_item_with_particular_variation($variation = "color"){
        //SELECT * FROM `product_configuration` left join variation_option ON product_configuration.variation_option_id =variation_option.vopid where variation_option.value like "black%"
    }

    function get_combination_of_variation(){
        //SELECT * FROM `product_configuration` left join variation_option ON product_configuration.variation_option_id =variation_option.vopid where variation_option.value like "black%" and variation_option.value like "SMALL%"

    }

    function get_all_variation_and_vaules_for_a_product($id){
         $pdo = $this->dbc;
         $stmt = $pdo->query("select DISTINCT(name) from variation left join variation_option on variation_option.variation_id = variation.vid where product_id = $id");
         return $stmt;
    }

    function return_all_an_array_of_varation_and_values($stmt, $p_id){
           $pdo = $this->dbc;
           $a = array();
           while($row = $stmt->fetch()){
           //array_push($a, $row['name']);
           $i=0;
           $b = array();
        
           $s =  $pdo->query("select * from product_configuration left join variation_option on variation_option.vopid = product_configuration.variation_option_id left join variation on variation.vid = variation_option.variation_id WHERE product_id = $p_id and name like '%".$row['name']."%'"."group by value");
           while($r = $s->fetch()){

           array_push($b, $r['value'].'/'.$r['vopid']);
           }
            $a[$row['name']] = $b;
           //array_push($a,$b);
           $i++;

           }
               return $a;
    }

    function get_size_variation($pid, $color=null){
          $pdo = $this->dbc;
          //echo func_num_args();
          if(isset($color)){
          $color = strtolower($color);
          $sql = "SELECT JSON_UNQUOTE(JSON_EXTRACT(sku, '$.size')) as size, InventoryItemID FROM `inventoryitem` WHERE productItemID = $pid and JSON_CONTAINS_PATH( sku, 'one', '$.color') = 1 and JSON_UNQUOTE(JSON_EXTRACT(sku, '$.color')) = '$color'";
          }else{
           $sql = "SELECT JSON_UNQUOTE(JSON_EXTRACT(sku, '$.size')) as size, InventoryItemID FROM `inventoryitem` WHERE productItemID = $pid";    
          }
          
          $stmt = $pdo->query($sql);
          $number_of_rows = $stmt->rowCount();
          if($number_of_rows == 1){
            return $stmt;
          }


         
    }
    function check_for_item_unveriation_availability($variation_id){
         //echo $variation_id;
           $pdo = $this->dbc;
           $stmt= $pdo->prepare("SELECT * FROM `product_configuration` left join inventoryitem on inventoryitem.InventoryItemID = product_configuration.inventory_item_id where `variation_option_id` = ? and ( inventoryitem.quantityOnHand >= 1)");
           $stmt->execute([$variation_id]);
           $number_of_rows = $stmt->rowCount();
           return $number_of_rows;
    }

    function decript_string($string){
           $string2 = explode(",",$string);
           foreach ($string2 as $key => $value) {
            if (strlen($value) === 0) {
                unset($string2[$key]);
              }
           }
           return(array_unique($string2));
    }

}

?>
