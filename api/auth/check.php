<?php
/**
 * Session Check API Endpoint
 * Checks if user is logged in and returns user info
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    if (isLoggedIn()) {
        sendJsonResponse([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => getCurrentUserId(),
                'username' => getCurrentUsername(),
                'firstName' => $_SESSION['first_name'] ?? '',
                'lastName' => $_SESSION['last_name'] ?? '',
                'role' => getCurrentUserRole(),
                'companyCode' => $_SESSION['company_code'] ?? ''
            ]
        ]);
    } else {
        sendJsonResponse([
            'success' => true,
            'authenticated' => false
        ]);
    }
    
} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการตรวจสอบ session'
    ], 500);
}
?>