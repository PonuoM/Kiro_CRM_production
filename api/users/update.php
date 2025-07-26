<?php
/**
 * Update User API Endpoint
 * Updates an existing user account
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/User.php';

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
    
    $userModel = new User();
    
    // Check if user exists
    $existingUser = $userModel->find($userId);
    if (!$existingUser) {
        sendJsonResponse(['success' => false, 'message' => 'ไม่พบผู้ใช้'], 404);
    }
    
    // Prepare user data
    $userData = [
        'id' => $userId,
        'Username' => trim($input['username'] ?? ''),
        'FirstName' => trim($input['firstName'] ?? ''),
        'LastName' => trim($input['lastName'] ?? ''),
        'Email' => trim($input['email'] ?? ''),
        'Phone' => trim($input['phone'] ?? ''),
        'CompanyCode' => trim($input['companyCode'] ?? ''),
        'Position' => trim($input['position'] ?? ''),
        'Role' => $input['role'] ?? ''
    ];
    
    // Add password if provided
    if (!empty($input['password'])) {
        $userData['Password'] = $input['password'];
    }
    
    // Validate user data
    $errors = $userModel->validateUserData($userData, true);
    
    if (!empty($errors)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $errors
        ], 400);
    }
    
    // Remove id from update data
    unset($userData['id']);
    
    // Update user
    $success = $userModel->updateUser($userId, $userData);
    
    if ($success) {
        // Log activity
        logActivity("User updated", "User: {$userData['Username']} updated by " . getCurrentUsername());
        
        sendJsonResponse([
            'success' => true,
            'message' => 'อัปเดตผู้ใช้สำเร็จ'
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการอัปเดตผู้ใช้'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Update user error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ], 500);
}
?>