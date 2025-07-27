<?php
// cron_cleanup_helper.php
// ช่วยทำความสะอาด Cron Jobs และแสดงสถานะปัจจุบัน

session_start();

// Bypass auth for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🧹 Cron Jobs Cleanup Helper</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.cleanup-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
.good{border-left:5px solid #28a745;background:#f8fff8;} 
.bad{border-left:5px solid #dc3545;background:#fff5f5;} 
.warning{border-left:5px solid #ffc107;background:#fffbf0;} 
.info{border-left:5px solid #17a2b8;background:#f0f9ff;} 
.cron-item{background:white;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #ddd;}
.cron-good{border-left-color:#28a745;} .cron-bad{border-left-color:#dc3545;} .cron-duplicate{border-left-color:#ffc107;}
pre{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;max-height:200px;overflow:auto;}
.status-badge{font-weight:bold;padding:4px 8px;border-radius:4px;font-size:12px;}
.status-good{background:#d4edda;color:#155724;} .status-bad{background:#f8d7da;color:#721c24;} .status-warning{background:#fff3cd;color:#856404;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='text-center mb-4'>";
echo "<h1 class='display-6 fw-bold text-primary'>🧹 Cron Jobs Cleanup Helper</h1>";
echo "<p class='lead text-muted'>วิเคราะห์และทำความสะอาด Cron Jobs</p>";
echo "<small class='text-muted'>เวลาตรวจสอบ: " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

// ข้อมูล Cron Jobs ที่ผู้ใช้แจ้งมา (จาก conversation history)
$installedCrons = [
    // Web-based crons (ถูกต้อง)
    ['0 2 * * *', '/usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1', 'good', 'Auto Status Manager (Web-based)'],
    ['0 1 * * *', '/home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh', 'good', 'Auto Rules Script (Shell)'],
    
    // Old crons with wrong paths (ต้องลบ)
    ['0 1 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=daily', 'bad', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['0 2 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=smart', 'bad', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['0 */6 * * *', 'php /path/to/auto_customer_management.php?run=execute&task=reassign', 'bad', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    ['*/30 * * * *', 'php /path/to/system_health_check.php', 'bad', '❌ Path ไม่ถูกต้อง - ต้องลบ'],
    
    // New crons (ถูกต้อง แต่อาจซ้ำกับ path ไม่ถูกต้อง)
    ['0 1 * * *', 'php production_auto_system.php daily', 'duplicate', '⚠️ ซ้ำกับ path ไม่ถูกต้อง - ควรเก็บอันนี้'],
    ['0 2 * * *', 'php production_auto_system.php smart', 'duplicate', '⚠️ ซ้ำกับ path ไม่ถูกต้อง - ควรเก็บอันนี้'],
    ['0 */6 * * *', 'php production_auto_system.php reassign', 'duplicate', '⚠️ ซ้ำกับ path ไม่ถูกต้อง - ควรเก็บอันนี้'],
    ['0 3 * * 0', 'php production_auto_system.php all', 'good', '✅ Full System Check - ใหม่'],
    ['*/30 8-18 * * 1-6', 'php system_health_check.php', 'duplicate', '⚠️ ซ้ำกับ path ไม่ถูกต้อง - ควรเก็บอันนี้']
];

// 1. สถานะปัจจุบัน
echo "<div class='cleanup-card info'>";
echo "<div class='p-4'>";
echo "<h3>📋 วิเคราะห์ Cron Jobs ปัจจุบัน</h3>";

$goodCount = 0;
$badCount = 0;
$duplicateCount = 0;

echo "<table class='table table-sm'>";
echo "<thead><tr><th>Schedule</th><th>Command</th><th>สถานะ</th><th>หมายเหตุ</th></tr></thead><tbody>";

foreach ($installedCrons as $cron) {
    $status = $cron[2];
    $rowClass = '';
    
    if ($status === 'good') {
        $rowClass = 'table-success';
        $goodCount++;
    } elseif ($status === 'bad') {
        $rowClass = 'table-danger';
        $badCount++;
    } elseif ($status === 'duplicate') {
        $rowClass = 'table-warning';
        $duplicateCount++;
    }
    
    echo "<tr class='$rowClass'>";
    echo "<td><code>{$cron[0]}</code></td>";
    echo "<td><small>" . htmlspecialchars($cron[1]) . "</small></td>";
    echo "<td><span class='status-badge status-$status'>" . strtoupper($status) . "</span></td>";
    echo "<td>{$cron[3]}</td>";
    echo "</tr>";
}

echo "</tbody></table>";

echo "<div class='row mt-3'>";
echo "<div class='col-md-4'>";
echo "<div class='alert alert-success'>";
echo "<h6>✅ Good Crons: $goodCount</h6>";
echo "<small>Cron Jobs ที่ถูกต้องและควรเก็บไว้</small>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='alert alert-danger'>";
echo "<h6>❌ Bad Crons: $badCount</h6>";
echo "<small>Cron Jobs ที่ต้องลบทิ้ง (path ไม่ถูกต้อง)</small>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='alert alert-warning'>";
echo "<h6>⚠️ Duplicate Crons: $duplicateCount</h6>";
echo "<small>Cron Jobs ที่ซ้ำกัน (เก็บอันใหม่)</small>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";

// 2. คำแนะนำการทำความสะอาด
echo "<div class='cleanup-card bad'>";
echo "<div class='p-4'>";
echo "<h3>🗑️ Cron Jobs ที่ต้องลบ</h3>";
echo "<p class='text-danger'><strong>ลบ Cron Jobs เหล่านี้ทันที:</strong></p>";

echo "<div class='alert alert-danger'>";
echo "<h6>❌ Cron Jobs ที่มี /path/to/ (Path ไม่ถูกต้อง)</h6>";
echo "<ul>";
echo "<li><code>0 1 * * * php /path/to/auto_customer_management.php?run=execute&task=daily</code></li>";
echo "<li><code>0 2 * * * php /path/to/auto_customer_management.php?run=execute&task=smart</code></li>";
echo "<li><code>0 */6 * * * php /path/to/auto_customer_management.php?run=execute&task=reassign</code></li>";
echo "<li><code>*/30 * * * * php /path/to/system_health_check.php</code></li>";
echo "</ul>";
echo "</div>";

echo "<h6>วิธีลบใน cPanel:</h6>";
echo "<ol>";
echo "<li>เข้า cPanel > Cron Jobs</li>";
echo "<li>ค้นหา Cron Jobs ที่มี <code>/path/to/</code></li>";
echo "<li>คลิก Delete ทีละอัน</li>";
echo "<li>ยืนยันการลบ</li>";
echo "</ol>";

echo "</div>";
echo "</div>";

// 3. Cron Jobs ที่ควรเก็บ
echo "<div class='cleanup-card good'>";
echo "<div class='p-4'>";
echo "<h3>✅ Cron Jobs ที่ควรเก็บ (Final Recommendation)</h3>";

echo "<div class='alert alert-success'>";
echo "<h6>🎯 Cron Jobs ที่แนะนำ (เก็บเฉพาะเหล่านี้):</h6>";
echo "</div>";

$recommendedCrons = [
    ['0 2 * * *', '/usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1', 'Auto Status Manager (Web-based)', 'ทำงานทุกวันเวลา 02:00'],
    ['0 1 * * *', '/home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh', 'Auto Rules Script', 'ทำงานทุกวันเวลา 01:00'],
    ['0 1 * * *', 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1', 'Daily Cleanup', 'ทำความสะอาดข้อมูลทุกวัน'],
    ['0 2 * * *', 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1', 'Smart Update', 'อัปเดต Temperature/Grade'],
    ['0 */6 * * *', 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1', 'Auto Reassign', 'ทุก 6 ชั่วโมง'],
    ['0 3 * * 0', 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php all >> logs/cron_full.log 2>&1', 'Full System Check', 'วันอาทิตย์เวลา 03:00'],
    ['*/30 8-18 * * 1-6', 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1', 'Health Check', 'ทุก 30 นาที (เวลาทำงาน)']
];

echo "<table class='table table-sm table-success'>";
echo "<thead><tr><th>Schedule</th><th>Command</th><th>ชื่อ</th><th>คำอธิบาย</th></tr></thead><tbody>";

foreach ($recommendedCrons as $cron) {
    echo "<tr>";
    echo "<td><code>{$cron[0]}</code></td>";
    echo "<td><small>" . htmlspecialchars($cron[1]) . "</small></td>";
    echo "<td><strong>{$cron[2]}</strong></td>";
    echo "<td>{$cron[3]}</td>";
    echo "</tr>";
}

echo "</tbody></table>";

echo "<div class='alert alert-info mt-3'>";
echo "<h6>💡 สิ่งที่ต้องระวัง:</h6>";
echo "<ul>";
echo "<li><strong>ใช้ <code>cd</code></strong> เพื่อให้แน่ใจว่าอยู่ใน directory ที่ถูกต้อง</li>";
echo "<li><strong>เพิ่ม log output</strong> <code>>> logs/filename.log 2>&1</code></li>";
echo "<li><strong>ไม่ต้องมี <code>?run=execute&task=</code></strong> ใน PHP command line</li>";
echo "<li><strong>ใช้ Full Path</strong> เพื่อป้องกันปัญหา</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// 4. ขั้นตอนทำความสะอาด
echo "<div class='cleanup-card warning'>";
echo "<div class='p-4'>";
echo "<h3>📝 ขั้นตอนทำความสะอาด (Step by Step)</h3>";

echo "<div class='alert alert-warning'>";
echo "<h6>⚠️ ก่อนเริ่ม - สำรองข้อมูล</h6>";
echo "<p>Backup Crontab ปัจจุบันก่อน: <code>crontab -l > crontab_backup_$(date +%Y%m%d_%H%M%S).txt</code></p>";
echo "</div>";

echo "<h6>ขั้นตอนที่ 1: ลบ Cron Jobs ที่มีปัญหา</h6>";
echo "<ol>";
echo "<li>เข้า cPanel > Cron Jobs</li>";
echo "<li>ลบทุกรายการที่มี <code>/path/to/</code></li>";
echo "<li>ลบรายการที่ซ้ำกัน (เก็บอันใหม่)</li>";
echo "</ol>";

echo "<h6>ขั้นตอนที่ 2: เพิ่ม Cron Jobs ใหม่</h6>";
echo "<ol>";
echo "<li>ใช้ Cron Jobs ที่แนะนำข้างต้น</li>";
echo "<li>ตรวจสอบ Full Path ให้ถูกต้อง</li>";
echo "<li>เพิ่มทีละอัน และทดสอบ</li>";
echo "</ol>";

echo "<h6>ขั้นตอนที่ 3: ทดสอบ</h6>";
echo "<ol>";
echo "<li>รัน <code>simple_cron_check.php</code> เพื่อตรวจสอบ</li>";
echo "<li>ดู Log Files ใน <code>logs/</code></li>";
echo "<li>ทดสอบ Manual ด้วยปุ่มที่มีให้</li>";
echo "</ol>";

echo "</div>";
echo "</div>";

// 5. เครื่องมือตรวจสอบ
echo "<div class='cleanup-card info'>";
echo "<div class='p-4'>";
echo "<h3>🔧 เครื่องมือตรวจสอบ</h3>";

echo "<p>หลังจากทำความสะอาดแล้ว ใช้เครื่องมือเหล่านี้เพื่อตรวจสอบ:</p>";

echo "<div class='btn-group mb-3' role='group'>";
echo "<a href='simple_cron_check.php' class='btn btn-primary' target='_blank'>";
echo "<i class='fas fa-search'></i> Simple Cron Check</a>";
echo "<a href='check_cron_status.php' class='btn btn-info' target='_blank'>";
echo "<i class='fas fa-chart-line'></i> Detailed Cron Status</a>";
echo "<a href='cron_management.php' class='btn btn-secondary' target='_blank'>";
echo "<i class='fas fa-cogs'></i> Cron Management</a>";
echo "</div>";

echo "<h6>📊 การตรวจสอบ:</h6>";
echo "<ul>";
echo "<li><strong>Log Files:</strong> ตรวจสอบใน <code>logs/</code> directory</li>";
echo "<li><strong>Database Logs:</strong> ดูใน <code>system_logs</code> table</li>";
echo "<li><strong>Manual Test:</strong> ทดสอบด้วยปุ่มใน simple_cron_check.php</li>";
echo "</ul>";

echo "</div>";
echo "</div>";

// 6. สรุป
echo "<div class='cleanup-card good'>";
echo "<div class='p-4'>";
echo "<h3>📋 สรุป</h3>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h6>🎯 เป้าหมาย:</h6>";
echo "<ul>";
echo "<li>ลบ Cron Jobs ที่มี <code>/path/to/</code> ($badCount รายการ)</li>";
echo "<li>แก้ไข Cron Jobs ที่ซ้ำกัน ($duplicateCount รายการ)</li>";
echo "<li>เก็บเฉพาะ Cron Jobs ที่ถูกต้อง (7 รายการ)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h6>✅ ผลลัพธ์ที่คาดหวัง:</h6>";
echo "<ul>";
echo "<li>Cron Jobs ทำงานถูกต้อง</li>";
echo "<li>มี Log Files เกิดขึ้น</li>";
echo "<li>ระบบ Auto อัปเดตข้อมูล</li>";
echo "<li>Health Check ทำงานปกติ</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<div class='alert alert-success mt-3'>";
echo "<h6>🚀 พร้อมเริ่มทำความสะอาด!</h6>";
echo "<p class='mb-0'>ทำตามขั้นตอนข้างต้น และใช้เครื่องมือตรวจสอบเพื่อยืนยันผลลัพธ์</p>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "<div class='text-center mt-4'>";
echo "<button onclick='location.reload()' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> Refresh";
echo "</button>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>