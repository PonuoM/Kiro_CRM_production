<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 Deep Analysis - การคำนวณปัญหา</h2>";
    
    // 1. ตรวจสอบข้อมูลใน orders ที่สร้างล่าสุด
    echo "<h3>1. ข้อมูลใน Orders Table (5 รายการล่าสุด)</h3>";
    $result = $conn->query("
        SELECT 
            DocumentNo,
            Products,
            Quantity,
            Price,
            SubtotalAmount,
            DiscountAmount,
            DiscountPercent,
            DiscountRemarks,
            CreatedDate
        FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 5
    ");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>DocumentNo</th><th>Products</th><th>Qty</th><th>Price</th>";
    echo "<th>SubtotalAmount</th><th>DiscountAmount</th><th>Discount%</th><th>Created</th>";
    echo "</tr>";
    
    $recentOrders = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $recentOrders[] = $row;
        
        // สีพื้นหลังถ้าข้อมูลดูแปลก
        $bgColor = '';
        if ($row['Price'] > 0 && $row['SubtotalAmount'] > 0) {
            if ($row['Price'] < $row['SubtotalAmount']) {
                $bgColor = 'background-color: #ffe6e6;'; // Price < SubtotalAmount (แปลก)
            }
        }
        
        echo "<tr style='{$bgColor}'>";
        echo "<td>{$row['DocumentNo']}</td>";
        echo "<td>" . htmlspecialchars($row['Products']) . "</td>";
        echo "<td>{$row['Quantity']}</td>";
        echo "<td>" . number_format($row['Price'], 2) . "</td>";
        echo "<td>" . number_format($row['SubtotalAmount'], 2) . "</td>";
        echo "<td>" . number_format($row['DiscountAmount'], 2) . "</td>";
        echo "<td>{$row['DiscountPercent']}%</td>";
        echo "<td>{$row['CreatedDate']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 2. วิเคราะห์ข้อมูลล่าสุด
    if (!empty($recentOrders)) {
        $latestOrder = $recentOrders[0];
        echo "<h3>2. วิเคราะห์ Order ล่าสุด: {$latestOrder['DocumentNo']}</h3>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<h4>📊 ข้อมูลดิบ:</h4>";
        echo "<ul>";
        echo "<li>Products: " . htmlspecialchars($latestOrder['Products']) . "</li>";
        echo "<li>Quantity: {$latestOrder['Quantity']}</li>";
        echo "<li>Price: " . number_format($latestOrder['Price'], 2) . "</li>";
        echo "<li>SubtotalAmount: " . number_format($latestOrder['SubtotalAmount'], 2) . "</li>";
        echo "<li>DiscountAmount: " . number_format($latestOrder['DiscountAmount'], 2) . "</li>";
        echo "<li>DiscountPercent: {$latestOrder['DiscountPercent']}%</li>";
        echo "</ul>";
        echo "</div>";
        
        // คำนวณสิ่งที่ควรเป็น
        $price = $latestOrder['Price'];
        $subtotal = $latestOrder['SubtotalAmount'];
        $discount = $latestOrder['DiscountAmount'];
        $discountPercent = $latestOrder['DiscountPercent'];
        
        echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
        echo "<h4>🧮 วิเคราะห์การคำนวณ:</h4>";
        
        // กรณีที่ 1: Price = ยอดก่อนส่วนลด, SubtotalAmount = ยอดสุทธิ
        echo "<strong>กรณีที่ 1: Price = ยอดก่อนส่วนลด, SubtotalAmount = ยอดสุทธิ</strong><br>";
        $calculated1 = $price - $discount;
        echo "คำนวณ: {$price} - {$discount} = " . number_format($calculated1, 2) . "<br>";
        echo "เทียบกับ SubtotalAmount: " . number_format($subtotal, 2);
        echo ($calculated1 == $subtotal) ? " ✅ ตรง!" : " ❌ ไม่ตรง!";
        echo "<br><br>";
        
        // กรณีที่ 2: SubtotalAmount = ยอดก่อนส่วนลด, Price = ยอดสุทธิ  
        echo "<strong>กรณีที่ 2: SubtotalAmount = ยอดก่อนส่วนลด, Price = ยอดสุทธิ</strong><br>";
        $calculated2 = $subtotal - $discount;
        echo "คำนวณ: {$subtotal} - {$discount} = " . number_format($calculated2, 2) . "<br>";
        echo "เทียบกับ Price: " . number_format($price, 2);
        echo ($calculated2 == $price) ? " ✅ ตรง!" : " ❌ ไม่ตรง!";
        echo "<br><br>";
        
        // ตรวจสอบ discount percent
        if ($price > 0) {
            $calculatedPercent1 = ($discount / $price) * 100;
            echo "<strong>Discount % จาก Price:</strong> " . number_format($calculatedPercent1, 2) . "% ";
            echo (abs($calculatedPercent1 - $discountPercent) < 0.01) ? "✅" : "❌";
            echo "<br>";
        }
        
        if ($subtotal > 0) {
            $calculatedPercent2 = ($discount / $subtotal) * 100;
            echo "<strong>Discount % จาก SubtotalAmount:</strong> " . number_format($calculatedPercent2, 2) . "% ";
            echo (abs($calculatedPercent2 - $discountPercent) < 0.01) ? "✅" : "❌";
            echo "<br>";
        }
        
        echo "</div>";
    }
    
    // 3. ตรวจสอบ order_items
    echo "<h3>3. ตรวจสอบ Order Items</h3>";
    if (!empty($recentOrders)) {
        $latestDoc = $recentOrders[0]['DocumentNo'];
        $result = $conn->query("
            SELECT * FROM order_items 
            WHERE DocumentNo = '{$latestDoc}'
            ORDER BY id
        ");
        
        if ($result->rowCount() > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>ProductName</th><th>Quantity</th><th>UnitPrice</th><th>LineTotal</th>";
            echo "</tr>";
            
            $totalLineTotal = 0;
            while ($item = $result->fetch(PDO::FETCH_ASSOC)) {
                $totalLineTotal += $item['LineTotal'];
                
                echo "<tr>";
                echo "<td>{$item['ProductName']}</td>";
                echo "<td>{$item['Quantity']}</td>";
                echo "<td>" . number_format($item['UnitPrice'], 2) . "</td>";
                echo "<td>" . number_format($item['LineTotal'], 2) . "</td>";
                echo "</tr>";
            }
            
            echo "<tr style='background: #fff3cd; font-weight: bold;'>";
            echo "<td colspan='3'>รวม LineTotal</td>";
            echo "<td>" . number_format($totalLineTotal, 2) . "</td>";
            echo "</tr>";
            echo "</table>";
            
            echo "<div style='background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0;'>";
            echo "<strong>🔍 เปรียบเทียบ:</strong><br>";
            echo "Sum(LineTotal): " . number_format($totalLineTotal, 2) . "<br>";
            echo "Order.Price: " . number_format($latestOrder['Price'], 2);
            echo (abs($totalLineTotal - $latestOrder['Price']) < 0.01) ? " ✅ ตรง" : " ❌ ไม่ตรง";
            echo "<br>";
            echo "Order.SubtotalAmount: " . number_format($latestOrder['SubtotalAmount'], 2);
            echo (abs($totalLineTotal - $latestOrder['SubtotalAmount']) < 0.01) ? " ✅ ตรง" : " ❌ ไม่ตรง";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px;'>❌ ไม่มี order_items สำหรับ {$latestDoc}</div>";
        }
    }
    
    // 4. ตรวจสอบการทำงานของ Frontend
    echo "<h3>4. ตรวจสอบการส่งข้อมูลจาก Frontend</h3>";
    echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
    echo "<h4>🔍 สิ่งที่ต้องตรวจสอบ:</h4>";
    echo "<ol>";
    echo "<li><strong>Form Input:</strong> ช่อง 'ยอดรวม (ก่อนหักส่วนลด)' ส่งไปใน field ไหน?</li>";
    echo "<li><strong>JavaScript:</strong> การคำนวณใน frontend ถูกต้องไหม?</li>";
    echo "<li><strong>API Request:</strong> JSON ที่ส่งไป create.php มีค่าอะไร?</li>";
    echo "<li><strong>Mapping:</strong> Frontend ส่ง 'ยอดรวม' ไปใน Price หรือ SubtotalAmount?</li>";
    echo "</ol>";
    echo "</div>";
    
    // 5. แนะนำการ Debug
    echo "<h3>5. แนะนำการ Debug</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='enable_debug'>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<h4>🛠️ เปิด Debug Mode:</h4>";
    echo "<p>เพิ่ม log ใน create.php เพื่อดูข้อมูลที่ส่งมาจาก frontend</p>";
    echo "</div>";
    echo "<button type='submit' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px;'>";
    echo "🔍 เปิด Debug Logging";
    echo "</button>";
    echo "</form>";
    
    // Handle Debug Enable
    if (isset($_POST['action']) && $_POST['action'] === 'enable_debug') {
        echo "<div style='background: #d4edda; padding: 10px; border-left: 4px solid #28a745; margin: 10px 0;'>";
        echo "✅ Debug mode เปิดแล้ว! ลองสร้าง order ใหม่แล้วดู error_log";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<script>
// เพิ่ม JavaScript เพื่อดู network requests
console.log("🔍 Debug Mode Active - Monitor Network Tab when creating orders");
</script>