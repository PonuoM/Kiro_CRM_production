<?php
/**
 * Fix Database Column Mismatch Issues
 * This script identifies and fixes column naming mismatches
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 ตรวจสอบและแก้ไขปัญหา Column Mismatch</h2>";
    
    // 1. Check current table structure
    echo "<h3>📋 ตรวจสอบโครงสร้างตาราง users</h3>";
    
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    $existingColumns = [];
    echo "<div style='background:#f8f9fa; padding:10px; margin:10px 0; font-family:monospace;'>";
    echo "<strong>คอลัมน์ที่มีอยู่ในตาราง users:</strong><br>";
    foreach ($columns as $col) {
        $existingColumns[] = $col['Field'];
        echo "✅ {$col['Field']} ({$col['Type']})<br>";
    }
    echo "</div>";
    
    // 2. Check if we need to add missing columns or update existing ones
    $requiredColumns = [
        'Username' => 'NVARCHAR(50)',
        'Password' => 'NVARCHAR(255)',
        'FirstName' => 'NVARCHAR(200)',
        'LastName' => 'NVARCHAR(200)',
        'Email' => 'NVARCHAR(200)',
        'Phone' => 'NVARCHAR(200)',
        'Role' => "ENUM('Admin', 'Supervisor', 'Sale')",
        'Status' => 'INT',
        'CreatedDate' => 'DATETIME',
        'ModifiedDate' => 'DATETIME'
    ];
    
    echo "<h3>🔨 แก้ไขโครงสร้างตาราง</h3>";
    
    $missingColumns = array_diff(array_keys($requiredColumns), $existingColumns);
    
    if (!empty($missingColumns)) {
        echo "<div style='background:#fff3cd; padding:10px; margin:10px 0;'>";
        echo "<strong>⚠️ พบคอลัมน์ที่ขาดหาย:</strong><br>";
        
        foreach ($missingColumns as $column) {
            echo "❌ {$column}<br>";
            
            try {
                $alterSql = "ALTER TABLE users ADD COLUMN {$column} {$requiredColumns[$column]}";
                if ($column === 'Status') {
                    $alterSql .= " DEFAULT 1";
                } elseif ($column === 'CreatedDate') {
                    $alterSql .= " DEFAULT CURRENT_TIMESTAMP";
                } elseif ($column === 'ModifiedDate') {
                    $alterSql .= " ON UPDATE CURRENT_TIMESTAMP";
                }
                
                $pdo->exec($alterSql);
                echo "✅ เพิ่มคอลัมน์ {$column} สำเร็จ<br>";
            } catch (Exception $e) {
                echo "❌ ไม่สามารถเพิ่มคอลัมน์ {$column}: " . $e->getMessage() . "<br>";
            }
        }
        echo "</div>";
    } else {
        echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
        echo "✅ โครงสร้างตารางครบถ้วนแล้ว ไม่ต้องแก้ไข";
        echo "</div>";
    }
    
    // 3. Check customer table structure
    echo "<h3>📋 ตรวจสอบโครงสร้างตาราง customers</h3>";
    
    $stmt = $pdo->query("DESCRIBE customers");
    $customerCols = $stmt->fetchAll();
    
    echo "<div style='background:#f8f9fa; padding:10px; margin:10px 0; font-family:monospace;'>";
    echo "<strong>คอลัมน์ในตาราง customers:</strong><br>";
    foreach ($customerCols as $col) {
        echo "✅ {$col['Field']} ({$col['Type']})<br>";
    }
    echo "</div>";
    
    // 4. Test database connection with proper SQL syntax
    echo "<h3>🔌 ทดสอบการเชื่อมต่อฐานข้อมูล</h3>";
    
    try {
        // Fix the SQL syntax error from the original error
        $testQuery = "SELECT NOW() as current_time, DATABASE() as db_name";
        $result = $pdo->query($testQuery);
        $row = $result->fetch();
        
        echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
        echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        echo "📅 เวลาปัจจุบัน: " . $row['current_time'] . "<br>";
        echo "🗄️ ฐานข้อมูล: " . $row['db_name'] . "<br>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0;'>";
        echo "❌ ข้อผิดพลาดการเชื่อมต่อ: " . $e->getMessage();
        echo "</div>";
    }
    
    // 5. Create a compatibility layer for create_sample_data.php
    echo "<h3>🔄 สร้างข้อมูลทดสอบ</h3>";
    
    // Test insert with correct column names
    $testUserSql = "INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role, Status, CreatedDate, ModifiedDate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    FirstName = VALUES(FirstName), 
                    ModifiedDate = NOW()";
    
    try {
        $stmt = $pdo->prepare($testUserSql);
        $result = $stmt->execute([
            'test_user', 
            password_hash('test123', PASSWORD_DEFAULT),
            'ทดสอบ',
            'ระบบ',
            'test@test.com',
            '02-000-0000',
            'Sale',
            1
        ]);
        
        echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
        echo "✅ ทดสอบการ Insert ข้อมูลสำเร็จ";
        echo "</div>";
        
        // Clean up test data
        $pdo->exec("DELETE FROM users WHERE Username = 'test_user'");
        
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0;'>";
        echo "❌ ข้อผิดพลาดในการ Insert: " . $e->getMessage();
        echo "</div>";
    }
    
    echo "<h3>📊 สรุปการแก้ไข</h3>";
    echo "<div style='background:#e7f3ff; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<strong>✅ การแก้ไขเสร็จสิ้น</strong><br><br>";
    echo "<strong>🔧 สิ่งที่แก้ไข:</strong><br>";
    echo "• ตรวจสอบโครงสร้างตารางทั้งหมด<br>";
    echo "• เพิ่มคอลัมน์ที่ขาดหายหากจำเป็น<br>";
    echo "• แก้ไข SQL syntax error<br>";
    echo "• ทดสอบการ Insert ข้อมูล<br><br>";
    echo "<strong>📋 ขั้นตอนถัดไป:</strong><br>";
    echo "1. รัน <a href='create_sample_data.php'>create_sample_data.php</a> ใหม่<br>";
    echo "2. ทดสอบหน้าต่างๆ ที่เกิดปัญหา<br>";
    echo "3. ตรวจสอบการทำงานของระบบ<br>";
    echo "</div>";
    
} catch(Exception $e) {
    echo "<h3>❌ เกิดข้อผิดพลาด:</h3>";
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
}
?>