<?php
/**
 * Customer Intelligence Debug API
 * แสดงขั้นตอนการคำนวณ Grade และ Temperature
 * ใช้สำหรับ Debug และ Monitoring
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    require_once '../../includes/customer_intelligence.php';
    
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
        case 'customer':
            debugCustomerIntelligence($pdo);
            break;
        case 'system':
            debugSystemOverview($pdo);
            break;
        case 'validation':
            validateSystemLogic($pdo);
            break;
        case 'compare':
            compareOldVsNew($pdo);
            break;
        default:
            debugSystemDashboard($pdo);
    }
}

function handlePostRequest($pdo, $action) {
    switch ($action) {
        case 'recalculate':
            recalculateCustomerIntelligence($pdo);
            break;
        case 'batch_update':
            batchUpdateIntelligence($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Debug specific customer intelligence calculation
 */
function debugCustomerIntelligence($pdo) {
    $customerCode = $_GET['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        $intelligence = new CustomerIntelligence($pdo);
        
        // Get customer basic data
        $customerSql = "SELECT CustomerCode, CustomerName, CustomerStatus, CustomerGrade as Grade, 
                               CustomerTemperature, TotalPurchase, AssignmentCount, Sales
                        FROM customers 
                        WHERE CustomerCode = ?";
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute([$customerCode]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            echo json_encode(['error' => 'Customer not found']);
            return;
        }
        
        // Calculate purchase data from orders
        $ordersSql = "SELECT OrderCode, DocumentDate, Price, OrderStatus
                      FROM orders 
                      WHERE CustomerCode = ? 
                      ORDER BY DocumentDate DESC";
        $ordersStmt = $pdo->prepare($ordersSql);
        $ordersStmt->execute([$customerCode]);
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalPurchase = 0;
        $validOrders = [];
        foreach ($orders as $order) {
            if ($order['Price'] > 0) {
                $totalPurchase += $order['Price'];
                $validOrders[] = $order;
            }
        }
        
        // Get call history
        $callSql = "SELECT CallDate, TalkStatus, CallResult, CallDuration
                    FROM call_logs 
                    WHERE CustomerCode = ? 
                    ORDER BY CallDate DESC 
                    LIMIT 10";
        $callStmt = $pdo->prepare($callSql);
        $callStmt->execute([$customerCode]);
        $callHistory = $callStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate expected Grade
        $expectedGrade = $intelligence->calculateCustomerGrade($customerCode);
        $expectedTemperature = $intelligence->calculateCustomerTemperature($customerCode);
        
        // Grade calculation logic
        $gradeLogic = [
            'total_purchase' => $totalPurchase,
            'criteria' => [
                'A' => ['min' => 810000, 'met' => $totalPurchase >= 810000],
                'B' => ['min' => 85000, 'met' => $totalPurchase >= 85000 && $totalPurchase < 810000],
                'C' => ['min' => 2000, 'met' => $totalPurchase >= 2000 && $totalPurchase < 85000],
                'D' => ['min' => 0, 'met' => $totalPurchase < 2000]
            ],
            'current_grade' => $customer['Grade'],
            'expected_grade' => $expectedGrade,
            'is_correct' => $customer['Grade'] === $expectedGrade
        ];
        
        // Temperature calculation logic
        $rejectionCount = 0;
        $lastCallResult = null;
        foreach ($callHistory as $call) {
            if ($lastCallResult === null) {
                $lastCallResult = $call;
            }
            
            $rejectionKeywords = ['ไม่สนใจ', 'ติดต่อไม่ได้', 'ปฏิเสธ', 'ไม่รับ', 'ไม่ต้องการ'];
            foreach ($rejectionKeywords as $keyword) {
                if (strpos($call['CallResult'], $keyword) !== false) {
                    $rejectionCount++;
                    break;
                }
            }
        }
        
        $temperatureLogic = [
            'current_temperature' => $customer['CustomerTemperature'],
            'expected_temperature' => $expectedTemperature,
            'is_correct' => $customer['CustomerTemperature'] === $expectedTemperature,
            'factors' => [
                'customer_status' => $customer['CustomerStatus'],
                'is_new_customer' => $customer['CustomerStatus'] === 'ลูกค้าใหม่',
                'has_call_history' => !empty($callHistory),
                'rejection_count' => $rejectionCount,
                'assignment_count' => $customer['AssignmentCount'],
                'is_high_grade' => in_array($customer['Grade'], ['A', 'B']),
                'high_purchase' => $totalPurchase > 50000,
                'last_call' => $lastCallResult
            ],
            'rules_applied' => []
        ];
        
        // Add temperature rules explanation
        if ($customer['CustomerStatus'] === 'ลูกค้าใหม่' && empty($callHistory)) {
            $temperatureLogic['rules_applied'][] = 'New customer without call history → HOT';
        }
        
        if (in_array($customer['Grade'], ['A', 'B']) && $totalPurchase > 50000) {
            $temperatureLogic['rules_applied'][] = 'Grade A/B with high purchase → Cannot be FROZEN';
        }
        
        if ($rejectionCount >= 2) {
            $temperatureLogic['rules_applied'][] = 'Multiple rejections (' . $rejectionCount . ') → COLD';
        }
        
        if ($customer['AssignmentCount'] >= 3) {
            $temperatureLogic['rules_applied'][] = 'High assignment count (' . $customer['AssignmentCount'] . ') → FROZEN (unless Grade A/B)';
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer' => $customer,
                'orders_analysis' => [
                    'total_orders' => count($orders),
                    'valid_orders' => count($validOrders),
                    'calculated_total' => $totalPurchase,
                    'stored_total' => $customer['TotalPurchase'],
                    'match' => abs($totalPurchase - $customer['TotalPurchase']) < 0.01,
                    'recent_orders' => array_slice($validOrders, 0, 5)
                ],
                'call_analysis' => [
                    'total_calls' => count($callHistory),
                    'rejection_count' => $rejectionCount,
                    'last_call' => $lastCallResult,
                    'recent_calls' => $callHistory
                ],
                'grade_calculation' => $gradeLogic,
                'temperature_calculation' => $temperatureLogic,
                'recommendations' => generateRecommendations($customer, $gradeLogic, $temperatureLogic)
            ],
            'message' => 'Customer intelligence debug completed'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Debug failed: ' . $e->getMessage()]);
    }
}

/**
 * Debug system overview
 */
function debugSystemOverview($pdo) {
    try {
        // Grade distribution
        $gradeSql = "SELECT 
                        COALESCE(CustomerGrade, 'NULL') as Grade,
                        COUNT(*) as count,
                        MIN(TotalPurchase) as min_purchase,
                        MAX(TotalPurchase) as max_purchase,
                        AVG(TotalPurchase) as avg_purchase
                     FROM customers 
                     GROUP BY CustomerGrade 
                     ORDER BY CustomerGrade";
        $gradeStmt = $pdo->query($gradeSql);
        $gradeDistribution = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Temperature distribution
        $tempSql = "SELECT 
                       COALESCE(CustomerTemperature, 'NULL') as Temperature,
                       COUNT(*) as count
                    FROM customers 
                    GROUP BY CustomerTemperature 
                    ORDER BY CustomerTemperature";
        $tempStmt = $pdo->query($tempSql);
        $temperatureDistribution = $tempStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent intelligence updates
        $recentSql = "SELECT CustomerCode, CustomerName, CustomerGrade as Grade, CustomerTemperature
                      FROM customers 
                      WHERE CustomerGrade IS NOT NULL OR CustomerTemperature IS NOT NULL
                      ORDER BY CustomerCode DESC
                      LIMIT 20";
        $recentStmt = $pdo->query($recentSql);
        $recentUpdates = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // System health checks
        $healthChecks = [
            'total_customers' => getTotalCustomers($pdo),
            'customers_with_grade' => getCustomersWithGrade($pdo),
            'customers_with_temperature' => getCustomersWithTemperature($pdo),
            'high_value_frozen' => getHighValueFrozen($pdo),
            'grade_mismatches' => getGradeMismatches($pdo)
        ];
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'grade_distribution' => $gradeDistribution,
                'temperature_distribution' => $temperatureDistribution,
                'recent_updates' => $recentUpdates,
                'health_checks' => $healthChecks,
                'system_status' => determineSystemStatus($healthChecks)
            ],
            'message' => 'System overview completed'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'System overview failed: ' . $e->getMessage()]);
    }
}

/**
 * Batch update customer intelligence
 */
function batchUpdateIntelligence($pdo) {
    if (!Permissions::hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied. Admin only.']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $limit = $input['limit'] ?? 100;
    $customerCodes = $input['customer_codes'] ?? [];
    
    try {
        $intelligence = new CustomerIntelligence($pdo);
        
        if (!empty($customerCodes)) {
            // Update specific customers
            $stats = ['processed' => 0, 'errors' => 0, 'changes' => 0];
            
            foreach ($customerCodes as $customerCode) {
                try {
                    $intelligence->updateCustomerIntelligence($customerCode);
                    $stats['processed']++;
                } catch (Exception $e) {
                    $stats['errors']++;
                    error_log("Failed to update {$customerCode}: " . $e->getMessage());
                }
            }
        } else {
            // Update all customers
            $stats = $intelligence->updateAllCustomersIntelligence($limit);
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $stats,
            'message' => 'Batch update completed'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Batch update failed: ' . $e->getMessage()]);
    }
}

// Helper functions
function getTotalCustomers($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
    return $stmt->fetchColumn();
}

function getCustomersWithGrade($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE Grade IS NOT NULL");
    return $stmt->fetchColumn();
}

function getCustomersWithTemperature($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE CustomerTemperature IS NOT NULL");
    return $stmt->fetchColumn();
}

function getHighValueFrozen($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE CustomerGrade IN ('A', 'B') AND CustomerTemperature = 'FROZEN' AND TotalPurchase > 50000");
    return $stmt->fetchColumn();
}

function getGradeMismatches($pdo) {
    $sql = "SELECT COUNT(*) FROM customers 
            WHERE (TotalPurchase >= 810000 AND CustomerGrade != 'A') 
               OR (TotalPurchase >= 85000 AND TotalPurchase < 810000 AND CustomerGrade != 'B')
               OR (TotalPurchase >= 2000 AND TotalPurchase < 85000 AND CustomerGrade != 'C')
               OR (TotalPurchase < 2000 AND CustomerGrade != 'D')";
    $stmt = $pdo->query($sql);
    return $stmt->fetchColumn();
}

function determineSystemStatus($healthChecks) {
    $issues = [];
    
    if ($healthChecks['high_value_frozen'] > 0) {
        $issues[] = "High-value customers marked as FROZEN: " . $healthChecks['high_value_frozen'];
    }
    
    if ($healthChecks['grade_mismatches'] > 0) {
        $issues[] = "Grade mismatches detected: " . $healthChecks['grade_mismatches'];
    }
    
    $coverage = [
        'grade' => ($healthChecks['customers_with_grade'] / max(1, $healthChecks['total_customers'])) * 100,
        'temperature' => ($healthChecks['customers_with_temperature'] / max(1, $healthChecks['total_customers'])) * 100
    ];
    
    if ($coverage['grade'] < 90) {
        $issues[] = "Low grade coverage: " . round($coverage['grade'], 1) . "%";
    }
    
    if ($coverage['temperature'] < 90) {
        $issues[] = "Low temperature coverage: " . round($coverage['temperature'], 1) . "%";
    }
    
    return [
        'status' => empty($issues) ? 'healthy' : 'warning',
        'issues' => $issues,
        'coverage' => $coverage
    ];
}

function generateRecommendations($customer, $gradeLogic, $temperatureLogic) {
    $recommendations = [];
    
    if (!$gradeLogic['is_correct']) {
        $recommendations[] = [
            'type' => 'grade_fix',
            'message' => "Grade should be {$gradeLogic['expected_grade']} instead of {$gradeLogic['current_grade']}",
            'action' => 'Update customer grade based on purchase amount'
        ];
    }
    
    if (!$temperatureLogic['is_correct']) {
        $recommendations[] = [
            'type' => 'temperature_fix',
            'message' => "Temperature should be {$temperatureLogic['expected_temperature']} instead of {$temperatureLogic['current_temperature']}",
            'action' => 'Update customer temperature based on interaction history'
        ];
    }
    
    if ($customer['Grade'] === 'A' && $customer['CustomerTemperature'] === 'FROZEN') {
        $recommendations[] = [
            'type' => 'high_value_attention',
            'message' => 'VIP customer marked as FROZEN - requires immediate attention',
            'action' => 'Contact customer or reassign to senior sales rep'
        ];
    }
    
    return $recommendations;
}

?>