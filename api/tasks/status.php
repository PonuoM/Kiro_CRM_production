<?php
/**
 * Update Task Status API Endpoint
 * POST /api/tasks/status.php
 * Updates task status (complete/pending)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow both POST and PUT requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง'
        ]);
        exit();
    }
    
    // Validate required fields
    if (empty($input['id']) || !is_numeric($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสงานไม่ถูกต้อง'
        ]);
        exit();
    }
    
    if (empty($input['status']) || !in_array($input['status'], ['รอดำเนินการ', 'เสร็จสิ้น'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'สถานะไม่ถูกต้อง'
        ]);
        exit();
    }
    
    $taskId = (int)$input['id'];
    $newStatus = $input['status'];
    
    // Create task instance
    $task = new Task();
    
    // Check if task exists and user has permission
    $existingTask = $task->find($taskId);
    if (!$existingTask) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบงานที่ระบุ'
        ]);
        exit();
    }
    
    // Check permission (Sales can only update their own tasks)
    $userRole = $_SESSION['Role'] ?? '';
    if ($userRole === 'Sale' && $existingTask['CreatedBy'] !== $_SESSION['Username']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'คุณไม่มีสิทธิ์แก้ไขงานนี้'
        ]);
        exit();
    }
    
    // Update task status
    $updateData = [
        'CustomerCode' => $existingTask['CustomerCode'],
        'FollowupDate' => $existingTask['FollowupDate'],
        'Remarks' => $existingTask['Remarks'],
        'Status' => $newStatus
    ];
    
    $result = $task->updateTask($taskId, $updateData, $_SESSION['Username']);
    
    if ($result['success']) {
        // Return updated task info
        $updatedTask = $task->find($taskId);
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'data' => $updatedTask
        ]);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Update task status API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ]);
}
?>