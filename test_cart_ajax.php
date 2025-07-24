<?php
// test_cart_ajax.php

// Setup environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define $_SERVER['DOCUMENT_ROOT'] if running from CLI or if not set
// Adjust this path to your actual web root.
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = __DIR__; // Assumes script is in the web root
}

// Include the same setup as your application
// This should provide $pdo, and instantiate $cart via new Cart($pdo, $promotion)
require_once __DIR__ . '/includes.php';

/**
 * Simulates an AJAX call to cart_ajax.php
 * @param array $postData Data to send via POST.
 * @return array Decoded JSON response.
 * @throws Exception If JSON response is invalid.
 */
function call_cart_ajax(array $postData): array
{
    // Store original POST and SERVER superglobals
    $originalPost = $_POST;
    $originalServer = $_SERVER;

    $_POST = $postData;
    $_SERVER['REQUEST_METHOD'] = 'POST'; // Simulate POST request

    ob_start();
    include __DIR__ . '/cart_ajax.php'; // Execute the target script
    $responseJson = ob_get_clean();

    // Restore original superglobals
    $_POST = $originalPost;
    $_SERVER = $originalServer;

    $decoded = json_decode($responseJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Raw Response: $responseJson\n";
        throw new Exception("Invalid JSON response from cart_ajax.php: " . json_last_error_msg());
    }
    return $decoded;
}

/**
 * Resets the cart session and re-initializes the global $cart object.
 */
function reset_cart_state()
{
    $_SESSION['cart'] = [];
    // Re-initialize $cart if it's a global used by cart_ajax.php via includes.php
    // This ensures the $cart object reflects the cleared session.
    global $pdo, $promotion, $cart;
    if (isset($pdo) && isset($promotion)) {
        $cart = new Cart($pdo, $promotion);
    } else {
        echo "Warning: \$pdo or \$promotion not available globally to re-initialize \$cart.\n";
    }
}

echo "Starting Cart AJAX Tests...\n\n";

// Mock product prices for verifying totals.
// Ensure these products exist in your database with these costs.
$mockProductPrices = [
    101 => 10.00,
    102 => 20.00,
    103 => 5.50,
];
$testCounter = 0;
$testsPassed = 0;
$testsFailed = 0;

function run_test($testName, $postData, callable $assertions)
{
    global $testCounter, $testsPassed, $testsFailed;
    $testCounter++;
    echo "Test $testCounter: $testName\n";
    try {
        $response = call_cart_ajax($postData);
        if ($assertions($response)) {
            echo "PASS\n";
            $testsPassed++;
        } else {
            echo "FAIL\n";
            $testsFailed++;
        }
        // print_r($response); // Uncomment for detailed response
    } catch (Exception $e) {
        echo "FAIL (Exception): " . $e->getMessage() . "\n";
        $testsFailed++;
    }
    echo "-------------------------\n";
}

// --- Test Cases ---

// Test 1: Add a new item successfully to an empty cart
reset_cart_state();
run_test(
    "Add new item (ID 101, Qty 1)",
    ['inventory_product_id' => 101, 'qty' => 1],
    function ($response) use ($mockProductPrices) {
        $expectedTotal = $mockProductPrices[101] * 1;
        $pass = $response['success'] === true &&
            $response['cartCount'] === 1 &&
            strpos(strtolower($response['message']), 'success') !== false &&
            strpos($response['cartTotalFormatted'], number_format($expectedTotal, 2)) !== false &&
            strpos($response['cartItemsHtml'], 'itemid=101') !== false &&
            strpos($response['cartItemsHtml'], '<span class="cart-product-qty">1</span>') !== false;
        if (!$pass) {
            echo "Expected Total: " . number_format($expectedTotal, 2) . "\n";
            print_r($response);
        }
        return $pass;
    }
);

// Test 2: Add more quantity to the existing item
// Cart state persists from Test 1
run_test(
    "Add more quantity to existing item (ID 101, Qty 2 more)",
    ['inventory_product_id' => 101, 'qty' => 2],
    function ($response) use ($mockProductPrices) {
        $expectedTotal = $mockProductPrices[101] * 3; // 1 (from prev) + 2 (current)
        $pass = $response['success'] === true &&
            $response['cartCount'] === 1 && // Still 1 distinct item type
            strpos($response['cartTotalFormatted'], number_format($expectedTotal, 2)) !== false &&
            strpos($response['cartItemsHtml'], '<span class="cart-product-qty">3</span>') !== false; // Check for quantity 3
        if (!$pass) {
            echo "Expected Total: " . number_format($expectedTotal, 2) . "\n";
            print_r($response);
        }
        return $pass;
    }
);

// Test 3: Add a different item
run_test(
    "Add a different item (ID 102, Qty 1)",
    ['inventory_product_id' => 102, 'qty' => 1],
    function ($response) use ($mockProductPrices) {
        $expectedTotal = ($mockProductPrices[101] * 3) + ($mockProductPrices[102] * 1);
        $pass = $response['success'] === true &&
            $response['cartCount'] === 2 && // Now 2 distinct item types
            strpos($response['cartTotalFormatted'], number_format($expectedTotal, 2)) !== false &&
            strpos($response['cartItemsHtml'], 'itemid=101') !== false &&
            strpos($response['cartItemsHtml'], 'itemid=102') !== false;
        if (!$pass) {
            echo "Expected Total: " . number_format($expectedTotal, 2) . "\n";
            print_r($response);
        }
        return $pass;
    }
);

// Test 4: Add item with size and color
reset_cart_state();
run_test(
    "Add item with size and color (ID 103, Qty 1, L, Blue)",
    ['inventory_product_id' => 103, 'qty' => 1, 'size' => 'L', 'color' => 'Blue'],
    function ($response) use ($mockProductPrices) {
        $expectedTotal = $mockProductPrices[103] * 1;
        // Note: cart_ajax.php's HTML generation doesn't currently show size/color in the dropdown.
        // We check if the item was added and count/total are correct.
        $pass = $response['success'] === true &&
            $response['cartCount'] === 1 &&
            strpos($response['cartTotalFormatted'], number_format($expectedTotal, 2)) !== false &&
            strpos($response['cartItemsHtml'], 'itemid=103') !== false;
        if (!$pass) {
            echo "Expected Total: " . number_format($expectedTotal, 2) . "\n";
            print_r($response);
        }
        return $pass;
    }
);

// Test 5: Add same item ID but different size/color
// This should still result in cartCount being 1 because Cart::getCartItemCount groups by product ID.
// The quantity for item 103 in the dropdown HTML should be the sum.
run_test(
    "Add same item ID, different variation (ID 103, Qty 2, M, Red)",
    ['inventory_product_id' => 103, 'qty' => 2, 'size' => 'M', 'color' => 'Red'],
    function ($response) use ($mockProductPrices) {
        $expectedTotal = $mockProductPrices[103] * (1 + 2); // 1 (L/Blue) + 2 (M/Red)
        $pass = $response['success'] === true &&
            $response['cartCount'] === 1 && // Still 1 distinct product ID (103)
            strpos($response['cartTotalFormatted'], number_format($expectedTotal, 2)) !== false &&
            strpos($response['cartItemsHtml'], '<span class="cart-product-qty">3</span>') !== false; // Summed quantity
        if (!$pass) {
            echo "Expected Total: " . number_format($expectedTotal, 2) . "\n";
            print_r($response);
        }
        return $pass;
    }
);

// Test 6: Add item with invalid quantity (0)
reset_cart_state();
run_test(
    "Add item with quantity 0",
    ['inventory_product_id' => 101, 'qty' => 0],
    function ($response) {
        $pass = $response['success'] === false &&
            $response['cartCount'] === 0 && // Cart should be empty
            strpos(strtolower($response['message']), 'quantity must be greater than zero') !== false;
        if (!$pass)
            print_r($response);
        return $pass;
    }
);

// Test 7: Add item with negative quantity
run_test(
    "Add item with negative quantity",
    ['inventory_product_id' => 101, 'qty' => -1],
    function ($response) {
        $pass = $response['success'] === false &&
            $response['cartCount'] === 0 && // Cart should still be empty
            strpos(strtolower($response['message']), 'quantity must be greater than zero') !== false;
        if (!$pass)
            print_r($response);
        return $pass;
    }
);

// Test 8: Missing inventory_product_id
run_test(
    "Missing product ID",
    ['qty' => 1],
    function ($response) {
        $pass = $response['success'] === false &&
            strpos(strtolower($response['message']), 'invalid product id or quantity') !== false;
        if (!$pass)
            print_r($response);
        return $pass;
    }
);

// Test 9: Missing qty
run_test(
    "Missing quantity",
    ['inventory_product_id' => 101],
    function ($response) {
        $pass = $response['success'] === false &&
            strpos(strtolower($response['message']), 'invalid product id or quantity') !== false;
        if (!$pass)
            print_r($response);
        return $pass;
    }
);

// Test 10: Non-numeric product ID
run_test(
    "Non-numeric product ID",
    ['inventory_product_id' => 'abc', 'qty' => 1],
    function ($response) {
        $pass = $response['success'] === false &&
            strpos(strtolower($response['message']), 'invalid product id or quantity') !== false;
        if (!$pass)
            print_r($response);
        return $pass;
    }
);

// Test 11: Non-numeric quantity
run_test(
    "Non-numeric quantity",
    ['inventory_product_id' => 101, 'qty' => 'xyz'],
    function ($response) {
        $pass = $response['success'] === false &&
            strpos(strtolower($response['message']), 'invalid product id or quantity') !== false;
        if (!$pass)
            print_r($response);
        return $pass;
    }
);

// Test 12: Empty size and color (should be treated as null)
reset_cart_state();
run_test(
    "Add item with empty size and color strings (ID 101, Qty 1)",
    ['inventory_product_id' => 101, 'qty' => 1, 'size' => '', 'color' => ''],
    function ($response) use ($mockProductPrices) {
        $expectedTotal = $mockProductPrices[101] * 1;
        $pass = $response['success'] === true &&
            $response['cartCount'] === 1 &&
            strpos($response['cartTotalFormatted'], number_format($expectedTotal, 2)) !== false;
        if (!$pass) {
            echo "Expected Total: " . number_format($expectedTotal, 2) . "\n";
            print_r($response);
        }
        return $pass;
    }
);


echo "\n--- Test Summary ---\n";
echo "Total Tests: $testCounter\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";

// Optional: Clean up session after all tests
// session_destroy();
?>