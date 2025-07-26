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

// Check if customer code provided
$customerCode = $_GET['code'] ?? $_GET['customer_code'] ?? '';
if (empty($customerCode)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Customer code is required'
    ]);
    exit;
}

try {
    require_once '../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get customer details using correct column names
    $sql = "SELECT * FROM customers WHERE CustomerCode = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customerCode]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Customer not found'
        ]);
        exit;
    }
    
    // Get recent tasks for this customer
    $sql = "SELECT * FROM tasks WHERE CustomerCode = ? ORDER BY FollowupDate DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customerCode]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent orders for this customer (if orders table exists)
    $orders = [];
    try {
        $sql = "SELECT * FROM orders WHERE CustomerCode = ? ORDER BY DocumentDate DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Orders table might not exist, that's ok
    }
    
    // Get call history (if call_logs table exists)
    $callLogs = [];
    try {
        $sql = "SELECT * FROM call_logs WHERE CustomerCode = ? ORDER BY CallDate DESC LIMIT 10";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $callLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Call logs table might not exist, that's ok
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'customer' => $customer,
            'tasks' => $tasks,
            'orders' => $orders,
            'call_logs' => $callLogs
        ],
        'message' => 'Customer details loaded successfully'
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