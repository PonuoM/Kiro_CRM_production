<?php
/**
 * Cleanup Duplicate Order Items
 * ทำความสะอาดข้อมูล order_items ที่ซ้ำ
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

echo "<h2>🧹 Cleanup Duplicate Order Items</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // เริ่ม transaction
    $pdo->beginTransaction();
    
    echo "<h3>1️⃣ ตรวจสอบข้อมูลก่อนทำความสะอาด</h3>";
    
    // นับ order_items ทั้งหมด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM order_items");
    $totalBefore = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // หา order_items ซ้ำ
    $stmt = $pdo->query("
        SELECT 
            DocumentNo,
            ProductCode,
            ProductName,
            UnitPrice,
            Quantity,
            COUNT(*) as duplicate_count
        FROM order_items
        GROUP BY DocumentNo, ProductCode, ProductName, UnitPrice, Quantity
        HAVING COUNT(*) > 1
        ORDER BY DocumentNo DESC, ProductCode
    ");
    
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $duplicateCount = count($duplicates);
    
    echo "<p><strong>Order Items ทั้งหมด:</strong> {$totalBefore} รายการ</p>";
    echo "<p><strong>พบข้อมูลซ้ำ:</strong> {$duplicateCount} กลุ่ม</p>";
    
    if ($duplicateCount > 0) {
        echo "<h4>รายการที่ซ้ำ:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>DocumentNo</th><th>ProductCode</th><th>ProductName</th><th>Price</th><th>Qty</th><th>Duplicate Count</th></tr>";
        
        $totalDuplicateItems = 0;
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td>{$dup['DocumentNo']}</td>";
            echo "<td>{$dup['ProductCode']}</td>";
            echo "<td>{$dup['ProductName']}</td>";
            echo "<td>" . number_format($dup['UnitPrice'], 2) . "</td>";
            echo "<td>{$dup['Quantity']}</td>";
            echo "<td style='color: red;'><strong>{$dup['duplicate_count']}</strong></td>";
            echo "</tr>";
            
            $totalDuplicateItems += ($dup['duplicate_count'] - 1); // จำนวนที่จะลบ (เก็บไว้ 1 อัน)
        }
        echo "</table>";
        
        echo "<p><strong>จำนวนรายการที่จะลบ:</strong> <span style='color: red;'>{$totalDuplicateItems} รายการ</span></p>";
        
        echo "<h3>2️⃣ ทำความสะอาดข้อมูลซ้ำ</h3>";
        
        // ลบข้อมูลซ้ำ (เก็บไว้แค่ record ที่มี id น้อยที่สุด)
        $cleanupSQL = "
            DELETE t1 FROM order_items t1
            INNER JOIN order_items t2
            WHERE t1.id > t2.id
              AND t1.DocumentNo = t2.DocumentNo
              AND t1.ProductCode = t2.ProductCode
              AND t1.ProductName = t2.ProductName
              AND t1.UnitPrice = t2.UnitPrice
              AND t1.Quantity = t2.Quantity
        ";
        
        $stmt = $pdo->prepare($cleanupSQL);
        $deleteResult = $stmt->execute();
        $deletedCount = $stmt->rowCount();
        
        if ($deleteResult) {
            echo "<p style='color: green;'>✅ ลบข้อมูลซ้ำสำเร็จ: {$deletedCount} รายการ</p>";
        } else {
            echo "<p style='color: red;'>❌ เกิดข้อผิดพลาดในการลบข้อมูล</p>";
            throw new Exception("Failed to delete duplicate records");
        }
        
    } else {
        echo "<p style='color: green;'>✅ ไม่พบข้อมูลซ้ำ</p>";
    }
    
    echo "<h3>3️⃣ ตรวจสอบผลลัพธ์หลังทำความสะอาด</h3>";
    
    // นับ order_items หลังทำความสะอาด
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM order_items");
    $totalAfter = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // ตรวจสอบว่ายังมีข้อมูลซ้ำหรือไม่
    $stmt = $pdo->query("
        SELECT COUNT(*) as remaining_duplicates
        FROM (
            SELECT DocumentNo, ProductCode, ProductName, UnitPrice, Quantity
            FROM order_items
            GROUP BY DocumentNo, ProductCode, ProductName, UnitPrice, Quantity
            HAVING COUNT(*) > 1
        ) as duplicates
    ");
    $remainingDuplicates = $stmt->fetch(PDO::FETCH_ASSOC)['remaining_duplicates'];
    
    echo "<p><strong>Order Items หลังทำความสะอาด:</strong> {$totalAfter} รายการ</p>";
    echo "<p><strong>ลดลง:</strong> " . ($totalBefore - $totalAfter) . " รายการ</p>";
    echo "<p><strong>ข้อมูลซ้ำที่เหลือ:</strong> {$remainingDuplicates} กลุ่ม</p>";
    
    if ($remainingDuplicates == 0) {
        echo "<p style='color: green;'>✅ ทำความสะอาดเสร็จสิ้น - ไม่มีข้อมูลซ้ำแล้ว</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ ยังมีข้อมูลซ้ำเหลืออยู่ อาจต้องตรวจสอบเพิ่มเติม</p>";
    }
    
    echo "<h3>4️⃣ ตรวจสอบความสมบูรณ์ของข้อมูล</h3>";
    
    // ตรวจสอบ orders ที่ไม่มี order_items
    $stmt = $pdo->query("
        SELECT 
            o.DocumentNo,
            o.CustomerCode,
            o.CreatedDate,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
        WHERE o.CreatedDate >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        GROUP BY o.DocumentNo
        HAVING COUNT(oi.id) = 0
        ORDER BY o.CreatedDate DESC
        LIMIT 10
    ");
    
    $ordersWithoutItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ordersWithoutItems)) {
        echo "<p style='color: green;'>✅ Orders ล่าสุดทั้งหมดมี order_items</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ พบ Orders ที่ไม่มี order_items:</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>DocumentNo</th><th>CustomerCode</th><th>CreatedDate</th></tr>";
        
        foreach ($ordersWithoutItems as $order) {
            echo "<tr>";
            echo "<td>{$order['DocumentNo']}</td>";
            echo "<td>{$order['CustomerCode']}</td>";
            echo "<td>{$order['CreatedDate']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // แสดงสถิติล่าสุด
    echo "<h4>สถิติ Orders และ Order Items (24 ชั่วโมงล่าสุด):</h4>";
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT o.DocumentNo) as order_count,
            COUNT(oi.id) as item_count,
            AVG(items_per_order.item_count) as avg_items_per_order
        FROM orders o
        LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
        LEFT JOIN (
            SELECT DocumentNo, COUNT(*) as item_count
            FROM order_items
            GROUP BY DocumentNo
        ) items_per_order ON o.DocumentNo = items_per_order.DocumentNo
        WHERE o.CreatedDate >= DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    echo "<li><strong>จำนวน Orders:</strong> {$stats['order_count']} รายการ</li>";
    echo "<li><strong>จำนวน Order Items:</strong> {$stats['item_count']} รายการ</li>";
    echo "<li><strong>เฉลี่ย Items ต่อ Order:</strong> " . number_format($stats['avg_items_per_order'], 2) . " รายการ</li>";
    echo "</ul>";
    
    // Commit transaction
    $pdo->commit();
    echo "<p style='color: green;'><strong>✅ Transaction committed successfully</strong></p>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
        echo "<p style='color: red;'><strong>❌ Transaction rolled back</strong></p>";
    }
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>📋 สรุปการแก้ไข</h3>
<p><strong>ปัญหาที่แก้ไข:</strong></p>
<ul>
<li>🔧 <strong>Double Call Issue:</strong> ลบการเรียก createOrderItems ซ้ำใน api/orders/create.php</li>
<li>🧹 <strong>Data Cleanup:</strong> ลบ order_items ที่ซ้ำในฐานข้อมูล</li>
<li>✅ <strong>Single Source:</strong> ตอนนี้ createOrderItems เรียกแค่ที่เดียวใน Order.php</li>
</ul>

<p><strong>คาดหวัง:</strong></p>
<ul>
<li>Orders ใหม่จะมี order_items ที่ถูกต้อง ไม่ซ้ำ</li>
<li>จำนวน items ตรงกับจำนวนสินค้าที่เลือก</li>
<li>ProductsCode และ ProductsDetail ครบถ้วน</li>
</ul>

<h3>🧪 ทดสอบ:</h3>
<ol>
<li>สร้าง Order ใหม่ด้วยสินค้า 2 รายการ</li>
<li>ตรวจสอบว่า order_items มีแค่ 2 รายการ (ไม่ซ้ำ)</li>
<li>รัน debug_duplicate_order_items.php อีกครั้งเพื่อยืนยัน</li>
</ol>