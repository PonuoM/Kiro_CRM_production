<?php
/**
 * Waiting Basket API
 * Management for customers in waiting status
 * Phase 2: SuperAdmin Role and Admin Workflows
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/permissions.php';

// Check waiting basket permission
if (!Permissions::hasPermission('waiting_basket')) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Waiting basket permission required.']);
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
        case 'waiting_customers':
            getWaitingCustomers($pdo);
            break;
        case 'priority_customers':
            getPriorityCustomers($pdo);
            break;
        case 'customer_history':
            getCustomerHistory($pdo);
            break;
        case 'waiting_stats':
            getWaitingStats($pdo);
            break;
        default:
            getWaitingDashboard($pdo);
    }
}

function handlePostRequest($pdo, $action) {
    switch ($action) {
        case 'add_to_waiting':
            addToWaiting($pdo);
            break;
        case 'update_priority':
            updatePriority($pdo);
            break;
        case 'bulk_prioritize':
            bulkPrioritize($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($pdo, $action) {
    switch ($action) {
        case 'move_to_distribution':
            moveToDistribution($pdo);
            break;
        case 'assign_from_waiting':
            assignFromWaiting($pdo);
            break;
        case 'update_status':
            updateWaitingStatus($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function getWaitingDashboard($pdo) {
    try {
        // Get summary statistics for waiting basket
        $stats = [];
        
        // Total customers in waiting basket
        $waitingSql = "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้ารอ'";
        $stmt = $pdo->prepare($waitingSql);
        $stmt->execute();
        $stats['total_waiting'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // High priority customers (Grade A or HOT) in waiting basket
        $prioritySql = "SELECT COUNT(*) as count FROM customers 
                       WHERE CartStatus = 'ตะกร้ารอ' 
                       AND (COALESCE(CustomerGrade, 'D') = 'A' OR COALESCE(CustomerTemperature, 'WARM') = 'HOT')";
        $stmt = $pdo->prepare($prioritySql);
        $stmt->execute();
        $stats['high_priority'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // COLD customers in waiting basket needing attention
        $coldSql = "SELECT COUNT(*) as count FROM customers 
                   WHERE CartStatus = 'ตะกร้ารอ' 
                   AND COALESCE(CustomerTemperature, 'WARM') = 'COLD'";
        $stmt = $pdo->prepare($coldSql);
        $stmt->execute();
        $stats['cold_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Customers in waiting basket with no recent contact (30+ days)
        $stagnantSql = "SELECT COUNT(*) as count FROM customers 
                       WHERE CartStatus = 'ตะกร้ารอ' 
                       AND (LastContactDate IS NULL OR LastContactDate < DATE_SUB(CURDATE(), INTERVAL 30 DAY))";
        $stmt = $pdo->prepare($stagnantSql);
        $stmt->execute();
        $stats['stagnant'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // New customers in waiting basket (added in last 7 days)
        $newSql = "SELECT COUNT(*) as count FROM customers 
                  WHERE CartStatus = 'ตะกร้ารอ' 
                  AND CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt = $pdo->prepare($newSql);
        $stmt->execute();
        $stats['new_customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'message' => 'Waiting basket dashboard loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getWaitingCustomers($pdo) {
    try {
        $grade = $_GET['grade'] ?? '';
        $temperature = $_GET['temperature'] ?? '';
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $limit = min(($_GET['limit'] ?? 100), 200);
        
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
                    ModifiedDate,
                    CASE 
                        WHEN COALESCE(CustomerGrade, 'D') = 'A' OR COALESCE(CustomerTemperature, 'WARM') = 'HOT' THEN 'HIGH'
                        WHEN COALESCE(CustomerTemperature, 'WARM') = 'COLD' OR ContactAttempts >= 3 THEN 'LOW'
                        ELSE 'MEDIUM'
                    END as Priority,
                    CASE 
                        WHEN LastContactDate IS NULL THEN 999
                        ELSE DATEDIFF(CURDATE(), LastContactDate)
                    END as DaysSinceContact
                FROM customers 
                WHERE CartStatus = 'ตะกร้ารอ'";
        
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
        
        if (!empty($status)) {
            $sql .= " AND CustomerStatus = ?";
            $params[] = $status;
        }
        
        if (!empty($priority) && in_array($priority, ['HIGH', 'MEDIUM', 'LOW'])) {
            $having = " HAVING Priority = ?";
            $params[] = $priority;
        }
        
        // Order by priority and urgency
        $sql .= " ORDER BY 
                    CASE COALESCE(CustomerGrade, 'D') WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                    CASE COALESCE(CustomerTemperature, 'WARM') WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                    DaysSinceContact DESC,
                    CreatedDate ASC";
        
        if (isset($having)) {
            $sql .= $having;
        }
        
        $sql .= " LIMIT ?";
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
                'status' => $status,
                'priority' => $priority,
                'limit' => $limit
            ],
            'message' => 'Waiting customers loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getPriorityCustomers($pdo) {
    try {
        // Get high priority customers that need immediate attention
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
                    CASE 
                        WHEN COALESCE(CustomerGrade, 'D') = 'A' AND COALESCE(CustomerTemperature, 'WARM') = 'HOT' THEN 'URGENT'
                        WHEN COALESCE(CustomerGrade, 'D') = 'A' OR COALESCE(CustomerTemperature, 'WARM') = 'HOT' THEN 'HIGH'
                        ELSE 'NORMAL'
                    END as PriorityLevel,
                    CASE 
                        WHEN LastContactDate IS NULL THEN 'Never contacted'
                        WHEN DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 'Long overdue'
                        WHEN DATEDIFF(CURDATE(), LastContactDate) > 14 THEN 'Overdue'
                        WHEN DATEDIFF(CURDATE(), LastContactDate) > 7 THEN 'Due soon'
                        ELSE 'Recent'
                    END as ContactStatus
                FROM customers 
                WHERE CartStatus = 'ตะกร้ารอ'
                AND (
                    COALESCE(CustomerGrade, 'D') = 'A' 
                    OR COALESCE(CustomerTemperature, 'WARM') = 'HOT'
                    OR (LastContactDate IS NULL OR DATEDIFF(CURDATE(), LastContactDate) > 14)
                )
                ORDER BY 
                    CASE COALESCE(CustomerGrade, 'D') WHEN 'A' THEN 1 ELSE 2 END,
                    CASE COALESCE(CustomerTemperature, 'WARM') WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                    CASE 
                        WHEN LastContactDate IS NULL THEN 999
                        ELSE DATEDIFF(CURDATE(), LastContactDate)
                    END DESC
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $customers,
            'count' => count($customers),
            'message' => 'Priority customers loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getWaitingStats($pdo) {
    try {
        // Comprehensive waiting basket statistics
        $stats = [];
        
        // Grade distribution in waiting basket
        $gradeSql = "SELECT 
                        COALESCE(CustomerGrade, 'D') as grade,
                        COUNT(*) as count,
                        AVG(COALESCE(TotalPurchase, 0)) as avg_purchase
                     FROM customers 
                     WHERE CartStatus = 'ตะกร้ารอ'
                     GROUP BY COALESCE(CustomerGrade, 'D')
                     ORDER BY grade";
        $stmt = $pdo->prepare($gradeSql);
        $stmt->execute();
        $stats['grade_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Temperature distribution
        $tempSql = "SELECT 
                       COALESCE(CustomerTemperature, 'WARM') as temperature,
                       COUNT(*) as count,
                       AVG(CASE 
                           WHEN LastContactDate IS NULL THEN 999
                           ELSE DATEDIFF(CURDATE(), LastContactDate)
                       END) as avg_days_no_contact
                    FROM customers 
                    WHERE CartStatus = 'ตะกร้ารอ'
                    GROUP BY COALESCE(CustomerTemperature, 'WARM')
                    ORDER BY temperature";
        $stmt = $pdo->prepare($tempSql);
        $stmt->execute();
        $stats['temperature_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Contact status breakdown
        $contactSql = "SELECT 
                          CASE 
                              WHEN LastContactDate IS NULL THEN 'Never contacted'
                              WHEN DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 'Over 30 days'
                              WHEN DATEDIFF(CURDATE(), LastContactDate) > 14 THEN '15-30 days'
                              WHEN DATEDIFF(CURDATE(), LastContactDate) > 7 THEN '8-14 days'
                              ELSE 'Within 7 days'
                          END as contact_category,
                          COUNT(*) as count
                       FROM customers 
                       WHERE CartStatus = 'ตะกร้ารอ'
                       GROUP BY contact_category
                       ORDER BY count DESC";
        $stmt = $pdo->prepare($contactSql);
        $stmt->execute();
        $stats['contact_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Status distribution
        $statusSql = "SELECT 
                         CustomerStatus,
                         COUNT(*) as count
                      FROM customers 
                      WHERE CartStatus = 'ตะกร้ารอ'
                      AND CustomerStatus IS NOT NULL
                      GROUP BY CustomerStatus
                      ORDER BY count DESC
                      LIMIT 10";
        $stmt = $pdo->prepare($statusSql);
        $stmt->execute();
        $stats['status_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $stats,
            'message' => 'Waiting basket statistics loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function addToWaiting($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCodes = $input['customer_codes'] ?? [];
    $reason = $input['reason'] ?? 'Moved to waiting basket';
    $movedBy = Permissions::getCurrentUser();
    
    if (empty($customerCodes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer codes required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        $successCount = 0;
        $errors = [];
        
        foreach ($customerCodes as $customerCode) {
            try {
                // Remove assignment (move to waiting)
                $sql = "UPDATE customers SET 
                           Sales = NULL,
                           ModifiedDate = NOW()
                        WHERE CustomerCode = ?";
                
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$customerCode])) {
                    $successCount++;
                } else {
                    $errors[] = "Failed to update customer {$customerCode}";
                }
                
            } catch (Exception $e) {
                $errors[] = "Customer {$customerCode}: " . $e->getMessage();
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'moved_count' => $successCount,
                'total_requested' => count($customerCodes),
                'reason' => $reason,
                'moved_by' => $movedBy,
                'errors' => $errors
            ],
            'message' => "Successfully moved {$successCount} customers to waiting basket"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Move to waiting failed: ' . $e->getMessage()]);
    }
}

function moveToDistribution($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCodes = $input['customer_codes'] ?? [];
    $priority = $input['priority'] ?? 'NORMAL';
    $movedBy = Permissions::getCurrentUser();
    
    if (empty($customerCodes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer codes required']);
        return;
    }
    
    try {
        // For now, we'll just ensure customers are in waiting basket (Sales = NULL)
        // In a more advanced system, we could add a separate priority table
        $pdo->beginTransaction();
        
        $successCount = 0;
        $errors = [];
        
        foreach ($customerCodes as $customerCode) {
            try {
                // Move customer from waiting basket to distribution basket
                $moveToDistributionSql = "UPDATE customers SET 
                                             CartStatus = 'ตะกร้าแจก',
                                             ModifiedDate = NOW()
                                          WHERE CustomerCode = ? AND CartStatus = 'ตะกร้ารอ'";
                $moveStmt = $pdo->prepare($moveToDistributionSql);
                
                if ($moveStmt->execute([$customerCode]) && $moveStmt->rowCount() > 0) {
                    $successCount++;
                } else {
                    $errors[] = "Customer {$customerCode} not found in waiting basket";
                }
                
            } catch (Exception $e) {
                $errors[] = "Customer {$customerCode}: " . $e->getMessage();
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'processed_count' => $successCount,
                'total_requested' => count($customerCodes),
                'priority' => $priority,
                'moved_by' => $movedBy,
                'errors' => $errors
            ],
            'message' => "Successfully processed {$successCount} customers for distribution"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Move to distribution failed: ' . $e->getMessage()]);
    }
}

function assignFromWaiting($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    $salesUsername = $input['sales_username'] ?? '';
    $assignedBy = Permissions::getCurrentUser();
    
    if (empty($customerCode) || empty($salesUsername)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code and sales username required']);
        return;
    }
    
    try {
        // Verify sales user exists and is active
        $userCheckSql = "SELECT username FROM users WHERE username = ? AND role = 'sales' AND status = 'active'";
        $userStmt = $pdo->prepare($userCheckSql);
        $userStmt->execute([$salesUsername]);
        if (!$userStmt->fetch()) {
            throw new Exception('Invalid or inactive sales user');
        }
        
        // Verify customer is in waiting basket
        $customerCheckSql = "SELECT CustomerCode FROM customers WHERE CustomerCode = ? AND CartStatus = 'ตะกร้ารอ'";
        $customerStmt = $pdo->prepare($customerCheckSql);
        $customerStmt->execute([$customerCode]);
        if (!$customerStmt->fetch()) {
            throw new Exception('Customer not found in waiting basket');
        }
        
        // Assign customer directly from waiting basket
        $assignSql = "UPDATE customers SET 
                         Sales = ?,
                         CartStatus = 'ลูกค้าแจกแล้ว',
                         ModifiedDate = NOW()
                      WHERE CustomerCode = ? AND CartStatus = 'ตะกร้ารอ'";
        
        $stmt = $pdo->prepare($assignSql);
        $stmt->execute([$salesUsername, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer_code' => $customerCode,
                'sales_username' => $salesUsername,
                'assigned_by' => $assignedBy,
                'assigned_at' => date('Y-m-d H:i:s')
            ],
            'message' => "Customer {$customerCode} assigned to {$salesUsername} from waiting basket"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Assignment from waiting failed: ' . $e->getMessage()]);
    }
}

function updateWaitingStatus($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    $newStatus = $input['new_status'] ?? '';
    $updatedBy = Permissions::getCurrentUser();
    
    if (empty($customerCode) || empty($newStatus)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code and new status required']);
        return;
    }
    
    try {
        // Update customer status (only for customers in waiting basket)
        $sql = "UPDATE customers SET 
                   CustomerStatus = ?,
                   ModifiedDate = NOW()
                WHERE CustomerCode = ? AND CartStatus = 'ตะกร้ารอ'";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newStatus, $customerCode]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Customer not found in waiting basket or no changes made');
        }
        
        // Update temperature based on new status
        $tempSql = "UPDATE customers SET 
                       CustomerTemperature = CASE 
                           WHEN ? IN ('ลูกค้าใหม่', 'คุยจบ', 'สนใจ') THEN 'HOT'
                           WHEN ? IN ('ไม่สนใจ', 'ติดต่อไม่ได้') THEN 'COLD'
                           ELSE 'WARM'
                       END,
                       TemperatureUpdatedDate = NOW()
                    WHERE CustomerCode = ?";
        
        $tempStmt = $pdo->prepare($tempSql);
        $tempStmt->execute([$newStatus, $newStatus, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer_code' => $customerCode,
                'new_status' => $newStatus,
                'updated_by' => $updatedBy,
                'updated_at' => date('Y-m-d H:i:s')
            ],
            'message' => "Customer {$customerCode} status updated successfully"
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Status update failed: ' . $e->getMessage()]);
    }
}

function getCustomerHistory($pdo) {
    $customerCode = $_GET['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        // Get customer details with history
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
                    Sales,
                    CreatedDate,
                    ModifiedDate,
                    CASE 
                        WHEN LastContactDate IS NULL THEN 'Never contacted'
                        WHEN DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 'Over 30 days ago'
                        WHEN DATEDIFF(CURDATE(), LastContactDate) > 14 THEN '2-4 weeks ago'
                        WHEN DATEDIFF(CURDATE(), LastContactDate) > 7 THEN '1-2 weeks ago'
                        ELSE 'Within last week'
                    END as ContactSummary
                FROM customers 
                WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['error' => 'Customer not found']);
            return;
        }
        
        // Generate recommendations
        $recommendations = [];
        
        if ($customer['CustomerTemperature'] === 'HOT') {
            $recommendations[] = '🔥 High priority - Contact immediately';
        }
        
        if ($customer['CustomerGrade'] === 'A') {
            $recommendations[] = '⭐ VIP customer - Special attention required';
        }
        
        if ($customer['ContactAttempts'] >= 3) {
            $recommendations[] = '📞 Multiple contact attempts - Consider different approach';
        }
        
        if ($customer['LastContactDate'] === null || strtotime($customer['LastContactDate']) < strtotime('-30 days')) {
            $recommendations[] = '⏰ Long time without contact - Priority follow-up needed';
        }
        
        if ($customer['CustomerTemperature'] === 'COLD') {
            $recommendations[] = '❄️ Cold customer - Re-engagement strategy needed';
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer' => $customer,
                'recommendations' => $recommendations,
                'waiting_status' => ($customer['Sales'] === null || $customer['Sales'] === '') ? 'In waiting basket' : 'Assigned to ' . $customer['Sales']
            ],
            'message' => 'Customer history loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

?>