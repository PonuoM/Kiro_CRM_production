<?php
/**
 * Get Task Detail API Endpoint
 * GET /api/tasks/detail.php?id={task_id}
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
    // Get task ID from URL parameter
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสงานไม่ถูกต้อง'
        ]);
        exit();
    }
    
    $taskId = (int)$_GET['id'];
    
    // Create task instance
    $task = new Task();
    
    // Get task with customer information
    $sql = "SELECT t.*, c.CustomerName, c.CustomerTel, c.CustomerAddress, c.CustomerStatus
            FROM tasks t 
            LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
            WHERE t.id = ?";
    
    $taskDetail = $task->queryOne($sql, [$taskId]);
    
    if (!$taskDetail) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบงานที่ระบุ'
        ]);
        exit();
    }
    
    // Check permission (Sales can only view their own tasks)
    $userRole = $_SESSION['Role'] ?? '';
    if ($userRole === 'Sale' && $taskDetail['CreatedBy'] !== $_SESSION['Username']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'คุณไม่มีสิทธิ์ดูงานนี้'
        ]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'data' => $taskDetail
    ]);
    
} catch (Exception $e) {
    error_log("Get task detail API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ]);
}
?>