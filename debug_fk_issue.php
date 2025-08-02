<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "=== ตรวจสอบ CREATE TABLE orders ===\n";
    $result = $conn->query("SHOW CREATE TABLE orders");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'] . "\n\n";
    
    echo "=== ตรวจสอบ CREATE TABLE order_items ===\n";
    $result = $conn->query("SHOW CREATE TABLE order_items");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo $row['Create Table'] . "\n\n";
    
    echo "=== ตรวจสอบ DESCRIBE orders ===\n";
    $result = $conn->query("DESCRIBE orders");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Field'] == 'DocumentNo') {
            echo "orders.DocumentNo: Type={$row['Type']}, Null={$row['Null']}, Key={$row['Key']}, Default={$row['Default']}, Extra={$row['Extra']}\n";
        }
    }
    
    echo "\n=== ตรวจสอบ DESCRIBE order_items ===\n";
    $result = $conn->query("DESCRIBE order_items");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Field'] == 'DocumentNo') {
            echo "order_items.DocumentNo: Type={$row['Type']}, Null={$row['Null']}, Key={$row['Key']}, Default={$row['Default']}, Extra={$row['Extra']}\n";
        }
    }
    
    echo "\n=== ตรวจสอบ Index ของ order_items ===\n";
    $result = $conn->query("SHOW INDEX FROM order_items");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Column_name'] == 'DocumentNo') {
            echo "DocumentNo Index: Key_name={$row['Key_name']}, Non_unique={$row['Non_unique']}, Seq_in_index={$row['Seq_in_index']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>