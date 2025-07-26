<?php
/**
 * User Detail API Endpoint
 * Returns detailed information about a specific user
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/User.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check authentication and authorization
requireLogin();
if (!hasRole('Admin') && !hasRole('Supervisor')) {
    sendJsonResponse(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
}

try {
    $userId = intval($_GET['id'] ?? 0);
    
    if ($userId <= 0) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
    }
    
    $userModel = new User();
    
    // Get user data
    $user = $userModel->find($userId);
    
    if (!$user) {
        sendJsonResponse(['success' => false, 'message' => 'ไม่พบผู้ใช้'], 404);
    }
    
    // Format user data (exclude password)
    $userData = [
        'id' => $user['id'],
        'username' => $user['Username'],
        'firstName' => $user['FirstName'],
        'lastName' => $user['LastName'],
        'fullName' => $user['FirstName'] . ' ' . $user['LastName'],
        'email' => $user['Email'],
        'phone' => $user['Phone'],
        'companyCode' => $user['CompanyCode'],
        'position' => $user['Position'],
        'role' => $user['Role'],
        'status' => $user['Status'],
        'statusText' => $user['Status'] == 1 ? 'ใช้งาน' : 'ปิดใช้งาน',
        'lastLoginDate' => $user['LastLoginDate'] ? formatThaiDate($user['LastLoginDate']) : 'ยังไม่เคยเข้าสู่ระบบ',
        'createdDate' => formatThaiDate($user['CreatedDate']),
        'createdBy' => $user['CreatedBy'],
        'modifiedDate' => $user['ModifiedDate'] ? formatThaiDate($user['ModifiedDate']) : null,
        'modifiedBy' => $user['ModifiedBy']
    ];
    
    sendJsonResponse([
        'success' => true,
        'data' => $userData
    ]);
    
} catch (Exception $e) {
    error_log("User detail error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้'
    ], 500);
}
?>