<?php
/**
 * CartStatus Monitoring Script
 * Run this script regularly to monitor and maintain data consistency
 * 
 * Usage:
 * - Web: /monitor_cartstatus.php?action=check&fix=true
 * - CLI: php monitor_cartstatus.php --fix --verbose
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
                COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' AND CartStatus != 'ลูกค้าแจกแล้ว' THEN 1 END) as type1_issues,
                COUNT(CASE WHEN (Sales IS NULL OR Sales = '') AND CartStatus = 'ลูกค้าแจกแล้ว' THEN 1 END) as type2_issues
            FROM customers
        ";
        
        $stmt = $pdo->prepare($checkSQL);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $inconsistent_count = $stats["type1_issues"] + $stats["type2_issues"];
        $status = $inconsistent_count > 0 ? "issues_found" : "clean";
        $auto_fixed_count = 0;
        $fix_details = [];
        
        // Auto-fix if requested and issues found
        if ($autoFix && $inconsistent_count > 0) {
            $pdo->beginTransaction();
            
            try {
                // Fix type 1: Has Sales but CartStatus not assigned
                if ($stats["type1_issues"] > 0) {
                    $fix1SQL = "
                        UPDATE customers 
                        SET CartStatus = 'ลูกค้าแจกแล้ว', 
                            ModifiedDate = NOW(),
                            ModifiedBy = 'auto_monitor'
                        WHERE Sales IS NOT NULL 
                        AND Sales != '' 
                        AND CartStatus != 'ลูกค้าแจกแล้ว'
                    ";
                    $stmt = $pdo->prepare($fix1SQL);
                    $stmt->execute();
                    $fixed1 = $stmt->rowCount();
                    if ($fixed1 > 0) {
                        $fix_details[] = "Fixed $fixed1 customers: added CartStatus for assigned customers";
                        $auto_fixed_count += $fixed1;
                    }
                }
                
                // Fix type 2: No Sales but CartStatus is assigned
                if ($stats["type2_issues"] > 0) {
                    $fix2SQL = "
                        UPDATE customers 
                        SET CartStatus = 'ตะกร้าแจก',
                            ModifiedDate = NOW(),
                            ModifiedBy = 'auto_monitor'
                        WHERE (Sales IS NULL OR Sales = '') 
                        AND CartStatus = 'ลูกค้าแจกแล้ว'
                    ";
                    $stmt = $pdo->prepare($fix2SQL);
                    $stmt->execute();
                    $fixed2 = $stmt->rowCount();
                    if ($fixed2 > 0) {
                        $fix_details[] = "Fixed $fixed2 customers: moved unassigned customers back to basket";
                        $auto_fixed_count += $fixed2;
                    }
                }
                
                $pdo->commit();
                $status = $auto_fixed_count > 0 ? "auto_fixed" : "manual_required";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $fix_details[] = "Auto-fix failed: " . $e->getMessage();
            }
        }
        
        // Log the check
        try {
            $logSQL = "
                INSERT INTO cartstatus_monitoring_log 
                (total_customers, inconsistent_count, type1_issues, type2_issues, auto_fixed_count, status, details) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $details = json_encode([
                "timestamp" => date("Y-m-d H:i:s"),
                "auto_fix_enabled" => $autoFix,
                "fix_details" => $fix_details
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
        } catch (Exception $e) {
            // Log table might not exist, continue anyway
        }
        
        return [
            "status" => "success",
            "consistency_status" => $status,
            "total_customers" => $stats["total_customers"],
            "inconsistent_count" => $inconsistent_count,
            "type1_issues" => $stats["type1_issues"],
            "type2_issues" => $stats["type2_issues"], 
            "auto_fixed_count" => $auto_fixed_count,
            "fix_details" => $fix_details,
            "timestamp" => date("Y-m-d H:i:s")
        ];
        
    } catch (Exception $e) {
        return [
            "status" => "error",
            "error" => $e->getMessage(),
            "timestamp" => date("Y-m-d H:i:s")
        ];
    }
}

// Execution logic
if (php_sapi_name() === "cli") {
    // Command line execution
    $autoFix = in_array("--fix", $argv);
    $verbose = in_array("--verbose", $argv);
    
    $result = checkCartStatusConsistency($autoFix);
    
    // Log to console
    $logMessage = "[" . date("Y-m-d H:i:s") . "] CartStatus Check: " . 
                  $result["consistency_status"];
    
    if ($result["status"] === "success") {
        $logMessage .= " - Total: " . $result["total_customers"] . 
                      " - Issues: " . $result["inconsistent_count"];
        
        if ($autoFix && $result["auto_fixed_count"] > 0) {
            $logMessage .= " - Fixed: " . $result["auto_fixed_count"];
        }
    } else {
        $logMessage .= " - ERROR: " . $result["error"];
    }
    
    echo $logMessage . "\n";
    
    // Verbose output
    if ($verbose) {
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
    // Exit with error code if issues found
    if ($result["status"] !== "success" || $result["inconsistent_count"] > 0) {
        exit(1);
    }
    
} else if (isset($_GET["action"]) && $_GET["action"] === "check") {
    // Web API execution
    header("Content-Type: application/json");
    $autoFix = isset($_GET["fix"]) && $_GET["fix"] === "true";
    echo json_encode(checkCartStatusConsistency($autoFix));
    
} else {
    // Web interface for manual checking
    header("Content-Type: text/html; charset=utf-8");
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>🔍 CartStatus Monitor</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-4">
            <h2>🔍 CartStatus Monitor</h2>
            <p class="text-muted">Monitor and maintain CartStatus data consistency</p>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="btn-group" role="group">
                        <button class="btn btn-info" onclick="checkStatus()">
                            <i class="fas fa-search"></i> Check Status
                        </button>
                        <button class="btn btn-warning" onclick="runAutoFix()">
                            <i class="fas fa-wrench"></i> Run Auto-Fix
                        </button>
                        <button class="btn btn-secondary" onclick="showLogs()">
                            <i class="fas fa-history"></i> View Logs
                        </button>
                    </div>
                </div>
            </div>
            
            <div id="results" class="mt-4"></div>
        </div>
        
        <script>
            function showResult(data, title = 'Results') {
                let alertClass = 'info';
                if (data.status === 'error') alertClass = 'danger';
                else if (data.consistency_status === 'clean') alertClass = 'success';
                else if (data.consistency_status === 'auto_fixed') alertClass = 'warning';
                
                document.getElementById('results').innerHTML = `
                    <div class="alert alert-${alertClass}">
                        <h5>${title}</h5>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            }
            
            function checkStatus() {
                fetch('?action=check')
                    .then(response => response.json())
                    .then(data => showResult(data, '📊 Status Check Results'))
                    .catch(error => showResult({status: 'error', error: error.message}, '❌ Error'));
            }
            
            function runAutoFix() {
                if (!confirm('Run auto-fix? This will modify database records.')) return;
                
                fetch('?action=check&fix=true')
                    .then(response => response.json())
                    .then(data => showResult(data, '🔧 Auto-Fix Results'))
                    .catch(error => showResult({status: 'error', error: error.message}, '❌ Error'));
            }
            
            function showLogs() {
                // This would connect to log viewing functionality
                alert('Log viewing feature - connect to monitoring log table');
            }
        </script>
    </body>
    </html>
    <?php
}
?>