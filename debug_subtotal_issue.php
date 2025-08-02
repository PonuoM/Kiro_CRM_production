<?php
echo "<h2>🔍 Debug SubtotalAmount Issue</h2>";

echo "<h3>1. ปัญหาปัจจุบัน</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
echo "<ul>";
echo "<li><strong>Frontend ส่ง:</strong> 260.00</li>";
echo "<li><strong>Database ได้:</strong> 376.92</li>";
echo "<li><strong>ความแตกต่าง:</strong> " . (376.92 - 260) . " บาท</li>";
echo "</ul>";
echo "</div>";

echo "<h3>2. สาเหตุที่เป็นไปได้</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<ol>";
echo "<li><strong>API create.php</strong> คำนวณใหม่ทับค่าที่ส่งมา</li>";
echo "<li><strong>Order.php</strong> คำนวณใหม่จาก products array</li>";
echo "<li><strong>Database trigger/constraint</strong> มีการคำนวณเพิ่ม</li>";
echo "<li><strong>BaseModel</strong> หรือ function อื่นแก้ไขข้อมูล</li>";
echo "</ol>";
echo "</div>";

echo "<h3>3. ตรวจสอบ Error Log</h3>";
echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
echo "<p>ดู Apache Error Log ว่ามี Debug message อะไรบ้าง:</p>";
echo "<pre style='background: #333; color: #fff; padding: 10px;'>";
echo "หา message เหล่านี้:
=== FRONTEND DIRECT MAPPING ===
Frontend Subtotal Amount: ???

=== ORDER.PHP RECEIVED DATA ===
SubtotalAmount: ???

Final order data before insert: [...]";
echo "</pre>";
echo "</div>";

echo "<h3>4. JavaScript Debug Command</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console ก่อน Submit Order
const originalSubmit = window.customerDetail?.submitOrder;
if (originalSubmit) {
    window.customerDetail.submitOrder = function() {
        console.log('🔍 DEBUGGING SUBTOTAL ISSUE');
        
        const subtotalElement = document.getElementById('subtotal-amount');
        const subtotalValue = subtotalElement?.value;
        
        console.log('📊 SUBTOTAL ANALYSIS:');
        console.log('Element found:', !!subtotalElement);
        console.log('Element value:', subtotalValue);
        console.log('Element type:', typeof subtotalValue);
        console.log('Parsed float:', parseFloat(subtotalValue || 0));
        
        // ตรวจสอบว่ามีการคำนวณใหม่ไหม
        const products = document.querySelectorAll('[name=\"product_quantity[]\"]');
        const prices = document.querySelectorAll('[name=\"product_price[]\"]');
        
        let calculatedSubtotal = 0;
        products.forEach((qtyInput, index) => {
            const qty = parseFloat(qtyInput.value || 0);
            const price = parseFloat(prices[index]?.value || 0);
            const lineTotal = qty * price;
            calculatedSubtotal += lineTotal;
            console.log(`Product \${index + 1}: \${qty} × \${price} = \${lineTotal}`);
        });
        
        console.log('📊 CALCULATION COMPARISON:');
        console.log('Frontend shows:', subtotalValue);
        console.log('Calculated from products:', calculatedSubtotal.toFixed(2));
        console.log('Match:', Math.abs(parseFloat(subtotalValue) - calculatedSubtotal) < 0.01 ? '✅ YES' : '❌ NO');
        
        return originalSubmit.call(this);
    };
    console.log('✅ Debug override set');
}";
echo "</pre>";
echo "</div>";

echo "<h3>5. ตรวจสอบ API Code</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<p>ต้องตรวจสอบใน <code>api/orders/create.php</code> ว่ามีการคำนวณใหม่ไหม:</p>";
echo "<pre>";
echo "// หาโค้ดแบบนี้ที่อาจคำนวณทับ:
\$totalAmount += (\$quantity * \$price);
\$orderData['SubtotalAmount'] = \$totalAmount; // ← นี่คือปัญหา!";
echo "</pre>";
echo "</div>";

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h3>6. ตรวจสอบข้อมูลสินค้าใน Order ล่าสุด</h3>";
    
    $result = $conn->query("
        SELECT DocumentNo, Products, ProductsDetail, Quantity, SubtotalAmount, Price 
        FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 1
    ");
    
    if ($result->rowCount() > 0) {
        $order = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
        echo "<strong>Order:</strong> " . $order['DocumentNo'] . "<br>";
        echo "<strong>Products:</strong> " . htmlspecialchars($order['Products']) . "<br>";
        echo "<strong>ProductsDetail:</strong> " . htmlspecialchars($order['ProductsDetail']) . "<br>";
        echo "<strong>Quantity:</strong> " . $order['Quantity'] . "<br>";
        echo "<strong>SubtotalAmount:</strong> " . $order['SubtotalAmount'] . "<br>";
        echo "<strong>Price:</strong> " . $order['Price'] . "<br>";
        echo "</div>";
        
        // ถ้ามี ProductsDetail ให้ decode ดู
        if (!empty($order['ProductsDetail'])) {
            $productsDetail = json_decode($order['ProductsDetail'], true);
            if ($productsDetail) {
                echo "<h4>📦 Products Detail:</h4>";
                echo "<pre style='background: #333; color: #fff; padding: 10px; font-size: 12px;'>";
                print_r($productsDetail);
                echo "</pre>";
                
                // คำนวณ subtotal จาก products detail
                $calculatedSubtotal = 0;
                foreach ($productsDetail as $product) {
                    $qty = (float)($product['quantity'] ?? 0);
                    $price = (float)($product['price'] ?? 0);
                    $calculatedSubtotal += ($qty * $price);
                }
                
                echo "<div style='background: " . (abs($calculatedSubtotal - 260) < 0.01 ? "#d4edda" : "#f8d7da") . "; padding: 10px;'>";
                echo "<strong>Calculated from ProductsDetail:</strong> " . number_format($calculatedSubtotal, 2) . "<br>";
                echo "<strong>Expected:</strong> 260.00<br>";
                echo "<strong>Database SubtotalAmount:</strong> " . $order['SubtotalAmount'] . "<br>";
                echo "<strong>Analysis:</strong> ";
                if (abs($calculatedSubtotal - 376.92) < 0.01) {
                    echo "❌ API คำนวณจาก products ทับค่าที่ส่งมา";
                } elseif (abs($calculatedSubtotal - 260) < 0.01) {
                    echo "✅ ProductsDetail ถูกต้อง แต่มีที่อื่นคำนวณทับ";
                } else {
                    echo "🤔 มีการคำนวณผิดพลาดที่อื่น";
                }
                echo "</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
pre { font-size: 12px; line-height: 1.4; }
</style>