<?php
session_start();
require_once "includes.php"; // Should provide $pdo and load classes

// Check if the user is logged in
if (!isset($_SESSION['uid'])) {
    $_SESSION['login_redirect'] = 'user_dashboard_overview.php'; // Redirect back here after login
    header("Location: login.php");
    exit();
}

$user_name_for_greeting = "User";
$total_orders_count = 0;

try {
    // Ensure User and Order objects are instantiated
    if (!isset($user) || !($user instanceof User)) {
        $user = new User($pdo);
    }
    if (!isset($orders) || !($orders instanceof Order)) {
        $orders = new Order($pdo);
    }

    // Fetch user's first name for greeting
    $userDetails = $user->getUserById($_SESSION['uid']); // Assuming getUserById fetches basic details
    if ($userDetails && !empty($userDetails['first_name'])) {
        $user_name_for_greeting = htmlspecialchars($userDetails['first_name']);
    } elseif ($userDetails && !empty($userDetails['customer_fname'])) { // Fallback if 'first_name' isn't the key
        $user_name_for_greeting = htmlspecialchars($userDetails['customer_fname']);
    }

    // Fetch total number of orders for the user
    $total_orders_count = $orders->count_number_of_orders(); // Assumes this method gets orders for the logged-in user

} catch (Exception $e) {
    error_log("Error in user_dashboard_overview.php setup: " . $e->getMessage());
    // Use default values if there's an error
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Dashboard - Goodguyng.com</title>
    <?php include "htlm-includes.php/metadata.php"; ?>
    <!-- Plugins CSS File -->

    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="assets/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="assets/css/demos/demo-13.css">
    <style>
        .dashboard-card {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
            text-align: center;
        }

        .dashboard-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #c96;
            /* Molla theme color */
        }

        .dashboard-card h5 {
            margin-bottom: 5px;
        }

        .dashboard-card p {
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <?php include "header_main.php"; ?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav mb-3">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">My Dashboard</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
                <div class="container">
                    <h2 class="text-center mb-3">Welcome back, <?= $user_name_for_greeting ?>!</h2>
                    <p class="text-center lead mb-5">From your dashboard, you can easily manage your orders, shipping
                        addresses, and account details.</p>

                    <div class="row">
                        <div class="col-md-4">
                            <a href="my_orders.php" class="text-decoration-none">
                                <div class="dashboard-card">
                                    <i class="icon-shopping-cart"></i>
                                    <h5>My Orders</h5>
                                    <p>View your order history and track current orders. You have
                                        <?= (int) $total_orders_count ?> order(s).
                                    </p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="manage_shipping_addresses.php" class="text-decoration-none">
                                <div class="dashboard-card">
                                    <i class="icon-map-marker"></i>
                                    <h5>My Addresses</h5>
                                    <p>Manage your shipping and billing addresses.</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="edit_profile.php" class="text-decoration-none">
                                <div class="dashboard-card">
                                    <i class="icon-user"></i>
                                    <h5>Account Details</h5>
                                    <p>Edit your personal information and password.</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="wishlist.php" class="text-decoration-none">
                                <div class="dashboard-card">
                                    <i class="icon-heart-o"></i>
                                    <h5>My Wishlist</h5>
                                    <p>View and manage your saved items.</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="logout.php" class="text-decoration-none">
                                <div class="dashboard-card">
                                    <i class="icon-log-out"></i>
                                    <h5>Logout</h5>
                                    <p>Sign out of your account.</p>
                                </div>
                            </a>
                        </div>
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

        <?php include "footer.php"; ?>
    </div><!-- End .page-wrapper -->

    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>
    <?php include "mobile-menue-index-page.php"; ?>
    <?php include "login-modal.php"; ?>
    <?php include "jsfile.php"; ?>
</body>

</html>