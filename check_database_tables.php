<?php
/**
 * Check Database Tables
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 Database Tables Check</title>
    <style>body{font-family:Arial;margin:20px} .table{border-collapse:collapse;width:100%} .table td,.table th{border:1px solid #ddd;padding:8px} .table th{background:#f2f2f2}</style>
</head>
<body>

<h1>🔍 Database Tables Check</h1>

<?php
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>📋 All Tables in Database:</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table class='table'>";
    echo "<thead><tr><th>Table Name</th><th>Row Count</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($tables as $table) {
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $count = $countStmt->fetchColumn();
            echo "<tr><td>{$table}</td><td>{$count}</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>{$table}</td><td>Error: " . $e->getMessage() . "</td></tr>";
        }
    }
    echo "</tbody></table>";
    
    // Check specific tables needed for sales
    echo "<h3>🔍 Required Tables Check:</h3>";
    $required_tables = ['orders', 'order_items', 'customers', 'products'];
    
    foreach ($required_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<p>✅ <strong>{$table}</strong> - EXISTS</p>";
            
            // Show structure
            try {
                $stmt = $pdo->query("DESCRIBE `{$table}`");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p><strong>Columns:</strong> ";
                $col_names = array_column($columns, 'Field');
                echo implode(', ', $col_names);
                echo "</p>";
            } catch (Exception $e) {
                echo "<p>Cannot describe table: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ <strong>{$table}</strong> - MISSING</p>";
        }
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>

<h3>📊 Alternative Tables Check:</h3>
<p>Looking for similar table names...</p>

<?php
// Look for similar table names
foreach ($tables as $table) {
    if (stripos($table, 'order') !== false || 
        stripos($table, 'sale') !== false || 
        stripos($table, 'item') !== false) {
        echo "<p>🔍 <strong>{$table}</strong> - Might be related to orders/sales</p>";
    }
}
?>

</body>
</html>