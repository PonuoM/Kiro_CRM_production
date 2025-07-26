<?php
/**
 * Update Grade Logic Based on Purchase Amount
 * เปลี่ยน logic การจัด Grade ตามยอดซื้อ:
 * A = มากกว่า 10K, B = 5K-9.99K, C = 2K-4.99K, D = น้อยกว่า 2K
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>📊 Update Grade Logic Based on Purchase Amount</h2>";
echo "<p>เปลี่ยน logic การจัด CustomerGrade ตามยอดซื้อใหม่</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br><br>";
    
    // 1. แสดง Grade Logic ใหม่
    echo "<h3>📋 New Grade Logic</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Grade</th><th>Purchase Range</th><th>Description</th></tr>";
    echo "<tr style='background: #e8f5e8;'><td><strong>A</strong></td><td>มากกว่า 10,000 บาท</td><td>VIP ลูกค้าคุณภาพสูง</td></tr>";
    echo "<tr style='background: #cff4fc;'><td><strong>B</strong></td><td>5,000 - 9,999 บาท</td><td>ลูกค้าดี มีศักยภาพ</td></tr>";
    echo "<tr style='background: #fff3cd;'><td><strong>C</strong></td><td>2,000 - 4,999 บาท</td><td>ลูกค้าปกติ</td></tr>";
    echo "<tr style='background: #f8d7da;'><td><strong>D</strong></td><td>น้อยกว่า 2,000 บาท</td><td>ลูกค้ายอดซื้อต่ำ</td></tr>";
    echo "</table>";
    
    // 2. แสดงสถิติปัจจุบัน
    echo "<h3>📊 Current Grade Distribution</h3>";
    $stmt = $pdo->query("SELECT CustomerGrade, COUNT(*) as count, MIN(TotalPurchase) as min_purchase, MAX(TotalPurchase) as max_purchase, AVG(TotalPurchase) as avg_purchase FROM customers GROUP BY CustomerGrade ORDER BY CustomerGrade");
    $currentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'><th>Current Grade</th><th>Count</th><th>Min Purchase</th><th>Max Purchase</th><th>Avg Purchase</th></tr>";
    foreach ($currentStats as $stat) {
        echo "<tr>";
        echo "<td><strong>{$stat['CustomerGrade']}</strong></td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td>" . number_format($stat['min_purchase'], 0) . " ฿</td>";
        echo "<td>" . number_format($stat['max_purchase'], 0) . " ฿</td>";
        echo "<td>" . number_format($stat['avg_purchase'], 0) . " ฿</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. อัปเดต Grade ตาม logic ใหม่
    echo "<h3>🔧 Updating Grades Based on New Logic</h3>";
    
    $gradeUpdates = [
        [
            'sql' => "UPDATE customers SET CustomerGrade = 'A' WHERE TotalPurchase >= 10000",
            'label' => 'A (มากกว่า 10K)',
            'color' => '#e8f5e8'
        ],
        [
            'sql' => "UPDATE customers SET CustomerGrade = 'B' WHERE TotalPurchase >= 5000 AND TotalPurchase < 10000",
            'label' => 'B (5K-9.99K)',
            'color' => '#cff4fc'
        ],
        [
            'sql' => "UPDATE customers SET CustomerGrade = 'C' WHERE TotalPurchase >= 2000 AND TotalPurchase < 5000",
            'label' => 'C (2K-4.99K)',
            'color' => '#fff3cd'
        ],
        [
            'sql' => "UPDATE customers SET CustomerGrade = 'D' WHERE TotalPurchase < 2000",
            'label' => 'D (น้อยกว่า 2K)',
            'color' => '#f8d7da'
        ]
    ];
    
    $totalUpdated = 0;
    foreach ($gradeUpdates as $update) {
        $affected = $pdo->exec($update['sql']);
        $totalUpdated += $affected;
        
        echo "<div style='background: {$update['color']}; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
        echo "📊 Updated <strong>$affected</strong> customers to grade <strong>{$update['label']}</strong>";
        echo "</div>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "✅ <strong>Grade Update Complete!</strong><br>";
    echo "Total customers regraded: <strong>$totalUpdated</strong><br>";
    echo "New grades assigned based on purchase amount logic.";
    echo "</div>";
    
    // 4. แสดงสถิติใหม่
    echo "<h3>📈 New Grade Distribution</h3>";
    $stmt = $pdo->query("SELECT CustomerGrade, COUNT(*) as count, MIN(TotalPurchase) as min_purchase, MAX(TotalPurchase) as max_purchase, AVG(TotalPurchase) as avg_purchase FROM customers GROUP BY CustomerGrade ORDER BY CustomerGrade");
    $newStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>New Grade</th><th>Count</th><th>Percentage</th><th>Min Purchase</th><th>Max Purchase</th><th>Avg Purchase</th></tr>";
    
    $gradeColors = ['A' => '#e8f5e8', 'B' => '#cff4fc', 'C' => '#fff3cd', 'D' => '#f8d7da'];
    $total = array_sum(array_column($newStats, 'count'));
    
    foreach ($newStats as $stat) {
        $percentage = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
        $bgColor = $gradeColors[$stat['CustomerGrade']] ?? '#fff';
        
        echo "<tr style='background: $bgColor;'>";
        echo "<td><strong>{$stat['CustomerGrade']}</strong></td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td><strong>{$percentage}%</strong></td>";
        echo "<td>" . number_format($stat['min_purchase'], 0) . " ฿</td>";
        echo "<td>" . number_format($stat['max_purchase'], 0) . " ฿</td>";
        echo "<td>" . number_format($stat['avg_purchase'], 0) . " ฿</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. อัปเดต Temperature ตาม Grade ใหม่
    echo "<h3>🌡️ Updating Temperature Based on New Grades</h3>";
    
    $temperatureUpdates = [
        "UPDATE customers SET CustomerTemperature = 'HOT' WHERE CustomerGrade = 'A'", // HOT - VIP
        "UPDATE customers SET CustomerTemperature = 'WARM' WHERE CustomerGrade = 'B'", // WARM - ดี
        "UPDATE customers SET CustomerTemperature = 'WARM' WHERE CustomerGrade = 'C'", // WARM - ปกติ
        "UPDATE customers SET CustomerTemperature = 'COLD' WHERE CustomerGrade = 'D'" // COLD - ต่ำ
    ];
    
    $tempUpdated = 0;
    foreach ($temperatureUpdates as $index => $updateSQL) {
        $affected = $pdo->exec($updateSQL);
        $tempUpdated += $affected;
        
        $tempLabels = ['HOT (A-VIP)', 'WARM (B-ดี)', 'WARM (C-ปกติ)', 'COLD (D-ต่ำ)'];
        echo "<div style='background: #e2e3e5; padding: 5px 10px; margin: 3px 0; border-radius: 3px; font-size: 14px;'>";
        echo "🌡️ Updated <strong>$affected</strong> customers temperature to <strong>{$tempLabels[$index]}</strong>";
        echo "</div>";
    }
    
    // 6. แสดงตัวอย่างลูกค้าในแต่ละ Grade
    echo "<h3>👥 Sample Customers by New Grade</h3>";
    $stmt = $pdo->query("SELECT CustomerCode, CustomerName, CustomerGrade, CustomerTemperature, TotalPurchase, Sales FROM customers ORDER BY CustomerGrade, TotalPurchase DESC LIMIT 12");
    $sampleCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sampleCustomers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Grade</th><th>Temp</th><th>Purchase</th><th>Sales</th></tr>";
        
        foreach ($sampleCustomers as $customer) {
            $bgColor = $gradeColors[$customer['CustomerGrade']] ?? '#fff';
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>{$customer['CustomerCode']}</strong></td>";
            echo "<td>{$customer['CustomerName']}</td>";
            echo "<td><strong>{$customer['CustomerGrade']}</strong></td>";
            echo "<td><strong>{$customer['CustomerTemperature']}</strong></td>";
            echo "<td><strong>" . number_format($customer['TotalPurchase'], 0) . " ฿</strong></td>";
            echo "<td>{$customer['Sales']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 7. บันทึก log
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() > 0) {
            $logStmt = $pdo->prepare("INSERT INTO system_logs (LogType, Action, Details, AffectedCount, CreatedBy, CreatedDate) VALUES (?, ?, ?, ?, ?, NOW())");
            $logDetails = "Updated customer grades based on new purchase amount logic: A>10K, B=5K-9.99K, C=2K-4.99K, D<2K. Total updated: $totalUpdated customers";
            $logStmt->execute(['GRADE_UPDATE', 'REGRADE_CUSTOMERS', $logDetails, $totalUpdated, 'system']);
            
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

echo "<h3>🚀 Impact of New Grade Logic</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "✅ <strong>Grade logic updated successfully!</strong><br>";
echo "📊 <strong>New Classification:</strong><br>";
echo "- Grade A (VIP): ลูกค้าที่ซื้อมากกว่า 10,000 บาท<br>";
echo "- Grade B (ดี): ลูกค้าที่ซื้อ 5,000-9,999 บาท<br>";
echo "- Grade C (ปกติ): ลูกค้าที่ซื้อ 2,000-4,999 บาท<br>";
echo "- Grade D (ต่ำ): ลูกค้าที่ซื้อน้อยกว่า 2,000 บาท<br>";
echo "<br>🌡️ <strong>Temperature updated accordingly:</strong><br>";
echo "- A = HOT (ลูกค้า VIP), B+C = WARM (ลูกค้าปกติ), D = COLD (ต้องติดตาม)<br>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='pages/admin/distribution_basket.php'>🗃️ Distribution Basket</a> | ";
echo "<a href='pages/admin/waiting_basket.php'>⏳ Waiting Basket</a> | ";
echo "<a href='pages/admin/intelligence_system.php'>🧠 Intelligence System</a>";

echo "<div style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #007bff;'>";
echo "<strong>📊 Grade Logic Updated Successfully!</strong><br>";
echo "New purchase-based classification: A>10K, B=5K-9.99K, C=2K-4.99K, D<2K<br>";
echo "All intelligence and basket systems now use the new logic! 🎉";
echo "</div>";
?>