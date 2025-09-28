<?php
// filepath: c:\wamp64\www\goodguy\test-phone-verification.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
$user = new User($pdo);

// Simple function to output test results in a readable format
function printResult($test, $success, $message = '')
{
    echo "<div style='" . ($success ? "color:green;" : "color:red;") . "'>";
    echo "<strong>" . ($success ? "✓ PASS: " : "✗ FAIL: ") . "</strong>";
    echo htmlspecialchars($test);
    if ($message) {
        echo " - " . htmlspecialchars($message);
    }
    echo "</div>";
}

// Function to check if phone_verification table exists and has correct structure
function checkVerificationTable($pdo)
{
    try {
        $sql = "SHOW TABLES LIKE 'phone_verification'";
        $stmt = $pdo->query($sql);

        if ($stmt->rowCount() === 0) {
            return ['exists' => false, 'message' => 'Table phone_verification does not exist'];
        }

        // Check table structure
        $sql = "DESCRIBE phone_verification";
        $stmt = $pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $requiredColumns = ['phone_id', 'verification_code', 'expiry_time', 'attempts'];
        $missingColumns = array_diff($requiredColumns, $columns);

        if (!empty($missingColumns)) {
            return ['exists' => true, 'valid' => false, 'message' => 'Missing columns: ' . implode(', ', $missingColumns)];
        }

        return ['exists' => true, 'valid' => true];
    } catch (PDOException $e) {
        return ['exists' => false, 'message' => 'Error checking table: ' . $e->getMessage()];
    }
}

// Function to create the phone_verification table if it doesn't exist
function createVerificationTable($pdo)
{
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `phone_verification` (
            `phone_id` int(11) NOT NULL,
            `verification_code` varchar(6) NOT NULL,
            `expiry_time` datetime NOT NULL,
            `attempts` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`phone_id`),
            CONSTRAINT `fk_phone_verification` FOREIGN KEY (`phone_id`) REFERENCES `phonenumber` (`phone_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $pdo->exec($sql);
        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error creating table: ' . $e->getMessage()];
    }
}

// Function to get a valid phone ID for testing
function getTestPhoneId($pdo)
{
    try {
        $sql = "SELECT phone_id FROM phonenumber ORDER BY phone_id LIMIT 1";
        $stmt = $pdo->query($sql);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

// Function to clean up any test data
function cleanupTestData($pdo, $phoneId)
{
    if (!$phoneId)
        return;

    try {
        $sql = "DELETE FROM phone_verification WHERE phone_id = :phone_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':phone_id' => $phoneId]);
    } catch (PDOException $e) {
        echo "Error cleaning up: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Verification Code Generator Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .result {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 3px;
            border: 1px solid #ddd;
        }

        pre {
            background-color: #f5f5f5;
            padding: 10px;
            overflow: auto;
            border-radius: 3px;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <h1>Phone Verification Code Generator Test</h1>

    <div class="section">
        <h2>1. Checking Database Tables</h2>
        <?php
        $tableCheck = checkVerificationTable($pdo);
        if (!$tableCheck['exists']) {
            printResult("Phone verification table check", false, $tableCheck['message']);

            echo "<div>Attempting to create the table...</div>";
            $createResult = createVerificationTable($pdo);

            if ($createResult['success']) {
                printResult("Created phone_verification table", true);
                $tableCheck = checkVerificationTable($pdo);
            } else {
                printResult("Creating phone_verification table", false, $createResult['message']);
            }
        }

        if ($tableCheck['exists']) {
            if (isset($tableCheck['valid']) && $tableCheck['valid']) {
                printResult("Phone verification table structure", true, "Table exists with correct columns");
            } else {
                printResult("Phone verification table structure", false, $tableCheck['message'] ?? "Unknown issue with table structure");
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>2. Testing Code Generation</h2>
        <?php
        // Get a valid phone ID for testing
        $testPhoneId = getTestPhoneId($pdo);

        if (!$testPhoneId) {
            printResult("Getting test phone ID", false, "No phone numbers found in the database. Please add a phone number first.");
        } else {
            printResult("Getting test phone ID", true, "Using phone_id: " . $testPhoneId);

            // Clean up any existing test data
            cleanupTestData($pdo, $testPhoneId);

            // Test 1: Generate a verification code
            try {
                $code = $user->generatePhoneVerificationCode($testPhoneId);

                if ($code && strlen($code) === 6 && is_numeric($code)) {
                    printResult("Generate verification code", true, "Generated code: " . $code);

                    // Test 2: Check if code was stored in database
                    $sql = "SELECT verification_code, expiry_time FROM phone_verification WHERE phone_id = :phone_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':phone_id' => $testPhoneId]);
                    $verificationData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($verificationData && $verificationData['verification_code'] === $code) {
                        printResult("Code stored in database", true, "Code matches: " . $code);
                        printResult("Expiration time", true, "Set to: " . $verificationData['expiry_time']);

                        // Test 3: Generate a new code for the same phone ID (should replace the old one)
                        $newCode = $user->generatePhoneVerificationCode($testPhoneId);

                        if ($newCode && $newCode !== $code) {
                            printResult("Generate new code for same phone", true, "New code: " . $newCode);

                            // Check if new code replaced old code
                            $sql = "SELECT verification_code FROM phone_verification WHERE phone_id = :phone_id";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([':phone_id' => $testPhoneId]);
                            $newVerificationData = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($newVerificationData && $newVerificationData['verification_code'] === $newCode) {
                                printResult("New code replaced old code", true, "Database updated successfully");
                            } else {
                                printResult("New code replaced old code", false, "Database not updated correctly");
                            }
                        } else {
                            printResult("Generate new code for same phone", false, "Failed to generate new code or same code returned");
                        }
                    } else {
                        printResult("Code stored in database", false, "Code not found in database or doesn't match");
                    }
                } else {
                    printResult("Generate verification code", false, "Invalid code returned: " . ($code ? $code : "null"));
                }
            } catch (Exception $e) {
                printResult("Generate verification code", false, "Exception: " . $e->getMessage());
            }

            // Test 4: Try with invalid phone ID
            try {
                $invalidCode = $user->generatePhoneVerificationCode(999999); // Assuming this ID doesn't exist
        
                if ($invalidCode === false) {
                    printResult("Handle invalid phone ID", true, "Correctly returned false for non-existent phone ID");
                } else {
                    printResult("Handle invalid phone ID", false, "Should have returned false for non-existent phone ID");
                }
            } catch (Exception $e) {
                printResult("Handle invalid phone ID", false, "Exception: " . $e->getMessage());
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <p>The `generatePhoneVerificationCode()` method was tested with the following results:</p>
        <ol>
            <li>Database table verification:
                <?= $tableCheck['exists'] ? '<span class="success">PASS</span>' : '<span class="error">FAIL</span>' ?>
            </li>
            <li>Code generation:
                <?= isset($code) && $code ? '<span class="success">PASS</span>' : '<span class="error">FAIL</span>' ?>
            </li>
            <li>Database storage:
                <?= isset($verificationData) && $verificationData ? '<span class="success">PASS</span>' : '<span class="error">FAIL</span>' ?>
            </li>
            <li>Code regeneration:
                <?= isset($newCode) && $newCode ? '<span class="success">PASS</span>' : '<span class="error">FAIL</span>' ?>
            </li>
            <li>Invalid phone ID handling:
                <?= isset($invalidCode) && $invalidCode === false ? '<span class="success">PASS</span>' : '<span class="error">FAIL</span>' ?>
            </li>
        </ol>

        <p>Check the logs for more details:</p>
        <?php
        $logPath = __DIR__ . '/logs/';
        $phpErrors = error_get_last();
        if ($phpErrors) {
            echo "<pre>" . print_r($phpErrors, true) . "</pre>";
        }
        ?>

        <form action="test-phone-verification.php" method="post">
            <button type="submit">Run Tests Again</button>
        </form>
    </div>
</body>

</html>