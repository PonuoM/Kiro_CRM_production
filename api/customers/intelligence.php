<?php
/**
 * Customer Intelligence API
 * Handles Customer Grading and Temperature System
 * Phase 1: Customer Intelligence System
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

try {
    require_once '../../config/database.php';
    require_once '../../includes/permissions.php';
    
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
        case 'grades':
            getGradeDistribution($pdo);
            break;
        case 'temperatures':
            getTemperatureDistribution($pdo);
            break;
        case 'summary':
            getIntelligenceSummary($pdo);
            break;
        case 'filters':
            getCustomersWithFilters($pdo);
            break;
        case 'customer':
            getCustomerIntelligence($pdo);
            break;
        default:
            getIntelligenceDashboard($pdo);
    }
}

function handlePostRequest($pdo, $action) {
    switch ($action) {
        case 'update_grade':
            updateCustomerGrade($pdo);
            break;
        case 'update_temperature':
            updateCustomerTemperature($pdo);
            break;
        case 'update_all_grades':
            updateAllGrades($pdo);
            break;
        case 'update_all_temperatures':
            updateAllTemperatures($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function handlePutRequest($pdo, $action) {
    switch ($action) {
        case 'manual_grade':
            setManualGrade($pdo);
            break;
        case 'manual_temperature':
            setManualTemperature($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Get grade distribution statistics
 */
function getGradeDistribution($pdo) {
    $sql = "SELECT 
                CustomerGrade,
                COUNT(*) as count,
                AVG(TotalPurchase) as avg_purchase,
                SUM(TotalPurchase) as total_revenue
            FROM customers 
            WHERE CustomerGrade IS NOT NULL
            GROUP BY CustomerGrade 
            ORDER BY CustomerGrade";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $distribution,
        'message' => 'Grade distribution loaded successfully'
    ], JSON_PRETTY_PRINT);
}

/**
 * Get temperature distribution statistics
 */
function getTemperatureDistribution($pdo) {
    $sql = "SELECT 
                CustomerTemperature,
                COUNT(*) as count,
                AVG(DATEDIFF(CURDATE(), LastContactDate)) as avg_days_since_contact
            FROM customers 
            WHERE CustomerTemperature IS NOT NULL
            GROUP BY CustomerTemperature 
            ORDER BY 
                CASE CustomerTemperature 
                    WHEN 'HOT' THEN 1 
                    WHEN 'WARM' THEN 2 
                    WHEN 'COLD' THEN 3 
                END";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $distribution,
        'message' => 'Temperature distribution loaded successfully'
    ], JSON_PRETTY_PRINT);
}

/**
 * Get comprehensive intelligence summary
 */
function getIntelligenceSummary($pdo) {
    $sql = "SELECT * FROM customer_intelligence_summary ORDER BY CustomerGrade, CustomerTemperature";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get totals
    $totalSql = "SELECT 
                    COUNT(*) as total_customers,
                    SUM(TotalPurchase) as total_revenue,
                    AVG(TotalPurchase) as avg_purchase
                 FROM customers";
    
    $totalStmt = $pdo->prepare($totalSql);
    $totalStmt->execute();
    $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'summary' => $summary,
            'totals' => $totals
        ],
        'message' => 'Intelligence summary loaded successfully'
    ], JSON_PRETTY_PRINT);
}

/**
 * Get customers with grade and temperature filters
 */
function getCustomersWithFilters($pdo) {
    $grade = $_GET['grade'] ?? '';
    $temperature = $_GET['temperature'] ?? '';
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    $sql = "SELECT 
                CustomerCode,
                CustomerName,
                CustomerTel,
                CustomerStatus,
                CustomerGrade,
                CustomerTemperature,
                TotalPurchase,
                LastContactDate,
                GradeCalculatedDate,
                Sales
            FROM customers 
            WHERE 1=1";
    
    $params = [];
    
    // Add grade filter
    if (!empty($grade) && in_array($grade, ['A', 'B', 'C', 'D'])) {
        $sql .= " AND CustomerGrade = ?";
        $params[] = $grade;
    }
    
    // Add temperature filter
    if (!empty($temperature) && in_array($temperature, ['HOT', 'WARM', 'COLD'])) {
        $sql .= " AND CustomerTemperature = ?";
        $params[] = $temperature;
    }
    
    // Add user filter for Sales role
    if (!$canViewAll) {
        $sql .= " AND Sales = ?";
        $params[] = $currentUser;
    }
    
    $sql .= " ORDER BY 
                CASE CustomerGrade WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                CASE CustomerTemperature WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                TotalPurchase DESC
            LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $customers,
        'count' => count($customers),
        'filters' => [
            'grade' => $grade,
            'temperature' => $temperature
        ],
        'message' => 'Filtered customers loaded successfully'
    ], JSON_PRETTY_PRINT);
}

/**
 * Get specific customer intelligence
 */
function getCustomerIntelligence($pdo) {
    $customerCode = $_GET['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    $sql = "SELECT 
                CustomerCode,
                CustomerName,
                CustomerTel,
                CustomerStatus,
                CustomerGrade,
                CustomerTemperature,
                TotalPurchase,
                LastContactDate,
                ContactAttempts,
                GradeCalculatedDate,
                TemperatureUpdatedDate,
                Sales
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
    
    // Get purchase history for grade calculation
    $orderSql = "SELECT 
                    OrderCode,
                    TotalAmount,
                    OrderDate,
                    OrderStatus
                 FROM orders 
                 WHERE CustomerCode = ? 
                 ORDER BY OrderDate DESC";
    
    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->execute([$customerCode]);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'customer' => $customer,
            'orders' => $orders,
            'intelligence' => [
                'grade_criteria' => getGradeCriteria($customer['TotalPurchase']),
                'temperature_criteria' => getTemperatureCriteria($customer),
                'recommendations' => getCustomerRecommendations($customer)
            ]
        ],
        'message' => 'Customer intelligence loaded successfully'
    ], JSON_PRETTY_PRINT);
}

/**
 * Update customer grade manually or automatically
 */
function updateCustomerGrade($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        // Call stored procedure to update grade
        $sql = "CALL UpdateCustomerGrade(?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        
        // Get updated customer data
        $customerSql = "SELECT CustomerGrade, TotalPurchase, GradeCalculatedDate FROM customers WHERE CustomerCode = ?";
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute([$customerCode]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $customer,
            'message' => 'Customer grade updated successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update grade: ' . $e->getMessage()]);
    }
}

/**
 * Update customer temperature manually or automatically
 */
function updateCustomerTemperature($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        // Call stored procedure to update temperature
        $sql = "CALL UpdateCustomerTemperature(?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        
        // Get updated customer data
        $customerSql = "SELECT CustomerTemperature, LastContactDate, ContactAttempts, TemperatureUpdatedDate FROM customers WHERE CustomerCode = ?";
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute([$customerCode]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $customer,
            'message' => 'Customer temperature updated successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update temperature: ' . $e->getMessage()]);
    }
}

/**
 * Update all customer grades (Admin only)
 */
function updateAllGrades($pdo) {
    if (!Permissions::hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied. Admin only.']);
        return;
    }
    
    try {
        $sql = "CALL UpdateAllCustomerGrades()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'All customer grades updated successfully',
            'details' => $result
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update all grades: ' . $e->getMessage()]);
    }
}

/**
 * Update all customer temperatures (Admin only)
 */
function updateAllTemperatures($pdo) {
    if (!Permissions::hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied. Admin only.']);
        return;
    }
    
    try {
        $sql = "CALL UpdateAllCustomerTemperatures()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'All customer temperatures updated successfully',
            'details' => $result
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update all temperatures: ' . $e->getMessage()]);
    }
}

/**
 * Set manual grade override
 */
function setManualGrade($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    $grade = $input['grade'] ?? '';
    
    if (empty($customerCode) || !in_array($grade, ['A', 'B', 'C', 'D'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid customer code or grade']);
        return;
    }
    
    if (!Permissions::hasPermission('manage_customers')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        return;
    }
    
    try {
        $sql = "UPDATE customers SET 
                    CustomerGrade = ?,
                    GradeCalculatedDate = NOW()
                WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$grade, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Customer grade set manually'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to set grade: ' . $e->getMessage()]);
    }
}

/**
 * Set manual temperature override
 */
function setManualTemperature($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    $temperature = $input['temperature'] ?? '';
    
    if (empty($customerCode) || !in_array($temperature, ['HOT', 'WARM', 'COLD'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid customer code or temperature']);
        return;
    }
    
    if (!Permissions::hasPermission('manage_customers')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied']);
        return;
    }
    
    try {
        $sql = "UPDATE customers SET 
                    CustomerTemperature = ?,
                    TemperatureUpdatedDate = NOW()
                WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$temperature, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Customer temperature set manually'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to set temperature: ' . $e->getMessage()]);
    }
}

/**
 * Get intelligence dashboard overview
 */
function getIntelligenceDashboard($pdo) {
    // Get grade stats
    $gradeSql = "SELECT CustomerGrade, COUNT(*) as count FROM customers WHERE CustomerGrade IS NOT NULL GROUP BY CustomerGrade";
    $gradeStmt = $pdo->prepare($gradeSql);
    $gradeStmt->execute();
    $grades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get temperature stats
    $tempSql = "SELECT CustomerTemperature, COUNT(*) as count FROM customers WHERE CustomerTemperature IS NOT NULL GROUP BY CustomerTemperature";
    $tempStmt = $pdo->prepare($tempSql);
    $tempStmt->execute();
    $temperatures = $tempStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top performers
    $topSql = "SELECT CustomerCode, CustomerName, CustomerGrade, CustomerTemperature, TotalPurchase 
               FROM customers 
               WHERE CustomerGrade IN ('A', 'B') AND CustomerTemperature = 'HOT'
               ORDER BY TotalPurchase DESC LIMIT 5";
    $topStmt = $pdo->prepare($topSql);
    $topStmt->execute();
    $topCustomers = $topStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'grades' => $grades,
            'temperatures' => $temperatures,
            'top_customers' => $topCustomers
        ],
        'message' => 'Intelligence dashboard loaded successfully'
    ], JSON_PRETTY_PRINT);
}

/**
 * Helper function to get grade criteria explanation
 */
function getGradeCriteria($totalPurchase) {
    return [
        'current_amount' => $totalPurchase,
        'criteria' => [
            'A' => ['min' => 10000, 'description' => 'VIP Customer - High Value'],
            'B' => ['min' => 5000, 'description' => 'Premium Customer'],
            'C' => ['min' => 2000, 'description' => 'Regular Customer'],
            'D' => ['min' => 0, 'description' => 'New Customer']
        ]
    ];
}

/**
 * Helper function to get temperature criteria explanation
 */
function getTemperatureCriteria($customer) {
    $daysSinceContact = null;
    if ($customer['LastContactDate']) {
        $daysSinceContact = (new DateTime())->diff(new DateTime($customer['LastContactDate']))->days;
    }
    
    return [
        'days_since_contact' => $daysSinceContact,
        'contact_attempts' => $customer['ContactAttempts'],
        'current_status' => $customer['CustomerStatus'],
        'criteria' => [
            'HOT' => 'New customers, positive status, or contacted within 7 days',
            'WARM' => 'Normal follow-up customers',
            'COLD' => 'Not interested or 3+ failed contact attempts'
        ]
    ];
}

/**
 * Helper function to get customer recommendations
 */
function getCustomerRecommendations($customer) {
    $recommendations = [];
    
    // Grade-based recommendations
    switch ($customer['CustomerGrade']) {
        case 'A':
            $recommendations[] = "🌟 VIP Treatment: Priority support and exclusive offers";
            break;
        case 'B':
            $recommendations[] = "⬆️ Upsell Opportunity: Close to VIP status";
            break;
        case 'C':
            $recommendations[] = "📈 Growth Potential: Focus on increasing purchase volume";
            break;
        case 'D':
            $recommendations[] = "🎯 New Customer: Build relationship and trust";
            break;
    }
    
    // Temperature-based recommendations
    switch ($customer['CustomerTemperature']) {
        case 'HOT':
            $recommendations[] = "🔥 Strike while hot: Follow up immediately";
            break;
        case 'WARM':
            $recommendations[] = "☀️ Maintain engagement: Regular follow-ups";
            break;
        case 'COLD':
            $recommendations[] = "❄️ Re-engagement needed: Try different approach";
            break;
    }
    
    return $recommendations;
}

?>