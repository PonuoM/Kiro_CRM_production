<?php
/**
 * Debug Tables - หาสาเหตุที่ชื่อตารางเป็นค่าว่าง
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h1>🔍 Debug SHOW TABLES</h1>";
    
    // Get database name
    $dbName = $connection->query("SELECT DATABASE() as db_name")->fetch()['db_name'];
    echo "<p><strong>Database:</strong> {$dbName}</p>";
    
    echo "<h2>1. ทดสอบ SHOW TABLES (PDO::FETCH_ASSOC)</h2>";
    $stmt1 = $connection->query("SHOW TABLES");
    $result1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($result1, true) . "</pre>";
    
    echo "<h2>2. ทดสอบ SHOW TABLES (PDO::FETCH_NUM)</h2>";
    $stmt2 = $connection->query("SHOW TABLES");
    $result2 = $stmt2->fetchAll(PDO::FETCH_NUM);
    echo "<pre>" . print_r($result2, true) . "</pre>";
    
    echo "<h2>3. ทดสอบ SHOW TABLES (PDO::FETCH_COLUMN)</h2>";
    $stmt3 = $connection->query("SHOW TABLES");
    $result3 = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>" . print_r($result3, true) . "</pre>";
    
    echo "<h2>4. ทดสอบ INFORMATION_SCHEMA</h2>";
    $stmt4 = $connection->query("SELECT TABLE_NAME, TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbName}'");
    $result4 = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($result4, true) . "</pre>";
    
    echo "<h2>5. ทดสอบแยกชื่อตาราง</h2>";
    if (!empty($result3)) {
        echo "<p>รายชื่อตารางจาก FETCH_COLUMN:</p><ul>";
        foreach ($result3 as $tableName) {
            echo "<li>'{$tableName}' (length: " . strlen($tableName) . ")</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($result4)) {
        echo "<p>รายชื่อจาก INFORMATION_SCHEMA:</p><ul>";
        foreach ($result4 as $row) {
            echo "<li>Table: '{$row['TABLE_NAME']}' Type: '{$row['TABLE_TYPE']}'</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>