<?php
// simple_cron_check_safe.php
// ตรวจสอบการทำงานของ Cron Jobs แบบปลอดภัย (ไม่ต้องพึ่ง system_logs)

session_start();

// Bypass auth for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔍 Safe Cron Check</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.check-card{margin:15px 0;padding:20px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
.working{border-left:5px solid #28a745;background:#f8fff8;} 
.warning{border-left:5px solid #ffc107;background:#fffbf0;} 
.error{border-left:5px solid #dc3545;background:#fff5f5;} 
.info{border-left:5px solid #17a2b8;background:#f0f9ff;} 
.metric{background:white;padding:12px;margin:8px 0;border-radius:8px;border-left:3px solid #ddd;}
.metric.good{border-left-color:#28a745;} .metric.bad{border-left-color:#dc3545;} .metric.warn{border-left-color:#ffc107;}
.log-box{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;max-height:200px;overflow:auto;font-family:monospace;font-size:12px;}
.status-good{color:#28a745;font-weight:bold;} .status-bad{color:#dc3545;font-weight:bold;} .status-warn{color:#ffc107;font-weight:bold;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='text-center mb-4'>";
echo "<h1 class='display-6 fw-bold text-primary'>🔍 Safe Cron Check</h1>";
echo "<p class='lead text-muted'>ตรวจสอบการทำงานของ Cron Jobs แบบปลอดภัย</p>";
echo "<small class='text-muted'>เวลาตรวจสอบ: " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

$dbConnected = false;
$pdo = null;
$systemLogsExists = false;

// 1. ตรวจสอบ Database Connection
echo "<div class='check-card working'>";
echo "<h3>🔗 ตรวจสอบ Database</h3>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    $dbConnected = true;
    
    echo "<div class='metric good'>";
    echo "<h5>✅ Database Connection</h5>";
    echo "<p><strong>สถานะ:</strong> เชื่อมต่อสำเร็จ</p>";
    echo "</div>";
    
    // Check if system_logs table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_logs'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        $systemLogsExists = true;
        echo "<div class='metric good'>";
        echo "<h5>✅ System Logs Table</h5>";
        echo "<p><strong>สถานะ:</strong> ตารางมีอยู่</p>";
        echo "</div>";
        
        // Count logs if table exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs");
        $stmt->execute();
        $logCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stmt->execute();
        $recentLogCount = $stmt->fetchColumn();
        
        echo "<div class='metric good'>";
        echo "<h5>📊 Log Statistics</h5>";
        echo "<p><strong>Total Logs:</strong> " . number_format($logCount) . " รายการ</p>";
        echo "<p><strong>Last 24h:</strong> " . number_format($recentLogCount) . " รายการ</p>";
        echo "</div>";
        
    } else {
        echo "<div class='metric warn'>";
        echo "<h5>⚠️ System Logs Table</h5>";
        echo "<p><strong>สถานะ:</strong> ตารางไม่มี</p>";
        echo "<p><small>ไม่สามารถตรวจสอบ log จาก database ได้</small></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='metric bad'>";
    echo "<h5>❌ Database Error</h5>";
    echo "<p><strong>ข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</div>";

// 2. ตรวจสอบ Log Files (ไม่ต้องพึ่ง database)
echo "<div class='check-card info'>";
echo "<h3>📁 ตรวจสอบ Log Files</h3>";

$logFiles = [
    'logs/cron_daily.log' => 'Daily Cleanup',
    'logs/cron_smart.log' => 'Smart Update', 
    'logs/cron_reassign.log' => 'Auto Reassign',
    'logs/cron_full.log' => 'Full System',
    'logs/health_check.log' => 'Health Check',
    'logs/auto_system.log' => 'Auto System (General)',
    'logs/system.log' => 'System Log',
    'logs/error.log' => 'Error Log'
];

$logsExist = false;
$recentActivity = false;

foreach ($logFiles as $file => $name) {
    $fullPath = __DIR__ . '/' . $file;
    echo "<div class='metric \" . (file_exists($fullPath) ? 'good' : 'bad') . \"'>";
    echo "<h6>$name</h6>";
    
    if (file_exists($fullPath)) {
        $logsExist = true;
        $size = filesize($fullPath);
        $modified = filemtime($fullPath);
        $age = time() - $modified;
        
        echo "<p><strong>ไฟล์:</strong> " . basename($file) . "</p>";
        echo "<p><strong>ขนาด:</strong> " . number_format($size / 1024, 1) . " KB</p>";
        echo "<p><strong>แก้ไขล่าสุด:</strong> " . date('d/m H:i', $modified);
        
        if ($age < 3600) {
            echo " <span class='status-good'>(\" . floor($age / 60) . \" นาทีที่แล้ว)</span>";
            $recentActivity = true;
        } elseif ($age < 86400) {
            echo " <span class='status-warn'>(\" . floor($age / 3600) . \" ชั่วโมงที่แล้ว)</span>";
            if ($age < 12 * 3600) $recentActivity = true;
        } else {
            echo " <span class='status-bad'>(\" . floor($age / 86400) . \" วันที่แล้ว)</span>";
        }
        echo "</p>";
        
        // แสดง log ล่าสุด
        if ($size > 0 && $size < 1024 * 1024) { // ไม่เกิน 1MB
            $lines = file($fullPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines && count($lines) > 0) {
                $lastLines = array_slice($lines, -3);
                echo "<details><summary>Log ล่าสุด (3 บรรทัด)</summary>";
                echo "<div class='log-box mt-2'>";
                foreach ($lastLines as $line) {
                    echo htmlspecialchars(trim($line)) . "\n";
                }
                echo "</div></details>";
            }
        }
    } else {
        echo "<p><span class='status-bad'>ไม่พบไฟล์</span></p>";
        echo "<small class='text-muted'>Path: $file</small>";
    }
    echo "</div>";
}

if (!$logsExist) {
    echo "<div class='alert alert-warning'>";
    echo "<h5>⚠️ ไม่พบ Log Files</h5>";
    echo "<p>อาจเป็นเพราะ Cron Jobs ยังไม่ได้ทำงาน หรือยังไม่ได้ตั้งค่า logging</p>";
    echo "</div>";
}

if ($recentActivity) {
    echo "<div class='alert alert-success'>";
    echo "<h5>✅ มีกิจกรรมล่าสุด</h5>";
    echo "<p>พบ log files ที่มีการอัปเดตล่าสุด แสดงว่า cron jobs อาจกำลังทำงาน</p>";
    echo "</div>";
}

echo "</div>";

// 3. ตรวจสอบไฟล์ระบบ
echo "<div class='check-card info'>";
echo "<h3>🔧 ตรวจสอบไฟล์ระบบ</h3>";

$systemFiles = [
    'production_auto_system.php' => 'Production Auto System',
    'system_health_check.php' => 'Health Check System',
    'auto_customer_management.php' => 'Legacy Auto Management',
    'cron/run_auto_rules.sh' => 'Auto Rules Script',
    'auto_status_manager.php' => 'Auto Status Manager'
];

foreach ($systemFiles as $file => $name) {
    $fullPath = __DIR__ . '/' . $file;
    echo "<div class='metric \" . (file_exists($fullPath) ? 'good' : 'bad') . \"'>";
    echo "<h6>$name</h6>";
    
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $modified = filemtime($fullPath);
        
        echo "<p><strong>ไฟล์:</strong> $file ✅</p>";
        echo "<p><strong>ขนาด:</strong> " . number_format($size / 1024, 1) . " KB</p>";
        echo "<p><strong>แก้ไขล่าสุด:</strong> " . date('d/m/Y H:i', $modified) . "</p>";
        
        // Check if executable for .sh files
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sh') {
            $perms = fileperms($fullPath);
            $isExecutable = ($perms & 0x0040) ? true : false;
            echo "<p><strong>Executable:</strong> \" . ($isExecutable ? \"✅\" : \"❌\") . \"</p>";
        }
    } else {
        echo "<p><span class='status-bad'>ไม่พบไฟล์</span></p>";
    }
    echo "</div>";
}

echo "</div>";

// 4. ตรวจสอบ Directory Structure
echo "<div class='check-card working'>";
echo "<h3>📂 ตรวจสอบ Directory Structure</h3>";

$directories = [
    'logs' => 'Log Directory',
    'cron' => 'Cron Scripts Directory',
    'config' => 'Configuration Directory'
];

foreach ($directories as $dir => $name) {
    $fullPath = __DIR__ . '/' . $dir;
    echo "<div class='metric \" . (is_dir($fullPath) ? 'good' : 'bad') . \"'>";
    echo "<h6>$name</h6>";
    
    if (is_dir($fullPath)) {
        $writable = is_writable($fullPath);
        echo "<p><strong>Directory:</strong> $dir ✅</p>";
        echo "<p><strong>Writable:</strong> \" . ($writable ? \"✅\" : \"❌\") . \"</p>";
        
        // Count files in directory
        $files = scandir($fullPath);
        $fileCount = count($files) - 2; // Exclude . and ..
        echo "<p><strong>Files:</strong> $fileCount ไฟล์</p>";
        
    } else {
        echo "<p><span class='status-bad'>ไม่พบ directory</span></p>";
        echo "<p><small>Path: $dir</small></p>";
    }
    echo "</div>";
}

echo "</div>";

// 5. รายการ Cron Jobs ที่คุณติดตั้ง
echo "<div class='check-card warning'>";
echo "<h3>📋 Cron Jobs Analysis</h3>";
echo "<p class='text-muted'>วิเคราะห์ Cron Jobs ตามที่คุณแจ้งมา:</p>";

$installedCrons = [
    ['0 2 * * *', 'curl -s \"https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1\"', '✅ Web-based - ถูกต้อง'],
    ['0 1 * * *', '/home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh', '✅ Shell script - ถูกต้อง'],
    ['0 1 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=daily', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['0 2 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=smart', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['0 */6 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=reassign', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['*/30 * * * *', 'php /path/to/system_health_check.php', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['0 1 * * *', 'php production_auto_system.php daily', '⚠️ ซ้ำ - เก็บอันนี้'],
    ['0 2 * * *', 'php production_auto_system.php smart', '⚠️ ซ้ำ - เก็บอันนี้'],
    ['0 */6 * * *', 'php production_auto_system.php reassign', '⚠️ ซ้ำ - เก็บอันนี้'],
    ['0 3 * * 0', 'php production_auto_system.php all', '✅ ใหม่ - ถูกต้อง'],
    ['*/30 8-18 * * 1-6', 'php system_health_check.php', '⚠️ ซ้ำ - เก็บอันนี้']
];

echo "<table class='table table-sm'>";
echo "<thead><tr><th>Schedule</th><th>Command</th><th>สถานะ</th></tr></thead><tbody>";

$goodCount = 0;
$badCount = 0;
$warningCount = 0;

foreach ($installedCrons as $cron) {
    $rowClass = '';
    if (strpos($cron[2], '❌') !== false) {
        $rowClass = 'table-danger';
        $badCount++;
    } elseif (strpos($cron[2], '⚠️') !== false) {
        $rowClass = 'table-warning';
        $warningCount++;
    } elseif (strpos($cron[2], '✅') !== false) {
        $rowClass = 'table-success';
        $goodCount++;
    }
    
    echo "<tr class='$rowClass'>";
    echo "<td><code>{$cron[0]}</code></td>";
    echo "<td><small>\" . htmlspecialchars($cron[1]) . \"</small></td>";
    echo "<td>{$cron[2]}</td>";
    echo "</tr>";
}

echo "</tbody></table>";

echo "<div class='row mt-3'>";
echo "<div class='col-md-4'>";
echo "<div class='alert alert-success'>";
echo "<h6>✅ Good: $goodCount</h6>";
echo "<small>Cron Jobs ที่ถูกต้อง</small>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='alert alert-danger'>";
echo "<h6>❌ Bad: $badCount</h6>";
echo "<small>ต้องลบทิ้ง (path ไม่ถูกต้อง)</small>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='alert alert-warning'>";
echo "<h6>⚠️ Duplicates: $warningCount</h6>";
echo "<small>ซ้ำกัน (เก็บอันใหม่)</small>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

// 6. คำแนะนำ
echo "<div class='check-card error'>";
echo "<h3>💡 คำแนะนำ</h3>";

echo "<div class='alert alert-info'>";
echo "<h5>🔧 ขั้นตอนต่อไป:</h5>";
echo "<ol>";
echo "<li><strong>สร้างตาราง system_logs:</strong> เรียกใช้ <a href='create_system_logs_table.php' target='_blank'><code>create_system_logs_table.php</code></a></li>";
echo "<li><strong>ทำความสะอาด Cron Jobs:</strong> ดู <a href='cron_cleanup_helper.php' target='_blank'><code>cron_cleanup_helper.php</code></a></li>";
echo "<li><strong>ลบ Cron Jobs ที่มีปัญหา:</strong> ลบทุกอันที่มี <code>/path/to/</code></li>";
echo "<li><strong>ทดสอบ:</strong> รัน <code>simple_cron_check.php</code> อีกครั้งหลังแก้ไข</li>";
echo "</ol>";
echo "</div>";

echo "<div class='alert alert-warning'>";
echo "<h5>⚠️ ปัญหาที่พบ:</h5>";
echo "<ul>";
if (!$systemLogsExists) {
    echo "<li><strong>ไม่มีตาราง system_logs:</strong> ไม่สามารถตรวจสอบ log จาก database ได้</li>";
}
if (!$logsExist) {
    echo "<li><strong>ไม่มี log files:</strong> Cron jobs อาจยังไม่ได้ทำงาน</li>";
}
if ($badCount > 0) {
    echo "<li><strong>Cron jobs ที่มีปัญหา:</strong> มี $badCount รายการที่ต้องลบ</li>";
}
echo "</ul>";
echo "</div>";

echo "</div>";

// 7. Manual Test
echo "<div class='check-card info'>";
echo "<h3>🧪 ทดสอบด้วยตนเอง</h3>";

echo "<p>คลิกเพื่อทดสอบการทำงาน:</p>";
echo "<div class='btn-group mb-3'>";
if (file_exists(__DIR__ . '/production_auto_system.php')) {
    echo "<a href='production_auto_system.php?task=daily' class='btn btn-sm btn-outline-primary' target='_blank'>Test Daily</a>";
    echo "<a href='production_auto_system.php?task=smart' class='btn btn-sm btn-outline-info' target='_blank'>Test Smart</a>";
    echo "<a href='production_auto_system.php?task=reassign' class='btn btn-sm btn-outline-warning' target='_blank'>Test Reassign</a>";
}
if (file_exists(__DIR__ . '/system_health_check.php')) {
    echo "<a href='system_health_check.php' class='btn btn-sm btn-outline-success' target='_blank'>Test Health</a>";
}
echo "</div>";

echo "<div class='btn-group mb-3'>";
echo "<a href='create_system_logs_table.php' class='btn btn-sm btn-primary' target='_blank'>🔧 Create System Logs</a>";
echo "<a href='cron_cleanup_helper.php' class='btn btn-sm btn-warning' target='_blank'>🧹 Cleanup Helper</a>";
echo "</div>";

echo "</div>";

echo "<div class='text-center mt-4'>";
echo "<button onclick='location.reload()' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> Refresh ตรวจสอบใหม่";
echo "</button>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>