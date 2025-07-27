<?php
// auto_customer_management.php
// ระบบจัดการลูกค้าอัตโนมัติ - ป้องกันไม่ต้องแก้ไขเองทุกครั้ง

session_start();

// Simple auth bypass for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'system_admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🤖 Auto Customer Management System</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:15px;} .system-section{margin:15px 0;padding:12px;border:2px solid #ddd;border-radius:8px;} .automated{border-color:#28a745;background:#f8fff8;} .manual{border-color:#17a2b8;background:#f0f9ff;} .cron{border-color:#6f42c1;background:#f8f4ff;} pre{background:#f8f9fa;padding:10px;border-radius:4px;}</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<h1>🤖 ระบบจัดการลูกค้าอัตโนมัติ</h1>";
echo "<p class='text-muted'>แก้ปัญหาไม่ต้องแก้ไขข้อมูลเองทุกครั้ง</p>";

$runMode = isset($_GET['run']) ? $_GET['run'] : 'preview';

if ($runMode === 'execute') {
    echo "<div class='alert alert-success'>";
    echo "<h4>🚀 กำลังรันระบบอัตโนมัติ...</h4>";
    echo "</div>";
} else {
    echo "<div class='alert alert-info'>";
    echo "<h4>👁️ โหมดแสดงตัวอย่าง</h4>";
    echo "<p>ระบบจะทำงานอย่างไรเมื่อเปิดใช้งาน <a href='?run=execute' class='btn btn-sm btn-success'>🚀 Run Auto System</a></p>";
    echo "</div>";
}

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    if ($runMode === 'execute') {
        $pdo->beginTransaction();
    }
    
    // Auto Rule 1: Auto-reassign ลูกค้าเลยเวลา
    echo "<div class='system-section automated'>";
    echo "<h2>🔄 Auto Rule 1: Auto-reassign ลูกค้าเลยเวลา</h2>";
    
    echo "<h4>📋 Business Rules:</h4>";
    echo "<ul>";
    echo "<li><strong>ลูกค้าใหม่เลย 30 วัน:</strong> ส่งกลับ Pool</li>";
    echo "<li><strong>ลูกค้าติดตามเลย 14 วัน:</strong> เปลี่ยน Sales หรือส่งกลับ Pool</li>";
    echo "<li><strong>ลูกค้าเก่าไม่ติดต่อเลย 90 วัน:</strong> เปลี่ยนเป็น FROZEN</li>";
    echo "</ul>";
    
    // Check ลูกค้าที่ต้อง auto-reassign
    $autoRules = [
        'new_overdue' => [
            'description' => 'ลูกค้าใหม่เลย 30 วัน',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30",
            'action_query' => "UPDATE customers SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30",
            'action_desc' => 'ส่งกลับ Pool'
        ],
        'follow_overdue' => [
            'description' => 'ลูกค้าติดตามเลย 14 วัน',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14",
            'action_query' => "UPDATE customers SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) BETWEEN 15 AND 30",
            'action_desc' => 'ส่งกลับ Pool (14-30 วัน)'
        ],
        'old_frozen' => [
            'description' => 'ลูกค้าเก่าไม่ติดต่อเลย 90 วัน',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90",
            'action_query' => "UPDATE customers SET CustomerTemperature = 'FROZEN', CustomerGrade = 'D' WHERE CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90",
            'action_desc' => 'เปลี่ยนเป็น FROZEN'
        ]
    ];
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Rule</th><th>จำนวนที่ต้องแก้ไข</th><th>การดำเนินการ</th><th>สถานะ</th></tr></thead><tbody>";
    
    $totalAutoFixed = 0;
    foreach ($autoRules as $ruleKey => $rule) {
        $stmt = $pdo->prepare($rule['query']);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $totalAutoFixed += $count;
        
        echo "<tr>";
        echo "<td><strong>" . $rule['description'] . "</strong></td>";
        echo "<td><span class='badge bg-warning'>$count</span></td>";
        echo "<td>" . $rule['action_desc'] . "</td>";
        
        if ($runMode === 'execute' && $count > 0) {
            $actionStmt = $pdo->prepare($rule['action_query']);
            $actionResult = $actionStmt->execute();
            $affectedRows = $actionStmt->rowCount();
            echo "<td><span class='badge bg-success'>แก้ไข $affectedRows รายการ</span></td>";
        } else {
            echo "<td><span class='badge bg-info'>พร้อมแก้ไข</span></td>";
        }
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    if ($runMode === 'execute') {
        echo "<div class='alert alert-success'>✅ Auto-reassign เรียบร้อย: รวม $totalAutoFixed รายการ</div>";
    } else {
        echo "<div class='alert alert-info'>📋 พร้อม Auto-reassign: รวม $totalAutoFixed รายการ</div>";
    }
    
    echo "</div>";
    
    // Auto Rule 2: Smart Grade/Temperature Update
    echo "<div class='system-section automated'>";
    echo "<h2>🌡️ Auto Rule 2: Smart Grade/Temperature Update</h2>";
    
    echo "<h4>🧠 Smart Logic:</h4>";
    echo "<ul>";
    echo "<li><strong>Temperature:</strong> ตามระยะเวลาติดต่อล่าสุด</li>";
    echo "<li><strong>Grade:</strong> ตามสถานะและ Temperature</li>";
    echo "<li><strong>Auto-adjust:</strong> ทุกวันเวลา 02:00 AM</li>";
    echo "</ul>";
    
    if ($runMode === 'execute') {
        // Update Temperature based on last contact
        $tempUpdateSql = "UPDATE customers SET 
            CustomerTemperature = CASE 
                WHEN Sales IS NULL OR CustomerStatus = 'ในตระกร้า' THEN 'FROZEN'
                WHEN LastContactDate IS NULL OR DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 'FROZEN'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 3 THEN 'HOT'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 7 THEN 'WARM'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 14 THEN 'COLD'
                ELSE 'FROZEN'
            END";
        
        $stmt = $pdo->prepare($tempUpdateSql);
        $stmt->execute();
        $tempUpdated = $stmt->rowCount();
        
        // Update Grade based on status and temperature
        $gradeUpdateSql = "UPDATE customers SET 
            CustomerGrade = CASE 
                WHEN CustomerStatus = 'ในตระกร้า' OR CustomerTemperature = 'FROZEN' THEN 'D'
                WHEN CustomerStatus = 'ลูกค้าใหม่' AND CustomerTemperature IN ('HOT', 'WARM') THEN 'A'
                WHEN CustomerStatus = 'ลูกค้าติดตาม' AND CustomerTemperature IN ('WARM', 'COLD') THEN 'B'
                WHEN CustomerStatus = 'ลูกค้าเก่า' THEN 'C'
                ELSE 'D'
            END";
        
        $stmt = $pdo->prepare($gradeUpdateSql);
        $stmt->execute();
        $gradeUpdated = $stmt->rowCount();
        
        echo "<div class='alert alert-success'>✅ อัปเดต Temperature: $tempUpdated รายการ</div>";
        echo "<div class='alert alert-success'>✅ อัปเดต Grade: $gradeUpdated รายการ</div>";
    } else {
        echo "<div class='alert alert-info'>📋 พร้อมอัปเดต Grade/Temperature อัตโนมัติ</div>";
    }
    
    echo "</div>";
    
    // Auto Rule 3: Daily Cleanup Tasks
    echo "<div class='system-section automated'>";
    echo "<h2>🧹 Auto Rule 3: Daily Cleanup Tasks</h2>";
    
    echo "<h4>🔄 Daily Tasks (รันทุกวันเวลา 01:00 AM):</h4>";
    
    $dailyTasks = [
        'fix_invalid_status' => [
            'name' => 'แก้ไขสถานะไม่สมเหตุสมผล',
            'description' => 'ลูกค้าใหม่ไม่มี Sales → ในตระกร้า',
            'query' => "UPDATE customers SET CustomerStatus = 'ในตระกร้า', AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL"
        ],
        'remove_sales_from_basket' => [
            'name' => 'ลบ Sales จากลูกค้าในตระกร้า',
            'description' => 'ลูกค้าในตระกร้าที่มี Sales → ลบ Sales',
            'query' => "UPDATE customers SET Sales = NULL WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL"
        ],
        'update_last_modified' => [
            'name' => 'อัปเดต ModifiedDate',
            'description' => 'อัปเดตวันที่แก้ไขล่าสุด',
            'query' => "UPDATE customers SET ModifiedDate = NOW() WHERE ModifiedDate < DATE_SUB(NOW(), INTERVAL 1 DAY)"
        ]
    ];
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Task</th><th>รายละเอียด</th><th>สถานะ</th></tr></thead><tbody>";
    
    foreach ($dailyTasks as $taskKey => $task) {
        echo "<tr>";
        echo "<td><strong>" . $task['name'] . "</strong></td>";
        echo "<td>" . $task['description'] . "</td>";
        
        if ($runMode === 'execute') {
            $stmt = $pdo->prepare($task['query']);
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<td><span class='badge bg-success'>ดำเนินการ $affected รายการ</span></td>";
        } else {
            echo "<td><span class='badge bg-info'>พร้อมดำเนินการ</span></td>";
        }
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "</div>";
    
    // Cron Job Setup
    echo "<div class='system-section cron'>";
    echo "<h2>⏰ Cron Job Setup</h2>";
    
    echo "<h4>📅 กำหนดการรันอัตโนมัติ:</h4>";
    
    $cronJobs = [
        'daily_cleanup' => [
            'time' => '0 1 * * *',
            'description' => 'Daily Cleanup - รันทุกวันเวลา 01:00 AM',
            'command' => 'php /path/to/auto_customer_management.php?run=execute&task=daily'
        ],
        'smart_update' => [
            'time' => '0 2 * * *', 
            'description' => 'Smart Grade/Temperature Update - รันทุกวันเวลา 02:00 AM',
            'command' => 'php /path/to/auto_customer_management.php?run=execute&task=smart'
        ],
        'auto_reassign' => [
            'time' => '0 */6 * * *',
            'description' => 'Auto-reassign - รันทุก 6 ชั่วโมง',
            'command' => 'php /path/to/auto_customer_management.php?run=execute&task=reassign'
        ],
        'health_check' => [
            'time' => '*/30 * * * *',
            'description' => 'System Health Check - รันทุก 30 นาที',
            'command' => 'php /path/to/system_health_check.php'
        ]
    ];
    
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Cron Schedule</th><th>Task</th><th>Command</th></tr></thead><tbody>";
    
    foreach ($cronJobs as $job) {
        echo "<tr>";
        echo "<td><code>" . $job['time'] . "</code></td>";
        echo "<td>" . $job['description'] . "</td>";
        echo "<td><small><code>" . $job['command'] . "</code></small></td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "<h4>🔧 วิธีติดตั้ง Cron Job:</h4>";
    echo "<pre>";
    echo "# เปิด crontab editor\n";
    echo "crontab -e\n\n";
    echo "# เพิ่มบรรทัดต่อไปนี้:\n";
    foreach ($cronJobs as $job) {
        echo $job['time'] . " " . $job['command'] . "\n";
    }
    echo "</pre>";
    
    echo "</div>";
    
    // Summary & Monitoring
    echo "<div class='system-section manual'>";
    echo "<h2>📊 System Summary & Monitoring</h2>";
    
    if ($runMode === 'execute') {
        $pdo->commit();
        
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ ระบบอัตโนมัติทำงานเรียบร้อย!</h4>";
        echo "<p>ข้อมูลได้รับการทำความสะอาดและปรับปรุงอัตโนมัติแล้ว</p>";
        echo "</div>";
        
        // Log the activity
        $logSql = "INSERT INTO system_logs (log_type, message, created_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($logSql);
        $stmt->execute(['auto_management', 'Auto customer management system executed successfully']);
    }
    
    echo "<h4>🎯 ประโยชน์ของระบบอัตโนมัติ:</h4>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<h5>✅ ข้อดี:</h5>";
    echo "<ul>";
    echo "<li>ไม่ต้องแก้ไขข้อมูลเองทุกครั้ง</li>";
    echo "<li>ข้อมูลสะอาดและตรง Logic เสมอ</li>";
    echo "<li>ลดภาระงาน Admin/Supervisor</li>";
    echo "<li>ป้องกันข้อผิดพลาดจากมนุษย์</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<h5>📋 การติดตาม:</h5>";
    echo "<ul>";
    echo "<li>System Logs สำหรับติดตามการทำงาน</li>";
    echo "<li>Email Alert เมื่อมีปัญหา</li>";
    echo "<li>Dashboard สำหรับ Monitor</li>";
    echo "<li>Reports รายสัปดาห์/เดือน</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h5>🚀 Next Steps:</h5>";
    echo "<ol>";
    echo "<li><strong>ติดตั้ง Cron Jobs</strong> ตามตารางข้างต้น</li>";
    echo "<li><strong>ทดสอบระบบ</strong> รันทดลองสัก 1-2 วัน</li>";
    echo "<li><strong>Monitor ผลลัพธ์</strong> ตรวจสอบว่าทำงานถูกต้อง</li>";
    echo "<li><strong>Fine-tune Rules</strong> ปรับแต่ง Business Rules ตามต้องการ</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    if ($runMode === 'execute') {
        $pdo->rollback();
    }
    echo "<div class='alert alert-danger'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>"; // container

echo "</body></html>";
?>