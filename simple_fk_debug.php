<?php
// Simple debug without framework
$host = 'localhost';
$dbname = 'primacom_CRM';
$username = 'primacom_bloguser';
$password = 'pJnL53Wkhju2LaGPytw8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>ตรวจสอบ Foreign Key Issue</h2>";
    
    // Check orders table
    echo "<h3>1. SHOW CREATE TABLE orders</h3>";
    $stmt = $pdo->query("SHOW CREATE TABLE orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    
    // Check order_items table
    echo "<h3>2. SHOW CREATE TABLE order_items</h3>";
    $stmt = $pdo->query("SHOW CREATE TABLE order_items");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    
    // Check DocumentNo fields specifically
    echo "<h3>3. DocumentNo Field Comparison</h3>";
    
    echo "<h4>orders.DocumentNo:</h4>";
    $stmt = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLLATION_NAME, IS_NULLABLE, COLUMN_KEY 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'DocumentNo'");
    $ordersField = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($ordersField, true) . "</pre>";
    
    echo "<h4>order_items.DocumentNo:</h4>";
    $stmt = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, COLLATION_NAME, IS_NULLABLE, COLUMN_KEY 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = '$dbname' AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'DocumentNo'");
    $itemsField = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($itemsField, true) . "</pre>";
    
    // Check indexes
    echo "<h3>4. Index Information</h3>";
    echo "<h4>orders indexes:</h4>";
    $stmt = $pdo->query("SHOW INDEX FROM orders WHERE Column_name = 'DocumentNo'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
    echo "<h4>order_items indexes:</h4>";
    $stmt = $pdo->query("SHOW INDEX FROM order_items WHERE Column_name = 'DocumentNo'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>