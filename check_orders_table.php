<?php
/**
 * Check Orders Table Structure
 * ตรวจสอบโครงสร้างตาราง orders และแก้ไขปัญหา column name
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🔍 Check Orders Table Structure</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // 1. ตรวจสอบว่าตาราง orders มีอยู่หรือไม่
    echo "<h3>📋 Table Existence Check</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ <strong>Table 'orders' does not exist!</strong><br>";
        echo "The auto status manager requires an orders table to track customer purchase history.";
        echo "</div>";
        
        echo "<h3>🛠️ Create Orders Table</h3>";
        echo "<p>Would you like to create a basic orders table?</p>";
        echo "<a href='?create_orders=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>✅ Create Orders Table</a><br><br>";
        
        if (isset($_GET['create_orders'])) {
            // สร้างตาราง orders
            $createSQL = "
                CREATE TABLE `orders` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `OrderID` varchar(50) NOT NULL COMMENT 'รหัสคำสั่งซื้อ',
                  `CustomerCode` varchar(20) NOT NULL COMMENT 'รหัสลูกค้า',
                  `OrderDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สั่งซื้อ',
                  `TotalAmount` decimal(10,2) DEFAULT 0.00 COMMENT 'จำนวนเงินรวม',
                  `OrderStatus` varchar(50) DEFAULT 'รอดำเนินการ' COMMENT 'สถานะคำสั่งซื้อ',
                  `CreatedBy` varchar(50) DEFAULT NULL COMMENT 'ผู้สร้างรายการ',
                  `CreatedDate` datetime DEFAULT CURRENT_TIMESTAMP,
                  `UpdatedDate` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_order_id` (`OrderID`),
                  KEY `idx_customer` (`CustomerCode`),
                  KEY `idx_order_date` (`OrderDate`),
                  KEY `idx_status` (`OrderStatus`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='ตารางคำสั่งซื้อ'
            ";
            
            $result = $pdo->exec($createSQL);
            
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Orders table created successfully!</strong>";
            echo "</div>";
            
            // เพิ่มข้อมูลตัวอย่าง
            $sampleOrders = [
                ['ORD001', 'TEST001', '2025-06-15 10:30:00', 15000.00, 'เสร็จสิ้น', 'sales01'],
                ['ORD002', 'TEST003', '2025-05-20 14:15:00', 25000.00, 'เสร็จสิ้น', 'sales01'],
                ['ORD003', 'TEST011', '2025-04-10 09:45:00', 8500.00, 'เสร็จสิ้น', 'sales01'],
                ['ORD004', 'TEST021', '2025-06-28 16:20:00', 12000.00, 'เสร็จสิ้น', 'sales02'],
                ['ORD005', 'TEST022', '2025-05-15 11:10:00', 18000.00, 'เสร็จสิ้น', 'sales02']
            ];
            
            $insertStmt = $pdo->prepare("INSERT INTO orders (OrderID, CustomerCode, OrderDate, TotalAmount, OrderStatus, CreatedBy) VALUES (?, ?, ?, ?, ?, ?)");
            
            $inserted = 0;
            foreach ($sampleOrders as $order) {
                try {
                    $insertStmt->execute($order);
                    $inserted++;
                } catch (Exception $e) {
                    // Skip duplicates
                }
            }
            
            echo "<div style='background: #cff4fc; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "📦 <strong>Added $inserted sample orders</strong> for testing automation rules";
            echo "</div>";
            
            echo "<a href='check_orders_table.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Refresh Page</a><br><br>";
        }
        
    } else {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ Table 'orders' exists";
        echo "</div>";
        
        // 2. ตรวจสอบโครงสร้างตาราง
        echo "<h3>🏗️ Table Structure</h3>";
        $stmt = $pdo->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $hasOrderDate = false;
        foreach ($columns as $col) {
            $bgColor = in_array($col['Field'], ['OrderDate', 'CreatedDate']) ? '#e8f5e8' : '#fff';
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
            
            if ($col['Field'] === 'OrderDate') {
                $hasOrderDate = true;
            }
        }
        echo "</table>";
        
        // 3. ตรวจสอบ column ที่จำเป็น
        echo "<h3>🔍 Required Columns Check</h3>";
        
        if ($hasOrderDate) {
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
            echo "✅ <strong>OrderDate column exists!</strong> Auto status manager should work now.";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
            echo "⚠️ <strong>OrderDate column not found.</strong> Checking for alternative date columns...";
            echo "</div>";
            
            // ค้นหา column วันที่อื่นๆ
            $dateColumns = [];
            foreach ($columns as $col) {
                if (strpos(strtolower($col['Type']), 'date') !== false || strpos(strtolower($col['Type']), 'time') !== false) {
                    $dateColumns[] = $col['Field'];
                }
            }
            
            if (count($dateColumns) > 0) {
                echo "<p><strong>Found date columns:</strong> " . implode(', ', $dateColumns) . "</p>";
                
                // แนะนำการแก้ไข
                if (in_array('CreatedDate', $dateColumns)) {
                    echo "<div style='background: #cff4fc; padding: 10px; border-radius: 5px;'>";
                    echo "💡 <strong>Suggestion:</strong> Use 'CreatedDate' as OrderDate for automation rules<br>";
                    echo "<a href='?fix_auto_manager=1' style='background: #0d6efd; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px;'>Fix Auto Manager</a>";
                    echo "</div>";
                }
            }
            
            if (isset($_GET['fix_auto_manager'])) {
                echo "<h4>🔧 Fixing Auto Status Manager</h4>";
                echo "<p>Will modify auto_status_manager.php to use 'CreatedDate' instead of 'OrderDate'</p>";
                echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
                echo "✅ <strong>Recommended:</strong> Modify auto_status_manager.php manually<br>";
                echo "Change: <code>o.OrderDate</code> → <code>o.CreatedDate</code><br>";
                echo "Or create an OrderDate column with: <code>ALTER TABLE orders ADD COLUMN OrderDate DATETIME DEFAULT CreatedDate</code>";
                echo "</div>";
            }
        }
        
        // 4. แสดงข้อมูลตัวอย่าง
        echo "<h3>📊 Sample Data</h3>";
        $stmt = $pdo->query("SELECT * FROM orders LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($sampleData) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
            $headers = array_keys($sampleData[0]);
            echo "<tr style='background: #f0f0f0;'>";
            foreach ($headers as $header) {
                echo "<th>$header</th>";
            }
            echo "</tr>";
            
            foreach ($sampleData as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    $bgColor = in_array($key, ['OrderDate', 'CreatedDate']) ? '#e8f5e8' : '#fff';
                    echo "<td style='background: $bgColor;'>$value</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "<p>📈 Total orders in database: <strong>$total</strong></p>";
            
        } else {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
            echo "⚠️ No sample data found in orders table";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "After resolving orders table issues:<br>";
echo "1. <a href='auto_status_manager.php'>⚙️ Test Auto Status Manager</a> - ควรทำงานได้แล้ว<br>";
echo "2. <a href='pages/daily_tasks_demo.php'>📅 Check Daily Tasks</a> - ดูงานที่เพิ่มขึ้น<br>";
echo "3. Set up cron job for daily automation<br>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='auto_status_manager.php'>⚙️ Auto Status</a> | ";
echo "<a href='workflow_management_summary.php'>📋 System Summary</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📅 Daily Tasks</a>";
?>