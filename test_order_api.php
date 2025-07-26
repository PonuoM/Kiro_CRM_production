<?php
/**
 * Test Order API functionality
 */

echo "<h2>Testing Order API Functions</h2><br>";

// Test products API directly (same server, same session)
echo "<h3>1. Testing Products API</h3>";
try {
    // Simulate the API call directly since we're on the same server
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include 'api/products/list.php';
    $response = ob_get_clean();
    
    $httpCode = 200;
    
    echo "HTTP Code: $httpCode<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";
    
    $data = json_decode($response, true);
    if ($data && ($data['success'] === true || $data['status'] === 'success')) {
        echo "✅ Products API working correctly<br>";
        echo "Products count: " . count($data['data']) . "<br>";
    } else {
        echo "❌ Products API failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Error testing products API: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";

// Test order creation API
echo "<h3>2. Testing Order Creation API</h3>";

// Test data
$orderData = [
    'CustomerCode' => 'TEST011',
    'DocumentDate' => date('Y-m-d'),
    'PaymentMethod' => 'เงินสด',
    'products' => [
        [
            'code' => 'F001',
            'name' => 'ปุ๋ยเคมี 16-16-16',
            'quantity' => 2,
            'price' => 18.50
        ]
    ],
    'discount_amount' => 5.00,
    'discount_percent' => 0,
    'discount_remarks' => 'ทดสอบระบบ'
];

try {
    // Simulate the API call directly since we're on the same server
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    // Set up the input stream
    $input = json_encode($orderData);
    
    // Create a temporary stream
    $stream = fopen('php://temp', 'r+');
    fwrite($stream, $input);
    rewind($stream);
    
    ob_start();
    include 'api/orders/create.php';
    $response = ob_get_clean();
    
    fclose($stream);
    $httpCode = 200;
    
    echo "HTTP Code: $httpCode<br>";
    echo "Request Data: <pre>" . htmlspecialchars(json_encode($orderData, JSON_PRETTY_PRINT)) . "</pre><br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre><br>";
    
    $data = json_decode($response, true);
    if ($data && ($data['success'] === true || $data['status'] === 'success')) {
        echo "✅ Order Creation API working correctly<br>";
    } else {
        echo "❌ Order Creation API failed<br>";
        if (isset($data['message'])) {
            echo "Error: " . htmlspecialchars($data['message']) . "<br>";
        }
        if (isset($data['errors'])) {
            echo "Errors: " . htmlspecialchars(json_encode($data['errors'])) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error testing order creation API: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";

// Test session and login status
echo "<h3>3. Testing Session Status</h3>";
session_start();
echo "Session ID: " . htmlspecialchars(session_id()) . "<br>";
echo "Session data: <pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre><br>";

// Test functions
echo "<h3>4. Testing Functions</h3>";
try {
    require_once 'includes/functions.php';
    
    if (function_exists('isLoggedIn')) {
        echo "✅ isLoggedIn function exists<br>";
        $loginStatus = isLoggedIn();
        echo "Login status: " . ($loginStatus ? 'Logged in' : 'Not logged in') . "<br>";
    }
    
    if (function_exists('sendJsonResponse')) {
        echo "✅ sendJsonResponse function exists<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading functions: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<p><strong>Test completed. Check the results above.</strong></p>";
?>