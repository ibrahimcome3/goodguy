<!DOCTYPE html>
<?php
include "../conn.php";
require_once  '../class/Conn.php';
require_once  '../class/Category.php';
?>
<html lang="en">
<head>
  <title>Bootstrap Example</title>

  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
  <h2>Modal Example</h2>
  <!-- Trigger the modal with a button -->
  <button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target="#myModal">Open Modal</button>

  <!-- Modal -->
  <div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">
    
      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Modal Header</h4>
        </div>
        <div class="modal-body">
          <p>Some text in the modal.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
      
    </div>
  </div>
   <div class="col-md-7 col-lg-8">
        <ul><li><a href="add-inventory-items-to-a-product.php">Click here to add inventory items to a product</a></li></ul>
<form action="product_adder.php" method="post" enctype="multipart/form-data">
    <h4>New Product Creation Form</h4>
     <div class='row'>
     <div class="col-sm-6">
         <label class="form-label" for="pid">ProductID</label>
         <input name="productid" id="pid" class="form-control" disabled="disabled" placeholder="auto generated">
     </div>
     
    <div class="col-sm-6">
         <label class="form-label" for="date">Date</label>
         <input name="date_created" class="form-control" id="date" disabled="disabled" placeholder="">
     </div>
     
     <div class="col-12">
         <label class="form-label" for="description">Description</label>
         <textarea name="description" class="form-control" id="description" placeholder=""></textarea>
     </div>
     

     
      <div class="col-sm-6">
         <label class="form-label" for="supplier">Vendor Information</label>
         <select name="vendor" class="form-control" id='supplier' >
                <?php
                $sql = "SELECT * FROM `supplier` ORDER BY `supplier`.`sup_company_name` ASC";
                $result = $mysqli->query($sql);
                 while($row = mysqli_fetch_array($result)){
                ?>

                <option value="<?=$row['sup_id'] ?>"><?= $row['sup_company_name'] ?></option>

               <?php } ?>
                </select>
                 <br>
<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter">
  Launch demo modal
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ...
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>
     </div>
     </div>
     
    <div class="col-sm-6">
         <label class="form-label" for="category">Category</label>
       <select name="category" class="form-control" >
                <?php
                $sql = "SELECT * FROM `category_new` ORDER BY `cat_id` ASC";
                $result = $mysqli->query($sql);
                 while($row = mysqli_fetch_array($result)){
                ?>

                <option value="<?=$row['cat_id'] ?>"><?= $row['cat_id']." " ?><?= $row['categoryName'] ?></option>

               <?php } ?>
                </select>
                 <br>
                 <a href="new-category.php" title="new-brand-creation">create new category</a>
     </div>
     
     
         <div class="col-sm-6">
         <label class="form-label" for="category">Brand</label>
           <select name="brand" class="form-control" >
                <?php
                $sql = "SELECT * FROM `brand` ORDER BY `brand`.`Name` ASC";
                $result = $mysqli->query($sql);
                 while($row = mysqli_fetch_array($result)){
                ?>

                <option value="<?=$row['brandID'] ?>"><?= $row['Name'] ?></option>

               <?php } ?>
                </select>
                 <br>
                
                <a href="new-brand.php" title="new-brand-creation">create new brand</a>
     </div>
     
    <div class="col-sm-6">
         <label class="form-label" for="category">product_information</label>
          <td><textarea width="162px" height="200px" class="form-control" name="produt_info" placeholder=""></textarea></td>

     </div>
     
    <div class="col-sm-6">
         <label class="form-label" for="category">additional_information</label>
         <textarea width="162px" height="200px" class="form-control" name="additional_info" placeholder=""></textarea>

     </div>
     
    <div class="col-sm-6">
         <label class="form-label" for="category">shipping_returns policy of a product</label>
                         <select name="ship_returns"  class="form-control" >
                <?php
                $sql = "SELECT * FROM `shipping_policy`";
                $result = $mysqli->query($sql);
                 while($row = mysqli_fetch_array($result)){
                ?>

                <option value="<?=$row['shipping_policy_id'] ?>"><?= $row['shipping_policy'] ?></option>

               <?php } ?>
                </select>
     </div>
     
    <div class="col-sm-6">
         <label class="form-label" for="category">Select a picture for this product (*jpg,png only)</label>
         <input type="file" name="product_picture" class="form-control"  id="fileToUpload">   
     </div>
     
    <div class="col-sm-6">
         <input type="submit"  class="form-control"  value="submit form for processing" />   
    </div>
     
     </div>
 

        <div  style="border: 3px solid brown; max-width: 400px;">
              <img id="imagePreview" src="../e.jpg"  width="200px" height="200px" />
        </div>
</form> 
        
    </div>    
 
</div>
</div>

</body>
</html>
