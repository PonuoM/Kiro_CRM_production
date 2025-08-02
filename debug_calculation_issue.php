<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 Debug การคำนวณ Subtotal</h2>";
    
    // 1. ตรวจสอบ order ที่มีปัญหา
    echo "<h3>1. ค้นหา Order ที่มีปัญหา (Price 500, Subtotal 899.60)</h3>";
    $result = $conn->query("
        SELECT 
            DocumentNo, 
            Price, 
            SubtotalAmount, 
            DiscountAmount, 
            DiscountPercent,
            Quantity,
            Products,
            TotalItems,
            (Price - DiscountAmount) as calculated_final
        FROM orders 
        WHERE Price = 500 OR SubtotalAmount = 899.60
        ORDER BY CreatedDate DESC
    ");
    
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>DocumentNo</th><th>Price</th><th>SubtotalAmount</th><th>DiscountAmount</th>";
        echo "<th>DiscountPercent</th><th>Qty</th><th>Products</th><th>Calculated Final</th>";
        echo "</tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $bgColor = ($row['SubtotalAmount'] != $row['Price']) ? 'background: #ffe6e6;' : '';
            echo "<tr style='{$bgColor}'>";
            echo "<td>{$row['DocumentNo']}</td>";
            echo "<td>" . number_format($row['Price'], 2) . "</td>";
            echo "<td><strong>" . number_format($row['SubtotalAmount'], 2) . "</strong></td>";
            echo "<td>" . number_format($row['DiscountAmount'], 2) . "</td>";
            echo "<td>{$row['DiscountPercent']}%</td>";
            echo "<td>{$row['Quantity']}</td>";
            echo "<td>" . htmlspecialchars($row['Products']) . "</td>";
            echo "<td>" . number_format($row['calculated_final'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 2. ตรวจสอบ order_items ที่เกี่ยวข้อง
    echo "<h3>2. ตรวจสอบ Order Items ที่เกี่ยวข้อง</h3>";
    $result = $conn->query("
        SELECT 
            oi.DocumentNo,
            oi.ProductName,
            oi.Quantity as item_qty,
            oi.UnitPrice,
            oi.LineTotal,
            o.Price as order_price,
            o.SubtotalAmount as order_subtotal,
            o.DiscountAmount
        FROM order_items oi
        JOIN orders o ON oi.DocumentNo = o.DocumentNo
        WHERE o.Price = 500 OR o.SubtotalAmount = 899.60
        ORDER BY oi.DocumentNo, oi.id
    ");
    
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>DocumentNo</th><th>Product</th><th>Item Qty</th><th>Unit Price</th>";
        echo "<th>Line Total</th><th>Order Price</th><th>Order Subtotal</th><th>Issue</th>";
        echo "</tr>";
        
        $currentDoc = '';
        $itemTotalSum = 0;
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if ($currentDoc != $row['DocumentNo']) {
                if ($currentDoc != '') {
                    // แสดงสรุปของ order ก่อนหน้า
                    echo "<tr style='background: #fff3cd; font-weight: bold;'>";
                    echo "<td colspan='4'>รวม {$currentDoc}</td>";
                    echo "<td>" . number_format($itemTotalSum, 2) . "</td>";
                    echo "<td colspan='3'>Sum Items vs Order Subtotal</td>";
                    echo "</tr>";
                }
                $currentDoc = $row['DocumentNo'];
                $itemTotalSum = 0;
            }
            
            $itemTotalSum += $row['LineTotal'];
            
            echo "<tr>";
            echo "<td>{$row['DocumentNo']}</td>";
            echo "<td>{$row['ProductName']}</td>";
            echo "<td>{$row['item_qty']}</td>";
            echo "<td>" . number_format($row['UnitPrice'], 2) . "</td>";
            echo "<td>" . number_format($row['LineTotal'], 2) . "</td>";
            echo "<td>" . number_format($row['order_price'], 2) . "</td>";
            echo "<td>" . number_format($row['order_subtotal'], 2) . "</td>";
            
            // หาปัญหา
            $issue = '';
            if ($row['LineTotal'] != ($row['UnitPrice'] * $row['item_qty'])) {
                $issue .= 'LineTotal ผิด; ';
            }
            if (abs($row['order_subtotal'] - $row['order_price']) > 0.01) {
                $issue .= 'Subtotal≠Price; ';
            }
            echo "<td style='color: red;'>{$issue}</td>";
            echo "</tr>";
        }
        
        // แสดงสรุปของ order สุดท้าย
        if ($currentDoc != '') {
            echo "<tr style='background: #fff3cd; font-weight: bold;'>";
            echo "<td colspan='4'>รวม {$currentDoc}</td>";
            echo "<td>" . number_format($itemTotalSum, 2) . "</td>";
            echo "<td colspan='3'>Sum Items vs Order Subtotal</td>";
            echo "</tr>";
        }
        
        echo "</table><br>";
    }
    
    // 3. วิเคราะห์สาเหตุ
    echo "<h3>3. วิเคราะห์สาเหตุปัญหา</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<h4>🔍 สาเหตุที่เป็นไปได้:</h4>";
    echo "<ol>";
    echo "<li><strong>Migration ผิดพลาด:</strong> UnitPrice คำนวณผิด → LineTotal ผิด</li>";
    echo "<li><strong>Quantity แจกจ่ายผิด:</strong> แยกสินค้าแล้ว quantity ไม่ถูกต้อง</li>";
    echo "<li><strong>SubtotalAmount ไม่ได้ update:</strong> ยังเป็นค่าเก่าจากก่อน migration</li>";
    echo "<li><strong>การคำนวณส่วนลด:</strong> ลำดับการคำนวณผิด (ก่อน/หลังส่วนลด)</li>";
    echo "</ol>";
    echo "</div><br>";
    
    // 4. แสดงสูตรที่ถูกต้อง
    echo "<h3>4. สูตรการคำนวณที่ถูกต้อง</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<h4>📐 สูตรที่ควรใช้:</h4>";
    echo "<code>";
    echo "ยอดรวม = 535<br>";
    echo "ส่วนลด = 35 (6.54%)<br>";
    echo "ยอดสุทธิ = 535 - 35 = 500<br><br>";
    
    echo "<strong>ใน orders table:</strong><br>";
    echo "Price = 500 (ยอดสุทธิหลังหักส่วนลด)<br>";
    echo "SubtotalAmount = 535 (ยอดก่อนหักส่วนลด)<br>";
    echo "DiscountAmount = 35<br><br>";
    
    echo "<strong>ใน order_items:</strong><br>";
    echo "UnitPrice = Price ÷ Quantity = 500 ÷ Quantity<br>";
    echo "LineTotal = UnitPrice × Item_Quantity<br>";
    echo "SUM(LineTotal) ควรเท่ากับ orders.Price (500)";
    echo "</code>";
    echo "</div><br>";
    
    // 5. แนะนำการแก้ไข
    echo "<h3>5. แนะนำการแก้ไข</h3>";
    echo "<form method='POST'>";
    echo "<input type='hidden' name='action' value='fix_calculation'>";
    echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
    echo "<h4>🔧 แก้ไขปัญหา:</h4>";
    echo "<ol>";
    echo "<li>ใช้ <strong>orders.Price</strong> (ยอดสุทธิ) ในการคำนวณ UnitPrice</li>";
    echo "<li>ใช้ <strong>orders.SubtotalAmount</strong> เป็นยอดก่อนส่วนลด</li>";
    echo "<li>Re-calculate order_items ให้ตรงกับหลักการนี้</li>";
    echo "</ol>";
    echo "</div>";
    echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin: 10px 0;'>";
    echo "🔧 แก้ไขการคำนวณ";
    echo "</button>";
    echo "</form>";
    
    // Handle Fix
    if (isset($_POST['action']) && $_POST['action'] === 'fix_calculation') {
        echo "<h3>🔧 กำลังแก้ไขการคำนวณ...</h3>";
        
        // อัปเดต order_items ให้ใช้ Price แทน SubtotalAmount
        $orders = $conn->query("
            SELECT DocumentNo, Price, Quantity 
            FROM orders 
            WHERE Price IS NOT NULL AND Price > 0
        ");
        
        $fixedCount = 0;
        
        while ($order = $orders->fetch(PDO::FETCH_ASSOC)) {
            $documentNo = $order['DocumentNo'];
            $netPrice = $order['Price']; // ยอดสุทธิหลังส่วนลด
            $totalQty = $order['Quantity'] ?: 1;
            
            if ($totalQty > 0) {
                $unitPrice = $netPrice / $totalQty;
                
                // อัปเดต order_items
                $stmt = $conn->prepare("
                    UPDATE order_items 
                    SET UnitPrice = ?, 
                        LineTotal = UnitPrice * Quantity 
                    WHERE DocumentNo = ?
                ");
                $stmt->execute([$unitPrice, $documentNo]);
                $fixedCount++;
            }
        }
        
        echo "<div style='background: #d4edda; padding: 10px; border-left: 4px solid #28a745;'>";
        echo "✅ แก้ไขเสร็จแล้ว! Updated {$fixedCount} orders";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>