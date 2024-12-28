<!DOCTYPE html>
<?php
include "../includes.php";



function getrelatedproducts($category9, $_id_of_what_get_image){
include "../conn.php";
$sql = "SELECT * FROM `inventoryitem` WHERE `category` = ".$category9. " and InventoryItemID != ". $_id_of_what_get_image." limit 5";
$result = $mysqli->query($sql);
return $result;

}

$num_items_in_cart = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

<html lang="en">


<!-- molla/product-sidebar.html  22 Nov 2019 10:03:32 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <title>vendor Page</title>
    <?php include "../htlm-includes.php/metadata.php"; ?> 
        <style>
       .truncate {
   overflow: hidden;
   text-overflow: ellipsis;
   display: -webkit-box;
   -webkit-line-clamp: 2; /* number of lines to show */
           line-clamp: 2;
   -webkit-box-orient: vertical;
}

    .flexbox {
    display: flex;
    flex-wrap: wrap;
    min-height: 200px;
     flex-grow: 1;
  flex-shrink: 0;
  flex-basis: 220px;
  
    }
    
    .srg{
        margin-left: 10px;
        
    }
    </style>
</head>

<body>

    <div class="page-wrapper">
        
        <?php

         include "../header-for-other-pages.php";
        ?>
        
             <main class="main">
                        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                            
                                <div class="container d-flex align-items-center">
                                        <ol class="breadcrumb">
                                                 <li>
                                                     <a href="#">add a product</a>
                                                 </li>
                                                 <li class = "srg">
                                                     <a href="#">Products</a>
                                                 </li>
                                        </ol>
                                        

                  
                                </div><!-- End .container -->
                               <div class="container"> 
                                <div class="row">
                                    <div class="col">
                                        <h4>Add a product</h4>
                                      <form action="" method="post" class="dropzone" id="my-dropzone" >
                            
                            <div class="form-group">  
                            <label for="pname">Product Name*</label>
                            <input type="text" class="form-control" id="pname" name="product_description" placeholder="Your name.." />
                            
                            </div>
                              <div class="form-group"> 
                            <label for="product_description">Product Description* (0/300)</label>
                            <textarea id="subject" class="form-control" name="product_description" placeholder="" style="height:200px"></textarea>
                           
                            </div>
                            <div class="form-group"> 
                            <label for="uniform_product_number">Uniform product code*</label>
                            <input type="text" class="form-control" id="uniform_product_number" name="uniform_product_number" placeholder="" />
                           
                            </div> 
                            <div class="form-group">
                            <label for="product_sku_number">Product SKU NUmber*</label>
                            <input type="text" class="form-control" id="product_sku_number" name="product_sku_number" placeholder="ABCD1235"  />
                            
                            </div> 
                            
                             <div class="form-group">
                            <label for="product_price">Product price*</label>
                            <input type="text" class="form-control" id="product_price" name="product_price" placeholder="N104,400.00"  />
                            
                            </div> 
                               
                            <div class="form-group">  
                            
                             <label for="product_price">Product Category*</label>
                            <select class="form-control" id="category" name="category">
                              <option value="">Select a category</option>
                              <option value="technology">Technology</option>
                            </select>
                            </div> 
                            
                            <div style="border: 1px solid yellow; margin-top: 20px; margin-bottom: 20px; padding: 10px;">
                                
                                <div style="width: 600px; height: 300px;"> upload an image
                                <input type="file"  name="files[]" multiple>
                              
                            </div>
                                
                            </div>
                            <div class="form-group">
                            <input type="submit" name="submit_contact_form" class="for-logging-in btn btn-outline-primary-2" value="Add Product">
                            </div> 
                 
                        
                          </form>
                            <script>
                               $("div#my-dropzone").dropzone({ url: "/file/post" });      
                            </script>            
                                    </div>
                                </div>
                        </div>
                        </nav><!-- End .breadcrumb-nav -->


                </main><!-- End .main -->


        <footer class="footer">
               <?php include "../footer.php"; ?>
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
       <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

    <?php include "../mobile-menue-index-page.php"; ?>
    <!-- Sign in / Register Modal -->
    <?php include "../login-modal.php"; ?>

    <!-- Plugins JS File -->
    <?php include "../jsfile.php"; ?>

     
 
</body>
 <script src="../assets/js/loadrelateditems.js"></script>
 <script type="text/javascript">
$(document).ready(function(){
  $(".submit").click(function(){

     if($('.size').length > 0){ 
     var size = $('.size option:selected').val();
     if(size == "" || size == "#") {
        alert("Please select a a size");
        return false;
     }
  } 
  });
});
</script>
 


<!-- molla/product-sidebar.html  22 Nov 2019 10:03:37 GMT -->
</html>