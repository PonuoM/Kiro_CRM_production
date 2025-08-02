<?php
/**
 * Auto Rules with Customer Activity Logging
 * Auto Rules ที่บันทึก Activity Log เพื่อทวนสอบได้
 */

// Security: CLI Only Access
if (php_sapi_name() !== 'cli' && !isset($_SERVER['HTTP_X_CRON_AUTH'])) {
    http_response_code(403);
    die("Access Denied: This script can only be executed via CLI or authorized cron.");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/CustomerActivityLogger.php';

class AutoRulesWithActivityLog {
    private $pdo;
    private $activityLogger;
    private $logger;
    private $stats;
    
    public function __construct() {
        $this->initializeDatabase();
        $this->activityLogger = new CustomerActivityLogger($this->pdo);
        $this->stats = [
            'processed_customers' => 0,
            'time_based_updates' => 0,
            'interaction_based_updates' => 0,
            'frozen_customers' => 0,
            'errors' => 0,
            'start_time' => microtime(true),
            'detailed_changes' => []
        ];
        
        $this->log('INFO', 'Auto Rules with Activity Logging initialized');
    }
    
    private function initializeDatabase() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            $this->log('ERROR', 'Database connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function log($level, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message";
        
        if (!empty($context)) {
            $logEntry .= ' | ' . json_encode($context);
        }
        
        // Output to console if CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry . PHP_EOL;
        }
        
        // Log to file
        $logFile = __DIR__ . '/../logs/cron_auto_rules_activity.log';
        @file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    
    public function execute() {
        $this->log('INFO', 'Starting Auto Rules execution with Activity Logging');
        
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
            $this->log('ERROR', 'Auto Rules execution failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function processTimeBasedRules() {
        $this->log('INFO', 'Processing Time-Based Rules');
        
        // Rule 1: New customers without call logs for 30 days
        $this->processNewCustomerTimeRule();
        
        // Rule 2: Existing customers without orders for 90 days (FIXED LOGIC)
        $this->processExistingCustomerTimeRule();
    }
    
    private function processNewCustomerTimeRule() {
        $this->log('INFO', 'Processing: New customer 30-day rule');
        
        try {
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.AssignDate, c.Sales, c.CartStatus
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
            
            foreach ($customers as $customer) {
                $this->pdo->beginTransaction();
                
                try {
                    // อัปเดตลูกค้า
                    $updateSql = "
                        UPDATE customers 
                        SET CartStatus = 'ตะกร้าแจก',
                            Sales = NULL,
                            ModifiedDate = NOW(),
                            ModifiedBy = 'auto_rules_with_logging'
                        WHERE CustomerCode = ?
                    ";
                    
                    $updateStmt = $this->pdo->prepare($updateSql);
                    $updateResult = $updateStmt->execute([$customer['CustomerCode']]);
                    
                    if ($updateResult) {
                        // บันทึก Activity Log - Cart Status Change
                        $this->activityLogger->logCartStatusChange(
                            $customer['CustomerCode'],
                            $customer['CustomerName'],
                            $customer['CartStatus'],
                            'ตะกร้าแจก',
                            'auto_rules_with_logging',
                            'ลูกค้าใหม่เลย 30 วัน ไม่มี Call Logs',
                            'new_customer_30_day_rule'
                        );
                        
                        // บันทึก Activity Log - Sales Removal (ถ้ามี)
                        if ($customer['Sales']) {
                            $this->activityLogger->logSalesAssignment(
                                $customer['CustomerCode'],
                                $customer['CustomerName'],
                                $customer['Sales'],
                                null,
                                'auto_rules_with_logging',
                                'ลบ Sales เมื่อย้ายไปตะกร้าแจก (30 วันไม่มี Call Logs)'
                            );
                        }
                        
                        $this->stats['time_based_updates']++;
                        $this->stats['detailed_changes'][] = [
                            'customer_code' => $customer['CustomerCode'],
                            'customer_name' => $customer['CustomerName'],
                            'rule' => 'new_customer_30_day',
                            'action' => 'moved_to_distribution_basket',
                            'old_cart_status' => $customer['CartStatus'],
                            'new_cart_status' => 'ตะกร้าแจก',
                            'old_sales' => $customer['Sales'],
                            'new_sales' => null
                        ];
                        
                        $this->pdo->commit();
                        
                        $this->log('INFO', 'New customer moved to distribution basket', [
                            'customer_code' => $customer['CustomerCode'],
                            'customer_name' => $customer['CustomerName'],
                            'old_sales' => $customer['Sales']
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $this->pdo->rollback();
                    $this->stats['errors']++;
                    $this->log('ERROR', 'Failed to process new customer: ' . $e->getMessage(), [
                        'customer_code' => $customer['CustomerCode']
                    ]);
                }
            }
            
            $this->log('SUCCESS', "New customer time rule processed: {$this->stats['time_based_updates']} customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->log('ERROR', 'Failed to process new customer time rule: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function processExistingCustomerTimeRule() {
        $this->log('INFO', 'Processing: Existing customer 90-day rule (FIXED LOGIC)');
        
        try {
            // FIXED SQL: หาลูกค้าติดตาม/เก่า ที่ไม่มี Orders หรือ Order สุดท้ายเลย 90 วัน
            $sql = "
                SELECT c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales, c.CartStatus,
                       MAX(o.DocumentDate) as LastOrderDate
                FROM customers c
                LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode
                WHERE c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
                AND c.CartStatus = 'กำลังดูแล'
                GROUP BY c.CustomerCode, c.CustomerName, c.CustomerStatus, c.Sales, c.CartStatus
                HAVING (
                    LastOrderDate IS NULL OR 
                    LastOrderDate <= DATE_SUB(NOW(), INTERVAL 90 DAY)
                )
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customers as $customer) {
                $this->pdo->beginTransaction();
                
                try {
                    // อัปเดตลูกค้า
                    $updateSql = "
                        UPDATE customers 
                        SET CartStatus = 'ตะกร้ารอ',
                            Sales = NULL,
                            ModifiedDate = NOW(),
                            ModifiedBy = 'auto_rules_with_logging'
                        WHERE CustomerCode = ?
                    ";
                    
                    $updateStmt = $this->pdo->prepare($updateSql);
                    $updateResult = $updateStmt->execute([$customer['CustomerCode']]);
                    
                    if ($updateResult) {
                        $reason = $customer['LastOrderDate'] 
                            ? "ไม่มี Orders เลย 90 วัน (Order สุดท้าย: {$customer['LastOrderDate']})"
                            : "ไม่เคยมี Orders เลย";
                        
                        // บันทึก Activity Log - Cart Status Change
                        $this->activityLogger->logCartStatusChange(
                            $customer['CustomerCode'],
                            $customer['CustomerName'],
                            $customer['CartStatus'],
                            'ตะกร้ารอ',
                            'auto_rules_with_logging',
                            $reason,
                            'existing_customer_90_day_rule'
                        );
                        
                        // บันทึก Activity Log - Sales Removal (ถ้ามี)
                        if ($customer['Sales']) {
                            $this->activityLogger->logSalesAssignment(
                                $customer['CustomerCode'],
                                $customer['CustomerName'],
                                $customer['Sales'],
                                null,
                                'auto_rules_with_logging',
                                'ลบ Sales เมื่อย้ายไปตะกร้ารอ (' . $reason . ')'
                            );
                        }
                        
                        $this->stats['time_based_updates']++;
                        $this->stats['detailed_changes'][] = [
                            'customer_code' => $customer['CustomerCode'],
                            'customer_name' => $customer['CustomerName'],
                            'rule' => 'existing_customer_90_day',
                            'action' => 'moved_to_waiting_basket',
                            'old_cart_status' => $customer['CartStatus'],
                            'new_cart_status' => 'ตะกร้ารอ',
                            'old_sales' => $customer['Sales'],
                            'new_sales' => null,
                            'last_order_date' => $customer['LastOrderDate']
                        ];
                        
                        $this->pdo->commit();
                        
                        $this->log('INFO', 'Existing customer moved to waiting basket', [
                            'customer_code' => $customer['CustomerCode'],
                            'customer_name' => $customer['CustomerName'],
                            'customer_status' => $customer['CustomerStatus'],
                            'old_sales' => $customer['Sales'],
                            'last_order_date' => $customer['LastOrderDate']
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $this->pdo->rollback();
                    $this->stats['errors']++;
                    $this->log('ERROR', 'Failed to process existing customer: ' . $e->getMessage(), [
                        'customer_code' => $customer['CustomerCode']
                    ]);
                }
            }
            
            $this->log('SUCCESS', "Existing customer time rule processed: additional customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->log('ERROR', 'Failed to process existing customer time rule: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function processInteractionBasedRules() {
        $this->log('INFO', 'Processing Interaction-Based Rules');
        
        try {
            $sql = "
                SELECT CustomerCode, CustomerName, ContactAttempts, Sales, CartStatus
                FROM customers
                WHERE CustomerStatus = 'ลูกค้าใหม่'
                AND CartStatus = 'กำลังดูแล'
                AND ContactAttempts >= 3
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customers as $customer) {
                $this->pdo->beginTransaction();
                
                try {
                    $updateSql = "
                        UPDATE customers 
                        SET CartStatus = 'ตะกร้าแจก',
                            Sales = NULL,
                            ModifiedDate = NOW(),
                            ModifiedBy = 'auto_rules_with_logging'
                        WHERE CustomerCode = ?
                    ";
                    
                    $updateStmt = $this->pdo->prepare($updateSql);
                    $updateResult = $updateStmt->execute([$customer['CustomerCode']]);
                    
                    if ($updateResult) {
                        // บันทึก Activity Log - Cart Status Change
                        $this->activityLogger->logCartStatusChange(
                            $customer['CustomerCode'],
                            $customer['CustomerName'],
                            $customer['CartStatus'],
                            'ตะกร้าแจก',
                            'auto_rules_with_logging',
                            "ติดต่อแล้ว {$customer['ContactAttempts']} ครั้ง",
                            'contact_attempts_rule'
                        );
                        
                        // บันทึก Activity Log - Sales Removal
                        if ($customer['Sales']) {
                            $this->activityLogger->logSalesAssignment(
                                $customer['CustomerCode'],
                                $customer['CustomerName'],
                                $customer['Sales'],
                                null,
                                'auto_rules_with_logging',
                                "ลบ Sales เมื่อย้ายไปตะกร้าแจก (ติดต่อแล้ว {$customer['ContactAttempts']} ครั้ง)"
                            );
                        }
                        
                        $this->stats['interaction_based_updates']++;
                        $this->stats['detailed_changes'][] = [
                            'customer_code' => $customer['CustomerCode'],
                            'customer_name' => $customer['CustomerName'],
                            'rule' => 'contact_attempts_3_times',
                            'action' => 'moved_to_distribution_basket',
                            'old_cart_status' => $customer['CartStatus'],
                            'new_cart_status' => 'ตะกร้าแจก',
                            'old_sales' => $customer['Sales'],
                            'new_sales' => null,
                            'contact_attempts' => $customer['ContactAttempts']
                        ];
                        
                        $this->pdo->commit();
                        
                        $this->log('INFO', 'Customer moved due to contact attempts', [
                            'customer_code' => $customer['CustomerCode'],
                            'contact_attempts' => $customer['ContactAttempts']
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $this->pdo->rollback();
                    $this->stats['errors']++;
                    $this->log('ERROR', 'Failed to process interaction-based customer: ' . $e->getMessage(), [
                        'customer_code' => $customer['CustomerCode']
                    ]);
                }
            }
            
            $this->log('SUCCESS', "Interaction-based rule processed: {$this->stats['interaction_based_updates']} customers moved");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->log('ERROR', 'Failed to process interaction-based rules: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function processFreezingRules() {
        $this->log('INFO', 'Processing Freezing Rules');
        
        try {
            $sql = "
                SELECT CustomerCode, CustomerName, AssignmentCount, CustomerTemperature
                FROM customers
                WHERE AssignmentCount >= 3
                AND CartStatus = 'ตะกร้าแจก'
                AND (CustomerTemperature IS NULL OR CustomerTemperature != 'FROZEN')
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($customers as $customer) {
                $this->pdo->beginTransaction();
                
                try {
                    $updateSql = "
                        UPDATE customers 
                        SET CustomerTemperature = 'FROZEN',
                            ModifiedDate = NOW(),
                            ModifiedBy = 'auto_rules_with_logging'
                        WHERE CustomerCode = ?
                    ";
                    
                    $updateStmt = $this->pdo->prepare($updateSql);
                    $updateResult = $updateStmt->execute([$customer['CustomerCode']]);
                    
                    if ($updateResult) {
                        // บันทึก Activity Log - Temperature Change
                        $this->activityLogger->logTemperatureChange(
                            $customer['CustomerCode'],
                            $customer['CustomerName'],
                            $customer['CustomerTemperature'],
                            'FROZEN',
                            'auto_rules_with_logging',
                            "แจกแล้ว {$customer['AssignmentCount']} ครั้ง ไม่มี Orders"
                        );
                        
                        $this->stats['frozen_customers']++;
                        $this->stats['detailed_changes'][] = [
                            'customer_code' => $customer['CustomerCode'],
                            'customer_name' => $customer['CustomerName'],
                            'rule' => 'assignment_count_3_times',
                            'action' => 'frozen_temperature',
                            'old_temperature' => $customer['CustomerTemperature'],
                            'new_temperature' => 'FROZEN',
                            'assignment_count' => $customer['AssignmentCount']
                        ];
                        
                        $this->pdo->commit();
                        
                        $this->log('INFO', 'Customer frozen', [
                            'customer_code' => $customer['CustomerCode'],
                            'assignment_count' => $customer['AssignmentCount']
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $this->pdo->rollback();
                    $this->stats['errors']++;
                    $this->log('ERROR', 'Failed to freeze customer: ' . $e->getMessage(), [
                        'customer_code' => $customer['CustomerCode']
                    ]);
                }
            }
            
            $this->log('SUCCESS', "Freezing rule processed: {$this->stats['frozen_customers']} customers frozen");
            
        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->log('ERROR', 'Failed to process freezing rules: ' . $e->getMessage());
            throw $e;
        }
    }
    
    private function generateSummary() {
        $executionTime = round(microtime(true) - $this->stats['start_time'], 2);
        $this->stats['execution_time'] = $executionTime;
        
        $totalChanges = count($this->stats['detailed_changes']);
        
        $this->log('INFO', 'Auto Rules Execution Summary', [
            'execution_time_seconds' => $executionTime,
            'time_based_updates' => $this->stats['time_based_updates'],
            'interaction_based_updates' => $this->stats['interaction_based_updates'],
            'frozen_customers' => $this->stats['frozen_customers'],
            'total_changes' => $totalChanges,
            'errors' => $this->stats['errors']
        ]);
        
        // Store in system_logs
        $this->storeExecutionStats();
        
        // Store detailed changes in Activity Log summary
        if ($totalChanges > 0) {
            $this->storeDetailedChangesSummary();
        }
    }
    
    private function storeExecutionStats() {
        try {
            $checkTable = $this->pdo->query("SHOW TABLES LIKE 'system_logs'");
            
            if ($checkTable->rowCount() > 0) {
                $sql = "INSERT INTO system_logs (log_type, message, details, created_at) VALUES (?, ?, ?, NOW())";
                
                $details = json_encode([
                    'time_based_updates' => $this->stats['time_based_updates'],
                    'interaction_based_updates' => $this->stats['interaction_based_updates'],
                    'frozen_customers' => $this->stats['frozen_customers'],
                    'total_changes' => count($this->stats['detailed_changes']),
                    'errors' => $this->stats['errors'],
                    'execution_time' => $this->stats['execution_time']
                ]);
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'CRON_EXECUTION_WITH_ACTIVITY_LOG',
                    'AUTO_RULES_WITH_DETAILED_TRACKING',
                    $details
                ]);
                
                $this->log('INFO', 'Execution statistics stored in system_logs');
            }
        } catch (Exception $e) {
            $this->log('WARNING', 'Failed to store execution statistics: ' . $e->getMessage());
        }
    }
    
    private function storeDetailedChangesSummary() {
        try {
            // Store summary of changes in customer_activity_log
            $summaryData = [
                'total_changes' => count($this->stats['detailed_changes']),
                'rules_applied' => array_unique(array_column($this->stats['detailed_changes'], 'rule')),
                'execution_time' => $this->stats['execution_time'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $sql = "
                INSERT INTO customer_activity_log (
                    customer_code, customer_name, activity_type, field_changed,
                    old_value, new_value, changed_from, changed_to, reason,
                    changed_by, automation_rule, additional_data
                ) VALUES (
                    'SYSTEM_SUMMARY', 'Auto Rules Execution Summary', 'SYSTEM_UPDATE', 'MULTIPLE',
                    NULL, NULL, 'Auto Rules Start', 'Auto Rules Complete', 
                    'Auto Rules execution completed with detailed activity logging',
                    'auto_rules_with_logging', 'execution_summary', ?
                )
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([json_encode($summaryData)]);
            
            $this->log('INFO', 'Detailed changes summary stored in customer_activity_log');
            
        } catch (Exception $e) {
            $this->log('WARNING', 'Failed to store detailed changes summary: ' . $e->getMessage());
        }
    }
    
    public function getStats() {
        return $this->stats;
    }
}

// Execution Entry Point
if (php_sapi_name() === 'cli' || isset($_SERVER['HTTP_X_CRON_AUTH'])) {
    try {
        $automation = new AutoRulesWithActivityLog();
        $automation->execute();
        
        $stats = $automation->getStats();
        
        if ($stats['errors'] > 0) {
            echo "Auto Rules with Activity Logging completed with errors. Check logs for details.\n";
            exit(1);
        } else {
            echo "Auto Rules with Activity Logging completed successfully.\n";
            echo "Summary: {$stats['time_based_updates']} time-based, {$stats['interaction_based_updates']} interaction-based, {$stats['frozen_customers']} frozen\n";
            echo "Total detailed changes logged: " . count($stats['detailed_changes']) . "\n";
            exit(0);
        }
        
    } catch (Exception $e) {
        echo "Fatal error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>