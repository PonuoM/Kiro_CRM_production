<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    echo "Checking CustomerStatus values in database:\n";
    $stmt = $pdo->query('SELECT CustomerStatus, COUNT(*) as count FROM customers GROUP BY CustomerStatus ORDER BY count DESC');
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($statuses as $status) {
        echo "- " . ($status['CustomerStatus'] ?: 'NULL') . ": " . $status['count'] . " customers\n";
    }

    echo "\nTotal customers: ";
    $total = $pdo->query('SELECT COUNT(*) as total FROM customers')->fetch(PDO::FETCH_ASSOC);
    echo $total['total'] . "\n";

    echo "\nSample customer data:\n";
    $sample = $pdo->query('SELECT CustomerCode, CustomerName, CustomerStatus FROM customers LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    foreach($sample as $customer) {
        echo "- " . $customer['CustomerCode'] . ": " . $customer['CustomerName'] . " (" . ($customer['CustomerStatus'] ?: 'NULL') . ")\n";
    }

} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>