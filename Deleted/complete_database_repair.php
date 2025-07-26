<?php
/**
 * Complete Database Repair Script
 * This script performs a comprehensive database repair and schema verification
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 การซ่อมแซมฐานข้อมูลแบบสมบูรณ์</h2>";
    echo "<div style='background:#f8f9fa; padding:15px; margin:10px 0; border-radius:8px;'>";
    
    // Step 1: Check and create missing tables
    echo "<h3>📋 Step 1: ตรวจสอบและสร้างตารางที่จำเป็น</h3>";
    
    // Check users table
    $checkUsers = "SHOW TABLES LIKE 'users'";
    $result = $pdo->query($checkUsers);
    
    if ($result->rowCount() == 0) {
        echo "⚠️ ตาราง users ไม่พบ - กำลังสร้างใหม่...<br>";
        $createUsers = "
        CREATE TABLE users (
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
        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='User accounts and authentication'";
        
        $pdo->exec($createUsers);
        echo "✅ ตาราง users สร้างสำเร็จ<br>";
    } else {
        echo "✅ ตาราง users พบแล้ว<br>";
    }
    
    // Check customers table
    $checkCustomers = "SHOW TABLES LIKE 'customers'";
    $result = $pdo->query($checkCustomers);
    
    if ($result->rowCount() == 0) {
        echo "⚠️ ตาราง customers ไม่พบ - กำลังสร้างใหม่...<br>";
        $createCustomers = "
        CREATE TABLE customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            CustomerCode NVARCHAR(50) UNIQUE NOT NULL,
            CustomerName NVARCHAR(500) NOT NULL,
            CustomerTel NVARCHAR(200) UNIQUE NOT NULL,
            CustomerEmail NVARCHAR(200),
            CustomerAddress NVARCHAR(500),
            CustomerProvince NVARCHAR(200),
            CustomerPostalCode NVARCHAR(50),
            Agriculture NVARCHAR(200),
            CustomerStatus ENUM('ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า') DEFAULT 'ลูกค้าใหม่',
            CartStatus ENUM('ตะกร้าแจก', 'ตะกร้ารอ', 'กำลังดูแล') DEFAULT 'กำลังดูแล',
            CustomerGrade ENUM('A', 'B', 'C', 'D') DEFAULT 'D',
            CustomerTemperature ENUM('HOT', 'WARM', 'COLD') DEFAULT 'COLD',
            TotalPurchase VARCHAR(20),
            Sales NVARCHAR(50),
            AssignDate DATETIME,
            OrderDate DATETIME,
            CallStatus NVARCHAR(50),
            TalkStatus NVARCHAR(50),
            Tags NVARCHAR(500),
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            CreatedBy NVARCHAR(50),
            ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
            ModifiedBy NVARCHAR(50),
            
            INDEX idx_customer_code (CustomerCode),
            INDEX idx_customer_tel (CustomerTel),
            INDEX idx_customer_status (CustomerStatus),
            INDEX idx_sales (Sales)
        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Customer information'";
        
        $pdo->exec($createCustomers);
        echo "✅ ตาราง customers สร้างสำเร็จ<br>";
    } else {
        echo "✅ ตาราง customers พบแล้ว<br>";
    }
    
    // Step 2: Add missing columns to existing tables
    echo "<h3>🔧 Step 2: เพิ่มคอลัมน์ที่ขาดหาย</h3>";
    
    // Check if customers table has CustomerGrade and CustomerTemperature
    $stmt = $pdo->query("DESCRIBE customers");
    $customerColumns = array_column($stmt->fetchAll(), 'Field');
    
    if (!in_array('CustomerGrade', $customerColumns)) {
        echo "⚠️ เพิ่มคอลัมน์ CustomerGrade...<br>";
        $pdo->exec("ALTER TABLE customers ADD COLUMN CustomerGrade ENUM('A', 'B', 'C', 'D') DEFAULT 'D' AFTER CustomerStatus");
        echo "✅ เพิ่ม CustomerGrade สำเร็จ<br>";
    }
    
    if (!in_array('CustomerTemperature', $customerColumns)) {
        echo "⚠️ เพิ่มคอลัมน์ CustomerTemperature...<br>";
        $pdo->exec("ALTER TABLE customers ADD COLUMN CustomerTemperature ENUM('HOT', 'WARM', 'COLD') DEFAULT 'COLD' AFTER CustomerGrade");
        echo "✅ เพิ่ม CustomerTemperature สำเร็จ<br>";
    }
    
    if (!in_array('TotalPurchase', $customerColumns)) {
        echo "⚠️ เพิ่มคอลัมน์ TotalPurchase...<br>";
        $pdo->exec("ALTER TABLE customers ADD COLUMN TotalPurchase VARCHAR(20) AFTER CustomerTemperature");
        echo "✅ เพิ่ม TotalPurchase สำเร็จ<br>";
    }
    
    if (!in_array('CustomerEmail', $customerColumns)) {
        echo "⚠️ เพิ่มคอลัมน์ CustomerEmail...<br>";
        $pdo->exec("ALTER TABLE customers ADD COLUMN CustomerEmail NVARCHAR(200) AFTER CustomerTel");
        echo "✅ เพิ่ม CustomerEmail สำเร็จ<br>";
    }
    
    // Step 3: Create orders table if missing
    $checkOrders = "SHOW TABLES LIKE 'orders'";
    $result = $pdo->query($checkOrders);
    
    if ($result->rowCount() == 0) {
        echo "⚠️ ตาราง orders ไม่พบ - กำลังสร้างใหม่...<br>";
        $createOrders = "
        CREATE TABLE orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            OrderCode VARCHAR(50) NOT NULL UNIQUE,
            CustomerCode VARCHAR(50) NOT NULL,
            DocumentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            DocumentNo VARCHAR(50),
            ProductName TEXT,
            Products TEXT,
            Quantity INT DEFAULT 1,
            TotalAmount VARCHAR(20),
            Price VARCHAR(20),
            PaymentMethod VARCHAR(50) DEFAULT 'เงินสด',
            CreatedBy VARCHAR(50),
            OrderBy VARCHAR(50),
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (CustomerCode),
            INDEX idx_order_date (OrderDate),
            FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON UPDATE CASCADE
        ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Customer orders'";
        
        $pdo->exec($createOrders);
        echo "✅ ตาราง orders สร้างสำเร็จ<br>";
    } else {
        echo "✅ ตาราง orders พบแล้ว<br>";
    }
    
    // Step 4: Create other missing tables
    $requiredTables = ['call_logs', 'tasks', 'sales_histories'];
    
    foreach ($requiredTables as $table) {
        $checkTable = "SHOW TABLES LIKE '$table'";
        $result = $pdo->query($checkTable);
        
        if ($result->rowCount() == 0) {
            echo "⚠️ ตาราง $table ไม่พบ - กำลังสร้างใหม่...<br>";
            
            switch ($table) {
                case 'call_logs':
                    $createSQL = "
                    CREATE TABLE call_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        CustomerCode NVARCHAR(50) NOT NULL,
                        CallDate DATETIME NOT NULL,
                        CallTime NVARCHAR(50),
                        CallMinutes NVARCHAR(50),
                        CallStatus ENUM('ติดต่อได้', 'ติดต่อไม่ได้') NOT NULL,
                        CallReason NVARCHAR(500),
                        TalkStatus ENUM('คุยจบ', 'คุยไม่จบ'),
                        TalkReason NVARCHAR(500),
                        Remarks NVARCHAR(500),
                        CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CreatedBy NVARCHAR(50),
                        
                        FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
                        INDEX idx_customer_code (CustomerCode),
                        INDEX idx_call_date (CallDate)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Customer communication logs'";
                    break;
                    
                case 'tasks':
                    $createSQL = "
                    CREATE TABLE tasks (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        CustomerCode NVARCHAR(50) NOT NULL,
                        FollowupDate DATETIME NOT NULL,
                        Remarks NVARCHAR(500),
                        Status ENUM('รอดำเนินการ', 'เสร็จสิ้น') DEFAULT 'รอดำเนินการ',
                        CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CreatedBy NVARCHAR(50),
                        ModifiedDate DATETIME ON UPDATE CURRENT_TIMESTAMP,
                        ModifiedBy NVARCHAR(50),
                        
                        FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
                        INDEX idx_customer_code (CustomerCode),
                        INDEX idx_followup_date (FollowupDate)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Follow-up tasks'";
                    break;
                    
                case 'sales_histories':
                    $createSQL = "
                    CREATE TABLE sales_histories (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        CustomerCode NVARCHAR(50) NOT NULL,
                        SaleName NVARCHAR(50) NOT NULL,
                        StartDate DATETIME NOT NULL,
                        EndDate DATETIME,
                        AssignBy NVARCHAR(50),
                        CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CreatedBy NVARCHAR(50),
                        
                        FOREIGN KEY (CustomerCode) REFERENCES customers(CustomerCode) ON DELETE CASCADE ON UPDATE CASCADE,
                        INDEX idx_customer_code (CustomerCode)
                    ) ENGINE=InnoDB COLLATE=utf8mb4_unicode_ci COMMENT='Sales assignment history'";
                    break;
            }
            
            $pdo->exec($createSQL);
            echo "✅ ตาราง $table สร้างสำเร็จ<br>";
        } else {
            echo "✅ ตาราง $table พบแล้ว<br>";
        }
    }
    
    // Step 5: Create admin user if not exists
    echo "<h3>👤 Step 3: ตรวจสอบผู้ใช้งาน Admin</h3>";
    
    $checkAdmin = "SELECT COUNT(*) as count FROM users WHERE Username = 'admin'";
    $stmt = $pdo->query($checkAdmin);
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "⚠️ ไม่พบผู้ใช้งาน admin - กำลังสร้างใหม่...<br>";
        $createAdmin = "INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role, Status, CreatedDate, CreatedBy) 
                       VALUES ('admin', ?, 'ผู้ดูแลระบบ', 'หลัก', 'admin@company.com', '02-000-0000', 'Admin', 1, NOW(), 'system')";
        $stmt = $pdo->prepare($createAdmin);
        $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
        echo "✅ สร้างผู้ใช้งาน admin สำเร็จ (รหัสผ่าน: admin123)<br>";
    } else {
        echo "✅ พบผู้ใช้งาน admin แล้ว<br>";
    }
    
    // Step 6: Final verification
    echo "<h3>✅ Step 4: การตรวจสอบสุดท้าย</h3>";
    
    $tables = ['users', 'customers', 'orders', 'call_logs', 'tasks', 'sales_histories'];
    $allTablesOK = true;
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "✅ ตาราง $table: {$result['count']} รายการ<br>";
        } catch (Exception $e) {
            echo "❌ ตาราง $table: มีปัญหา - " . $e->getMessage() . "<br>";
            $allTablesOK = false;
        }
    }
    
    echo "<h3>🎉 สรุปผลการซ่อมแซม</h3>";
    
    if ($allTablesOK) {
        echo "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin:10px 0;'>";
        echo "<strong>✅ การซ่อมแซมฐานข้อมูลสำเร็จ!</strong><br><br>";
        echo "<strong>📋 สิ่งที่ดำเนินการ:</strong><br>";
        echo "• ตรวจสอบและสร้างตารางที่จำเป็น<br>";
        echo "• เพิ่มคอลัมน์ที่ขาดหาย<br>";
        echo "• สร้างผู้ใช้งาน admin<br>";
        echo "• ตรวจสอบความสมบูรณ์ของข้อมูล<br><br>";
        echo "<strong>🔑 ข้อมูลการเข้าสู่ระบบ:</strong><br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br><br>";
        echo "<strong>📋 ขั้นตอนถัดไป:</strong><br>";
        echo "1. รัน <a href='create_sample_data.php'>create_sample_data.php</a> เพื่อสร้างข้อมูลตัวอย่าง<br>";
        echo "2. ทดสอบการเข้าสู่ระบบ<br>";
        echo "3. ทดสอบหน้าต่างๆ ที่เกิดปัญหา<br>";
        echo "</div>";
    } else {
        echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin:10px 0;'>";
        echo "<strong>⚠️ การซ่อมแซมยังไม่สมบูรณ์</strong><br>";
        echo "กรุณาตรวจสอบข้อผิดพลาดข้างต้นและดำเนินการแก้ไข";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ เกิดข้อผิดพลาดระหว่างการซ่อมแซม:</h3>";
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
}
?>