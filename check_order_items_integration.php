<?php
/**
 * ตรวจสอบการบันทึกข้อมูลใน order_items และ ProductsCode
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

echo "<h2>🔍 ตรวจสอบ Order Items Integration</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>1️⃣ ตรวจสอบตาราง order_items</h3>";
    
    // ตรวจสอบว่าตาราง order_items มีอยู่หรือไม่
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_items'");
    $orderItemsExists = $stmt->rowCount() > 0;
    
    if (!$orderItemsExists) {
        echo "<p style='color: red;'>❌ ตาราง order_items ไม่มี</p>";
        echo "<p>💡 <strong>สาเหตุ:</strong> ระบบยังไม่ได้ implement การบันทึกลง order_items</p>";
        echo "<p>📋 <strong>แนะนำ:</strong> รัน SQL script เพื่อสร้างตาราง:</p>";
        echo "<code>mysql -u root -p crm_system < database_design_order_items.sql</code>";
    } else {
        echo "<p style='color: green;'>✅ ตาราง order_items มีอยู่</p>";
        
        // ตรวจสอบโครงสร้างตาราง
        $stmt = $pdo->query("DESCRIBE order_items");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>โครงสร้างตาราง order_items:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Key</th><th>Default</th><th>Comment</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>" . ($column['Comment'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // ตรวจสอบข้อมูลใน order_items
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM order_items");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p><strong>จำนวนข้อมูลใน order_items:</strong> {$count} รายการ</p>";
        
        if ($count > 0) {
            // แสดงข้อมูล 5 รายการล่าสุด
            echo "<h4>ข้อมูล 5 รายการล่าสุด:</h4>";
            $stmt = $pdo->query("
                SELECT DocumentNo, ProductCode, ProductName, UnitPrice, Quantity, LineTotal, CreatedDate
                FROM order_items 
                ORDER BY CreatedDate DESC 
                LIMIT 5
            ");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>DocumentNo</th><th>ProductCode</th><th>ProductName</th><th>UnitPrice</th><th>Qty</th><th>LineTotal</th><th>Date</th></tr>";
            
            foreach ($items as $item) {
                echo "<tr>";
                echo "<td>{$item['DocumentNo']}</td>";
                echo "<td>{$item['ProductCode']}</td>";
                echo "<td>{$item['ProductName']}</td>";
                echo "<td>" . number_format($item['UnitPrice'], 2) . "</td>";
                echo "<td>{$item['Quantity']}</td>";
                echo "<td>" . number_format($item['LineTotal'], 2) . "</td>";
                echo "<td>{$item['CreatedDate']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ ไม่มีข้อมูลใน order_items</p>";
        }
    }
    
    echo "<h3>2️⃣ ตรวจสอบ ProductsCode ในการบันทึก</h3>";
    
    // ตรวจสอบข้อมูลใน orders ล่าสุด
    $stmt = $pdo->query("
        SELECT DocumentNo, CustomerCode, Products, ProductsDetail, CreatedDate
        FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 5
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>ข้อมูล Products ใน orders ล่าสุด:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>DocumentNo</th><th>Products</th><th>ProductsDetail (JSON)</th><th>Has ProductCode</th></tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>{$order['DocumentNo']}</td>";
        echo "<td>" . htmlspecialchars($order['Products'] ?? '') . "</td>";
        
        $productsDetail = $order['ProductsDetail'];
        if ($productsDetail) {
            $products = json_decode($productsDetail, true);
            if ($products && is_array($products)) {
                echo "<td>";
                foreach ($products as $i => $product) {
                    echo ($i + 1) . ". " . htmlspecialchars($product['name'] ?? 'No name');
                    if (isset($product['code'])) {
                        echo " (Code: {$product['code']})";
                    }
                    echo "<br>";
                }
                echo "</td>";
                
                // ตรวจสอบว่ามี ProductCode หรือไม่
                $hasProductCode = false;
                foreach ($products as $product) {
                    if (!empty($product['code'])) {
                        $hasProductCode = true;
                        break;
                    }
                }
                echo "<td>" . ($hasProductCode ? "✅ มี" : "❌ ไม่มี") . "</td>";
            } else {
                echo "<td>Invalid JSON</td>";
                echo "<td>❌ ไม่มี</td>";
            }
        } else {
            echo "<td>-</td>";
            echo "<td>❌ ไม่มี</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3️⃣ ตรวจสอบความสัมพันธ์ระหว่าง orders และ order_items</h3>";
    
    if ($orderItemsExists) {
        // ตรวจสอบ orders ที่มี order_items
        $stmt = $pdo->query("
            SELECT 
                o.DocumentNo,
                o.CustomerCode,
                o.SubtotalAmount,
                o.Subtotal_amount2,
                COUNT(oi.id) as ItemCount,
                SUM(oi.LineTotal) as ItemsTotal
            FROM orders o
            LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
            WHERE o.CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY o.DocumentNo
            ORDER BY o.CreatedDate DESC
            LIMIT 10
        ");
        
        $ordersWithItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Orders ล่าสุดและ order_items:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>DocumentNo</th><th>Customer</th><th>Subtotal</th><th>Subtotal2</th><th>Items Count</th><th>Items Total</th><th>Status</th></tr>";
        
        foreach ($ordersWithItems as $order) {
            echo "<tr>";
            echo "<td>{$order['DocumentNo']}</td>";
            echo "<td>{$order['CustomerCode']}</td>";
            echo "<td>" . number_format($order['SubtotalAmount'], 2) . "</td>";
            echo "<td>" . number_format($order['Subtotal_amount2'], 2) . "</td>";
            echo "<td>{$order['ItemCount']}</td>";
            echo "<td>" . number_format($order['ItemsTotal'], 2) . "</td>";
            
            if ($order['ItemCount'] == 0) {
                echo "<td style='color: red;'>❌ ไม่มี items</td>";
            } else {
                $totalDiff = abs($order['Subtotal_amount2'] - $order['ItemsTotal']);
                if ($totalDiff < 0.01) {
                    echo "<td style='color: green;'>✅ ตรง</td>";
                } else {
                    echo "<td style='color: orange;'>⚠️ ไม่ตรง</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>4️⃣ สรุปปัญหาที่พบ</h3>";
    
    $issues = [];
    
    if (!$orderItemsExists) {
        $issues[] = "❌ ตาราง order_items ไม่มี - ไม่มีการบันทึกรายละเอียดสินค้า";
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM order_items");
        $itemsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($itemsCount == 0) {
            $issues[] = "⚠️ ตาราง order_items ว่าง - ระบบไม่ได้บันทึกข้อมูลลงใน order_items";
        }
    }
    
    // ตรวจสอบ ProductsCode ใน Frontend
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
        COUNT(CASE WHEN ProductsDetail IS NOT NULL AND ProductsDetail != '' THEN 1 END) as with_detail
        FROM orders 
        WHERE CreatedDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $productStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($productStats['with_detail'] == 0) {
        $issues[] = "❌ ProductsDetail ไม่มีข้อมูล - Frontend ไม่ส่ง ProductsCode";
    }
    
    if (empty($issues)) {
        echo "<p style='color: green;'>✅ ไม่พบปัญหาร้ายแรง</p>";
    } else {
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        
        echo "<h4>💡 แนวทางแก้ไข:</h4>";
        echo "<ol>";
        echo "<li><strong>สร้างตาราง order_items:</strong> รัน database_design_order_items.sql</li>";
        echo "<li><strong>แก้ไข API create.php:</strong> ให้บันทึกข้อมูลลง order_items ด้วย</li>";
        echo "<li><strong>แก้ไข Frontend:</strong> ให้ส่ง ProductsCode ใน products array</li>";
        echo "<li><strong>ทดสอบ:</strong> สร้าง order ใหม่และตรวจสอบผล</li>";
        echo "</ol>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>📋 สรุปสถานะ</h3>
<p><strong>ปัญหาหลัก:</strong></p>
<ul>
<li>ระบบยังไม่ได้ implement การบันทึกลง order_items</li>
<li>ProductsCode ไม่ได้ถูกส่งจาก Frontend</li>
<li>ขาดความสัมพันธ์ระหว่าง orders (header) และ order_items (detail)</li>
</ul>

<p><strong>ผลกระทบ:</strong></p>
<ul>
<li>ไม่สามารถ report รายละเอียดสินค้าแต่ละชิ้นได้</li>
<li>ไม่มี ProductsCode สำหรับอ้างอิง</li>
<li>ข้อมูลไม่ตาม Database Normalization</li>
</ul>