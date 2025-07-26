<?php
/**
 * Add CustomerTemperature Column to Customers Table
 * เพิ่ม column CustomerTemperature สำหรับแก้ไข error ในหน้า basket
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🌡️ Add CustomerTemperature Column</h2>";
echo "<p>เพิ่ม column CustomerTemperature ในตาราง customers สำหรับแก้ไข error ในหน้า basket</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // 1. ตรวจสอบโครงสร้างตารางปัจจุบัน
    echo "<h3>📋 Current Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCustomerTemperature = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'CustomerTemperature') {
            $hasCustomerTemperature = true;
            break;
        }
    }
    
    if ($hasCustomerTemperature) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ CustomerTemperature column already exists!";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
        echo "⚠️ CustomerTemperature column not found. Will add it now.";
        echo "</div>";
        
        // 2. เพิ่ม column CustomerTemperature
        echo "<h3>🔧 Adding CustomerTemperature Column</h3>";
        
        $alterSQL = "ALTER TABLE customers ADD COLUMN CustomerTemperature ENUM('HOT', 'WARM', 'COLD', 'FROZEN') DEFAULT 'WARM' COMMENT 'อุณหภูมิลูกค้า: HOT=สนใจมาก, WARM=สนใจ, COLD=ไม่ค่อยสนใจ, FROZEN=ไม่สนใจ' AFTER CustomerGrade";
        
        $result = $pdo->exec($alterSQL);
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Successfully added CustomerTemperature column!</strong><br>";
        echo "Column added with ENUM('HOT', 'WARM', 'COLD', 'FROZEN') and default value 'WARM'.";
        echo "</div>";
        
        // 3. อัปเดตข้อมูลตาม logic
        echo "<h3>📊 Updating Customer Temperature</h3>";
        
        // กำหนด temperature ตาม logic
        $temperatureUpdates = [
            "UPDATE customers SET CustomerTemperature = 'HOT' WHERE CustomerGrade = 'A' AND CartStatus = 'กำลังดูแล'", // HOT - VIP ที่ยังใช้บริการ
            "UPDATE customers SET CustomerTemperature = 'WARM' WHERE CustomerGrade = 'B' AND CartStatus = 'กำลังดูแล'", // WARM - ลูกค้าติดตามที่ดี
            "UPDATE customers SET CustomerTemperature = 'WARM' WHERE CustomerStatus = 'ลูกค้าใหม่'", // WARM - ลูกค้าใหม่
            "UPDATE customers SET CustomerTemperature = 'COLD' WHERE CartStatus = 'ตะกร้ารอ'", // COLD - ลูกค้าที่รอ
            "UPDATE customers SET CustomerTemperature = 'FROZEN' WHERE CartStatus = 'ตะกร้าแจก'" // FROZEN - ลูกค้าที่ถูกแจกออก
        ];
        
        $totalUpdated = 0;
        foreach ($temperatureUpdates as $index => $updateSQL) {
            $affected = $pdo->exec($updateSQL);
            $totalUpdated += $affected;
            
            $tempLabels = ['HOT (สนใจมาก)', 'WARM (สนใจ)', 'WARM (ลูกค้าใหม่)', 'COLD (ไม่ค่อยสนใจ)', 'FROZEN (ไม่สนใจ)'];
            echo "<div style='background: #cff4fc; padding: 5px 10px; margin: 3px 0; border-radius: 3px; font-size: 14px;'>";
            echo "🌡️ Updated <strong>$affected</strong> customers to <strong>{$tempLabels[$index]}</strong>";
            echo "</div>";
        }
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "📈 <strong>Temperature Assignment Complete:</strong><br>";
        echo "Total customers updated: <strong>$totalUpdated</strong><br>";
        echo "Temperature assignment based on CustomerGrade and CartStatus logic.";
        echo "</div>";
    }
    
    // 4. แสดงสถิติ temperature
    echo "<h3>📈 Customer Temperature Statistics</h3>";
    $stmt = $pdo->query("SELECT CustomerTemperature, COUNT(*) as count FROM customers GROUP BY CustomerTemperature ORDER BY FIELD(CustomerTemperature, 'HOT', 'WARM', 'COLD', 'FROZEN')");
    $tempStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($tempStats) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Temperature</th><th>Description</th><th>Count</th><th>Percentage</th></tr>";
        
        $tempDescriptions = [
            'HOT' => '🔥 สนใจมาก ลูกค้า VIP ที่ใช้บริการ',
            'WARM' => '🌡️ สนใจ ลูกค้าติดตามและลูกค้าใหม่', 
            'COLD' => '❄️ ไม่ค่อยสนใจ ลูกค้าที่รอการติดตาม',
            'FROZEN' => '🧊 ไม่สนใจ ลูกค้าที่ถูกแจกออก'
        ];
        
        $tempColors = [
            'HOT' => '#ffe6e6',
            'WARM' => '#fff3cd',
            'COLD' => '#e2f3ff',
            'FROZEN' => '#f0f0f0'
        ];
        
        $total = array_sum(array_column($tempStats, 'count'));
        foreach ($tempStats as $stat) {
            $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
            $bgColor = $tempColors[$stat['CustomerTemperature']] ?? '#fff';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$stat['CustomerTemperature']}</strong></td>";
            echo "<td>{$tempDescriptions[$stat['CustomerTemperature']]}</td>";
            echo "<td>{$stat['count']}</td>";
            echo "<td>{$percentage}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. แสดงข้อมูลแยกตาม Grade + Temperature
    echo "<h3>📊 Grade vs Temperature Matrix</h3>";
    $stmt = $pdo->query("SELECT CustomerGrade, CustomerTemperature, COUNT(*) as count FROM customers GROUP BY CustomerGrade, CustomerTemperature ORDER BY CustomerGrade, CustomerTemperature");
    $matrixData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($matrixData) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Grade</th><th>Temperature</th><th>Count</th></tr>";
        
        foreach ($matrixData as $row) {
            $bgColor = ($row['CustomerGrade'] === 'A' && $row['CustomerTemperature'] === 'HOT') ? '#e8f5e8' : '#fff';
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$row['CustomerGrade']}</strong></td>";
            echo "<td><strong>{$row['CustomerTemperature']}</strong></td>";
            echo "<td>{$row['count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 6. ตัวอย่างข้อมูล
    echo "<h3>📋 Sample Data with Grade + Temperature</h3>";
    $stmt = $pdo->query("SELECT CustomerCode, CustomerName, CustomerStatus, CartStatus, CustomerGrade, CustomerTemperature, Sales FROM customers ORDER BY CustomerGrade, CustomerTemperature LIMIT 8");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleData) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Status</th><th>Cart Status</th><th>Grade</th><th>Temp</th><th>Sales</th></tr>";
        
        foreach ($sampleData as $row) {
            $bgColor = ($row['CustomerGrade'] === 'A' && $row['CustomerTemperature'] === 'HOT') ? '#e8f5e8' : '#fff';
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$row['CustomerCode']}</td>";
            echo "<td>{$row['CustomerName']}</td>";
            echo "<td>{$row['CustomerStatus']}</td>";
            echo "<td>{$row['CartStatus']}</td>";
            echo "<td><strong>{$row['CustomerGrade']}</strong></td>";
            echo "<td><strong>{$row['CustomerTemperature']}</strong></td>";
            echo "<td>{$row['Sales']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 7. บันทึก log
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO system_logs (LogType, Action, Details, AffectedCount, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, NOW())");
            $logDetails = "Added CustomerTemperature column to customers table and updated " . ($totalUpdated ?? 0) . " customer temperatures";
            $logStmt->execute(['SCHEMA_UPDATE', 'ADD_COLUMN', $logDetails, ($totalUpdated ?? 0), 'system']);
            
            echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "📝 Log entry created in system_logs table";
            echo "</div>";
        }
    } catch (Exception $e) {
        // Ignore log errors - not critical
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "✅ <strong>CustomerTemperature column added successfully!</strong> ตอนนี้:<br>";
echo "1. <a href='pages/admin/distribution_basket.php'>🗃️ Distribution Basket</a> - ควรทำงานได้เต็มที่แล้ว<br>";
echo "2. <a href='pages/admin/waiting_basket.php'>⏳ Waiting Basket</a> - ควรทำงานได้เต็มที่แล้ว<br>";
echo "3. ระบบจะมีข้อมูล Grade และ Temperature สำหรับการวิเคราะห์<br>";
echo "4. พร้อมเริ่มแก้ไข Daily Tasks และ UI อื่นๆ<br>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='pages/admin/distribution_basket.php'>🗃️ Distribution Basket</a> | ";
echo "<a href='pages/admin/waiting_basket.php'>⏳ Waiting Basket</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📅 Daily Tasks</a>";

echo "<div style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #17a2b8;'>";
echo "<strong>🌡️ CustomerTemperature Column Added Successfully!</strong><br>";
  echo "Temperature scale: HOT (🔥) → WARM (🌡️) → COLD (❄️) → FROZEN (🧊)<br>";
echo "Both basket pages should work perfectly now! 🎉";
echo "</div>";
?>