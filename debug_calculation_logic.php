<?php
echo "<h2>🔍 Debug Calculation Logic</h2>";

echo "<h3>1. ตัวอย่างการคำนวณที่ควรเป็น</h3>";
echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
echo "<h4>📊 ตัวอย่าง: สินค้า 2 รายการ</h4>";
echo "<ul>";
echo "<li><strong>สินค้า A:</strong> 1 หน่วย × 300 บาท = 300 บาท</li>";
echo "<li><strong>สินค้า B:</strong> 1 หน่วย × 235 บาท = 235 บาท</li>";
echo "<li><strong>รวม (Subtotal):</strong> 300 + 235 = <strong>535 บาท</strong></li>";
echo "<li><strong>ส่วนลด:</strong> 35 บาท (6.54%)</li>";
echo "<li><strong>ยอดสุทธิ:</strong> 535 - 35 = <strong>500 บาท</strong></li>";
echo "</ul>";
echo "</div>";

echo "<h3>2. การ Map ค่าใน Database ที่ถูกต้อง</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Frontend Textbox</th><th>Database Column</th><th>ค่าที่ควร</th><th>ความหมาย</th>";
echo "</tr>";

$mapping = [
    ['total-quantity', 'Quantity', '2', 'จำนวนรวมทั้งหมด'],
    ['subtotal-amount', 'SubtotalAmount', '535.00', 'ยอดรวมก่อนหักส่วนลด'],
    ['discount-amount', 'DiscountAmount', '35.00', 'จำนวนเงินส่วนลด'],
    ['discount-percent', 'DiscountPercent', '6.54', 'เปอร์เซ็นต์ส่วนลด'],
    ['discount-remarks', 'DiscountRemarks', 'หมายเหตุ', 'หมายเหตุส่วนลด'],
    ['total-amount', 'Price', '500.00', 'ยอดสุทธิหลังหักส่วนลด']
];

foreach ($mapping as $row) {
    echo "<tr>";
    echo "<td><code>#{$row[0]}</code></td>";
    echo "<td><strong>{$row[1]}</strong></td>";
    echo "<td>{$row[2]}</td>";
    echo "<td>{$row[3]}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>3. ตรวจสอบ JavaScript Logic</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔍 จุดที่ต้องตรวจสอบ:</h4>";
echo "<ol>";
echo "<li><strong>calculateOrderTotals():</strong> รวมยอดสินค้าถูกต้องไหม?</li>";
echo "<li><strong>calculateDiscountFromPercent():</strong> คำนวณส่วนลดจาก % ถูกไหม?</li>";
echo "<li><strong>calculateFinalTotal():</strong> คำนวณยอดสุทธิถูกไหม?</li>";
echo "<li><strong>submitOrder():</strong> ส่งข้อมูลไป API ถูกต้องไหม?</li>";
echo "</ol>";
echo "</div>";

echo "<h3>4. การทดสอบแบบ Step-by-Step</h3>";
?>
<script>
console.log("=== CALCULATION LOGIC DEBUG ===");

// ตัวอย่างการคำนวณ
console.log("1. Product Calculation:");
const product1_qty = 1;
const product1_price = 300;
const product1_total = product1_qty * product1_price;
console.log(`   Product 1: ${product1_qty} × ${product1_price} = ${product1_total}`);

const product2_qty = 1;
const product2_price = 235;
const product2_total = product2_qty * product2_price;
console.log(`   Product 2: ${product2_qty} × ${product2_price} = ${product2_total}`);

console.log("2. Order Totals:");
const total_quantity = product1_qty + product2_qty;
const subtotal_amount = product1_total + product2_total;
console.log(`   Total Quantity: ${total_quantity}`);
console.log(`   Subtotal Amount: ${subtotal_amount}`);

console.log("3. Discount Calculation:");
const discount_amount = 35;
const discount_percent = (discount_amount / subtotal_amount) * 100;
console.log(`   Discount Amount: ${discount_amount}`);
console.log(`   Discount Percent: ${discount_percent.toFixed(2)}%`);

console.log("4. Final Total:");
const final_total = subtotal_amount - discount_amount;
console.log(`   Final Total: ${subtotal_amount} - ${discount_amount} = ${final_total}`);

console.log("5. Expected Database Values:");
console.log(`   Quantity: ${total_quantity}`);
console.log(`   SubtotalAmount: ${subtotal_amount}`);
console.log(`   DiscountAmount: ${discount_amount}`);
console.log(`   DiscountPercent: ${discount_percent.toFixed(2)}`);
console.log(`   Price: ${final_total}`);

// ตรวจสอบฟังก์ชันปัจจุบัน
console.log("6. Current JavaScript Functions Check:");
if (typeof calculateOrderTotals === 'function') {
    console.log("   ✅ calculateOrderTotals() exists");
} else {
    console.log("   ❌ calculateOrderTotals() NOT found");
}

if (typeof calculateDiscountFromPercent === 'function') {
    console.log("   ✅ calculateDiscountFromPercent() exists");
} else {
    console.log("   ❌ calculateDiscountFromPercent() NOT found");
}

if (typeof calculateFinalTotal === 'function') {
    console.log("   ✅ calculateFinalTotal() exists");
} else {
    console.log("   ❌ calculateFinalTotal() NOT found");
}
</script>

<?php
echo "<h3>5. สาเหตุที่เป็นไปได้</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
echo "<h4>🚨 ปัญหาที่เป็นไปได้:</h4>";
echo "<ol>";
echo "<li><strong>JavaScript ทำงานไม่ถูกต้อง:</strong> ฟังก์ชันคำนวณมีบัค</li>";
echo "<li><strong>Event Listeners ไม่ทำงาน:</strong> การคำนวณไม่ trigger</li>";
echo "<li><strong>Form Submission ผิด:</strong> ส่งค่าผิด field</li>";
echo "<li><strong>API Mapping ผิด:</strong> รับค่าจาก field ผิด</li>";
echo "<li><strong>Database Structure:</strong> field type หรือ constraint ผิด</li>";
echo "</ol>";
echo "</div>";

echo "<h3>6. วิธีการ Debug</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>🛠️ ขั้นตอนการ Debug:</h4>";
echo "<ol>";
echo "<li><strong>เปิด Developer Console</strong> (F12)</li>";
echo "<li><strong>ไปที่ Tab Console</strong> ดู log ข้างบน</li>";
echo "<li><strong>ลองสร้าง Order</strong> แล้วดู Network Tab</li>";
echo "<li><strong>ตรวจสอบ Request Payload</strong> ที่ส่งไป API</li>";
echo "<li><strong>ดู Response</strong> จาก API</li>";
echo "<li><strong>ตรวจสอบค่าใน Database</strong></li>";
echo "</ol>";
echo "</div>";

echo "<h3>7. Test Form (สำหรับทดสอบ)</h3>";
?>
<form id="test-calculation-form" style="background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6;">
    <h4>ทดสอบการคำนวณ</h4>
    
    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
        <div>
            <label>สินค้า 1 - จำนวน:</label>
            <input type="number" id="test-qty1" value="1" min="1" onchange="testCalculation()">
        </div>
        <div>
            <label>ราคา:</label>
            <input type="number" id="test-price1" value="300" step="0.01" onchange="testCalculation()">
        </div>
    </div>
    
    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
        <div>
            <label>สินค้า 2 - จำนวน:</label>
            <input type="number" id="test-qty2" value="1" min="1" onchange="testCalculation()">
        </div>
        <div>
            <label>ราคา:</label>
            <input type="number" id="test-price2" value="235" step="0.01" onchange="testCalculation()">
        </div>
    </div>
    
    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
        <div>
            <label>ส่วนลด (บาท):</label>
            <input type="number" id="test-discount" value="35" step="0.01" onchange="testCalculation()">
        </div>
    </div>
    
    <div style="background: white; padding: 15px; border: 1px solid #ccc;">
        <h5>ผลลัพธ์:</h5>
        <div>จำนวนรวม: <span id="result-qty">-</span></div>
        <div>ยอดรวม: <span id="result-subtotal">-</span> บาท</div>
        <div>ส่วนลด: <span id="result-discount">-</span> บาท (<span id="result-percent">-</span>%)</div>
        <div><strong>ยอดสุทธิ: <span id="result-total">-</span> บาท</strong></div>
    </div>
</form>

<script>
function testCalculation() {
    const qty1 = parseFloat(document.getElementById('test-qty1').value || 0);
    const price1 = parseFloat(document.getElementById('test-price1').value || 0);
    const qty2 = parseFloat(document.getElementById('test-qty2').value || 0);
    const price2 = parseFloat(document.getElementById('test-price2').value || 0);
    const discount = parseFloat(document.getElementById('test-discount').value || 0);
    
    const totalQty = qty1 + qty2;
    const subtotal = (qty1 * price1) + (qty2 * price2);
    const discountPercent = subtotal > 0 ? (discount / subtotal) * 100 : 0;
    const finalTotal = Math.max(0, subtotal - discount);
    
    document.getElementById('result-qty').textContent = totalQty;
    document.getElementById('result-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('result-discount').textContent = discount.toFixed(2);
    document.getElementById('result-percent').textContent = discountPercent.toFixed(2);
    document.getElementById('result-total').textContent = finalTotal.toFixed(2);
    
    console.log("=== TEST CALCULATION ===");
    console.log("Input:", {qty1, price1, qty2, price2, discount});
    console.log("Output:", {totalQty, subtotal, discount, discountPercent, finalTotal});
}

// Run initial calculation
testCalculation();
</script>

**เปิดไฟล์นี้ใน Browser:** `http://localhost/Kiro_CRM_production/debug_calculation_logic.php`

**แล้วดู Console (F12) และทดสอบการคำนวณ!** 🔍