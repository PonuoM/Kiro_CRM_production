<?php
/**
 * Delete Task API Endpoint
 * DELETE /api/tasks/delete.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow both DELETE and POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
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
    // Get task ID from URL parameter or JSON input
    $taskId = null;
    
    if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
        $taskId = (int)$_GET['id'];
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input && !empty($input['id']) && is_numeric($input['id'])) {
            $taskId = (int)$input['id'];
        }
    }
    
    if (!$taskId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสงานไม่ถูกต้อง'
        ]);
        exit();
    }
    
    // Create task instance
    $task = new Task();
    
    // Check if task exists and user has permission to delete
    $existingTask = $task->find($taskId);
    if (!$existingTask) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบงานที่ระบุ'
        ]);
        exit();
    }
    
    // Check permission (Sales can only delete their own tasks, Admin/Supervisor can delete any)
    $userRole = $_SESSION['Role'] ?? '';
    if ($userRole === 'Sale' && $existingTask['CreatedBy'] !== $_SESSION['Username']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'คุณไม่มีสิทธิ์ลบงานนี้'
        ]);
        exit();
    }
    
    // Delete task
    $success = $task->delete($taskId);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'ลบงานสำเร็จ'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการลบงาน'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Delete task API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ]);
}
?>