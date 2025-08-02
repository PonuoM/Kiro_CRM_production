<?php
/**
 * Setup Cron Jobs with Activity Logging
 * คำแนะนำการตั้งค่า Cron Jobs สำหรับ Auto Rules พร้อม Activity Log
 */

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>⏰ Setup Cron Jobs</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "<style>body{font-family:Arial,sans-serif;padding:15px;background:#f8f9fa;} .section{margin:15px 0;padding:15px;border:2px solid #ddd;border-radius:8px;background:white;}</style>";
echo "</head><body>";

echo "<h1>⏰ Setup Cron Jobs with Activity Logging</h1>";
echo "<p>คำแนะนำการตั้งค่า Cron Jobs สำหรับ Auto Rules พร้อมระบบ Activity Log</p>";

echo "<div class='section'>";
echo "<h3><i class='fas fa-robot'></i> 🎯 Cron Jobs ที่แนะนำ</h3>";

echo "<div class='alert alert-info'>";
echo "<h4>📋 รายการ Cron Jobs สำหรับ CRM:</h4>";
echo "</div>";

$cronJobs = [
    [
        'name' => 'Auto Rules with Activity Logging',
        'schedule' => '0 2 * * *',
        'frequency' => 'ทุกวันเวลา 02:00 น.',
        'command' => '/usr/bin/php /full/path/to/cron/auto_rules_with_activity_log.php >> /full/path/to/logs/cron_auto_rules_activity.log 2>&1',
        'description' => 'รัน Auto Rules พร้อมบันทึก Activity Log ทุกการเปลี่ยนแปลง',
        'priority' => 'สูง',
        'color' => 'success'
    ],
    [
        'name' => 'Daily Cleanup',
        'schedule' => '0 1 * * *',
        'frequency' => 'ทุกวันเวลา 01:00 น.',
        'command' => 'cd /full/path/to/project && php production_auto_system.php daily >> logs/cron_daily.log 2>&1',
        'description' => 'ทำความสะอาดข้อมูลลูกค้าทุกวัน',
        'priority' => 'กลาง',
        'color' => 'primary'
    ],
    [
        'name' => 'Smart Update (Temperature & Grade)',
        'schedule' => '0 3 * * *',
        'frequency' => 'ทุกวันเวลา 03:00 น.',
        'command' => 'cd /full/path/to/project && php production_auto_system.php smart >> logs/cron_smart.log 2>&1',
        'description' => 'อัปเดต Temperature และ Grade ของลูกค้า',
        'priority' => 'กลาง',
        'color' => 'info'
    ],
    [
        'name' => 'Auto Reassign',
        'schedule' => '0 */6 * * *',
        'frequency' => 'ทุก 6 ชั่วโมง',
        'command' => 'cd /full/path/to/project && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1',
        'description' => 'จัดสรรลูกค้าใหม่อัตโนมัติ',
        'priority' => 'กลาง',
        'color' => 'warning'
    ],
    [
        'name' => 'Health Check',
        'schedule' => '*/30 8-18 * * 1-6',
        'frequency' => 'ทุก 30 นาที (เวลาทำงาน)',
        'command' => 'cd /full/path/to/project && php system_health_check.php >> logs/health_check.log 2>&1',
        'description' => 'ตรวจสอบสุขภาพระบบ',
        'priority' => 'ต่ำ',
        'color' => 'secondary'
    ]
];

foreach ($cronJobs as $job) {
    echo "<div class='card mb-3'>";
    echo "<div class='card-header bg-{$job['color']} text-white'>";
    echo "<h5 class='mb-0'><i class='fas fa-clock'></i> {$job['name']}</h5>";
    echo "<small>Priority: {$job['priority']} | {$job['frequency']}</small>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<p>{$job['description']}</p>";
    echo "<div class='row'>";
    echo "<div class='col-md-3'>";
    echo "<strong>Schedule:</strong><br>";
    echo "<code class='badge bg-dark'>{$job['schedule']}</code>";
    echo "</div>";
    echo "<div class='col-md-9'>";
    echo "<strong>Command:</strong><br>";
    echo "<code style='background:#f8f9fa;padding:5px;border-radius:3px;font-size:11px;'>";
    echo htmlspecialchars($job['command']);
    echo "</code>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";

echo "<div class='section'>";
echo "<h3><i class='fas fa-terminal'></i> 🔧 วิธีการตั้งค่า Cron Jobs</h3>";

echo "<div class='alert alert-warning'>";
echo "<h4>⚠️ สำคัญ: แก้ไข Path ให้ถูกต้อง</h4>";
echo "<p>เปลี่ยน <code>/full/path/to/</code> เป็น path จริงของโปรเจ็กต์</p>";
echo "<p>เช่น: <code>/home/primacom/public_html/crm_system/Kiro_CRM_production/</code></p>";
echo "</div>";

echo "<h4>📝 ขั้นตอนการตั้งค่า:</h4>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h5>1. เปิด Crontab Editor</h5>";
echo "<pre style='background:#2d3748;color:#e2e8f0;padding:10px;border-radius:5px;'>crontab -e</pre>";

echo "<h5>2. เพิ่ม Cron Jobs</h5>";
echo "<p>Copy และแก้ไข path แล้ววางใน crontab:</p>";
echo "</div>";

echo "<div class='col-md-6'>";
echo "<h5>3. ตรวจสอบ Cron Jobs</h5>";
echo "<pre style='background:#2d3748;color:#e2e8f0;padding:10px;border-radius:5px;'>crontab -l</pre>";

echo "<h5>4. ตรวจสอบ Log Files</h5>";
echo "<pre style='background:#2d3748;color:#e2e8f0;padding:10px;border-radius:5px;'>tail -f logs/cron_auto_rules_activity.log</pre>";
echo "</div>";
echo "</div>";

echo "<h4>📋 Crontab Content (Copy ไปใช้):</h4>";
echo "<pre style='background:#f8f9fa;padding:15px;border-radius:5px;border:1px solid #ddd;'>";

$crontabContent = "# CRM Auto Rules with Activity Logging
# ================================

# Auto Rules with Activity Logging (หลัก - สำคัญที่สุด)
0 2 * * * /usr/bin/php /home/primacom/public_html/crm_system/Kiro_CRM_production/cron/auto_rules_with_activity_log.php >> /home/primacom/public_html/crm_system/Kiro_CRM_production/logs/cron_auto_rules_activity.log 2>&1

# Daily System Maintenance
0 1 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php daily >> logs/cron_daily.log 2>&1

# Smart Update (Temperature & Grade)
0 3 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php smart >> logs/cron_smart.log 2>&1

# Auto Reassign (ทุก 6 ชั่วโมง)
0 */6 * * * cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php production_auto_system.php reassign >> logs/cron_reassign.log 2>&1

# Health Check (เวลาทำงาน)
*/30 8-18 * * 1-6 cd /home/primacom/public_html/crm_system/Kiro_CRM_production && php system_health_check.php >> logs/health_check.log 2>&1";

echo htmlspecialchars($crontabContent);
echo "</pre>";

echo "</div>";

echo "<div class='section'>";
echo "<h3><i class='fas fa-check-circle'></i> ✅ ขั้นตอนการทดสอบ</h3>";

echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5>1. ทดสอบ Activity Logger</h5>";
echo "<p>ทดสอบการบันทึก Log</p>";
echo "<a href='test_activity_logger.php' class='btn btn-primary' target='_blank'>";
echo "<i class='fas fa-flask'></i> ทดสอบ Logger";
echo "</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5>2. ทดสอบ Auto Rules</h5>";
echo "<p>รัน Auto Rules Manual</p>";
echo "<a href='cron/auto_rules_with_activity_log.php' class='btn btn-success' target='_blank'>";
echo "<i class='fas fa-robot'></i> ทดสอบ Auto Rules";
echo "</a>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='card text-center'>";
echo "<div class='card-body'>";
echo "<h5>3. ดู Activity Log</h5>";
echo "<p>ตรวจสอบผลลัพธ์</p>";
echo "<a href='view_customer_activity.php' class='btn btn-info' target='_blank'>";
echo "<i class='fas fa-list'></i> ดู Activity Log";
echo "</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

echo "<div class='section'>";
echo "<h3><i class='fas fa-lightbulb'></i> 💡 คำแนะนำเพิ่มเติม</h3>";

echo "<div class='alert alert-success'>";
echo "<h4>✅ ข้อดีของระบบใหม่:</h4>";
echo "<ul>";
echo "<li><strong>📋 Activity Log:</strong> ทวนสอบการเปลี่ยนแปลงได้ทุกอย่าง</li>";
echo "<li><strong>🎯 Logic 90 วัน:</strong> ใช้ Logic ที่ถูกต้องตามต้องการ</li>";
echo "<li><strong>🔄 Sales Column Clear:</strong> ลบ Sales อัตโนมัติเมื่อย้ายลูกค้า</li>";
echo "<li><strong>📊 Detailed Tracking:</strong> บันทึกเหตุผลและกฎที่ใช้</li>";
echo "</ul>";
echo "</div>";

echo "<div class='alert alert-info'>";
echo "<h4>📋 ลำดับการทำงาน Auto Rules:</h4>";
echo "<ol>";
echo "<li><strong>01:00</strong> - Daily Cleanup</li>";
echo "<li><strong>02:00</strong> - Auto Rules with Activity Logging (หลัก)</li>";
echo "<li><strong>03:00</strong> - Smart Update (Temperature & Grade)</li>";
echo "<li><strong>ทุก 6 ชม.</strong> - Auto Reassign</li>";
echo "<li><strong>ทุก 30 นาที</strong> - Health Check (เวลาทำงาน)</li>";
echo "</ol>";
echo "</div>";

echo "<div class='alert alert-warning'>";
echo "<h4>⚠️ สิ่งที่ต้องระวัง:</h4>";
echo "<ul>";
echo "<li><strong>Path:</strong> แก้ไข path ให้ถูกต้องก่อนตั้ง cron</li>";
echo "<li><strong>Permissions:</strong> ตรวจสอบ file permissions</li>";
echo "<li><strong>Log Space:</strong> ตรวจสอบพื้นที่ disk สำหรับ log files</li>";
echo "<li><strong>Timezone:</strong> ตรวจสอบ timezone ของ server</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<div class='text-center mt-4'>";
echo "<a href='cron_status_final.php' class='btn btn-secondary btn-lg'>";
echo "<i class='fas fa-chart-line'></i> ดูสถานะ Cron Jobs";
echo "</a>";
echo "</div>";

echo "</body></html>";
?>