<?php
/**
 * Test Order Form Improvements
 * ทดสอบการปรับปรุงฟอร์มสร้างคำสั่งซื้อ
 */

header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Order Form Improvements</title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .warning { background-color: #fff3cd; border-color: #ffeaa7; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .feature-list {
            list-style-type: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:before {
            content: "✅ ";
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Order Form Improvements</h1>
        <p><strong>วันที่ทดสอบ:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <div class="test-section success">
            <h3>✅ การปรับปรุงที่ทำเสร็จแล้ว</h3>
            <ul class="feature-list">
                <li><strong>ป้องกันการ Submit ด้วย Enter:</strong> เพิ่ม <code>onkeydown="return preventEnterSubmit(event)"</code> ใน form</li>
                <li><strong>Payment Method เป็น Required:</strong> เพิ่ม <code>required</code> attribute และ <code>*</code> ใน label</li>
                <li><strong>เพิ่มตัวเลือก "เก็บเงินปลายทาง":</strong> เพิ่มใน dropdown Payment Method</li>
                <li><strong>JavaScript Validation:</strong> ตรวจสอบ Payment Method ก่อน submit</li>
                <li><strong>Reset Button Function:</strong> สร้าง <code>resetOrderSubmitButton()</code> function</li>
            </ul>
        </div>
        
        <div class="test-section info">
            <h3>🧪 วิธีทดสอบ</h3>
            <ol>
                <li><strong>ทดสอบ Enter Key Prevention:</strong>
                    <ul>
                        <li>ไปที่หน้า Customer Detail</li>
                        <li>เปิดฟอร์มสร้างคำสั่งซื้อ</li>
                        <li>กรอกข้อมูลในช่องต่างๆ และกด Enter</li>
                        <li><strong>คาดหวัง:</strong> ฟอร์มไม่ควร submit อัตโนมัติ</li>
                    </ul>
                </li>
                
                <li><strong>ทดสอบ Payment Method Required:</strong>
                    <ul>
                        <li>กรอกข้อมูลสินค้าครบถ้วน</li>
                        <li>ไม่เลือก Payment Method (ปล่อยว่าง)</li>
                        <li>กดปุ่ม "บันทึก"</li>
                        <li><strong>คาดหวัง:</strong> แสดง error "กรุณาเลือกวิธีการชำระเงิน"</li>
                    </ul>
                </li>
                
                <li><strong>ทดสอบ "เก็บเงินปลายทาง" Option:</strong>
                    <ul>
                        <li>เปิด dropdown Payment Method</li>
                        <li><strong>คาดหวัง:</strong> เห็นตัวเลือก "เก็บเงินปลายทาง"</li>
                        <li>เลือกและบันทึก order</li>
                        <li>ตรวจสอบใน database ว่า PaymentMethod = "เก็บเงินปลายทาง"</li>
                    </ul>
                </li>
            </ol>
        </div>
        
        <div class="test-section warning">
            <h3>⚠️ จุดที่ต้องระวัง</h3>
            <ul>
                <li><strong>Enter Key Prevention:</strong> ใช้งานได้เฉพาะใน input fields ปกติ ไม่รวม textarea</li>
                <li><strong>Required Validation:</strong> ทำงานทั้ง HTML5 validation และ JavaScript validation</li>
                <li><strong>Button State:</strong> ถ้าเกิด error ปุ่มจะ reset กลับสู่สถานะปกติ</li>
            </ul>
        </div>
        
        <div class="test-section info">
            <h3>🔧 รายละเอียดการแก้ไข</h3>
            
            <h4>1. ไฟล์ที่แก้ไข:</h4>
            <ul>
                <li><code>pages/customer_detail.php</code>:
                    <ul>
                        <li>เพิ่ม <code>onkeydown="return preventEnterSubmit(event)"</code> ใน form</li>
                        <li>เพิ่ม <code>required</code> ใน payment method select</li>
                        <li>เพิ่ม <code>*</code> ใน label Payment Method</li>
                        <li>เพิ่มตัวเลือก "เก็บเงินปลายทาง"</li>
                    </ul>
                </li>
                <li><code>assets/js/customer-detail.js</code>:
                    <ul>
                        <li>เพิ่ม <code>preventEnterSubmit()</code> function</li>
                        <li>เพิ่ม payment method validation ใน <code>submitOrder()</code></li>
                        <li>เพิ่ม <code>resetOrderSubmitButton()</code> function</li>
                    </ul>
                </li>
            </ul>
            
            <h4>2. Payment Method Options ใหม่:</h4>
            <ul>
                <li>เงินสด</li>
                <li>โอนเงิน</li>
                <li>เช็ค</li>
                <li>บัตรเครดิต</li>
                <li><strong>เก็บเงินปลายทาง</strong> (ใหม่)</li>
            </ul>
        </div>
        
        <div class="test-section success">
            <h3>📊 คาดหวังผลลัพธ์</h3>
            
            <h4>✅ พฤติกรรมที่ถูกต้อง:</h4>
            <ul>
                <li>กด Enter ในช่องกรอกข้อมูล → ไม่ submit form</li>
                <li>ไม่เลือก Payment Method → แสดง error message</li>
                <li>เลือก "เก็บเงินปลายทาง" → บันทึกได้ปกติ</li>
                <li>กด Submit หลายครั้ง → ป้องกัน double submission</li>
            </ul>
            
            <h4>🚨 พฤติกรรมที่ควรหลีกเลี่ยง:</h4>
            <ul>
                <li>กด Enter แล้ว submit ทันที</li>
                <li>บันทึก order โดยไม่มี Payment Method</li>
                <li>ปุ่มค้างใน loading state หลัง error</li>
            </ul>
        </div>
        
        <div class="test-section info">
            <h3>🛠️ Debug Commands</h3>
            
            <h4>ตรวจสอบ Payment Method ใน Database:</h4>
            <code>
                SELECT DocumentNo, CustomerCode, PaymentMethod, CreatedDate<br>
                FROM orders<br>
                WHERE PaymentMethod = 'เก็บเงินปลายทาง'<br>
                ORDER BY CreatedDate DESC<br>
                LIMIT 5;
            </code>
            
            <h4>ตรวจสอบ Orders ที่ไม่มี Payment Method:</h4>
            <code>
                SELECT DocumentNo, CustomerCode, PaymentMethod, CreatedDate<br>
                FROM orders<br>
                WHERE PaymentMethod IS NULL OR PaymentMethod = ''<br>
                ORDER BY CreatedDate DESC<br>
                LIMIT 10;
            </code>
        </div>
        
        <hr>
        <p><strong>📝 หมายเหตุ:</strong> หลังจากทดสอบแล้ว ให้ลองสร้าง order จริงเพื่อยืนยันว่าทุกอย่างทำงานถูกต้อง</p>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="../pages/customer_detail.php?code=CUST001" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                🧪 ทดสอบกับลูกค้า CUST001
            </a>
        </div>
    </div>
</body>
</html>