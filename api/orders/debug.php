<?php
/**
 * Debug endpoint for order creation issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    echo json_encode([
        'success' => true,
        'message' => 'Debug endpoint working',
        'data' => [
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'unknown',
            'post_data' => file_get_contents('php://input'),
            'session_started' => session_status() === PHP_SESSION_ACTIVE,
            'includes_exist' => [
                'functions.php' => file_exists(__DIR__ . '/../../includes/functions.php'),
                'Order.php' => file_exists(__DIR__ . '/../../includes/Order.php'),
                'BaseModel.php' => file_exists(__DIR__ . '/../../includes/BaseModel.php')
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Debug error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>