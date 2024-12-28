<?php  require_once "includes.php"; 
       if(!isset($_SESSION['uid'])){
    header("Location: login-page.php");
    exit();
  }
 
 $user__ = $user_->get_user_records();
 ?>
<!DOCTYPE html>
<html lang="en">


<!-- molla/dashboard.html  22 Nov 2019 10:03:13 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
	<style>
		.table.table-summary tbody td {
			padding: 5px;
			height: 15px;
			border-bottom: .1rem solid #ebebeb;
		}
	</style>
</head>

<body>
    <div class="page-wrapper">
       <?php
         include "header-for-other-pages.php";
       ?>

        <main class="main">
        
            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-3">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                        <li class="breadcrumb-item"><a href="category.php">Shop</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Account</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
						<?php 
						
						if(isset($_GET['error'])){
							echo "<div style=\"margin-bottom: 8px;\"><center><p style='color: red;'>".$_GET['error']."</p></center></div>";
						}
						
						?>
  
            	<div class="dashboard">
	                <div class="container">
	                	<div class="row">
	                		<aside class="col-md-4 col-lg-3">
	                			<ul class="nav nav-dashboard flex-column mb-3 mb-md-0" role="tablist">
								    <li class="nav-item">
								        <a class="nav-link active" id="tab-dashboard-link" data-toggle="tab" href="#tab-dashboard" role="tab" aria-controls="tab-dashboard" aria-selected="true">Dashboard</a>
								    </li>
								    <li class="nav-item">
								        <a class="nav-link" id="tab-orders-link" data-toggle="tab" href="#tab-orders" role="tab" aria-controls="tab-orders" aria-selected="false">Orders</a>
								    </li>
								    <li class="nav-item">
								        <a class="nav-link" id="tab-phone-link" data-toggle="tab" href="#tab-phone" role="tab" aria-controls="tab-phone" aria-selected="false">Phone Number</a>
								    </li>
								    <li class="nav-item">
								        <a class="nav-link" id="tab-address-link" data-toggle="tab" href="#tab-address" role="tab" aria-controls="tab-address" aria-selected="false">Adresses</a>
								    </li>
								    <li class="nav-item">
								        <a class="nav-link" id="tab-account-link" data-toggle="tab" href="#tab-account" role="tab" aria-controls="tab-account" aria-selected="false">Account Details</a>
								    </li>
								    <li class="nav-item">
								        <a class="nav-link" href="logout.php">Sign Out</a>
								    </li>
								</ul>
	                		</aside><!-- End .col-lg-3 -->

	                		<div class="col-md-8 col-lg-9">
	                			<div class="tab-content">
								    <div class="tab-pane fade show active" id="tab-dashboard" role="tabpanel" aria-labelledby="tab-dashboard-link">
								    	<p>Hello <span class="font-weight-normal text-dark">User</span> (not <span class="font-weight-normal text-dark">User</span>? <a href="#">Log out</a>) 
								    	<br>
								    	From your account dashboard you can view your <a href="#tab-orders" class="tab-trigger-link link-underline">recent orders</a>, manage your <a href="#tab-address" class="tab-trigger-link">shipping and billing addresses</a>, and <a href="#tab-account" class="tab-trigger-link">edit your password and account details</a>.</p>
								    </div><!-- .End .tab-pane -->

								    <div class="tab-pane fade" id="tab-orders" role="tabpanel" aria-labelledby="tab-orders-link">
								    	<?php if($orders->count_number_of_orders() === 0){ ?>
										<p>No order has been made yet.</p>
										<a href="index.php" class="btn btn-outline-primary-2"><span>GO SHOP</span><i class="icon-long-arrow-right"></i></a>
								        <?php  }else{ ?>
										
									<div class="table-responsive small">
										<table class="table table-striped table-sm table table-summary">
										  <thead>
											<tr>
											  <th scope="col">#OrderID</th>
											  <th scope="col">Order Date</th>
											  <th scope="col">Order Total</th>
											  <th scope="col">Delivery Date</th>
											  <th scope="col">Status</th>
											  <th scope="col">Action</th>
											</tr>
										  </thead>
										  <tbody class="table-summary table">
										  <?php  
										    $stmt = $orders->get_orders();
											while($row = $stmt->fetch()){
									      ?>
										  
											<tr>
											  <td><a style="color: blue" href='order_detail.php?order_id=<?=$row['order_id']?>'><?= $row['order_id'] ?></a></td>
											  <td><?= $row['order_date_created'] ?></td>
											  <td><?= $row['order_total'] ?></td>
											  <td><?= $row['order_date_created'] ?></td>
											  <td><?= $row['order_status'] ?></td>
											  <td><a onclick="return confirm('Are you sure?');" href="cancel-order.php?order_id=<?=$row['order_id']?>">Cancel</a></td>
											</tr>
											<?php } ?>
										  </tbody>
										</table>
									  </div>
										<?php } ?>
									</div><!-- .End .tab-pane -->

								    
									<div class="tab-pane fade" id="tab-phone" role="tabpanel" aria-labelledby="tab-phone-link">
									<?php 
												  $stmt = $user_->get_phone_number(); 
											      while($row = $stmt->fetch()){
												  $ph = $row['PhoneNumber'];
												  $phid = $row['phone_id'];
										
									?>
									<ul>
										<li phone-id="<?= $row['phone_id'] ?>">
										<p class="phon widget-title" phone-number=<?= $row['PhoneNumber'] ?>><?= $row['PhoneNumber'] ?> <span style="color: green; margin-left: 10px;"><?php if($row['default_'] === 1)  {echo "&#10004;"; } ?></span></p>
										<p><a class="edit-phone-number" phone-id="<?= $row['phone_id'] ?> "  <?php echo 'href="edit-phone-number-page.php?phone='.$ph.'&phone_id='.$phid.'"'; ?>>Edit</a>' &#8729;    <a href="delete_phone_number.php?phone_id=<?=$row['phone_id']?>" phone-id="<?= $row['CustomerID'] ?> ">Delete</a> &#8729;   <a href="make-phone-number-default.php?phone_id=<?=$row['phone_id']?>" phone-id="<?= $row['CustomerID'] ?> ">Make Default</a></p>
										</li>
									</ul>
									<?php 
									
									}
									
									?>
									
									
									
						
								    	<!--<a href="category.html" class="btn btn-outline-primary-2"><span>GO SHOP</span><i class="icon-long-arrow-right"></i></a>-->
								    </div><!-- .End .tab-pane -->

								    <div class="tab-pane fade" id="tab-address" role="tabpanel" aria-labelledby="tab-address-link">
								    	<p>The following addresses will be used on the checkout page by default.</p>

								    	<div class="row">
								    		<div class="col">
								  
											
											<?php $stmt = $user_->get_address_(); 
											      while($row = $stmt->fetch()){
											?>
								    		<div class="col-md-8 col-lg-9">
								    			<div class="card card-dashboard">
								    				<div class="card-body">
								    					<h3 class="card-title">Shipping Address</h3><!-- End .card-title -->

														<p><?= $row['address1']?><br>
														<?= $row['address2']?><br>
														<?= "State: ".$row['state']?><br>
														<?= "Zip: ".$row['zip']?><br>
														<?= "City: ".$row['city']?><br>
														
														<a href="edit-shipping_address.php?sno=<?= $row['shipping_address_no'] ?>">Edit</a>  </p>
								    				</div><!-- End .card-body -->
								    			</div><!-- End .card-dashboard -->
								    		</div><!-- End .col-lg-6 -->
											<?php } ?>
								    	</div><!-- End .row -->
								    </div><!-- .End .tab-pane -->
								</div>	

								    <div class="tab-pane fade" id="tab-account" role="tabpanel" aria-labelledby="tab-account-link">
								    	<form action="update_customer_records.php" method="POST">
			                				<div class="row">
			                					<div class="col-sm-6">
			                						<label>First Name *</label>
			                						<input value="<?= $user__['customer_fname'] ?>" type="text" name="fname" class="form-control" required>
			                					</div><!-- End .col-sm-6 -->

			                					<div class="col-sm-6">
			                						<label>Last Name *</label>
			                						<input type="text" value="<?= $user__['customer_lname'] ?>" name="lname" class="form-control" required>
			                					</div><!-- End .col-sm-6 -->
			                				</div><!-- End .row -->

		            						<label>Display Name *</label>
		            						<input type="text" class="form-control" value="<?= $user__['customer_display_name'] ?>" name="dname" required>
		            						<small class="form-text">This will be how your name will be displayed in the account section and in reviews</small>

		                					<label>Email address *</label>
		        							<input type="email" value="<?= $user__['customer_email'] ?>" name="cemail" class="form-control" required>

		            						<label>Current password (leave blank to leave unchanged)</label>
		            						<input type="password" name="cpassword" class="form-control">

		            						<label>New password (leave blank to leave unchanged)</label>
		            						<input type="password" name="npassword" class="form-control">

		            						<label>Confirm new password</label>
		            						<input type="password" name="cnpassword" class="form-control mb-2">

		                					<button type="submit" class="btn btn-outline-primary-2">
			                					<span>SAVE CHANGES</span>
			            						<i class="icon-long-arrow-right"></i>
			                				</button>
			                			</form>
								    </div><!-- .End .tab-pane -->
								</div>
	                		</div><!-- End .col-lg-9 -->
	                	</div><!-- End .row -->
	                </div><!-- End .container -->
                </div><!-- End .dashboard -->
            </div><!-- End .page-content -->
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
    <?php include "jsfile.php"; ?>
</body>
</html>