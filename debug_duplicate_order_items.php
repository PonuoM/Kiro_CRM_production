<?php
/**
 * Debug Duplicate Order Items
 * ตรวจสอบสาเหตุการสร้าง order_items ซ้ำ
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

echo "<h2>🐛 Debug Duplicate Order Items</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>1️⃣ ตรวจสอบ Order Items ซ้ำ</h3>";
    
    // หา DocumentNo ที่มี order_items เกิน 2 รายการ (สำหรับ order ที่มี 2 สินค้า)
    $stmt = $pdo->query("
        SELECT 
            DocumentNo,
            COUNT(*) as item_count,
            GROUP_CONCAT(CONCAT(ProductCode, ':', ProductName, ':', Quantity, ':', UnitPrice) SEPARATOR ' | ') as items_summary
        FROM order_items 
        GROUP BY DocumentNo
        HAVING COUNT(*) > 2
        ORDER BY MAX(CreatedDate) DESC
        LIMIT 10
    ");
    
    $duplicateOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateOrders)) {
        echo "<p style='color: green;'>✅ ไม่พบ order_items ที่มีจำนวนเกินปกติ</p>";
        
        // แสดง orders ปกติ
        $stmt = $pdo->query("
            SELECT 
                DocumentNo,
                COUNT(*) as item_count,
                GROUP_CONCAT(CONCAT(ProductCode, ':', ProductName) SEPARATOR ' | ') as items_summary,
                MAX(CreatedDate) as latest_date
            FROM order_items 
            GROUP BY DocumentNo
            ORDER BY MAX(CreatedDate) DESC
            LIMIT 5
        ");
        
        $normalOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Orders ล่าสุด (ปกติ):</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>DocumentNo</th><th>Item Count</th><th>Items</th><th>Date</th></tr>";
        
        foreach ($normalOrders as $order) {
            echo "<tr>";
            echo "<td>{$order['DocumentNo']}</td>";
            echo "<td>{$order['item_count']}</td>";
            echo "<td style='font-size: 12px;'>{$order['items_summary']}</td>";
            echo "<td>{$order['latest_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ พบ order_items ซ้ำ {" . count($duplicateOrders) . "} orders</p>";
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>DocumentNo</th><th>Item Count</th><th>Items Summary</th></tr>";
        
        foreach ($duplicateOrders as $order) {
            echo "<tr>";
            echo "<td style='color: red;'><strong>{$order['DocumentNo']}</strong></td>";
            echo "<td style='color: red;'><strong>{$order['item_count']}</strong></td>";
            echo "<td style='font-size: 11px;'>{$order['items_summary']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // วิเคราะห์รายละเอียดของ order แรกที่มีปัญหา
        $firstDuplicateDoc = $duplicateOrders[0]['DocumentNo'];
        
        echo "<h4>รายละเอียด order_items ของ {$firstDuplicateDoc}:</h4>";
        $stmt = $pdo->prepare("
            SELECT id, DocumentNo, ProductCode, ProductName, UnitPrice, Quantity, LineTotal, CreatedDate, CreatedBy
            FROM order_items 
            WHERE DocumentNo = ?
            ORDER BY CreatedDate, id
        ");
        $stmt->execute([$firstDuplicateDoc]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>ProductCode</th><th>ProductName</th><th>Price</th><th>Qty</th><th>Total</th><th>CreatedDate</th><th>CreatedBy</th><th>Status</th></tr>";
        
        $groupedItems = [];
        foreach ($items as $item) {
            $key = $item['ProductCode'] . '|' . $item['ProductName'] . '|' . $item['UnitPrice'] . '|' . $item['Quantity'];
            if (!isset($groupedItems[$key])) {
                $groupedItems[$key] = [];
            }
            $groupedItems[$key][] = $item;
        }
        
        foreach ($items as $item) {
            $key = $item['ProductCode'] . '|' . $item['ProductName'] . '|' . $item['UnitPrice'] . '|' . $item['Quantity'];
            $isDuplicate = count($groupedItems[$key]) > 1;
            
            echo "<tr" . ($isDuplicate ? " style='background-color: #ffcccc;'" : "") . ">";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['ProductCode']}</td>";
            echo "<td>{$item['ProductName']}</td>";
            echo "<td>" . number_format($item['UnitPrice'], 2) . "</td>";
            echo "<td>{$item['Quantity']}</td>";
            echo "<td>" . number_format($item['LineTotal'], 2) . "</td>";
            echo "<td>{$item['CreatedDate']}</td>";
            echo "<td>{$item['CreatedBy']}</td>";
            echo "<td>" . ($isDuplicate ? "<span style='color: red;'>🔄 DUPLICATE</span>" : "✅ OK") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>2️⃣ ตรวจสอบสาเหตุการเรียก createOrderItems</h3>";
    
    // ตรวจสอบ error log
    echo "<h4>ตรวจสอบ PHP Error Log:</h4>";
    $errorLogPath = ini_get('error_log');
    if (empty($errorLogPath)) {
        $errorLogPath = '/var/log/apache2/error.log'; // Default path
    }
    
    echo "<p><strong>Error Log Path:</strong> {$errorLogPath}</p>";
    
    // หาข้อมูลในฐานข้อมูลเกี่ยวกับการสร้าง order ล่าสุด
    echo "<h4>Orders ล่าสุดและ order_items ที่เกี่ยวข้อง:</h4>";
    $stmt = $pdo->query("
        SELECT 
            o.DocumentNo,
            o.CustomerCode,
            o.SubtotalAmount,
            o.Subtotal_amount2,
            o.CreatedDate as OrderCreated,
            COUNT(oi.id) as ItemCount,
            SUM(oi.LineTotal) as ItemsTotal,
            MIN(oi.CreatedDate) as FirstItemCreated,
            MAX(oi.CreatedDate) as LastItemCreated,
            TIMESTAMPDIFF(SECOND, MIN(oi.CreatedDate), MAX(oi.CreatedDate)) as TimeSpanSeconds
        FROM orders o
        LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
        WHERE o.CreatedDate >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY o.DocumentNo
        ORDER BY o.CreatedDate DESC
        LIMIT 10
    ");
    
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>DocumentNo</th><th>Customer</th><th>Order Created</th><th>Item Count</th><th>First Item</th><th>Last Item</th><th>Time Span</th><th>Status</th></tr>";
    
    foreach ($recentOrders as $order) {
        $timeSpan = $order['TimeSpanSeconds'] ?? 0;
        $isDuplicate = $order['ItemCount'] > 2; // สมมติว่า order ปกติมี 2 items
        $hasTimeGap = $timeSpan > 1; // ถ้าห่างกันมากกว่า 1 วินาที = อาจมีการเรียกหลายครั้ง
        
        echo "<tr" . ($isDuplicate ? " style='background-color: #ffcccc;'" : "") . ">";
        echo "<td>{$order['DocumentNo']}</td>";
        echo "<td>{$order['CustomerCode']}</td>";
        echo "<td>{$order['OrderCreated']}</td>";
        echo "<td" . ($isDuplicate ? " style='color: red; font-weight: bold;'" : "") . ">{$order['ItemCount']}</td>";
        echo "<td>{$order['FirstItemCreated']}</td>";
        echo "<td>{$order['LastItemCreated']}</td>";
        echo "<td" . ($hasTimeGap ? " style='color: orange;'" : "") . ">{$timeSpan}s</td>";
        
        if ($isDuplicate && $hasTimeGap) {
            echo "<td style='color: red;'>🚨 Multiple calls</td>";
        } elseif ($isDuplicate) {
            echo "<td style='color: orange;'>⚠️ Same time duplicate</td>";
        } else {
            echo "<td style='color: green;'>✅ Normal</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3️⃣ สาเหตุที่เป็นไปได้</h3>";
    echo "<ol>";
    echo "<li><strong>Double API Call:</strong> Frontend เรียก API สร้าง order 2 ครั้ง</li>";
    echo "<li><strong>Double createOrderItems Call:</strong> Backend เรียก createOrderItems 2 ครั้งในการสร้าง order เดียว</li>";
    echo "<li><strong>Transaction Issue:</strong> Transaction ไม่ทำงานถูกต้อง</li>";
    echo "<li><strong>Browser Double Submit:</strong> User กดปุ่ม Submit หลายครั้ง</li>";
    echo "</ol>";
    
    echo "<h3>4️⃣ แนวทางแก้ไข</h3>";
    echo "<ol>";
    echo "<li><strong>เพิ่ม Duplicate Check:</strong> ตรวจสอบ order_items ก่อนสร้างใหม่</li>";
    echo "<li><strong>ปรับปรุง Frontend:</strong> ป้องกัน double submit</li>";
    echo "<li><strong>ลบข้อมูลซ้ำ:</strong> ทำความสะอาดข้อมูลที่มีอยู่</li>";
    echo "</ol>";
    
    // แสดงคำสั่ง SQL สำหรับลบข้อมูลซ้ำ
    if (!empty($duplicateOrders)) {
        echo "<h4>🧹 คำสั่ง SQL สำหรับลบข้อมูลซ้ำ:</h4>";
        echo "<div style='background-color: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
        echo "<p><strong>⚠️ ระวัง:</strong> ทำ backup ก่อนรัน SQL นี้</p>";
        echo "<code>";
        echo "-- ลบ order_items ซ้ำ (เก็บไว้แค่ record แรกของแต่ละสินค้า)<br>";
        echo "DELETE t1 FROM order_items t1<br>";
        echo "INNER JOIN order_items t2<br>";
        echo "WHERE t1.id > t2.id<br>";
        echo "&nbsp;&nbsp;AND t1.DocumentNo = t2.DocumentNo<br>";
        echo "&nbsp;&nbsp;AND t1.ProductCode = t2.ProductCode<br>";
        echo "&nbsp;&nbsp;AND t1.ProductName = t2.ProductName<br>";
        echo "&nbsp;&nbsp;AND t1.UnitPrice = t2.UnitPrice<br>";
        echo "&nbsp;&nbsp;AND t1.Quantity = t2.Quantity;<br><br>";
        
        echo "-- ตรวจสอบผลลัพธ์<br>";
        echo "SELECT DocumentNo, COUNT(*) as item_count<br>";
        echo "FROM order_items<br>";
        echo "GROUP BY DocumentNo<br>";
        echo "HAVING COUNT(*) > 2<br>";
        echo "ORDER BY MAX(CreatedDate) DESC;";
        echo "</code>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>📋 วิธีใช้งาน</h3>
<ol>
<li><strong>รัน Debug:</strong> <code>http://localhost/Kiro_CRM_production/debug_duplicate_order_items.php</code></li>
<li><strong>ตรวจสอบ Error Log:</strong> ดูใน logs/php_errors.log หรือ Apache error log</li>
<li><strong>สร้าง Order ทดสอบ:</strong> สร้าง order ใหม่และดูว่าเกิดซ้ำหรือไม่</li>
<li><strong>ลบข้อมูลซ้ำ:</strong> ใช้ SQL command ที่แสดงไว้ (ทำ backup ก่อน)</li>
</ol>