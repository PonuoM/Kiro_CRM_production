<?php
/**
 * Database Migration Script - Create Products Table
 * Run this once to create the products table and populate initial data
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>Products Table Migration</h2>\n";
    
    // Read and execute SQL file
    $sql = file_get_contents('create_products_table.sql');
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "✓ Executed statement\n";
        }
    }
    
    // Verify the table and data
    echo "\n<h3>Verification:</h3>\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Products count: " . $result['count'] . "\n";
    
    $stmt = $pdo->query("SELECT product_code, product_name, category FROM products ORDER BY category, product_code LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nSample products:\n";
    foreach ($products as $product) {
        echo "- {$product['product_code']}: {$product['product_name']} ({$product['category']})\n";
    }
    
    echo "\n<p><strong>Migration completed successfully!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>