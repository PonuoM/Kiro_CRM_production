<?php
/**
 * Setup CartStatus Monitoring System
 * Automated monitoring and alerting for data consistency
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Create monitoring log table
    $createLogTable = "
    CREATE TABLE IF NOT EXISTS cartstatus_monitoring_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        check_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        total_customers INT,
        inconsistent_count INT,
        type1_issues INT COMMENT 'Has Sales but CartStatus not assigned',
        type2_issues INT COMMENT 'No Sales but CartStatus is assigned',
        auto_fixed_count INT DEFAULT 0,
        status ENUM('clean', 'issues_found', 'auto_fixed', 'manual_required') DEFAULT 'clean',
        details JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB COMMENT='CartStatus consistency monitoring log';
    ";
    
    $pdo->exec($createLogTable);
    $results[] = "✅ Created monitoring log table";
    
    // 2. Create monitoring function
    $monitoringScript = '<?php
/**
 * CartStatus Monitoring Script
 * Run this script regularly to monitor and maintain data consistency
 */

require_once dirname(__FILE__) . "/config/database.php";

function checkCartStatusConsistency($autoFix = false) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Check for inconsistencies
        $checkSQL = "
            SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN Sales IS NOT NULL AND Sales != \'\' AND CartStatus != \'ลูกค้าแจกแล้ว\' THEN 1 END) as type1_issues,
                COUNT(CASE WHEN (Sales IS NULL OR Sales = \'\') AND CartStatus = \'ลูกค้าแจกแล้ว\' THEN 1 END) as type2_issues
            FROM customers
        ";
        
        $stmt = $pdo->prepare($checkSQL);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $inconsistent_count = $stats["type1_issues"] + $stats["type2_issues"];
        $status = $inconsistent_count > 0 ? "issues_found" : "clean";
        $auto_fixed_count = 0;
        
        // Auto-fix if requested and issues found
        if ($autoFix && $inconsistent_count > 0) {
            require_once dirname(__FILE__) . "/includes/CartStatusAutoFix.php";
            $fixResult = autoFixCartStatus(false);
            
            if ($fixResult["status"] === "success") {
                $auto_fixed_count = $fixResult["fixed_count"];
                $status = $auto_fixed_count > 0 ? "auto_fixed" : "manual_required";
            }
        }
        
        // Log the check
        $logSQL = "
            INSERT INTO cartstatus_monitoring_log 
            (total_customers, inconsistent_count, type1_issues, type2_issues, auto_fixed_count, status, details) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $details = json_encode([
            "timestamp" => date("Y-m-d H:i:s"),
            "auto_fix_enabled" => $autoFix,
            "auto_fix_result" => $autoFix ? ($fixResult ?? null) : null
        ]);
        
        $stmt = $pdo->prepare($logSQL);
        $stmt->execute([
            $stats["total_customers"],
            $inconsistent_count,
            $stats["type1_issues"], 
            $stats["type2_issues"],
            $auto_fixed_count,
            $status,
            $details
        ]);
        
        return [
            "status" => "success",
            "consistency_status" => $status,
            "total_customers" => $stats["total_customers"],
            "inconsistent_count" => $inconsistent_count,
            "type1_issues" => $stats["type1_issues"],
            "type2_issues" => $stats["type2_issues"], 
            "auto_fixed_count" => $auto_fixed_count,
            "timestamp" => date("Y-m-d H:i:s")
        ];
        
    } catch (Exception $e) {
        return [
            "status" => "error",
            "error" => $e->getMessage()
        ];
    }
}

// Can be called directly or via web
if (php_sapi_name() === "cli") {
    // Command line execution
    $autoFix = in_array("--fix", $argv);
    $result = checkCartStatusConsistency($autoFix);
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else if (isset($_GET["action"]) && $_GET["action"] === "check") {
    // Web execution
    header("Content-Type: application/json");
    $autoFix = isset($_GET["fix"]) && $_GET["fix"] === "true";
    echo json_encode(checkCartStatusConsistency($autoFix));
}
?>';
    
    file_put_contents('/mnt/c/xampp/htdocs/Kiro_CRM_production/monitor_cartstatus.php', $monitoringScript);
    $results[] = "✅ Created monitoring script";
    
    // 3. Create cron job setup script
    $cronScript = '#!/bin/bash
# CartStatus Monitoring Cron Job
# Add this to crontab to run monitoring every hour

# Check every hour (no auto-fix)
# 0 * * * * /usr/bin/php /path/to/monitor_cartstatus.php

# Auto-fix daily at 2 AM
# 0 2 * * * /usr/bin/php /path/to/monitor_cartstatus.php --fix

# Example crontab entries:
echo "Add these lines to crontab (crontab -e):"
echo "# CartStatus monitoring every hour"
echo "0 * * * * cd /mnt/c/xampp/htdocs/Kiro_CRM_production && /usr/bin/php monitor_cartstatus.php"
echo ""
echo "# CartStatus auto-fix daily at 2 AM" 
echo "0 2 * * * cd /mnt/c/xampp/htdocs/Kiro_CRM_production && /usr/bin/php monitor_cartstatus.php --fix"
';
    
    file_put_contents('/mnt/c/xampp/htdocs/Kiro_CRM_production/setup_cartstatus_cron.sh', $cronScript);
    chmod('/mnt/c/xampp/htdocs/Kiro_CRM_production/setup_cartstatus_cron.sh', 0755);
    $results[] = "✅ Created cron job setup script";
    
    // 4. Test the monitoring system
    $testResult = [];
    try {
        include '/mnt/c/xampp/htdocs/Kiro_CRM_production/monitor_cartstatus.php';
        $testResult = checkCartStatusConsistency(false);
        $results[] = "✅ Monitoring system test: " . $testResult["consistency_status"];
    } catch (Exception $e) {
        $results[] = "⚠️ Monitoring test warning: " . $e->getMessage();
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Monitoring system setup completed',
        'details' => $results,
        'files_created' => [
            'cartstatus_monitoring_log' => 'Database table for monitoring logs',
            'monitor_cartstatus.php' => 'Main monitoring script',
            'setup_cartstatus_cron.sh' => 'Cron job setup helper'
        ],
        'test_result' => $testResult,
        'next_steps' => [
            'Set up cron jobs for automated monitoring',
            'Configure alerts for critical issues',
            'Review monitoring logs regularly'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>