<?php
/**
 * Migration script to add discount fields to orders table
 */

try {
    $pdo = new PDO('mysql:host=localhost;dbname=crm_system;charset=utf8mb4', 'root', '123456');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Starting migration to add discount fields...\n";
    
    // Check if fields already exist
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $hasDiscountAmount = in_array('DiscountAmount', $columns);
    
    if ($hasDiscountAmount) {
        echo "✅ Discount fields already exist!\n";
        exit;
    }
    
    // Add discount fields
    $alterStatements = [
        "ALTER TABLE orders ADD COLUMN DiscountAmount DECIMAL(10,2) DEFAULT 0 COMMENT 'Discount amount in currency'",
        "ALTER TABLE orders ADD COLUMN DiscountPercent DECIMAL(5,2) DEFAULT 0 COMMENT 'Discount percentage (0-100)'",
        "ALTER TABLE orders ADD COLUMN DiscountRemarks NVARCHAR(500) DEFAULT '' COMMENT 'Discount remarks or reason'",
        "ALTER TABLE orders ADD COLUMN ProductsDetail JSON COMMENT 'Detailed products information in JSON format'",
        "ALTER TABLE orders ADD COLUMN SubtotalAmount DECIMAL(10,2) DEFAULT 0 COMMENT 'Subtotal before discount'"
    ];
    
    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ " . substr($sql, 0, 60) . "...\n";
        } catch (Exception $e) {
            echo "⚠️ " . $e->getMessage() . "\n";
        }
    }
    
    // Add indexes
    try {
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_discount_amount (DiscountAmount)");
        echo "✅ Added index for DiscountAmount\n";
    } catch (Exception $e) {
        echo "⚠️ Index error: " . $e->getMessage() . "\n";
    }
    
    try {
        $pdo->exec("ALTER TABLE orders ADD INDEX idx_discount_percent (DiscountPercent)");
        echo "✅ Added index for DiscountPercent\n";
    } catch (Exception $e) {
        echo "⚠️ Index error: " . $e->getMessage() . "\n";
    }
    
    echo "🎉 Migration completed successfully!\n";
    
    // Show updated table structure
    echo "\n📋 Updated orders table structure:\n";
    $stmt = $pdo->query("DESCRIBE orders");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-20s %-20s %s\n", $row['Field'], $row['Type'], $row['Comment'] ?? '');
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>