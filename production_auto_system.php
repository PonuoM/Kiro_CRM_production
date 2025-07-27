<?php
// production_auto_system.php
// ระบบจัดการลูกค้าอัตโนมัติสำหรับ Production
// รันผ่าน Cron Job และ Manual

// Check if running from command line (Cron) or web
$isCron = php_sapi_name() === 'cli';

if (!$isCron) {
    session_start();
    
    // Enhanced auth check for web access
    if (!isset($_SESSION['user_login']) || !in_array($_SESSION['user_role'], ['admin', 'supervisor'])) {
        die("Access denied. Admin/Supervisor only.");
    }
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🤖 Production Auto System</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "<style>
    body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
    .auto-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:4px solid #28a745;background:linear-gradient(135deg,#e8f5e9,#f1f8e9);} 
    .rule-header{background:linear-gradient(135deg,#2e7d32,#4caf50);color:white;border-radius:8px 8px 0 0;padding:15px 20px;margin:-15px -20px 15px -20px;}
    .metric{background:white;border-radius:8px;padding:12px;margin:8px 0;border-left:3px solid #4caf50;}
    .progress-ring{width:40px;height:40px;} 
    .status-badge{font-size:0.75em;border-radius:12px;padding:4px 8px;}
    pre{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;}
    .cron-section{border-left:4px solid #6f42c1;background:linear-gradient(135deg,#f3e5f5,#f8f4ff);}
    </style>";
    echo "</head><body>";
    
    echo "<div class='container-fluid'>";
    echo "<div class='text-center mb-4'>";
    echo "<h1 class='display-5 fw-bold text-success'>🤖 Production Auto System</h1>";
    echo "<p class='lead text-muted'>ระบบจัดการลูกค้าอัตโนมัติ - รันประจำ</p>";
    echo "</div>";
}

$startTime = microtime(true);
$logEntries = [];
$results = [];

function logMessage($message, $type = 'info') {
    global $logEntries, $isCron;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntries[] = "[{$timestamp}] [{$type}] {$message}";
    
    if ($isCron) {
        echo "[{$timestamp}] {$message}\n";
    }
}

function addResult($rule, $affected, $details = '') {
    global $results;
    $results[$rule] = [
        'affected' => $affected,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->beginTransaction();
    
    logMessage("Production Auto System started", "info");
    
    // Get current task from URL parameter or default to 'all'
    $task = $_GET['task'] ?? ($argv[1] ?? 'all');
    
    if (!$isCron) {
        echo "<div class='auto-card'>";
        echo "<div class='rule-header'><h3><i class='fas fa-cogs'></i> Running Task: " . htmlspecialchars($task) . "</h3></div>";
    }
    
    // Rule 1: Auto-reassign overdue customers
    if ($task === 'all' || $task === 'reassign') {
        if (!$isCron) {
            echo "<h4><i class='fas fa-exchange-alt'></i> Auto-Reassign Rules</h4>";
        }
        
        logMessage("Starting auto-reassign rules", "info");
        
        // 1.1: New customers > 30 days -> Back to pool
        $sql = "UPDATE customers 
                SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL 
                WHERE CustomerStatus = 'ลูกค้าใหม่' 
                AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $newOverdue = $stmt->rowCount();
        addResult('new_overdue', $newOverdue, 'ลูกค้าใหม่เลย 30 วัน -> ตระกร้า');
        logMessage("New customers > 30 days: {$newOverdue} moved to pool", "success");
        
        // 1.2: Follow customers 15-30 days -> Back to pool
        $sql = "UPDATE customers 
                SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL 
                WHERE CustomerStatus = 'ลูกค้าติดตาม' 
                AND LastContactDate IS NOT NULL 
                AND DATEDIFF(CURDATE(), LastContactDate) BETWEEN 15 AND 30";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $followToPool = $stmt->rowCount();
        addResult('follow_to_pool', $followToPool, 'ลูกค้าติดตาม 15-30 วัน -> ตระกร้า');
        logMessage("Follow customers 15-30 days: {$followToPool} moved to pool", "success");
        
        // 1.3: Follow customers > 30 days -> Old customers
        $sql = "UPDATE customers 
                SET CustomerStatus = 'ลูกค้าเก่า' 
                WHERE CustomerStatus = 'ลูกค้าติดตาม' 
                AND LastContactDate IS NOT NULL 
                AND DATEDIFF(CURDATE(), LastContactDate) > 30";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $followToOld = $stmt->rowCount();
        addResult('follow_to_old', $followToOld, 'ลูกค้าติดตาม > 30 วัน -> ลูกค้าเก่า');
        logMessage("Follow customers > 30 days: {$followToOld} moved to old", "success");
        
        // 1.4: Old customers > 90 days -> FROZEN
        $sql = "UPDATE customers 
                SET CustomerTemperature = 'FROZEN', CustomerGrade = 'D' 
                WHERE CustomerStatus = 'ลูกค้าเก่า' 
                AND LastContactDate IS NOT NULL 
                AND DATEDIFF(CURDATE(), LastContactDate) > 90";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $oldFrozen = $stmt->rowCount();
        addResult('old_frozen', $oldFrozen, 'ลูกค้าเก่า > 90 วัน -> FROZEN');
        logMessage("Old customers > 90 days: {$oldFrozen} set to FROZEN", "success");
        
        if (!$isCron) {
            echo "<div class='row'>";
            echo "<div class='col-md-3'><div class='metric'><h5>$newOverdue</h5><small>ลูกค้าใหม่ -> Pool</small></div></div>";
            echo "<div class='col-md-3'><div class='metric'><h5>$followToPool</h5><small>ติดตาม -> Pool</small></div></div>";
            echo "<div class='col-md-3'><div class='metric'><h5>$followToOld</h5><small>ติดตาม -> เก่า</small></div></div>";
            echo "<div class='col-md-3'><div class='metric'><h5>$oldFrozen</h5><small>เก่า -> FROZEN</small></div></div>";
            echo "</div>";
        }
    }
    
    // Rule 2: Smart Temperature/Grade Update
    if ($task === 'all' || $task === 'smart') {
        if (!$isCron) {
            echo "<h4 class='mt-4'><i class='fas fa-thermometer-half'></i> Smart Temperature/Grade Update</h4>";
        }
        
        logMessage("Starting smart temperature/grade update", "info");
        
        // Update Temperature based on last contact
        $tempSql = "UPDATE customers SET 
            CustomerTemperature = CASE 
                WHEN Sales IS NULL OR CustomerStatus = 'ในตระกร้า' THEN 'FROZEN'
                WHEN LastContactDate IS NULL OR DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 'FROZEN'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 3 THEN 'HOT'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 7 THEN 'WARM'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 14 THEN 'COLD'
                ELSE 'FROZEN'
            END";
        
        $stmt = $pdo->prepare($tempSql);
        $stmt->execute();
        $tempUpdated = $stmt->rowCount();
        addResult('temperature_update', $tempUpdated, 'Temperature updated by smart logic');
        logMessage("Temperature updated: {$tempUpdated} customers", "success");
        
        // Update Grade based on status and temperature
        $gradeSql = "UPDATE customers SET 
            CustomerGrade = CASE 
                WHEN CustomerStatus = 'ในตระกร้า' OR CustomerTemperature = 'FROZEN' THEN 'D'
                WHEN CustomerStatus = 'ลูกค้าใหม่' AND CustomerTemperature IN ('HOT', 'WARM') THEN 'A'
                WHEN CustomerStatus = 'ลูกค้าติดตาม' AND CustomerTemperature IN ('WARM', 'COLD') THEN 'B'
                WHEN CustomerStatus = 'ลูกค้าเก่า' THEN 'C'
                ELSE 'D'
            END";
        
        $stmt = $pdo->prepare($gradeSql);
        $stmt->execute();
        $gradeUpdated = $stmt->rowCount();
        addResult('grade_update', $gradeUpdated, 'Grade updated by smart logic');
        logMessage("Grade updated: {$gradeUpdated} customers", "success");
        
        if (!$isCron) {
            echo "<div class='row'>";
            echo "<div class='col-md-6'><div class='metric'><h5>$tempUpdated</h5><small>Temperature Updated</small></div></div>";
            echo "<div class='col-md-6'><div class='metric'><h5>$gradeUpdated</h5><small>Grade Updated</small></div></div>";
            echo "</div>";
        }
    }
    
    // Rule 3: Daily Cleanup
    if ($task === 'all' || $task === 'daily') {
        if (!$isCron) {
            echo "<h4 class='mt-4'><i class='fas fa-broom'></i> Daily Cleanup Tasks</h4>";
        }
        
        logMessage("Starting daily cleanup tasks", "info");
        
        // 3.1: Fix invalid status (new customers without sales)
        $sql = "UPDATE customers 
                SET CustomerStatus = 'ในตระกร้า', AssignDate = NULL 
                WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $invalidStatus = $stmt->rowCount();
        addResult('invalid_status', $invalidStatus, 'ลูกค้าใหม่ไม่มี Sales -> ตระกร้า');
        logMessage("Invalid status fixed: {$invalidStatus} customers", "success");
        
        // 3.2: Remove sales from basket customers
        $sql = "UPDATE customers 
                SET Sales = NULL 
                WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $basketSalesRemoved = $stmt->rowCount();
        addResult('basket_sales_removed', $basketSalesRemoved, 'ลบ Sales จากลูกค้าตระกร้า');
        logMessage("Sales removed from basket: {$basketSalesRemoved} customers", "success");
        
        // 3.3: Update ModifiedDate
        $sql = "UPDATE customers 
                SET ModifiedDate = NOW() 
                WHERE ModifiedDate < DATE_SUB(NOW(), INTERVAL 1 DAY) 
                AND (CustomerStatus != 'ในตระกร้า' OR Sales IS NOT NULL)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $modifiedUpdated = $stmt->rowCount();
        addResult('modified_updated', $modifiedUpdated, 'ModifiedDate updated');
        logMessage("ModifiedDate updated: {$modifiedUpdated} customers", "success");
        
        if (!$isCron) {
            echo "<div class='row'>";
            echo "<div class='col-md-4'><div class='metric'><h5>$invalidStatus</h5><small>Invalid Status Fixed</small></div></div>";
            echo "<div class='col-md-4'><div class='metric'><h5>$basketSalesRemoved</h5><small>Basket Sales Removed</small></div></div>";
            echo "<div class='col-md-4'><div class='metric'><h5>$modifiedUpdated</h5><small>ModifiedDate Updated</small></div></div>";
            echo "</div>";
        }
    }
    
    $pdo->commit();
    $endTime = microtime(true);
    $executionTime = round(($endTime - $startTime) * 1000, 2);
    
    logMessage("Production Auto System completed successfully in {$executionTime}ms", "success");
    
    // Log to system_logs table
    $totalAffected = array_sum(array_column($results, 'affected'));
    $logSql = "INSERT INTO system_logs (log_type, message, created_at) VALUES (?, ?, NOW())";
    $stmt = $pdo->prepare($logSql);
    $stmt->execute(['auto_system', "Production auto system task '{$task}' completed. {$totalAffected} customers affected in {$executionTime}ms"]);
    
    if (!$isCron) {
        echo "<div class='alert alert-success mt-4'>";
        echo "<h5><i class='fas fa-check-circle'></i> Production Auto System เสร็จสมบูรณ์!</h5>";
        echo "<p>รวมลูกค้าที่ได้รับการปรับปรุง: <strong>{$totalAffected}</strong> รายการ</p>";
        echo "<small class='text-muted'>Execution time: {$executionTime}ms</small>";
        echo "</div>";
        
        echo "<h5 class='mt-4'>📊 Summary Results:</h5>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Rule</th><th>Affected</th><th>Details</th><th>Time</th></tr></thead><tbody>";
        
        foreach ($results as $rule => $data) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($rule) . "</code></td>";
            echo "<td><span class='badge bg-success'>{$data['affected']}</span></td>";
            echo "<td>" . htmlspecialchars($data['details']) . "</td>";
            echo "<td><small class='text-muted'>{$data['timestamp']}</small></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        echo "</div>"; // auto-card
    }
    
    // Show Cron Setup (web only)
    if (!$isCron) {
        echo "<div class='auto-card cron-section'>";
        echo "<div class='rule-header' style='background:linear-gradient(135deg,#6a1b9a,#9c27b0);'>";
        echo "<h3><i class='fas fa-clock'></i> Cron Job Setup</h3>";
        echo "</div>";
        
        echo "<h5>📅 Recommended Cron Schedule:</h5>";
        
        $cronJobs = [
            'daily_cleanup' => [
                'schedule' => '0 1 * * *',
                'description' => 'Daily Cleanup - ทุกวันเวลา 01:00 AM',
                'command' => 'php ' . realpath(__FILE__) . ' daily'
            ],
            'smart_update' => [
                'schedule' => '0 2 * * *',
                'description' => 'Smart Update - ทุกวันเวลา 02:00 AM',
                'command' => 'php ' . realpath(__FILE__) . ' smart'
            ],
            'auto_reassign' => [
                'schedule' => '0 */6 * * *',
                'description' => 'Auto-reassign - ทุก 6 ชั่วโมง',
                'command' => 'php ' . realpath(__FILE__) . ' reassign'
            ],
            'full_system' => [
                'schedule' => '0 3 * * 0',
                'description' => 'Full System Check - ทุกวันอาทิตย์เวลา 03:00 AM',
                'command' => 'php ' . realpath(__FILE__) . ' all'
            ]
        ];
        
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Schedule</th><th>Description</th><th>Command</th></tr></thead><tbody>";
        
        foreach ($cronJobs as $job) {
            echo "<tr>";
            echo "<td><code>{$job['schedule']}</code></td>";
            echo "<td>{$job['description']}</td>";
            echo "<td><small><code>{$job['command']}</code></small></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        echo "<h6>🔧 Installation Commands:</h6>";
        echo "<pre>";
        echo "# Edit crontab\n";
        echo "crontab -e\n\n";
        echo "# Add these lines:\n";
        foreach ($cronJobs as $job) {
            echo "{$job['schedule']} {$job['command']}\n";
        }
        echo "</pre>";
        
        echo "<div class='alert alert-info'>";
        echo "<h6><i class='fas fa-info-circle'></i> Manual Execution:</h6>";
        echo "<div class='btn-group mb-2'>";
        echo "<a href='?task=daily' class='btn btn-outline-primary btn-sm'>Run Daily</a>";
        echo "<a href='?task=smart' class='btn btn-outline-info btn-sm'>Run Smart</a>";
        echo "<a href='?task=reassign' class='btn btn-outline-warning btn-sm'>Run Reassign</a>";
        echo "<a href='?task=all' class='btn btn-outline-success btn-sm'>Run All</a>";
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        
        echo "</div>"; // container
        echo "</body></html>";
    }

} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollback();
    }
    
    $errorMsg = "Production Auto System error: " . $e->getMessage();
    logMessage($errorMsg, "error");
    
    // Log error to system_logs
    if (isset($pdo)) {
        try {
            $logSql = "INSERT INTO system_logs (log_type, message, created_at) VALUES (?, ?, NOW())";
            $stmt = $pdo->prepare($logSql);
            $stmt->execute(['auto_system_error', $errorMsg]);
        } catch (Exception $logError) {
            // Ignore logging errors
        }
    }
    
    if (!$isCron) {
        echo "<div class='alert alert-danger'>";
        echo "<h4><i class='fas fa-exclamation-triangle'></i> Error</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<small class='text-muted'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</small>";
        echo "</div>";
        
        if (isset($logEntries) && !empty($logEntries)) {
            echo "<h6>Log Entries:</h6>";
            echo "<pre>" . implode("\n", array_slice($logEntries, -20)) . "</pre>";
        }
        
        echo "</div></body></html>";
    } else {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Return success code for cron
if ($isCron) {
    exit(0);
}
?>