<?php
echo "<h2>🧪 ทดสอบการแก้ไข Order.php</h2>";

echo "<h3>1. JavaScript สำหรับทดสอบ</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console หลังเปิด Modal สร้าง Order
console.log('🧪 TESTING ORDER.PHP FIX');

// สร้างข้อมูลทดสอบแบบใหม่
document.getElementById('total-quantity').value = '2';
document.getElementById('subtotal-amount').value = '260.00';
document.getElementById('discount-amount').value = '30.00';
document.getElementById('discount-percent').value = '11.54';
document.getElementById('total-amount').value = '230.00';

// Trigger events
['total-quantity', 'subtotal-amount', 'discount-amount', 'discount-percent', 'total-amount'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
});

console.log('✅ Test data set - Ready to submit!');
console.log('Expected values:');
console.log('- Quantity: 2');
console.log('- SubtotalAmount: 260.00');
console.log('- DiscountAmount: 30.00');
console.log('- DiscountPercent: 11.54');
console.log('- Price: 230.00');";
echo "</pre>";
echo "</div>";

echo "<h3>2. ขั้นตอนทดสอบ</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<ol>";
echo "<li><strong>เปิด Modal สร้าง Order</strong></li>";
echo "<li><strong>วาง JavaScript</strong> ข้างบนใน Console</li>";
echo "<li><strong>กด Submit Order</strong></li>";
echo "<li><strong>เปิดไฟล์:</strong> <code>check_latest_order.php</code></li>";
echo "<li><strong>ดูผลการเปรียบเทียบ</strong></li>";
echo "</ol>";
echo "</div>";

echo "<h3>3. สิ่งที่ควรเห็นหลังแก้ไข</h3>";
echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
echo "<h4>✅ ผลที่ควรได้:</h4>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Field</th><th>Expected</th><th>Status</th>";
echo "</tr>";

$expectedResults = [
    'Quantity' => '2',
    'SubtotalAmount' => '260.00',
    'DiscountAmount' => '30.00',
    'DiscountPercent' => '11.54',
    'Price' => '230.00'
];

foreach ($expectedResults as $field => $value) {
    echo "<tr style='background: #d4edda;'>";
    echo "<td><strong>{$field}</strong></td>";
    echo "<td>{$value}</td>";
    echo "<td>✅ ตรง</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

echo "<h3>4. การตรวจสอบ Error Log</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<p>หลังจาก Submit Order แล้ว จะเห็น Log ใน Console ของ XAMPP/Apache:</p>";
echo "<pre style='background: #333; color: #fff; padding: 10px;'>";
echo "=== ORDER CREATE DEBUG ===
Input data received: {...}
=== FRONTEND DIRECT MAPPING ===
Frontend Total Quantity: 2
Frontend Subtotal Amount: 260
Frontend Discount Amount: 30
Frontend Discount Percent: 11.54
Frontend Final Total: 230
=== ORDER.PHP RECEIVED DATA ===
Quantity: 2
SubtotalAmount: 260
DiscountAmount: 30
DiscountPercent: 11.54
Price: 230";
echo "</pre>";
echo "</div>";

echo "<h3>5. Quick Check Links</h3>";
echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
echo "<p><strong>🔗 Links สำหรับทดสอบ:</strong></p>";
echo "<ul>";
echo "<li><a href='get_customer_urls.php' target='_blank'>get_customer_urls.php</a> - หา URL ลูกค้า</li>";
echo "<li><a href='check_latest_order.php' target='_blank'>check_latest_order.php</a> - ตรวจสอบ Order ล่าสุด</li>";
echo "<li><a href='trace_order_creation.php' target='_blank'>trace_order_creation.php</a> - Debug Order Creation</li>";
echo "</ul>";
echo "</div>";
?>

<style>
table { font-size: 12px; }
th, td { padding: 8px; text-align: left; }
pre { font-size: 12px; line-height: 1.4; }
</style>