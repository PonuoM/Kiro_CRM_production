<?php
/**
 * Add CustomerGrade Column to Customers Table
 * เพิ่ม column CustomerGrade สำหรับแก้ไข error ในหน้า Distribution/Waiting Basket
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>⚡ Add CustomerGrade Column</h2>";
echo "<p>เพิ่ม column CustomerGrade ในตาราง customers สำหรับแก้ไข error ในหน้า basket</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // 1. ตรวจสอบโครงสร้างตารางปัจจุบัน
    echo "<h3>📋 Current Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCustomerGrade = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'CustomerGrade') {
            $hasCustomerGrade = true;
            break;
        }
    }
    
    if ($hasCustomerGrade) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ CustomerGrade column already exists!";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
        echo "⚠️ CustomerGrade column not found. Will add it now.";
        echo "</div>";
        
        // 2. เพิ่ม column CustomerGrade
        echo "<h3>🔧 Adding CustomerGrade Column</h3>";
        
        $alterSQL = "ALTER TABLE customers ADD COLUMN CustomerGrade ENUM('A', 'B', 'C', 'D', 'F') DEFAULT 'C' COMMENT 'เกรดลูกค้า: A=VIP, B=ดี, C=ปกติ, D=ต่ำ, F=ไม่ควรติดต่อ' AFTER CustomerStatus";
        
        $result = $pdo->exec($alterSQL);
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Successfully added CustomerGrade column!</strong><br>";
        echo "Column added with ENUM('A', 'B', 'C', 'D', 'F') and default value 'C'.";
        echo "</div>";
        
        // 3. อัปเดตข้อมูลตามสถานะลูกค้า
        echo "<h3>📊 Updating Customer Grades</h3>";
        
        // กำหนด grade ตาม logic
        $gradeUpdates = [
            "UPDATE customers SET CustomerGrade = 'A' WHERE CustomerStatus = 'ลูกค้าเก่า' AND CartStatus = 'กำลังดูแล'", // VIP - ลูกค้าเก่าที่ยังใช้บริการ
            "UPDATE customers SET CustomerGrade = 'B' WHERE CustomerStatus = 'ลูกค้าติดตาม' AND CartStatus = 'กำลังดูแล'", // ดี - ลูกค้าติดตามที่ยังสนใจ
            "UPDATE customers SET CustomerGrade = 'C' WHERE CustomerStatus = 'ลูกค้าใหม่'", // ปกติ - ลูกค้าใหม่
            "UPDATE customers SET CustomerGrade = 'D' WHERE CartStatus = 'ตะกร้ารอ'", // ต่ำ - ลูกค้าที่รอ
            "UPDATE customers SET CustomerGrade = 'F' WHERE CartStatus = 'ตะกร้าแจก'" // ไม่ควรติดต่อ - ลูกค้าที่ถูกแจกออก
        ];
        
        $totalUpdated = 0;
        foreach ($gradeUpdates as $index => $updateSQL) {
            $affected = $pdo->exec($updateSQL);
            $totalUpdated += $affected;
            
            $gradeLabels = ['A (VIP)', 'B (ดี)', 'C (ปกติ)', 'D (ต่ำ)', 'F (ไม่ควรติดต่อ)'];
            echo "<div style='background: #cff4fc; padding: 5px 10px; margin: 3px 0; border-radius: 3px; font-size: 14px;'>";
            echo "📋 Updated <strong>$affected</strong> customers to grade <strong>{$gradeLabels[$index]}</strong>";
            echo "</div>";
        }
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "📈 <strong>Grade Assignment Complete:</strong><br>";
        echo "Total customers updated: <strong>$totalUpdated</strong><br>";
        echo "Grade assignment based on CustomerStatus and CartStatus logic.";
        echo "</div>";
    }
    
    // 4. แสดงสถิติ grade
    echo "<h3>📈 Customer Grade Statistics</h3>";
    $stmt = $pdo->query("SELECT CustomerGrade, COUNT(*) as count FROM customers GROUP BY CustomerGrade ORDER BY CustomerGrade");
    $gradeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($gradeStats) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Grade</th><th>Description</th><th>Count</th><th>Percentage</th></tr>";
        
        $gradeDescriptions = [
            'A' => 'VIP ลูกค้าเก่าที่สำคัญ',
            'B' => 'ดี ลูกค้าติดตามที่มีศักยภาพ', 
            'C' => 'ปกติ ลูกค้าใหม่ทั่วไป',
            'D' => 'ต่ำ ลูกค้าที่รอการติดตาม',
            'F' => 'ไม่ควรติดต่อ ลูกค้าที่ถูกแจกออก'
        ];
        
        $total = array_sum(array_column($gradeStats, 'count'));
        foreach ($gradeStats as $stat) {
            $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
            $bgColor = $stat['CustomerGrade'] === 'A' ? '#e8f5e8' : '#fff';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$stat['CustomerGrade']}</strong></td>";
            echo "<td>{$gradeDescriptions[$stat['CustomerGrade']]}</td>";
            echo "<td>{$stat['count']}</td>";
            echo "<td>{$percentage}%</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. ตัวอย่างข้อมูล
    echo "<h3>📋 Sample Data with CustomerGrade</h3>";
    $stmt = $pdo->query("SELECT CustomerCode, CustomerName, CustomerStatus, CartStatus, CustomerGrade, Sales FROM customers ORDER BY CustomerGrade, CustomerCode LIMIT 10");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleData) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Status</th><th>Cart Status</th><th>Grade</th><th>Sales</th></tr>";
        
        foreach ($sampleData as $row) {
            $bgColor = $row['CustomerGrade'] === 'A' ? '#e8f5e8' : '#fff';
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$row['CustomerCode']}</td>";
            echo "<td>{$row['CustomerName']}</td>";
            echo "<td>{$row['CustomerStatus']}</td>";
            echo "<td>{$row['CartStatus']}</td>";
            echo "<td><strong>{$row['CustomerGrade']}</strong></td>";
            echo "<td>{$row['Sales']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 6. บันทึก log
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO system_logs (LogType, Action, Details, AffectedCount, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, NOW())");
            $logDetails = "Added CustomerGrade column to customers table and updated " . ($totalUpdated ?? 0) . " customer grades";
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
echo "✅ <strong>CustomerGrade column added successfully!</strong> ตอนนี้:<br>";
echo "1. <a href='pages/admin/distribution_basket.php'>🗃️ Distribution Basket</a> - ควรทำงานได้แล้ว<br>";
echo "2. <a href='pages/admin/waiting_basket.php'>⏳ Waiting Basket</a> - ควรทำงานได้แล้ว<br>";
echo "3. จะเริ่มแก้ไข Daily Tasks สำหรับ sales02 ต่อไป<br>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='pages/admin/distribution_basket.php'>🗃️ Distribution Basket</a> | ";
echo "<a href='pages/admin/waiting_basket.php'>⏳ Waiting Basket</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📅 Daily Tasks</a>";

echo "<div style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<strong>✅ CustomerGrade Column Added Successfully!</strong><br>";
echo "Grade distribution: A(VIP) → B(ดี) → C(ปกติ) → D(ต่ำ) → F(ไม่ควรติดต่อ)<br>";
echo "Basket pages should work now! 🎉";
echo "</div>";
?>