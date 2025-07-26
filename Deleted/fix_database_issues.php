<?php
/**
 * Database Issues Diagnostic and Fix Tool
 * Checks and repairs common database schema issues
 */

require_once 'config/database.php';

try {
    echo "<h2>🔧 เครื่องมือตรวจสอบและแก้ไขปัญหาฐานข้อมูล</h2>";
    echo "<div style='background:#f8f9fa; padding:15px; margin:10px 0; border-radius:8px;'>";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>📋 ตรวจสอบโครงสร้างฐานข้อมูล</h3>";
    
    // 1. Check if users table exists and has correct columns
    echo "<h4>1. ตรวจสอบตาราง users</h4>";
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['id', 'Username', 'Password', 'FirstName', 'LastName', 'Email', 'Phone', 'Role', 'Status'];
        
        echo "<ul>";
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columnNames)) {
                echo "<li>✅ Column '$col' exists</li>";
            } else {
                echo "<li>❌ Column '$col' missing</li>";
            }
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "❌ Error checking users table: " . $e->getMessage() . "<br>";
        
        // Try to create users table if it doesn't exist
        echo "<h4>🔧 Creating users table...</h4>";
        try {
            $createUsersSQL = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                Username NVARCHAR(50) UNIQUE NOT NULL,
                Password NVARCHAR(255) NOT NULL,
                FirstName NVARCHAR(200) NOT NULL,
                LastName NVARCHAR(200) NOT NULL,
                Email NVARCHAR(200),
                Phone NVARCHAR(200),
                CompanyCode NVARCHAR(10),
                Position NVARCHAR(200),
                Role ENUM('Admin', 'Supervisor', 'Sale') NOT NULL,
                LastLoginDate DATETIME,
                Status INT DEFAULT 1,
                CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
                CreatedBy NVARCHAR(50),
                ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
                ModifiedBy NVARCHAR(50),
                
                INDEX idx_username (Username),
                INDEX idx_role (Role),
                INDEX idx_status (Status)
            ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($createUsersSQL);
            echo "✅ Users table created successfully<br>";
        } catch (Exception $createError) {
            echo "❌ Failed to create users table: " . $createError->getMessage() . "<br>";
        }
    }
    
    // 2. Check customers table
    echo "<h4>2. ตรวจสอบตาราง customers</h4>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
        $result = $stmt->fetch();
        echo "✅ Customers table exists with {$result['count']} records<br>";
    } catch (Exception $e) {
        echo "❌ Customers table issue: " . $e->getMessage() . "<br>";
    }
    
    // 3. Test database connection
    echo "<h4>3. ทестการเชื่อมต่อฐานข้อมูล</h4>";
    try {
        $stmt = $pdo->query("SELECT NOW() as current_time, DATABASE() as db_name");
        $result = $stmt->fetch();
        echo "✅ Database connection OK<br>";
        echo "• Database: " . $result['db_name'] . "<br>";
        echo "• Time: " . $result['current_time'] . "<br>";
    } catch (Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
    
    // 4. Check for required admin user
    echo "<h4>4. ตรวจสอบผู้ใช้งาน admin</h4>";
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE Username = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "✅ Admin user exists<br>";
        } else {
            echo "❌ Admin user not found. Creating...<br>";
            $adminSQL = "INSERT INTO users (Username, Password, FirstName, LastName, Email, Role, Status, CreatedDate, CreatedBy) 
                        VALUES ('admin', ?, 'ผู้ดูแลระบบ', 'หลัก', 'admin@company.com', 'Admin', 1, NOW(), 'system')";
            $stmt = $pdo->prepare($adminSQL);
            $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
            echo "✅ Admin user created (username: admin, password: admin123)<br>";
        }
    } catch (Exception $e) {
        echo "❌ Admin user check failed: " . $e->getMessage() . "<br>";
    }
    
    // 5. File permissions check
    echo "<h4>5. ตรวจสอบสิทธิ์ไฟล์</h4>";
    $directories = ['logs', 'uploads', 'backups'];
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Created directory: $dir<br>";
        }
        
        if (is_writable($dir)) {
            echo "✅ Directory '$dir' is writable<br>";
        } else {
            echo "❌ Directory '$dir' is not writable<br>";
        }
    }
    
    echo "<h3>🎉 การตรวจสอบเสร็จสิ้น</h3>";
    echo "<p><strong>หากปัญหายังไม่ได้รับการแก้ไข:</strong></p>";
    echo "<ol>";
    echo "<li>ตรวจสอบการตั้งค่าฐานข้อมูลในไฟล์ config/database.php</li>";
    echo "<li>ตรวจสอบว่าฐานข้อมูล MySQL กำลังทำงาน</li>";
    echo "<li>ตรวจสอบสิทธิ์ผู้ใช้ฐานข้อมูล</li>";
    echo "<li>รัน sql/production_setup.sql เพื่อสร้างตารางใหม่</li>";
    echo "</ol>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ เกิดข้อผิดพลาดขณะตรวจสอบ:</h3>";
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
}
?>