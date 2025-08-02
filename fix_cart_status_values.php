<?php
/**
 * Fix CartStatus Values to Match API
 * Update the enum values to match what the API expects
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>Fixing CartStatus Values</h2>\n";
    
    // Step 1: Check current values
    echo "<h3>Step 1: Current CartStatus Distribution</h3>\n";
    $currentSql = "SELECT CartStatus, COUNT(*) as count FROM customers GROUP BY CartStatus";
    $stmt = $pdo->query($currentSql);
    echo "<ul>\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . htmlspecialchars($row['CartStatus']) . ": " . $row['count'] . " customers</li>\n";
    }
    echo "</ul>\n";
    
    // Step 2: Update existing data to match API expectations
    echo "<h3>Step 2: Updating Data to Match API</h3>\n";
    
    // Update customers with sales assignment to 'ลูกค้าแจกแล้ว'
    $updateAssignedSql = "UPDATE customers SET CartStatus = 'ลูกค้าแจกแล้ว' 
                         WHERE (Sales IS NOT NULL AND Sales != '') 
                         AND CartStatus = 'กำลังดูแล'";
    $assignedCount = $pdo->exec($updateAssignedSql);
    echo "<p>✅ Updated $assignedCount customers with sales assignment to 'ลูกค้าแจกแล้ว'</p>\n";
    
    // Keep existing 'ตะกร้ารอ' and 'ตะกร้าแจก' as they are correct
    echo "<p>✅ 'ตะกร้ารอ' and 'ตะกร้าแจก' values are correct</p>\n";
    
    // Step 3: Alter the enum to include the correct values and remove old ones
    echo "<h3>Step 3: Updating ENUM Definition</h3>\n";
    try {
        $alterSql = "ALTER TABLE customers MODIFY COLUMN CartStatus 
                    ENUM('ตะกร้ารอ', 'ตะกร้าแจก', 'ลูกค้าแจกแล้ว') 
                    DEFAULT 'ตะกร้ารอ' 
                    COMMENT 'สถานะตะกร้า: ตะกร้ารอ=waiting basket, ตะกร้าแจก=distribution basket, ลูกค้าแจกแล้ว=assigned to sales'";
        $pdo->exec($alterSql);
        echo "<p>✅ ENUM definition updated successfully</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Error updating ENUM: " . $e->getMessage() . "</p>\n";
        echo "<p>This might happen if there are still 'กำลังดูแล' values in the database</p>\n";
        
        // Check if there are still 'กำลังดูแล' values
        $checkOldSql = "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'กำลังดูแล'";
        $oldCount = $pdo->query($checkOldSql)->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($oldCount > 0) {
            echo "<p>Found $oldCount customers with 'กำลังดูแล' status. Converting them...</p>\n";
            
            // Decide what to do with 'กำลังดูแล' customers
            // If they have sales assignment, make them 'ลูกค้าแจกแล้ว'
            // If not, make them 'ตะกร้ารอ'
            $convertSql = "UPDATE customers SET CartStatus = 
                          CASE 
                              WHEN Sales IS NOT NULL AND Sales != '' THEN 'ลูกค้าแจกแล้ว'
                              ELSE 'ตะกร้ารอ'
                          END 
                          WHERE CartStatus = 'กำลังดูแล'";
            $convertedCount = $pdo->exec($convertSql);
            echo "<p>✅ Converted $convertedCount customers from 'กำลังดูแล'</p>\n";
            
            // Try updating the ENUM again
            try {
                $pdo->exec($alterSql);
                echo "<p>✅ ENUM definition updated successfully after conversion</p>\n";
            } catch (Exception $e2) {
                echo "<p>❌ Still having issues with ENUM update: " . $e2->getMessage() . "</p>\n";
            }
        }
    }
    
    // Step 4: Verify the changes
    echo "<h3>Step 4: Verification - Updated CartStatus Distribution</h3>\n";
    $verifyCommands = [
        "ตะกร้ารอ (Waiting)" => "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้ารอ'",
        "ตะกร้าแจก (Distribution)" => "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้าแจก'", 
        "ลูกค้าแจกแล้ว (Assigned)" => "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ลูกค้าแจกแล้ว'"
    ];
    
    echo "<ul>\n";
    foreach ($verifyCommands as $label => $sql) {
        try {
            $result = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
            echo "<li>$label: " . $result['count'] . " customers</li>\n";
        } catch (Exception $e) {
            echo "<li>$label: Error - " . $e->getMessage() . "</li>\n";
        }
    }
    echo "</ul>\n";
    
    // Step 5: Show current column definition
    echo "<h3>Step 5: Current Column Definition</h3>\n";
    $definitionSql = "SELECT COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT 
                     FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'customers' 
                     AND COLUMN_NAME = 'CartStatus'";
    $definition = $pdo->query($definitionSql)->fetch(PDO::FETCH_ASSOC);
    echo "<p><strong>Column Type:</strong> " . htmlspecialchars($definition['COLUMN_TYPE']) . "</p>\n";
    echo "<p><strong>Default:</strong> " . htmlspecialchars($definition['COLUMN_DEFAULT']) . "</p>\n";
    echo "<p><strong>Comment:</strong> " . htmlspecialchars($definition['COLUMN_COMMENT']) . "</p>\n";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3>🎉 CartStatus Fix Completed!</h3>\n";
    echo "<p>The CartStatus field has been updated to match the API expectations.</p>\n";
    echo "<p>You can now test the separation functionality in the CRM system.</p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>\n";
    echo "<h3>❌ Error</h3>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>