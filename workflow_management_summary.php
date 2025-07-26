<?php
/**
 * Workflow Management Summary
 * สรุประบบจัดการ workflow และการติดตั้งระบบอัตโนมัติ
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h1>🎯 Workflow Management System - Complete Solution</h1>";
echo "<p>ระบบจัดการ workflow ที่สมบูรณ์สำหรับ Kiro CRM พร้อมระบบอัตโนมัติ</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $dbConnected = true;
} catch (Exception $e) {
    $dbConnected = false;
}

// ตรวจสอบสถานะระบบ
echo "<h2>📊 System Status Check</h2>";

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
echo "<h3>🗄️ Database Connection</h3>";
if ($dbConnected) {
    echo "<span style='color: green;'>✅ Database connected successfully</span><br>";
    
    // ตรวจสอบตาราง system_logs
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        $systemLogsExists = $stmt->rowCount() > 0;
        
        if ($systemLogsExists) {
            echo "<span style='color: green;'>✅ system_logs table exists</span><br>";
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_logs");
            $logCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<span style='color: blue;'>ℹ️ Total log entries: $logCount</span><br>";
        } else {
            echo "<span style='color: orange;'>⚠️ system_logs table not found</span><br>";
            echo "<a href='install_system_logs.php' style='background: #ffc107; color: black; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Install Now</a><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error checking system_logs: " . $e->getMessage() . "</span><br>";
    }
    
} else {
    echo "<span style='color: red;'>❌ Database connection failed</span><br>";
}
echo "</div>";

// 2. ตรวจสอบข้อมูลลูกค้า
if ($dbConnected) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
    echo "<h3>👥 Customer Data Status</h3>";
    
    try {
        // สถิติลูกค้าตาม CartStatus
        $stmt = $pdo->query("SELECT CartStatus, COUNT(*) as count FROM customers GROUP BY CartStatus ORDER BY count DESC");
        $cartStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Cart Status</th><th>Count</th><th>Percentage</th></tr>";
        
        $totalCustomers = array_sum(array_column($cartStats, 'count'));
        foreach ($cartStats as $stat) {
            $percentage = $totalCustomers > 0 ? round(($stat['count'] / $totalCustomers) * 100, 1) : 0;
            $bgColor = $stat['CartStatus'] === 'กำลังดูแล' ? '#e8f5e8' : '#fff';
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$stat['CartStatus']}</strong></td>";
            echo "<td>{$stat['count']}</td>";
            echo "<td>{$percentage}%</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // ตรวจสอบลูกค้าที่ต้องการความสนใจ
        $stmt = $pdo->query("
            SELECT COUNT(*) as need_attention 
            FROM customers 
            WHERE CustomerStatus = 'ลูกค้าใหม่' 
            AND CartStatus != 'กำลังดูแล' 
            AND Sales IS NOT NULL
        ");
        $needAttention = $stmt->fetch(PDO::FETCH_ASSOC)['need_attention'];
        
        if ($needAttention > 0) {
            echo "<span style='color: orange;'>⚠️ $needAttention customers need CartStatus fix</span><br>";
            echo "<a href='fix_workflow_data.php' style='background: #ffc107; color: black; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Fix Now</a><br>";
        } else {
            echo "<span style='color: green;'>✅ All customers have correct CartStatus</span><br>";
        }
        
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Error checking customer data: " . $e->getMessage() . "</span><br>";
    }
    
    echo "</div>";
}

// 3. Workflow Summary
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f8f9fa;'>";
echo "<h3>🔄 Correct Workflow Process</h3>";
echo "<div style='display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;'>";

echo "<div style='text-align: center; padding: 10px; background: #e3f2fd; border-radius: 5px; margin: 5px; flex: 1; min-width: 200px;'>";
echo "<strong>Step 1: Admin แจก</strong><br>";
echo "CustomerStatus: ลูกค้าใหม่<br>";
echo "CartStatus: <strong>กำลังดูแล</strong><br>";
echo "Sales: sales01/sales02";
echo "</div>";

echo "<div style='text-align: center; padding: 5px;'>→</div>";

echo "<div style='text-align: center; padding: 10px; background: #e8f5e8; border-radius: 5px; margin: 5px; flex: 1; min-width: 200px;'>";
echo "<strong>Step 2: Sales ติดตาม</strong><br>";
echo "อัปเดต CustomerStatus<br>";
echo "ทำงาน tasks<br>";
echo "บันทึกผลการติดตาม";
echo "</div>";

echo "<div style='text-align: center; padding: 5px;'>→</div>";

echo "<div style='text-align: center; padding: 10px; background: #fff3cd; border-radius: 5px; margin: 5px; flex: 1; min-width: 200px;'>";
echo "<strong>Step 3: ระบบอัตโนมัติ</strong><br>";
echo "30 วัน → ตะกร้าแจก<br>";
echo "3 เดือน → ตะกร้ารอ<br>";
echo "รันทุกวันเวลา 02:00";
echo "</div>";

echo "</div>";
echo "</div>";

// 4. Available Tools
echo "<h2>🛠️ Available Management Tools</h2>";

$tools = [
    [
        'name' => 'Fix Workflow Data',
        'file' => 'fix_workflow_data.php',
        'description' => 'ปรับปรุงข้อมูลให้ตรงกับ workflow ที่ถูกต้อง',
        'icon' => '🔧',
        'color' => '#17a2b8'
    ],
    [
        'name' => 'Auto Status Manager',
        'file' => 'auto_status_manager.php',
        'description' => 'ระบบจัดการสถานะลูกค้าอัตโนมัติ (30 วัน/3 เดือน)',
        'icon' => '⚙️',
        'color' => '#28a745'
    ],
    [
        'name' => 'Create Daily Tasks',
        'file' => 'create_daily_tasks.php',
        'description' => 'สร้างงานประจำวันสำหรับลูกค้า "กำลังดูแล"',
        'icon' => '📅',
        'color' => '#ffc107'
    ],
    [
        'name' => 'Install System Logs',
        'file' => 'install_system_logs.php',
        'description' => 'ติดตั้งตาราง system_logs สำหรับเก็บ log',
        'icon' => '🗄️',
        'color' => '#6f42c1'
    ],
    [
        'name' => 'Debug Daily Tasks',
        'file' => 'debug_daily_tasks.php',
        'description' => 'วิเคราะห์ปัญหาการแสดงงานประจำวัน',
        'icon' => '🔍',
        'color' => '#fd7e14'
    ],
    [
        'name' => 'Simple Login Test',
        'file' => 'simple_login_test.php',
        'description' => 'ทดสอบการ login และตรวจสอบ session',
        'icon' => '🔑',
        'color' => '#dc3545'
    ]
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";

foreach ($tools as $tool) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: white;'>";
    echo "<h4 style='color: {$tool['color']}; margin-top: 0;'>{$tool['icon']} {$tool['name']}</h4>";
    echo "<p style='font-size: 14px; color: #666; margin: 10px 0;'>{$tool['description']}</p>";
    echo "<a href='{$tool['file']}' style='background: {$tool['color']}; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; font-size: 14px;'>Open Tool</a>";
    echo "</div>";
}

echo "</div>";

// 5. Cron Job Setup
echo "<h2>⏰ Cron Job Setup</h2>";
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f8f9fa;'>";
echo "<h3>วิธีตั้งค่า Cron Job สำหรับระบบอัตโนมัติ</h3>";

echo "<strong>1. สำหรับ cPanel/WHM:</strong><br>";
echo "<code style='background: #e9ecef; padding: 10px; display: block; margin: 10px 0; border-radius: 5px;'>";
echo "0 2 * * * /usr/bin/curl -s \"https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1\" > /dev/null 2>&1";
echo "</code>";

echo "<strong>2. สำหรับ Ubuntu/Linux Server:</strong><br>";
echo "<code style='background: #e9ecef; padding: 10px; display: block; margin: 10px 0; border-radius: 5px;'>";
echo "# เปิด crontab<br>";
echo "crontab -e<br><br>";
echo "# เพิ่มบรรทัดนี้<br>";
echo "0 2 * * * /usr/bin/curl -s \"https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1\" > /dev/null 2>&1";
echo "</code>";

echo "<strong>3. ตรวจสอบ Cron Job ทำงาน:</strong><br>";
echo "- ดู log ใน system_logs table<br>";
echo "- ตรวจสอบการเปลี่ยนแปลง CartStatus<br>";
echo "- รัน manual test ด้วย auto_status_manager.php<br>";

echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
echo "⚠️ <strong>สำคัญ:</strong> ทดสอบด้วย Dry Run ก่อนเสมอ (ไม่ใส่ ?execute=1)";
echo "</div>";

echo "</div>";

// 6. Troubleshooting
echo "<h2>🚨 Troubleshooting</h2>";
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";

echo "<h3>ปัญหาที่พบบ่อย:</h3>";
echo "<ul>";
echo "<li><strong>Daily tasks แสดงแค่ 2 รายการ:</strong> ใช้ create_daily_tasks.php</li>";
echo "<li><strong>CartStatus ไม่ถูกต้อง:</strong> ใช้ fix_workflow_data.php</li>";
echo "<li><strong>Login ไม่ได้:</strong> ใช้ simple_login_test.php</li>";
echo "<li><strong>ระบบอัตโนมัติไม่ทำงาน:</strong> ตรวจสอบ cron job และ system_logs</li>";
echo "<li><strong>Permission denied:</strong> ตรวจสอบ file permissions (644 สำหรับ .php files)</li>";
echo "</ul>";

echo "<h3>ขั้นตอนการแก้ไขปัญหา:</h3>";
echo "<ol>";
echo "<li>ตรวจสอบ Database connection</li>";
echo "<li>ตรวจสอบ session และ login status</li>";
echo "<li>รัน debug tools เพื่อวิเคราะห์ปัญหา</li>";
echo "<li>ตรวจสอบ error logs ใน server</li>";
echo "<li>ทดสอบแต่ละขั้นตอนแยกๆ</li>";
echo "</ol>";

echo "</div>";

echo "<h2>📞 Support</h2>";
echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px; background: #e3f2fd;'>";
echo "<p>หากต้องการความช่วยเหลือเพิ่มเติม:</p>";
echo "<ul>";
echo "<li>ตรวจสอบ system_logs table สำหรับ error messages</li>";
echo "<li>รัน debug tools เพื่อวิเคราะห์ปัญหาเฉพาะ</li>";
echo "<li>ส่ง error message พร้อม screenshot ถ้ามี</li>";
echo "</ul>";
echo "</div>";

echo "<div style='text-align: center; margin: 30px 0; padding: 20px; background: #d4edda; border-radius: 8px;'>";
echo "<h3 style='color: #155724; margin-top: 0;'>🎉 Workflow Management System Ready!</h3>";
echo "<p>ระบบจัดการ workflow พร้อมใช้งานแล้ว รวมทั้งระบบอัตโนมัติสำหรับจัดการสถานะลูกค้า</p>";
echo "<a href='pages/daily_tasks_demo.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🚀 Go to Daily Tasks</a>";
echo "</div>";
?>