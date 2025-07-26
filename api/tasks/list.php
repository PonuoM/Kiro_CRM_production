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
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    // Get date filter if provided
    $dateFilter = $_GET['Date'] ?? null;
    
    // Build query with permission filters
    $sql = "SELECT t.*, c.CustomerName, c.CustomerTel, c.CustomerStatus, c.Sales 
            FROM tasks t 
            LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
            WHERE 1=1";
    
    $params = [];
    
    // Add date filter if provided
    if ($dateFilter) {
        $sql .= " AND DATE(t.FollowupDate) = ?";
        $params[] = $dateFilter;
    }
    
    // Add user filter for Sales role
    if (!$canViewAll) {
        $sql .= " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
        $params[] = $currentUser;
        $params[] = $currentUser;
    }
    
    $sql .= " ORDER BY t.FollowupDate DESC LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $tasks,
        'count' => count($tasks),
        'message' => count($tasks) === 0 ? 'No tasks found' : 'All tasks loaded successfully'
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