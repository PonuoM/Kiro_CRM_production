<?php
/**
 * Lead Management Automation Rules - Updated Version
 * รวม Customer Intelligence System
 * 
 * Updated to include:
 * - Customer Grade & Temperature Intelligence
 * - Real-time calculation integration
 * - Enhanced automation rules
 * 
 * Execution: Should be run daily via cron job
 * Security: CLI access only, protected from web access
 */

// ================================
// SECURITY: CLI ONLY ACCESS
// ================================
if (php_sapi_name() !== 'cli' && !isset($_SERVER['HTTP_X_CRON_AUTH'])) {
    http_response_code(403);
    die("Access Denied: This script can only be executed via CLI or authorized cron.");
}

// ================================
// INITIALIZATION
// ================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 600); // 10 minutes max

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/customer_intelligence.php';

// ================================
// ENHANCED LOGGING SYSTEM
// ================================
class EnhancedCronLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/cron_auto_rules_enhanced.log';
        
        // Ensure logs directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also output to console if CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
    
    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }
    
    public function success($message, $context = []) {
        $this->log('SUCCESS', $message, $context);
    }
}

// ================================
// ENHANCED AUTOMATION CLASS
// ================================
class EnhancedLeadManagementAutomation {
    private $pdo;
    private $logger;
    private $intelligence;
    private $stats;
    
    public function __construct() {
        $this->logger = new EnhancedCronLogger();
        $this->stats = [
            'processed_customers' => 0,
            'intelligence_updates' => 0,
            'grade_updates' => 0,
            'temperature_updates' => 0,
            'cart_status_updates' => 0,
            'frozen_customers' => 0,
            'high_value_unfrozen' => 0,
            'errors' => 0,
            'start_time' => microtime(true)
        ];
        
        $this->initializeDatabase();
        $this->intelligence = new CustomerIntelligence($this->pdo);
        $this->logger->info('Enhanced Lead Management Automation initialized with Customer Intelligence');
    }
    
    private function initializeDatabase() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->logger->info('Database connection established');
        } catch (Exception $e) {
            $this->logger->error('Database connection failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Main execution method - runs all automation rules
     */
    public function execute() {
        $this->logger->info('Starting Enhanced Lead Management Automation execution');
        
        try {
            // Step 1: Update Customer Intelligence (Grade & Temperature)
            $this->updateCustomerIntelligence();
            
            // Step 2: Process High-Value Customer Protection
            $this->processHighValueCustomerProtection();
            
            // Step 3: Process Time-Based Rules (with Intelligence)
            $this->processTimeBasedRules();
            
            // Step 4: Process Interaction-Based Rules (with Intelligence)
            $this->processInteractionBasedRules();
            
            // Step 5: Process Enhanced Freezing Rules
            $this->processEnhancedFreezingRules();
            
            // Step 6: Validate System Health
            $this->validateSystemHealth();
            
            // Step 7: Generate execution summary
            $this->generateSummary();
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Automation execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update Customer Intelligence for all customers
     * This ensures Grade and Temperature are current
     */
    private function updateCustomerIntelligence() {
        $this->logger->info('Updating Customer Intelligence (Grade & Temperature)');
        
        try {
            // Get customers that need intelligence updates (sample or all based on load)
            $sql = "SELECT CustomerCode FROM customers 
                    ORDER BY RAND() 
                    LIMIT 500"; // Process 500 random customers daily
            
            $stmt = $this->pdo->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $gradeUpdates = 0;
            $tempUpdates = 0;
            
            foreach ($customers as $customerCode) {
                try {
                    // Get current values
                    $currentSql = "SELECT CustomerGrade, CustomerTemperature FROM customers WHERE CustomerCode = ?";
                    $currentStmt = $this->pdo->prepare($currentSql);
                    $currentStmt->execute([$customerCode]);
                    $current = $currentStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$current) continue;
                    
                    // Calculate new values
                    $newGrade = $this->intelligence->calculateCustomerGrade($customerCode);
                    $newTemperature = $this->intelligence->calculateCustomerTemperature($customerCode);
                    
                    // Update if changed
                    $updates = [];
                    $params = [];
                    
                    if ($current['CustomerGrade'] !== $newGrade) {
                        $updates[] = "CustomerGrade = ?";
                        $params[] = $newGrade;
                        $gradeUpdates++;
                    }
                    
                    if ($current['CustomerTemperature'] !== $newTemperature) {
                        $updates[] = "CustomerTemperature = ?";
                        $params[] = $newTemperature;
                        $tempUpdates++;
                    }
                    
                    if (!empty($updates)) {
                        $params[] = $customerCode;
                        $updateSql = "UPDATE customers SET " . implode(", ", $updates) . " WHERE CustomerCode = ?";
                        $updateStmt = $this->pdo->prepare($updateSql);
                        $updateStmt->execute($params);
                        
                        $this->logger->info('Customer intelligence updated', [
                            'customer_code' => $customerCode,
                            'grade_change' => $current['CustomerGrade'] . ' → ' . $newGrade,
                            'temperature_change' => $current['CustomerTemperature'] . ' → ' . $newTemperature
                        ]);
                    }
                    
                    $this->stats['processed_customers']++;
                    
                } catch (Exception $e) {
                    $this->stats['errors']++;
                    $this->logger->error('Failed to update customer intelligence', [
                        'customer_code' => $customerCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->stats['grade_updates'] = $gradeUpdates;
            $this->stats['temperature_updates'] = $tempUpdates;
            $this->stats['intelligence_updates'] = $gradeUpdates + $tempUpdates;
            
            $this->logger->success("Customer Intelligence updated", [
                'customers_processed' => count($customers),
                'grade_updates' => $gradeUpdates,
                'temperature_updates' => $tempUpdates
            ]);
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to update customer intelligence', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Process High-Value Customer Protection
     * Grade A,B customers should not be FROZEN if they have high purchase amounts
     */
    private function processHighValueCustomerProtection() {
        $this->logger->info('Processing High-Value Customer Protection');
        
        try {
            // Find Grade A,B customers that are FROZEN but have high purchase amounts
            $sql = "
                SELECT CustomerCode, CustomerName, CustomerGrade, TotalPurchase, CustomerTemperature
                FROM customers 
                WHERE CustomerGrade IN ('A', 'B')
                AND CustomerTemperature = 'FROZEN'
                AND TotalPurchase > 50000
                LIMIT 100
            ";
            
            $stmt = $this->pdo->query($sql);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $unfrozenCount = 0;
            
            foreach ($customers as $customer) {
                // Unfreeze high-value customers
                $updateSql = "
                    UPDATE customers 
                    SET CustomerTemperature = 'WARM',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_intelligence'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $unfrozenCount++;
                    $this->logger->info('High-value customer unfrozen', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'grade' => $customer['CustomerGrade'],
                        'total_purchase' => $customer['TotalPurchase'],
                        'previous_temperature' => 'FROZEN',
                        'new_temperature' => 'WARM'
                    ]);
                }
            }
            
            $this->stats['high_value_unfrozen'] = $unfrozenCount;
            $this->logger->success("High-value customer protection processed: $unfrozenCount customers unfrozen");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process high-value customer protection', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Enhanced Time-Based Rules with Intelligence
     */
    private function processTimeBasedRules() {
        $this->logger->info('Processing Enhanced Time-Based Rules');
        
        // Rule 1: New customers (Grade D) without call logs for 30 days
        $this->processNewCustomerTimeRule();
        
        // Rule 2: Existing customers without orders for 3 months (except Grade A,B)
        $this->processExistingCustomerTimeRule();
    }
    
    private function processNewCustomerTimeRule() {
        $this->logger->info('Processing: Enhanced new customer 30-day rule (Grade D focus)');
        
        try {
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.AssignDate, c.Sales, c.CustomerGrade
                FROM customers c
                WHERE c.CustomerStatus = 'ลูกค้าใหม่'
                AND c.CustomerGrade = 'D'
                AND c.CartStatus = 'กำลังดูแล'
                AND c.AssignDate IS NOT NULL
                AND c.AssignDate <= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND NOT EXISTS (
                    SELECT 1 FROM call_logs cl 
                    WHERE cl.CustomerCode = c.CustomerCode 
                    AND cl.CallDate > DATE_SUB(NOW(), INTERVAL 30 DAY)
                )
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้าแจก',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_intelligence'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Grade D customer moved to distribution basket', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'grade' => $customer['CustomerGrade'],
                        'assign_date' => $customer['AssignDate'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['cart_status_updates'] += $updateCount;
            $this->logger->success("Enhanced new customer rule processed: $updateCount Grade D customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process enhanced new customer rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function processExistingCustomerTimeRule() {
        $this->logger->info('Processing: Enhanced existing customer rule (protect Grade A,B)');
        
        try {
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales, c.CustomerGrade,
                       MAX(o.DocumentDate) as LastOrderDate
                FROM customers c
                LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode
                WHERE c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
                AND c.CartStatus = 'กำลังดูแล'
                AND c.CustomerGrade NOT IN ('A', 'B')  -- Protect high-value customers
                AND (
                    o.DocumentDate IS NULL 
                    OR MAX(o.DocumentDate) <= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                )
                GROUP BY c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales, c.CustomerGrade
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้ารอ',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_intelligence'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Non-premium customer moved to waiting basket', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'grade' => $customer['CustomerGrade'],
                        'customer_status' => $customer['CustomerStatus'],
                        'last_order_date' => $customer['LastOrderDate'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['cart_status_updates'] += $updateCount;
            $this->logger->success("Enhanced existing customer rule processed: $updateCount non-premium customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process enhanced existing customer rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Enhanced Interaction-Based Rules with Intelligence
     */
    private function processInteractionBasedRules() {
        $this->logger->info('Processing Enhanced Interaction-Based Rules');
        
        try {
            $sql = "
                SELECT CustomerCode, CustomerName, ContactAttempts, Sales, CustomerGrade, CustomerTemperature
                FROM customers
                WHERE CustomerStatus = 'ลูกค้าใหม่'
                AND CartStatus = 'กำลังดูแล'
                AND ContactAttempts >= 3
                AND CustomerGrade NOT IN ('A', 'B')  -- Protect high-value customers
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้าแจก',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_intelligence'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Customer moved due to contact attempts (intelligence-aware)', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'grade' => $customer['CustomerGrade'],
                        'temperature' => $customer['CustomerTemperature'],
                        'contact_attempts' => $customer['ContactAttempts'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['cart_status_updates'] += $updateCount;
            $this->logger->success("Enhanced interaction-based rule processed: $updateCount customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process enhanced interaction-based rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Enhanced Freezing Rules with Intelligence Protection
     */
    private function processEnhancedFreezingRules() {
        $this->logger->info('Processing Enhanced Freezing Rules (with Grade A,B protection)');
        
        try {
            $sql = "
                SELECT CustomerCode, CustomerName, AssignmentCount, CustomerTemperature, 
                       CustomerGrade, TotalPurchase, Sales
                FROM customers
                WHERE AssignmentCount >= 3
                AND CartStatus = 'ตะกร้าแจก'
                AND (CustomerTemperature IS NULL OR CustomerTemperature != 'FROZEN')
                AND NOT (CustomerGrade IN ('A', 'B') AND TotalPurchase > 50000)  -- Protect high-value
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                $updateSql = "
                    UPDATE customers 
                    SET CustomerTemperature = 'FROZEN',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_intelligence'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Customer frozen (intelligence-protected)', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'grade' => $customer['CustomerGrade'],
                        'total_purchase' => $customer['TotalPurchase'],
                        'assignment_count' => $customer['AssignmentCount'],
                        'previous_temperature' => $customer['CustomerTemperature'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['frozen_customers'] += $updateCount;
            $this->logger->success("Enhanced freezing rule processed: $updateCount customers frozen (Grade A,B protected)");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process enhanced freezing rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Validate system health and intelligence accuracy
     */
    private function validateSystemHealth() {
        $this->logger->info('Validating system health and intelligence accuracy');
        
        try {
            // Check for Grade mismatches
            $gradeMismatchSql = "
                SELECT COUNT(*) as count
                FROM customers 
                WHERE (TotalPurchase >= 810000 AND CustomerGrade != 'A')
                   OR (TotalPurchase >= 85000 AND TotalPurchase < 810000 AND CustomerGrade != 'B')
                   OR (TotalPurchase >= 2000 AND TotalPurchase < 85000 AND CustomerGrade != 'C')
                   OR (TotalPurchase < 2000 AND CustomerGrade != 'D')
            ";
            
            $stmt = $this->pdo->query($gradeMismatchSql);
            $gradeMismatches = $stmt->fetchColumn();
            
            // Check for high-value frozen customers
            $frozenHighValueSql = "
                SELECT COUNT(*) as count
                FROM customers 
                WHERE CustomerGrade IN ('A', 'B') 
                AND CustomerTemperature = 'FROZEN' 
                AND TotalPurchase > 50000
            ";
            
            $stmt = $this->pdo->query($frozenHighValueSql);
            $frozenHighValue = $stmt->fetchColumn();
            
            $healthStatus = [
                'grade_mismatches' => $gradeMismatches,
                'frozen_high_value' => $frozenHighValue,
                'status' => ($gradeMismatches == 0 && $frozenHighValue == 0) ? 'HEALTHY' : 'NEEDS_ATTENTION'
            ];
            
            $this->logger->info('System health validation completed', $healthStatus);
            
            if ($healthStatus['status'] === 'NEEDS_ATTENTION') {
                $this->logger->warning('System health issues detected', [
                    'grade_mismatches' => $gradeMismatches,
                    'frozen_high_value_customers' => $frozenHighValue,
                    'recommendation' => 'Run customer intelligence fix script'
                ]);
            }
            
        } catch (Exception $e) {
            $this->logger->error('System health validation failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Generate enhanced execution summary
     */
    private function generateSummary() {
        $executionTime = round(microtime(true) - $this->stats['start_time'], 2);
        $this->stats['execution_time'] = $executionTime;
        
        $this->logger->success('Enhanced Automation Execution Summary', [
            'execution_time_seconds' => $executionTime,
            'customers_processed' => $this->stats['processed_customers'],
            'intelligence_updates' => $this->stats['intelligence_updates'],
            'grade_updates' => $this->stats['grade_updates'],
            'temperature_updates' => $this->stats['temperature_updates'],
            'cart_status_updates' => $this->stats['cart_status_updates'],
            'frozen_customers' => $this->stats['frozen_customers'],
            'high_value_unfrozen' => $this->stats['high_value_unfrozen'],
            'total_updates' => $this->stats['intelligence_updates'] + $this->stats['cart_status_updates'] + $this->stats['frozen_customers'],
            'errors' => $this->stats['errors'],
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ]);
        
        $this->storeExecutionStats();
    }
    
    /**
     * Store enhanced execution statistics
     */
    private function storeExecutionStats() {
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'system_logs'");
            
            if ($checkTable->rowCount() > 0) {
                $sql = "
                    INSERT INTO system_logs (
                        LogType, Action, Details, AffectedCount, 
                        ExecutionTime, MemoryUsage, CreatedBy, CreatedDate
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ";
                
                $details = json_encode([
                    'customers_processed' => $this->stats['processed_customers'],
                    'intelligence_updates' => $this->stats['intelligence_updates'],
                    'grade_updates' => $this->stats['grade_updates'],
                    'temperature_updates' => $this->stats['temperature_updates'],
                    'cart_status_updates' => $this->stats['cart_status_updates'],
                    'frozen_customers' => $this->stats['frozen_customers'],
                    'high_value_unfrozen' => $this->stats['high_value_unfrozen'],
                    'errors' => $this->stats['errors']
                ], JSON_UNESCAPED_UNICODE);
                
                $totalUpdates = $this->stats['intelligence_updates'] + 
                               $this->stats['cart_status_updates'] + 
                               $this->stats['frozen_customers'];
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'CRON_EXECUTION_ENHANCED',
                    'AUTO_RULES_DAILY_WITH_INTELLIGENCE',
                    $details,
                    $totalUpdates,
                    $this->stats['execution_time'],
                    round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'auto_rules_cron_enhanced'
                ]);
                
                $this->logger->info('Enhanced execution statistics stored in system_logs');
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to store enhanced execution statistics', ['error' => $e->getMessage()]);
        }
    }
    
    public function getStats() {
        return $this->stats;
    }
}

// ================================
// EXECUTION ENTRY POINT
// ================================
if (php_sapi_name() === 'cli' || isset($_SERVER['HTTP_X_CRON_AUTH'])) {
    try {
        $automation = new EnhancedLeadManagementAutomation();
        $automation->execute();
        
        $stats = $automation->getStats();
        
        if ($stats['errors'] > 0) {
            echo "Enhanced cron job completed with errors. Check logs for details.\n";
            exit(1);
        } else {
            echo "Enhanced cron job completed successfully.\n";
            echo "Stats: " . json_encode($stats, JSON_UNESCAPED_UNICODE) . "\n";
            exit(0);
        }
        
    } catch (Exception $e) {
        echo "Fatal error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>