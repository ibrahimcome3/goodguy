<!DOCTYPE HTML>
<?php
session_start();
include "../conn.php";
 ?>
<html>

<head>
  <title>Adding inventory items to a product</title>

	<!-- END syntax highlighter -->

	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
    <script src="jquery.searchabledropdown-1.0.8.src.js"></script>
  <script>
  
  

function updateInput() {
    var number = document.getElementByClass("productitem").value;
    document.getElementById("productid").value = number;
}
</script>

       <script>
    $(document).ready(function(){
      $("#fileToUpload").change(function(){
        if (this.files && this.files[0]) {
          var reader = new FileReader();
          reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result);
          }
          reader.readAsDataURL(this.files[0]);
        }
      });
      
       $("select").searchable();

    });
  </script>
 <style>
  .tabs {
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .tabs li {
    display: inline-block;
    margin-right: 10px;
  }

  .tabs li a {
    display: block;
    padding: 5px 10px;
    background-color: #eee;
    color: #333;
    text-decoration: none;
  }

  .tabs li a:hover {
    background-color: #ccc;
  }

  </style>
</head>

<body>
   <?php
   $sql = "SELECT * FROM productitem ORDER BY productID DESC LIMIT 5";
$result = mysqli_query($mysqli, $sql);

// Check for errors
if (!$result) {
    die("Error: " . mysqli_error($conn));
}
   ?>
    <ul class="tabs">
   <?php


while ($row = mysqli_fetch_assoc($result)) {
 ?>

       <li><a href="#" onclick="updateInput()" class="productitem"><?php echo $row["productID"]; ?></a><br><?php echo $row["date_added"]; ?></li>

 <?php
}

// Close the connection


    ?>
   </ul>
</body>
 <form action="inventory_item_uploader.php" method="post" enctype="multipart/form-data">

    <table>
        <tr>
            <td colspan="2"><b><u>New Inventory Item insertion page</u></b>
             <br/>
            </td>
        </tr>
        <tr>
            <td>ProductID</td>
            <td><input name="productid" id="productid" placeholder=""></td>
        </tr>
        <tr>
            <td>Inventory Item Identification Number</td>
            <td><input name="ivenidennumber" disabled="disabled" placeholder="auto generated"></td>
        </tr>
        <tr>
            <td>Description</td>
            <td><textarea width="162px" height="200px" name="description" placeholder=""></textarea></td>
        </tr>

         <tr>
            <td>SKU (SKu is must be in jason)</td>
            <td><textarea width="162px" height="200px" name="sku" value='{"size": "50cl"}'></textarea></td>
        </tr>
        <tr>
            <p>Choose a color for your item:</p>
            
            <div>
              <input type="color" id="head" name="head" value="#e66465" />
              <label for="head">color</label>
            </div>
            
            

        </tr>
        <tr>
            <td>Quintity in inventory</td>
            <td>
                <input name="quintity_in_inventory">
            </td>
        </tr>
        <tr>
        <td>Cost Price</td>
            <td>
                <input name="cost">
            </td>
        </tr>
        <tr>
        <td>Reorder Qunitiy</td>
            <td>
                <input name="reorder_quintity">
            </td>
        </tr>
         <tr>
            <td>Date added to inventory</td>
            <td><input name="date_created" disabled="disabled" placeholder=""></td>
        </tr>

        <tr>
            <td>Category</td>
            <td>    <select name="category">
                <?php
                $sql = "SELECT * FROM `category_new` ORDER BY `category_new`.`categoryName` ASC";
                $result = $mysqli->query($sql);
                 while($row = mysqli_fetch_array($result)){
                ?>

                <option value="<?=$row['cat_id'] ?>"><?= $row['categoryName'] ?></option>

               <?php } ?>
                </select>
                 <br>
                 <a href="new-category.php" title="new-brand-creation">create new category</a>
            </td>
        </tr>
        
          <tr>
            <td>Barcode Number </td>
            <td><input type="text"  name="barcode" placeholder="bar code number"/></td>
        </tr>
        <tr>
            <td>Select a picture for this product (*jpg,png only)</td>
            <td>
            <input type="file" name="files[]" multiple name="inventory_item_images" id="fileToUpload" multiple/>

            </td>
        </tr>
         <tr>
             <td><input type="submit" value="submit form for processing" /></td>
         </tr>
    </table>

</form>
</html>