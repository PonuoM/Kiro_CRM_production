<?php
/**
 * Toggle User Status API Endpoint
 * Activates or deactivates a user account
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/User.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check authentication and authorization
requireLogin();
if (!hasRole('Admin')) {
    sendJsonResponse(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }
    
    // Verify CSRF token - Optional for API calls
    $csrf_token = $input['csrf_token'] ?? '';
    if (!empty($csrf_token) && !verifyCSRFToken($csrf_token)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
    }
    
    $userId = intval($input['id'] ?? 0);
    if ($userId <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
    }
    
    // Prevent self-deactivation
    if ($userId == getCurrentUserId()) {
        sendJsonResponse(['success' => false, 'message' => 'ไม่สามารถปิดใช้งานบัญชีของตนเองได้'], 400);
    }
    
    $userModel = new User();
    
    // Check if user exists
    $existingUser = $userModel->find($userId);
    if (!$existingUser) {
        sendJsonResponse(['success' => false, 'message' => 'ไม่พบผู้ใช้'], 404);
    }
    
    $currentStatus = $existingUser['Status'];
    $newStatus = $currentStatus == 1 ? 0 : 1;
    $action = $newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    
    // Update status
    $success = $userModel->update($userId, [
        'Status' => $newStatus,
        'ModifiedDate' => date('Y-m-d H:i:s'),
        'ModifiedBy' => getCurrentUsername()
    ]);
    
    if ($success) {
        // Log activity
        logActivity("User status changed", "User: {$existingUser['Username']} {$action} by " . getCurrentUsername());
        
        sendJsonResponse([
            'success' => true,
            'message' => "{$action}ผู้ใช้สำเร็จ",
            'data' => [
                'id' => $userId,
                'status' => $newStatus,
                'statusText' => $newStatus == 1 ? 'ใช้งาน' : 'ปิดใช้งาน'
            ]
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะผู้ใช้'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Toggle user status error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ], 500);
}
?>