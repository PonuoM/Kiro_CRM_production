<?php
/**
 * Add CartStatusDate Column to Customers Table
 * เพิ่ม column CartStatusDate สำหรับติดตามเวลาที่เปลี่ยนสถานะ
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🔧 Add CartStatusDate Column</h2>";
echo "<p>เพิ่ม column CartStatusDate ในตาราง customers สำหรับติดตามเวลาที่เปลี่ยนสถานะ</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // 1. ตรวจสอบโครงสร้างตารางปัจจุบัน
    echo "<h3>📋 Current Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasCartStatusDate = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'CartStatusDate') {
            $hasCartStatusDate = true;
            break;
        }
    }
    
    if ($hasCartStatusDate) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ CartStatusDate column already exists!";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
        echo "⚠️ CartStatusDate column not found. Will add it now.";
        echo "</div>";
        
        // 2. เพิ่ม column CartStatusDate
        echo "<h3>🔧 Adding CartStatusDate Column</h3>";
        
        $alterSQL = "ALTER TABLE customers ADD COLUMN CartStatusDate DATETIME NULL DEFAULT NULL COMMENT 'วันที่เปลี่ยนสถานะ CartStatus' AFTER CartStatus";
        
        $result = $pdo->exec($alterSQL);
        
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Successfully added CartStatusDate column!</strong><br>";
        echo "Column added after CartStatus with DATETIME type and NULL default.";
        echo "</div>";
        
        // 3. อัปเดตข้อมูลเก่าให้มี CartStatusDate
        echo "<h3>📊 Updating Existing Data</h3>";
        
        // Set CartStatusDate = CreatedDate for existing records
        $updateSQL = "UPDATE customers SET CartStatusDate = CreatedDate WHERE CartStatusDate IS NULL";
        $updateResult = $pdo->exec($updateSQL);
        
        echo "<div style='background: #cff4fc; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "📅 <strong>Updated existing records:</strong><br>";
        echo "Set CartStatusDate = CreatedDate for <strong>$updateResult</strong> existing customers.<br>";
        echo "This provides a baseline date for the automation rules.";
        echo "</div>";
    }
    
    // 4. ตรวจสอบโครงสร้างหลังการเปลี่ยนแปลง
    echo "<h3>🔍 Updated Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE customers");
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($updatedColumns as $col) {
        $bgColor = $col['Field'] === 'CartStatusDate' ? '#e8f5e8' : '#fff';
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. ตัวอย่างข้อมูล
    echo "<h3>📋 Sample Data with CartStatusDate</h3>";
    $stmt = $pdo->query("SELECT CustomerCode, CustomerName, CartStatus, CartStatusDate, CreatedDate FROM customers LIMIT 5");
    $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleData) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Cart Status</th><th>Cart Status Date</th><th>Created Date</th></tr>";
        
        foreach ($sampleData as $row) {
            echo "<tr>";
            echo "<td>{$row['CustomerCode']}</td>";
            echo "<td>{$row['CustomerName']}</td>";
            echo "<td>{$row['CartStatus']}</td>";
            echo "<td>" . ($row['CartStatusDate'] ? date('d/m/Y H:i', strtotime($row['CartStatusDate'])) : 'NULL') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($row['CreatedDate'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 6. บันทึก log
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO system_logs (LogType, Action, Details, AffectedCount, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, NOW())");
            $logDetails = "Added CartStatusDate column to customers table and updated " . ($updateResult ?? 0) . " existing records";
            $logStmt->execute(['SCHEMA_UPDATE', 'ADD_COLUMN', $logDetails, ($updateResult ?? 0), 'system']);
            
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
echo "✅ <strong>Column added successfully!</strong> Now you can:<br>";
echo "1. <a href='fix_workflow_data.php'>🔧 Fix workflow data</a> - ปรับปรุงข้อมูลให้ตรง workflow<br>";
echo "2. <a href='auto_status_manager.php'>⚙️ Test auto status manager</a> - ทดสอบระบบอัตโนมัติ<br>";
echo "3. The automation system will now work properly with date tracking!";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='fix_workflow_data.php'>🔧 Fix Workflow</a> | ";
echo "<a href='auto_status_manager.php'>⚙️ Auto Status</a> | ";
echo "<a href='workflow_management_summary.php'>📋 System Summary</a>";
?>