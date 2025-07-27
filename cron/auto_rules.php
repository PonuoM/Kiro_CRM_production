<?php
/**
 * Lead Management Automation Rules - Cron Job
 * Story 1.2: Develop Lead Management Cron Job
 * 
 * This script implements the Hybrid Logic and Freezing Rules 
 * for automated lead management as defined in PRD.md
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
ini_set('max_execution_time', 300); // 5 minutes max

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Include dependencies
require_once __DIR__ . '/../config/database.php';

// ================================
// LOGGING SYSTEM
// ================================
class CronLogger {
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/cron_auto_rules.log';
        
        // Ensure logs directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
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
// MAIN AUTOMATION CLASS
// ================================
class LeadManagementAutomation {
    private $pdo;
    private $logger;
    private $stats;
    
    public function __construct() {
        $this->logger = new CronLogger();
        $this->stats = [
            'processed_customers' => 0,
            'time_based_updates' => 0,
            'interaction_based_updates' => 0,
            'frozen_customers' => 0,
            'errors' => 0,
            'start_time' => microtime(true)
        ];
        
        $this->initializeDatabase();
        $this->logger->info('Lead Management Automation initialized');
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
        $this->logger->info('Starting Lead Management Automation execution');
        
        try {
            // Step 1: Process Time-Based Hybrid Logic Rules
            $this->processTimeBasedRules();
            
            // Step 2: Process Interaction-Based Hybrid Logic Rules
            $this->processInteractionBasedRules();
            
            // Step 3: Process Freezing Rules
            $this->processFreezingRules();
            
            // Step 4: Generate execution summary
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
     * AC2: Process Time-Based Hybrid Logic Rules
     * 
     * Rule 1: ลูกค้าใหม่ - ไม่มี call_logs ใหม่ภายใน 30 วัน → CartStatus = 'ตะกร้าแจก'
     * Rule 2: ลูกค้าติดตาม/เก่า - ไม่มี orders ใหม่ภายใน 3 เดือน → CartStatus = 'ตะกร้ารอ'
     */
    private function processTimeBasedRules() {
        $this->logger->info('Processing Time-Based Hybrid Logic Rules');
        
        // Rule 1: New customers without call logs for 30 days
        $this->processNewCustomerTimeRule();
        
        // Rule 2: Existing customers without orders for 3 months
        $this->processExistingCustomerTimeRule();
    }
    
    private function processNewCustomerTimeRule() {
        $this->logger->info('Processing: New customer 30-day rule');
        
        try {
            // Find new customers assigned >30 days ago with no recent call logs
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.AssignDate, c.Sales
                FROM customers c
                WHERE c.CustomerStatus = 'ลูกค้าใหม่'
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
                // Update CartStatus to 'ตะกร้าแจก'
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้าแจก',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('New customer moved to distribution basket', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'assign_date' => $customer['AssignDate'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['time_based_updates'] += $updateCount;
            $this->logger->success("New customer time rule processed: $updateCount customers moved to distribution basket");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process new customer time rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function processExistingCustomerTimeRule() {
        $this->logger->info('Processing: Existing customer 3-month rule');
        
        try {
            // Find existing customers with no orders for 3 months
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales,
                       MAX(o.DocumentDate) as LastOrderDate
                FROM customers c
                LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode
                WHERE c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
                AND c.CartStatus = 'กำลังดูแล'
                AND (
                    o.DocumentDate IS NULL 
                    OR MAX(o.DocumentDate) <= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                )
                GROUP BY c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                // Update CartStatus to 'ตะกร้ารอ'
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้ารอ',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Existing customer moved to waiting basket', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'customer_status' => $customer['CustomerStatus'],
                        'last_order_date' => $customer['LastOrderDate'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['time_based_updates'] += $updateCount;
            $this->logger->success("Existing customer time rule processed: $updateCount customers moved to waiting basket");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process existing customer time rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * AC2: Process Interaction-Based Hybrid Logic Rules
     * 
     * Rule: ลูกค้าใหม่ - ContactAttempts >= 3 และยัง CustomerStatus = 'ลูกค้าใหม่' → CartStatus = 'ตะกร้าแจก'
     */
    private function processInteractionBasedRules() {
        $this->logger->info('Processing Interaction-Based Hybrid Logic Rules');
        
        try {
            // Find new customers with 3+ contact attempts still in new status
            $sql = "
                SELECT CustomerCode, CustomerName, ContactAttempts, Sales
                FROM customers
                WHERE CustomerStatus = 'ลูกค้าใหม่'
                AND CartStatus = 'กำลังดูแล'
                AND ContactAttempts >= 3
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                // Update CartStatus to 'ตะกร้าแจก'
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้าแจก',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Customer moved to distribution basket due to contact attempts', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'contact_attempts' => $customer['ContactAttempts'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['interaction_based_updates'] += $updateCount;
            $this->logger->success("Interaction-based rule processed: $updateCount customers moved to distribution basket");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process interaction-based rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * AC3: Process Freezing Rules
     * 
     * Rule: AssignmentCount >= 3 และถูกดึงคืนอีกครั้ง → CustomerTemperature = 'FROZEN'
     * Note: ลูกค้าที่ FROZEN จะไม่แสดงใน "ตะกร้าแจก" เป็นเวลา 6 เดือน
     */
    private function processFreezingRules() {
        $this->logger->info('Processing Freezing Rules');
        
        try {
            // Find customers with AssignmentCount >= 3 that are back in distribution basket
            $sql = "
                SELECT CustomerCode, CustomerName, AssignmentCount, CustomerTemperature, Sales
                FROM customers
                WHERE AssignmentCount >= 3
                AND CartStatus = 'ตะกร้าแจก'
                AND (CustomerTemperature IS NULL OR CustomerTemperature != 'FROZEN')
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $updateCount = 0;
            
            foreach ($customers as $customer) {
                // Update CustomerTemperature to 'FROZEN'
                $updateSql = "
                    UPDATE customers 
                    SET CustomerTemperature = 'FROZEN',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Customer frozen due to high assignment count', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'assignment_count' => $customer['AssignmentCount'],
                        'previous_temperature' => $customer['CustomerTemperature'],
                        'sales' => $customer['Sales']
                    ]);
                }
            }
            
            $this->stats['frozen_customers'] += $updateCount;
            $this->logger->success("Freezing rule processed: $updateCount customers frozen");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process freezing rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Generate execution summary and statistics
     */
    private function generateSummary() {
        $executionTime = round(microtime(true) - $this->stats['start_time'], 2);
        $this->stats['execution_time'] = $executionTime;
        
        $this->logger->info('Execution Summary', [
            'execution_time_seconds' => $executionTime,
            'time_based_updates' => $this->stats['time_based_updates'],
            'interaction_based_updates' => $this->stats['interaction_based_updates'],
            'frozen_customers' => $this->stats['frozen_customers'],
            'total_updates' => $this->stats['time_based_updates'] + $this->stats['interaction_based_updates'] + $this->stats['frozen_customers'],
            'errors' => $this->stats['errors'],
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ]);
        
        // Store execution statistics in database
        $this->storeExecutionStats();
    }
    
    /**
     * Store execution statistics for monitoring
     */
    private function storeExecutionStats() {
        try {
            // Check if system_logs table exists
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'system_logs'");
            
            if ($checkTable->rowCount() > 0) {
                $sql = "
                    INSERT INTO system_logs (
                        LogType, Action, Details, AffectedCount, 
                        ExecutionTime, MemoryUsage, CreatedBy, CreatedDate
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ";
                
                $details = json_encode([
                    'time_based_updates' => $this->stats['time_based_updates'],
                    'interaction_based_updates' => $this->stats['interaction_based_updates'],
                    'frozen_customers' => $this->stats['frozen_customers'],
                    'errors' => $this->stats['errors']
                ]);
                
                $totalUpdates = $this->stats['time_based_updates'] + 
                               $this->stats['interaction_based_updates'] + 
                               $this->stats['frozen_customers'];
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'CRON_EXECUTION',
                    'AUTO_RULES_DAILY',
                    $details,
                    $totalUpdates,
                    $this->stats['execution_time'],
                    round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'auto_rules_cron'
                ]);
                
                $this->logger->info('Execution statistics stored in system_logs');
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to store execution statistics', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get execution statistics
     */
    public function getStats() {
        return $this->stats;
    }
}

// ================================
// EXECUTION ENTRY POINT
// ================================
if (php_sapi_name() === 'cli' || isset($_SERVER['HTTP_X_CRON_AUTH'])) {
    try {
        $automation = new LeadManagementAutomation();
        $automation->execute();
        
        $stats = $automation->getStats();
        
        // Exit with appropriate code
        if ($stats['errors'] > 0) {
            echo "Cron job completed with errors. Check logs for details.\n";
            exit(1);
        } else {
            echo "Cron job completed successfully.\n";
            exit(0);
        }
        
    } catch (Exception $e) {
        echo "Fatal error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>