<?php
/**
 * Debug Order Insert Issue
 * This file will help us debug the order creation problem
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start session
session_start();

// Simulate logged in user for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['user_role'] = 'Admin';

require_once 'includes/functions.php';
require_once 'includes/Order.php';

echo "<h2>Order Insert Debug Test</h2>\n";

try {
    // Test data similar to what frontend sends
    $testOrderData = [
        'CustomerCode' => 'TEST011',
        'DocumentDate' => '2025-07-29',
        'PaymentMethod' => 'เงินสด',
        'DiscountAmount' => 5,
        'DiscountPercent' => 5.88,
        'DiscountRemarks' => '',
        'products' => [
            [
                'code' => 'FER-W01',
                'name' => 'ปุ๋ยน้ำ สูตร 4-24-24',
                'quantity' => 1,
                'price' => 85
            ]
        ],
        'SubtotalAmount' => 85,
        'Products' => 'ปุ๋ยน้ำ สูตร 4-24-24',
        'Quantity' => 1,
        'Price' => 80
    ];
    
    echo "<h3>1. Testing Database Connection</h3>\n";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected successfully<br>\n";
    
    echo "<h3>2. Testing Customer Exists</h3>\n";
    $stmt = $pdo->prepare("SELECT CustomerCode FROM customers WHERE CustomerCode = ?");
    $stmt->execute(['TEST011']);
    $customer = $stmt->fetch();
    
    if ($customer) {
        echo "✅ Customer TEST011 exists in database<br>\n";
    } else {
        echo "❌ Customer TEST011 NOT found in database<br>\n";
        
        // Let's see what customers exist
        $stmt = $pdo->query("SELECT CustomerCode FROM customers LIMIT 5");
        $customers = $stmt->fetchAll();
        echo "Available customers: ";
        foreach ($customers as $c) {
            echo $c['CustomerCode'] . ", ";
        }
        echo "<br>\n";
    }
    
    echo "<h3>3. Testing Order Model</h3>\n";
    $orderModel = new Order();
    
    echo "<h4>3.1 Check Table Structure</h4>\n";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll();
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>\n";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>\n";
    }
    echo "</table><br>\n";
    
    echo "<h4>3.2 Validate Order Data</h4>\n";
    $validationErrors = $orderModel->validateOrderData($testOrderData);
    if (empty($validationErrors)) {
        echo "✅ Order data validation passed<br>\n";
    } else {
        echo "❌ Validation errors:<ul>\n";
        foreach ($validationErrors as $error) {
            echo "<li>$error</li>\n";
        }
        echo "</ul>\n";
    }
    
    echo "<h4>3.3 Test Direct Insert</h4>\n";
    
    // Prepare data like Order.php does
    $insertData = $testOrderData;
    
    // Generate DocumentNo
    $insertData['DocumentNo'] = generateDocumentNo();
    $insertData['CreatedDate'] = date('Y-m-d H:i:s');
    $insertData['CreatedBy'] = 'debug_test';
    $insertData['OrderBy'] = 'debug_test';
    
    // Remove products array
    unset($insertData['products']);
    
    // Check discount columns exist
    $discountExists = $orderModel->columnExists('DiscountAmount');
    echo "DiscountAmount column exists: " . ($discountExists ? 'YES' : 'NO') . "<br>\n";
    
    if (!$discountExists) {
        unset($insertData['DiscountAmount']);
        unset($insertData['DiscountPercent']);
        unset($insertData['DiscountRemarks']);
        unset($insertData['SubtotalAmount']);
        echo "Removed discount fields from insert data<br>\n";
    }
    
    echo "<h4>Final Insert Data:</h4>\n";
    echo "<pre>" . print_r($insertData, true) . "</pre>\n";
    
    echo "<h4>3.4 Check Customer Table Structure</h4>\n";
    $stmt = $pdo->query("DESCRIBE customers");
    $customerColumns = $stmt->fetchAll();
    echo "Customer table columns: ";
    foreach ($customerColumns as $col) {
        echo $col['Field'] . ", ";
    }
    echo "<br>\n";
    
    // Check if LastPurchaseDate exists
    $hasLastPurchaseDate = false;
    foreach ($customerColumns as $col) {
        if ($col['Field'] === 'LastPurchaseDate') {
            $hasLastPurchaseDate = true;
            break;
        }
    }
    echo "LastPurchaseDate column exists in customers table: " . ($hasLastPurchaseDate ? 'YES' : 'NO') . "<br>\n";
    
    echo "<h4>3.5 Attempting Insert</h4>\n";
    $result = $orderModel->createOrder($testOrderData);
    
    if ($result) {
        echo "✅ Order created successfully! DocumentNo: $result<br>\n";
        
        // Verify it was inserted
        $insertedOrder = $orderModel->findByDocumentNo($result);
        echo "<h4>Inserted Order Data:</h4>\n";
        echo "<pre>" . print_r($insertedOrder, true) . "</pre>\n";
        
    } else {
        echo "❌ Order creation failed<br>\n";
        
        // Get PDO error
        $errorInfo = $pdo->errorInfo();
        echo "PDO Error Info: <pre>" . print_r($errorInfo, true) . "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Exception Occurred</h3>\n";
    echo "Message: " . $e->getMessage() . "<br>\n";
    echo "File: " . $e->getFile() . "<br>\n";
    echo "Line: " . $e->getLine() . "<br>\n";
    echo "Stack Trace:<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<br><a href='javascript:history.back()'>← Back</a>\n";
?>