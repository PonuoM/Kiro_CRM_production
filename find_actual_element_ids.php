<?php
echo "<h2>🔍 หา Element ID ที่แท้จริงในหน้าสร้าง Order</h2>";

echo "<h3>1. JavaScript สำหรับหา Element ทั้งหมด</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console เมื่ออยู่ในหน้า Customer Detail (หลังเปิด Modal แล้ว)
console.log('=== SCANNING ALL FORM ELEMENTS ===');

// หา input ทั้งหมดที่เกี่ยวกับจำนวนและเงิน
const allInputs = document.querySelectorAll('input');
const potentialElements = [];

allInputs.forEach((input, index) => {
    const id = input.id;
    const name = input.name;
    const type = input.type;
    const value = input.value;
    const placeholder = input.placeholder;
    
    // กรองเฉพาะ input ที่น่าจะเกี่ยวกับ order
    if (id || name || placeholder) {
        const elementInfo = `[\${index}] ID: \${id || 'none'} | Name: \${name || 'none'} | Type: \${type} | Value: \${value} | Placeholder: \${placeholder || 'none'}`;
        
        // ตรวจสอบคำสำคัญที่เกี่ยวกับการสั่งซื้อ
        const orderRelated = /quantity|amount|total|discount|percent|price|order/i;
        if (orderRelated.test(id + name + placeholder)) {
            console.log('🎯 ORDER RELATED:', elementInfo);
            potentialElements.push(input);
        } else {
            console.log('📋 OTHER:', elementInfo);
        }
    }
});

console.log('=== POTENTIAL ORDER ELEMENTS ===');
potentialElements.forEach((el, i) => {
    console.log(`[\${i}] ID: \${el.id} | Value: \${el.value}`);
});

// หา Modal/Popup element
console.log('=== LOOKING FOR MODAL/POPUP ===');
const modals = document.querySelectorAll('.modal, .popup, [class*=\"modal\"], [class*=\"popup\"], [class*=\"order\"]');
modals.forEach((modal, i) => {
    console.log(`Modal [\${i}]:`, modal.className, modal.id);
    const inputs = modal.querySelectorAll('input');
    inputs.forEach(input => {
        console.log(`  Input: ID=\${input.id} Name=\${input.name} Type=\${input.type}`);
    });
});";
echo "</pre>";
echo "</div>";

echo "<h3>2. ทางเลือกในการเข้าถึง Element</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>วิธีหา Element ที่ถูกต้อง:</h4>";
echo "<ol>";
echo "<li><strong>เปิด Modal สร้าง Order ก่อน</strong> แล้วค่อยรัน JavaScript</li>";
echo "<li><strong>ใช้ querySelector</strong> แทน getElementById หาก ID ไม่ตรง</li>";
echo "<li><strong>หา Element จาก text/placeholder</strong> หาก ID ไม่มี</li>";
echo "<li><strong>ใช้ name attribute</strong> แทน ID</li>";
echo "</ol>";
echo "</div>";

echo "<h3>3. JavaScript แบบ Flexible</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// JavaScript ที่ยืดหยุ่นกว่า - หา Element หลายวิธี
function findOrderElements() {
    console.log('=== FLEXIBLE ELEMENT FINDER ===');
    
    // วิธีที่ 1: ตาม ID เดิม
    const elements = {
        totalQuantity: document.getElementById('total-quantity') || 
                      document.querySelector('[name=\"total-quantity\"]') ||
                      document.querySelector('input[placeholder*=\"จำนวน\"]') ||
                      document.querySelector('input[placeholder*=\"quantity\"]'),
                      
        subtotalAmount: document.getElementById('subtotal-amount') || 
                       document.querySelector('[name=\"subtotal-amount\"]') ||
                       document.querySelector('input[placeholder*=\"ยอดรวม\"]') ||
                       document.querySelector('input[placeholder*=\"subtotal\"]'),
                       
        discountAmount: document.getElementById('discount-amount') || 
                       document.querySelector('[name=\"discount-amount\"]') ||
                       document.querySelector('input[placeholder*=\"ส่วนลด\"]') ||
                       document.querySelector('input[placeholder*=\"discount\"]'),
                       
        discountPercent: document.getElementById('discount-percent') || 
                        document.querySelector('[name=\"discount-percent\"]') ||
                        document.querySelector('input[placeholder*=\"เปอร์เซ็นต์\"]') ||
                        document.querySelector('input[placeholder*=\"percent\"]'),
                        
        totalAmount: document.getElementById('total-amount') || 
                    document.querySelector('[name=\"total-amount\"]') ||
                    document.querySelector('input[placeholder*=\"ยอดสุทธิ\"]') ||
                    document.querySelector('input[placeholder*=\"total\"]')
    };
    
    console.log('Found elements:');
    Object.keys(elements).forEach(key => {
        const element = elements[key];
        if (element) {
            console.log(`✅ \${key}:`, element.id || element.name || 'no-id', '= \${element.value}');
        } else {
            console.log(`❌ \${key}: NOT FOUND`);
        }
    });
    
    return elements;
}

// เรียกใช้ฟังก์ชัน
const orderElements = findOrderElements();";
echo "</pre>";
echo "</div>";

echo "<h3>4. ขั้นตอนการ Debug ที่ถูกต้อง</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>📋 ลำดับขั้นตอน:</h4>";
echo "<ol>";
echo "<li><strong>เข้าหน้า Customer Detail</strong> ด้วย URL</li>";
echo "<li><strong>คลิกปุ่ม \"สร้างคำสั่งซื้อ\"</strong> เพื่อเปิด Modal</li>";
echo "<li><strong>รอให้ Modal เปิดเสร็จ</strong></li>";
echo "<li><strong>เปิด Console (F12)</strong></li>";
echo "<li><strong>วาง JavaScript code</strong> ที่ให้ไปข้างบน</li>";
echo "<li><strong>กรอกข้อมูลทดสอบ</strong> (สินค้า, จำนวน, ราคา)</li>";
echo "<li><strong>ดูค่าใน Console</strong> ว่าถูกต้องไหม</li>";
echo "<li><strong>กด Submit</strong> และดู Network Tab</li>";
echo "</ol>";
echo "</div>";

echo "<h3>5. หาก Element ยังไม่เจอ</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
echo "<h4>🚨 แนวทางการแก้ไข:</h4>";
echo "<ul>";
echo "<li>ให้ส่ง <strong>Screenshot หน้า Modal</strong> มาให้ดู</li>";
echo "<li>ใช้คำสั่ง <code>document.body.innerHTML</code> ดู HTML ทั้งหมด</li>";
echo "<li>ตรวจสอบว่า Modal ใช้ <strong>iframe</strong> หรือไม่</li>";
echo "<li>อาจต้องแก้ไข <strong>customer-detail.js</strong> เพื่อเปลี่ยน Element ID</li>";
echo "</ul>";
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