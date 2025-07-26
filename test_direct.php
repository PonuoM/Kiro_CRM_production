<?php
session_start();

// Mock session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'admin';
    $_SESSION['role'] = 'Admin';
    $_SESSION['username'] = 'admin';
}

echo "<h3>Direct API Test</h3>";

try {
    require_once 'config/database.php';
    require_once 'includes/permissions.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    echo "✅ Current user: $currentUser<br>";
    echo "✅ Can view all: " . ($canViewAll ? 'Yes' : 'No') . "<br><br>";
    
    // Test each status directly
    $statuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];
    
    foreach($statuses as $status) {
        echo "<h4>Testing: $status</h4>";
        
        $sql = "SELECT CustomerCode, CustomerName, CustomerStatus FROM customers WHERE CustomerStatus = ? LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($customers) . " customers:<br>";
        
        foreach($customers as $customer) {
            echo "- " . $customer['CustomerCode'] . ": " . $customer['CustomerName'] . "<br>";
        }
        echo "<hr>";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>