<?php
/**
 * Enhanced Daily Tasks API
 * Returns comprehensive daily task data with statistics for daily tasks page
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
    
    // Current date and time
    $today = date('Y-m-d');
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Base query for tasks with permissions
    $baseWhere = '';
    $baseParams = [];
    
    if (!$canViewAll) {
        $baseWhere = " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
        $baseParams = [$currentUser, $currentUser];
    }
    
    // Include CustomerStatusManager for new business logic
    require_once '../../includes/CustomerStatusManager.php';
    $statusManager = new CustomerStatusManager();
    
    // Get today's tasks - combining ลูกค้าใหม่ and regular tasks
    $todayTasks = [];
    
    // First: Get ลูกค้าใหม่ (new customers that need to be contacted)
    $newCustomerQuery = "SELECT 
                            CONCAT('customer_', c.CustomerCode) as id,
                            c.CustomerCode,
                            c.CustomerName, 
                            c.CustomerTel, 
                            c.Sales,
                            c.CustomerStatus,
                            c.CreatedDate,
                            c.LastContactDate,
                            c.CreatedDate as FollowupDate,
                            'ติดต่อลูกค้าใหม่' as Remarks,
                            'ติดต่อลูกค้า' as TaskType,
                            'รอดำเนินการ' as Status,
                            (SELECT COUNT(*) FROM call_logs cl WHERE cl.CustomerCode = c.CustomerCode) as contact_count,
                            (SELECT cl2.TalkStatus FROM call_logs cl2 WHERE cl2.CustomerCode = c.CustomerCode ORDER BY cl2.CallDate DESC LIMIT 1) as last_talk_status
                        FROM customers c
                        WHERE c.CustomerStatus = 'ลูกค้าใหม่' {$baseWhere}
                        ORDER BY c.CreatedDate ASC";
    
    $stmt = $pdo->prepare($newCustomerQuery);
    $stmt->execute($baseParams);
    $newCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Second: Get regular scheduled tasks for today
    $regularTasksQuery = "SELECT 
                            t.id,
                            c.CustomerCode,
                            c.CustomerName, 
                            c.CustomerTel, 
                            c.Sales,
                            c.CustomerStatus,
                            c.CreatedDate,
                            c.LastContactDate,
                            t.FollowupDate,
                            t.Remarks,
                            t.TaskType,
                            t.Status,
                            (SELECT COUNT(*) FROM call_logs cl WHERE cl.CustomerCode = c.CustomerCode) as contact_count,
                            (SELECT cl2.TalkStatus FROM call_logs cl2 WHERE cl2.CustomerCode = c.CustomerCode ORDER BY cl2.CallDate DESC LIMIT 1) as last_talk_status
                         FROM tasks t 
                         LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                         WHERE DATE(t.FollowupDate) = ? {$baseWhere}
                         ORDER BY t.FollowupDate ASC";
    
    $stmt = $pdo->prepare($regularTasksQuery);
    $stmt->execute(array_merge([$today], $baseParams));
    $regularTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine: ลูกค้าใหม่ first, then regular tasks
    $todayTasks = array_merge($newCustomers, $regularTasks);
    
    // Get overdue tasks (before today)
    $overdueQuery = "SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales 
                     FROM tasks t 
                     LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                     WHERE DATE(t.FollowupDate) < ? AND (t.Status != 'เสร็จสิ้น' OR t.Status IS NULL) {$baseWhere}
                     ORDER BY t.FollowupDate ASC";
    
    $stmt = $pdo->prepare($overdueQuery);
    $stmt->execute(array_merge([$today], $baseParams));
    $overdueTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get upcoming tasks (next 7 days)
    $nextWeek = date('Y-m-d', strtotime('+7 days'));
    $upcomingQuery = "SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales 
                      FROM tasks t 
                      LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                      WHERE DATE(t.FollowupDate) > ? AND DATE(t.FollowupDate) <= ? {$baseWhere}
                      ORDER BY t.FollowupDate ASC";
    
    $stmt = $pdo->prepare($upcomingQuery);
    $stmt->execute(array_merge([$today, $nextWeek], $baseParams));
    $upcomingTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completed tasks count for today
    $completedQuery = "SELECT COUNT(*) as total
                       FROM tasks t 
                       LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                       WHERE DATE(t.FollowupDate) = ? AND t.Status = 'เสร็จสิ้น' {$baseWhere}";
    
    $stmt = $pdo->prepare($completedQuery);
    $stmt->execute(array_merge([$today], $baseParams));
    $completedCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate completion rate
    $totalToday = count($todayTasks);
    $completionRate = $totalToday > 0 ? round(($completedCount / $totalToday) * 100, 1) : 0;
    
    // Calculate statistics
    $summary = [
        'today_count' => count($todayTasks),
        'overdue_count' => count($overdueTasks),
        'upcoming_count' => count($upcomingTasks),
        'total_completed' => (int)$completedCount,
        'completion_rate' => $completionRate
    ];
    
    // Format the response as expected by daily-tasks.js
    $response = [
        'success' => true,
        'message' => 'Tasks loaded successfully',
        'data' => [
            'summary' => $summary,
            'today' => [
                'tasks' => $todayTasks,
                'count' => count($todayTasks)
            ],
            'overdue' => [
                'tasks' => $overdueTasks,
                'count' => count($overdueTasks)
            ],
            'upcoming' => [
                'tasks' => $upcomingTasks,
                'count' => count($upcomingTasks)
            ]
        ],
        'timestamp' => $currentDateTime,
        'user' => $currentUser,
        'permissions' => [
            'can_view_all' => $canViewAll
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>