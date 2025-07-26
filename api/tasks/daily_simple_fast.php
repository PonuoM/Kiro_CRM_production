<?php
/**
 * Fast Simple Daily Tasks API
 * Minimal version for debugging
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../includes/permissions.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    // Current date
    $today = date('Y-m-d');
    
    // Check if this is a team request (for admin/supervisor)
    $isTeamRequest = isset($_GET['team']) && $_GET['team'] === '1';
    
    // Simple base permissions
    $baseWhere = '';
    $baseParams = [];
    
    // If team request but user cannot view all data, deny access
    if ($isTeamRequest && !$canViewAll) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized to view team data'
        ]);
        exit;
    }
    
    if (!$canViewAll && !$isTeamRequest) {
        $baseWhere = " AND (c.Sales = ? OR c.CreatedBy = ?)";
        $baseParams = [$currentUser, $currentUser];
    }
    
    // Get ลูกค้าใหม่ (new customers) - SIMPLIFIED
    $newCustomerQuery = "SELECT 
                            CONCAT('customer_', c.CustomerCode) as id,
                            c.CustomerCode,
                            c.CustomerName, 
                            c.CustomerTel, 
                            c.Sales,
                            c.CustomerStatus,
                            c.CreatedDate as FollowupDate,
                            'ติดต่อลูกค้าใหม่' as Remarks,
                            'รอดำเนินการ' as Status,
                            0 as contact_count
                        FROM customers c
                        WHERE c.CustomerStatus = 'ลูกค้าใหม่' {$baseWhere}
                        ORDER BY c.CreatedDate ASC
                        LIMIT 20";
    
    $stmt = $pdo->prepare($newCustomerQuery);
    $stmt->execute($baseParams);
    $newCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get regular tasks - SIMPLIFIED
    $regularTasksQuery = "SELECT 
                            t.id,
                            c.CustomerCode,
                            c.CustomerName, 
                            c.CustomerTel, 
                            c.Sales,
                            c.CustomerStatus,
                            t.FollowupDate,
                            t.Remarks,
                            COALESCE(t.Status, 'รอดำเนินการ') as Status,
                            0 as contact_count
                         FROM tasks t 
                         LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                         WHERE DATE(t.FollowupDate) = ? {$baseWhere}
                         ORDER BY t.FollowupDate ASC
                         LIMIT 20";
    
    $stmt = $pdo->prepare($regularTasksQuery);
    $stmt->execute(array_merge([$today], $baseParams));
    $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine tasks
    $todayTasks = array_merge($newCustomers, $regularTasks);
    
    // Simple stats
    $totalToday = count($todayTasks);
    $completedCount = 0; // Simplified - no complex counting
    
    $summary = [
        'today_count' => $totalToday,
        'overdue_count' => 0,
        'upcoming_count' => 0,
        'total_completed' => $completedCount,
        'completion_rate' => 0
    ];
    
    // Response
    $response = [
        'success' => true,
        'message' => 'Tasks loaded successfully',
        'data' => [
            'summary' => $summary,
            'today' => [
                'tasks' => $todayTasks,
                'count' => count($todayTasks)
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $currentUser,
        'permissions' => [
            'can_view_all' => $canViewAll
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>