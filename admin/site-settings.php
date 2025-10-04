<?php
session_start();
require_once "../includes.php";

// --- Authentication ---
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// --- Helper Functions for Settings ---

/**
 * Fetches all settings from the database and returns them as an associative array.
 *
 * @param PDO $pdo The database connection object.
 * @return array An associative array of setting_name => setting_value.
 */
function get_all_settings(PDO $pdo): array
{
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT setting_name, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // In a real app, you'd log this error.
        // For now, we return an empty array on failure.
        error_log("Error fetching settings: " . $e->getMessage());
    }
    return $settings;
}

/**
 * Updates a setting in the database. Uses INSERT ... ON DUPLICATE KEY UPDATE.
 *
 * @param PDO $pdo The database connection object.
 * @param string $name The name of the setting.
 * @param string $value The new value for the setting.
 * @return bool True on success, false on failure.
 */
function update_setting(PDO $pdo, string $name, string $value): bool
{
    try {
        $sql = "INSERT INTO settings (setting_name, setting_value) VALUES (:name, :value)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([':name' => $name, ':value' => $value]);
    } catch (PDOException $e) {
        error_log("Error updating setting '{$name}': " . $e->getMessage());
        return false;
    }
}

// --- Handle Form Submission ---
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // A simple CSRF token check could be added here for more security
    $allowed_settings = [
        'store_name',
        'store_address',
        'store_phone',
        'store_email',
        'shipping_cost_default',
        'free_shipping_threshold'
    ];

    $all_updates_succeeded = true;
    foreach ($allowed_settings as $setting_name) {
        if (isset($_POST[$setting_name])) {
            $setting_value = trim($_POST[$setting_name]);
            if (!update_setting($pdo, $setting_name, $setting_value)) {
                $all_updates_succeeded = false;
            }
        }
    }

    if ($all_updates_succeeded) {
        $success_message = "Settings updated successfully!";
    } else {
        $error_message = "An error occurred while updating some settings. Please check the logs.";
    }
}

// --- Data Fetching for Display ---
$settings = get_all_settings($pdo);

// Define default values for display if a setting is not in the DB yet
$defaults = [
    'store_name' => 'GoodGuy Shop',
    'store_address' => '123 GoodGuy Lane, Lagos, Nigeria',
    'store_phone' => '+234 800 000 0000',
    'store_email' => 'care@goodguyng.com',
    'shipping_cost_default' => '5000.00',
    'free_shipping_threshold' => '100000.00'
];

$settings = array_merge($defaults, $settings);

?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <title>Site Settings</title>
    <?php include 'admin-header.php'; ?>
    <?php include 'admin-include.php'; ?>
</head>

<body>
    <main class="main" id="top">
        <?php include "includes/admin_navbar.php"; ?>
        <div class="content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-3">
                    <li class="breadcrumb-item"><a href="index.php"><span class="fas fa-home me-1"></span>Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Site Settings</li>
                </ol>
            </nav>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">General Site Settings</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="site-settings.php">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="store_name">Store Name</label>
                                <input class="form-control" id="store_name" name="store_name" type="text"
                                    value="<?= htmlspecialchars($settings['store_name']) ?>" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="store_email">Store Email</label>
                                <input class="form-control" id="store_email" name="store_email" type="email"
                                    value="<?= htmlspecialchars($settings['store_email']) ?>" />
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="store_address">Store Address</label>
                                <textarea class="form-control" id="store_address" name="store_address"
                                    rows="3"><?= htmlspecialchars($settings['store_address']) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="store_phone">Store Phone</label>
                                <input class="form-control" id="store_phone" name="store_phone" type="text"
                                    value="<?= htmlspecialchars($settings['store_phone']) ?>" />
                            </div>
                        </div>

                        <hr class="my-4" />

                        <h5 class="mb-3">E-commerce Settings</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_cost_default">Default Shipping Cost (₦)</label>
                                <input class="form-control" id="shipping_cost_default" name="shipping_cost_default"
                                    type="number" step="0.01"
                                    value="<?= htmlspecialchars($settings['shipping_cost_default']) ?>" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="free_shipping_threshold">Free Shipping Threshold
                                    (₦)</label>
                                <input class="form-control" id="free_shipping_threshold" name="free_shipping_threshold"
                                    type="number" step="0.01"
                                    value="<?= htmlspecialchars($settings['free_shipping_threshold']) ?>" />
                                <div class="form-text">Set to 0 to disable free shipping.</div>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button class="btn btn-primary" type="submit">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include 'includes/admin_footer.php'; ?>
        </div>
    </main>
    <?php include 'admin-phoenix-scripts.php'; // Assuming you have a common script include file ?>
</body>

</html>