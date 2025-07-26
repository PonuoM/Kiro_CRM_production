<!DOCTYPE html>
<html>
<head>
    <title>Orders Table Structure Check</title>
    <style>
        body { font-family: monospace; white-space: pre-wrap; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .add-column { color: blue; font-weight: bold; }
    </style>
</head>
<body>
<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='section'>";
    echo "=== ORDERS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE orders");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($structure);
    echo "</div>";
    
    // Check if ProductsDetail column exists
    $hasProductsDetail = false;
    foreach ($structure as $column) {
        if ($column['Field'] === 'ProductsDetail') {
            $hasProductsDetail = true;
            break;
        }
    }
    
    echo "<div class='section'>";
    if ($hasProductsDetail) {
        echo "✅ ProductsDetail column EXISTS\n";
    } else {
        echo "❌ ProductsDetail column MISSING\n";
        echo "<div class='add-column'>";
        echo "Need to add: ALTER TABLE orders ADD COLUMN ProductsDetail TEXT AFTER Products;\n";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "=== SAMPLE ORDERS DATA ===\n";
    $stmt = $pdo->query("SELECT * FROM orders LIMIT 3");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($orders);
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section' style='color: red;'>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "</div>";
}
?>
</body>
</html>