<?php
/**
 * Distribution Basket API
 * SuperAdmin/Admin feature for distributing leads to Sales users
 * Phase 2: SuperAdmin Role and Admin Workflows
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication and permissions
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/permissions.php';

// Check distribution basket permission
if (!Permissions::hasPermission('distribution_basket')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Distribution basket permission required.']);
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $action);
            break;
        case 'POST':
            handlePostRequest($pdo, $action);
            break;
        case 'PUT':
            handlePutRequest($pdo, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}

function handleGetRequest($pdo, $action) {
    switch ($action) {
        case 'pending':
            getPendingCustomers($pdo);
            break;
        case 'unassigned':
            getUnassignedCustomers($pdo);
            break;
        case 'sales_users':
            getSalesUsers($pdo);
            break;
        case 'assignment_stats':
            getAssignmentStats($pdo);
            break;
        case 'recent_assignments':
            getRecentAssignments($pdo);
            break;
        default:
            getDistributionDashboard($pdo);
    }
}

function handlePostRequest($pdo, $action) {
    switch ($action) {
        case 'assign':
            assignCustomers($pdo);
            break;
        case 'bulk_assign':
            bulkAssignCustomers($pdo);
            break;
        case 'auto_distribute':
            autoDistributeCustomers($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($pdo, $action) {
    switch ($action) {
        case 'reassign':
            reassignCustomer($pdo);
            break;
        case 'move_to_waiting':
            moveToWaitingBasket($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getDistributionDashboard($pdo) {
    try {
        // Get summary statistics
        $stats = [];
        
        // Customers in distribution basket ready to be assigned
        $unassignedSql = "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้าแจก'";
        $stmt = $pdo->prepare($unassignedSql);
        $stmt->execute();
        $stats['unassigned'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Active sales users
        $salesSql = "SELECT COUNT(*) as count FROM users WHERE Role = 'Sales' AND Status = 1";
        $stmt = $pdo->prepare($salesSql);
        $stmt->execute();
        $stats['active_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // HOT customers in distribution basket
        $hotSql = "SELECT COUNT(*) as count FROM customers 
                   WHERE CartStatus = 'ตะกร้าแจก' 
                   AND COALESCE(CustomerTemperature, 'WARM') = 'HOT'";
        $stmt = $pdo->prepare($hotSql);
        $stmt->execute();
        $stats['hot_unassigned'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Grade A customers in distribution basket
        $gradeASql = "SELECT COUNT(*) as count FROM customers 
                      WHERE CartStatus = 'ตะกร้าแจก' 
                      AND COALESCE(CustomerGrade, 'D') = 'A'";
        $stmt = $pdo->prepare($gradeASql);
        $stmt->execute();
        $stats['grade_a_unassigned'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Recent assignment activity (last 7 days) - customers moved to assigned status
        $recentSql = "SELECT COUNT(*) as count FROM customers 
                      WHERE CartStatus = 'ลูกค้าแจกแล้ว' 
                      AND ModifiedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $pdo->prepare($recentSql);
        $stmt->execute();
        $stats['recent_assignments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'message' => 'Distribution dashboard loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getUnassignedCustomers($pdo) {
    try {
        $grade = $_GET['grade'] ?? '';
        $temperature = $_GET['temperature'] ?? '';
        $limit = min(($_GET['limit'] ?? 50), 200); // Max 200 records
        
        $sql = "SELECT 
                    CustomerCode,
                    CustomerName,
                    CustomerTel,
                    CustomerStatus,
                    COALESCE(CustomerGrade, 'D') as CustomerGrade,
                    COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                    COALESCE(TotalPurchase, 0) as TotalPurchase,
                    LastContactDate,
                    COALESCE(ContactAttempts, 0) as ContactAttempts,
                    CreatedDate,
                    ModifiedDate
                FROM customers 
                WHERE CartStatus = 'ตะกร้าแจก'";
        
        $params = [];
        
        // Add filters
        if (!empty($grade) && in_array($grade, ['A', 'B', 'C', 'D'])) {
            $sql .= " AND COALESCE(CustomerGrade, 'D') = ?";
            $params[] = $grade;
        }
        
        if (!empty($temperature) && in_array($temperature, ['HOT', 'WARM', 'COLD'])) {
            $sql .= " AND COALESCE(CustomerTemperature, 'WARM') = ?";
            $params[] = $temperature;
        }
        
        // Order by priority: Grade A first, then HOT temperature, then latest
        $sql .= " ORDER BY 
                    CASE COALESCE(CustomerGrade, 'D') WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                    CASE COALESCE(CustomerTemperature, 'WARM') WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                    CreatedDate DESC
                  LIMIT ?";
        $params[] = $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $customers,
            'count' => count($customers),
            'filters' => [
                'grade' => $grade,
                'temperature' => $temperature,
                'limit' => $limit
            ],
            'message' => 'Unassigned customers loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getSalesUsers($pdo) {
    try {
        $sql = "SELECT 
                    Username as username,
                    FirstName as first_name,
                    LastName as last_name,
                    Email as email,
                    Status as status,
                    CreatedDate as created_at,
                    (SELECT COUNT(*) FROM customers WHERE Sales = users.Username) as assigned_customers,
                    (SELECT COUNT(*) FROM customers WHERE Sales = users.Username AND COALESCE(CustomerGrade, 'D') = 'A') as grade_a_customers,
                    (SELECT COUNT(*) FROM customers WHERE Sales = users.Username AND COALESCE(CustomerTemperature, 'WARM') = 'HOT') as hot_customers
                FROM users 
                WHERE Role = 'Sales' AND Status = 1
                ORDER BY assigned_customers ASC, Username";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $salesUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $salesUsers,
            'count' => count($salesUsers),
            'message' => 'Sales users loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function assignCustomers($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCodes = $input['customer_codes'] ?? [];
    $salesUsername = $input['sales_username'] ?? '';
    $assignedBy = Permissions::getCurrentUser();
    
    if (empty($customerCodes) || empty($salesUsername)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer codes and sales username required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Verify sales user exists and is active
        $userCheckSql = "SELECT Username FROM users WHERE Username = ? AND Role = 'Sales' AND Status = 1";
        $userStmt = $pdo->prepare($userCheckSql);
        $userStmt->execute([$salesUsername]);
        if (!$userStmt->fetch()) {
            throw new Exception('Invalid or inactive sales user');
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($customerCodes as $customerCode) {
            try {
                // Check if customer exists and is in distribution basket
                $checkSql = "SELECT CustomerCode FROM customers WHERE CustomerCode = ? AND CartStatus = 'ตะกร้าแจก'";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$customerCode]);
                
                if ($checkStmt->fetch()) {
                    // Assign customer and update CartStatus
                    $assignSql = "UPDATE customers SET 
                                     Sales = ?,
                                     CartStatus = 'ลูกค้าแจกแล้ว',
                                     ModifiedDate = NOW()
                                  WHERE CustomerCode = ?";
                    $assignStmt = $pdo->prepare($assignSql);
                    $assignStmt->execute([$salesUsername, $customerCode]);
                    
                    $successCount++;
                } else {
                    $errors[] = "Customer {$customerCode} not found in distribution basket";
                }
                
            } catch (Exception $e) {
                $errors[] = "Customer {$customerCode}: " . $e->getMessage();
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'assigned_count' => $successCount,
                'total_requested' => count($customerCodes),
                'sales_username' => $salesUsername,
                'assigned_by' => $assignedBy,
                'errors' => $errors
            ],
            'message' => "Successfully assigned {$successCount} customers to {$salesUsername}"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Assignment failed: ' . $e->getMessage()]);
    }
}

function bulkAssignCustomers($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $assignmentRules = $input['assignment_rules'] ?? [];
    $assignedBy = Permissions::getCurrentUser();
    
    if (empty($assignmentRules)) {
        http_response_code(400);
        echo json_encode(['error' => 'Assignment rules required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $totalAssigned = 0;
        $results = [];
        
        foreach ($assignmentRules as $rule) {
            $grade = $rule['grade'] ?? '';
            $temperature = $rule['temperature'] ?? '';
            $salesUsername = $rule['sales_username'] ?? '';
            $maxCount = min(($rule['max_count'] ?? 10), 50); // Max 50 per rule
            
            if (empty($salesUsername)) {
                $results[] = [
                    'rule' => $rule,
                    'assigned_count' => 0,
                    'error' => 'Sales username required'
                ];
                continue;
            }
            
            // Verify sales user
            $userCheckSql = "SELECT Username FROM users WHERE Username = ? AND Role = 'Sales' AND Status = 1";
            $userStmt = $pdo->prepare($userCheckSql);
            $userStmt->execute([$salesUsername]);
            if (!$userStmt->fetch()) {
                $results[] = [
                    'rule' => $rule,
                    'assigned_count' => 0,
                    'error' => 'Invalid or inactive sales user'
                ];
                continue;
            }
            
            // Build query for this rule - only from distribution basket
            $sql = "SELECT CustomerCode FROM customers 
                    WHERE CartStatus = 'ตะกร้าแจก'";
            $params = [];
            
            if (!empty($grade) && in_array($grade, ['A', 'B', 'C', 'D'])) {
                $sql .= " AND COALESCE(CustomerGrade, 'D') = ?";
                $params[] = $grade;
            }
            
            if (!empty($temperature) && in_array($temperature, ['HOT', 'WARM', 'COLD'])) {
                $sql .= " AND COALESCE(CustomerTemperature, 'WARM') = ?";
                $params[] = $temperature;
            }
            
            $sql .= " ORDER BY 
                        CASE COALESCE(CustomerGrade, 'D') WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                        CASE COALESCE(CustomerTemperature, 'WARM') WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                        CreatedDate ASC
                      LIMIT ?";
            $params[] = $maxCount;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Assign customers and update CartStatus
            $assignedCount = 0;
            foreach ($customers as $customerCode) {
                $assignSql = "UPDATE customers SET 
                                 Sales = ?,
                                 CartStatus = 'ลูกค้าแจกแล้ว',
                                 ModifiedDate = NOW()
                              WHERE CustomerCode = ? AND CartStatus = 'ตะกร้าแจก'";
                $assignStmt = $pdo->prepare($assignSql);
                if ($assignStmt->execute([$salesUsername, $customerCode])) {
                    $assignedCount++;
                }
            }
            
            $totalAssigned += $assignedCount;
            $results[] = [
                'rule' => $rule,
                'assigned_count' => $assignedCount,
                'available_count' => count($customers)
            ];
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_assigned' => $totalAssigned,
                'rules_processed' => count($assignmentRules),
                'results' => $results,
                'assigned_by' => $assignedBy
            ],
            'message' => "Bulk assignment completed. {$totalAssigned} customers assigned."
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Bulk assignment failed: ' . $e->getMessage()]);
    }
}

function autoDistributeCustomers($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $maxPerSales = min(($input['max_per_sales'] ?? 20), 50); // Max 50 per sales user
    $prioritizeHot = $input['prioritize_hot'] ?? true;
    $assignedBy = Permissions::getCurrentUser();
    
    try {
        $pdo->beginTransaction();
        
        // Get active sales users with current workload
        $salesSql = "SELECT 
                        Username as username,
                        (SELECT COUNT(*) FROM customers WHERE Sales = users.Username) as current_load
                     FROM users 
                     WHERE Role = 'Sales' AND Status = 1
                     ORDER BY current_load ASC";
        
        $salesStmt = $pdo->prepare($salesSql);
        $salesStmt->execute();
        $salesUsers = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($salesUsers)) {
            throw new Exception('No active sales users available');
        }
        
        // Get customers from distribution basket (prioritize HOT and Grade A)
        $customersSql = "SELECT CustomerCode FROM customers 
                         WHERE CartStatus = 'ตะกร้าแจก'
                         ORDER BY 
                             CASE COALESCE(CustomerGrade, 'D') WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                             " . ($prioritizeHot ? "CASE COALESCE(CustomerTemperature, 'WARM') WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END," : "") . "
                             CreatedDate ASC";
        
        $customersStmt = $pdo->prepare($customersSql);
        $customersStmt->execute();
        $customers = $customersStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $totalAssigned = 0;
        $salesIndex = 0;
        $assignments = [];
        
        foreach ($customers as $customerCode) {
            // Find next available sales user
            $attempts = 0;
            while ($attempts < count($salesUsers)) {
                $salesUser = $salesUsers[$salesIndex];
                
                // Check if this sales user hasn't reached the limit
                if (!isset($assignments[$salesUser['username']])) {
                    $assignments[$salesUser['username']] = 0;
                }
                
                if ($assignments[$salesUser['username']] < $maxPerSales) {
                    // Assign customer and update CartStatus
                    $assignSql = "UPDATE customers SET 
                                     Sales = ?,
                                     CartStatus = 'ลูกค้าแจกแล้ว',
                                     ModifiedDate = NOW()
                                  WHERE CustomerCode = ? AND CartStatus = 'ตะกร้าแจก'";
                    $assignStmt = $pdo->prepare($assignSql);
                    
                    if ($assignStmt->execute([$salesUser['username'], $customerCode])) {
                        $assignments[$salesUser['username']]++;
                        $totalAssigned++;
                    }
                    break;
                }
                
                $salesIndex = ($salesIndex + 1) % count($salesUsers);
                $attempts++;
            }
            
            // If all sales users are at capacity, stop
            if ($attempts >= count($salesUsers)) {
                break;
            }
            
            $salesIndex = ($salesIndex + 1) % count($salesUsers);
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_assigned' => $totalAssigned,
                'total_customers' => count($customers),
                'assignments' => $assignments,
                'max_per_sales' => $maxPerSales,
                'prioritize_hot' => $prioritizeHot,
                'assigned_by' => $assignedBy
            ],
            'message' => "Auto-distribution completed. {$totalAssigned} customers distributed."
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Auto-distribution failed: ' . $e->getMessage()]);
    }
}

function getAssignmentStats($pdo) {
    try {
        // Sales user performance stats
        $statsSql = "SELECT 
                        u.username,
                        u.first_name,
                        u.last_name,
                        COUNT(c.CustomerCode) as total_customers,
                        COUNT(CASE WHEN c.CustomerGrade = 'A' THEN 1 END) as grade_a_count,
                        COUNT(CASE WHEN c.CustomerTemperature = 'HOT' THEN 1 END) as hot_count,
                        AVG(c.TotalPurchase) as avg_purchase,
                        SUM(c.TotalPurchase) as total_revenue
                     FROM users u
                     LEFT JOIN customers c ON u.username = c.Sales
                     WHERE u.role = 'sales' AND u.status = 'active'
                     GROUP BY u.username, u.first_name, u.last_name
                     ORDER BY total_customers DESC";
        
        $stmt = $pdo->prepare($statsSql);
        $stmt->execute();
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $stats,
            'message' => 'Assignment statistics loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getRecentAssignments($pdo) {
    try {
        $limit = min(($_GET['limit'] ?? 20), 100);
        
        $sql = "SELECT 
                    CustomerCode,
                    CustomerName,
                    Sales,
                    COALESCE(CustomerGrade, 'D') as CustomerGrade,
                    COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                    ModifiedDate as assigned_at
                FROM customers 
                WHERE Sales IS NOT NULL AND Sales != ''
                AND ModifiedDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY ModifiedDate DESC
                LIMIT ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $assignments,
            'count' => count($assignments),
            'message' => 'Recent assignments loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function reassignCustomer($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    $newSalesUsername = $input['new_sales_username'] ?? '';
    $assignedBy = Permissions::getCurrentUser();
    
    if (empty($customerCode) || empty($newSalesUsername)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code and new sales username required']);
        return;
    }
    
    try {
        // Verify sales user exists and is active
        $userCheckSql = "SELECT Username FROM users WHERE Username = ? AND Role = 'Sales' AND Status = 1";
        $userStmt = $pdo->prepare($userCheckSql);
        $userStmt->execute([$newSalesUsername]);
        if (!$userStmt->fetch()) {
            throw new Exception('Invalid or inactive sales user');
        }
        
        // Get current assignment
        $currentSql = "SELECT Sales FROM customers WHERE CustomerCode = ?";
        $currentStmt = $pdo->prepare($currentSql);
        $currentStmt->execute([$customerCode]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            throw new Exception('Customer not found');
        }
        
        // Reassign customer (maintain CartStatus as assigned)
        $reassignSql = "UPDATE customers SET 
                           Sales = ?,
                           CartStatus = 'ลูกค้าแจกแล้ว',
                           ModifiedDate = NOW()
                        WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($reassignSql);
        $stmt->execute([$newSalesUsername, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer_code' => $customerCode,
                'previous_sales' => $current['Sales'],
                'new_sales' => $newSalesUsername,
                'reassigned_by' => $assignedBy,
                'reassigned_at' => date('Y-m-d H:i:s')
            ],
            'message' => "Customer {$customerCode} reassigned successfully"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Reassignment failed: ' . $e->getMessage()]);
    }
}

function moveToWaitingBasket($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    $reason = $input['reason'] ?? 'Moved to waiting basket';
    $assignedBy = Permissions::getCurrentUser();
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        // Remove assignment (move back to waiting basket)
        $sql = "UPDATE customers SET 
                   Sales = NULL,
                   CartStatus = 'ตะกร้ารอ',
                   ModifiedDate = NOW()
                WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Customer not found or no changes made');
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer_code' => $customerCode,
                'reason' => $reason,
                'moved_by' => $assignedBy,
                'moved_at' => date('Y-m-d H:i:s')
            ],
            'message' => "Customer {$customerCode} moved to waiting basket"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Move to waiting basket failed: ' . $e->getMessage()]);
    }
}

?>