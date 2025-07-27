<?php
// system_health_check.php
// ตรวจสอบสุขภาพระบบ CRM

// Check if running from command line (Cron) or web
$isCron = php_sapi_name() === 'cli';
$isQuiet = $isCron || (isset($_GET['quiet']) && $_GET['quiet'] === 'true');

if (!$isCron) {
    session_start();
    
    if (!isset($_SESSION['user_login']) || !in_array($_SESSION['user_role'], ['admin', 'supervisor'])) {
        die("Access denied. Admin/Supervisor only.");
    }
    
    if (!$isQuiet) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔍 System Health Check</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "<style>
        body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
        .health-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
        .health-good{border-left:4px solid #28a745;background:linear-gradient(135deg,#e8f5e9,#f1f8e9);} 
        .health-warning{border-left:4px solid #ffc107;background:linear-gradient(135deg,#fff8e1,#fffbf0);} 
        .health-critical{border-left:4px solid #dc3545;background:linear-gradient(135deg,#ffebee,#fff5f5);} 
        .metric{background:white;border-radius:8px;padding:12px;margin:8px 0;border-left:3px solid #ddd;}
        .metric.good{border-left-color:#28a745;} .metric.warning{border-left-color:#ffc107;} .metric.critical{border-left-color:#dc3545;}
        .status-icon{font-size:1.2em;margin-right:8px;}
        pre{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;max-height:200px;overflow:auto;}
        </style>";
        echo "</head><body>";
        
        echo "<div class='container-fluid'>";
        echo "<div class='text-center mb-4'>";
        echo "<h1 class='display-5 fw-bold text-primary'>🔍 System Health Check</h1>";
        echo "<p class='lead text-muted'>ตรวจสอบสุขภาพระบบ CRM</p>";
        echo "</div>";
    }
}

$startTime = microtime(true);
$healthStatus = 'good'; // good, warning, critical
$healthIssues = [];
$healthMetrics = [];

function addMetric($name, $value, $status = 'good', $details = '') {
    global $healthMetrics;
    $healthMetrics[] = [
        'name' => $name,
        'value' => $value,
        'status' => $status,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function addIssue($message, $level = 'warning') {
    global $healthIssues, $healthStatus;
    $healthIssues[] = [
        'message' => $message,
        'level' => $level,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($level === 'critical' || ($level === 'warning' && $healthStatus === 'good')) {
        $healthStatus = $level;
    }
}

function logHealth($message, $level = 'info') {
    global $isCron;
    
    $timestamp = date('Y-m-d H:i:s');
    if ($isCron) {
        echo "[{$timestamp}] {$message}\n";
    }
}

try {
    require_once 'config/database.php';
    
    // Test 1: Database Connection
    logHealth("Testing database connection", "info");
    
    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        $stmt = $pdo->query("SELECT VERSION() as version, NOW() as current_time");
        $dbInfo = $stmt->fetch();
        
        addMetric("Database Connection", "Connected", "good", "MySQL {$dbInfo['version']}");
        logHealth("Database connection: OK", "success");
    } catch (Exception $e) {
        addMetric("Database Connection", "Failed", "critical", $e->getMessage());
        addIssue("Database connection failed: " . $e->getMessage(), "critical");
        logHealth("Database connection: FAILED", "error");
    }
    
    if ($healthStatus !== 'critical') {
        // Test 2: Customer Data Integrity
        logHealth("Checking customer data integrity", "info");
        
        $integrityChecks = [
            'basket_with_sales' => [
                'name' => 'ลูกค้าในตระกร้าที่มี Sales',
                'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL",
                'threshold' => 0
            ],
            'new_without_sales' => [
                'name' => 'ลูกค้าใหม่ที่ไม่มี Sales',
                'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL",
                'threshold' => 10
            ],
            'overdue_new' => [
                'name' => 'ลูกค้าใหม่เลยเวลา > 30 วัน',
                'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30",
                'threshold' => 5
            ],
            'overdue_follow' => [
                'name' => 'ลูกค้าติดตามเลยเวลา > 14 วัน',
                'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14",
                'threshold' => 20
            ]
        ];
        
        foreach ($integrityChecks as $key => $check) {
            $stmt = $pdo->prepare($check['query']);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            $status = 'good';
            if ($count > $check['threshold']) {
                $status = ($count > $check['threshold'] * 2) ? 'critical' : 'warning';
                addIssue("{$check['name']}: {$count} รายการ (เกินขีดจำกัด {$check['threshold']})", $status);
            }
            
            addMetric($check['name'], $count, $status, "Threshold: {$check['threshold']}");
        }
        
        // Test 3: System Performance
        logHealth("Checking system performance", "info");
        
        // Database response time
        $dbStart = microtime(true);
        $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
        $customerCount = $stmt->fetchColumn();
        $dbTime = round((microtime(true) - $dbStart) * 1000, 2);
        
        $dbStatus = 'good';
        if ($dbTime > 1000) {
            $dbStatus = 'critical';
            addIssue("Database response time is slow: {$dbTime}ms", "critical");
        } elseif ($dbTime > 500) {
            $dbStatus = 'warning';
            addIssue("Database response time is concerning: {$dbTime}ms", "warning");
        }
        
        addMetric("Database Response Time", "{$dbTime}ms", $dbStatus, "Query: SELECT COUNT(*) FROM customers");
        addMetric("Total Customers", $customerCount, "good");
        
        // Check for recent auto system runs
        $stmt = $pdo->prepare("SELECT created_at FROM system_logs WHERE log_type = 'auto_system' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $lastAutoRun = $stmt->fetchColumn();
        
        if ($lastAutoRun) {
            $hoursSinceLastRun = (time() - strtotime($lastAutoRun)) / 3600;
            $autoStatus = 'good';
            
            if ($hoursSinceLastRun > 24) {
                $autoStatus = 'critical';
                addIssue("Auto system hasn't run for " . round($hoursSinceLastRun, 1) . " hours", "critical");
            } elseif ($hoursSinceLastRun > 12) {
                $autoStatus = 'warning';
                addIssue("Auto system last ran " . round($hoursSinceLastRun, 1) . " hours ago", "warning");
            }
            
            addMetric("Last Auto System Run", date('d/m/Y H:i', strtotime($lastAutoRun)), $autoStatus, round($hoursSinceLastRun, 1) . " hours ago");
        } else {
            addMetric("Last Auto System Run", "Never", "warning", "Auto system has never run");
            addIssue("Auto system has never run", "warning");
        }
        
        // Test 4: File System Health
        logHealth("Checking file system health", "info");
        
        // Check disk space
        $diskFree = disk_free_space('.');
        $diskTotal = disk_total_space('.');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = round(($diskUsed / $diskTotal) * 100, 2);
        
        $diskStatus = 'good';
        if ($diskPercent > 95) {
            $diskStatus = 'critical';
            addIssue("Disk space critically low: {$diskPercent}%", "critical");
        } elseif ($diskPercent > 85) {
            $diskStatus = 'warning';
            addIssue("Disk space getting low: {$diskPercent}%", "warning");
        }
        
        addMetric("Disk Usage", "{$diskPercent}%", $diskStatus, number_format($diskFree / 1024 / 1024 / 1024, 2) . "GB free");
        
        // Check log directory
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            addMetric("Log Directory", "Missing", "warning", "Directory: {$logDir}");
            addIssue("Log directory doesn't exist: {$logDir}", "warning");
        } elseif (!is_writable($logDir)) {
            addMetric("Log Directory", "Not Writable", "critical", "Directory: {$logDir}");
            addIssue("Log directory is not writable: {$logDir}", "critical");
        } else {
            addMetric("Log Directory", "OK", "good", "Directory: {$logDir}");
        }
        
        // Test 5: Recent Errors
        logHealth("Checking for recent errors", "info");
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE log_type LIKE '%error%' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $recentErrors = $stmt->fetchColumn();
        
        $errorStatus = 'good';
        if ($recentErrors > 10) {
            $errorStatus = 'critical';
            addIssue("High error rate: {$recentErrors} errors in last 24 hours", "critical");
        } elseif ($recentErrors > 5) {
            $errorStatus = 'warning';
            addIssue("Elevated error rate: {$recentErrors} errors in last 24 hours", "warning");
        }
        
        addMetric("Recent Errors (24h)", $recentErrors, $errorStatus);
    }
    
    $endTime = microtime(true);
    $checkTime = round(($endTime - $startTime) * 1000, 2);
    
    addMetric("Health Check Duration", "{$checkTime}ms", "good");
    
    // Log results to database
    if (isset($pdo)) {
        $logSql = "INSERT INTO system_logs (log_type, message, created_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($logSql);
        $issueCount = count($healthIssues);
        $stmt->execute(['health_check', "Health check completed. Status: {$healthStatus}, Issues: {$issueCount}, Duration: {$checkTime}ms"]);
    }
    
    logHealth("Health check completed. Status: {$healthStatus}", "info");
    
    if (!$isQuiet) {
        // Display results (web only)
        $cardClass = "health-{$healthStatus}";
        
        echo "<div class='health-card {$cardClass}'>";
        echo "<div class='p-4'>";
        
        // Overall status
        $statusIcons = [
            'good' => '✅',
            'warning' => '⚠️', 
            'critical' => '❌'
        ];
        
        $statusColors = [
            'good' => 'success',
            'warning' => 'warning',
            'critical' => 'danger'
        ];
        
        echo "<div class='alert alert-{$statusColors[$healthStatus]} mb-4'>";
        echo "<h4><span class='status-icon'>{$statusIcons[$healthStatus]}</span>System Status: " . strtoupper($healthStatus) . "</h4>";
        echo "<p class='mb-0'>Health check completed in {$checkTime}ms</p>";
        echo "</div>";
        
        // Issues
        if (!empty($healthIssues)) {
            echo "<h5>🚨 Issues Found:</h5>";
            echo "<div class='row mb-4'>";
            foreach ($healthIssues as $issue) {
                $alertClass = $issue['level'] === 'critical' ? 'danger' : 'warning';
                echo "<div class='col-md-6 mb-2'>";
                echo "<div class='alert alert-{$alertClass} py-2 px-3'>";
                echo "<strong>[{$issue['level']}]</strong> {$issue['message']}";
                echo "<br><small class='text-muted'>{$issue['timestamp']}</small>";
                echo "</div>";
                echo "</div>";
            }
            echo "</div>";
        }
        
        // Metrics
        echo "<h5>📊 Health Metrics:</h5>";
        echo "<div class='row'>";
        foreach ($healthMetrics as $metric) {
            echo "<div class='col-md-4 col-lg-3 mb-3'>";
            echo "<div class='metric {$metric['status']}'>";
            echo "<h6 class='mb-1'>{$metric['name']}</h6>";
            echo "<h5 class='mb-1'>{$metric['value']}</h5>";
            if ($metric['details']) {
                echo "<small class='text-muted'>{$metric['details']}</small>";
            }
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        
        // Recommendations
        echo "<div class='alert alert-info mt-4'>";
        echo "<h6><i class='fas fa-lightbulb'></i> Recommendations:</h6>";
        echo "<ul class='mb-0'>";
        
        if ($healthStatus === 'critical') {
            echo "<li>🚨 <strong>Critical issues detected</strong> - Immediate action required</li>";
            echo "<li>📞 Contact system administrator</li>";
            echo "<li>🔍 Check system logs for detailed error information</li>";
        } elseif ($healthStatus === 'warning') {
            echo "<li>⚠️ <strong>Warning issues detected</strong> - Monitor closely</li>";
            echo "<li>🔧 Run production fix if data integrity issues persist</li>";
            echo "<li>📅 Schedule maintenance window if needed</li>";
        } else {
            echo "<li>✅ System is healthy</li>";
            echo "<li>📊 Continue regular monitoring</li>";
            echo "<li>🔄 Run auto system if any minor issues detected</li>";
        }
        
        echo "</ul>";
        echo "</div>";
        
        echo "</div>"; // p-4
        echo "</div>"; // health-card
        
        echo "</div>"; // container
        echo "</body></html>";
    }

} catch (Exception $e) {
    $errorMsg = "Health check failed: " . $e->getMessage();
    logHealth($errorMsg, "error");
    
    addMetric("Health Check", "Failed", "critical", $e->getMessage());
    addIssue($errorMsg, "critical");
    
    if (!$isQuiet) {
        echo "<div class='alert alert-danger'>";
        echo "<h4><i class='fas fa-exclamation-triangle'></i> Health Check Failed</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<small class='text-muted'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</small>";
        echo "</div>";
        
        echo "</div></body></html>";
    }
    
    if ($isCron) {
        exit(1);
    }
}

// Return appropriate exit code for cron
if ($isCron) {
    exit($healthStatus === 'critical' ? 1 : 0);
}
?>