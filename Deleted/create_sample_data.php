<?php
/**
 * Create Sample Data for CRM System
 * Creates 2 teams with supervisors and sales staff
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🏢 กำลังสร้างข้อมูลตัวอย่างสำหรับระบบ CRM</h2>";
    
    // Enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 1. Create Users (2 Supervisors + 6 Sales staff)
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
            'status' => 'active',
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
            'status' => 'active',
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
            'status' => 'active',
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
            'status' => 'active',
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
            'status' => 'active',
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
            'status' => 'active',
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
            'status' => 'active',
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
            'status' => 'active',
            'team' => 'Team Beta'
        ]
    ];
    
    // Insert users - Fixed column name mapping
    $userSql = "INSERT INTO users (Username, Password, FirstName, LastName, Email, Phone, Role, Status, CreatedDate, ModifiedDate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                FirstName = VALUES(FirstName), 
                LastName = VALUES(LastName),
                Email = VALUES(Email),
                Phone = VALUES(Phone),
                Role = VALUES(Role),
                Status = VALUES(Status),
                ModifiedDate = NOW()";
    
    $userStmt = $pdo->prepare($userSql);
    foreach ($users as $user) {
        try {
            $userStmt->execute([
                $user['username'], 
                $user['password'], 
                $user['first_name'],  // Maps to FirstName column
                $user['last_name'],   // Maps to LastName column
                $user['email'], 
                $user['phone'], 
                $user['role'], 
                $user['status']
            ]);
            echo "✅ สร้างผู้ใช้: {$user['first_name']} {$user['last_name']} ({$user['role']})<br>";
        } catch (PDOException $e) {
            echo "❌ ไม่สามารถสร้างผู้ใช้ {$user['username']}: " . $e->getMessage() . "<br>";
            continue;
        }
    }
    
    // 2. Create Customers with different grades and temperatures
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
        [
            'code' => 'CUS' . date('Ymd') . '006',
            'name' => 'ออฟฟิศ เซ็นเตอร์',
            'tel' => '02-666-6666',
            'email' => 'contact@office-center.co.th',
            'address' => '333 ถนนอโศก แขวงคลองตัน เขตวัฒนา กรุงเทพมหานคร 10110',
            'status' => 'ลูกค้าเก่า',
            'grade' => 'B',
            'temperature' => 'WARM',
            'total_purchase' => '7,800',
            'sales' => 'sales_beta3'
        ],
        
        // Lower-value customers (Grade C & D)
        [
            'code' => 'CUS' . date('Ymd') . '007',
            'name' => 'ร้านก๋วยเตี๋ยว ลุงเสถียร',
            'tel' => '081-777-7777',
            'email' => 'noodle@uncle.com',
            'address' => '44 ซอยรามคำแหง 12 แขวงหัวหมาก เขตบางกะปิ กรุงเทพมหานคร 10240',
            'status' => 'ลูกค้าใหม่',
            'grade' => 'C',
            'temperature' => 'HOT',
            'total_purchase' => '3,200',
            'sales' => 'sales_alpha1'
        ],
        [
            'code' => 'CUS' . date('Ymd') . '008',
            'name' => 'บิวตี้ซาลอน เจนนี่',
            'tel' => '081-888-8888',
            'email' => 'jenny@beauty.com',
            'address' => '55 ถนนพระราม 9 แขวงห้วยขวาง เขตห้วยขวาง กรุงเทพมหานคร 10310',
            'status' => 'ลูกค้าติดตาม',
            'grade' => 'C',
            'temperature' => 'WARM',
            'total_purchase' => '2,800',
            'sales' => 'sales_alpha2'
        ],
        [
            'code' => 'CUS' . date('Ymd') . '009',
            'name' => 'ร้านซ่อมรองเท้า ตุ่น',
            'tel' => '081-999-9999',
            'email' => '',
            'address' => '66 ตลาดนัดจตุจักร แขวงจตุจักร เขตจตุจักร กรุงเทพมหานคร 10900',
            'status' => 'ลูกค้าใหม่',
            'grade' => 'D',
            'temperature' => 'COLD',
            'total_purchase' => '850',
            'sales' => 'sales_beta1'
        ],
        [
            'code' => 'CUS' . date('Ymd') . '010',
            'name' => 'ร้านขายผลไม้ สดใส',
            'tel' => '081-101-1010',
            'email' => 'fruit@fresh.com',
            'address' => '77 ตลาดคลองเตย แขวงคลองเตย เขตคลองเตย กรุงเทพมหานคร 10110',
            'status' => 'ลูกค้าใหม่',
            'grade' => 'D',
            'temperature' => 'HOT',
            'total_purchase' => '1,200',
            'sales' => 'sales_beta2'
        ],
    ];
    
    // Insert customers
    $customerSql = "INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerEmail, CustomerAddress, CustomerStatus, CustomerGrade, CustomerTemperature, TotalPurchase, Sales, CreatedDate, ModifiedDate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    CustomerName = VALUES(CustomerName),
                    CustomerTel = VALUES(CustomerTel),
                    CustomerEmail = VALUES(CustomerEmail),
                    CustomerAddress = VALUES(CustomerAddress),
                    CustomerStatus = VALUES(CustomerStatus),
                    CustomerGrade = VALUES(CustomerGrade),
                    CustomerTemperature = VALUES(CustomerTemperature),
                    TotalPurchase = VALUES(TotalPurchase),
                    Sales = VALUES(Sales),
                    ModifiedDate = NOW()";
    
    $customerStmt = $pdo->prepare($customerSql);
    foreach ($customers as $customer) {
        $customerStmt->execute([
            $customer['code'], $customer['name'], $customer['tel'], 
            $customer['email'], $customer['address'], $customer['status'],
            $customer['grade'], $customer['temperature'], $customer['total_purchase'],
            $customer['sales']
        ]);
        echo "✅ สร้างลูกค้า: {$customer['name']} (Grade {$customer['grade']}, {$customer['temperature']}) -> {$customer['sales']}<br>";
    }
    
    // 3. Create sample orders
    echo "<h3>🛒 สร้างคำสั่งซื้อตัวอย่าง...</h3>";
    
    // Check if orders table exists, if not, create it
    $checkOrderTable = "SHOW TABLES LIKE 'orders'";
    $result = $pdo->query($checkOrderTable);
    
    if ($result->rowCount() == 0) {
        echo "📝 สร้างตาราง orders...<br>";
        $createOrderTable = "
        CREATE TABLE IF NOT EXISTS orders (
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
        )";
        $pdo->exec($createOrderTable);
    }
    
    $orders = [
        // Grade A customers - higher value orders
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
        ],
        [
            'code' => 'ORD' . date('Ymd') . '003',
            'customer_code' => 'CUS' . date('Ymd') . '003',
            'document_no' => 'INV-003',
            'product_name' => 'ระบบจองห้องพัก Online',
            'quantity' => 1,
            'total_amount' => '18,750',
            'payment_method' => 'โอนเงิน',
            'created_by' => 'sales_beta1'
        ],
        
        // Grade B customers - medium value orders
        [
            'code' => 'ORD' . date('Ymd') . '004',
            'customer_code' => 'CUS' . date('Ymd') . '004',
            'document_no' => 'INV-004',
            'product_name' => 'ระบบสั่งอาหาร และจัดการเมนู',
            'quantity' => 1,
            'total_amount' => '8,500',
            'payment_method' => 'เงินสด',
            'created_by' => 'sales_alpha3'
        ],
        [
            'code' => 'ORD' . date('Ymd') . '005',
            'customer_code' => 'CUS' . date('Ymd') . '005',
            'document_no' => 'INV-005',
            'product_name' => 'ระบบขายหน้าร้าน Basic',
            'quantity' => 1,
            'total_amount' => '6,200',
            'payment_method' => 'เงินสด',
            'created_by' => 'sales_beta2'
        ],
        [
            'code' => 'ORD' . date('Ymd') . '006',
            'customer_code' => 'CUS' . date('Ymd') . '006',
            'document_no' => 'INV-006',
            'product_name' => 'ซอฟท์แวร์จัดการออฟฟิศ',
            'quantity' => 1,
            'total_amount' => '7,800',
            'payment_method' => 'โอนเงิน',
            'created_by' => 'sales_beta3'
        ],
        
        // Grade C & D customers - lower value orders
        [
            'code' => 'ORD' . date('Ymd') . '007',
            'customer_code' => 'CUS' . date('Ymd') . '007',
            'document_no' => 'INV-007',
            'product_name' => 'แอปสั่งอาหาร Mobile',
            'quantity' => 1,
            'total_amount' => '3,200',
            'payment_method' => 'เงินสด',
            'created_by' => 'sales_alpha1'
        ],
        [
            'code' => 'ORD' . date('Ymd') . '008',
            'customer_code' => 'CUS' . date('Ymd') . '008',
            'document_no' => 'INV-008',
            'product_name' => 'ระบบนัดหมาย ลูกค้า',
            'quantity' => 1,
            'total_amount' => '2,800',
            'payment_method' => 'เงินสด',
            'created_by' => 'sales_alpha2'
        ]
    ];
    
    $orderSql = "INSERT INTO orders (OrderCode, CustomerCode, DocumentNo, OrderDate, ProductName, Quantity, TotalAmount, PaymentMethod, CreatedBy, CreatedDate) 
                 VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE 
                 ProductName = VALUES(ProductName),
                 Quantity = VALUES(Quantity),
                 TotalAmount = VALUES(TotalAmount),
                 PaymentMethod = VALUES(PaymentMethod),
                 CreatedBy = VALUES(CreatedBy)";
    
    $orderStmt = $pdo->prepare($orderSql);
    foreach ($orders as $order) {
        $orderStmt->execute([
            $order['code'], $order['customer_code'], $order['document_no'],
            $order['product_name'], $order['quantity'], $order['total_amount'],
            $order['payment_method'], $order['created_by']
        ]);
        echo "✅ สร้างคำสั่งซื้อ: {$order['document_no']} - {$order['product_name']} (฿{$order['total_amount']})<br>";
    }
    
    echo "<h3>📊 สรุปข้อมูลที่สร้าง</h3>";
    echo "<div style='background:#f0f8ff; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<strong>👥 ทีมงาน:</strong><br>";
    echo "• Team Alpha: supervisor_alpha, sales_alpha1, sales_alpha2, sales_alpha3<br>";
    echo "• Team Beta: supervisor_beta, sales_beta1, sales_beta2, sales_beta3<br><br>";
    
    echo "<strong>🏪 ลูกค้า (10 รายการ):</strong><br>";
    echo "• Grade A (VIP): 3 ราย - ยอดซื้อ 15,000-22,500 บาท<br>";
    echo "• Grade B (Premium): 3 ราย - ยอดซื้อ 6,200-8,500 บาท<br>";
    echo "• Grade C (Regular): 2 ราย - ยอดซื้อ 2,800-3,200 บาท<br>";
    echo "• Grade D (New): 2 ราย - ยอดซื้อ 850-1,200 บาท<br><br>";
    
    echo "<strong>🌡️ อุณหภูมิลูกค้า:</strong><br>";
    echo "• HOT: 4 ราย (ต้องติดตามด่วน)<br>";
    echo "• WARM: 4 ราย (ติดตามปกติ)<br>";
    echo "• COLD: 2 ราย (ต้องกระตุ้น)<br><br>";
    
    echo "<strong>🛒 คำสั่งซื้อ:</strong> 8 รายการ<br>";
    echo "<strong>💰 ยอดขายรวม:</strong> " . number_format(array_sum([15000, 22500, 18750, 8500, 6200, 7800, 3200, 2800]), 0) . " บาท<br>";
    echo "</div>";
    
    echo "<h3>🔑 ข้อมูลการเข้าสู่ระบบ</h3>";
    echo "<div style='background:#fff3cd; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<strong>Username / Password (ทั้งหมด):</strong> 123456<br><br>";
    echo "<strong>Supervisors:</strong><br>";
    echo "• supervisor_alpha (สมชาย จัดการดี)<br>";
    echo "• supervisor_beta (สมสุข บริหารดี)<br><br>";
    echo "<strong>Sales Team Alpha:</strong><br>";
    echo "• sales_alpha1 (สมหญิง ขายเก่ง)<br>";
    echo "• sales_alpha2 (สมศักดิ์ พูดเก่ง)<br>";
    echo "• sales_alpha3 (สมพร ชนะใจ)<br><br>";
    echo "<strong>Sales Team Beta:</strong><br>";
    echo "• sales_beta1 (สมปอง ขายดี)<br>";
    echo "• sales_beta2 (สมใจ บริการดี)<br>";
    echo "• sales_beta3 (สมคิด มั่นใจ)<br>";
    echo "</div>";
    
    echo "<h2>🎉 สร้างข้อมูลตัวอย่างเรียบร้อย!</h2>";
    echo "<p>ตอนนี้คุณสามารถเข้าใช้งานระบบ CRM พร้อมข้อมูลตัวอย่างที่สมบูรณ์แล้ว</p>";
    
} catch(Exception $e) {
    echo "<h3>❌ เกิดข้อผิดพลาด:</h3>";
    echo "<div style='background:#f8d7da; color:#721c24; padding:15px; border-radius:8px;'>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "</div>";
}
?>