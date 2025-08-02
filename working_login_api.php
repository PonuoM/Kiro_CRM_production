<?php
/**
 * Working Login API 
 * ใช้ database connection ที่ทำงานแน่นอน
 */

// เริ่ม session ก่อนทุกอย่าง
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เปิด error reporting เพื่อ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Clear any previous output
ob_clean();

// Simple function definitions
function sendJsonResponse($data, $status_code = 200) {
    if (ob_get_level()) ob_clean();
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = json_encode(['success' => false, 'message' => 'JSON encoding error']);
    }
    echo $json;
    exit();
}

function simpleVerifyPassword($password, $hash) {
    // ลองหลายวิธี
    if ($password === $hash) return true; // Plain text
    if (md5($password) === $hash) return true; // MD5
    if (sha1($password) === $hash) return true; // SHA1
    if (password_verify($password, $hash)) return true; // PHP password_hash
    return false;
}

try {
    // Allow both GET and POST for testing
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        sendJsonResponse([
            'success' => false, 
            'message' => 'This is the Working Login API endpoint. Use POST method with JSON data.',
            'debug' => [
                'method' => $_SERVER['REQUEST_METHOD'],
                'available_users' => ['admin', 'sales01', 'supervisor', 'manager'],
                'instructions' => 'Send POST request with JSON: {"username":"admin","password":"your_password","csrf_token":""}'
            ]
        ], 200);
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
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
    
    // Debug logging
    error_log("Working Login - Username: $username, Session: " . session_id());
    
    // ใช้ database connection เดียวกับที่ working
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // ใช้ query ที่ทดสอบแล้วว่าทำงาน (จาก check_users_table.php)
    $sql = "SELECT id, Username, Password, Role, Status, 
                   CONCAT(IFNULL(FirstName, ''), ' ', IFNULL(LastName, '')) as FullName,
                   FirstName, LastName, CompanyCode
            FROM users WHERE Username = ? AND Status = 1 LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // ทดสอบ password แบบ simple
        if (simpleVerifyPassword($password, $user['Password'])) {
            
            // อัพเดท last login
            try {
                $pdo->prepare("UPDATE users SET LastLoginDate = NOW() WHERE id = ?")->execute([$user['id']]);
            } catch (Exception $e) {
                // ไม่สำคัญถ้า update ไม่ได้
                error_log("LastLogin update failed: " . $e->getMessage());
            }
            
            // ตั้ง session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['user_role'] = $user['Role'];
            $_SESSION['user_login'] = $user['Username'];
            $_SESSION['first_name'] = $user['FirstName'] ?? '';
            $_SESSION['last_name'] = $user['LastName'] ?? '';
            $_SESSION['company_code'] = $user['CompanyCode'] ?? '';
            
            // Log
            error_log("Login Success - User: $username, ID: {$user['id']}");
            
            sendJsonResponse([
                'success' => true,
                'message' => 'เข้าสู่ระบบสำเร็จ',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['Username'],
                    'fullName' => $user['FullName'],
                    'firstName' => $user['FirstName'] ?? '',
                    'lastName' => $user['LastName'] ?? '',
                    'role' => $user['Role'],
                    'companyCode' => $user['CompanyCode'] ?? ''
                ],
                'redirect' => 'dashboard.php',
                'debug' => [
                    'session_id' => session_id(),
                    'login_method' => 'working_api',
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);
            
        } else {
            error_log("Login Failed - Wrong Password: $username");
            sendJsonResponse([
                'success' => false,
                'message' => 'Username หรือ Password ไม่ถูกต้อง',
                'debug' => [
                    'user_found' => true,
                    'password_match' => false,
                    'username_tried' => $username
                ]
            ], 401);
        }
        
    } else {
        error_log("Login Failed - User Not Found: $username");
        
        // แสดง users ที่มีอยู่สำหรับ debug
        $available_users = $pdo->query("SELECT Username FROM users WHERE Status = 1 LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        
        sendJsonResponse([
            'success' => false,
            'message' => 'ไม่พบ Username ในระบบ หรือ User ถูก disable',
            'debug' => [
                'user_found' => false,
                'username_tried' => $username,
                'available_users' => $available_users,
                'hint' => 'ลองใช้ username: ' . implode(', ', $available_users)
            ]
        ], 401);
    }
    
} catch (Exception $e) {
    // Clear output and log error
    ob_clean();
    
    error_log("Working Login Error: " . $e->getMessage());
    error_log("Error File: " . $e->getFile());
    error_log("Error Line: " . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], 500);
}
?>