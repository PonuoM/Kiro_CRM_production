<?php
/**
 * Test Script for Subtotal_amount2 Column
 * ทดสอบการบันทึกข้อมูลในคอลัมน์ใหม่
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

echo "<h2>🧪 Test Subtotal_amount2 Column</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>1️⃣ ตรวจสอบโครงสร้างตาราง</h3>";
    
    // ตรวจสอบว่าคอลัมน์ Subtotal_amount2 มีอยู่แล้วหรือไม่
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasSubtotal2 = false;
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Default</th><th>Comment</th></tr>";
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'Subtotal_amount2') {
            $hasSubtotal2 = true;
            echo "<tr style='background-color: #d4edda;'>";
        } else {
            echo "<tr>";
        }
        
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>" . ($column['Comment'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if (!$hasSubtotal2) {
        echo "<p style='color: red;'>❌ คอลัมน์ Subtotal_amount2 ยังไม่มี กรุณารัน SQL script ก่อน:</p>";
        echo "<code>mysql -u root -p crm_system < add_subtotal_amount2_column.sql</code>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ คอลัมน์ Subtotal_amount2 มีอยู่แล้ว</p>";
    
    echo "<h3>2️⃣ ข้อมูลล่าสุดในตาราง orders</h3>";
    
    // แสดงข้อมูลล่าสุด
    $stmt = $pdo->query("
        SELECT 
            DocumentNo,
            CustomerCode,
            SubtotalAmount as 'Old_Subtotal',
            Subtotal_amount2 as 'New_Subtotal',
            DiscountAmount,
            Price as 'Total',
            CreatedDate
        FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 5
    ");
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p>ไม่มีข้อมูล orders</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        echo "<th>DocumentNo</th>";
        echo "<th>CustomerCode</th>";
        echo "<th>Old Subtotal</th>";
        echo "<th>New Subtotal</th>";
        echo "<th>Difference</th>";
        echo "<th>Status</th>";
        echo "</tr>";
        
        foreach ($orders as $order) {
            $oldSubtotal = (float)$order['Old_Subtotal'];
            $newSubtotal = (float)$order['New_Subtotal'];
            $difference = $oldSubtotal - $newSubtotal;
            
            echo "<tr>";
            echo "<td>{$order['DocumentNo']}</td>";
            echo "<td>{$order['CustomerCode']}</td>";
            echo "<td>" . number_format($oldSubtotal, 2) . "</td>";
            echo "<td>" . number_format($newSubtotal, 2) . "</td>";
            echo "<td>" . number_format($difference, 2) . "</td>";
            
            if (abs($difference) < 0.01) {
                echo "<td style='color: green;'>✅ ตรง</td>";
            } else {
                echo "<td style='color: red;'>❌ ไม่ตรง</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>3️⃣ สรุปปัญหาที่พบ</h3>";
    
    // หาข้อมูลที่มีปัญหา
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN ABS(SubtotalAmount - Subtotal_amount2) > 0.01 THEN 1 END) as problem_orders,
            AVG(SubtotalAmount - Subtotal_amount2) as avg_difference
        FROM orders 
        WHERE CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Orders ใน 7 วันล่าสุด:</strong> {$summary['total_orders']} รายการ</p>";
    echo "<p><strong>Orders ที่มีปัญหา:</strong> {$summary['problem_orders']} รายการ</p>";
    echo "<p><strong>ค่าเฉลี่ยความแตกต่าง:</strong> " . number_format($summary['avg_difference'], 2) . " บาท</p>";
    
    if ($summary['problem_orders'] > 0) {
        echo "<p style='color: orange;'>⚠️ พบปัญหาในการคำนวณ subtotal</p>";
        
        // แสดงรายการที่มีปัญหา
        echo "<h4>รายการที่มีปัญหา:</h4>";
        $stmt = $pdo->query("
            SELECT 
                DocumentNo,
                CustomerCode,
                SubtotalAmount,
                Subtotal_amount2,
                (SubtotalAmount - Subtotal_amount2) as difference,
                CreatedDate
            FROM orders 
            WHERE ABS(SubtotalAmount - Subtotal_amount2) > 0.01
            ORDER BY CreatedDate DESC
            LIMIT 10
        ");
        
        $problemOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>DocumentNo</th><th>CustomerCode</th><th>Old</th><th>New</th><th>Diff</th><th>Date</th></tr>";
        
        foreach ($problemOrders as $order) {
            echo "<tr>";
            echo "<td>{$order['DocumentNo']}</td>";
            echo "<td>{$order['CustomerCode']}</td>";
            echo "<td>" . number_format($order['SubtotalAmount'], 2) . "</td>";
            echo "<td>" . number_format($order['Subtotal_amount2'], 2) . "</td>";
            echo "<td style='color: red;'>" . number_format($order['difference'], 2) . "</td>";
            echo "<td>{$order['CreatedDate']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✅ ไม่พบปัญหา</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>📋 วิธีการทดสอบ</h3>
<ol>
<li><strong>รัน SQL Script:</strong> <code>mysql -u root -p crm_system < add_subtotal_amount2_column.sql</code></li>
<li><strong>ทดสอบสร้าง Order ใหม่:</strong> ไปที่หน้า Customer Detail และสร้าง Order</li>
<li><strong>ตรวจสอบผล:</strong> ดูว่าคอลัมน์ Subtotal_amount2 มีค่าที่ถูกต้องหรือไม่</li>
<li><strong>เปรียบเทียบ:</strong> เปรียบเทียบค่าใน SubtotalAmount และ Subtotal_amount2</li>
</ol>

<p><strong>Expected Result:</strong> Subtotal_amount2 ควรเก็บค่าที่ถูกต้องจาก Frontend (เช่น 260) ในขณะที่ SubtotalAmount อาจจะยังเป็นค่าที่ผิด (เช่น 376.92)</p>