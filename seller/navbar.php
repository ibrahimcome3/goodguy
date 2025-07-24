<?php
//Make absolutely sure this is at the very top!
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "../conn.php";
require_once '../class/User.php'; // Make sure the User class is included
$u = new User($pdo);


//Check if the user is connected.
if (!isset($_SESSION['uid'])) {
    header("Location: ../login.php");
    exit;
}
$user = $u->getUserById($_SESSION['uid']);
//Check if the user is a seller
if ($user['user_role'] == "customer") {
    header("Location: ../index.php");
    exit;
}

?>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container">
        <a class="navbar-brand" href="seller-dashboard.php">

            <svg width="40px" viewBox="0 -1 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink">

                <title>emoji_happy_simple [#454]</title>
                <desc>Created with Sketch.</desc>
                <defs></defs>
                <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                    <g id="Dribbble-Light-Preview" transform="translate(-224.000000, -6165.000000)" fill="#000000">
                        <g id="icons" transform="translate(56.000000, 160.000000)">
                            <path
                                d="M176,6009.21053 L180,6009.21053 L180,6005 L176,6005 L176,6009.21053 Z M168,6008.15789 L172,6008.15789 L172,6006.05263 L168,6006.05263 L168,6008.15789 Z M177,6010.26316 L179,6010.26316 C179,6016.57895 169,6016.57895 169,6010.26316 L171,6010.26316 C171,6014.47368 177,6014.47368 177,6010.26316 L177,6010.26316 Z"
                                id="emoji_happy_simple-[#454]"></path>
                            <?php $storeName = "Goodguy"; ?>
                            <text x="2" y="10" font-family="sans-serif" font-size="4"
                                fill="black"><?php echo $storeName; ?></text>
                        </g>
                    </g>
                </g>
            </svg>


            Good Guy
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="seller-dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../seller/seller-dashboard.php">Manage Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../user/manage_orders.php">Manage Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="edit_seller_information.php">Edit Profile</a>
                </li>
                <?php if ($u->isSuperAdmin($mysqli, $_SESSION['uid'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/manage_admins.php">Manage Admins</a>
                    </li>
                <?php endif; ?>

            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Account
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item"
                                href="../admin/user_profile.php?user_id=<?= $user['customer_id'] ?>">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Settings</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>