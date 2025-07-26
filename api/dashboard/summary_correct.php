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
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    
    // Get summary statistics
    $summary = [];
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
    $summary['total_customers'] = $stmt->fetchColumn();
    
    // New customers this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers WHERE DATE_FORMAT(CreatedDate, '%Y-%m') = ?");
    $stmt->execute([$thisMonth]);
    $summary['new_customers_this_month'] = $stmt->fetchColumn();
    
    // Tasks today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE DATE(FollowupDate) = ?");
    $stmt->execute([$today]);
    $summary['tasks_today'] = $stmt->fetchColumn();
    
    // Pending tasks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks WHERE Status = 'รอดำเนินการ'");
    $summary['pending_tasks'] = $stmt->fetchColumn();
    
    // Orders this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE_FORMAT(DocumentDate, '%Y-%m') = ?");
    $stmt->execute([$thisMonth]);
    $summary['orders_this_month'] = $stmt->fetchColumn();
    
    // Revenue this month
    $stmt = $pdo->prepare("SELECT SUM(Price) as revenue FROM orders WHERE DATE_FORMAT(DocumentDate, '%Y-%m') = ?");
    $stmt->execute([$thisMonth]);
    $summary['revenue_this_month'] = $stmt->fetchColumn() ?: 0;
    
    // Customer status breakdown
    $stmt = $pdo->query("SELECT CustomerStatus, COUNT(*) as count FROM customers GROUP BY CustomerStatus");
    $customerStatusData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $summary['customer_status_breakdown'] = $customerStatusData;
    
    echo json_encode([
        'status' => 'success',
        'data' => $summary,
        'date' => $today,
        'message' => 'Dashboard summary loaded successfully'
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