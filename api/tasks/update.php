<?php
/**
 * Update Task API Endpoint
 * PUT /api/tasks/update.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Allow both PUT and POST requests
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
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
    
    // Validate task ID
    if (empty($input['id']) || !is_numeric($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'รหัสงานไม่ถูกต้อง'
        ]);
        exit();
    }
    
    $taskId = (int)$input['id'];
    
    // Create task instance
    $task = new Task();
    
    // Check if task exists and user has permission to update
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
    
    // Prepare update data
    $updateData = [];
    
    if (isset($input['CustomerCode'])) {
        $updateData['CustomerCode'] = trim($input['CustomerCode']);
    }
    
    if (isset($input['FollowupDate'])) {
        $updateData['FollowupDate'] = trim($input['FollowupDate']);
    }
    
    if (isset($input['Remarks'])) {
        $updateData['Remarks'] = !empty($input['Remarks']) ? trim($input['Remarks']) : null;
    }
    
    if (isset($input['Status'])) {
        $updateData['Status'] = trim($input['Status']);
    }
    
    // If no data to update
    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ไม่มีข้อมูลที่ต้องอัปเดต'
        ]);
        exit();
    }
    
    // Merge with existing data for validation
    $fullData = array_merge([
        'CustomerCode' => $existingTask['CustomerCode'],
        'FollowupDate' => $existingTask['FollowupDate'],
        'Remarks' => $existingTask['Remarks'],
        'Status' => $existingTask['Status']
    ], $updateData);
    
    // Update task
    $result = $task->updateTask($taskId, $fullData, $_SESSION['Username']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Update task API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ]);
}
?>