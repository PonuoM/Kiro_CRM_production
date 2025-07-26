<?php
/**
 * Login API Endpoint
 * Handles user authentication
 */

// Disable error display for API endpoints
ini_set('display_errors', 0);

// Start output buffering to prevent any unwanted output
ob_start();

// Start session first before anything else
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Clear any previous output
ob_clean();

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/User.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
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
    
    // Debug CSRF token (remove in production)
    error_log("CSRF Debug - Received: " . $csrf_token);
    error_log("CSRF Debug - Session: " . ($_SESSION['csrf_token'] ?? 'not set'));
    error_log("CSRF Debug - Session ID: " . session_id());
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token - Debug: received[' . substr($csrf_token, 0, 10) . '...] vs session[' . substr($_SESSION['csrf_token'] ?? 'none', 0, 10) . '...]'], 403);
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
                'firstName' => $user['FirstName'],
                'lastName' => $user['LastName'],
                'role' => $user['Role'],
                'companyCode' => $user['CompanyCode']
            ],
            'redirect' => 'dashboard.php'
        ]);
    } else {
        // Log failed login attempt
        logActivity("Failed login attempt", "Username: {$username}");
        
        sendJsonResponse([
            'success' => false,
            'message' => 'Username หรือ Password ไม่ถูกต้อง'
        ], 401);
    }
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    ob_clean();
    error_log("Login error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], 500);
}
?>