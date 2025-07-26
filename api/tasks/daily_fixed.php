<?php
header('Content-Type: application/json');
session_start();

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
    
    // Get today's tasks - try both table structures
    $today = date('Y-m-d');
    
    // Try new table structure first
    try {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE due_date = ? ORDER BY created_at DESC");
        $stmt->execute([$today]);
        $tasks = $stmt->fetchAll();
    } catch(Exception $e) {
        // Try old table structure with Capital letters
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE DueDate = ? ORDER BY CreatedDate DESC");
        $stmt->execute([$today]);
        $tasks = $stmt->fetchAll();
    }
    
    $response = [
        'status' => 'success',
        'data' => $tasks,
        'count' => count($tasks),
        'date' => $today
    ];
    
    echo json_encode($response);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>