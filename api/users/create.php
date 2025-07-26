<?php
/**
 * Create User API Endpoint
 * Creates a new user account
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
    
    $userModel = new User();
    
    // Prepare user data
    $userData = [
        'Username' => trim($input['username'] ?? ''),
        'Password' => $input['password'] ?? '',
        'FirstName' => trim($input['firstName'] ?? ''),
        'LastName' => trim($input['lastName'] ?? ''),
        'Email' => trim($input['email'] ?? ''),
        'Phone' => trim($input['phone'] ?? ''),
        'CompanyCode' => trim($input['companyCode'] ?? ''),
        'Position' => trim($input['position'] ?? ''),
        'Role' => $input['role'] ?? ''
    ];
    
    // Validate user data
    $errors = $userModel->validateUserData($userData, false);
    
    if (!empty($errors)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $errors
        ], 400);
    }
    
    // Create user
    $userId = $userModel->createUser($userData);
    
    if ($userId) {
        // Log activity
        logActivity("User created", "New user: {$userData['Username']} by " . getCurrentUsername());
        
        sendJsonResponse([
            'success' => true,
            'message' => 'สร้างผู้ใช้สำเร็จ',
            'data' => [
                'id' => $userId,
                'username' => $userData['Username']
            ]
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการสร้างผู้ใช้'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Create user error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ], 500);
}
?>