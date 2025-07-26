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
    
    // Get today's date
    $today = date('Y-m-d');
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
    $offset = ($page - 1) * $limit;
    
    // Build query based on role permissions
    $sql = "SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales 
            FROM tasks t 
            LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
            WHERE DATE(t.FollowupDate) = ?";
    
    $params = [$today];
    
    // Add user filter for Sales role
    if (!$canViewAll) {
        $sql .= " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
        $params[] = $currentUser;
        $params[] = $currentUser;
    }
    
    $sql .= " ORDER BY t.FollowupDate ASC LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM tasks t 
                 LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                 WHERE DATE(t.FollowupDate) = ?";
    
    $countParams = [$today];
    
    if (!$canViewAll) {
        $countSql .= " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
        $countParams[] = $currentUser;
        $countParams[] = $currentUser;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalTasks = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // If no tasks today, get next few days with pagination
    if (empty($tasks)) {
        $sql = "SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales 
                FROM tasks t 
                LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                WHERE t.FollowupDate >= ?";
        
        $nextParams = [$today];
        
        // Add user filter for Sales role
        if (!$canViewAll) {
            $sql .= " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
            $nextParams[] = $currentUser;
            $nextParams[] = $currentUser;
        }
        
        $sql .= " ORDER BY t.FollowupDate ASC LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($nextParams);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for upcoming tasks
        $countSql = "SELECT COUNT(*) as total 
                     FROM tasks t 
                     LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                     WHERE t.FollowupDate >= ?";
        
        $countParams = [$today];
        
        if (!$canViewAll) {
            $countSql .= " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
            $countParams[] = $currentUser;
            $countParams[] = $currentUser;
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalTasks = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    $totalPages = ceil($totalTasks / $limit);
    
    echo json_encode([
        'status' => 'success',
        'data' => $tasks,
        'count' => count($tasks),
        'total' => $totalTasks,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1,
        'date' => $today,
        'message' => count($tasks) === 0 ? 'No tasks found' : 'Tasks loaded successfully'
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