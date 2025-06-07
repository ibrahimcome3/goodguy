<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once "includes.php"; // Main include file

// Ensure user is logged in
if (!isset($_SESSION['uid'])) { // Assuming 'uid' is your session variable for user ID
    $_SESSION['error_message'] = "You must be logged in to manage your shipping addresses.";
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['uid'];
$shipping_addresses = [];
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']); // Clear messages after retrieving

// Ensure User class is available and instantiated
if (!isset($user) || !($user instanceof User)) {
    if (class_exists('User') && isset($pdo)) {
        $user = new User($pdo); // Assumes User class is in User.php and included via includes.php
    } else {
        error_log("User class or PDO not available in manage_shipping_addresses.php");
        // Display a user-friendly error or redirect
        die("A critical error occurred. Please try again later or contact support.");
    }
}

// Generate CSRF token if not already set for actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle delete action (should be POST for security)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_address') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid request. Please try again.";
    } else {
        $address_to_delete = isset($_POST['shipping_address_no']) ? filter_var($_POST['shipping_address_no'], FILTER_VALIDATE_INT) : null;
        if ($address_to_delete) {
            // Assuming $user object is available and has deleteShippingAddress method
            if ($user->deleteShippingAddress($address_to_delete, $customer_id)) {
                $_SESSION['success_message'] = "Shipping address deleted successfully.";
            } else {
                $_SESSION['error_message'] = "Failed to delete shipping address or address not found.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid address ID for deletion.";
        }
    }
    // Regenerate CSRF token after processing
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: manage_shipping_addresses.php"); // Redirect to clear POST data and show messages
    exit();
}

// Fetch user's shipping addresses
try {
    // Assuming $user object is available and has getShippingAddressesByCustomerId method
    $shipping_addresses = $user->getShippingAddressesByCustomerId($customer_id);
} catch (Exception $e) {
    error_log("Error fetching shipping addresses for customer ID {$customer_id}: " . $e->getMessage());
    $error_message = "Could not retrieve your shipping addresses at this time.";
    // $shipping_addresses will remain empty, and the page will show the error or "no addresses" message.
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Shipping Addresses - Goodguy</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <style>
        .address-card {
            margin-bottom: 20px;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .address-card h5 {
            margin-top: 0;
        }

        .address-actions form {
            display: inline-block;
            margin-left: 5px;
        }

        .address-details p {
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>
        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">

                </div>
            </nav>

            <div class="page-content">
                <div class="container">
                    <h2 class="text-center mb-4">Manage Your Shipping Addresses</h2>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success text-center mb-5"><?= htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger text-center mb-5"><?= htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>

                    <div class="text-right mb-3">
                        <a href="shipping_address_form.php" class="btn btn-primary">Add New Shipping Address</a>
                    </div>

                    <?php if (!empty($shipping_addresses)): ?>
                        <div class="row">
                            <?php foreach ($shipping_addresses as $address): ?>
                                <div class="col-md-6">
                                    <div class="address-card">
                                        <div class="address-details">
                                            <p><strong>Address:</strong> <?= htmlspecialchars($address['address1']); ?></p>
                                            <?php if (!empty($address['address2'])): ?>
                                                <p><?= htmlspecialchars($address['address2']); ?></p>
                                            <?php endif; ?>
                                            <p><?= htmlspecialchars($address['city']); ?>,
                                                <?= htmlspecialchars($address['state'] ?? 'N/A'); ?>
                                                <?= htmlspecialchars($address['zip']); ?>
                                            </p>
                                            <p><?= htmlspecialchars($address['country']); ?></p>
                                            <?php if (!empty($address['shipping_area_id'])): ?>
                                                <p><em>Shipping Area ID: <?= htmlspecialchars($address['shipping_area_id']); ?></em>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <hr>
                                        <div class="address-actions">
                                            <a href="shipping_address_form.php?shipping_address_no=<?= $address['shipping_address_no']; ?>"
                                                class="btn btn-sm btn-outline-primary">Edit</a>
                                            <form action="manage_shipping_addresses.php" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this address?');">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="shipping_address_no"
                                                    value="<?= $address['shipping_address_no']; ?>">
                                                <input type="hidden" name="action" value="delete_address">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">You have no saved shipping addresses. <a href="shipping_address_form.php">Add
                                one now!</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <?php include "footer.php"; ?>
    </div>
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-modal.php"; ?>
    <?php include "jsfile.php"; ?>
</body>

</html>