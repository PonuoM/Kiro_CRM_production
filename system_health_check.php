<?php
/**
 * System Health Check - Comprehensive diagnostic tool
 */

require_once 'config/database.php';

try {
    echo "<h2>🏥 ตรวจสอบสุขภาพระบบ CRM</h2>";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $healthScore = 100;
    
    // 1. Database Connection Test
    echo "<h3>🔌 การเชื่อมต่อฐานข้อมูล</h3>";
    try {
        $stmt = $pdo->query("SELECT NOW() as current_time, DATABASE() as db_name");
        $dbInfo = $stmt->fetch();
        
        echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
        echo "✅ การเชื่อมต่อฐานข้อมูลปกติ<br>";
        echo "📅 เวลาเซิร์ฟเวอร์: " . $dbInfo['current_time'] . "<br>";
        echo "🗄️ ฐานข้อมูล: " . $dbInfo['db_name'] . "<br>";
        echo "</div>";
    } catch (Exception $e) {
        $healthScore -= 50;
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0;'>";
        echo "❌ การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage();
        echo "</div>";
    }
    
    // 2. Table Check
    echo "<h3>📋 ตรวจสอบตาราง</h3>";
    
    $tables = ['users', 'customers', 'orders'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "✅ ตาราง $table: $count รายการ<br>";
        } catch (Exception $e) {
            echo "❌ ตาราง $table: " . $e->getMessage() . "<br>";
            $healthScore -= 15;
        }
    }
    
    // 3. Health Summary
    echo "<h3>🎯 สรุป</h3>";
    
    $color = $healthScore >= 90 ? '#d4edda' : ($healthScore >= 70 ? '#fff3cd' : '#f8d7da');
    $status = $healthScore >= 90 ? 'ดีเยี่ยม' : ($healthScore >= 70 ? 'พอใช้' : 'ต้องแก้ไข');
    
    echo "<div style='background:$color; padding:15px; border-radius:8px;'>";
    echo "<h4>คะแนนสุขภาพ: $healthScore/100 ($status)</h4>";
    
    if ($healthScore < 100) {
        echo "<strong>📋 แนะนำให้ดำเนินการ:</strong><br>";
        echo "1. รัน <a href='complete_database_repair.php'>complete_database_repair.php</a><br>";
        echo "2. รัน <a href='create_sample_data_fixed.php'>create_sample_data_fixed.php</a><br>";
        echo "3. ทดสอบหน้าเว็บต่างๆ<br>";
    } else {
        echo "✅ ระบบทำงานปกติ";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; padding:15px; border-radius:8px;'>";
    echo "❌ ข้อผิดพลาด: " . $e->getMessage();
    echo "</div>";
}
?>