<?php
/**
 * Fix Discount Database Issues
 * Add missing discount columns and test functionality
 */

require_once 'config/database.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $results = [];
    
    // 1. Check current table structure
    $stmt = $pdo->query("DESCRIBE orders");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Add missing discount columns
    $requiredColumns = [
        'DiscountAmount' => 'DECIMAL(10,2) DEFAULT 0.00 COMMENT "จำนวนส่วนลด (บาท)"',
        'DiscountPercent' => 'DECIMAL(5,2) DEFAULT 0.00 COMMENT "เปอร์เซ็นต์ส่วนลด"',
        'DiscountRemarks' => 'TEXT NULL COMMENT "หมายเหตุส่วนลด"'
    ];
    
    $addedColumns = 0;
    foreach ($requiredColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            try {
                $sql = "ALTER TABLE orders ADD COLUMN $columnName $columnDefinition";
                $pdo->exec($sql);
                $results[] = "✅ Added column: $columnName";
                $addedColumns++;
            } catch (Exception $e) {
                $results[] = "❌ Failed to add column $columnName: " . $e->getMessage();
            }
        } else {
            $results[] = "✅ Column already exists: $columnName";
        }
    }
    
    // 3. Test insert with discount data
    try {
        $testSql = "INSERT INTO orders 
                   (DocumentNo, CustomerCode, DocumentDate, Products, Quantity, Price, 
                    DiscountAmount, DiscountPercent, DiscountRemarks, SubtotalAmount, 
                    OrderBy, CreatedDate, CreatedBy) 
                   VALUES 
                   ('TESTDISC001', 'TESTCUST001', NOW(), 'Test Product with Discount', 2, 50.00, 
                    10.00, 10.00, 'Test 10% discount', 80.00, 
                    'system_test', NOW(), 'system_test')";
        
        $pdo->exec($testSql);
        $results[] = "✅ Test insert with discount data: SUCCESS";
        
        // Verify the inserted data
        $verifySql = "SELECT DocumentNo, DiscountAmount, DiscountPercent, DiscountRemarks, SubtotalAmount 
                     FROM orders WHERE DocumentNo = 'TESTDISC001'";
        $stmt = $pdo->prepare($verifySql);
        $stmt->execute();
        $testData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($testData) {
            $results[] = "✅ Verified test data: " . json_encode($testData, JSON_UNESCAPED_UNICODE);
        }
        
        // Clean up test data
        $pdo->exec("DELETE FROM orders WHERE DocumentNo = 'TESTDISC001' AND CustomerCode = 'TESTCUST001'");
        $results[] = "✅ Cleaned up test data";
        
    } catch (Exception $e) {
        $results[] = "❌ Test insert failed: " . $e->getMessage();
    }
    
    // 4. Show current table structure
    $stmt = $pdo->query("DESCRIBE orders");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $discountCols = array_filter($finalColumns, function($col) {
        return stripos($col['Field'], 'discount') !== false;
    });
    
    // 5. Check existing orders for discount data
    $checkDataSql = "SELECT COUNT(*) as total_orders, 
                     COUNT(CASE WHEN DiscountAmount > 0 THEN 1 END) as orders_with_discount_amount,
                     COUNT(CASE WHEN DiscountPercent > 0 THEN 1 END) as orders_with_discount_percent
                     FROM orders";
    $stmt = $pdo->prepare($checkDataSql);
    $stmt->execute();
    $discountStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Discount database fix completed',
        'results' => $results,
        'added_columns' => $addedColumns,
        'discount_columns' => array_column($discountCols, 'Field'),
        'discount_stats' => $discountStats,
        'recommendations' => [
            'Test order creation from customer_detail.php form',
            'Verify Order.php model recognizes new columns',
            'Check that API correctly saves discount values'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}
?>