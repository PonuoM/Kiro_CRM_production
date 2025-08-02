<?php
/**
 * Run discount fields migration
 * This script adds discount columns to orders table
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>Running Discount Fields Migration</h2>\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'DiscountAmount'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Discount fields already exist. Migration skipped.</p>\n";
        exit;
    }
    
    // Add discount fields
    $migrations = [
        "ALTER TABLE orders ADD COLUMN DiscountAmount DECIMAL(10,2) DEFAULT 0 COMMENT 'Discount amount in currency'",
        "ALTER TABLE orders ADD COLUMN DiscountPercent DECIMAL(5,2) DEFAULT 0 COMMENT 'Discount percentage (0-100)'",
        "ALTER TABLE orders ADD COLUMN DiscountRemarks NVARCHAR(500) DEFAULT '' COMMENT 'Discount remarks or reason'",
        "ALTER TABLE orders ADD COLUMN ProductsDetail JSON COMMENT 'Detailed products information in JSON format'",
        "ALTER TABLE orders ADD COLUMN SubtotalAmount DECIMAL(10,2) DEFAULT 0 COMMENT 'Subtotal before discount'",
        "ALTER TABLE orders ADD INDEX idx_discount_amount (DiscountAmount)",
        "ALTER TABLE orders ADD INDEX idx_discount_percent (DiscountPercent)"
    ];
    
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Executed: " . htmlspecialchars($sql) . "</p>\n";
        } catch (PDOException $e) {
            echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            echo "<p>SQL: " . htmlspecialchars($sql) . "</p>\n";
        }
    }
    
    // Verify columns were added
    echo "<h3>Updated Table Structure:</h3>\n";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<p><strong>✅ Migration completed successfully!</strong></p>\n";
    echo "<p>You can now test creating orders with discount functionality.</p>\n";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Migration failed:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>