<?php
/**
 * Install System Logs Table
 * สร้างตาราง system_logs สำหรับเก็บ log การทำงานของระบบอัตโนมัติ
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🗄️ Install System Logs Table</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // อ่านไฟล์ SQL
    $sqlContent = file_get_contents('create_system_logs_table.sql');
    if (!$sqlContent) {
        throw new Exception("Cannot read SQL file");
    }
    
    // แยก SQL statements
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "<h3>📋 Executing SQL Statements</h3>";
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // ข้าม comment และ empty statement
            if (empty(trim($statement)) || preg_match('/^(--|\#)/', trim($statement))) {
                continue;
            }
            
            $result = $pdo->exec($statement);
            echo "<div style='background: #d4edda; padding: 5px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px;'>";
            echo "✅ Statement " . ($index + 1) . ": " . substr(trim($statement), 0, 100) . "...";
            echo "</div>";
            $success++;
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 5px; margin: 5px 0; border-radius: 3px; font-family: monospace; font-size: 12px;'>";
            echo "❌ Statement " . ($index + 1) . " Error: " . $e->getMessage();
            echo "<br><small>" . substr(trim($statement), 0, 200) . "...</small>";
            echo "</div>";
            $errors++;
        }
    }
    
    echo "<h3>📊 Installation Summary</h3>";
    echo "<div style='background: " . ($errors > 0 ? "#fff3cd" : "#d4edda") . "; padding: 15px; border-radius: 5px;'>";
    echo "📈 <strong>Results:</strong><br>";
    echo "- Successful statements: <strong>$success</strong><br>";
    echo "- Failed statements: <strong>$errors</strong><br>";
    echo "- Status: " . ($errors > 0 ? "⚠️ <strong>Completed with warnings</strong>" : "✅ <strong>Successfully completed</strong>") . "<br>";
    echo "</div>";
    
    // ตรวจสอบว่าตารางถูกสร้างแล้วหรือยัง
    echo "<h3>🔍 Verification</h3>";
    
    try {
        $stmt = $pdo->query("DESCRIBE system_logs");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($columns) > 0) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>system_logs table created successfully!</strong><br>";
            echo "Table has <strong>" . count($columns) . "</strong> columns:<br>";
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
            echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
            
            // ตรวจสอบ sample data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM system_logs");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>📄 Sample records in table: <strong>$count</strong></p>";
            
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ Table verification failed - system_logs table not found";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Verification error: " . $e->getMessage();
        echo "</div>";
    }
    
    // แสดงตัวอย่างการใช้งาน
    echo "<h3>💡 Usage Examples</h3>";
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
    echo "<strong>How to use system_logs:</strong><br><br>";
    
    echo "<strong>1. Insert log entry:</strong><br>";
    echo "<code style='background: #f8f9fa; padding: 5px; border-radius: 3px; display: block; margin: 5px 0;'>";
    echo "\$stmt = \$pdo->prepare(\"INSERT INTO system_logs (LogType, Action, Details, AffectedCount, CreatedBy) VALUES (?, ?, ?, ?, ?)\");<br>";
    echo "\$stmt->execute(['AUTO_STATUS', 'BATCH_UPDATE', 'Updated 5 customers', 5, 'auto_system']);";
    echo "</code>";
    
    echo "<strong>2. View recent logs:</strong><br>";
    echo "<code style='background: #f8f9fa; padding: 5px; border-radius: 3px; display: block; margin: 5px 0;'>";
    echo "SELECT * FROM recent_system_logs WHERE LogType = 'AUTO_STATUS' ORDER BY CreatedDate DESC LIMIT 10;";
    echo "</code>";
    
    echo "<strong>3. Check daily statistics:</strong><br>";
    echo "<code style='background: #f8f9fa; padding: 5px; border-radius: 3px; display: block; margin: 5px 0;'>";
    echo "SELECT DATE(CreatedDate) as date, COUNT(*) as operations, SUM(AffectedCount) as total_affected<br>";
    echo "FROM system_logs WHERE CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(CreatedDate);";
    echo "</code>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Installation Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #cff4fc; padding: 15px; border-radius: 5px;'>";
echo "After installing system_logs table, you can:<br>";
echo "1. <a href='fix_workflow_data.php'>🔧 Fix workflow data</a> - ปรับปรุงข้อมูลให้ตรง workflow<br>";
echo "2. <a href='auto_status_manager.php'>⚙️ Test auto status manager</a> - ทดสอบระบบอัตโนมัติ<br>";
echo "3. Set up cron job for daily execution<br>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='fix_workflow_data.php'>🔧 Fix Workflow</a> | ";
echo "<a href='auto_status_manager.php'>⚙️ Auto Status</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📋 Daily Tasks</a>";
?>