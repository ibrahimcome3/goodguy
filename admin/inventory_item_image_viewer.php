<!DOCTYPE HTML>
<?php include "../conn.php";   ?>
<html>

<head>
  <title>Inventory Item Image Viewer</title>
</head>

<body>
    <div style="display: flex;">
   <?php
   $id = $_GET['id'];
   $sql = "SELECT * FROM `inventory_item_image` WHERE `inventory_item_id` =$id order by category asc";
        $result = $mysqli->query($sql);
                 if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        if($row["is_primary"] ==1 ){
                           echo "this is the primary image !";
                           }
                        ?>

                    <div style="display: flex; flex-direction: column;"><img style="width: 300px;" src="<?php echo $row['image_path']?>" style="border: 0" alt=""/>
                    <a href="delete_image.php?id=<?php echo $row['inventory_item_image_id'] ?>">Delete</a>
                    <a href="make_primary?id=<?php echo $row['inventory_item_image_id'] ?>&inid=<?php echo $id ?>">Make it the primary display image</a>
                    </div>
                    <?php

                    }
                }else{
                  echo "No images found for this item";
                }

   ?>
  </div>
</body>

</html>