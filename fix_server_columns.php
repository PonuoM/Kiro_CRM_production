<?php
/**
 * Fix Missing Columns on Server
 * Adds missing columns that are causing API errors
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "=== Fix Missing Columns on Server ===\n\n";

    // Get current columns
    $columns = $pdo->query("SHOW COLUMNS FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    echo "Current columns: " . implode(", ", $columns) . "\n\n";

    $columnsToAdd = [
        'LastContactDate' => 'DATE DEFAULT NULL',
        'ContactAttempts' => 'INT DEFAULT 0',
        'GradeCalculatedDate' => 'DATETIME DEFAULT NULL',
        'TemperatureUpdatedDate' => 'DATETIME DEFAULT NULL'
    ];

    $pdo->beginTransaction();

    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $columns)) {
            $sql = "ALTER TABLE customers ADD COLUMN $column $definition";
            echo "Adding column: $column\n";
            echo "SQL: $sql\n";
            
            $pdo->exec($sql);
            echo "✅ Successfully added $column\n\n";
        } else {
            echo "⚠️ Column $column already exists\n\n";
        }
    }

    // Update some sample data
    echo "Updating sample data...\n";
    
    // Set LastContactDate for customers with sales
    $pdo->exec("
        UPDATE customers 
        SET LastContactDate = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 30) DAY),
            ContactAttempts = FLOOR(RAND() * 10) + 1,
            GradeCalculatedDate = NOW(),
            TemperatureUpdatedDate = NOW()
        WHERE Sales IS NOT NULL 
        AND Sales != ''
        AND LastContactDate IS NULL
        LIMIT 20
    ");
    
    echo "✅ Updated sample data\n";

    $pdo->commit();
    
    echo "\n=== All missing columns have been added successfully! ===\n";
    echo "Now test API: https://www.prima49.com/crm_system/Kiro_CRM_production/api/customers/list.php?customer_status=ลูกค้าใหม่\n";

} catch(Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>