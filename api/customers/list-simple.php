<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    require_once '../../config/database.php';
    require_once '../../includes/permissions.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get parameters
    $customerStatus = $_GET['customer_status'] ?? 'all';
    $unassigned = isset($_GET['unassigned']) && $_GET['unassigned'] === 'true';
    $search = $_GET['search'] ?? '';
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    // Build query
    $sql = "SELECT 
                CustomerCode,
                CustomerName,
                CustomerTel,
                CustomerStatus,
                CustomerGrade,
                CustomerTemperature,
                TotalPurchase,
                LastContactDate,
                Sales,
                CreatedDate,
                CustomerProvince,
                ModifiedDate
            FROM customers WHERE 1=1";
    $params = [];
    
    // Add unassigned filter (for admin/manager only)
    if ($unassigned && $canViewAll) {
        $sql .= " AND (Sales IS NULL OR Sales = '')";
    }
    // Add status filter if specified
    elseif ($customerStatus !== 'all') {
        // Decode URL encoded Thai text
        $customerStatus = urldecode($customerStatus);
        $sql .= " AND CustomerStatus = ?";
        $params[] = $customerStatus;
    }
    
    // Add search filter if specified
    if (!empty($search)) {
        $sql .= " AND (CustomerName LIKE ? OR CustomerCode LIKE ? OR CustomerTel LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add user filter for Sales role - they see only assigned customers
    if (!$canViewAll) {
        $sql .= " AND Sales = ?";
        $params[] = $currentUser;
    }
    
    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    
    $sql .= " ORDER BY 
                CASE CustomerGrade WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                CASE CustomerTemperature WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                CreatedDate DESC 
            LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for summary
    $totalSql = "SELECT COUNT(*) as total FROM customers WHERE 1=1";
    $totalParams = [];
    
    // Apply same filters for total count
    if ($unassigned && $canViewAll) {
        $totalSql .= " AND (Sales IS NULL OR Sales = '')";
    } elseif ($customerStatus !== 'all') {
        $totalSql .= " AND CustomerStatus = ?";
        $totalParams[] = $customerStatus;
    }
    
    if (!empty($search)) {
        $totalSql .= " AND (CustomerName LIKE ? OR CustomerCode LIKE ? OR CustomerTel LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $totalParams[] = $searchTerm;
        $totalParams[] = $searchTerm;
        $totalParams[] = $searchTerm;
    }
    
    // Apply user filter for total count
    if (!$canViewAll) {
        $totalSql .= " AND Sales = ?";
        $totalParams[] = $currentUser;
    }
    
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->execute($totalParams);
    $totalCount = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'status' => 'success',
        'data' => $customers,
        'count' => count($customers),
        'total' => $totalCount,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit)
        ],
        'filters' => [
            'status' => $customerStatus,
            'unassigned' => $unassigned,
            'search' => $search
        ],
        'message' => count($customers) === 0 ? 'No customers found' : 'Customers loaded successfully'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>