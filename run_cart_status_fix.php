<?php
/**
 * Execute CartStatus Fix Script
 * Add CartStatus field to separate waiting and distribution baskets
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "Starting CartStatus field addition...\n";
    
    // Check if CartStatus column exists
    $checkSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'customers' 
                 AND COLUMN_NAME = 'CartStatus'";
    $result = $pdo->query($checkSql)->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "Adding CartStatus column...\n";
        
        // Add CartStatus column
        $addColumnSql = "ALTER TABLE customers ADD COLUMN CartStatus ENUM('ตะกร้ารอ', 'ตะกร้าแจก', 'ลูกค้าแจกแล้ว') DEFAULT 'ตะกร้ารอ' COMMENT 'สถานะตะกร้า: ตะกร้ารอ=waiting basket, ตะกร้าแจก=distribution basket, ลูกค้าแจกแล้ว=assigned to sales' AFTER CustomerStatus";
        $pdo->exec($addColumnSql);
        
        // Add index
        $addIndexSql = "ALTER TABLE customers ADD INDEX idx_cart_status (CartStatus)";
        $pdo->exec($addIndexSql);
        
        echo "CartStatus column added successfully!\n";
    } else {
        echo "CartStatus column already exists.\n";
    }
    
    // Update existing data
    echo "Updating existing customer cart status...\n";
    $updateSql = "UPDATE customers SET 
                    CartStatus = CASE 
                        WHEN Sales IS NOT NULL AND Sales != '' THEN 'ลูกค้าแจกแล้ว'
                        ELSE 'ตะกร้ารอ'
                    END
                  WHERE CartStatus IS NULL OR CartStatus = ''";
    
    $affected = $pdo->exec($updateSql);
    echo "Updated {$affected} customer records.\n";
    
    // Add composite index if not exists
    $checkIndexSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS 
                      WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'customers' 
                      AND INDEX_NAME = 'idx_cart_sales_status'";
    $indexResult = $pdo->query($checkIndexSql)->fetch(PDO::FETCH_ASSOC);
    
    if ($indexResult['count'] == 0) {
        $compositeIndexSql = "ALTER TABLE customers ADD INDEX idx_cart_sales_status (CartStatus, Sales)";
        $pdo->exec($compositeIndexSql);
        echo "Composite index added.\n";
    }
    
    // Show current distribution
    echo "\nCurrent CartStatus distribution:\n";
    $distributionSql = "SELECT CartStatus, COUNT(*) as Count FROM customers GROUP BY CartStatus";
    $distribution = $pdo->query($distributionSql)->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($distribution as $row) {
        echo "- {$row['CartStatus']}: {$row['Count']} customers\n";
    }
    
    echo "\nCartStatus field setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>