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
    
    // Get all tasks with customer information
    $sql = "SELECT t.*, c.CustomerName, c.CustomerTel, c.CustomerStatus 
            FROM tasks t 
            LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
            ORDER BY t.FollowupDate DESC 
            LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $tasks,
        'count' => count($tasks),
        'message' => count($tasks) === 0 ? 'No tasks found' : 'All tasks loaded successfully'
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