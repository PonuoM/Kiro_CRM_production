<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🚀 Real Migration Script</h2>";
    
    // 1. ตรวจสอบข้อมูล ProductsDetail ว่ามีอะไรบ้าง
    echo "<h3>1. ตรวจสอบ ProductsDetail</h3>";
    $result = $conn->query("
        SELECT DocumentNo, Products, ProductsDetail, Quantity, Price 
        FROM orders 
        WHERE ProductsDetail IS NOT NULL AND ProductsDetail != '' 
        LIMIT 3
    ");
    
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>DocumentNo</th><th>Products</th><th>ProductsDetail</th><th>Qty</th><th>Price</th></tr>";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['DocumentNo']}</td>";
            echo "<td>" . htmlspecialchars($row['Products']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['ProductsDetail'], 0, 100)) . "...</td>";
            echo "<td>{$row['Quantity']}</td>";
            echo "<td>" . number_format($row['Price'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "ℹ️ ProductsDetail ส่วนใหญ่ว่าง จะใช้ Products เป็นหลัก<br><br>";
    }
    
    // 2. แสดงกลยุทธ์ Migration
    echo "<h3>2. กลยุทธ์ Migration</h3>";
    echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
    echo "<h4>📋 แผนการ Migration:</h4>";
    echo "<ul>";
    echo "<li><strong>Products Column:</strong> แยกสินค้าที่มี comma (,) เป็นหลายรายการ</li>";
    echo "<li><strong>Unit Price:</strong> คำนวณจาก Price ÷ Quantity</li>";
    echo "<li><strong>Subtotal:</strong> UnitPrice × Quantity สำหรับแต่ละรายการ</li>";
    echo "<li><strong>ProductsDetail:</strong> ใช้เป็นข้อมูลเสริมถ้ามี</li>";
    echo "</ul>";
    echo "</div><br>";
    
    // 3. ตัวอย่างการแยกข้อมูล
    echo "<h3>3. ตัวอย่างการแยกข้อมูล (ใช้ SubtotalAmount)</h3>";
    $result = $conn->query("
        SELECT DocumentNo, Products, Quantity, Price, SubtotalAmount, ProductsDetail
        FROM orders 
        WHERE Products IS NOT NULL AND Products != ''
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>DocumentNo</th><th>Products</th><th>Price (ก่อนส่วนลด)</th><th>SubtotalAmount (สุทธิ)</th><th>Will Split To</th><th>Unit Price</th>";
    echo "</tr>";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $products = $row['Products'];
        $quantity = $row['Quantity'] ?: 1;
        $price = $row['Price'] ?: 0; // ยอดก่อนส่วนลด
        $subtotalAmount = $row['SubtotalAmount'] ?: 0; // ยอดสุทธิ
        $netPrice = $subtotalAmount ?: $price; // ใช้ยอดสุทธิในการคำนวณ
        $unitPrice = $quantity > 0 ? $netPrice / $quantity : 0;
        
        // แยกสินค้า
        $productList = explode(',', $products);
        $splitProducts = [];
        foreach ($productList as $product) {
            $product = trim($product);
            if (!empty($product)) {
                $splitProducts[] = $product;
            }
        }
        
        echo "<tr>";
        echo "<td>{$row['DocumentNo']}</td>";
        echo "<td>" . htmlspecialchars($products) . "</td>";
        echo "<td>" . number_format($price, 2) . "</td>";
        echo "<td>" . number_format($subtotalAmount, 2) . "</td>";
        echo "<td>";
        foreach ($splitProducts as $i => $product) {
            $itemQty = ceil($quantity / count($splitProducts));
            $itemSubtotal = $unitPrice * $itemQty;
            echo ($i + 1) . ". " . htmlspecialchars($product) . " (Qty: {$itemQty}, Subtotal: " . number_format($itemSubtotal, 2) . ")<br>";
        }
        echo "</td>";
        echo "<td>" . number_format($unitPrice, 2) . " (from สุทธิ)</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 4. Migration Button
    echo "<h3>4. 🚀 เริ่ม Migration</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='migrate'>";
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<p><strong>พร้อม Migrate แล้ว!</strong></p>";
    echo "<p>จะทำการ:</p>";
    echo "<ul>";
    echo "<li>✅ เคลียร์ข้อมูลเก่าใน order_items</li>";
    echo "<li>✅ แยกสินค้าจาก Products column</li>";
    echo "<li>✅ คำนวณ UnitPrice และ Subtotal</li>";
    echo "<li>✅ เชื่อมโยง DocumentNo</li>";
    echo "</ul>";
    echo "</div>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; margin: 10px 0;'>";
    echo "🔄 เริ่ม Migration ข้อมูล";
    echo "</button>";
    echo "</form>";
    
    // Handle Migration
    if (isset($_POST['action']) && $_POST['action'] === 'migrate') {
        echo "<h3>🔄 กำลัง Migrate...</h3>";
        
        // เคลียร์ข้อมูลเก่า
        $conn->exec("DELETE FROM order_items");
        echo "🗑️ เคลียร์ข้อมูลเก่าใน order_items สำเร็จ<br>";
        
        // ดึงข้อมูลจาก orders
        $orders = $conn->query("
            SELECT DocumentNo, Products, ProductsDetail, Quantity, Price, SubtotalAmount, CreatedDate, CreatedBy
            FROM orders 
            WHERE Products IS NOT NULL AND Products != ''
        ");
        
        $migratedCount = 0;
        $orderCount = 0;
        
        while ($order = $orders->fetch(PDO::FETCH_ASSOC)) {
            $orderCount++;
            $documentNo = $order['DocumentNo'];
            $products = $order['Products'];
            $totalQuantity = $order['Quantity'] ?: 1;
            $totalPrice = $order['Price'] ?: 0; // ยอดก่อนหักส่วนลด
            $subtotalAmount = $order['SubtotalAmount'] ?: 0; // ยอดสุทธิ
            $createdDate = $order['CreatedDate'];
            $createdBy = $order['CreatedBy'];
            
            echo "📦 Processing {$documentNo}...<br>";
            
            // แยกสินค้า
            $productList = explode(',', $products);
            $validProducts = [];
            
            foreach ($productList as $product) {
                $product = trim($product);
                if (!empty($product)) {
                    $validProducts[] = $product;
                }
            }
            
            if (empty($validProducts)) {
                continue;
            }
            
            // คำนวณ unit price และ quantity สำหรับแต่ละรายการ
            // ใช้ SubtotalAmount (ยอดสุทธิ) ในการคำนวณ UnitPrice แทน Price
            $itemCount = count($validProducts);
            $netPrice = $subtotalAmount ?: $totalPrice; // ยอดสุทธิหลังหักส่วนลด
            $unitPrice = $totalQuantity > 0 ? $netPrice / $totalQuantity : 0;
            $qtyPerItem = ceil($totalQuantity / $itemCount);
            
            foreach ($validProducts as $product) {
                $subtotal = $unitPrice * $qtyPerItem;
                
                // Insert ข้อมูลลง order_items
                $stmt = $conn->prepare("
                    INSERT INTO order_items 
                    (DocumentNo, ProductName, Quantity, UnitPrice, LineTotal, CreatedDate, CreatedBy) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $documentNo,
                    $product,
                    $qtyPerItem,
                    $unitPrice,
                    $subtotal,
                    $createdDate,
                    $createdBy
                ]);
                
                $migratedCount++;
            }
        }
        
        echo "<br><div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<h4>✅ Migration สำเร็จ!</h4>";
        echo "📊 Processed Orders: <strong>{$orderCount}</strong><br>";
        echo "📦 Created Items: <strong>{$migratedCount}</strong><br>";
        echo "</div>";
        
        // แสดงผลลัพธ์
        echo "<h4>ผลลัพธ์ Migration:</h4>";
        $result = $conn->query("
            SELECT 
                COUNT(*) as total_items,
                COUNT(DISTINCT DocumentNo) as unique_orders,
                ROUND(AVG(UnitPrice), 2) as avg_unit_price,
                ROUND(SUM(LineTotal), 2) as total_value
            FROM order_items
        ");
        $summary = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Metric</th><th>Value</th></tr>";
        echo "<tr><td>Total Items</td><td><strong>{$summary['total_items']}</strong></td></tr>";
        echo "<tr><td>Unique Orders</td><td><strong>{$summary['unique_orders']}</strong></td></tr>";
        echo "<tr><td>Average Unit Price</td><td><strong>" . number_format($summary['avg_unit_price'], 2) . "</strong></td></tr>";
        echo "<tr><td>Total Value</td><td><strong>" . number_format($summary['total_value'], 2) . "</strong></td></tr>";
        echo "</table>";
        
        echo "<br><div style='background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;'>";
        echo "<strong>🎯 ขั้นตอนต่อไป:</strong> แก้ไข Sales Performance API เพื่อแสดงข้อมูลจาก order_items";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>