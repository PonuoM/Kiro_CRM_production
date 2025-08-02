<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔧 แก้ไข Order Items และ Migrate ข้อมูล</h2>";
    
    // 1. เพิ่ม created_at column ถ้ายังไม่มี
    echo "<h3>1. เพิ่ม created_at column</h3>";
    try {
        $conn->exec("ALTER TABLE order_items ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✅ เพิ่ม created_at column สำเร็จ<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ️ created_at column มีอยู่แล้ว<br>";
        } else {
            echo "❌ Error adding created_at: " . $e->getMessage() . "<br>";
        }
    }
    
    // 2. ตรวจสอบ structure ของ order_items
    echo "<h3>2. ตรวจสอบ order_items structure</h3>";
    $result = $conn->query("DESCRIBE order_items");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 3. ตรวจสอบข้อมูลใน orders ที่ต้อง migrate
    echo "<h3>3. ตรวจสอบข้อมูลใน orders</h3>";
    $result = $conn->query("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN ProductName IS NOT NULL AND ProductName != '' THEN 1 END) as orders_with_products,
            COUNT(CASE WHEN LOCATE(',', ProductName) > 0 THEN 1 END) as multi_product_orders
        FROM orders
    ");
    $stats = $result->fetch(PDO::FETCH_ASSOC);
    
    echo "<div>";
    echo "📊 Total orders: <strong>{$stats['total_orders']}</strong><br>";
    echo "📦 Orders with products: <strong>{$stats['orders_with_products']}</strong><br>";
    echo "🔢 Multi-product orders: <strong>{$stats['multi_product_orders']}</strong><br>";
    echo "</div><br>";
    
    // 4. แสดงตัวอย่างข้อมูลที่จะ migrate
    echo "<h3>4. ตัวอย่างข้อมูลที่จะ migrate (5 รายการแรก)</h3>";
    $result = $conn->query("
        SELECT DocumentNo, CustomerName, ProductName, TotalAmount, Quantity 
        FROM orders 
        WHERE ProductName IS NOT NULL AND ProductName != '' 
        LIMIT 5
    ");
    
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>DocumentNo</th><th>Customer</th><th>ProductName</th><th>Total</th><th>Qty</th>";
        echo "</tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['DocumentNo']}</td>";
            echo "<td>{$row['CustomerName']}</td>";
            echo "<td>{$row['ProductName']}</td>";
            echo "<td>" . number_format($row['TotalAmount'], 2) . "</td>";
            echo "<td>{$row['Quantity']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. Migration Script
    echo "<h3>5. 🚀 Migration Script</h3>";
    echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
    echo "<p><strong>เตรียมพร้อม Migration แล้ว!</strong></p>";
    echo "<p>จะทำการแยกข้อมูลจาก orders ไปยัง order_items โดย:</p>";
    echo "<ul>";
    echo "<li>✅ แยก ProductName ที่มี comma (,) เป็นหลายรายการ</li>";
    echo "<li>✅ คำนวณ UnitPrice = TotalAmount / Quantity</li>";
    echo "<li>✅ สร้าง Subtotal สำหรับแต่ละรายการ</li>";
    echo "<li>✅ เก็บ DocumentNo เพื่อเชื่อมโยงกับ orders</li>";
    echo "</ul>";
    echo "<p><strong>คลิกปุ่มด้านล่างเพื่อเริ่ม Migration:</strong></p>";
    echo "</div>";
    
    // Migration Button
    echo "<form method='POST' style='margin: 20px 0;'>";
    echo "<input type='hidden' name='action' value='migrate'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>";
    echo "🚀 เริ่ม Migration ข้อมูล";
    echo "</button>";
    echo "</form>";
    
    // Handle Migration
    if (isset($_POST['action']) && $_POST['action'] === 'migrate') {
        echo "<h3>🔄 กำลัง Migrate ข้อมูล...</h3>";
        
        // เคลียร์ข้อมูลเก่าก่อน
        $conn->exec("DELETE FROM order_items");
        echo "🗑️ เคลียร์ข้อมูลเก่าใน order_items<br>";
        
        // ดึงข้อมูลจาก orders
        $orders = $conn->query("
            SELECT DocumentNo, CustomerName, ProductName, TotalAmount, Quantity, created_at
            FROM orders 
            WHERE ProductName IS NOT NULL AND ProductName != ''
        ");
        
        $migrated_count = 0;
        
        while ($order = $orders->fetch(PDO::FETCH_ASSOC)) {
            $documentNo = $order['DocumentNo'];
            $customerName = $order['CustomerName'];
            $totalAmount = $order['TotalAmount'];
            $totalQuantity = $order['Quantity'] ?: 1;
            $orderCreatedAt = $order['created_at'];
            
            // แยก ProductName
            $products = explode(',', $order['ProductName']);
            $itemCount = count($products);
            
            foreach ($products as $index => $product) {
                $product = trim($product);
                if (empty($product)) continue;
                
                // คำนวณ quantity และ price สำหรับแต่ละรายการ
                $itemQuantity = ceil($totalQuantity / $itemCount);
                $unitPrice = $totalAmount / $totalQuantity;
                $subtotal = $unitPrice * $itemQuantity;
                
                // Insert ข้อมูลลง order_items
                $stmt = $conn->prepare("
                    INSERT INTO order_items 
                    (DocumentNo, ProductName, Quantity, UnitPrice, Subtotal, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $documentNo,
                    $product,
                    $itemQuantity,
                    $unitPrice,
                    $subtotal,
                    $orderCreatedAt
                ]);
                
                $migrated_count++;
            }
        }
        
        echo "✅ <strong>Migration สำเร็จ!</strong><br>";
        echo "📊 Migrated <strong>{$migrated_count}</strong> รายการ<br><br>";
        
        // แสดงผลลัพธ์
        echo "<h4>ผลลัพธ์ Migration:</h4>";
        $result = $conn->query("
            SELECT 
                COUNT(*) as total_items,
                COUNT(DISTINCT DocumentNo) as unique_orders,
                AVG(UnitPrice) as avg_unit_price,
                SUM(Subtotal) as total_value
            FROM order_items
        ");
        $summary = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<strong>📈 สรุปผลลัพธ์:</strong><br>";
        echo "• Total Items: <strong>{$summary['total_items']}</strong><br>";
        echo "• Unique Orders: <strong>{$summary['unique_orders']}</strong><br>";
        echo "• Average Unit Price: <strong>" . number_format($summary['avg_unit_price'], 2) . "</strong><br>";
        echo "• Total Value: <strong>" . number_format($summary['total_value'], 2) . "</strong><br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>