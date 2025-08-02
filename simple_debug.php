<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "=== DEBUG TOTALPURCHASE ISSUE ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Check orders table
    echo "1. Checking orders table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $orderCount = $stmt->fetchColumn();
    echo "Total orders: $orderCount\n";
    
    if ($orderCount > 0) {
        // Check columns
        $stmt = $pdo->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(', ', $columns) . "\n";
        
        // Check sample data
        $stmt = $pdo->query("SELECT CustomerCode, Price, TotalAmount FROM orders LIMIT 3");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Sample data:\n";
        foreach ($samples as $sample) {
            echo "  {$sample['CustomerCode']}: Price={$sample['Price']}, TotalAmount={$sample['TotalAmount']}\n";
        }
        
        // Check CUST003
        echo "\n2. Checking CUST003...\n";
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(Price) as total_price FROM orders WHERE CustomerCode = ?");
        $stmt->execute(['CUST003']);
        $cust003 = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "CUST003 orders: {$cust003['count']}, Total Price: {$cust003['total_price']}\n";
    } else {
        echo "❌ No orders found!\n";
    }
    
    // Check if the UPDATE query would work
    echo "\n3. Testing update query...\n";
    try {
        $updateSql = "
            UPDATE customers c
            SET TotalPurchase = COALESCE((
                SELECT SUM(o.Price) 
                FROM orders o 
                WHERE o.CustomerCode = c.CustomerCode 
                AND o.Price IS NOT NULL
                AND o.Price > 0
            ), 0)
            WHERE c.CustomerCode = 'CUST003'
        ";
        
        $stmt = $pdo->prepare($updateSql);
        $result = $stmt->execute();
        $affected = $stmt->rowCount();
        echo "✅ Update test successful. Affected rows: $affected\n";
        
    } catch (Exception $e) {
        echo "❌ Update test failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "💥 FATAL ERROR: " . $e->getMessage() . "\n";
}
?>