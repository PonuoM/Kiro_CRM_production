<?php
/**
 * Debug CartStatus Issue
 * Find and fix the empty CartStatus values
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>Debug CartStatus Issue</h2>\n";
    
    // Step 1: Find customers with sales but empty/null CartStatus
    echo "<h3>Step 1: Find Customers with Sales Assignment but Empty CartStatus</h3>\n";
    $debugSql = "SELECT CustomerCode, CustomerName, Sales, CartStatus, 
                        CASE 
                            WHEN CartStatus IS NULL THEN 'NULL' 
                            WHEN CartStatus = '' THEN 'EMPTY STRING'
                            ELSE CONCAT('VALUE: ', CartStatus)
                        END as CartStatusDebug
                 FROM customers 
                 WHERE (Sales IS NOT NULL AND Sales != '') 
                 AND (CartStatus IS NULL OR CartStatus = '' OR CartStatus != 'ลูกค้าแจกแล้ว')
                 LIMIT 10";
    
    $stmt = $pdo->query($debugSql);
    $problemCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($problemCustomers) > 0) {
        echo "<p>Found " . count($problemCustomers) . " customers with sales assignment but incorrect CartStatus:</p>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>CustomerCode</th><th>CustomerName</th><th>Sales</th><th>CartStatus Debug</th></tr>\n";
        
        foreach ($problemCustomers as $customer) {
            echo "<tr>\n";
            echo "<td>" . htmlspecialchars($customer['CustomerCode']) . "</td>\n";
            echo "<td>" . htmlspecialchars($customer['CustomerName']) . "</td>\n";
            echo "<td>" . htmlspecialchars($customer['Sales']) . "</td>\n";
            echo "<td>" . htmlspecialchars($customer['CartStatusDebug']) . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Step 2: Fix the empty CartStatus values
        echo "<h3>Step 2: Fix Empty CartStatus Values</h3>\n";
        $fixSql = "UPDATE customers 
                   SET CartStatus = 'ลูกค้าแจกแล้ว' 
                   WHERE (Sales IS NOT NULL AND Sales != '') 
                   AND (CartStatus IS NULL OR CartStatus = '' OR CartStatus != 'ลูกค้าแจกแล้ว')";
        
        $fixedCount = $pdo->exec($fixSql);
        echo "<p>✅ Fixed $fixedCount customers by setting CartStatus = 'ลูกค้าแจกแล้ว'</p>\n";
        
    } else {
        echo "<p>✅ No customers found with sales assignment and incorrect CartStatus</p>\n";
    }
    
    // Step 3: Check for any other CartStatus issues
    echo "<h3>Step 3: Check All CartStatus Values</h3>\n";
    $allStatusSql = "SELECT 
                        CASE 
                            WHEN CartStatus IS NULL THEN 'NULL'
                            WHEN CartStatus = '' THEN 'EMPTY STRING'
                            ELSE CartStatus
                        END as CartStatusDisplay,
                        COUNT(*) as count
                     FROM customers 
                     GROUP BY CartStatus
                     ORDER BY count DESC";
    
    $stmt = $pdo->query($allStatusSql);
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>CartStatus</th><th>Count</th></tr>\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($row['CartStatusDisplay']) . "</td>\n";
        echo "<td>" . $row['count'] . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Step 4: Final verification
    echo "<h3>Step 4: Final Verification</h3>\n";
    $finalChecks = [
        "ตะกร้ารอ (Waiting)" => "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้ารอ'",
        "ตะกร้าแจก (Distribution)" => "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้าแจก'", 
        "ลูกค้าแจกแล้ว (Assigned)" => "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ลูกค้าแจกแล้ว'",
        "Customers with Sales + Assigned Status" => "SELECT COUNT(*) as count FROM customers WHERE (Sales IS NOT NULL AND Sales != '') AND CartStatus = 'ลูกค้าแจกแล้ว'",
        "Customers with Sales but Wrong Status" => "SELECT COUNT(*) as count FROM customers WHERE (Sales IS NOT NULL AND Sales != '') AND CartStatus != 'ลูกค้าแจกแล้ว'"
    ];
    
    echo "<ul>\n";
    foreach ($finalChecks as $label => $sql) {
        try {
            $result = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
            $color = ($label == "Customers with Sales but Wrong Status" && $result['count'] > 0) ? "color: red;" : "color: green;";
            echo "<li style='$color'><strong>$label:</strong> " . $result['count'] . " customers</li>\n";
        } catch (Exception $e) {
            echo "<li style='color: red;'><strong>$label:</strong> Error - " . $e->getMessage() . "</li>\n";
        }
    }
    echo "</ul>\n";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3>🎉 Debug and Fix Completed!</h3>\n";
    echo "<p>All CartStatus issues should now be resolved.</p>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Test the cart status separation in the CRM system</li>\n";
    echo "<li>Go to ตะกร้ารอ (Waiting Basket) page</li>\n";
    echo "<li>Go to ตะกร้าแจก (Distribution Basket) page</li>\n";
    echo "<li>Verify that assigned customers don't appear in either basket</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3>❌ Error</h3>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>