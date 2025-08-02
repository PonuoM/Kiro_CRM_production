<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 ตรวจสอบสถานะ Order Items</h2>";
    
    // 1. ตรวจสอบจำนวนข้อมูลใน order_items
    echo "<h3>1. จำนวนข้อมูลใน order_items</h3>";
    $result = $conn->query("SELECT COUNT(*) as total FROM order_items");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total order items: <strong>{$count['total']}</strong><br>";
    
    // 2. ตรวจสอบข้อมูลตัวอย่าง
    echo "<h3>2. ข้อมูลตัวอย่าง 5 รายการแรก</h3>";
    $result = $conn->query("SELECT * FROM order_items ORDER BY created_at DESC LIMIT 5");
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>DocumentNo</th><th>ProductName</th><th>Quantity</th><th>UnitPrice</th><th>Subtotal</th><th>Created</th>";
        echo "</tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['DocumentNo']}</td>";
            echo "<td>{$row['ProductName']}</td>";
            echo "<td>{$row['Quantity']}</td>";
            echo "<td>" . number_format($row['UnitPrice'], 2) . "</td>";
            echo "<td>" . number_format($row['Subtotal'], 2) . "</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ <strong>ไม่มีข้อมูลใน order_items table</strong>";
    }
    
    // 3. ตรวจสอบ Orders ที่มีหลายรายการ
    echo "<h3>3. Orders ที่มีหลายรายการ (Multi-item orders)</h3>";
    $result = $conn->query("
        SELECT 
            o.DocumentNo,
            o.CustomerName,
            o.TotalAmount,
            COUNT(oi.id) as item_count,
            GROUP_CONCAT(oi.ProductName SEPARATOR ', ') as products
        FROM orders o
        LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo
        GROUP BY o.DocumentNo
        HAVING item_count > 1
        ORDER BY item_count DESC
        LIMIT 10
    ");
    
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>DocumentNo</th><th>Customer</th><th>Total</th><th>Items</th><th>Products</th>";
        echo "</tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['DocumentNo']}</td>";
            echo "<td>{$row['CustomerName']}</td>";
            echo "<td>" . number_format($row['TotalAmount'], 2) . "</td>";
            echo "<td><strong>{$row['item_count']}</strong></td>";
            echo "<td>{$row['products']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ <strong>ไม่พบ orders ที่มีหลายรายการ หรือข้อมูลยังไม่ได้เชื่อมโยง</strong>";
    }
    
    // 4. ตรวจสอบ Orders ทั้งหมด vs Order Items
    echo "<h3>4. สรุปข้อมูล Orders vs Order Items</h3>";
    $orders_count = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch(PDO::FETCH_ASSOC);
    $items_count = $conn->query("SELECT COUNT(*) as total FROM order_items")->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='display: flex; gap: 20px;'>";
    echo "<div>📋 Total Orders: <strong>{$orders_count['total']}</strong></div>";
    echo "<div>📦 Total Order Items: <strong>{$items_count['total']}</strong></div>";
    echo "</div>";
    
    if ($items_count['total'] == 0) {
        echo "<br><div style='background: #ffe6e6; padding: 10px; border-left: 4px solid #ff4444;'>";
        echo "<strong>⚠️ ปัญหา:</strong> order_items table ยังไม่มีข้อมูล<br>";
        echo "ต้องทำการ migrate ข้อมูลจาก orders table ไปยัง order_items ก่อน";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>