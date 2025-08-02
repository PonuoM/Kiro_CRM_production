<?php
/**
 * Test Order Items Integration Fix
 * ทดสอบการแก้ไขการบันทึกข้อมูลลง order_items
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config/database.php';

echo "<h2>🧪 Test Order Items Integration Fix</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>1️⃣ ตรวจสอบข้อมูลก่อนทดสอบ</h3>";
    
    // นับ order_items ก่อนทดสอบ
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM order_items");
    $itemsCountBefore = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // นับ orders ก่อนทดสอบ
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $ordersCountBefore = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>Orders ก่อนทดสอบ:</strong> {$ordersCountBefore} รายการ</p>";
    echo "<p><strong>Order Items ก่อนทดสอบ:</strong> {$itemsCountBefore} รายการ</p>";
    
    echo "<h3>2️⃣ สร้าง Mock Order เพื่อทดสอบ</h3>";
    
    // สมมติข้อมูล Order สำหรับทดสอบ
    $testOrderData = [
        'CustomerCode' => 'TEST001',
        'DocumentDate' => date('Y-m-d H:i:s'),
        'PaymentMethod' => 'เงินสด',
        'products' => [
            [
                'name' => 'F001 - ปุ๋ยเคมี 16-16-16',
                'code' => 'F001',
                'quantity' => 2,
                'price' => 18.50
            ],
            [
                'name' => 'O001 - ปุ๋ยหมักมีกากมด',
                'code' => 'O001',
                'quantity' => 1,
                'price' => 45.00
            ]
        ],
        'total_quantity' => 3,
        'subtotal_amount' => 82.00, // (2*18.50) + (1*45.00)
        'discount_amount' => 2.00,
        'discount_percent' => 2.44,
        'total_amount' => 80.00
    ];
    
    // คำนวณค่าที่คาดหวัง
    $expectedSubtotal = 0;
    foreach ($testOrderData['products'] as $product) {
        $expectedSubtotal += $product['quantity'] * $product['price'];
    }
    
    echo "<p><strong>ข้อมูลทดสอบ:</strong></p>";
    echo "<ul>";
    echo "<li>Customer: {$testOrderData['CustomerCode']}</li>";
    echo "<li>Products: " . count($testOrderData['products']) . " รายการ</li>";
    echo "<li>Expected Subtotal: " . number_format($expectedSubtotal, 2) . " บาท</li>";
    echo "<li>Total Amount: " . number_format($testOrderData['total_amount'], 2) . " บาท</li>";
    echo "</ul>";
    
    echo "<h4>รายการสินค้า:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Code</th><th>Name</th><th>Qty</th><th>Price</th><th>Line Total</th></tr>";
    
    foreach ($testOrderData['products'] as $product) {
        $lineTotal = $product['quantity'] * $product['price'];
        echo "<tr>";
        echo "<td>{$product['code']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['quantity']}</td>";
        echo "<td>" . number_format($product['price'], 2) . "</td>";
        echo "<td>" . number_format($lineTotal, 2) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>3️⃣ การทดสอบ API</h3>";
    echo "<p><strong>วิธีทดสอบ:</strong></p>";
    echo "<ol>";
    echo "<li>ไปที่หน้า Customer Detail ของลูกค้า TEST001</li>";
    echo "<li>สร้าง Order ใหม่ด้วยข้อมูลดังนี้:</li>";
    echo "<ul>";
    echo "<li>สินค้า 1: F001 - ปุ๋ยเคมี 16-16-16 จำนวน 2 ราคา 18.50</li>";
    echo "<li>สินค้า 2: O001 - ปุ๋ยหมักมีกากมด จำนวน 1 ราคา 45.00</li>";
    echo "<li>ส่วนลด: 2 บาท</li>";
    echo "</ul>";
    echo "<li>กด Submit และตรวจสอบผล</li>";
    echo "</ol>";
    
    echo "<h3>4️⃣ คำสั่ง SQL สำหรับตรวจสอบ</h3>";
    echo "<h4>ตรวจสอบ Orders ล่าสุด:</h4>";
    echo "<code>";
    echo "SELECT DocumentNo, CustomerCode, SubtotalAmount, Subtotal_amount2, ProductsDetail<br>";
    echo "FROM orders <br>";
    echo "ORDER BY CreatedDate DESC <br>";
    echo "LIMIT 5;";
    echo "</code>";
    
    echo "<h4>ตรวจสอบ Order Items ล่าสุด:</h4>";
    echo "<code>";
    echo "SELECT DocumentNo, ProductCode, ProductName, UnitPrice, Quantity, LineTotal<br>";
    echo "FROM order_items <br>";
    echo "ORDER BY CreatedDate DESC <br>";
    echo "LIMIT 10;";
    echo "</code>";
    
    echo "<h4>ตรวจสอบความสัมพันธ์:</h4>";
    echo "<code>";
    echo "SELECT <br>";
    echo "&nbsp;&nbsp;o.DocumentNo,<br>";
    echo "&nbsp;&nbsp;o.CustomerCode,<br>";
    echo "&nbsp;&nbsp;o.Subtotal_amount2 as OrderSubtotal,<br>";
    echo "&nbsp;&nbsp;COUNT(oi.id) as ItemCount,<br>";
    echo "&nbsp;&nbsp;SUM(oi.LineTotal) as ItemsTotal,<br>";
    echo "&nbsp;&nbsp;(o.Subtotal_amount2 - SUM(oi.LineTotal)) as Difference<br>";
    echo "FROM orders o<br>";
    echo "LEFT JOIN order_items oi ON o.DocumentNo = oi.DocumentNo<br>";
    echo "WHERE o.CreatedDate >= DATE_SUB(NOW(), INTERVAL 1 HOUR)<br>";
    echo "GROUP BY o.DocumentNo<br>";
    echo "ORDER BY o.CreatedDate DESC;";
    echo "</code>";
    
    echo "<h3>5️⃣ ผลลัพธ์ที่คาดหวัง</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ตาราง</th><th>ที่คาดหวัง</th><th>หมายเหตุ</th></tr>";
    echo "<tr>";
    echo "<td><strong>orders</strong></td>";
    echo "<td>";
    echo "• SubtotalAmount: 376.92 (เก่า-ผิด)<br>";
    echo "• Subtotal_amount2: 82.00 (ใหม่-ถูก)<br>";
    echo "• ProductsDetail: มีข้อมูล JSON";
    echo "</td>";
    echo "<td>Header ของ Order</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>order_items</strong></td>";
    echo "<td>";
    echo "• 2 รายการใหม่<br>";
    echo "• ProductCode: F001, O001<br>";
    echo "• LineTotal รวม: 82.00";
    echo "</td>";
    echo "<td>Detail ของ Order</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td><strong>ความสัมพันธ์</strong></td>";
    echo "<td>";
    echo "• Subtotal_amount2 = SUM(LineTotal)<br>";
    echo "• ItemCount = 2<br>";
    echo "• Difference = 0";
    echo "</td>";
    echo "<td>ข้อมูลสอดคล้องกัน</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<h3>6️⃣ Debugging</h3>";
    echo "<p>ถ้าไม่ทำงาน ให้ตรวจสอบ Error Log:</p>";
    echo "<ul>";
    echo "<li><strong>PHP Error Log:</strong> ดูใน /logs/php_errors.log</li>";
    echo "<li><strong>Application Log:</strong> ดูใน error_log ของ Apache/PHP</li>";
    echo "<li><strong>Console Log:</strong> ดูใน Browser Developer Tools</li>";
    echo "</ul>";
    
    echo "<p><strong>Log Messages ที่ควรเห็น:</strong></p>";
    echo "<ul>";
    echo "<li>=== CREATING ORDER ITEMS ===</li>";
    echo "<li>DocumentNo: DOC...</li>";
    echo "<li>Products: Array with product data</li>";
    echo "<li>Creating order item 1: ...</li>";
    echo "<li>Creating order item 2: ...</li>";
    echo "<li>=== ORDER ITEMS CREATED SUCCESSFULLY ===</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<hr>
<h3>📋 สรุปการแก้ไข</h3>
<p><strong>สิ่งที่แก้ไข:</strong></p>
<ol>
<li><strong>api/orders/create.php:</strong> เพิ่มการเรียก createOrderItems หลังสร้าง order สำเร็จ</li>
<li><strong>includes/Order.php:</strong> เพิ่ม method createOrderItems สำหรับบันทึกข้อมูลลง order_items</li>
<li><strong>ProductsDetail:</strong> ปรับให้เก็บข้อมูล products ใน JSON format</li>
<li><strong>ProductsCode:</strong> สามารถดึงจาก product.code หรือแยกจาก product.name</li>
</ol>

<p><strong>คุณสมบัติใหม่:</strong></p>
<ul>
<li>✅ บันทึกข้อมูลลง order_items ทุกครั้งที่สร้าง order</li>
<li>✅ รองรับ ProductsCode ทั้งจาก Frontend และการแยกจาก ProductName</li>
<li>✅ เก็บ ProductsDetail ใน JSON format</li>
<li>✅ Transaction Safety - ถ้า order_items ไม่สำเร็จ จะ log warning แต่ไม่ rollback order</li>
</ul>