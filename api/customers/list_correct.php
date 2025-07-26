<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    require_once '../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get customer status from query parameter
    $customerStatus = $_GET['customer_status'] ?? 'all';
    
    // Build query using correct column names (Capital letters)
    if ($customerStatus === 'all') {
        $sql = "SELECT * FROM customers ORDER BY CreatedDate DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Decode URL encoded Thai text
        $customerStatus = urldecode($customerStatus);
        
        $sql = "SELECT * FROM customers WHERE CustomerStatus = ? ORDER BY CreatedDate DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerStatus]);
    }
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $customers,
        'count' => count($customers),
        'filter' => $customerStatus,
        'message' => count($customers) === 0 ? 'No customers found' : 'Customers loaded successfully'
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>