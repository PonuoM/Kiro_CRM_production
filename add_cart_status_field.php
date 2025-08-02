<?php
/**
 * Add CartStatus Field to Database
 * Simple script to add CartStatus field without complex migration
 */

// Database connection
$host = 'localhost';
$database = 'kiro_crm';
$username = 'root';
$password = '123456';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Check if CartStatus column exists
    $checkSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = 'kiro_crm' 
                 AND TABLE_NAME = 'customers' 
                 AND COLUMN_NAME = 'CartStatus'";
    $result = $pdo->query($checkSql)->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "Adding CartStatus column...\n";
        
        // Add CartStatus column
        $addColumnSql = "ALTER TABLE customers ADD COLUMN CartStatus ENUM('ตะกร้ารอ', 'ตะกร้าแจก', 'ลูกค้าแจกแล้ว') DEFAULT 'ตะกร้ารอ' COMMENT 'สถานะตะกร้า' AFTER CustomerStatus";
        $pdo->exec($addColumnSql);
        echo "CartStatus column added.\n";
        
        // Add index
        $addIndexSql = "ALTER TABLE customers ADD INDEX idx_cart_status (CartStatus)";
        $pdo->exec($addIndexSql);
        echo "Index added.\n";
        
        // Update existing data
        $updateSql = "UPDATE customers SET 
                        CartStatus = CASE 
                            WHEN Sales IS NOT NULL AND Sales != '' THEN 'ลูกค้าแจกแล้ว'
                            ELSE 'ตะกร้ารอ'
                        END";
        $affected = $pdo->exec($updateSql);
        echo "Updated $affected customer records.\n";
        
    } else {
        echo "CartStatus column already exists.\n";
    }
    
    // Show distribution
    echo "\nCurrent CartStatus distribution:\n";
    $distributionSql = "SELECT CartStatus, COUNT(*) as Count FROM customers GROUP BY CartStatus";
    $stmt = $pdo->query($distributionSql);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['CartStatus']}: {$row['Count']} customers\n";
    }
    
    echo "\nCartStatus field setup completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>