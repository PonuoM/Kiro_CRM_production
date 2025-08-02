<?php
/**
 * Lead Management Automation Rules - Fixed Version
 * แก้ไข SQL Error และ Permission Issues
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
ini_set('max_execution_time', 300);

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../config/database.php';

// ================================
// FIXED LOGGING SYSTEM
// ================================
class FixedCronLogger {
    private $logFile;
    private $canWriteFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/../logs/cron_auto_rules.log';
        
        // ตรวจสอบว่าสามารถเขียนไฟล์ได้หรือไม่
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $this->canWriteFile = is_writable($logDir) || is_writable($this->logFile);
        
        if (!$this->canWriteFile) {
            // ใช้ error_log แทน
            error_log("Cannot write to log file: {$this->logFile}");
        }
    }
    
    public function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        // ลองเขียนไฟล์ แต่ไม่ให้ error หยุดการทำงาน
        if ($this->canWriteFile) {
            @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } else {
            // ใช้ error_log แทน
            error_log("[$level] $message");
        }
        
        // แสดงใน console หาก CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry;
        }
    }
    
    public function info($message, $context = []) { $this->log('INFO', $message, $context); }
    public function warning($message, $context = []) { $this->log('WARNING', $message, $context); }
    public function error($message, $context = []) { $this->log('ERROR', $message, $context); }
    public function success($message, $context = []) { $this->log('SUCCESS', $message, $context); }
}

// ================================
// FIXED AUTOMATION CLASS
// ================================
class FixedLeadManagementAutomation {
    private $pdo;
    private $logger;
    private $stats;
    
    public function __construct() {
        $this->logger = new FixedCronLogger();
        $this->stats = [
            'processed_customers' => 0,
            'time_based_updates' => 0,
            'interaction_based_updates' => 0,
            'frozen_customers' => 0,
            'errors' => 0,
            'start_time' => microtime(true)
        ];
        
        $this->initializeDatabase();
        $this->logger->info('Fixed Lead Management Automation initialized');
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
    
    public function execute() {
        $this->logger->info('Starting Fixed Lead Management Automation execution');
        
        try {
            // Step 1: Process Time-Based Rules
            $this->processTimeBasedRules();
            
            // Step 2: Process Interaction-Based Rules
            $this->processInteractionBasedRules();
            
            // Step 3: Process Freezing Rules
            $this->processFreezingRules();
            
            // Step 4: Generate Summary
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
    
    private function processTimeBasedRules() {
        $this->logger->info('Processing Fixed Time-Based Rules');
        
        // Rule 1: New customers without call logs for 30 days
        $this->processNewCustomerTimeRule();
        
        // Rule 2: Existing customers without orders for 3 months (FIXED)
        $this->processExistingCustomerTimeRuleFixed();
    }
    
    private function processNewCustomerTimeRule() {
        $this->logger->info('Processing: New customer 30-day rule');
        
        try {
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
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้าแจก',
                        Sales = NULL,
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_fixed'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('New customer moved to distribution basket', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName']
                    ]);
                }
            }
            
            $this->stats['time_based_updates'] += $updateCount;
            $this->logger->success("New customer time rule processed: $updateCount customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process new customer time rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function processExistingCustomerTimeRuleFixed() {
        $this->logger->info('Processing: Fixed Existing customer 3-month rule');
        
        try {
            // FIXED SQL: แยก subquery เพื่อหลีกเลี่ยง "Invalid use of group function"
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales
                FROM customers c
                WHERE c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
                AND c.CartStatus = 'กำลังดูแล'
                AND (
                    NOT EXISTS (SELECT 1 FROM orders o WHERE o.CustomerCode = c.CustomerCode)
                    OR 
                    c.CustomerCode IN (
                        SELECT sub_c.CustomerCode 
                        FROM customers sub_c
                        LEFT JOIN orders sub_o ON sub_c.CustomerCode = sub_o.CustomerCode
                        WHERE sub_c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
                        AND sub_c.CartStatus = 'กำลังดูแล'
                        GROUP BY sub_c.CustomerCode
                        HAVING MAX(sub_o.DocumentDate) <= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                    )
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
                    SET CartStatus = 'ตะกร้ารอ',
                        Sales = NULL,
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_fixed'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Existing customer moved to waiting basket', [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'customer_status' => $customer['CustomerStatus']
                    ]);
                }
            }
            
            $this->stats['time_based_updates'] += $updateCount;
            $this->logger->success("Fixed existing customer time rule processed: $updateCount customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process existing customer time rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function processInteractionBasedRules() {
        $this->logger->info('Processing Interaction-Based Rules');
        
        try {
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
                $updateSql = "
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้าแจก',
                        Sales = NULL,
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_fixed'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Customer moved due to contact attempts', [
                        'customer_code' => $customer['CustomerCode'],
                        'contact_attempts' => $customer['ContactAttempts']
                    ]);
                }
            }
            
            $this->stats['interaction_based_updates'] += $updateCount;
            $this->logger->success("Interaction-based rule processed: $updateCount customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->logger->error('Failed to process interaction-based rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function processFreezingRules() {
        $this->logger->info('Processing Freezing Rules');
        
        try {
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
                $updateSql = "
                    UPDATE customers 
                    SET CustomerTemperature = 'FROZEN',
                        ModifiedDate = NOW(),
                        ModifiedBy = 'auto_rules_cron_fixed'
                    WHERE CustomerCode = ?
                ";
                
                $updateStmt = $this->pdo->prepare($updateSql);
                
                if ($updateStmt->execute([$customer['CustomerCode']])) {
                    $updateCount++;
                    $this->logger->info('Customer frozen', [
                        'customer_code' => $customer['CustomerCode'],
                        'assignment_count' => $customer['AssignmentCount']
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
    
    private function generateSummary() {
        $executionTime = round(microtime(true) - $this->stats['start_time'], 2);
        $this->stats['execution_time'] = $executionTime;
        
        $this->logger->info('Execution Summary', [
            'execution_time_seconds' => $executionTime,
            'time_based_updates' => $this->stats['time_based_updates'],
            'interaction_based_updates' => $this->stats['interaction_based_updates'],
            'frozen_customers' => $this->stats['frozen_customers'],
            'total_updates' => $this->stats['time_based_updates'] + $this->stats['interaction_based_updates'] + $this->stats['frozen_customers'],
            'errors' => $this->stats['errors']
        ]);
        
        // Store in system_logs (ถ้าทำได้)
        $this->storeExecutionStats();
    }
    
    private function storeExecutionStats() {
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'system_logs'");
            
            if ($checkTable->rowCount() > 0) {
                // ตรวจสอบ structure ของ system_logs
                $columns = $this->pdo->query("SHOW COLUMNS FROM system_logs")->fetchAll(PDO::FETCH_COLUMN);
                
                // ใช้ columns ที่มีจริง
                if (in_array('log_type', $columns) && in_array('message', $columns)) {
                    $sql = "INSERT INTO system_logs (log_type, message, details, created_at) VALUES (?, ?, ?, NOW())";
                    
                    $details = json_encode([
                        'time_based_updates' => $this->stats['time_based_updates'],
                        'interaction_based_updates' => $this->stats['interaction_based_updates'],
                        'frozen_customers' => $this->stats['frozen_customers'],
                        'errors' => $this->stats['errors'],
                        'execution_time' => $this->stats['execution_time']
                    ]);
                    
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        'CRON_EXECUTION',
                        'AUTO_RULES_DAILY_FIXED',
                        $details
                    ]);
                    
                    $this->logger->info('Execution statistics stored in system_logs');
                }
            }
        } catch (Exception $e) {
            $this->logger->warning('Failed to store execution statistics', ['error' => $e->getMessage()]);
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
        $automation = new FixedLeadManagementAutomation();
        $automation->execute();
        
        $stats = $automation->getStats();
        
        if ($stats['errors'] > 0) {
            echo "Fixed Cron job completed with errors. Check logs for details.\n";
            exit(1);
        } else {
            echo "Fixed Cron job completed successfully.\n";
            echo "Summary: {$stats['time_based_updates']} time-based, {$stats['interaction_based_updates']} interaction-based, {$stats['frozen_customers']} frozen\n";
            exit(0);
        }
        
    } catch (Exception $e) {
        echo "Fatal error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>