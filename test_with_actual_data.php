<?php
echo "<h2>🧪 ทดสอบด้วยข้อมูลจริงตาม Test Case</h2>";

echo "<h3>📋 Test Case เป้าหมาย</h3>";
echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
echo "<ul>";
echo "<li><strong>สินค้า:</strong> 2 รายการ</li>";
echo "<li><strong>จำนวนรวม:</strong> 2 หน่วย</li>";
echo "<li><strong>ยอดรวม:</strong> 535 บาท</li>";
echo "<li><strong>ส่วนลด:</strong> 35 บาท (6.54%)</li>";
echo "<li><strong>ยอดสุทธิ:</strong> 500 บาท</li>";
echo "</ul>";
echo "</div>";

echo "<h3>1. JavaScript สำหรับใส่ข้อมูลทดสอบ</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console เมื่ออยู่ในหน้าสร้าง Order (Modal เปิดแล้ว)
console.log('=== SETTING TEST DATA ===');

// ใส่ข้อมูลทดสอบตาม Test Case
document.getElementById('total-quantity').value = '2';
document.getElementById('subtotal-amount').value = '535.00';
document.getElementById('discount-amount').value = '35.00';
document.getElementById('discount-percent').value = '6.54';
document.getElementById('total-amount').value = '500.00';

// Trigger events เพื่อให้ระบบรู้ว่ามีการเปลี่ยนแปลง
const fields = ['total-quantity', 'subtotal-amount', 'discount-amount', 'discount-percent', 'total-amount'];
fields.forEach(fieldId => {
    const element = document.getElementById(fieldId);
    if (element) {
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }
});

console.log('=== TEST DATA SET ===');
console.log('Total Quantity:', document.getElementById('total-quantity').value);
console.log('Subtotal Amount:', document.getElementById('subtotal-amount').value);
console.log('Discount Amount:', document.getElementById('discount-amount').value);
console.log('Discount Percent:', document.getElementById('discount-percent').value);
console.log('Total Amount:', document.getElementById('total-amount').value);";
echo "</pre>";
echo "</div>";

echo "<h3>2. Override submitOrder Function</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// Override submitOrder เพื่อดูข้อมูลที่ส่งไป
const originalSubmitOrder = window.customerDetail?.submitOrder;
if (originalSubmitOrder) {
    window.customerDetail.submitOrder = function() {
        console.log('=== INTERCEPTED ORDER SUBMISSION ===');
        
        const data = {
            total_quantity: document.getElementById('total-quantity')?.value,
            subtotal_amount: document.getElementById('subtotal-amount')?.value,
            discount_amount: document.getElementById('discount-amount')?.value,
            discount_percent: document.getElementById('discount-percent')?.value,
            total_amount: document.getElementById('total-amount')?.value
        };
        
        console.log('📤 Data being sent:', data);
        
        // ตรวจสอบว่าข้อมูลตรงกับ Test Case ไหม
        const expectedData = {
            total_quantity: '2',
            subtotal_amount: '535.00',
            discount_amount: '35.00',
            discount_percent: '6.54',
            total_amount: '500.00'
        };
        
        console.log('🎯 Expected data:', expectedData);
        
        let isCorrect = true;
        Object.keys(expectedData).forEach(key => {
            const actual = parseFloat(data[key] || 0);
            const expected = parseFloat(expectedData[key] || 0);
            const match = Math.abs(actual - expected) < 0.01;
            
            console.log(`${match ? '✅' : '❌'} ${key}: ${actual} ${match ? '==' : '!='} ${expected}`);
            if (!match) isCorrect = false;
        });
        
        console.log(isCorrect ? '🎉 DATA MATCHES TEST CASE!' : '🚨 DATA DOES NOT MATCH!');
        
        // เรียก original function
        return originalSubmitOrder.call(this);
    };
    console.log('✅ submitOrder function overridden successfully');
} else {
    console.log('❌ customerDetail.submitOrder not found');
}";
echo "</pre>";
echo "</div>";

echo "<h3>3. Monitor Network Request</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>📡 ขั้นตอนการตรวจสอบ Network:</h4>";
echo "<ol>";
echo "<li><strong>เปิด Network Tab</strong> ใน DevTools (F12)</li>";
echo "<li><strong>กรอง</strong> ด้วย <code>create.php</code></li>";
echo "<li><strong>รัน JavaScript</strong> ข้างบนเพื่อใส่ข้อมูลทดสอบ</li>";
echo "<li><strong>กด Submit Order</strong></li>";
echo "<li><strong>คลิกที่ Request</strong> ใน Network Tab</li>";
echo "<li><strong>ดู Request Payload</strong> ว่าส่งข้อมูลอะไรไป</li>";
echo "<li><strong>ดู Response</strong> ว่า API ตอบอะไรกลับ</li>";
echo "</ol>";
echo "</div>";

echo "<h3>4. ตรวจสอบผลใน Database</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>💾 หลังจาก Submit แล้ว:</h4>";
echo "<ol>";
echo "<li><strong>เปิดไฟล์:</strong> <code>trace_order_creation.php</code></li>";
echo "<li><strong>ดู Expected vs Actual</strong> ใน Table</li>";
echo "<li><strong>ถ้าค่าไม่ตรง</strong> → มีปัญหาใน API หรือ Database</li>";
echo "<li><strong>ถ้าค่าตรง</strong> → ปัญหาอยู่ที่อื่น</li>";
echo "</ol>";
echo "</div>";

echo "<h3>5. All-in-One Test Script</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// All-in-One: ใส่ข้อมูล + Override function + พร้อม Submit
console.log('🚀 STARTING COMPLETE TEST');

// Step 1: Set test data
document.getElementById('total-quantity').value = '2';
document.getElementById('subtotal-amount').value = '535.00';
document.getElementById('discount-amount').value = '35.00';
document.getElementById('discount-percent').value = '6.54';
document.getElementById('total-amount').value = '500.00';

// Step 2: Trigger events
['total-quantity', 'subtotal-amount', 'discount-amount', 'discount-percent', 'total-amount'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
});

// Step 3: Override submitOrder
const orig = window.customerDetail?.submitOrder;
if (orig) {
    window.customerDetail.submitOrder = function() {
        console.log('📤 SUBMITTING:', {
            qty: document.getElementById('total-quantity').value,
            subtotal: document.getElementById('subtotal-amount').value,
            discount: document.getElementById('discount-amount').value,
            percent: document.getElementById('discount-percent').value,
            total: document.getElementById('total-amount').value
        });
        return orig.call(this);
    };
}

console.log('✅ TEST SETUP COMPLETE - Ready to submit!');";
echo "</pre>";
echo "</div>";
?>

<style>
pre {
    font-size: 12px;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>