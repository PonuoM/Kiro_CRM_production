<?php
/**
 * Auto Status Manager - ระบบจัดการสถานะลูกค้าอัตโนมัติ
 * กฎ: 30 วัน (ลูกค้าใหม่ → ตะกร้าแจก), 3 เดือน (ลูกค้าติดตาม/เก่า → ตะกร้ารอ)
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>⚙️ Auto Status Manager</h2>";
echo "<p>ระบบจัดการสถานะลูกค้าอัตโนมัติตามกฎเวลา</p>";

// รับพารามิเตอร์สำหรับการรันจริง
$isDryRun = !isset($_GET['execute']); // ถ้าไม่ใส่ ?execute=1 จะเป็น dry run
$today = date('Y-m-d');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br>";
    echo "📅 Processing date: <strong>$today</strong><br>";
    echo "🔍 Mode: " . ($isDryRun ? "<strong>DRY RUN</strong> (preview only)" : "<strong>EXECUTE</strong> (will make changes)") . "<br><br>";
    
    // ===== กฎที่ 1: ลูกค้าใหม่ 30 วัน → ตะกร้าแจก =====
    echo "<h3>📋 Rule 1: ลูกค้าใหม่ 30 วัน → ตะกร้าแจก</h3>";
    
    $stmt = $pdo->prepare("
        SELECT CustomerCode, CustomerName, CustomerStatus, CartStatus, Sales, 
               CreatedDate, CartStatusDate,
               DATEDIFF(NOW(), COALESCE(CartStatusDate, CreatedDate)) as days_since_assigned
        FROM customers 
        WHERE CustomerStatus = 'ลูกค้าใหม่' 
        AND CartStatus = 'กำลังดูแล'
        AND DATEDIFF(NOW(), COALESCE(CartStatusDate, CreatedDate)) >= 30
        AND Sales IS NOT NULL
        ORDER BY days_since_assigned DESC
    ");
    $stmt->execute();
    $rule1Customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rule1Customers) > 0) {
        echo "<p>Found <strong>" . count($rule1Customers) . "</strong> customers to move to 'ตะกร้าแจก':</p>";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 14px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Code</th><th>Name</th><th>Sales</th><th>Days Since Assigned</th><th>Cart Status</th><th>Assigned Date</th>";
        echo "</tr>";
        
        $rule1Count = 0;
        foreach ($rule1Customers as $customer) {
            $bgColor = $customer['days_since_assigned'] >= 45 ? '#ffe6e6' : '#fff3cd';
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$customer['CustomerCode']}</td>";
            echo "<td>{$customer['CustomerName']}</td>";
            echo "<td>{$customer['Sales']}</td>";
            echo "<td><strong>{$customer['days_since_assigned']} วัน</strong></td>";
            echo "<td>{$customer['CartStatus']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($customer['CartStatusDate'] ?? $customer['CreatedDate'])) . "</td>";
            echo "</tr>";
            $rule1Count++;
        }
        echo "</table>";
        
        if (!$isDryRun) {
            // ดำเนินการอัปเดต
            $updateStmt = $pdo->prepare("
                UPDATE customers 
                SET CartStatus = 'ตะกร้าแจก', 
                    CartStatusDate = NOW(),
                    Sales = NULL
                WHERE CustomerStatus = 'ลูกค้าใหม่' 
                AND CartStatus = 'กำลังดูแล'
                AND DATEDIFF(NOW(), COALESCE(CartStatusDate, CreatedDate)) >= 30
                AND Sales IS NOT NULL
            ");
            $result1 = $updateStmt->execute();
            $affected1 = $updateStmt->rowCount();
            
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Executed Rule 1:</strong> Moved <strong>$affected1</strong> customers to 'ตะกร้าแจก'";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
        echo "ℹ️ No customers found for Rule 1 (ลูกค้าใหม่ 30+ days)";
        echo "</div>";
    }
    
    // ===== กฎที่ 2: ลูกค้าติดตาม/เก่า 3 เดือน → ตะกร้ารอ =====
    echo "<h3>📋 Rule 2: ลูกค้าติดตาม/เก่า 3 เดือน → ตะกร้ารอ</h3>";
    
    $stmt = $pdo->prepare("
        SELECT c.CustomerCode, c.CustomerName, c.CustomerStatus, c.CartStatus, c.Sales,
               c.CreatedDate, c.CartStatusDate,
               COALESCE(MAX(o.DocumentDate), c.CreatedDate) as last_order_date,
               DATEDIFF(NOW(), COALESCE(MAX(o.DocumentDate), c.CreatedDate)) as days_since_last_order
        FROM customers c
        LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode
        WHERE c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
        AND c.CartStatus = 'กำลังดูแล'
        AND c.Sales IS NOT NULL
        GROUP BY c.CustomerCode
        HAVING DATEDIFF(NOW(), COALESCE(MAX(o.DocumentDate), c.CreatedDate)) >= 90
        ORDER BY days_since_last_order DESC
    ");
    $stmt->execute();
    $rule2Customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rule2Customers) > 0) {
        echo "<p>Found <strong>" . count($rule2Customers) . "</strong> customers to move to 'ตะกร้ารอ':</p>";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 14px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Code</th><th>Name</th><th>Status</th><th>Sales</th><th>Days Since Last Order</th><th>Last Order</th>";
        echo "</tr>";
        
        foreach ($rule2Customers as $customer) {
            $bgColor = $customer['days_since_last_order'] >= 180 ? '#ffe6e6' : '#fff3cd';
            echo "<tr style='background: $bgColor;'>";
            echo "<td>{$customer['CustomerCode']}</td>";
            echo "<td>{$customer['CustomerName']}</td>";
            echo "<td>{$customer['CustomerStatus']}</td>";
            echo "<td>{$customer['Sales']}</td>";
            echo "<td><strong>{$customer['days_since_last_order']} วัน</strong></td>";
            echo "<td>" . date('d/m/Y', strtotime($customer['last_order_date'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!$isDryRun) {
            // ดำเนินการอัปเดต - ใช้วิธีง่ายกว่า โดยอัปเดตตาม CustomerCode ที่ได้จาก query แรก
            $customerCodes = array_column($rule2Customers, 'CustomerCode');
            
            if (!empty($customerCodes)) {
                $placeholders = str_repeat('?,', count($customerCodes) - 1) . '?';
                $updateStmt = $pdo->prepare("
                    UPDATE customers 
                    SET CartStatus = 'ตะกร้ารอ', 
                        CartStatusDate = NOW(),
                        Sales = NULL
                    WHERE CustomerCode IN ($placeholders)
                    AND CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า')
                    AND CartStatus = 'กำลังดูแล'
                ");
                $result2 = $updateStmt->execute($customerCodes);
                $affected2 = $updateStmt->rowCount();
            } else {
                $result2 = true;
                $affected2 = 0;
            }
            
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "✅ <strong>Executed Rule 2:</strong> Moved <strong>$affected2</strong> customers to 'ตะกร้ารอ'";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #d1ecf1; padding: 10px; border-radius: 5px;'>";
        echo "ℹ️ No customers found for Rule 2 (ลูกค้าติดตาม/เก่า 3+ months)";
        echo "</div>";
    }
    
    // ===== สรุปผลการดำเนินการ =====
    echo "<h3>📊 Summary</h3>";
    
    if (!$isDryRun) {
        $totalAffected = ($affected1 ?? 0) + ($affected2 ?? 0);
        
        // บันทึก log
        if ($totalAffected > 0) {
            $logStmt = $pdo->prepare("
                INSERT INTO system_logs (LogType, Action, Details, CreatedBy, CreatedDate) 
                VALUES ('AUTO_STATUS', 'BATCH_UPDATE', ?, 'auto_system', NOW())
            ");
            $logDetails = "Auto status update: Rule1($affected1) + Rule2($affected2) = Total($totalAffected) customers updated";
            $logStmt->execute([$logDetails]);
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "✅ <strong>Execution Complete!</strong><br>";
        echo "- Rule 1 (30 days): <strong>" . ($affected1 ?? 0) . "</strong> customers moved to 'ตะกร้าแจก'<br>";
        echo "- Rule 2 (3 months): <strong>" . ($affected2 ?? 0) . "</strong> customers moved to 'ตะกร้ารอ'<br>";
        echo "- Total processed: <strong>$totalAffected</strong> customers<br>";
        echo "- Execution time: " . date('Y-m-d H:i:s') . "<br>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "🔍 <strong>DRY RUN Complete!</strong><br>";
        echo "- Rule 1 would affect: <strong>" . count($rule1Customers) . "</strong> customers<br>";
        echo "- Rule 2 would affect: <strong>" . count($rule2Customers) . "</strong> customers<br>";
        echo "- Total would be processed: <strong>" . (count($rule1Customers) + count($rule2Customers)) . "</strong> customers<br>";
        echo "<br><a href='?execute=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>⚡ EXECUTE CHANGES</a>";
        echo "</div>";
    }
    
    // ===== แสดงสถิติปัจจุบัน =====
    echo "<h3>📈 Current Statistics</h3>";
    $stmt = $pdo->prepare("
        SELECT CartStatus, COUNT(*) as count 
        FROM customers 
        GROUP BY CartStatus 
        ORDER BY 
            CASE CartStatus 
                WHEN 'กำลังดูแล' THEN 1 
                WHEN 'ตะกร้ารอ' THEN 2 
                WHEN 'ตะกร้าแจก' THEN 3 
                ELSE 4 
            END
    ");
    $stmt->execute();
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Cart Status</th><th>Count</th><th>Percentage</th></tr>";
    
    $total = array_sum(array_column($stats, 'count'));
    foreach ($stats as $stat) {
        $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
        $bgColor = $stat['CartStatus'] === 'กำลังดูแล' ? '#e8f5e8' : '#fff';
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>{$stat['CartStatus']}</strong></td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td>{$percentage}%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🔗 Navigation</h3>";
echo "<a href='auto_status_manager.php'>🔄 Dry Run Again</a> | ";
echo "<a href='fix_workflow_data.php'>🔧 Fix Workflow Data</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📋 Daily Tasks</a>";

echo "<h3>⏰ Cron Job Setup</h3>";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
echo "<strong>วิธีตั้ง Cron Job ให้รันทุกวัน:</strong><br>";
echo "<code>0 2 * * * /usr/bin/curl -s \"https://www.prima49.com/crm_system/Kiro_CRM_production/auto_status_manager.php?execute=1\" > /dev/null 2>&1</code><br>";
echo "<small>รันทุกวันเวลา 02:00 น.</small>";
echo "</div>";
?>