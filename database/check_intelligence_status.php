<?php
/**
 * Check Intelligence System Database Status
 * This script checks if Intelligence columns and functions exist
 */

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $status = [
        'database_connection' => true,
        'customers_table' => false,
        'intelligence_columns' => [],
        'intelligence_functions' => [],
        'intelligence_views' => [],
        'sample_data' => [],
        'recommendations' => []
    ];
    
    // Check if customers table exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers LIMIT 1");
        $stmt->execute();
        $status['customers_table'] = true;
    } catch (Exception $e) {
        $status['customers_table'] = false;
        $status['error'] = 'Customers table does not exist';
        echo json_encode($status, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Check Intelligence columns
    $intelligenceColumns = [
        'CustomerGrade' => 'ENUM(\'A\', \'B\', \'C\', \'D\')',
        'TotalPurchase' => 'DECIMAL(10,2)',
        'LastPurchaseDate' => 'DATE',
        'GradeCalculatedDate' => 'DATETIME',
        'CustomerTemperature' => 'ENUM(\'HOT\', \'WARM\', \'COLD\')',
        'LastContactDate' => 'DATE',
        'ContactAttempts' => 'INT',
        'TemperatureUpdatedDate' => 'DATETIME'
    ];
    
    foreach ($intelligenceColumns as $column => $type) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM customers LIKE ?");
            $stmt->execute([$column]);
            $result = $stmt->fetch();
            $status['intelligence_columns'][$column] = $result ? true : false;
        } catch (Exception $e) {
            $status['intelligence_columns'][$column] = false;
        }
    }
    
    // Check Intelligence functions
    $intelligenceFunctions = [
        'CalculateCustomerGrade',
        'CalculateCustomerTemperature'
    ];
    
    foreach ($intelligenceFunctions as $function) {
        try {
            $stmt = $pdo->prepare("SHOW FUNCTION STATUS WHERE Name = ?");
            $stmt->execute([$function]);
            $result = $stmt->fetch();
            $status['intelligence_functions'][$function] = $result ? true : false;
        } catch (Exception $e) {
            $status['intelligence_functions'][$function] = false;
        }
    }
    
    // Check Intelligence views
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'customer_intelligence_summary'");
        $stmt->execute();
        $result = $stmt->fetch();
        $status['intelligence_views']['customer_intelligence_summary'] = $result ? true : false;
    } catch (Exception $e) {
        $status['intelligence_views']['customer_intelligence_summary'] = false;
    }
    
    // Get sample data if Intelligence is set up
    $columnsExist = array_sum($status['intelligence_columns']) > 0;
    if ($columnsExist) {
        try {
            // Get grade distribution
            $stmt = $pdo->prepare("SELECT 
                COALESCE(CustomerGrade, 'D') as grade, 
                COUNT(*) as count 
                FROM customers 
                GROUP BY COALESCE(CustomerGrade, 'D')
                ORDER BY grade");
            $stmt->execute();
            $gradeData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $status['sample_data']['grade_distribution'] = $gradeData;
            
            // Get temperature distribution
            $stmt = $pdo->prepare("SELECT 
                COALESCE(CustomerTemperature, 'WARM') as temperature, 
                COUNT(*) as count 
                FROM customers 
                GROUP BY COALESCE(CustomerTemperature, 'WARM')
                ORDER BY temperature");
            $stmt->execute();
            $tempData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $status['sample_data']['temperature_distribution'] = $tempData;
            
            // Get total customers
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM customers");
            $stmt->execute();
            $totalResult = $stmt->fetch();
            $status['sample_data']['total_customers'] = $totalResult['total'];
            
        } catch (Exception $e) {
            $status['sample_data']['error'] = $e->getMessage();
        }
    }
    
    // Generate recommendations
    $columnsReady = count(array_filter($status['intelligence_columns']));
    $functionsReady = count(array_filter($status['intelligence_functions']));
    $viewsReady = count(array_filter($status['intelligence_views']));
    
    if ($columnsReady === 0) {
        $status['recommendations'][] = "🔧 Run database setup to add Intelligence columns";
        $status['setup_required'] = true;
    } elseif ($columnsReady < count($intelligenceColumns)) {
        $status['recommendations'][] = "⚠️ Some Intelligence columns are missing - partial setup detected";
        $status['setup_required'] = true;
    } else {
        $status['recommendations'][] = "✅ Intelligence columns are ready";
        $status['setup_required'] = false;
    }
    
    if ($functionsReady === 0) {
        $status['recommendations'][] = "🔧 Create Intelligence functions for automatic calculations";
    } elseif ($functionsReady < count($intelligenceFunctions)) {
        $status['recommendations'][] = "⚠️ Some Intelligence functions are missing";
    } else {
        $status['recommendations'][] = "✅ Intelligence functions are ready";
    }
    
    if ($viewsReady === 0) {
        $status['recommendations'][] = "🔧 Create Intelligence summary view for reporting";
    } else {
        $status['recommendations'][] = "✅ Intelligence views are ready";
    }
    
    $status['overall_status'] = $status['setup_required'] ? 'setup_required' : 'ready';
    $status['completion_percentage'] = round((($columnsReady + $functionsReady + $viewsReady) / (count($intelligenceColumns) + count($intelligenceFunctions) + 1)) * 100, 1);
    
    echo json_encode($status, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'database_connection' => false
    ], JSON_PRETTY_PRINT);
}
?>