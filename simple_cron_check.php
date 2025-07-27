<?php
// simple_cron_check.php
// ตรวจสอบการทำงานของ Cron Jobs แบบง่าย

session_start();

// Bypass auth for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔍 Simple Cron Check</title>";
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
echo "<h1 class='display-6 fw-bold text-primary'>🔍 Simple Cron Check</h1>";
echo "<p class='lead text-muted'>ตรวจสอบการทำงานของ Cron Jobs แบบง่าย</p>";
echo "<small class='text-muted'>เวลาตรวจสอบ: " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // 1. ตรวจสอบ System Logs
    echo "<div class='check-card working'>";
    echo "<h3>📊 ตรวจสอบจาก System Logs</h3>";
    
    $logQueries = [
        'auto_system' => [
            'name' => 'Auto System',
            'query' => "SELECT COUNT(*) as count, MAX(created_at) as last_run FROM system_logs WHERE log_type = 'auto_system' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            'expected' => 'ควรมีการทำงานภายใน 24 ชั่วโมง'
        ],
        'health_check' => [
            'name' => 'Health Check', 
            'query' => "SELECT COUNT(*) as count, MAX(created_at) as last_run FROM system_logs WHERE log_type = 'health_check' AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)",
            'expected' => 'ควรมีการทำงานภายใน 2 ชั่วโมง'
        ]
    ];
    
    foreach ($logQueries as $key => $check) {
        $stmt = $pdo->prepare($check['query']);
        $stmt->execute();
        $result = $stmt->fetch();
        
        $count = $result['count'];
        $lastRun = $result['last_run'];
        
        echo "<div class='metric " . ($count > 0 ? 'good' : 'bad') . "'>";
        echo "<h5>{$check['name']}</h5>";
        echo "<p><strong>การทำงาน:</strong> $count ครั้ง</p>";
        if ($lastRun) {
            $timeAgo = time() - strtotime($lastRun);
            $timeText = '';
            if ($timeAgo < 3600) {
                $timeText = floor($timeAgo / 60) . ' นาทีที่แล้ว';
            } elseif ($timeAgo < 86400) {
                $timeText = floor($timeAgo / 3600) . ' ชั่วโมงที่แล้ว';
            } else {
                $timeText = floor($timeAgo / 86400) . ' วันที่แล้ว';
            }
            echo "<p><strong>ครั้งล่าสุด:</strong> " . date('d/m H:i', strtotime($lastRun)) . " ($timeText)</p>";
        } else {
            echo "<p><strong>ครั้งล่าสุด:</strong> <span class='status-bad'>ไม่เคยทำงาน</span></p>";
        }
        echo "<small class='text-muted'>{$check['expected']}</small>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // 2. ตรวจสอบ Log Files
    echo "<div class='check-card info'>";
    echo "<h3>📁 ตรวจสอบ Log Files</h3>";
    
    $logFiles = [
        'logs/cron_daily.log' => 'Daily Cleanup',
        'logs/cron_smart.log' => 'Smart Update', 
        'logs/cron_reassign.log' => 'Auto Reassign',
        'logs/cron_full.log' => 'Full System',
        'logs/health_check.log' => 'Health Check'
    ];
    
    $logsExist = false;
    foreach ($logFiles as $file => $name) {
        $fullPath = __DIR__ . '/' . $file;
        echo "<div class='metric " . (file_exists($fullPath) ? 'good' : 'bad') . "'>";
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
                echo " <span class='status-good'>(" . floor($age / 60) . " นาทีที่แล้ว)</span>";
            } elseif ($age < 86400) {
                echo " <span class='status-warn'>(" . floor($age / 3600) . " ชั่วโมงที่แล้ว)</span>";
            } else {
                echo " <span class='status-bad'>(" . floor($age / 86400) . " วันที่แล้ว)</span>";
            }
            echo "</p>";
            
            // แสดง log ล่าสุด
            if ($size > 0) {
                $lines = file($fullPath);
                if ($lines && count($lines) > 0) {
                    $lastLines = array_slice($lines, -5);
                    echo "<details><summary>Log ล่าสุด (5 บรรทัด)</summary>";
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
        echo "<p>อาจเป็นเพราะ Cron Jobs ยังไม่ได้ทำงาน หรือ path ไม่ถูกต้อง</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // 3. ตรวจสอบไฟล์ที่จำเป็น
    echo "<div class='check-card info'>";
    echo "<h3>🔧 ตรวจสอบไฟล์ระบบ</h3>";
    
    $systemFiles = [
        'production_auto_system.php' => 'Production Auto System',
        'system_health_check.php' => 'Health Check System',
        'auto_customer_management.php' => 'Legacy Auto Management',
        'cron/run_auto_rules.sh' => 'Auto Rules Script'
    ];
    
    foreach ($systemFiles as $file => $name) {
        $fullPath = __DIR__ . '/' . $file;
        echo "<div class='metric " . (file_exists($fullPath) ? 'good' : 'bad') . "'>";
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
                echo "<p><strong>Executable:</strong> " . ($isExecutable ? "✅" : "❌") . "</p>";
            }
        } else {
            echo "<p><span class='status-bad'>ไม่พบไฟล์</span></p>";
        }
        echo "</div>";
    }
    
    echo "</div>";
    
    // 4. ตรวจสอบ Database Tables
    echo "<div class='check-card working'>";
    echo "<h3>🗄️ ตรวจสอบ Database</h3>";
    
    $tableChecks = [
        'customers' => "SELECT COUNT(*) as count FROM customers",
        'system_logs' => "SELECT COUNT(*) as count FROM system_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)",
        'tasks' => "SELECT COUNT(*) as count FROM tasks WHERE DATE(FollowupDate) = CURDATE()"
    ];
    
    foreach ($tableChecks as $table => $query) {
        echo "<div class='metric good'>";
        echo "<h6>Table: $table</h6>";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            $count = $result['count'];
            
            echo "<p><strong>จำนวนข้อมูล:</strong> " . number_format($count) . " รายการ</p>";
            
            if ($table === 'system_logs') {
                echo "<small class='text-muted'>Log ใน 7 วันที่ผ่านมา</small>";
            } elseif ($table === 'tasks') {
                echo "<small class='text-muted'>งานวันนี้</small>";
            }
            
        } catch (Exception $e) {
            echo "<p><span class='status-bad'>Error: " . htmlspecialchars($e->getMessage()) . "</span></p>";
        }
        
        echo "</div>";
    }
    
    echo "</div>";
    
    // 5. รายการ Cron Jobs ที่คุณติดตั้ง
    echo "<div class='check-card warning'>";
    echo "<h3>📋 Cron Jobs ที่คุณติดตั้งไว้</h3>";
    echo "<p class='text-muted'>จากที่คุณแจ้งมา มี Cron Jobs ต่อไปนี้:</p>";
    
    $installedCrons = [
        ['0 2 * * *', 'curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1"', 'Auto Status Manager (Web)'],
        ['0 1 * * *', '/home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh', 'Auto Rules Script'],
        ['0 1 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=daily', '⚠️ Path ไม่ถูกต้อง'],
        ['0 2 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=smart', '⚠️ Path ไม่ถูกต้อง'],
        ['0 */6 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=reassign', '⚠️ Path ไม่ถูกต้อง'],
        ['*/30 * * * *', 'php /path/to/system_health_check.php', '⚠️ Path ไม่ถูกต้อง'],
        ['0 1 * * *', 'php production_auto_system.php daily', '✅ ใหม่ - Daily'],
        ['0 2 * * *', 'php production_auto_system.php smart', '✅ ใหม่ - Smart'],
        ['0 */6 * * *', 'php production_auto_system.php reassign', '✅ ใหม่ - Reassign'],
        ['0 3 * * 0', 'php production_auto_system.php all', '✅ ใหม่ - Full System'],
        ['*/30 8-18 * * 1-6', 'php system_health_check.php', '✅ ใหม่ - Health Check']
    ];
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Schedule</th><th>Command</th><th>หมายเหตุ</th></tr></thead><tbody>";
    
    foreach ($installedCrons as $cron) {
        $rowClass = strpos($cron[2], '⚠️') !== false ? 'table-warning' : 
                   (strpos($cron[2], '✅') !== false ? 'table-success' : '');
        
        echo "<tr class='$rowClass'>";
        echo "<td><code>{$cron[0]}</code></td>";
        echo "<td><small>" . htmlspecialchars($cron[1]) . "</small></td>";
        echo "<td>{$cron[2]}</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "<div class='alert alert-info mt-3'>";
    echo "<h5>💡 สังเกต:</h5>";
    echo "<ul>";
    echo "<li><strong>✅ ใหม่:</strong> Cron Jobs ที่ใช้ไฟล์ใหม่ (production_auto_system.php, system_health_check.php)</li>";
    echo "<li><strong>⚠️ Path ไม่ถูกต้อง:</strong> ใช้ /path/to/ ซึ่งไม่ใช่ path จริง</li>";
    echo "<li><strong>Duplicate:</strong> มี Jobs ที่ทำงานซ้ำกัน เช่น Daily Cleanup มี 2 อัน</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    
    // 6. คำแนะนำ
    echo "<div class='check-card error'>";
    echo "<h3>🔧 คำแนะนำการแก้ไข</h3>";
    
    echo "<h5>ปัญหาที่พบ:</h5>";
    echo "<ol>";
    echo "<li><strong>Path ไม่ถูกต้อง:</strong> /path/to/ ต้องเปลี่ยนเป็น path จริง</li>";
    echo "<li><strong>Cron Jobs ซ้ำ:</strong> มีหลายตัวทำงานเดียวกัน</li>";
    echo "<li><strong>Schedule ชนกัน:</strong> หลายตัวรันเวลาเดียวกัน</li>";
    echo "</ol>";
    
    echo "<h5>ขั้นตอนแก้ไข:</h5>";
    echo "<div class='alert alert-warning'>";
    echo "<h6>1. ลบ Cron Jobs ที่ path ไม่ถูกต้อง</h6>";
    echo "<p>ลบ jobs ที่มี <code>/path/to/</code></p>";
    echo "</div>";
    
    echo "<div class='alert alert-success'>";
    echo "<h6>2. ใช้เฉพาะ Cron Jobs ใหม่</h6>";
    echo "<p>เก็บเฉพาะ jobs ที่ใช้ production_auto_system.php และ system_health_check.php</p>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h6>3. Path ที่ถูกต้อง:</h6>";
    echo "<code>" . realpath(__DIR__ . '/production_auto_system.php') . "</code><br>";
    echo "<code>" . realpath(__DIR__ . '/system_health_check.php') . "</code>";
    echo "</div>";
    
    echo "</div>";
    
    // 7. Manual Test
    echo "<div class='check-card info'>";
    echo "<h3>🧪 ทดสอบด้วยตนเอง</h3>";
    
    echo "<p>คลิกเพื่อทดสอบการทำงาน:</p>";
    echo "<div class='btn-group mb-3'>";
    echo "<a href='production_auto_system.php?task=daily' class='btn btn-sm btn-outline-primary' target='_blank'>Test Daily</a>";
    echo "<a href='production_auto_system.php?task=smart' class='btn btn-sm btn-outline-info' target='_blank'>Test Smart</a>";
    echo "<a href='production_auto_system.php?task=reassign' class='btn btn-sm btn-outline-warning' target='_blank'>Test Reassign</a>";
    echo "<a href='system_health_check.php' class='btn btn-sm btn-outline-success' target='_blank'>Test Health</a>";
    echo "</div>";
    
    echo "<p><strong>หลังจากคลิกแล้ว:</strong></p>";
    echo "<ul>";
    echo "<li>ดูว่าหน้าทำงานปกติหรือไม่</li>";
    echo "<li>กลับมา Refresh หน้านี้เพื่อดู Logs ใหม่</li>";
    echo "<li>ตรวจสอบว่ามี Log files เกิดขึ้นหรือไม่</li>";
    echo "</ul>";
    
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Database Error</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='text-center mt-4'>";
echo "<button onclick='location.reload()' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> Refresh ตรวจสอบใหม่";
echo "</button>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>