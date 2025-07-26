<?php
session_start();

// Mock session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'test_user';
    $_SESSION['role'] = 'Admin';
    $_SESSION['username'] = 'admin';
}

echo "<h3>Simple API Test</h3>";

try {
    require_once 'config/database.php';
    require_once 'includes/permissions.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "✅ Database connection successful<br>";
    
    // Test basic query
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "✅ Found $count customers with status 'ลูกค้าใหม่'<br>";
    
    // Test API directly
    echo "<br><h4>Testing API inclusion:</h4>";
    
    // Simulate GET parameters
    $_GET['customer_status'] = 'ลูกค้าใหม่';
    
    ob_start();
    include 'api/customers/list.php';
    $output = ob_get_clean();
    
    echo "API Output:<br>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>