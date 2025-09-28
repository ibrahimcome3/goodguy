<!DOCTYPE html>
<?php
require_once "includes.php";
session_start(); // Ensure session is started for potential user context

// Ensure necessary objects are available from includes.php or instantiate them
try {
	if (!isset($pdo)) {
		// This should ideally not happen if includes.php is working correctly
		throw new Exception("PDO connection not available.");
	}

	// Assuming these are standard objects needed. If includes.php provides them, these checks can be simpler.
	if (!isset($orders) || !($orders instanceof Order)) {
		$orders = new Order($pdo);
	}
	if (!isset($invt) || !($invt instanceof InventoryItem)) {
		$invt = new InventoryItem($pdo);
	}
	if (!isset($promotion) || !($promotion instanceof Promotion)) {
		$promotion = new Promotion($pdo);
	}
} catch (Exception $e) {
	error_log("Error setting up objects in order_detail.php: " . $e->getMessage());
	die("A critical error occurred. Please try again later or contact support.");
}
?>
<html lang="en">


<!-- molla/login.html  22 Nov 2019 10:04:03 GMT -->

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Order Detail</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
		integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
	<!-- Favicon -->
	<?php include "htlm-includes.php/metadata.php"; ?>
	<!-- Plugins CSS File -->
	<link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
	<!-- Main CSS File -->
	<link rel="stylesheet" href="assets/css/demos/demo-13.css">
	<style>
		.table.table-summary tbody td {
			padding: 5px;
			height: 10px;
			border-bottom: .1rem solid #ebebeb;
		}
	</style>
</head>

<body>
	<div class="page-wrapper">
		<div class="container">

			<?php

			include "header_main.php";
			?>

		</div>
		<main class="main">
			<nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
				<div class="container">
					<ol class="breadcrumb">
						<?php echo breadcrumbs(); ?>
					</ol>
				</div><!-- End .container -->
			</nav><!-- End .breadcrumb-nav -->
			<div class="container">
				<div class="login-page">
					<div class="container">
						<div class="table-responsive small">
							<?php
							$order_id_from_get = isset($_GET['order_id']) ? filter_var($_GET['order_id'], FILTER_SANITIZE_NUMBER_INT) : null;
							if (!$order_id_from_get) {
								die("Invalid Order ID.");
							}

							// Fetch main order details to get the overall status, payment method, and creation date
							$mainOrderDetails = null;
							$mainOrderStatus = 'unknown'; // Default status if order not found
							$paymentMethod = 'N/A';
							$orderDateCreated = null;
							$estimatedDelivery = 'Will be estimated once shipped.'; // Default delivery estimate
							
							$nonDeletableStatuses = ['completed', 'concluded', 'delivered', 'cancelled']; // Define non-deletable statuses
							try {
								$mainOrderDetails = $orders->get_order_by_id($order_id_from_get); // Assumes this method exists in Order class
								if ($mainOrderDetails) {
									$mainOrderStatus = isset($mainOrderDetails['order_status']) ? strtolower($mainOrderDetails['order_status']) : 'unknown';
									$paymentMethod = $mainOrderDetails['payment_method'] ?? 'N/A';
									$orderDateCreated = $mainOrderDetails['order_date_created'] ?? null;

									if (in_array($mainOrderStatus, ['delivered', 'completed', 'concluded'])) {
										$estimatedDelivery = "Delivered";
									} elseif ($mainOrderStatus === 'shipped' && $orderDateCreated) {
										$estimatedDelivery = date('M d, Y', strtotime($orderDateCreated . ' +3 days')) . " - " . date('M d, Y', strtotime($orderDateCreated . ' +7 days'));
									} elseif (in_array($mainOrderStatus, ['processing', 'paid']) && $orderDateCreated) {
										$estimatedDelivery = "Typically 3-7 business days after shipping.";
									}
								}
							} catch (Exception $e) {
								error_log("Error fetching main order details for order ID $order_id_from_get: " . $e->getMessage());
							}
							?>
							<p>Order #<?= htmlspecialchars($order_id_from_get) ?></p>
							<p><strong>Payment Method:</strong>
								<?= htmlspecialchars(ucwords(str_replace('_', ' ', $paymentMethod))) ?></p>
							<p><strong>Estimated Delivery:</strong> <?= htmlspecialchars($estimatedDelivery) ?></p>

							<?php if ($order_id_from_get): // Only show track button if order ID is valid ?>
								<a href="track_order.php?order_id=<?= htmlspecialchars($order_id_from_get) ?>"
									class="btn btn-info btn-sm mb-3">Track Order</a>
							<?php endif; ?>
							<table class="table table-striped table-sm table table-summary">
								<thead>
									<tr>
										<th scope="col">Item</th>
										<th scope="col">Description</th>
										<th scope="col">Price</th>
										<th scope="col">Quantity</th>
										<th scope="col">Status</th>
										<th scope="col">Action</th>

									</tr>
								</thead>
								<tbody class="table-summary table">
									<?php
									try {
										$items_found_and_displayed = false; // Flag to track if any items are processed
										$stmt_order_items = $orders->get_order_item($order_id_from_get); // Use sanitized order_id
										if ($stmt_order_items) {
											while ($row = $stmt_order_items->fetch(PDO::FETCH_ASSOC)) {
												$items_found_and_displayed = true; // Set flag as an item is being processed
												// Assuming $row contains 'InventoryItemID', 'description', 'item_price', 'quwantitiyofitem', 'status'
												$inventoryItemId = $row['InventoryItemID'] ?? null;
												$productDescription = $row['description'] ?? 'N/A'; // Get description from order item data
												$itemPrice = $row['item_price'] ?? ($orders->get_order_item_price($inventoryItemId, $order_id_from_get) ?? 0);
												$quantity = $row['quwantitiyofitem'] ?? 0;
												$itemStatus = $row['status'] ?? 'N/A';
												$productImage = $inventoryItemId ? $invt->get_product_image($inventoryItemId) : 'assets/images/no-image-placeholder.png';
												$productIdForPromoCheck = $invt->getProductIdForInventoryItem($inventoryItemId);
												?>
												<tr>
													<td>
														<div>
															<figure>
																<a
																	href="product-detail.php?itemid=<?= htmlspecialchars($inventoryItemId) ?>">
																	<img src="<?= htmlspecialchars($productImage) ?>" width="70"
																		alt="Product Image">
																</a>
															</figure>
															<?php if ($productIdForPromoCheck && $promotion->check_if_item_is_in_promotion($productIdForPromoCheck)): ?>
																<span
																	style="margin-left: 10px;color: green; background-color: yellow;">On
																	Sale</span>
															<?php endif; ?>

														</div>
													</td>
													<td>
														<div>
															<a
																href="product-detail.php?itemid=<?= htmlspecialchars($inventoryItemId) ?>">
																<?= htmlspecialchars($productDescription) ?>
															</a>
														</div>
													</td>
													<td>&#8358;<?= htmlspecialchars(number_format($itemPrice, 2)) ?></td>
													<td><?= htmlspecialchars($quantity) ?></td>
													<td><?= htmlspecialchars(ucfirst($itemStatus)) ?></td>
													<td>
														<?php if ($mainOrderStatus !== 'completed') { ?>
															<?php if (!in_array($mainOrderStatus, $nonDeletableStatuses)) { ?>
																<?php $delete_link = "delete-order-item.php?oid=" . htmlspecialchars($order_id_from_get) . "&oitem=" . htmlspecialchars($inventoryItemId); ?>
																<a onclick="return confirm('Are you sure you want to remove this item from the order?');"
																	href="<?= $delete_link ?>" style="color: blue;">Remove</a>
															<?php } else { ?>

															<?php } ?>

														<?php } ?>

													</td>
												</tr>
												<?php
											} // End of while loop
										} // End of if ($stmt_order_items)
									
										if (!$items_found_and_displayed) {
											echo "<tr><td colspan='6'>No items found for this order.</td></tr>";
										}
									} catch (Exception $e) {
										error_log("Error fetching order items: " . $e->getMessage());
										echo "<tr><td colspan='6'>Could not retrieve order items at this time.</td></tr>";
									}
									?>
								</tbody>
							</table>

						</div>
					</div><!-- End .container -->
				</div><!-- End .login-page section-bg -->
			</div>
		</main><!-- End .main -->

		<footer class="footer">
			<?php include "footer.php" ?>

		</footer><!-- End .footer -->
	</div><!-- End .page-wrapper -->
	<button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

	<!-- Mobile Menu -->
	<div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

	<?php include "mobile-menue-index-page.php"; ?>


	<!-- Sign in / Register Modal -->
	<?php include "login-modal.php"; ?>

	<?php include "jsfile.php"; ?>
</body>

</html>