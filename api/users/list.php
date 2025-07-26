<?php
/**
 * Users List API Endpoint
 * Returns list of users with filtering and pagination
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/User.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check authentication and authorization
requireLogin();
if (!hasRole('Admin') && !hasRole('Supervisor')) {
    sendJsonResponse(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง'], 403);
}

try {
    $userModel = new User();
    
    // Get query parameters
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? RECORDS_PER_PAGE)));
    $offset = ($page - 1) * $limit;
    
    // Build conditions
    $conditions = [];
    $searchConditions = '';
    $params = [];
    
    if (!empty($role)) {
        $conditions['Role'] = $role;
    }
    
    if ($status !== '') {
        $conditions['Status'] = intval($status);
    }
    
    // Build SQL query
    $sql = "SELECT id, Username, FirstName, LastName, Email, Phone, CompanyCode, Position, Role, Status, LastLoginDate, CreatedDate FROM users";
    $countSql = "SELECT COUNT(*) as total FROM users";
    
    $whereClause = [];
    
    // Add conditions
    foreach ($conditions as $field => $value) {
        $whereClause[] = "{$field} = ?";
        $params[] = $value;
    }
    
    // Add search condition
    if (!empty($search)) {
        $searchClause = "(Username LIKE ? OR FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?)";
        $whereClause[] = $searchClause;
        $searchTerm = "%{$search}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($whereClause)) {
        $whereString = " WHERE " . implode(' AND ', $whereClause);
        $sql .= $whereString;
        $countSql .= $whereString;
    }
    
    // Get total count
    $totalResult = $userModel->queryOne($countSql, $params);
    $total = $totalResult ? $totalResult['total'] : 0;
    
    // Add ordering and pagination
    $sql .= " ORDER BY FirstName, LastName LIMIT {$limit} OFFSET {$offset}";
    
    // Get users
    $users = $userModel->query($sql, $params);
    
    // Format user data
    $formattedUsers = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['Username'],
            'firstName' => $user['FirstName'],
            'lastName' => $user['LastName'],
            'fullName' => $user['FirstName'] . ' ' . $user['LastName'],
            'email' => $user['Email'],
            'phone' => $user['Phone'],
            'companyCode' => $user['CompanyCode'],
            'position' => $user['Position'],
            'role' => $user['Role'],
            'status' => $user['Status'],
            'statusText' => $user['Status'] == 1 ? 'ใช้งาน' : 'ปิดใช้งาน',
            'lastLoginDate' => $user['LastLoginDate'] ? formatThaiDate($user['LastLoginDate']) : 'ยังไม่เคยเข้าสู่ระบบ',
            'createdDate' => formatThaiDate($user['CreatedDate'])
        ];
    }, $users);
    
    // Calculate pagination info
    $totalPages = ceil($total / $limit);
    
    sendJsonResponse([
        'success' => true,
        'data' => $formattedUsers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
            'hasNext' => $page < $totalPages,
            'hasPrev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Users list error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้'
    ], 500);
}
?>