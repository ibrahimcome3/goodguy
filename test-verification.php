<?php
// filepath: c:\wamp64\www\goodguy\test-verification.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes.php'; // Adjust as needed for your environment

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Simple test harness to run and log tests
 */
class TestRunner
{
    private $passCount = 0;
    private $failCount = 0;
    private $results = [];

    public function runTest($name, $function)
    {
        try {
            $result = $function();
            if ($result === true) {
                $this->passCount++;
                $this->results[$name] = ['status' => 'PASS', 'message' => ''];
                return true;
            } else {
                $this->failCount++;
                $this->results[$name] = ['status' => 'FAIL', 'message' => $result ?? 'Test returned false'];
                return false;
            }
        } catch (Exception $e) {
            $this->failCount++;
            $this->results[$name] = ['status' => 'ERROR', 'message' => $e->getMessage()];
            return false;
        }
    }

    public function displayResults()
    {
        echo "<h2>Test Results</h2>";
        echo "<p><strong>Passed:</strong> {$this->passCount}, <strong>Failed:</strong> {$this->failCount}</p>";

        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Test</th><th>Status</th><th>Message</th></tr>";

        foreach ($this->results as $name => $result) {
            $statusColor = $result['status'] === 'PASS' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$name}</td>";
            echo "<td style='color:{$statusColor};'>{$result['status']}</td>";
            echo "<td>{$result['message']}</td>";
            echo "</tr>";
        }

        echo "</table>";
    }
}

// Create test instance
$testRunner = new TestRunner();
$user = new User($pdo);

// Function to generate a random email for testing
function generateRandomEmail()
{
    return 'test_' . time() . '_' . rand(1000, 9999) . '@example.com';
}

// Function to generate random phone numbers
function generateRandomPhone($valid = true)
{
    if ($valid) {
        // Valid Nigerian phone number formats
        $formats = [
            '0' . rand(700000000, 899999999), // 11 digits starting with 0
            '234' . rand(700000000, 899999999), // 13 digits starting with 234
            rand(700000000, 899999999) // 9 digits (will be auto-fixed with leading 0)
        ];
        return $formats[array_rand($formats)];
    } else {
        // Invalid formats
        $formats = [
            '123456', // Too short
            '1' . rand(10000000000, 99999999999), // 12 digits not starting with 0 or 234
            'abcdefghijk', // Non-numeric
            '+123' . rand(1000000, 9999999) // Wrong country code
        ];
        return $formats[array_rand($formats)];
    }
}

// 1. Test Phone Number Validation
$testRunner->runTest('Phone Validation - Valid Nigerian format', function () use ($user) {
    $result = $user->validatePhoneNumber('08012345678');
    return $result === true ? true : "Expected true but got: " . $result;
});

$testRunner->runTest('Phone Validation - Valid International format', function () use ($user) {
    $result = $user->validatePhoneNumber('2348012345678');
    return $result === true ? true : "Expected true but got: " . $result;
});

$testRunner->runTest('Phone Validation - Auto-fix format', function () use ($user) {
    $result = $user->validatePhoneNumber('8012345678');
    return $result === '08012345678' ? true : "Expected 08012345678 but got: " . $result;
});

$testRunner->runTest('Phone Validation - Invalid format', function () use ($user) {
    $result = $user->validatePhoneNumber('123456');
    return is_string($result) && strpos($result, 'valid') !== false ? true : "Expected error message but got: " . $result;
});

$testRunner->runTest('Phone Validation - Non-numeric input', function () use ($user) {
    $result = $user->validatePhoneNumber('abcdefghijk');
    return is_string($result) && strpos($result, 'valid') !== false ? true : "Expected error message but got: " . $result;
});

// 2. Test Phone Number Formatting
$testRunner->runTest('Phone Formatting - Standard format', function () use ($user) {
    $result = $user->formatPhoneNumber('08012345678');
    return $result === '0801 234 5678' ? true : "Expected '0801 234 5678' but got: " . $result;
});

$testRunner->runTest('Phone Formatting - International format', function () use ($user) {
    $result = $user->formatPhoneNumber('2348012345678');
    return $result === '+234 801 234 5678' ? true : "Expected '+234 801 234 5678' but got: " . $result;
});

// 3. Test Email Verification Code Generation
$testEmail = "ibrahimcome3@gmail.com";//generateRandomEmail();

$testRunner->runTest('Email Verification Code - Generation', function () use ($user, $testEmail) {
    $code = $user->generateEmailVerificationCode($testEmail);
    return strlen($code) === 6 && is_numeric($code) ? true : "Expected 6-digit code but got: " . $code;
});

// 4. Test Email Verification
$testEmail = "ibrahimcome3@gmail.com";//generateRandomEmail();
$testCode = null;

$testRunner->runTest('Email Verification - Code Generation & Storage', function () use ($user, $testEmail, &$testCode) {
    $testCode = $user->generateEmailVerificationCode($testEmail);
    return $testCode !== false ? true : "Failed to generate verification code";
});

$testRunner->runTest('Email Verification - Invalid Code', function () use ($user, $testEmail) {
    $result = $user->verifyEmail($testEmail, '000000');
    return is_string($result) && strpos($result, 'Invalid') !== false ? true : "Expected error message but got: " . $result;
});

$testRunner->runTest('Email Verification - Valid Code', function () use ($user, $testEmail, $testCode) {
    $result = $user->verifyEmail($testEmail, $testCode);
    return $result === true ? true : "Expected true but got: " . $result;
});

// 5. Test Phone Number Management
// Create a test user account for phone testing if needed
$testUserId = null;
$testPhone = '0' . rand(700000000, 899999999);

// Use an existing user or create one for testing
if (isset($_SESSION['uid'])) {
    $testUserId = $_SESSION['uid'];
} else {
    // You might want to create a test user or use a known test account ID
    // $testUserId = $user->registerUser([...]);
    $testUserId = 1; // Use ID 1 or another known test account
}

if ($testUserId) {
    $testRunner->runTest('Phone Management - Add Phone', function () use ($user, $testUserId, $testPhone) {
        $result = $user->addPhoneNumber($testUserId, $testPhone);
        return $result === true ? true : "Failed to add phone: " . $result;
    });

    $testRunner->runTest('Phone Management - Get Phones', function () use ($user, $testUserId) {
        $phones = $user->getPhoneNumbersByUserId($testUserId);
        return is_array($phones) && count($phones) > 0 ? true : "No phones found";
    });

    // Find a non-default phone to set as default
    $phones = $user->getPhoneNumbersByUserId($testUserId);
    $nonDefaultPhone = null;
    foreach ($phones as $phone) {
        if ($phone['default_'] != 1) {
            $nonDefaultPhone = $phone;
            break;
        }
    }

    if ($nonDefaultPhone) {
        $testRunner->runTest('Phone Management - Set Default', function () use ($user, $testUserId, $nonDefaultPhone) {
            $result = $user->setDefaultUserPhoneNumber($nonDefaultPhone['phone_id'], $testUserId);
            return $result === true ? true : "Failed to set default phone";
        });
    }
}



// Add this after the Email Verification Code test section
// Test Email Sending Function
$testEmailAddress = "ibrahimcome3@gmail.com"; // Use your actual email for testing
$testName = "Ibrahim";
$testVerificationCode = "123456";

$testRunner->runTest('Email Sending - sendVerificationEmail', function () use ($user, $testEmailAddress, $testName, $testVerificationCode) {
    try {
        echo "<div class='alert alert-info'>Attempting to send verification email to {$testEmailAddress}...</div>";
        $result = $user->sendVerificationEmail($testEmailAddress, $testVerificationCode, $testName);
        var_dump($result);
        exit;
        if ($result === true) {
            return true;
        } else {
            return "Failed to send email. Check your SMTP settings and error logs.";
        }
    } catch (Exception $e) {
        return "Exception: " . $e->getMessage();
    }
});

// Add this after the Phone Number Formatting tests
// Test Phone Verification Email
// $testPhoneNumber = "08012345678";

// $testRunner->runTest('Email Sending - sendPhoneVerificationEmail', function () use ($user, $testEmailAddress, $testPhoneNumber, $testVerificationCode) {
//     try {
//         echo "<div class='alert alert-info'>Attempting to send phone verification email to {$testEmailAddress}...</div>";
//         $result = $user->sendPhoneVerificationEmail($testEmailAddress, $testPhoneNumber, $testVerificationCode);

//         if ($result === true) {
//             return true;
//         } else {
//             return "Failed to send phone verification email. Check your SMTP settings and error logs.";
//         }
//     } catch (Exception $e) {
//         return "Exception: " . $e->getMessage();
//     }
// });

// Display all test results
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Functions Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            color: #0088cc;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f2f2f2;
            text-align: left;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .pass {
            color: green;
        }

        .fail,
        .error {
            color: red;
        }

        .section {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <h1>Verification Functions Test</h1>

    <div class="section">
        <h2>Email Verification Test</h2>
        <p>This test script validates the email verification functionality including code generation and verification.
        </p>
    </div>

    <div class="section">
        <h2>Phone Number Validation Test</h2>
        <p>This test script validates the phone number validation, formatting and management functions.</p>
    </div>

    <?php $testRunner->displayResults(); ?>

    <div class="section">
        <h3>Additional Notes:</h3>
        <ul>
            <li>Email verification codes are 6 digits and expire after 1 hour</li>
            <li>Valid Nigerian phone numbers start with 0 (11 digits) or 234 (13 digits)</li>
            <li>The system can automatically fix 10-digit numbers by adding a leading 0</li>
        </ul>
    </div>
</body>

</html>