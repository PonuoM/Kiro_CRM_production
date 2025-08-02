<?php
/**
 * Add Discount Columns to Orders Table
 * Add DiscountAmount, DiscountPercent, DiscountRemarks if they don't exist
 */

require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $results = [];
    $addedColumns = 0;
    
    // Check existing columns
    $stmt = $pdo->query("DESCRIBE orders");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Define required discount columns
    $requiredColumns = [
        'DiscountAmount' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "จำนวนส่วนลด (บาท)"',
        'DiscountPercent' => 'DECIMAL(5,2) DEFAULT 0.00 COMMENT "เปอร์เซ็นต์ส่วนลด"',
        'DiscountRemarks' => 'TEXT NULL COMMENT "หมายเหตุส่วนลด"'
    ];
    
    // Add missing columns
    foreach ($requiredColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            $sql = "ALTER TABLE orders ADD COLUMN $columnName $columnDefinition";
            $pdo->exec($sql);
            $results[] = "Added column: $columnName";
            $addedColumns++;
        } else {
            $results[] = "Column already exists: $columnName";
        }
    }
    
    // Update Order.php model if needed
    $orderModelPath = 'includes/Order.php';
    if (file_exists($orderModelPath)) {
        $orderContent = file_get_contents($orderModelPath);
        
        // Check if discount fields are included in insert/update statements
        if (strpos($orderContent, 'DiscountAmount') === false) {
            $results[] = "Note: Order.php model may need updating to include discount fields";
        }
    }
    
    // Test insert with discount data
    $testResults = [];
    try {
        // Create a test order with discount data
        $testSql = "INSERT INTO orders 
                   (DocumentNo, CustomerCode, DocumentDate, Products, Quantity, Price, 
                    DiscountAmount, DiscountPercent, DiscountRemarks, SubtotalAmount, 
                    OrderBy, CreatedDate, CreatedBy) 
                   VALUES 
                   ('TEST001', 'TEST001', NOW(), 'Test Product', 1, 100.00, 
                    10.00, 5.00, 'Test discount', 85.00, 
                    'test_user', NOW(), 'test_user')";
        
        $pdo->exec($testSql);
        $testResults[] = "✅ Test insert with discount data: SUCCESS";
        
        // Verify the data
        $verifySql = "SELECT DocumentNo, DiscountAmount, DiscountPercent, DiscountRemarks 
                     FROM orders WHERE DocumentNo = 'TEST001'";
        $stmt = $pdo->prepare($verifySql);
        $stmt->execute();
        $testData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testData) {
            $testResults[] = "✅ Verified discount data: " . json_encode($testData);
        }
        
        // Clean up test data
        $pdo->exec("DELETE FROM orders WHERE DocumentNo = 'TEST001' AND CustomerCode = 'TEST001'");
        $testResults[] = "✅ Cleaned up test data";
        
    } catch (Exception $e) {
        $testResults[] = "❌ Test insert failed: " . $e->getMessage();
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully processed discount columns",
        'details' => [
            'added_columns' => $addedColumns,
            'column_operations' => $results,
            'test_results' => $testResults
        ],
        'recommendations' => [
            'Check api/orders/create.php includes discount fields in INSERT statement',
            'Verify Order.php model includes discount fields',
            'Test actual order creation from customer_detail.php form'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>