<?php
/**
 * Check Products Table Structure
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><title>🔍 Check Products Table</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;background:#d4edda;padding:10px;margin:5px 0;border-radius:3px;} .error{color:red;background:#f8d7da;padding:10px;margin:5px 0;border-radius:3px;} .info{color:blue;background:#d1ecf1;padding:10px;margin:5px 0;border-radius:3px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;}</style>";
echo "</head><body>";

echo "<h1>🔍 Check Products Table Structure</h1>";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'sales02';
    $_SESSION['role'] = 'Sales';
}

try {
    require_once __DIR__ . '/config/database.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>1. Products Table Structure</h2>";
    
    // Get table structure
    $stmt = $pdo->prepare("DESCRIBE products");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>" . $column['Field'] . "</strong></td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>2. Sample Data</h2>";
    
    // Get sample data
    $stmt = $pdo->prepare("SELECT * FROM products LIMIT 10");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        echo "<table>";
        
        // Header
        echo "<tr>";
        foreach (array_keys($products[0]) as $key) {
            echo "<th>" . $key . "</th>";
        }
        echo "</tr>";
        
        // Data
        foreach ($products as $product) {
            echo "<tr>";
            foreach ($product as $value) {
                echo "<td>" . ($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No data found</div>";
    }
    
    echo "<h2>3. รหัสสินค้าที่ขึ้นต้นด้วย FER และ BIO</h2>";
    
    // Find columns that might contain product codes
    $possibleColumns = [];
    foreach ($columns as $column) {
        $columnName = $column['Field'];
        if (stripos($columnName, 'code') !== false || 
            stripos($columnName, 'product') !== false ||
            stripos($columnName, 'id') !== false) {
            $possibleColumns[] = $columnName;
        }
    }
    
    echo "<div class='info'>📊 Possible product code columns: " . implode(', ', $possibleColumns) . "</div>";
    
    // Test each possible column for FER/BIO patterns
    foreach ($possibleColumns as $column) {
        echo "<h3>Testing column: {$column}</h3>";
        
        try {
            // Look for FER products
            $stmt = $pdo->prepare("SELECT {$column}, COUNT(*) as count FROM products WHERE {$column} LIKE 'FER%' GROUP BY {$column} LIMIT 5");
            $stmt->execute();
            $ferProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($ferProducts)) {
                echo "<div class='success'>✅ Found FER products in {$column}:</div>";
                foreach ($ferProducts as $product) {
                    echo "<div class='info'>• " . $product[$column] . " (count: " . $product['count'] . ")</div>";
                }
            }
            
            // Look for BIO products
            $stmt = $pdo->prepare("SELECT {$column}, COUNT(*) as count FROM products WHERE {$column} LIKE 'BIO%' GROUP BY {$column} LIMIT 5");
            $stmt->execute();
            $bioProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($bioProducts)) {
                echo "<div class='success'>✅ Found BIO products in {$column}:</div>";
                foreach ($bioProducts as $product) {
                    echo "<div class='info'>• " . $product[$column] . " (count: " . $product['count'] . ")</div>";
                }
            }
            
            if (empty($ferProducts) && empty($bioProducts)) {
                echo "<div class='error'>❌ No FER/BIO products found in {$column}</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error testing {$column}: " . $e->getMessage() . "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>