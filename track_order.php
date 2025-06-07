<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "includes.php"; // Main include file

// Validate Order ID from GET parameter
$order_id_from_get = isset($_GET['order_id']) ? filter_var($_GET['order_id'], FILTER_VALIDATE_INT) : null;

if (!$order_id_from_get || $order_id_from_get <= 0) {
    $_SESSION['error_message'] = "Invalid Order ID provided for tracking.";
    header("Location: my_orders.php"); // Redirect to a general orders page or dashboard
    exit();
}

$order_details = null;
$tracking_events = []; // Placeholder for actual tracking events

try {
    if (!isset($pdo)) {
        throw new Exception("PDO connection not available.");
    }
    if (!isset($orders) || !($orders instanceof Order)) {
        $orders = new Order($pdo); // Instantiate Order class if not already available
    }

    // Fetch order details
    $order_details = $orders->get_order_by_id($order_id_from_get);

    if (!$order_details) {
        $_SESSION['error_message'] = "Order #{$order_id_from_get} not found.";
        // Consider redirecting or just showing the message on this page
    } else {
        // Fetch actual tracking events from the database
        $tracking_events = $orders->getTrackingEvents($order_id_from_get);
        // The sorting is now handled by the SQL query in getTrackingEvents method
    }

} catch (Exception $e) {
    error_log("Error on track_order.php for order ID {$order_id_from_get}: " . $e->getMessage());
    $_SESSION['error_message'] = "A system error occurred while trying to track your order.";
    // $order_details will remain null, and the page will show an error
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Track Order #<?= htmlspecialchars($order_id_from_get) ?> - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <style>
        .tracking-timeline {
            list-style-type: none;
            padding-left: 0;
        }

        .tracking-timeline li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .tracking-timeline li:last-child {
            border-bottom: none;
        }

        .tracking-status {
            font-weight: bold;
        }

        .tracking-location {
            color: #555;
            font-size: 0.9em;
        }

        .tracking-timestamp {
            color: #777;
            font-size: 0.85em;
            display: block;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <?php echo breadcrumbs(); // Ensure breadcrumbs() handles this new page ?>
                        <li class="breadcrumb-item"><a href="my_orders.php">My Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Track Order</li>
                    </ol>
                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <h2 class="text-center mb-4">Track Your Order</h2>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger text-center"><?= htmlspecialchars($_SESSION['error_message']); ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <?php if ($order_details): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                Order #<?= htmlspecialchars($order_details['order_id']) ?> - Status: <span
                                    class="badge badge-info"><?= htmlspecialchars(ucfirst($order_details['order_status'])) ?></span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">Tracking History</h5>
                                <?php if (!empty($tracking_events)): ?>
                                    <ul class="tracking-timeline">
                                        <?php foreach ($tracking_events as $event): ?>
                                            <li>
                                                <span class="tracking-status"><?= htmlspecialchars($event['status']) ?></span>
                                                <span class="tracking-location">at
                                                    <?= htmlspecialchars($event['location']) ?></span>
                                                <span
                                                    class="tracking-timestamp"><?= htmlspecialchars(date("M d, Y - h:i A", strtotime($event['timestamp']))) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No detailed tracking information available yet. Please check back later.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="order_detail.php?order_id=<?= htmlspecialchars($order_id_from_get) ?>"
                                class="btn btn-outline-primary-2">View Full Order Details</a>
                        </div>
                    <?php elseif (!isset($_SESSION['error_message'])): // Show only if no other error was set ?>
                        <p class="text-center">Please enter a valid Order ID to track.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php include "footer.php"; ?>
    </div>

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <?php include "mobile-menue-index-page.php"; // Or your standard mobile menu ?>
    <?php include "login-modal.php"; // If needed on this page ?>
    <?php include "jsfile.php"; ?>
</body>

</html>