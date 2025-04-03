<?php
include "../conn.php";
require_once '../class/User.php';

$u = new User();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'];
    $customerId = $_POST['customer_id']; // Use customer_id instead of user_id

    if ($action === "remove_admin") {
        // Check if the user is a super admin and if removing would leave fewer than 2 super admins.
        $customer = $u->getCustomerById($mysqli, $customerId);
        if ($customer['super_admin']) {
            $superAdminsCount = $u->getSuperAdminsCount($mysqli);
            if ($superAdminsCount <= 2) {
                echo "<p style='color:red;'>Cannot remove this super admin. At least two must exist.</p>";
                exit;
            }
        }

        // Only super admins can remove super admins
        if ($customer['super_admin'] && !$u->isSuperAdmin($mysqli, $_SESSION['uid'])) {
            echo "<p style='color:red;'>Only a super admin can remove a super admin.</p>";
            exit;
        }

        //Start Transaction
        $mysqli->begin_transaction();
        try {
            // Remove admin status in both tables
            if ($u->removeAdmin($mysqli, $customerId)) {
                $mysqli->commit();
                header("Location: manage_admins.php");
                exit;
            } else {
                $mysqli->rollback(); //Rollback in case of error
                echo "Error removing admin.";
                exit;
            }
        } catch (Exception $e) {
            $mysqli->rollback(); //Rollback in case of error
            echo "Error removing admin: " . $e->getMessage();
            exit;
        }
    }
}
?>