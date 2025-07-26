<?php
/**
 * Fix Workflow Data - ปรับปรุงข้อมูลให้ตรงกับ workflow ที่ถูกต้อง
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🔧 Fix Workflow Data</h2>";
echo "<p>ปรับปรุงข้อมูลให้ตรงกับ workflow: Admin แจกลูกค้า → CartStatus = 'กำลังดูแล'</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // 1. แสดงสถานะปัจจุบัน
    echo "<h3>📊 Current Status</h3>";
    $stmt = $pdo->prepare("SELECT CartStatus, COUNT(*) as count FROM customers GROUP BY CartStatus");
    $stmt->execute();
    $currentStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Cart Status</th><th>Count</th></tr>";
    foreach ($currentStatus as $status) {
        echo "<tr><td>{$status['CartStatus']}</td><td>{$status['count']}</td></tr>";
    }
    echo "</table>";
    
    // 2. ตรวจสอบลูกค้าที่ควรเป็น "กำลังดูแล"
    echo "<h3>🎯 Target Customers (ควรเป็น 'กำลังดูแล')</h3>";
    echo "<p>ลูกค้าที่มี Sales และมีสถานะ 'ลูกค้าใหม่' หรือ 'ลูกค้าติดตาม'</p>";
    
    $stmt = $pdo->prepare("
        SELECT CustomerCode, CustomerName, CustomerStatus, CartStatus, Sales, CreatedDate 
        FROM customers 
        WHERE Sales IN ('sales01', 'sales02', 'supervisor01') 
        AND CustomerStatus IN ('ลูกค้าใหม่', 'ลูกค้าติดตาม')
        AND CartStatus != 'กำลังดูแล'
        ORDER BY Sales, CustomerCode
    ");
    $stmt->execute();
    $targetCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($targetCustomers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Code</th><th>Name</th><th>Current Status</th><th>Current Cart</th><th>Sales</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($targetCustomers as $customer) {
            echo "<tr>";
            echo "<td>{$customer['CustomerCode']}</td>";
            echo "<td>{$customer['CustomerName']}</td>";
            echo "<td>{$customer['CustomerStatus']}</td>";
            echo "<td><strong>{$customer['CartStatus']}</strong></td>";
            echo "<td>{$customer['Sales']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($customer['CreatedDate'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p>Found <strong>" . count($targetCustomers) . "</strong> customers that should be 'กำลังดูแล'</p>";
        
        // 3. ปุ่มสำหรับแก้ไข
        echo "<h3>🚀 Fix Actions</h3>";
        echo "<a href='?fix_cart_status=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>✅ Fix Cart Status to 'กำลังดูแล'</a><br><br>";
        
    } else {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "✅ All customers with Sales assignment already have correct CartStatus = 'กำลังดูแล'";
        echo "</div>";
    }
    
    // 4. ดำเนินการแก้ไข
    if (isset($_GET['fix_cart_status']) && count($targetCustomers) > 0) {
        echo "<h3>🔧 Fixing Cart Status...</h3>";
        
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET CartStatus = 'กำลังดูแล', 
                CartStatusDate = NOW()
            WHERE Sales IN ('sales01', 'sales02', 'supervisor01')
            AND CustomerStatus IN ('ลูกค้าใหม่', 'ลูกค้าติดตาม')
            AND CartStatus != 'กำลังดูแล'
        ");
        
        $result = $stmt->execute();
        $affected = $stmt->rowCount();
        
        if ($result) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Success!</strong> Updated <strong>$affected</strong> customers to CartStatus = 'กำลังดูแล'<br>";
            echo "CartStatusDate has been set to current time for tracking purposes.";
            echo "</div>";
            
            // สร้าง log entry
            $logStmt = $pdo->prepare("
                INSERT INTO system_logs (LogType, Action, Details, CreatedBy, CreatedDate) 
                VALUES ('WORKFLOW_FIX', 'UPDATE_CART_STATUS', ?, 'system', NOW())
            ");
            $logDetails = "Updated $affected customers to CartStatus = 'กำลังดูแล' for workflow compliance";
            $logStmt->execute([$logDetails]);
            
            echo "<a href='fix_workflow_data.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Refresh to See Results</a><br><br>";
            
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ Failed to update cart status";
            echo "</div>";
        }
    }
    
    // 5. แสดงสถานะหลังการแก้ไข
    if (isset($_GET['fix_cart_status'])) {
        echo "<h3>📊 Updated Status</h3>";
        $stmt = $pdo->prepare("SELECT CartStatus, COUNT(*) as count FROM customers GROUP BY CartStatus");
        $stmt->execute();
        $updatedStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Cart Status</th><th>Count</th></tr>";
        foreach ($updatedStatus as $status) {
            echo "<tr><td>{$status['CartStatus']}</td><td>{$status['count']}</td></tr>";
        }
        echo "</table>";
    }
    
    // 6. แสดงสรุป workflow
    echo "<h3>📋 Correct Workflow Summary</h3>";
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
    echo "<strong>🔄 Workflow Steps:</strong><br>";
    echo "1. <strong>Admin แจกลูกค้า</strong> → CustomerStatus = 'ลูกค้าใหม่', CartStatus = 'กำลังดูแล'<br>";
    echo "2. <strong>Sale/Supervisor ติดตาม</strong> → อัปเดต CustomerStatus ตามผลการติดตาม<br>";
    echo "3. <strong>ระบบอัตโนมัติ</strong> → ตรวจสอบและย้าย CartStatus ตามกฎเวลา<br>";
    echo "<br><strong>⏰ Auto Rules:</strong><br>";
    echo "- ลูกค้าใหม่ 30 วันไม่อัปเดต → 'ตะกร้าแจก'<br>";
    echo "- ลูกค้าติดตาม/เก่า 3 เดือนไม่ซื้อ → 'ตะกร้ารอ'<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='create_daily_tasks.php'>📅 Create Daily Tasks</a> | ";
echo "<a href='debug_daily_tasks.php'>🔍 Debug Analysis</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📋 Daily Tasks Page</a>";
?>