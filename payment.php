<?php
// payment.php
// Start session if not already started (best practice: start in a central bootstrap/include file)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include essential files and configurations
require_once "includes.php"; // Should define $pdo, classes, breadcrumbs(), etc.
require_once 'vendor/autoload.php'; // Composer autoloader for Paystack SDK

// Load environment variables for sensitive keys like Paystack Secret
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    // Log error, but don't necessarily stop execution in production
    error_log("Error loading .env file: " . $e->getMessage());
    // Consider fallback configuration or showing a generic error message
}

// Use Paystack SDK Namespace
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

// --- 1. Input Validation and Order Fetching ---

// Validate Order ID from GET parameter
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id']) || (int) $_GET['order_id'] <= 0) {
    $_SESSION['error_message'] = "Invalid Order ID.";
    header("Location: index.php"); // Redirect to a safe page
    exit();
}
$orderId = (int) $_GET['order_id'];

// Instantiate necessary classes, passing the PDO connection
try {
    $order = new Order($pdo);
    $invt = new InventoryItem($pdo); // Assuming InventoryItem class needs PDO
    $user = new User($pdo);
} catch (Exception $e) {
    error_log("Error instantiating classes: " . $e->getMessage());
    // Display a generic error page or message
    die("A system error occurred. Please try again later.");
}

// Fetch main order details
$orderDetails = $order->getOrderDetails($orderId);
if (!$orderDetails) {
    $_SESSION['error_message'] = "Order #{$orderId} not found.";
    header("Location: index.php"); // Or user's order history page
    exit();
}

// Fetch order items for display
$orderItems = $order->getOrderItems($orderId);
if ($orderItems === false) {
    // Log error, but allow page to load (will show "No items found")
    error_log("Failed to fetch items for order ID: " . $orderId);
    $orderItems = []; // Ensure it's an empty array for JavaScript
}

// Fetch Shipping Cost (for display purposes)
// IMPORTANT: Verify 'shipping_area_id' is the correct key in your $orderDetails array
$shippingAreaIdFromOrder = $order->getShippingAreaIdFromAddress($orderDetails['order_shipping_address']) ?? null;
$shippingCost = null;
if ($shippingAreaIdFromOrder !== null && $shippingAreaIdFromOrder > 0) {
    $shippingCost = $order->getShippingAreaCost((int) $shippingAreaIdFromOrder);
}

// Fetch Shipping Address details
// IMPORTANT: Verify 'order_shipping_address' is the correct key for the ADDRESS ID
$shippingAddressId = $orderDetails['order_shipping_address'] ?? null;
$shippingAddress = null;
if ($shippingAddressId) {
    // This method should return the full address details array
    $shippingAddress = $order->getOrderShippingAddress((int) $shippingAddressId);
}

// --- 2. Handle Payment Processing (POST Request) ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $paymentMethod = $orderDetails['payment_method'] ?? null;

    // --- Recalculation and Update (USE WITH CAUTION) ---
    try {
        // a. Get Order Items again (needed for subtotal)
        // Note: This assumes getOrderItems returns items with the price paid/current price
        $currentOrderItems = $order->getOrderItems($orderId);
        if ($currentOrderItems === false) {
            throw new Exception("Could not retrieve order items for recalculation.");
        }

        // b. Recalculate Subtotal
        $recalculatedSubtotal = 0.00;
        foreach ($currentOrderItems as $item) {
            // Use the price stored with the order item
            $itemPrice = (float) ($item['item_price'] ?? 0);
            $itemQty = (int) ($item['quwantitiyofitem'] ?? 0); // Fix typo if possible
            $recalculatedSubtotal += ($itemPrice * $itemQty);
        }

        // c. Recalculate Shipping Cost (using existing logic)
        $shippingAreaId = $order->getShippingAreaIdFromAddress($orderDetails['order_shipping_address']);
        $recalculatedShippingCost = 0.00;
        if ($shippingAreaId) {
            $recalculatedShippingCost = $order->getShippingAreaCost($shippingAreaId) ?? 0.00;
        }

        // d. Recalculate Final Total (Add taxes/subtract discounts if applicable)
        $recalculatedFinalTotal = $recalculatedSubtotal + $recalculatedShippingCost;

        // e. Update the Order in the Database
        $updateSuccess = $order->updateOrderCosts(
            $orderId,
            $recalculatedSubtotal,
            $recalculatedShippingCost,
            $recalculatedFinalTotal
        );

        if (!$updateSuccess) {
            // Log error but maybe proceed with original total? Or stop?
            error_log("Failed to update order costs for Order ID $orderId before payment.");
            // Decide how to handle this failure - stop payment? Use original total?
            // Using original total for safety:
            // $finalOrderTotal = (float) ($orderDetails['order_total'] ?? 0);
            // Or stop:
            // $_SESSION['payment_error'] = "Failed to update order details. Please try again.";
            // header("Location: payment.php?order_id=" . $orderId);
            // exit();
        } else {
            // Use the newly calculated total if update was successful
            $finalOrderTotal = $recalculatedFinalTotal;
            // Optional: Refresh $orderDetails if needed elsewhere after this point
            // $orderDetails = $order->getOrderDetails($orderId);
        }

    } catch (Exception $e) {
        error_log("Error recalculating order costs for Order ID $orderId: " . $e->getMessage());
        // Decide how to handle - stop payment? Use original total?
        // Using original total for safety:
        $finalOrderTotal = (float) ($orderDetails['order_total'] ?? 0);
        // Or stop:
        // $_SESSION['payment_error'] = "An error occurred preparing your order for payment. Please try again.";
        // header("Location: payment.php?order_id=" . $orderId);
        // exit();
    }

    // --- Paystack Card Payment Logic ---
    if ($paymentMethod == 'card') {

        $paystackSecretKey = getenv('PAYSTACK_SECRET_KEY');
        if (empty($paystackSecretKey)) {
            error_log("Paystack Secret Key not found or empty in environment variables.");
            $_SESSION['payment_error'] = "Payment gateway configuration error [PSK01]. Please contact support.";
            header("Location: payment.php?order_id=" . $orderId);
            exit();
        }

        // CRUCIAL: Use the final order_total stored in the database for this order
        $finalOrderTotal = (float) ($orderDetails['order_total'] ?? 0);

        // Prevent payment if total is zero or negative
        if ($finalOrderTotal <= 0) {
            $_SESSION['payment_error'] = "Invalid order total (₦" . number_format($finalOrderTotal, 2) . "). Cannot proceed with payment.";
            header("Location: payment.php?order_id=" . $orderId);
            exit();
        }

        // Convert the FINAL total to kobo (Paystack's lowest currency unit for NGN)
        $amountInKobo = (int) round($finalOrderTotal * 100);
        $txRef = "GG-" . $orderId . "-" . time(); // Generate a unique transaction reference

        // Construct full callback URL dynamically
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $callbackUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/paystack-callback.php'; // Ensure this callback file exists and handles verification

        // Fetch customer details (use actual data from order or user session)
        // IMPORTANT: Verify these keys in your $orderDetails array
        $customerEmail = $orderDetails['customer_email'] ?? ($shippingAddress['user_email'] ?? null); // Get from order, fallback to address user email
        $customerName = $orderDetails['customer_name'] ?? ($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name'] ?? 'Valued Customer');

        // Ensure email is available
        if (empty($customerEmail)) {
            $_SESSION['payment_error'] = "Customer email is missing. Cannot proceed with payment.";
            error_log("Missing customer email for order ID: " . $orderId);
            header("Location: payment.php?order_id=" . $orderId);
            exit();
        }


        try {
            $paystack = new Paystack($paystackSecretKey);
            $tranx = $paystack->transaction->initialize([
                'amount' => $amountInKobo,       // Amount in Kobo
                'email' => $customerEmail,      // Customer's email
                'reference' => $txRef,              // Unique transaction reference
                'callback_url' => $callbackUrl,        // Your verification URL
                'metadata' => [                    // Optional: Store extra info
                    'order_id' => $orderId,
                    'customer_name' => $customerName,
                    'description' => "Payment for Order #{$orderId}",
                    'custom_fields' => [
                        [
                            "display_name" => "Order ID",
                            "variable_name" => "order_id",
                            "value" => (string) $orderId
                        ],
                        [
                            "display_name" => "Customer Name",
                            "variable_name" => "customer_name",
                            "value" => $customerName
                        ]
                    ]
                ]
            ]);

            // Check if initialization was successful and redirect to Paystack's page
            if ($tranx->status && isset($tranx->data->authorization_url)) {
                // Optional: Store reference in session if needed for callback verification
                // $_SESSION['paystack_reference_' . $orderId] = $txRef;
                header('Location: ' . $tranx->data->authorization_url);
                exit();
            } else {
                // Handle initialization failure
                $errorMessage = $tranx->message ?? 'Unknown Paystack error during initialization.';
                $_SESSION['payment_error'] = "Could not initialize payment. Reason: " . htmlspecialchars($errorMessage);
                error_log("Paystack Init Error for Order $orderId: " . json_encode($tranx));
                header("Location: payment.php?order_id=" . $orderId);
                exit();
            }

        } catch (ApiException $e) {
            // Handle API errors (e.g., connection issues, invalid key)
            $_SESSION['payment_error'] = "Payment API error: " . htmlspecialchars($e->getMessage());
            error_log("Paystack API Exception for Order $orderId: " . $e->getMessage() . " | Response: " . ($e->getResponseObject() ? json_encode($e->getResponseObject()) : 'N/A'));
            header("Location: payment.php?order_id=" . $orderId);
            exit();
        } catch (Exception $e) {
            // Handle other general errors during the process
            $_SESSION['payment_error'] = "Payment system error: " . htmlspecialchars($e->getMessage());
            error_log("General Payment Exception for Order $orderId: " . $e->getMessage());
            header("Location: payment.php?order_id=" . $orderId);
            exit();
        }

        // --- Bank Transfer Logic ---
    } elseif ($paymentMethod == 'bank_transfer') {
        // Update order status to indicate payment is pending confirmation
        $order->updateOrderStatus($orderId, 'on-hold'); // Use 'on-hold' or 'pending-payment'
        // Do NOT clear the cart here; payment is not yet confirmed.
        // Redirect to a confirmation page explaining the next steps
        header("Location: order-confirmation.php?order_id=" . $orderId . "&method=transfer");
        exit();

        // --- Pay on Delivery Logic ---
    } elseif ($paymentMethod == 'pay_on_delivery') {
        // Update order status to 'processing' as it's confirmed for COD
        $order->updateOrderStatus($orderId, 'processing');
        // Clear the user's cart ONLY AFTER the order is successfully confirmed
        unset($_SESSION['cart']);
        // Redirect to a confirmation page
        header("Location: order-confirmation.php?order_id=" . $orderId . "&method=cod");
        exit();

        // --- Invalid Payment Method ---
    } else {
        $_SESSION['payment_error'] = "Invalid payment method selected for this order.";
        // Redirect back to checkout or order history, as payment page isn't appropriate
        header("Location: checkout.php");
        exit();
    }
}

// --- 3. HTML Page Rendering ---
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - Order #<?= htmlspecialchars($orderId) ?> - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; // Include meta tags, base CSS links ?>
    <style>
        /* Basic styling for pagination and table */
        .pagination-controls {
            margin-top: 20px;
            text-align: center;
        }

        .pagination-controls button {
            margin: 0 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .pagination-controls button:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .order-items-table img {
            max-width: 60px;
            height: auto;
            margin-right: 10px;
        }

        .order-items-table td {
            vertical-align: middle !important;
        }

        .order-items-table th,
        .order-items-table td {
            padding: 10px 8px;
            /* Spacing inside cells */
        }

        .order-summary-details strong {
            display: inline-block;
            min-width: 100px;
        }

        /* Align labels */
        .order-summary-details hr {
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <?php include "header-for-other-pages.php"; // Include the site header ?>

    <main class="main">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
            <div class="container d-flex align-items-center">
                <ol class="breadcrumb">
                    <?php echo breadcrumbs(); // Assumes breadcrumbs() function is defined in includes.php ?>
                </ol>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="page-content">
            <div class="container">
                <h3>Complete Your Payment</h3>
                <h4>Order #<?= htmlspecialchars($orderId) ?></h4>

                <!-- Display Payment Error Messages -->
                <?php if (isset($_SESSION['payment_error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['payment_error']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['payment_error']); // Clear message after displaying ?>
                <?php endif; ?>
                <!-- Display General Error Messages -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error_message']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php unset($_SESSION['error_message']); // Clear message after displaying ?>
                <?php endif; ?>


                <div class="row">
                    <!-- Left Column: Order Summary & Shipping Address -->
                    <div class="col-lg-6">
                        <!-- Order Summary Card -->
                        <div class="card card-summary mb-3">
                            <div class="card-header">Order Summary</div>
                            <div class="card-body order-summary-details">
                                <?php // Optional: Display Subtotal if available in $orderDetails ?>
                                <?php if (isset($orderDetails['order_subtotal'])): ?>
                                    <p><strong>Subtotal:</strong> &#8358;
                                        <?= htmlspecialchars(number_format($orderDetails['order_subtotal'], 2)) ?>
                                    </p>
                                <?php endif; ?>

                                <?php // Display Shipping Cost (fetched for display) ?>
                                <?php if ($shippingCost !== null): ?>
                                    <p><strong>Shipping:</strong> &#8358;
                                        <?= htmlspecialchars(number_format($shippingCost, 2)) ?>
                                    </p>
                                <?php elseif ($shippingAreaIdFromOrder !== null): ?>
                                    <p><strong>Shipping:</strong> <em>(Cost not found)</em></p>
                                <?php else: ?>
                                    <p><strong>Shipping:</strong> <em>(Not applicable)</em></p>
                                <?php endif; ?>

                                <?php // Display Taxes, Discounts etc. if applicable and available ?>

                                <hr>

                                <p><strong>Order Total:</strong> &#8358;
                                    <?= htmlspecialchars(number_format($orderDetails['order_total'] ?? 0, 2)) ?>
                                </p>

                                <p style="margin-top: 15px;"><strong>Payment Method:</strong>
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $orderDetails['payment_method'] ?? 'N/A'))) ?>
                                </p>
                                <p><strong>Order Status:</strong> <span
                                        class="badge badge-info"><?= htmlspecialchars(ucwords($orderDetails['order_status'] ?? 'N/A')) ?></span>
                                </p>
                                <p><strong>Date Placed:</strong>
                                    <?= isset($orderDetails['order_date_created']) ? htmlspecialchars(date("F j, Y, g:i a", strtotime($orderDetails['order_date_created']))) : 'N/A' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Shipping Address Card -->
                        <div class="card card-summary mb-3">
                            <div class="card-header">Shipping Address</div>
                            <div class="card-body">
                                <?php if ($shippingAddress):
                                    // Fetch state name using the dedicated function and address ID
                                    $stateName = $order->getShippingAddressStateName((int) $shippingAddressId);
                                    // Fetch primary phone number for the logged-in user
                                    $primaryPhoneNumber = isset($_SESSION['uid']) ? $user->getPrimaryActivePhoneNumber((int) $_SESSION['uid']) : null;
                                    ?>
                                    <address>
                                        <?= htmlspecialchars($shippingAddress['first_name'] ?? '') ?>
                                        <?= htmlspecialchars($shippingAddress['last_name'] ?? '') ?><br>
                                        <?= htmlspecialchars($shippingAddress['address1'] ?? '') ?><br>
                                        <?php if (!empty($shippingAddress['address2'])): ?>
                                            <?= htmlspecialchars($shippingAddress['address2']) ?><br><?php endif; ?>
                                        <?= htmlspecialchars($shippingAddress['city'] ?? '') ?>,
                                        <?= $stateName ? htmlspecialchars($stateName) : 'State N/A' ?>
                                        <?= htmlspecialchars($shippingAddress['zip'] ?? '') ?><br>
                                        <?php // Use correct key for zip ?>
                                        <?= htmlspecialchars($shippingAddress['country'] ?? '') ?><br>
                                        <?php if ($primaryPhoneNumber): ?>Phone:
                                            <?= htmlspecialchars($primaryPhoneNumber) ?>     <?php else: ?>Phone: N/A<?php endif; ?>
                                    </address>
                                <?php else: ?>
                                    <p class="text-danger">Shipping address details not found for this order.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Order Items & Payment Form -->
                    <div class="col-lg-6">
                        <!-- Order Items Card -->
                        <div class="card card-summary mb-3">
                            <div class="card-header">Items in this Order</div>
                            <div class="card-body">
                                <?php if (!empty($orderItems)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm order-items-table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Qty</th>
                                                    <th>Price</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody id="order-items-list">
                                                <!-- JS will populate this -->
                                                <tr>
                                                    <td colspan="4" class="text-center">Loading items...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="pagination-controls" class="pagination-controls">
                                        <!-- JS will populate this -->
                                    </div>
                                <?php else: ?>
                                    <p>No items found for this order.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Confirmation Form Card -->
                        <div class="card card-summary mb-3">
                            <div class="card-header">Confirm Payment</div>
                            <div class="card-body">
                                <form action="payment.php?order_id=<?= htmlspecialchars($orderId) ?>" method="post"
                                    id="payment-form">
                                    <?php if (($orderDetails['payment_method'] ?? null) == 'card'): ?>
                                        <p>Click the button below to proceed to secure card payment via Paystack.</p>
                                        <button type="submit" class="btn btn-primary btn-order btn-block">Proceed to Card
                                            Payment</button>
                                    <?php elseif (($orderDetails['payment_method'] ?? null) == 'bank_transfer'): ?>
                                        <p>Please make a bank transfer to the following account using your Order ID
                                            <strong>(<?= htmlspecialchars($orderId) ?>)</strong> as the payment reference:
                                        </p>
                                        <p><strong>Account Name:</strong> Goodguy Enterprises</p> <!-- Replace -->
                                        <p><strong>Account Number:</strong> 1234567890</p> <!-- Replace -->
                                        <p><strong>Bank Name:</strong> Example Bank Plc</p> <!-- Replace -->
                                        <hr>
                                        <p>After making the transfer, your order status will be updated once payment is
                                            manually confirmed. Click below to acknowledge.</p>
                                        <button type="submit" class="btn btn-primary btn-order btn-block">Confirm Order (Pay
                                            via Transfer)</button>
                                    <?php elseif (($orderDetails['payment_method'] ?? null) == 'pay_on_delivery'): ?>
                                        <p>Your order will be processed, and you will pay the courier upon delivery.</p>
                                        <button type="submit" class="btn btn-primary btn-order btn-block">Confirm Order (Pay
                                            on Delivery)</button>
                                    <?php else: ?>
                                        <p class="text-danger">The payment method for this order is invalid or not set.
                                            Please contact support or <a href="checkout.php">return to checkout</a>.</p>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
            </div><!-- End .container -->
        </div><!-- End .page-content -->
    </main><!-- End .main -->

    <?php include "footer.php"; // Include the site footer ?>

    <!-- JavaScript for Order Item Pagination -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Get Data (Passed from PHP)
            const allOrderItems = <?= json_encode($orderItems ?: []); ?>; // Ensure it's a valid JS array
            const itemsPerPage = 5; // Number of items per page
            let currentPage = 1;

            // 2. Get DOM Elements
            const itemsListContainer = document.getElementById('order-items-list');
            const paginationControlsContainer = document.getElementById('pagination-controls');

            // 3. Function to Display a Specific Page
            function displayPage(page) {
                if (!itemsListContainer) return; // Safety check
                itemsListContainer.innerHTML = ''; // Clear previous items
                page = parseInt(page);
                if (page < 1) page = 1;

                const totalPages = Math.ceil(allOrderItems.length / itemsPerPage);
                if (page > totalPages && totalPages > 0) page = totalPages; // Don't go beyond last page

                currentPage = page;
                const startIndex = (page - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;
                const paginatedItems = allOrderItems.slice(startIndex, endIndex);

                // Handle cases with no items
                if (paginatedItems.length === 0) {
                    itemsListContainer.innerHTML = `<tr><td colspan="4" class="text-center">${allOrderItems.length > 0 ? 'No items on this page.' : 'No items found in this order.'}</td></tr>`;
                } else {
                    // Loop through items for the current page and create table rows
                    paginatedItems.forEach(item => {
                        const row = document.createElement('tr');

                        // --- Extract data using CORRECT keys from PHP/DB ---
                        // IMPORTANT: Verify these keys match your $orderItems array structure!
                        const productId = item.InventoryItemID || null;
                        const productName = item.description || 'N/A';
                        // !!! Use the key with the typo if not fixed in DB/PHP query !!!
                        const quantity = parseInt(item.quwantitiyofitem || 0);
                        // Use the key holding the FINAL unit price paid (incl. promotion)
                        const price = parseFloat(item.item_price || 0);
                        const subtotal = price * quantity; // Calculate line total

                        // --- Create Table Cells ---
                        // Product Cell (with link)
                        const cellProduct = document.createElement('td');
                        if (productId) {
                            const link = document.createElement('a');
                            link.href = `product-detail.php?itemid=${productId}`;
                            link.textContent = productName;
                            cellProduct.appendChild(link);
                        } else {
                            cellProduct.textContent = productName;
                        }

                        // Quantity Cell
                        const cellQty = document.createElement('td');
                        cellQty.textContent = quantity;

                        // Price Cell (Unit Price)
                        const cellPrice = document.createElement('td');
                        cellPrice.innerHTML = `&#8358; ${price.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                        // Subtotal Cell (Line Total)
                        const cellSubtotal = document.createElement('td');
                        cellSubtotal.innerHTML = `&#8358; ${subtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

                        // --- Append Cells to Row ---
                        row.appendChild(cellProduct);
                        row.appendChild(cellQty);
                        row.appendChild(cellPrice);
                        row.appendChild(cellSubtotal);

                        // --- Append Row to Table Body ---
                        itemsListContainer.appendChild(row);
                    });
                }
                renderPaginationControls(); // Update pagination buttons
            }

            // 4. Function to Render Pagination Controls (Prev/Next buttons)
            function renderPaginationControls() {
                if (!paginationControlsContainer) return; // Safety check
                paginationControlsContainer.innerHTML = ''; // Clear previous controls

                const totalPages = Math.ceil(allOrderItems.length / itemsPerPage);
                if (totalPages <= 1) return; // No controls needed if 1 page or less

                // Previous Button
                const prevButton = document.createElement('button');
                prevButton.innerHTML = '&laquo; Prev';
                prevButton.disabled = (currentPage === 1);
                prevButton.classList.add('btn', 'btn-sm', 'btn-outline-secondary');
                prevButton.addEventListener('click', () => displayPage(currentPage - 1));
                paginationControlsContainer.appendChild(prevButton);

                // Page Info Text
                const pageInfo = document.createElement('span');
                pageInfo.textContent = ` Page ${currentPage} of ${totalPages} `;
                pageInfo.style.margin = '0 10px';
                paginationControlsContainer.appendChild(pageInfo);

                // Next Button
                const nextButton = document.createElement('button');
                nextButton.innerHTML = 'Next &raquo;';
                nextButton.disabled = (currentPage === totalPages);
                nextButton.classList.add('btn', 'btn-sm', 'btn-outline-secondary');
                nextButton.addEventListener('click', () => displayPage(currentPage + 1));
                paginationControlsContainer.appendChild(nextButton);
            }

            // 5. Initial Display Load
            displayPage(1); // Load the first page of items when the DOM is ready
        });
    </script>

</body>

</html>