<?php
function check_item_in_existance($id){
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from inventoryitem where InventoryItemID = $id");
        $row = $stmt->fetch();
        $row_count = count($row);
        if($row_count > 0){
            return true;
        }else {
            return false;
        }
    }