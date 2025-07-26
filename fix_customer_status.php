<?php
/**
 * Fix Customer Status Script
 * Updates customers with NULL or empty CustomerStatus
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "=== Customer Status Fix Script ===\n\n";

    // Check current status distribution
    echo "Current CustomerStatus distribution:\n";
    $stmt = $pdo->query('SELECT CustomerStatus, COUNT(*) as count FROM customers GROUP BY CustomerStatus ORDER BY count DESC');
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($statuses as $status) {
        echo "- " . ($status['CustomerStatus'] ?: 'NULL/Empty') . ": " . $status['count'] . " customers\n";
    }

    // Count customers with NULL or empty status
    $nullCount = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE CustomerStatus IS NULL OR CustomerStatus = ''")->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($nullCount > 0) {
        echo "\nFound $nullCount customers with NULL/empty CustomerStatus\n";
        echo "Updating them with default statuses...\n";

        // Update customers with default statuses based on some logic
        $pdo->beginTransaction();

        // 1. Customers created recently (last 30 days) = ลูกค้าใหม่
        $result1 = $pdo->exec("
            UPDATE customers 
            SET CustomerStatus = 'ลูกค้าใหม่' 
            WHERE (CustomerStatus IS NULL OR CustomerStatus = '') 
            AND CreatedDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        echo "- Updated $result1 recent customers as 'ลูกค้าใหม่'\n";

        // 2. Customers with sales assigned and some purchase history = ลูกค้าติดตาม  
        $result2 = $pdo->exec("
            UPDATE customers 
            SET CustomerStatus = 'ลูกค้าติดตาม' 
            WHERE (CustomerStatus IS NULL OR CustomerStatus = '') 
            AND Sales IS NOT NULL 
            AND Sales != ''
            AND (TotalPurchase > 0 OR LastContactDate IS NOT NULL)
        ");
        echo "- Updated $result2 customers with sales assigned as 'ลูกค้าติดตาม'\n";

        // 3. Older customers with purchase history = ลูกค้าเก่า
        $result3 = $pdo->exec("
            UPDATE customers 
            SET CustomerStatus = 'ลูกค้าเก่า' 
            WHERE (CustomerStatus IS NULL OR CustomerStatus = '') 
            AND CreatedDate < DATE_SUB(NOW(), INTERVAL 90 DAY)
            AND TotalPurchase > 0
        ");
        echo "- Updated $result3 old customers with purchases as 'ลูกค้าเก่า'\n";

        // 4. Remaining customers = ลูกค้าใหม่ (default)
        $result4 = $pdo->exec("
            UPDATE customers 
            SET CustomerStatus = 'ลูกค้าใหม่' 
            WHERE CustomerStatus IS NULL OR CustomerStatus = ''
        ");
        echo "- Updated $result4 remaining customers as 'ลูกค้าใหม่' (default)\n";

        $pdo->commit();
        echo "\nCustomer status update completed successfully!\n";

    } else {
        echo "\nAll customers already have CustomerStatus assigned.\n";
    }

    // Show final distribution
    echo "\nFinal CustomerStatus distribution:\n";
    $stmt = $pdo->query('SELECT CustomerStatus, COUNT(*) as count FROM customers GROUP BY CustomerStatus ORDER BY count DESC');
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($statuses as $status) {
        echo "- " . $status['CustomerStatus'] . ": " . $status['count'] . " customers\n";
    }

    echo "\n=== Script completed successfully ===\n";

} catch(Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>