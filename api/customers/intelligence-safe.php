<?php
/**
 * Customer Intelligence API - Safe Version
 * This version handles cases where Intelligence columns don't exist yet
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
    
    // Check if Intelligence columns exist
    $intelligenceReady = checkIntelligenceColumns($pdo);
    
    if (!$intelligenceReady) {
        // Return setup message if columns don't exist
        echo json_encode([
            'status' => 'setup_required',
            'message' => 'Intelligence system needs to be set up. Please run the database setup script.',
            'setup_required' => true
        ]);
        exit;
    }
    
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

function checkIntelligenceColumns($pdo) {
    try {
        // Check if Intelligence columns exist
        $stmt = $pdo->prepare("SHOW COLUMNS FROM customers LIKE 'CustomerGrade'");
        $stmt->execute();
        $gradeColumn = $stmt->fetch();
        
        $stmt = $pdo->prepare("SHOW COLUMNS FROM customers LIKE 'CustomerTemperature'");
        $stmt->execute();
        $tempColumn = $stmt->fetch();
        
        return $gradeColumn && $tempColumn;
    } catch (Exception $e) {
        return false;
    }
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
        case 'setup':
            setupIntelligenceSystem($pdo);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

function setupIntelligenceSystem($pdo) {
    if (!Permissions::hasPermission('manage_users')) {
        http_response_code(403);
        echo json_encode(['error' => 'Permission denied. Admin only.']);
        return;
    }
    
    try {
        $executedStatements = 0;
        $failedStatements = 0;
        $errors = [];
        
        // Step 1: Add columns safely using IF NOT EXISTS logic
        $intelligenceColumns = [
            'CustomerGrade' => "ENUM('A', 'B', 'C', 'D') NULL COMMENT 'Customer Grade based on purchase amount'",
            'TotalPurchase' => "DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total purchase amount for grading'",
            'LastPurchaseDate' => "DATE NULL COMMENT 'Last purchase date'",
            'GradeCalculatedDate' => "DATETIME NULL COMMENT 'When grade was last calculated'",
            'CustomerTemperature' => "ENUM('HOT', 'WARM', 'COLD') DEFAULT 'WARM' COMMENT 'Customer interaction temperature'",
            'LastContactDate' => "DATE NULL COMMENT 'Last contact date for temperature calculation'",
            'ContactAttempts' => "INT DEFAULT 0 COMMENT 'Number of contact attempts'",
            'TemperatureUpdatedDate' => "DATETIME NULL COMMENT 'When temperature was last updated'"
        ];
        
        foreach ($intelligenceColumns as $columnName => $columnDefinition) {
            try {
                // Check if column exists
                $checkSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = 'customers' 
                           AND COLUMN_NAME = ?";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$columnName]);
                $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] == 0) {
                    // Column doesn't exist, add it
                    $addSql = "ALTER TABLE customers ADD COLUMN {$columnName} {$columnDefinition}";
                    $pdo->exec($addSql);
                    $executedStatements++;
                } else {
                    // Column already exists
                    $executedStatements++;
                }
            } catch (Exception $e) {
                $failedStatements++;
                $errors[] = "Column {$columnName} addition failed: " . $e->getMessage();
            }
        }
        
        // Step 2: Add indexes safely
        $indexes = [
            'idx_customer_grade' => 'CustomerGrade',
            'idx_customer_temperature' => 'CustomerTemperature',
            'idx_total_purchase' => 'TotalPurchase',
            'idx_last_contact' => 'LastContactDate'
        ];
        
        foreach ($indexes as $indexName => $columnName) {
            try {
                // Check if index exists
                $checkIndexSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS 
                                WHERE TABLE_SCHEMA = DATABASE() 
                                AND TABLE_NAME = 'customers' 
                                AND INDEX_NAME = ?";
                $checkIndexStmt = $pdo->prepare($checkIndexSql);
                $checkIndexStmt->execute([$indexName]);
                $indexResult = $checkIndexStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($indexResult['count'] == 0) {
                    // Index doesn't exist, create it
                    $createIndexSql = "CREATE INDEX {$indexName} ON customers({$columnName})";
                    $pdo->exec($createIndexSql);
                    $executedStatements++;
                } else {
                    // Index already exists
                    $executedStatements++;
                }
            } catch (Exception $e) {
                $failedStatements++;
                $errors[] = "Index {$indexName} creation failed: " . $e->getMessage();
            }
        }
        
        // Step 3: Initialize default values
        try {
            $pdo->exec("UPDATE customers 
                        SET 
                            CustomerGrade = 'D',
                            TotalPurchase = 0.00,
                            CustomerTemperature = 'WARM',
                            ContactAttempts = 0,
                            GradeCalculatedDate = NOW(),
                            TemperatureUpdatedDate = NOW()
                        WHERE CustomerGrade IS NULL");
            $executedStatements++;
        } catch (Exception $e) {
            $failedStatements++;
            $errors[] = "Default value initialization failed: " . $e->getMessage();
        }
        
        // Step 4: Create summary view
        try {
            $pdo->exec("CREATE OR REPLACE VIEW customer_intelligence_summary AS
                        SELECT 
                            COALESCE(CustomerGrade, 'D') as CustomerGrade,
                            COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                            COUNT(*) as customer_count,
                            AVG(COALESCE(TotalPurchase, 0)) as avg_purchase,
                            SUM(COALESCE(TotalPurchase, 0)) as total_revenue
                        FROM customers 
                        GROUP BY COALESCE(CustomerGrade, 'D'), COALESCE(CustomerTemperature, 'WARM')
                        ORDER BY CustomerGrade, CustomerTemperature");
            $executedStatements++;
        } catch (Exception $e) {
            $failedStatements++;
            $errors[] = "View creation failed: " . $e->getMessage();
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Intelligence system setup completed successfully',
            'details' => [
                'executed_statements' => $executedStatements,
                'failed_statements' => $failedStatements,
                'errors' => $errors
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Setup failed: ' . $e->getMessage()
        ]);
    }
}

function getGradeDistribution($pdo) {
    try {
        $sql = "SELECT 
                    COALESCE(CustomerGrade, 'D') as CustomerGrade,
                    COUNT(*) as count,
                    AVG(COALESCE(TotalPurchase, 0)) as avg_purchase,
                    SUM(COALESCE(TotalPurchase, 0)) as total_revenue
                FROM customers 
                GROUP BY COALESCE(CustomerGrade, 'D')
                ORDER BY CustomerGrade";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'success',
            'data' => $distribution,
            'message' => 'Grade distribution loaded successfully'
        ], JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getTemperatureDistribution($pdo) {
    try {
        $sql = "SELECT 
                    COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                    COUNT(*) as count,
                    AVG(COALESCE(DATEDIFF(CURDATE(), LastContactDate), 30)) as avg_days_since_contact
                FROM customers 
                GROUP BY COALESCE(CustomerTemperature, 'WARM')
                ORDER BY 
                    CASE COALESCE(CustomerTemperature, 'WARM')
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
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getIntelligenceDashboard($pdo) {
    try {
        // Get basic grade stats
        $gradeSql = "SELECT 
                        COALESCE(CustomerGrade, 'D') as CustomerGrade, 
                        COUNT(*) as count 
                     FROM customers 
                     GROUP BY COALESCE(CustomerGrade, 'D')";
        $gradeStmt = $pdo->prepare($gradeSql);
        $gradeStmt->execute();
        $grades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get basic temperature stats
        $tempSql = "SELECT 
                       COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature, 
                       COUNT(*) as count 
                    FROM customers 
                    GROUP BY COALESCE(CustomerTemperature, 'WARM')";
        $tempStmt = $pdo->prepare($tempSql);
        $tempStmt->execute();
        $temperatures = $tempStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get sample top customers
        $topSql = "SELECT CustomerCode, CustomerName, 
                          COALESCE(CustomerGrade, 'D') as CustomerGrade,
                          COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                          COALESCE(TotalPurchase, 0) as TotalPurchase
                   FROM customers 
                   ORDER BY COALESCE(TotalPurchase, 0) DESC 
                   LIMIT 5";
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
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function getCustomerIntelligence($pdo) {
    $customerCode = $_GET['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
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
        
        // Generate recommendations and criteria
        $intelligence = [
            'grade_criteria' => getGradeCriteria($customer['TotalPurchase']),
            'temperature_criteria' => getTemperatureCriteria($customer),
            'recommendations' => getCustomerRecommendations($customer)
        ];
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'customer' => $customer,
                'intelligence' => $intelligence
            ],
            'message' => 'Customer intelligence loaded successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function updateCustomerGrade($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        // Calculate total purchase from orders if available
        $totalPurchase = 0;
        try {
            $orderSql = "SELECT SUM(TotalAmount) as total FROM orders WHERE CustomerCode = ? AND OrderStatus IN ('completed', 'paid')";
            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute([$customerCode]);
            $orderResult = $orderStmt->fetch(PDO::FETCH_ASSOC);
            $totalPurchase = $orderResult['total'] ?? 0;
        } catch (Exception $e) {
            // Orders table might not exist
            $totalPurchase = 0;
        }
        
        // Calculate grade
        $grade = 'D';
        if ($totalPurchase >= 10000) $grade = 'A';
        elseif ($totalPurchase >= 5000) $grade = 'B';
        elseif ($totalPurchase >= 2000) $grade = 'C';
        
        // Update customer
        $updateSql = "UPDATE customers SET 
                         TotalPurchase = ?,
                         CustomerGrade = ?,
                         GradeCalculatedDate = NOW()
                      WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$totalPurchase, $grade, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'CustomerGrade' => $grade,
                'TotalPurchase' => $totalPurchase,
                'GradeCalculatedDate' => date('Y-m-d H:i:s')
            ],
            'message' => 'Customer grade updated successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update grade: ' . $e->getMessage()]);
    }
}

function updateCustomerTemperature($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $customerCode = $input['customer_code'] ?? '';
    
    if (empty($customerCode)) {
        http_response_code(400);
        echo json_encode(['error' => 'Customer code required']);
        return;
    }
    
    try {
        // Get customer info
        $sql = "SELECT CustomerStatus, LastContactDate, COALESCE(ContactAttempts, 0) as ContactAttempts FROM customers WHERE CustomerCode = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception('Customer not found');
        }
        
        // Calculate temperature
        $temperature = 'WARM';
        $daysSinceContact = 999;
        
        if ($customer['LastContactDate']) {
            $daysSinceContact = (new DateTime())->diff(new DateTime($customer['LastContactDate']))->days;
        }
        
        if (in_array($customer['CustomerStatus'], ['ลูกค้าใหม่', 'คุยจบ', 'สนใจ']) || $daysSinceContact <= 7) {
            $temperature = 'HOT';
        } elseif (in_array($customer['CustomerStatus'], ['ไม่สนใจ', 'ติดต่อไม่ได้']) || $customer['ContactAttempts'] >= 3) {
            $temperature = 'COLD';
        }
        
        // Update customer
        $updateSql = "UPDATE customers SET 
                         CustomerTemperature = ?,
                         TemperatureUpdatedDate = NOW()
                      WHERE CustomerCode = ?";
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([$temperature, $customerCode]);
        
        echo json_encode([
            'status' => 'success',
            'data' => [
                'CustomerTemperature' => $temperature,
                'LastContactDate' => $customer['LastContactDate'],
                'ContactAttempts' => $customer['ContactAttempts'],
                'TemperatureUpdatedDate' => date('Y-m-d H:i:s')
            ],
            'message' => 'Customer temperature updated successfully'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update temperature: ' . $e->getMessage()]);
    }
}

function getCustomersWithFilters($pdo) {
    $grade = $_GET['grade'] ?? '';
    $temperature = $_GET['temperature'] ?? '';
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    try {
        $sql = "SELECT 
                    CustomerCode,
                    CustomerName,
                    CustomerTel,
                    CustomerStatus,
                    COALESCE(CustomerGrade, 'D') as CustomerGrade,
                    COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                    COALESCE(TotalPurchase, 0) as TotalPurchase,
                    LastContactDate,
                    Sales
                FROM customers 
                WHERE 1=1";
        
        $params = [];
        
        // Add grade filter
        if (!empty($grade) && in_array($grade, ['A', 'B', 'C', 'D'])) {
            $sql .= " AND COALESCE(CustomerGrade, 'D') = ?";
            $params[] = $grade;
        }
        
        // Add temperature filter
        if (!empty($temperature) && in_array($temperature, ['HOT', 'WARM', 'COLD'])) {
            $sql .= " AND COALESCE(CustomerTemperature, 'WARM') = ?";
            $params[] = $temperature;
        }
        
        // Add user filter for Sales role
        if (!$canViewAll) {
            $sql .= " AND Sales = ?";
            $params[] = $currentUser;
        }
        
        $sql .= " ORDER BY 
                    CASE COALESCE(CustomerGrade, 'D') WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                    CASE COALESCE(CustomerTemperature, 'WARM') WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                    COALESCE(TotalPurchase, 0) DESC
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
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

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