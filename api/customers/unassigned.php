<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../includes/permissions.php';
    
    // Only Admin and Manager can see unassigned customers
    if (!Permissions::hasPermission('view_all_data')) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Access denied. Only Admin and Manager can view unassigned customers.'
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get unassigned customers (Sales is NULL or empty)
    $sql = "SELECT * FROM customers WHERE (Sales IS NULL OR Sales = '') ORDER BY CreatedDate DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available sales users for assignment
    $salesSql = "SELECT username FROM users WHERE role = 'sales' AND status = 'active'";
    $salesStmt = $pdo->prepare($salesSql);
    $salesStmt->execute();
    $salesUsers = $salesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'status' => 'success',
        'data' => $customers,
        'sales_users' => $salesUsers,
        'count' => count($customers),
        'message' => count($customers) === 0 ? 'No unassigned customers found' : 'Unassigned customers loaded successfully'
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>