<?php
session_start();
require_once "includes.php";
if (!isset($_SESSION['uid'])) {
	header("Location: login.php");
	exit();
}

// Ensure necessary objects are available from includes.php
// If not, they would need to be instantiated here.
// For this example, we'll assume includes.php provides $pdo
// and we'll instantiate User and Order if they aren't already.

if (!isset($user) || !($user instanceof User)) { // Assuming $user is the standard User object
	$user = new User($pdo);
}
if (!isset($orders) || !($orders instanceof Order)) { // Assuming $orders is the standard Order object
	$orders = new Order($pdo);
}

$user_details_for_form = []; // For the account details form
$user_name_for_greeting = "User"; // Default greeting name
try {
	$user_details_for_form = $user->get_user_records(); // Assuming this method fetches current user's details
	$user_name_for_greeting = !empty($user_details_for_form['customer_fname']) ? htmlspecialchars($user_details_for_form['customer_fname']) : "User";
} catch (Exception $e) {
	error_log("Error fetching user records for dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">


<!-- molla/dashboard.html  22 Nov 2019 10:03:13 GMT -->

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" type="text/javascript"></script>
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/bootstrap.bundle.min.js"></script>
	<!-- jsfile.php -->
	<!-- jQuery is already loaded from metadata.php -->
	<script src="assets/js/bootstrap.bundle.min.js"></script> <!-- Includes Popper.js, recommended for Bootstrap 4+ -->
	<!-- Other theme plugins like hoverIntent, waypoints, superfish, owl.carousel -->
	<script src="assets/js/jquery.hoverIntent.min.js"></script>
	<script src="assets/js/jquery.waypoints.min.js"></script>
	<script src="assets/js/superfish.min.js"></script>
	<script src="assets/js/owl.carousel.min.js"></script>
	<!-- Your theme's main JS file -->


	<!-- ... other scripts ... -->
	<script src="assets/js/main.js"></script>


	<title>Dashboard</title>
	<?php include "htlm-includes.php/metadata.php"; ?>
	<link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
	<!-- Main CSS File -->
	<link rel="stylesheet" href="assets/css/demos/demo-13.css">
	<link rel="stylesheet" href="assets/css/plugins/nouislider/nouislider.css">
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
		include "header_main.php";
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

				if (isset($_GET['error'])) {
					echo "<div style=\"margin-bottom: 8px;\"><center><p style='color: red;'>" . $_GET['error'] . "</p></center></div>";
				}

				?>

				<div class="dashboard">
					<div class="container">
						<div class="row">
							<aside class="col-md-4 col-lg-3">
								<ul class="nav nav-dashboard flex-column mb-3 mb-md-0" role="tablist">
									<li class="nav-item">
										<a class="nav-link active" id="tab-dashboard-link" data-toggle="tab"
											href="#tab-dashboard" role="tab" aria-controls="tab-dashboard"
											aria-selected="true">Dashboard</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="tab-orders-link" data-toggle="tab" href="#tab-orders"
											role="tab" aria-controls="tab-orders" aria-selected="false">Orders</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="tab-phone-link" data-toggle="tab" href="#tab-phone"
											role="tab" aria-controls="tab-phone" aria-selected="false">Phone Number</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="tab-address-link" data-toggle="tab" href="#tab-address"
											role="tab" aria-controls="tab-address" aria-selected="false">Adresses</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" id="tab-account-link" data-toggle="tab" href="#tab-account"
											role="tab" aria-controls="tab-account" aria-selected="false">Account
											Details</a>
									</li>
									<li class="nav-item">
										<a class="nav-link" href="logout.php">Sign Out</a>
									</li>
								</ul>
							</aside><!-- End .col-lg-3 -->

							<div class="col-md-8 col-lg-9">
								<div class="tab-content">
									<div class="tab-pane fade show active" id="tab-dashboard" role="tabpanel"
										aria-labelledby="tab-dashboard-link">
										<p>Hello <span
												class="font-weight-normal text-dark"><?= $user_name_for_greeting ?></span>
											(not <span
												class="font-weight-normal text-dark"><?= $user_name_for_greeting ?></span>?
											<a href="logout.php">Log
												out</a>)
											<br>
											From your account dashboard you can view your <a href="#tab-orders"
												class="tab-trigger-link link-underline">recent orders</a>, manage your
											<a href="#tab-address" class="tab-trigger-link">shipping and billing
												addresses</a>, and <a href="#tab-account" class="tab-trigger-link">edit
												your password and account details</a>.
										</p>
									</div><!-- .End .tab-pane -->

									<div class="tab-pane fade" id="tab-orders" role="tabpanel"
										aria-labelledby="tab-orders-link">
										<?php
										$order_count = 0;
										try {
											$order_count = $orders->count_number_of_orders();
										} catch (Exception $e) {
											error_log("Error counting orders: " . $e->getMessage());
										}
										if ($order_count === 0) {
											?>
											<p>No order has been made yet.</p>
											<a href="index.php" class="btn btn-outline-primary-2"><span>GO SHOP</span><i
													class="icon-long-arrow-right"></i></a>
										<?php } else { ?>
											<div class="form-group">
												<input type="text" id="orderSearchInput" class="form-control mb-3"
													placeholder="Search your orders (e.g., by ID, Status, Date)...">
											</div>

											<div class="table-responsive ">
												<?php // Removed 'small' class for potentially better default styling, can be added back if needed ?>
												<table class="table table-striped table-hover table-sm table-summary"
													id="ordersTable"> <?php // Added table-hover and an ID ?>
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
													<tbody> <?php // Removed redundant classes from tbody ?>
														<?php
														try {
															$stmt_orders = $orders->get_orders();
															if ($stmt_orders) {
																while ($row = $stmt_orders->fetch(PDO::FETCH_ASSOC)) {
																	?>
																	<tr>
																		<td><a style="color: blue"
																				href='order_detail.php?order_id=<?= htmlspecialchars($row['order_id']) ?>'><?= htmlspecialchars($row['order_id']) ?></a>
																		</td>
																		<td><?= htmlspecialchars($row['order_date_created']) ?></td>
																		<td>&#8358;<?= htmlspecialchars(number_format($row['order_total'], 2)) ?>
																		</td>
																		<td><?= htmlspecialchars($row['order_date_created']) ?></td>
																		<?php // Consider if this should be a different date ?>
																		<td><?= htmlspecialchars($row['order_status']) ?></td>
																		<td><a onclick="return confirm('Are you sure you want to cancel this order?');"
																				href="cancel-order.php?order_id=<?= htmlspecialchars($row['order_id']) ?>">Cancel</a>
																		</td>
																	</tr>
																<?php }
															}
														} catch (Exception $e) {
															error_log("Error fetching orders: " . $e->getMessage());
															echo "<tr><td colspan='6'>Could not retrieve orders at this time.</td></tr>";
														} ?>
													</tbody>
												</table>
											</div>
											<nav aria-label="Orders Page Navigation">
												<ul class="pagination justify-content-center" id="ordersPagination">
													<!-- Pagination links will be generated by JavaScript -->
												</ul>
											</nav>
										<?php } ?>
									</div><!-- .End .tab-pane -->


									<div class="tab-pane fade" id="tab-phone" role="tabpanel"
										aria-labelledby="tab-phone-link">
										<?php
										try {
											$stmt_phones = $user->get_phone_number(); // Assuming $user is the correct User object
											if ($stmt_phones && $stmt_phones->rowCount() > 0) {
												echo "<ul>";
												while ($row = $stmt_phones->fetch(PDO::FETCH_ASSOC)) {
													$ph = htmlspecialchars($row['PhoneNumber']);
													$phid = htmlspecialchars($row['phone_id']);
													?>
													<li data-phone-id="<?= $phid ?>">
														<p class="phon widget-title" data-phone-number="<?= $ph ?>">
															<?= $ph ?>
															<?php if (($row['default_'] ?? 0) === 1): ?>
																<span style="color: green; margin-left: 10px;">&#10004;</span>
															<?php endif; ?>
														</p>
														<p>
															<a class="edit-phone-number" data-phone-id="<?= $phid ?>"
																href="edit-phone-number-page.php?phone=<?= urlencode($ph) ?>&phone_id=<?= $phid ?>">Edit</a>
															&bull;
															<a href="delete_phone_number.php?phone_id=<?= $phid ?>"
																onclick="return confirm('Are you sure you want to delete this phone number?');">Delete</a>
															&bull;
															<a href="make-phone-number-default.php?phone_id=<?= $phid ?>">Make
																Default</a>
														</p>
													</li>
													<?php
												}
												echo "</ul>";
											} else {
												echo "<p>No phone numbers found. <a href='add-phone-number.php'>Add a phone number</a>.</p>"; // Link to add phone
											}
										} catch (Exception $e) {
											error_log("Error fetching phone numbers: " . $e->getMessage());
											echo "<p>Could not retrieve phone numbers at this time.</p>";
										}
										?>




										<!--<a href="category.html" class="btn btn-outline-primary-2"><span>GO SHOP</span><i class="icon-long-arrow-right"></i></a>-->
									</div><!-- .End .tab-pane -->

									<div class="tab-pane fade" id="tab-address" role="tabpanel"
										aria-labelledby="tab-address-link">
										<p>The following addresses will be used on the checkout page by default.</p>

										<div class="row">
											<div class="col">
												<?php
												try {
													$stmt_addresses = $user->get_address_(); // Assuming $user is the correct User object
													if ($stmt_addresses && $stmt_addresses->rowCount() > 0) {
														while ($row = $stmt_addresses->fetch(PDO::FETCH_ASSOC)) {
															?>
															<div class="card card-dashboard mb-3">
																<?php // Added mb-3 for spacing ?>
																<div class="card-body">
																	<h3 class="card-title">Shipping Address</h3>
																	<p>
																		<?= htmlspecialchars($row['address1']) ?><br>
																		<?php if (!empty($row['address2'])): ?>
																			<?= htmlspecialchars($row['address2']) ?><br>
																		<?php endif; ?>
																		<?= "City: " . htmlspecialchars($row['city']) ?><br>
																		<?= "State: " . htmlspecialchars($row['state_name']) ?><br>
																		<?php if (!empty($row['zip'])): ?>
																			<?= "Zip: " . htmlspecialchars($row['zip']) ?><br>
																		<?php endif; ?>
																		<a
																			href="edit-shipping_address.php?sno=<?= htmlspecialchars($row['shipping_address_no']) ?>">Edit</a>
																	</p>
																</div><!-- End .card-body -->
															</div><!-- End .card-dashboard -->
														<?php }
													} else {
														echo "<p>No shipping addresses found. <a href='add-address.php'>Add an address</a>.</p>"; // Link to add address
													}
												} catch (Exception $e) {
													error_log("Error fetching addresses: " . $e->getMessage());
													echo "<p>Could not retrieve addresses at this time.</p>";
												} ?>
											</div><!-- End .row -->
										</div><!-- .End .tab-pane -->
									</div>

									<div class="tab-pane fade" id="tab-account" role="tabpanel"
										aria-labelledby="tab-account-link">
										<form action="update_customer_records.php" method="POST">
											<div class="row">
												<div class="col-sm-6">
													<label>First Name *</label>
													<input
														value="<?= htmlspecialchars($user_details_for_form['customer_fname'] ?? '') ?>"
														type="text" name="fname" class="form-control" required>
												</div><!-- End .col-sm-6 -->

												<div class="col-sm-6">
													<label>Last Name *</label>
													<input type="text"
														value="<?= htmlspecialchars($user_details_for_form['customer_lname'] ?? '') ?>"
														name="lname" class="form-control" required>
												</div><!-- End .col-sm-6 -->
											</div><!-- End .row -->

											<label>Display Name *</label>
											<input type="text" class="form-control"
												value="<?= htmlspecialchars($user_details_for_form['customer_display_name'] ?? '') ?>"
												name="dname" required>
											<small class="form-text">This will be how your name will be displayed in the
												account section and in reviews</small>

											<label>Email address *</label>
											<input type="email"
												value="<?= htmlspecialchars($user_details_for_form['customer_email'] ?? '') ?>"
												name="cemail" class="form-control" required>

											<label>Current password (leave blank to leave unchanged)</label>
											<input type="password" name="cpassword" class="form-control"
												autocomplete="current-password">

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
	<script>
		$(document).ready(function () {
			// Orders Table: Client-side Search/Filter
			$("#orderSearchInput").on("keyup", function () {
				var value = $(this).val().toLowerCase();
				$("#ordersTable tbody tr").filter(function () {
					$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
				});
				// After filtering, re-apply pagination to the visible rows
				applyOrdersPagination();
			});

			// Orders Table: Client-side Pagination
			var ordersTable = $('#ordersTable');
			var ordersRows = ordersTable.find('tbody tr');
			var ordersPerPage = 5; // Set how many orders to show per page
			var numPages = Math.ceil(ordersRows.length / ordersPerPage);

			function showOrdersPage(pageNum) {
				var start = (pageNum - 1) * ordersPerPage;
				var end = start + ordersPerPage;

				ordersRows.hide().slice(start, end).show();

				// Update pagination controls
				$('#ordersPagination li').removeClass('active');
				$('#ordersPagination li').eq(pageNum - 1).addClass('active'); // Page numbers are 1-based
			}

			function applyOrdersPagination() {
				ordersRows = $("#ordersTable tbody tr:visible"); // Consider only visible rows after filtering
				numPages = Math.ceil(ordersRows.length / ordersPerPage);
				$('#ordersPagination').empty(); // Clear existing pagination

				if (numPages > 1) {
					// Previous button
					$('#ordersPagination').append(
						$('<li>').addClass('page-item').append(
							$('<a>').addClass('page-link').attr('href', '#').text('Previous').on('click', function (e) {
								e.preventDefault();
								var currentPage = $('#ordersPagination li.active').index(); // 0-based index
								if (currentPage > 0) {
									showOrdersPage(currentPage); // currentPage is 1-based for showOrdersPage
								}
							})
						)
					);

					for (var i = 1; i <= numPages; i++) {
						$('<li>').addClass('page-item')
							.append($('<a>').addClass('page-link').attr('href', '#').text(i))
							.appendTo('#ordersPagination')
							.on('click', function (e) {
								e.preventDefault();
								showOrdersPage(parseInt($(this).text()));
							});
					}
					// Next button
					$('#ordersPagination').append(
						$('<li>').addClass('page-item').append(
							$('<a>').addClass('page-link').attr('href', '#').text('Next').on('click', function (e) {
								e.preventDefault();
								var currentPage = $('#ordersPagination li.active').index(); // 0-based index
								var totalPagesForNav = $('#ordersPagination li').length - 2; // Exclude prev/next
								if (currentPage < totalPagesForNav) { // currentPage is 1-based for showOrdersPage
									showOrdersPage(currentPage + 2);
								}
							})
						)
					);
				}
				if (ordersRows.length > 0) {
					showOrdersPage(1); // Show the first page initially
				} else if ($("#orderSearchInput").val() !== "") {
					// If search yields no results, ensure table is empty (it should be due to filter)
					// and pagination is cleared.
				}
			}

			if (ordersRows.length > 0) {
				applyOrdersPagination();
			}
		});
	</script>
</body>

</html>