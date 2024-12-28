<?php
require_once "includes.php";
include "conn.php";
//echo $_SERVER['HTTP_REFERER'];
if(!isset($_SESSION['uid'])){
   header("Location: login.php");
   exit(); 
}

$url = $_SERVER['HTTP_REFERER'];
$tokens = explode('/', $url);
//echo $tokens[sizeof($tokens)-1];
if($tokens[sizeof($tokens)-1] === 'dashboard.php'){
    $_SESSION[ 'dashboard' ] = TRUE;
    $_SESSION['durl'] = $tokens[sizeof($tokens)-1];
}
try{

       $sql = "SELECT `shipping_address_no`, `customer_id`, `address1`, `address2`, `state`, ship_cost,  `zip`, `city` FROM `shipping_address` WHERE `customer_id`= ".$_SESSION['uid']." and shipping_address_no = ".$_GET['sno'];
       $res = $mysqli->query($sql);
       if($res){

         if ($res->num_rows == 1) {
            $row = $res->fetch_assoc();
           }

      }

}
catch(Exception $e)
    {
        echo $e->getMessage();
        exit();

    }

if(!empty($_POST)){
    $error = '';
    $customer_id = $_SESSION['uid'];
    $streetaddress1=$_POST['streetaddress1'];
    $streetaddress2=$_POST['streetaddress2'];
    $country= 'NIGERIA';
    $state=$_POST['state'];
    $shipment_area = $_POST['shipment'];
    $city=$_POST['city'];
    $zip=$_POST['zip'];

    if(empty($city)){
    $error =   "City field is empty";

    }else if(empty($zip)){
    $error =   "Zip field is empty";

    }else if(empty($customer_id)){
    header("Location: login.php");
    exit(5);

    }else if(empty($state)){
    $error =   "State field is empty";

    }else if(empty($streetaddress1)){
    $error =   "Address line one is empty";

    }else if(empty($streetaddress2)){
    $error =   "Address line toe is empty";
    }else if($shipment_area == -1){
    $error =   "You forgot to select your shipment area after selecting state";
    }else if($state == -1){
    $error =   "You forgot to select your ship state";
    }else{
    try{
      $sql = "UPDATE `shipping_address` SET `address1`='$streetaddress1',`address2`='$streetaddress2',`state`='$state',`zip`='$zip',`city`='$city', ship_cost = '$shipment_area'  WHERE shipping_address_no =". $_GET['sno'] ." and customer_id = $customer_id";
      $res = $mysqli->query($sql);

    if($res){
        if($_SESSION[ 'dashboard' ] === TRUE){
          unset( $_SESSION[ 'dashboard' ] );  
          header("Location: ".$_SESSION['durl']); 
          exit();          
        }
          header("Location: shipping-address-selection.php");
    }else{
        throw new Exception('<br>Could not submit your form. pls try again later<br>');
    }
    }catch(Exception $e)
    {
        echo $e->getMessage();
        exit();

    }
    }

 

    /*
    else{
    $sql = "UPDATE `customer` SET `customer_fname`='$fname',`customer_lname`='$lname', `customer_city`= '$city',`customer_email`= '$email',`customer_address1`='$streetaddress1',`customer_address2`='$streetaddress2',`customer_country`='$country',`customer_state`='$state',`customer_phone`= '$phone_number',`customer_zip`='$zip',`customer_status`='MEMBER',`password`='$password',`profile_image`='anonymous.jpg' WHERE customer_id = $customer_id ";
    echo $sql;
    $res = $mysqli->query($sql);
    if($res){
        header("Location: complete-registration-confermation-page.php");
    }

    }

    */
}

?>
<!DOCTYPE html>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Shipping address edit page</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
</head>

<body>
    
    <div class="page-wrapper">
        <?php
         include "header-for-other-pages.php";
        ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                      <?php  echo breadcrumbs();  ?>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page pb-8  pb-md-12 pt-lg-17 pb-lg-17">
            	<div class="container">
            	<?php if(isset($error)){ ?>    
                <div class="alert alert-danger" role="alert"><?php if(isset($error)){echo $error;} ?></div>
                <?php } ?>
                 <div class="row">
                        		<div class="col-lg-9">
                        		    <form action="" method="post">
                        			<h2 class="checkout-title">Edit your address</h2><!-- End .checkout-title -->
                						<label>Street address *</label>
                						<input type="text" class="form-control" name="streetaddress1"  value="<?= $row['address1'] ?>" placeholder="House number and Street name" required>
                						<input type="text" class="form-control" name="streetaddress2"  value="<?= $row['address2'] ?>" placeholder="Appartments, suite, unit etc ..." required>

                						<div class="row">
                        					<div class="col-sm-6">
                        						<label>Town / City *</label>
                        						<input type="text"  value="<?= $row['city'] ?>" name="city" class="form-control" required>
                        					</div><!-- End .col-sm-6 -->

                        						<div class="col-sm-6">
                        						<label>State *</label>
                        						
                        									<select name="state" id="state" class="form-control">
                                                            <option  value="-1">select state </option>
                                                            <?php
                                                            $ship = new Shipment();
                                                            $s = $ship->get_shipment_state();
                                                            while ($row__ = mysqli_fetch_array($s)) {
                                                                
                                                                ?>

                                                              <option value="<?= $row__['state_id'] ?>" <?php if($row__['state_id'] == '21') echo 'selected="selected"' ?>>
                                                                    <?= $row__['state_name'] ?>
                                                                  
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                        					</div><!-- End .col-sm-6 -->
                        				</div><!-- End .row -->

                        				<div class="row">
                        				    <div class=" col-sm-6">
                                                    <label for="sortby" style="color: blue;">Select shipping area *</label>
                                                   
                                                        <select name="shipment" id="shipment" class="form-control">
                                                            <option shipment-price="0.00" value="-1">select shipping
                                                                cost</option>
                                                            <?php
                                                            $ship = new Shipment();
                                                            $s = $ship->get_shipment_area();
                                                            while ($row_ = mysqli_fetch_array($s)) {
                                                                ?>

                                                                <option shipment-price="<?= $row_['area_cost'] ?>"
                                                                    value="<?= $row_['area_id'] ?>">
                                                                    <?= $row_['area_name'] ?>
                                                                    <?= "(N" . $row_['area_cost'] . ")" ?>
                                                                </option>
                                                            <?php } ?>
                                                        </select>
                                            </div>
                        					<div class="col-sm-6">
                        						<label>Postcode / ZIP *</label>
                        						<input type="text"  name="zip" value="<?= $row['zip'] ?>" class="form-control">
                        					</div><!-- End .col-sm-6 -->

                        				</div><!-- End .row -->
                                        <button type="submit" class="btn btn-primary btn-round">
                                                					<span>Save</span><i class="icon-long-arrow-right"></i>
                                                			 </button>
                                    </form>
                        		</div><!-- End .col-lg-9 -->

                        	</div><!-- End .row -->



            	</div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->

        <footer class="footer">

                <?php include "footer.php"; ?>

        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->
        <?php include "mobile-menue.php"; ?>

    <!-- Sign in / Register Modal -->
    <?php include "login-module.php"; ?>

    <!-- Sign in / Register Modal -->


    <!-- Plugins JS File -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/main.js"></script>
        <script type="text/javascript">
                $(document).ready(function(){
            $("#state").change(function(){ 
       
                var state_id = $("#state option:selected").val();
                let arrayString = '';
  
    $.ajax({
        url: 'select-state.php', 
        type: 'POST',
        //dataType: 'json',
        data: {state_id : state_id},
        success: function(data, status, xhr) {
            arrayString += "<option value='-1'>select a area</option>";
            console.log(data);
            $.each(JSON.parse(data), function(key,valueObj){
               arrayString += "<option value = '"+ key +"' >"+ valueObj +"</option>";
            });
            console.log(arrayString);
            $("#shipment").html(arrayString);
        },
        error: function(xhr, status, error) {
             alert("Error = "+status);
        }
    });    
    var opt = $(this).find('option:selected')[0];   

            });
        });
    </script>
</body>


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->
</html>