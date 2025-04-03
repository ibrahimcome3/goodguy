<!DOCTYPE html>
<?php
include "../conn.php";
require_once  '../class/Conn.php';
require_once  '../class/Category.php';
?>
<html>
<head>
  
  <title>Product Manager</title>
  
<link rel="stylesheet" href="bootstrap/bootstrap-5.3.3-dist/css/bootstrap.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="bootstrap/bootstrap-5.3.3-dist/js/bootstrap.min.js"></script>
        <script src="cat.js"></script>
    <script src="jquery.searchabledropdown-1.0.8.src.js"></script>
    
    
<style>
    .mb-3 { margin-bottom: 1rem !important;}
</style>
 
</head>
<body>
    <div class="container">
   
<!-- Modal -->
<div class="modal fade" id="category" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Add new Category</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
             <form>
            <table>
                <tr>
                    <td><b>Category</b></td>
                    <td>
                    
                    <select id="sub1" class="sub1" name="sub1">
                    <option disabled selected value> -- select an option -- </option>
                        <?php
                        $sql = "select * from category_new where depth = 1";
                        $result = $mysqli->query($sql);
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                ?>
                                                                   <option val= <?= $row['cat_id'] ?> ><?= $row['categoryName'] ?></option>
                                                                                                                                                                                                                                                                                <?php }
                        } ?>
                    </select>
                 
              
                    <table>
                        <tr>
                            <td><input type="text" class="b1" name="category" value="" placeholder="type a new sub category"></td>
                            <td><input id="b" type="submit" value="submit" /></td>
                        </tr>
                        <tr><td><div id="b11"></div></td></tr>
                    </table>
                </td>
                    
                </tr>

                <tr>
                    <td><b>Sub category 1</b></td>
                    <td><select id="sub2" class="sub2" name="sub2" >
                    <option disabled selected value> -- select an option -- </option>
        
                    </select>
                   
                    <table>
                        <tr>
                            <td><input type="text" val="" class="b2" placeholder="type a new sub category"></td>
                            <td><input type="submit" id="b2" value="submit" /></td>
                        </tr>
                    </table>
                </td>
                    
                </tr>

                <tr>
                    <td><b>Sub category 2</b></td>
                    <td><select id="sub3" name="sub3" >
                     <option disabled selected value> -- select an option -- </option>
                    </select>
                  
                    <table>
                        <tr>
                            <td><input type="text" class="b3" placeholder="type a new sub category"></td>
                            <td><input type="submit" id="b3" value="submit" /></td>
                        </tr>
                    </table>
                </td>
                    <!--<td><input type="submit" value="add new category" /></td> -->
                </tr>
                <tr style="display:none;">
                    <td></td>
                    <td><input type="submit" class='submitCategoryForm' value="add a new category" /></td>
                </tr>
            </table>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id='saveCategory' class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="brand" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Add new Category</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="category-adder.php" method="post">
              <div data-mdb-input-init class="form-outline mb-4">
                        <label class="form-label" for="brand_name">Name</label>
                        <input type="text" id="brand_name" class="form-control" />
                       
                    </div>

                    <!-- Email input -->
                    <div data-mdb-input-init class="form-outline mb-4">
                        <label class="form-label" for="brand_url">barand Url</label>
                        <input type="email" id="brand_url" class="form-control" />
                        
                    </div>



                    <!-- Checkbox -->
                    <div data-mdb-input-init class="form-outline mb-4">
                        <label class="form-label" for="customFile">Brand Image</label>
                       <input type="file" name="imgbrand" class="form-control" id="customFile" />
                        </label>
                    </div>

                    <!-- Submit button -->
                   
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id='savebarand' class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>
        
        
    <div class="col-md-7 col-lg-8">
        <p><a href="add-inventory-items-to-a-product.php">Click here to add inventory items to a product</a></p>
<form action="product_adder.php" method="post" enctype="multipart/form-data">
    <h4 class="mb-3">New Product Creation Form</h4>
     <div class='row'>
     <div class="col-sm-6" style="display: none;">
         <label class="form-label" for="pid"><h6 class="my-0">Product ID</h6> </label>
         <input name="productid" id="pid" class="form-control" disabled="disabled" placeholder="auto generated">
     </div>
     
    <div class="col-sm-6" style="display: none;">
         <label class="form-label" for="date"><h6 class="my-0">Date</h6>  Date</label>
         <input name="date_created" class="form-control" id="date" disabled="disabled" placeholder="">
     </div>
     </div>
     <div class="row">
     <div class="col-12">
         <label class="form-label" for="description"><h6 class="my-0"> Description</h6> </label>
         <textarea name="description" class="form-control" id="description" placeholder=""></textarea>
     </div>
     </div>

     <div class="row">
              <div class="col-sm-6">
         <label class="form-label" for="supplier"><h6 class="my-0">Vendor Information</h6></label>
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


     </div>
     
          
    <div class="col-sm-6">
         <label class="form-label" for="category"><h6 class="my-0">Returns policy of a product</h6></label>
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
     
         
     </div>


    <div class="row">
            <div class="col-sm-6">
         <label class="form-label" for="category"><h6 class="my-0">Category</h6></label>
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
                 <a href="new-category.php" title="new-brand-creation" data-bs-toggle="modal" data-bs-target="#category">create new category</a>
     </div>
     
     
    <div class="col-sm-6">
         <label class="form-label" for="category"><h6 class="my-0"> Brand</h6></label>
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
                 <a href="brand-adder.php" title="new-brand-creation" data-bs-toggle="modal" data-bs-target="#brand">create new brand</a>
     </div>
        
    </div> 

    <div class="row"> 
    <div class="col-12">
         <label class="form-label" for="category"><h6 class="my-0">Product Information</h6></label>
          <td><textarea class="form-control" name="produt_info" placeholder=""></textarea></td>

     </div>
    </div>
     <div class="row">
    <div class="col-12">
         <label class="form-label" for="category"><h6 class="my-0">Additional Information</h6></label>
         <textarea  class="form-control" name="additional_info" placeholder=""></textarea>

     </div>
     </div>

     

     

     
    <div class="row">
        <b>SKU (SKu is must be in jason)</b>
        <textarea width="162px" height="200px" name="sku" value='{"size": "50cl"}'></textarea> 
        
    </div>
    <!--
    <div class="row">
         
            <b>Quintity in inventory</b>
            
                <input class="form-control" name="quintity_in_inventory">
            
        
            
        <b>Reorder Qunitiy</b>
            
                <input class="form-control" name="reorder_quintity">
            
        
    </div>
    -->
    <div class="row">
        <b>Cost Price</b>
            
                <input class="form-control" name="cost">
    </div>
    <div>
            <b>Barcode Number </b>
            <input type="text" class="form-control"   name="barcode" placeholder="bar code number"/>
    
 
    </div>
          <div>Select a picture for this product (*jpg,png only)
            
            <input type="file" name="files[]" multiple name="inventory_item_images" id="fileToUpload" multiple/>

            </div>
    
        <div  style="border: 3px solid brown; max-width: 400px; margin-top: 20px;">
              <img id="imagePreview" src="../e.jpg"  width="200px" height="200px" />
        </div>
        
        <br/>
        <br/>
        <div class="col-sm-6">
         <input type="submit"  class="form-control"  value="submit form for processing" />   
        </div>
        
        <br/>
        <br/>
</form> 
        
    </div>    
 
</div>
</body>
	<!-- <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script> -->

     
   <script type="text/javascript">
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
      
       //$("select").searchable();

            $('#saveCategory').click(function() {
                let cat_name, cat_sku, cat_length, cat_depth;
                cat_name = $("#cat_name").val();
                cat_depth = $("#cat_depth").val();
                cat_length = $("#cat_length").val();
                cat_jason = $("#cat_jason").val();
                
                console.log(cat_name+cat_depth,cat_length,cat_jason);
              $.ajax({
                type: "POST",
                url: "category-adder.php",
                data: {cat_name : cat_name, cat_depth : cat_depth, cat_length : cat_length, cat_jason: cat_jason}, // serializes the form's elements.
                success: function(data)
                {
                  console.log(data); // show response from the php script.
                }
    });
           });
           
    
            $('#savebarand').click(function() {
                alert(1);
                let brand_name, brand_url;
                brand_name = $("#brand_name").val();
                brand_url = $("#brand_url").val();
                
       
                    $.ajax({
                type: "POST",
                url: "brand-adder.php",
                data: {brand_name : brand_name, brand_url : brand_url}, // serializes the form's elements.
                success: function(data)
                {
                  console.log(data); // show response from the php script.
                }
                        });
           });
    });
  </script>
</html>