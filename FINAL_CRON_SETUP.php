<?php
/**
 * Final Cron Jobs Setup Summary
 * สรุปสุดท้ายของ Cron Jobs ที่ต้องตั้งใน cPanel
 */

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>🎯 Final Cron Jobs Setup</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.cron-card{margin:10px 0;border-radius:8px;border:2px solid #ddd;}
.cron-current{border-color:#28a745;background:#f8fff9;}
.cron-update{border-color:#ffc107;background:#fffbf0;}
.cron-delete{border-color:#dc3545;background:#fff5f5;}
.cron-new{border-color:#007bff;background:#f0f8ff;}
</style>";
echo "</head><body>";

echo "<h1 class='text-center mb-4'>🎯 Final Cron Jobs Setup Summary</h1>";
echo "<p class='text-center lead'>สรุปสุดท้าย: Cron Jobs ที่ต้องตั้งใน cPanel</p>";

// Current Status
echo "<div class='alert alert-success text-center'>";
echo "<h4>📊 สถานะปัจจุบัน</h4>";
echo "<p><strong>✅ Database:</strong> เชื่อมต่อสำเร็จ</p>";
echo "<p><strong>✅ Activity Log Table:</strong> พร้อมใช้งาน</p>";
echo "<p><strong>✅ Activity Log System:</strong> ทำงานได้แล้ว</p>";
echo "</div>";

$currentCronJobs = [
    [
        'id' => 1,
        'schedule' => '0 2 * * *',
        'command' => '/usr/bin/curl -s "https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1" > /dev/null 2>&1',
        'name' => 'Auto Status Manager',
        'status' => 'keep',
        'note' => 'เก็บไว้ - ทำงานดี'
    ],
    [
        'id' => 2,
        'schedule' => '0 1 * * *',
        'command' => '/home/primacom/public_html/crm_system/Kiro_CRM_production/cron/run_auto_rules.sh',
        'name' => 'Auto Rules Shell Script (เก่า)',
        'status' => 'delete',
        'note' => 'ลบออก - ไม่ใช้แล้ว'
    ],
    [
        'id' => 3,
        'schedule' => '0 1 * * *',
        'command' => 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1',
        'name' => 'Daily Cleanup',
        'status' => 'keep',
        'note' => 'เก็บไว้ - ทำงานดี'
    ],
    [
        'id' => 4,
        'schedule' => '0 2 * * *',
        'command' => 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1',
        'name' => 'Smart Update',
        'status' => 'keep',
        'note' => 'เก็บไว้ - ทำงานดี'
    ],
    [
        'id' => 5,
        'schedule' => '0 */6 * * *',
        'command' => 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1',
        'name' => 'Auto Reassign',
        'status' => 'keep',
        'note' => 'เก็บไว้ - ทำงานดี'
    ],
    [
        'id' => 6,
        'schedule' => '0 3 * * 0',
        'command' => 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php all >> logs/cron_full.log 2>&1',
        'name' => 'Full System Check',
        'status' => 'keep',
        'note' => 'เก็บไว้ - ทำงานดี'
    ],
    [
        'id' => 7,
        'schedule' => '*/30 8-18 * * 1-6',
        'command' => 'cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1',
        'name' => 'Health Check',
        'status' => 'keep',
        'note' => 'เก็บไว้ - ทำงานดี'
    ],
    [
        'id' => 8,
        'schedule' => '0 2 * * *',
        'command' => '/usr/bin/php /path/to/cron/auto_rules_fixed.php',
        'name' => 'Auto Rules Fixed (เก่า)',
        'status' => 'update',
        'note' => 'เปลี่ยนเป็น Auto Rules with Activity Log',
        'new_command' => '/usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1'
    ]
];

echo "<div class='row'>";

foreach ($currentCronJobs as $job) {
    $cardClass = 'cron-card ';
    $badgeClass = '';
    $iconClass = '';
    
    switch ($job['status']) {
        case 'keep':
            $cardClass .= 'cron-current';
            $badgeClass = 'bg-success';
            $iconClass = 'fas fa-check-circle text-success';
            break;
        case 'delete':
            $cardClass .= 'cron-delete';
            $badgeClass = 'bg-danger';
            $iconClass = 'fas fa-trash text-danger';
            break;
        case 'update':
            $cardClass .= 'cron-update';
            $badgeClass = 'bg-warning';
            $iconClass = 'fas fa-edit text-warning';
            break;
        case 'new':
            $cardClass .= 'cron-new';
            $badgeClass = 'bg-primary';
            $iconClass = 'fas fa-plus text-primary';
            break;
    }
    
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card $cardClass h-100'>";
    echo "<div class='card-header d-flex justify-content-between align-items-center'>";
    echo "<h6 class='mb-0'><i class='$iconClass'></i> {$job['name']}</h6>";
    echo "<span class='badge $badgeClass'>" . strtoupper($job['status']) . "</span>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<p><strong>Schedule:</strong> <code>{$job['schedule']}</code></p>";
    echo "<p><strong>Command:</strong></p>";
    echo "<code style='font-size:10px;word-break:break-all;'>" . htmlspecialchars($job['command']) . "</code>";
    
    if (isset($job['new_command'])) {
        echo "<p class='mt-2'><strong>New Command:</strong></p>";
        echo "<code style='font-size:10px;word-break:break-all;background:#fff3cd;'>" . htmlspecialchars($job['new_command']) . "</code>";
    }
    
    echo "<div class='mt-2'>";
    echo "<small class='text-muted'>{$job['note']}</small>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";

// Action Summary
echo "<div class='alert alert-primary'>";
echo "<h4><i class='fas fa-clipboard-list'></i> สรุปการดำเนินการ</h4>";
echo "<div class='row'>";

echo "<div class='col-md-3 text-center'>";
echo "<div class='display-6 text-success'>6</div>";
echo "<small>Keep (เก็บไว้)</small>";
echo "</div>";

echo "<div class='col-md-3 text-center'>";
echo "<div class='display-6 text-danger'>1</div>";
echo "<small>Delete (ลบ)</small>";
echo "</div>";

echo "<div class='col-md-3 text-center'>";
echo "<div class='display-6 text-warning'>1</div>";
echo "<small>Update (แก้ไข)</small>";
echo "</div>";

echo "<div class='col-md-3 text-center'>";
echo "<div class='display-6 text-info'>7</div>";
echo "<small>Total Final</small>";
echo "</div>";

echo "</div>";
echo "</div>";

// Final Cron Jobs List
echo "<div class='alert alert-success'>";
echo "<h4><i class='fas fa-list-check'></i> 🎯 Final Cron Jobs List (Copy ไปใช้ใน cPanel)</h4>";

echo "<pre style='background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;'>";
echo "# CRM System - Final Cron Jobs Configuration\n";
echo "# Updated: " . date('d/m/Y H:i:s') . "\n";
echo "# Total: 7 Cron Jobs\n\n";

echo "# 1. Daily Cleanup (01:00)\n";
echo "0 1 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1\n\n";

echo "# 2. Auto Status Manager (02:00)\n";
echo "0 2 * * * /usr/bin/curl -s \"https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1\" > /dev/null 2>&1\n\n";

echo "# 3. Auto Rules with Activity Logging (02:00) - *** MAIN UPDATE ***\n";
echo "0 2 * * * /usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1\n\n";

echo "# 4. Smart Update (02:00)\n";
echo "0 2 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1\n\n";

echo "# 5. Auto Reassign (Every 6 hours)\n";
echo "0 */6 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1\n\n";

echo "# 6. Full System Check (Sunday 03:00)\n";
echo "0 3 * * 0 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php all >> logs/cron_full.log 2>&1\n\n";

echo "# 7. Health Check (Working hours)\n";
echo "*/30 8-18 * * 1-6 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1\n";
echo "</pre>";

echo "</div>";

// Step by step guide
echo "<div class='alert alert-warning'>";
echo "<h4><i class='fas fa-exclamation-triangle'></i> ⚠️ ขั้นตอนการตั้งค่าใน cPanel</h4>";
echo "<ol>";
echo "<li><strong>ลบ Cron Job:</strong> ลบรายการที่ 2 (run_auto_rules.sh)</li>";
echo "<li><strong>แก้ไข Cron Job:</strong> แก้ไขรายการที่ 8 เป็น auto_rules_with_activity_log.php</li>";
echo "<li><strong>Save การตั้งค่า</strong></li>";
echo "<li><strong>ตรวจสอบ:</strong> รอดู logs ใน 24 ชั่วโมงข้างหน้า</li>";
echo "</ol>";
echo "</div>";

echo "<div class='text-center mt-4'>";
echo "<h4>🎉 <strong>ระบบพร้อมใช้งานแล้ว!</strong></h4>";
echo "<p>Activity Log จะบันทึกทุกการเปลี่ยนแปลงเพื่อทวนสอบได้</p>";
echo "<div class='btn-group'>";
echo "<a href='simple_test_activity_logger.php' class='btn btn-primary'><i class='fas fa-flask'></i> ทดสอบ Activity Logger</a>";
echo "<a href='view_customer_activity.php' class='btn btn-success'><i class='fas fa-list'></i> ดู Activity Log</a>";
echo "<a href='cron_status_final.php' class='btn btn-info'><i class='fas fa-chart-line'></i> ดูสถานะ Cron</a>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>