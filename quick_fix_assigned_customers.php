<?php
/**
 * Quick Fix for Assigned Customers
 * Update customers with sales assignment to have correct CartStatus
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>Quick Fix: Update Assigned Customers CartStatus</h2>\n";
    
    // Step 1: Show current status
    echo "<h3>Before Fix:</h3>\n";
    $beforeSql = "SELECT 
                    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 END) as with_sales,
                    COUNT(CASE WHEN Sales IS NULL OR Sales = '' THEN 1 END) as without_sales,
                    COUNT(CASE WHEN CartStatus = 'กำลังดูแล' THEN 1 END) as caring_status
                  FROM customers";
    $before = $pdo->query($beforeSql)->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>\n";
    echo "<li>Customers with sales assignment: " . $before['with_sales'] . "</li>\n";
    echo "<li>Customers without sales assignment: " . $before['without_sales'] . "</li>\n";
    echo "<li>Customers with 'กำลังดูแล' status: " . $before['caring_status'] . "</li>\n";
    echo "</ul>\n";
    
    // Step 2: Update customers with sales assignment
    echo "<h3>Step 1: Update Customers with Sales Assignment</h3>\n";
    $updateAssignedSql = "UPDATE customers 
                         SET CartStatus = 'ลูกค้าแจกแล้ว' 
                         WHERE (Sales IS NOT NULL AND Sales != '') 
                         AND CartStatus != 'ลูกค้าแจกแล้ว'";
    $assignedUpdated = $pdo->exec($updateAssignedSql);
    echo "<p>✅ Updated $assignedUpdated customers with sales assignment to 'ลูกค้าแจกแล้ว'</p>\n";
    
    // Step 3: Convert remaining 'กำลังดูแล' to 'ตะกร้ารอ'
    echo "<h3>Step 2: Convert Remaining 'กำลังดูแล' to 'ตะกร้ารอ'</h3>\n";
    $convertRemainingSql = "UPDATE customers 
                           SET CartStatus = 'ตะกร้ารอ' 
                           WHERE CartStatus = 'กำลังดูแล'";
    $remainingConverted = $pdo->exec($convertRemainingSql);
    echo "<p>✅ Converted $remainingConverted remaining customers to 'ตะกร้ารอ'</p>\n";
    
    // Step 4: Show final distribution
    echo "<h3>Final CartStatus Distribution:</h3>\n";
    $finalSql = "SELECT CartStatus, COUNT(*) as count FROM customers GROUP BY CartStatus";
    $stmt = $pdo->query($finalSql);
    echo "<ul>\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . htmlspecialchars($row['CartStatus']) . ": " . $row['count'] . " customers</li>\n";
    }
    echo "</ul>\n";
    
    // Step 5: Verify assigned customers have sales
    echo "<h3>Verification: Assigned Customers with Sales</h3>\n";
    $verifySql = "SELECT COUNT(*) as count FROM customers 
                  WHERE CartStatus = 'ลูกค้าแจกแล้ว' 
                  AND (Sales IS NOT NULL AND Sales != '')";
    $verifyResult = $pdo->query($verifySql)->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Customers with 'ลูกค้าแจกแล้ว' status and sales assignment: " . $verifyResult['count'] . "</p>\n";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3>🎉 Quick Fix Completed!</h3>\n";
    echo "<p>All customers with sales assignments now have CartStatus = 'ลูกค้าแจกแล้ว'</p>\n";
    echo "<p>The cart status separation should now work correctly.</p>\n";
    echo "<p><strong>Next:</strong> Test the waiting and distribution basket pages</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3>❌ Error</h3>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>