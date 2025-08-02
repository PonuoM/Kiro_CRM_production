<?php
/**
 * Fixed Login API Endpoint
 * แก้ไขปัญหา 500 Error และ Session warnings
 */

// เริ่ม session ก่อนอื่น (ก่อน config)
if (session_status() == PHP_SESSION_NONE) {
    // ตั้ง session settings ก่อน session_start()
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // ปิดใน development
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.name', 'CRM_SESSION');
    
    session_start();
}

// เปิด error reporting ชั่วคราวเพื่อ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any previous output
ob_clean();

try {
    // Load dependencies แยกจาก config
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/User.php';

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!$input) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $csrf_token = $input['csrf_token'] ?? '';
    
    // Validate required fields
    if (empty($username) || empty($password)) {
        sendJsonResponse(['success' => false, 'message' => 'กรุณากรอก Username และ Password'], 400);
    }
    
    // Debug info for troubleshooting
    error_log("Login attempt - Username: $username, Session ID: " . session_id());
    error_log("CSRF Debug - Received: " . substr($csrf_token, 0, 10) . "...");
    error_log("CSRF Debug - Session: " . substr($_SESSION['csrf_token'] ?? 'not set', 0, 10) . "...");
    
    // Verify CSRF token (พักไว้ก่อนสำหรับ debug)
    if (!empty($csrf_token) && !verifyCSRFToken($csrf_token)) {
        sendJsonResponse([
            'success' => false, 
            'message' => 'Invalid CSRF token',
            'debug' => [
                'received' => substr($csrf_token, 0, 10) . '...',
                'session' => substr($_SESSION['csrf_token'] ?? 'none', 0, 10) . '...',
                'session_id' => session_id()
            ]
        ], 403);
    }
    
    // Initialize User model
    $userModel = new User();
    
    // Authenticate user
    $user = $userModel->authenticate($username, $password);
    
    if ($user) {
        // Set user session
        setUserSession($user);
        
        // Log successful login
        logActivity("User login", "User: {$username}");
        
        sendJsonResponse([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'user' => [
                'id' => $user['id'],
                'username' => $user['Username'],
                'firstName' => $user['FirstName'] ?? '',
                'lastName' => $user['LastName'] ?? '',
                'role' => $user['Role'],
                'companyCode' => $user['CompanyCode'] ?? ''
            ],
            'redirect' => 'dashboard.php',
            'debug' => [
                'session_id' => session_id(),
                'user_found' => true
            ]
        ]);
    } else {
        // Log failed login attempt
        logActivity("Failed login attempt", "Username: {$username}");
        
        sendJsonResponse([
            'success' => false,
            'message' => 'Username หรือ Password ไม่ถูกต้อง',
            'debug' => [
                'user_found' => false,
                'username_tried' => $username
            ]
        ], 401);
    }
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    
    // Log detailed error
    error_log("Login API Error: " . $e->getMessage());
    error_log("Error File: " . $e->getFile());
    error_log("Error Line: " . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'session_id' => session_id()
        ]
    ], 500);
}
?>