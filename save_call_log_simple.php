<?php
/**
 * Save Call Log - Simple Version  
 * บันทึกข้อมูลการโทรแบบง่าย
 */

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: simple_call_history_test.php');
    exit;
}

$current_user = $_SESSION['username'] ?? 'unknown';
$customer_code = $_POST['customer_code'] ?? '';

try {
    // Database connection
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Validate customer exists
    $stmt = $pdo->prepare("SELECT CustomerCode FROM customers WHERE CustomerCode = ?");
    $stmt->execute([$customer_code]);
    if (!$stmt->fetch()) {
        throw new Exception("ไม่พบข้อมูลลูกค้า: " . $customer_code);
    }
    
    // Prepare call log data
    $call_data = [
        'CustomerCode' => $customer_code,
        'CallDate' => date('Y-m-d H:i:s'),
        'CallStatus' => $_POST['call_status'] ?? '',
        'TalkStatus' => !empty($_POST['talk_status']) ? $_POST['talk_status'] : null,
        'CallMinutes' => !empty($_POST['call_minutes']) ? (int)$_POST['call_minutes'] : null,
        'Remarks' => !empty($_POST['remarks']) ? $_POST['remarks'] : null,
        'CreatedBy' => $current_user,
        'CreatedDate' => date('Y-m-d H:i:s')
    ];
    
    // Validate required fields
    if (empty($call_data['CustomerCode']) || empty($call_data['CallStatus'])) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็น");
    }
    
    // Insert call log
    $sql = "INSERT INTO call_logs (CustomerCode, CallDate, CallStatus, TalkStatus, CallMinutes, Remarks, CreatedBy, CreatedDate) 
            VALUES (:CustomerCode, :CallDate, :CallStatus, :TalkStatus, :CallMinutes, :Remarks, :CreatedBy, :CreatedDate)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($call_data);
    
    if ($stmt->rowCount() > 0) {
        // Success - redirect back with success message
        $redirect_url = "debug_call_history_simple.php?customer=" . urlencode($customer_code) . "&success=1";
        header("Location: $redirect_url");
        exit;
    } else {
        throw new Exception("ไม่สามารถบันทึกข้อมูลได้");
    }
    
} catch (Exception $e) {
    // Error - redirect back with error message
    $error_msg = urlencode($e->getMessage());
    $redirect_url = "debug_call_history_simple.php?customer=" . urlencode($customer_code) . "&error=" . $error_msg;
    header("Location: $redirect_url");
    exit;
}
?>