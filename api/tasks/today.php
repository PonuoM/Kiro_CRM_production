<?php
/**
 * Get Today's Tasks API Endpoint
 * GET /api/tasks/today.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

require_once '../../includes/functions.php';
require_once '../../includes/Task.php';

// Check authentication
session_start();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาเข้าสู่ระบบ'
    ]);
    exit();
}

try {
    // Create task instance
    $task = new Task();
    
    // Get today's tasks (for sales users, only their own tasks)
    $userRole = $_SESSION['Role'] ?? '';
    $createdBy = ($userRole === 'Sale') ? $_SESSION['Username'] : null;
    
    $todayTasks = $task->getTodayTasks($createdBy);
    
    // Also get overdue tasks
    $overdueTasks = $task->getOverdueTasks($createdBy);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'today' => $todayTasks,
            'overdue' => $overdueTasks,
            'total_today' => count($todayTasks),
            'total_overdue' => count($overdueTasks)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get today's tasks API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ]);
}
?>