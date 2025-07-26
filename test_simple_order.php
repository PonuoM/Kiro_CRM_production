<?php
/**
 * Simple Order System Test
 */

session_start();
require_once 'includes/functions.php';

echo "<h2>Simple Order System Test</h2><br>";

// Check if logged in
if (!isLoggedIn()) {
    echo "❌ Not logged in. Please login first.<br>";
    echo '<a href="pages/login.php">Go to Login</a>';
    exit;
}

echo "✅ User logged in: " . $_SESSION['username'] . " (" . $_SESSION['user_role'] . ")<br><br>";

// Test 1: Load products data
echo "<h3>Test 1: Load Products Data</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Try to get products from database
    $sql = "SELECT product_code, product_name, category, unit, standard_price, is_active FROM products WHERE is_active = 1 LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        echo "✅ Found " . count($products) . " products in database:<br>";
        foreach ($products as $product) {
            echo "- {$product['product_code']}: {$product['product_name']} ({$product['standard_price']} บาท)<br>";
        }
    } else {
        echo "⚠️ No products found in database<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "Using mock products data instead:<br>";
    
    $mockProducts = [
        ['product_code' => 'F001', 'product_name' => 'ปุ๋ยเคมี 16-16-16', 'standard_price' => '18.50'],
        ['product_code' => 'F002', 'product_name' => 'ปุ๋ยเคมี 15-15-15', 'standard_price' => '17.50'],
        ['product_code' => 'O001', 'product_name' => 'ปุ๋ยหมักมีกากมด', 'standard_price' => '45.00']
    ];
    
    foreach ($mockProducts as $product) {
        echo "- {$product['product_code']}: {$product['product_name']} ({$product['standard_price']} บาท)<br>";
    }
}

echo "<br><hr><br>";

// Test 2: Check Order Model
echo "<h3>Test 2: Check Order Model</h3>";
try {
    require_once 'includes/Order.php';
    $orderModel = new Order();
    echo "✅ Order model loaded successfully<br>";
    
    // Test validation
    $testData = [
        'CustomerCode' => 'TEST011',
        'DocumentDate' => date('Y-m-d'),
        'PaymentMethod' => 'เงินสด',
        'products' => [
            [
                'name' => 'ปุ๋ยเคมี 16-16-16',
                'quantity' => 2,
                'price' => 18.50
            ]
        ]
    ];
    
    $errors = $orderModel->validateOrderData($testData);
    if (empty($errors)) {
        echo "✅ Order validation passed<br>";
    } else {
        echo "❌ Order validation failed:<br>";
        foreach ($errors as $error) {
            echo "- $error<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Order model error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";

// Test 3: Test JavaScript compatibility
echo "<h3>Test 3: JavaScript Response Format</h3>";

// Simulate products API response
$productsResponse = [
    'success' => true,
    'data' => [
        ['product_code' => 'F001', 'product_name' => 'ปุ๋ยเคมี 16-16-16', 'category' => 'ปุ๋ยเคมี', 'standard_price' => '18.50'],
        ['product_code' => 'F002', 'product_name' => 'ปุ๋ยเคมี 15-15-15', 'category' => 'ปุ๋ยเคมี', 'standard_price' => '17.50'],
    ],
    'categories' => ['ปุ๋ยเคมี', 'ปุ๋ยอินทรีย์'],
    'total_count' => 2
];

echo "Products API response format:<br>";
echo "<pre>" . htmlspecialchars(json_encode($productsResponse, JSON_PRETTY_PRINT)) . "</pre>";

// Simulate order API response
$orderResponse = [
    'success' => true,
    'message' => 'สร้างคำสั่งซื้อสำเร็จ',
    'data' => [
        'DocumentNo' => 'ORD-' . date('Ymd') . '-001'
    ]
];

echo "Order API response format:<br>";
echo "<pre>" . htmlspecialchars(json_encode($orderResponse, JSON_PRETTY_PRINT)) . "</pre>";

echo "<br><hr><br>";

// Test urls
echo "<h3>Test 4: Check URLs</h3>";
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
echo "Base URL: $baseUrl<br>";
echo "Products API: $baseUrl/api/products/list.php<br>";
echo "Orders API: $baseUrl/api/orders/create.php<br>";
echo "Customer Detail: $baseUrl/pages/customer_detail.php?code=TEST011<br>";

echo "<br><p><strong>Test completed. If you see ✅ for most tests, the system should work properly.</strong></p>";
?>