<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 ตรวจสอบ Order ล่าสุด</h2>";
    
    // ดึงข้อมูล Order ล่าสุด
    $result = $conn->query("
        SELECT * FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 1
    ");
    
    if ($result->rowCount() > 0) {
        $latestOrder = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>📋 ข้อมูล Order ล่าสุด</h3>";
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
        echo "<strong>Document No:</strong> " . $latestOrder['DocumentNo'] . "<br>";
        echo "<strong>Customer:</strong> " . $latestOrder['CustomerCode'] . "<br>";
        echo "<strong>Created:</strong> " . $latestOrder['CreatedDate'] . "<br>";
        echo "</div>";
        
        echo "<h3>💰 เปรียบเทียบข้อมูลการคำนวณ</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 14px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Field</th><th>Expected (Frontend)</th><th>Actual (Database)</th><th>Status</th>";
        echo "</tr>";
        
        // ข้อมูลที่ควรจะเป็น (จาก Frontend)
        $expectedData = [
            'Quantity' => 2,
            'SubtotalAmount' => 260.00,
            'DiscountAmount' => 30.00,
            'DiscountPercent' => 11.54,
            'Price' => 230.00
        ];
        
        foreach ($expectedData as $field => $expected) {
            $actual = (float)$latestOrder[$field];
            $status = (abs($actual - $expected) < 0.01) ? '✅ ตรง' : '❌ ผิด';
            $bgColor = (abs($actual - $expected) < 0.01) ? 'background: #d4edda;' : 'background: #f8d7da;';
            
            echo "<tr style='{$bgColor}'>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$expected}</td>";
            echo "<td>{$actual}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // แสดงข้อมูลเพิ่มเติม
        echo "<h3>📦 ข้อมูลสินค้า</h3>";
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<strong>Products:</strong> " . htmlspecialchars($latestOrder['Products']) . "<br>";
        echo "<strong>Payment Method:</strong> " . htmlspecialchars($latestOrder['PaymentMethod']) . "<br>";
        echo "<strong>Discount Remarks:</strong> " . htmlspecialchars($latestOrder['DiscountRemarks']);
        echo "</div>";
        
        // สรุปผล
        $allCorrect = true;
        $issues = [];
        
        foreach ($expectedData as $field => $expected) {
            $actual = (float)$latestOrder[$field];
            if (abs($actual - $expected) >= 0.01) {
                $allCorrect = false;
                $issues[] = "{$field}: Expected {$expected}, Got {$actual}";
            }
        }
        
        echo "<h3>🎯 สรุปผล</h3>";
        if ($allCorrect) {
            echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
            echo "<h4>🎉 SUCCESS!</h4>";
            echo "<p>ข้อมูลทั้งหมดถูกต้อง! การแก้ไข Direct Mapping ทำงานได้แล้ว</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
            echo "<h4>🚨 ยังมีปัญหา</h4>";
            echo "<ul>";
            foreach ($issues as $issue) {
                echo "<li>{$issue}</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: #dc3545;'>❌ ไม่มี Order ในระบบ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
table { font-size: 12px; }
th, td { padding: 8px; text-align: left; }
</style>