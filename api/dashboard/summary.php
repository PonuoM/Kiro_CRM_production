<?php
/**
 * Enhanced Dashboard API with Time Remaining and Customer Temperature
 * Story 3.1: Enhance Dashboard API
 * 
 * Provides both summary statistics and customer list with time_remaining_days
 */
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
    
    $today = date('Y-m-d');
    $thisMonth = date('Y-m');
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    // Get request parameters
    $includeCustomers = isset($_GET['include_customers']) && $_GET['include_customers'] === 'true';
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;
    
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
    
    $response = [
        'status' => 'success',
        'data' => [
            'summary' => $summary,
            'date' => $today
        ],
        'message' => 'Dashboard summary loaded successfully'
    ];
    
    // Include customer list with time remaining if requested
    if ($includeCustomers) {
        $customers = getCustomersWithTimeRemaining($pdo, $canViewAll, $currentUser, $limit, $offset);
        $response['data']['customers'] = $customers['data'];
        $response['data']['pagination'] = $customers['pagination'];
        $response['message'] = 'Dashboard with customer list loaded successfully';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}

/**
 * Get customers with time remaining calculation
 * @param PDO $pdo Database connection
 * @param bool $canViewAll User permission
 * @param string $currentUser Current user identifier
 * @param int $limit Results limit
 * @param int $offset Results offset
 * @return array Customer list with pagination
 */
function getCustomersWithTimeRemaining($pdo, $canViewAll, $currentUser, $limit, $offset) {
    // Build SQL query with time remaining calculation
    $sql = "SELECT 
                CustomerCode,
                CustomerName,
                CustomerTel,
                CustomerStatus,
                CustomerGrade,
                CustomerTemperature,
                Sales,
                AssignDate,
                LastContactDate,
                ContactAttempts,
                CreatedDate,
                -- Time remaining calculation based on customer status and assignment (Fixed calculation)
                CASE 
                    WHEN CustomerStatus = 'ลูกค้าใหม่' AND AssignDate IS NOT NULL THEN 
                        30 - DATEDIFF(CURDATE(), DATE(AssignDate))
                    WHEN CustomerStatus = 'ลูกค้าใหม่' AND AssignDate IS NULL THEN 
                        7 - DATEDIFF(CURDATE(), DATE(CreatedDate)) -- Unassigned new customers expire in 7 days
                    WHEN CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL THEN 
                        14 - DATEDIFF(CURDATE(), DATE(LastContactDate)) -- Follow up customers expire in 14 days from last contact
                    WHEN CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NULL THEN 
                        14 - DATEDIFF(CURDATE(), DATE(COALESCE(AssignDate, CreatedDate)))
                    WHEN CustomerStatus = 'ลูกค้าเก่า' THEN 
                        90 - DATEDIFF(CURDATE(), DATE(COALESCE(LastContactDate, AssignDate, CreatedDate)))
                    ELSE 
                        30 - DATEDIFF(CURDATE(), DATE(COALESCE(AssignDate, CreatedDate)))
                END as time_remaining_days,
                -- Calculate priority based on time remaining and temperature
                CASE 
                    WHEN CustomerTemperature = 'HOT' THEN 1
                    WHEN CustomerTemperature = 'WARM' THEN 2
                    WHEN CustomerTemperature = 'COLD' THEN 3
                    WHEN CustomerTemperature = 'FROZEN' THEN 4
                    ELSE 5
                END as priority_score
            FROM customers WHERE 1=1";
    
    $params = [];
    
    // Add user filter for Sales role - they see only assigned customers
    if (!$canViewAll) {
        $sql .= " AND Sales = ?";
        $params[] = $currentUser;
    }
    
    // Simplified ordering for better performance
    $sql .= " ORDER BY 
                time_remaining_days ASC,
                CustomerTemperature = 'HOT' DESC,
                CreatedDate DESC
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format customer data
    foreach ($customers as &$customer) {
        // Ensure time_remaining_days is integer
        $customer['time_remaining_days'] = (int)$customer['time_remaining_days'];
        
        // Add time status indicator
        if ($customer['time_remaining_days'] <= 0) {
            $customer['time_status'] = 'OVERDUE';
        } elseif ($customer['time_remaining_days'] <= 7) {
            $customer['time_status'] = 'URGENT';
        } elseif ($customer['time_remaining_days'] <= 14) {
            $customer['time_status'] = 'SOON';
        } else {
            $customer['time_status'] = 'NORMAL';
        }
        
        // Remove internal priority score
        unset($customer['priority_score']);
        
        // Format dates - ensure both old and new format fields exist for compatibility
        if ($customer['AssignDate']) {
            $customer['assign_date'] = $customer['AssignDate'];
        } else {
            $customer['assign_date'] = null;
        }
        
        if ($customer['LastContactDate']) {
            $customer['last_contact_date'] = $customer['LastContactDate'];
        } else {
            $customer['last_contact_date'] = null;
        }
        
        if ($customer['CreatedDate']) {
            $customer['created_date'] = $customer['CreatedDate'];
        } else {
            $customer['created_date'] = null;
        }
        
        // Keep original date fields for backward compatibility (don't unset them)
    }
    
    // Get total count for pagination
    $totalSql = "SELECT COUNT(*) as total FROM customers WHERE 1=1";
    $totalParams = [];
    
    if (!$canViewAll) {
        $totalSql .= " AND Sales = ?";
        $totalParams[] = $currentUser;
    }
    
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->execute($totalParams);
    $totalCount = $totalStmt->fetchColumn();
    
    return [
        'data' => $customers,
        'pagination' => [
            'page' => intval($offset / $limit) + 1,
            'limit' => $limit,
            'total' => $totalCount,
            'total_pages' => ceil($totalCount / $limit),
            'count' => count($customers)
        ]
    ];
}
?>