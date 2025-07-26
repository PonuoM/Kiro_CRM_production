<?php
/**
 * Fixed Create Sample Data for CRM System
 * Creates comprehensive sample data with proper error handling
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🏢 กำลังสร้างข้อมูลตัวอย่างสำหรับระบบ CRM (Version 2.0)</h2>";
    
    // Enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Step 1: Check table structure first
    echo "<h3>🔍 ตรวจสอบโครงสร้างฐานข้อมูล</h3>";
    
    // Check users table structure
    try {
        $stmt = $pdo->query("DESCRIBE users");
        $userColumns = array_column($stmt->fetchAll(), 'Field');
        echo "<div style='background:#e7f3ff; padding:10px; margin:10px 0;'>";
        echo "<strong>✅ ตาราง users มีคอลัมน์:</strong> " . implode(', ', $userColumns) . "<br>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0;'>";
        echo "❌ ไม่สามารถตรวจสอบตาราง users: " . $e->getMessage();
        echo "</div>";
        throw new Exception("Database structure check failed");
    }
    
    // Check customers table structure
    try {
        $stmt = $pdo->query("DESCRIBE customers");
        $customerColumns = array_column($stmt->fetchAll(), 'Field');
        echo "<div style='background:#e7f3ff; padding:10px; margin:10px 0;'>";
        echo "<strong>✅ ตาราง customers มีคอลัมน์:</strong> " . implode(', ', array_slice($customerColumns, 0, 10)) . "... (รวม " . count($customerColumns) . " คอลัมน์)<br>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0;'>";
        echo "❌ ไม่สามารถตรวจสอบตาราง customers: " . $e->getMessage();
        echo "</div>";
        throw new Exception("Database structure check failed");
    }
    
    // Step 2: Clear existing sample data
    echo "<h3>🧹 ล้างข้อมูลตัวอย่างเก่า</h3>";
    
    try {
        $pdo->exec("DELETE FROM orders WHERE CustomerCode LIKE 'CUS%'");
        $pdo->exec("DELETE FROM call_logs WHERE CustomerCode LIKE 'CUS%'");
        $pdo->exec("DELETE FROM tasks WHERE CustomerCode LIKE 'CUS%'");
        $pdo->exec("DELETE FROM sales_histories WHERE CustomerCode LIKE 'CUS%'");
        $pdo->exec("DELETE FROM customers WHERE CustomerCode LIKE 'CUS%'");
        $pdo->exec("DELETE FROM users WHERE Username LIKE '%_alpha%' OR Username LIKE '%_beta%'");
        echo "✅ ล้างข้อมูลเก่าเรียบร้อย<br>";
    } catch (Exception $e) {
        echo "⚠️ การล้างข้อมูล: " . $e->getMessage() . "<br>";
    }
    
    // Step 3: Create Users (2 Supervisors + 6 Sales staff)
    echo "<h3>👥 สร้างผู้ใช้งาน...</h3>";
    
    $users = [
        // Team Alpha - Supervisor
        [
            'username' => 'supervisor_alpha',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมชาย',
            'last_name' => 'จัดการดี',
            'email' => 'supervisor.alpha@company.com',
            'phone' => '081-111-1111',
            'role' => 'Supervisor',
            'status' => 1,
            'team' => 'Team Alpha'
        ],
        // Team Alpha - Sales Staff
        [
            'username' => 'sales_alpha1',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมหญิง',
            'last_name' => 'ขายเก่ง',
            'email' => 'sales.alpha1@company.com',
            'phone' => '081-111-1112',
            'role' => 'Sale',
            'status' => 1,
            'team' => 'Team Alpha'
        ],
        [
            'username' => 'sales_alpha2',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมศักดิ์',
            'last_name' => 'พูดเก่ง',
            'email' => 'sales.alpha2@company.com',
            'phone' => '081-111-1113',
            'role' => 'Sale',
            'status' => 1,
            'team' => 'Team Alpha'
        ],
        [
            'username' => 'sales_alpha3',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมพร',
            'last_name' => 'ชนะใจ',
            'email' => 'sales.alpha3@company.com',
            'phone' => '081-111-1114',
            'role' => 'Sale',
            'status' => 1,
            'team' => 'Team Alpha'
        ],
        // Team Beta - Supervisor
        [
            'username' => 'supervisor_beta',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมสุข',
            'last_name' => 'บริหารดี',
            'email' => 'supervisor.beta@company.com',
            'phone' => '081-222-2221',
            'role' => 'Supervisor',
            'status' => 1,
            'team' => 'Team Beta'
        ],
        // Team Beta - Sales Staff
        [
            'username' => 'sales_beta1',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมปอง',
            'last_name' => 'ขายดี',
            'email' => 'sales.beta1@company.com',
            'phone' => '081-222-2222',
            'role' => 'Sale',
            'status' => 1,
            'team' => 'Team Beta'
        ],
        [
            'username' => 'sales_beta2',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมใจ',
            'last_name' => 'บริการดี',
            'email' => 'sales.beta2@company.com',
            'phone' => '081-222-2223',
            'role' => 'Sale',
            'status' => 1,
            'team' => 'Team Beta'
        ],
        [
            'username' => 'sales_beta3',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'first_name' => 'สมคิด',
            'last_name' => 'มั่นใจ',
            'email' => 'sales.beta3@company.com',
            'phone' => '081-222-2224',
            'role' => 'Sale',
            'status' => 1,
            'team' => 'Team Beta'
        ]
    ];
    
    // Insert users with proper error handling
    $userSql = "INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role, Status, CreatedDate, ModifiedDate, CreatedBy) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'system')
                ON DUPLICATE KEY UPDATE 
                FirstName = VALUES(FirstName), 
                LastName = VALUES(LastName),
                Email = VALUES(Email),
                Phone = VALUES(Phone),
                ModifiedDate = NOW()";
    
    $userStmt = $pdo->prepare($userSql);
    $userCount = 0;
    
    foreach ($users as $user) {
        try {
            $result = $userStmt->execute([
                $user['username'], 
                $user['password'], 
                $user['first_name'],
                $user['last_name'],
                $user['email'], 
                $user['phone'], 
                $user['role'], 
                $user['status']
            ]);
            
            if ($result) {
                $userCount++;
                echo "✅ สร้างผู้ใช้: {$user['first_name']} {$user['last_name']} ({$user['role']})<br>";
            }
        } catch (PDOException $e) {
            echo "❌ ไม่สามารถสร้างผู้ใช้ {$user['username']}: " . $e->getMessage() . "<br>";
            echo "<div style='background:#fff3cd; padding:10px; margin:5px 0;'>";
            echo "Debug info: SQL State: " . $e->getCode() . "<br>";
            echo "</div>";
        }
    }
    
    echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
    echo "✅ สร้างผู้ใช้งานสำเร็จ: {$userCount}/" . count($users) . " คน<br>";
    echo "</div>";
    
    // Step 4: Create Customers with different grades and temperatures
    echo "<h3>🏪 สร้างลูกค้า...</h3>";
    
    $customers = [
        // High-value customers (Grade A)
        [
            'code' => 'CUS' . date('Ymd') . '001',
            'name' => 'บริษัท เอบีซี จำกัด (มหาชน)',
            'tel' => '02-111-1111',
            'email' => 'contact@abc-corp.com',
            'address' => '123 ถนนสีลม แขวงสีลม เขตบางรัก กรุงเทพมหานคร 10500',
            'status' => 'ลูกค้าเก่า',
            'grade' => 'A',
            'temperature' => 'WARM',
            'total_purchase' => '15,000',
            'sales' => 'sales_alpha1'
        ],
        [
            'code' => 'CUS' . date('Ymd') . '002',
            'name' => 'ห้างสรรพสินค้า เดอะมอลล์',
            'tel' => '02-222-2222',
            'email' => 'purchase@themall.co.th',
            'address' => '456 ถนนรัชดาภิเษก แขวงดินแดง เขตดินแดง กรุงเทพมหานคร 10400',
            'status' => 'ลูกค้าติดตาม',
            'grade' => 'A',
            'temperature' => 'HOT',
            'total_purchase' => '22,500',
            'sales' => 'sales_alpha2'
        ],
        [
            'code' => 'CUS' . date('Ymd') . '003',
            'name' => 'โรงแรม แกรนด์ไฮแอท',
            'tel' => '02-333-3333',
            'email' => 'procurement@grandhyatt.com',
            'address' => '789 ถนนปลื้มจิต แขวงลุมพินี เขตปทุมวัน กรุงเทพมหานคร 10330',
            'status' => 'ลูกค้าเก่า',
            'grade' => 'A',
            'temperature' => 'COLD',
            'total_purchase' => '18,750',
            'sales' => 'sales_beta1'
        ],
        // Medium-value customers (Grade B)
        [
            'code' => 'CUS' . date('Ymd') . '004',
            'name' => 'ร้านอาหาร สวนผึ้ง',
            'tel' => '02-444-4444',
            'email' => 'order@suanpung.com',
            'address' => '111 ถนนสุขุมวิท แขวงคลองตัน เขตวัฒนา กรุงเทพมหานคร 10110',
            'status' => 'ลูกค้าใหม่',
            'grade' => 'B',
            'temperature' => 'HOT',
            'total_purchase' => '8,500',
            'sales' => 'sales_alpha3'
        ],
        [
            'code' => 'CUS' . date('Ymd') . '005',
            'name' => 'คาเฟ่ อเมซอน',
            'tel' => '02-555-5555',
            'email' => 'info@amazon-cafe.com',
            'address' => '222 ถนนพระราม 4 แขวงสุริยวงศ์ เขตบางรัก กรุงเทพมหานคร 10500',
            'status' => 'ลูกค้าติดตาม',
            'grade' => 'B',
            'temperature' => 'WARM',
            'total_purchase' => '6,200',
            'sales' => 'sales_beta2'
        ],
        // Lower-value customers (Grade C & D)
        [
            'code' => 'CUS' . date('Ymd') . '006',
            'name' => 'ร้านก๋วยเตี๋ยว ลุงเสถียร',
            'tel' => '081-777-7777',
            'email' => 'noodle@uncle.com',
            'address' => '44 ซอยรามคำแหง 12 แขวงหัวหมาก เขตบางกะปิ กรุงเทพมหานคร 10240',
            'status' => 'ลูกค้าใหม่',
            'grade' => 'C',
            'temperature' => 'HOT',
            'total_purchase' => '3,200',
            'sales' => 'sales_alpha1'
        ]
    ];
    
    // Dynamic customer SQL based on available columns
    $customerBasicColumns = ['CustomerCode', 'CustomerName', 'CustomerTel', 'CustomerAddress', 'CustomerStatus', 'Sales', 'CreatedDate', 'ModifiedDate'];
    $customerExtendedColumns = ['CustomerEmail', 'CustomerGrade', 'CustomerTemperature', 'TotalPurchase'];
    
    // Check which columns exist
    $availableExtendedColumns = array_intersect($customerExtendedColumns, $customerColumns);
    $allCustomerColumns = array_merge($customerBasicColumns, $availableExtendedColumns);
    
    $customerColumnsSql = implode(', ', $allCustomerColumns);
    $customerPlaceholders = implode(', ', array_fill(0, count($allCustomerColumns), '?'));
    
    $customerSql = "INSERT INTO customers ($customerColumnsSql) 
                    VALUES ($customerPlaceholders)
                    ON DUPLICATE KEY UPDATE 
                    CustomerName = VALUES(CustomerName),
                    ModifiedDate = NOW()";
    
    echo "<div style='background:#e7f3ff; padding:10px; margin:10px 0;'>";
    echo "<strong>📋 กำลังใช้คอลัมน์:</strong> " . implode(', ', $allCustomerColumns) . "<br>";
    echo "</div>";
    
    $customerStmt = $pdo->prepare($customerSql);
    $customerCount = 0;
    
    foreach ($customers as $customer) {
        try {
            $values = [
                $customer['code'],
                $customer['name'],
                $customer['tel'],
                $customer['address'],
                $customer['status'],
                $customer['sales'],
                date('Y-m-d H:i:s'), // CreatedDate
                date('Y-m-d H:i:s')  // ModifiedDate
            ];
            
            // Add extended column values if they exist
            if (in_array('CustomerEmail', $availableExtendedColumns)) {
                $values[] = $customer['email'];
            }
            if (in_array('CustomerGrade', $availableExtendedColumns)) {
                $values[] = $customer['grade'];
            }
            if (in_array('CustomerTemperature', $availableExtendedColumns)) {
                $values[] = $customer['temperature'];
            }
            if (in_array('TotalPurchase', $availableExtendedColumns)) {
                $values[] = $customer['total_purchase'];
            }
            
            $result = $customerStmt->execute($values);
            
            if ($result) {
                $customerCount++;
                echo "✅ สร้างลูกค้า: {$customer['name']} ";
                if (isset($customer['grade'])) echo "(Grade {$customer['grade']}) ";
                echo "-> {$customer['sales']}<br>";
            }
        } catch (PDOException $e) {
            echo "❌ ไม่สามารถสร้างลูกค้า {$customer['name']}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
    echo "✅ สร้างลูกค้าสำเร็จ: {$customerCount}/" . count($customers) . " ราย<br>";
    echo "</div>";
    
    // Step 5: Create sample orders (only if orders table exists)
    $checkOrderTable = "SHOW TABLES LIKE 'orders'";
    $result = $pdo->query($checkOrderTable);
    
    if ($result->rowCount() > 0) {
        echo "<h3>🛒 สร้างคำสั่งซื้อตัวอย่าง...</h3>";
        
        $orders = [
            [
                'code' => 'ORD' . date('Ymd') . '001',
                'customer_code' => 'CUS' . date('Ymd') . '001',
                'document_no' => 'INV-001',
                'product_name' => 'ระบบจัดการสินค้า Premium Package',
                'quantity' => 1,
                'total_amount' => '15,000',
                'payment_method' => 'โอนเงิน',
                'created_by' => 'sales_alpha1'
            ],
            [
                'code' => 'ORD' . date('Ymd') . '002',
                'customer_code' => 'CUS' . date('Ymd') . '002',
                'document_no' => 'INV-002',
                'product_name' => 'ระบบ POS และบริการติดตั้ง',
                'quantity' => 1,
                'total_amount' => '22,500',
                'payment_method' => 'เช็ค',
                'created_by' => 'sales_alpha2'
            ]
        ];
        
        $orderSql = "INSERT INTO orders (OrderCode, CustomerCode, DocumentNo, OrderDate, ProductName, Quantity, TotalAmount, PaymentMethod, CreatedBy, CreatedDate) 
                     VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, NOW())
                     ON DUPLICATE KEY UPDATE 
                     ProductName = VALUES(ProductName),
                     TotalAmount = VALUES(TotalAmount)";
        
        $orderStmt = $pdo->prepare($orderSql);
        $orderCount = 0;
        
        foreach ($orders as $order) {
            try {
                $result = $orderStmt->execute([
                    $order['code'], 
                    $order['customer_code'], 
                    $order['document_no'],
                    $order['product_name'], 
                    $order['quantity'], 
                    $order['total_amount'],
                    $order['payment_method'], 
                    $order['created_by']
                ]);
                
                if ($result) {
                    $orderCount++;
                    echo "✅ สร้างคำสั่งซื้อ: {$order['document_no']} - {$order['product_name']} (฿{$order['total_amount']})<br>";
                }
            } catch (PDOException $e) {
                echo "❌ ไม่สามารถสร้างคำสั่งซื้อ {$order['document_no']}: " . $e->getMessage() . "<br>";
            }
        }
        
        echo "<div style='background:#d4edda; padding:10px; margin:10px 0;'>";
        echo "✅ สร้างคำสั่งซื้อสำเร็จ: {$orderCount}/" . count($orders) . " รายการ<br>";
        echo "</div>";
    } else {
        echo "<h3>⚠️ ไม่พบตาราง orders - ข้ามการสร้างคำสั่งซื้อ</h3>";
    }
    
    // Final summary
    echo "<h3>📊 สรุปข้อมูลที่สร้าง</h3>";
    echo "<div style='background:#f0f8ff; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<strong>✅ สร้างข้อมูลสำเร็จ</strong><br><br>";
    echo "<strong>👥 ผู้ใช้งาน:</strong> {$userCount} คน<br>";
    echo "<strong>🏪 ลูกค้า:</strong> {$customerCount} ราย<br>";
    if (isset($orderCount)) echo "<strong>🛒 คำสั่งซื้อ:</strong> {$orderCount} รายการ<br>";
    echo "<br><strong>🔑 ข้อมูลการเข้าสู่ระบบ:</strong><br>";
    echo "Username / Password: 123456 (สำหรับทุก user)<br><br>";
    echo "<strong>Supervisors:</strong> supervisor_alpha, supervisor_beta<br>";
    echo "<strong>Sales Teams:</strong> sales_alpha1, sales_alpha2, sales_alpha3, sales_beta1, sales_beta2, sales_beta3<br>";
    echo "</div>";
    
    echo "<h2>🎉 สร้างข้อมูลตัวอย่างเสร็จสิ้น!</h2>";
    echo "<p><strong>ขั้นตอนถัดไป:</strong> ทดสอบหน้าต่างๆ และการทำงานของระบบ</p>";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
} catch(Exception $e) {
    echo "<h3>❌ เกิดข้อผิดพลาด:</h3>";
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
    
    echo "<h4>🔧 วิธีแก้ไข:</h4>";
    echo "<ol>";
    echo "<li>รัน <a href='complete_database_repair.php'>complete_database_repair.php</a> ก่อน</li>";
    echo "<li>ตรวจสอบการเชื่อมต่อฐานข้อมูลใน config/database.php</li>";
    echo "<li>ตรวจสอบสิทธิ์การเข้าถึงฐานข้อมูล</li>";
    echo "</ol>";
}
?>