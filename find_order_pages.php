<?php
echo "<h2>🔍 หาหน้าสร้าง Order ในระบบ CRM</h2>";

// 1. แสดงเส้นทางหลักของระบบ
echo "<h3>1. เส้นทางหลักของระบบ</h3>";
echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
echo "<h4>📂 โครงสร้างไฟล์:</h4>";
echo "<ul>";
echo "<li><strong>index.php</strong> → หน้าแรก/Login</li>";
echo "<li><strong>pages/dashboard.php</strong> → หน้าหลักหลัง Login</li>";
echo "<li><strong>pages/customer_list.php</strong> → รายการลูกค้าทั้งหมด</li>";
echo "<li><strong>pages/customer_detail.php</strong> → รายละเอียดลูกค้า + ฟอร์มสร้าง Order</li>";
echo "<li><strong>pages/sales_performance.php</strong> → ดูผลงานขาย</li>";
echo "</ul>";
echo "</div>";

// 2. แสดงวิธีการเข้าถึง
echo "<h3>2. วิธีการเข้าถึงหน้าสร้าง Order</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<h4>🚀 ขั้นตอนการเข้าถึง:</h4>";
echo "<ol>";
echo "<li><strong>เปิด Browser</strong> ไปที่: <code>http://localhost/Kiro_CRM_production/</code></li>";
echo "<li><strong>Login</strong> เข้าระบบ (ถ้ายังไม่ได้ Login)</li>";
echo "<li><strong>เลือก 1 ใน 2 วิธี:</strong>";
echo "<ul>";
echo "<li><strong>วิธีที่ 1:</strong> ไปหน้า Customer List → คลิกลูกค้าคนใดคนหนึ่ง</li>";
echo "<li><strong>วิธีที่ 2:</strong> เข้าตรงๆ ที่ Customer Detail</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>ในหน้า Customer Detail</strong> จะมีปุ่ม <strong>'สร้างคำสั่งซื้อ'</strong></li>";
echo "<li><strong>คลิกปุ่ม</strong> จะเปิด Modal (ป๊อปอัพ) สำหรับสร้าง Order</li>";
echo "</ol>";
echo "</div>";

// 3. แสดง URL ตรงๆ
echo "<h3>3. URL สำหรับทดสอบ</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>🔗 URL ที่สามารถใช้ทดสอบได้:</h4>";

// ตรวจสอบว่ามีลูกค้าอะไรในระบบบ้าง
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $result = $conn->query("SELECT CustomerCode, CustomerName FROM customers LIMIT 5");
    
    if ($result->rowCount() > 0) {
        echo "<p><strong>เลือกลูกค้าใดลูกค้าหนึ่งเพื่อทดสอบ:</strong></p>";
        echo "<ul>";
        while ($customer = $result->fetch(PDO::FETCH_ASSOC)) {
            $customerCode = urlencode($customer['CustomerCode']);
            $url = "http://localhost/Kiro_CRM_production/pages/customer_detail.php?code={$customerCode}";
            echo "<li>";
            echo "<strong>{$customer['CustomerName']}</strong><br>";
            echo "<a href='{$url}' target='_blank' style='color: #007bff;'>{$url}</a>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: #dc3545;'>❌ ไม่มีลูกค้าในระบบ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ ไม่สามารถดึงข้อมูลลูกค้าได้: " . $e->getMessage() . "</p>";
}

echo "</div>";

// 4. แสดงขั้นตอนการใช้งาน
echo "<h3>4. ขั้นตอนการใช้งานหน้าสร้าง Order</h3>";
echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
echo "<h4>📋 ขั้นตอนการสร้าง Order:</h4>";
echo "<ol>";
echo "<li><strong>เข้าหน้า Customer Detail</strong> (ตาม URL ข้างบน)</li>";
echo "<li><strong>หาปุ่ม 'สร้างคำสั่งซื้อ'</strong> หรือ 'Create Order'</li>";
echo "<li><strong>คลิกปุ่ม</strong> จะเปิด Modal/Popup</li>";
echo "<li><strong>กรอกข้อมูล:</strong>";
echo "<ul>";
echo "<li>เลือกสินค้า</li>";
echo "<li>ใส่จำนวน</li>";
echo "<li>ใส่ราคา</li>";
echo "<li>ใส่ส่วนลด (ถ้ามี)</li>";
echo "</ul>";
echo "</li>";
echo "<li><strong>ดูยอดรวม</strong> ในช่อง total-quantity, subtotal-amount, discount-amount, total-amount</li>";
echo "<li><strong>เปิด Console (F12)</strong> แล้ววาง JavaScript Code ที่ให้ไป</li>";
echo "<li><strong>กด Submit</strong> และดู Console + Network Tab</li>";
echo "</ol>";
echo "</div>";

// 5. ตัวอย่าง Screenshot (อธิบายเป็นข้อความ)  
echo "<h3>5. จุดที่ต้องดูในหน้า Order Form</h3>";
echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
echo "<h4>🔍 Element ที่สำคัญ:</h4>";
echo "<ul>";
echo "<li><strong>input#total-quantity</strong> → จำนวนรวม</li>";
echo "<li><strong>input#subtotal-amount</strong> → ยอดรวมก่อนส่วนลด</li>";
echo "<li><strong>input#discount-amount</strong> → จำนวนเงินส่วนลด</li>";
echo "<li><strong>input#discount-percent</strong> → เปอร์เซ็นต์ส่วนลด</li>";
echo "<li><strong>input#total-amount</strong> → ยอดสุทธิ</li>";
echo "</ul>";
echo "</div>";

?>

<style>
ul, ol {
    line-height: 1.6;
}

li {
    margin-bottom: 8px;
}

a {
    text-decoration: none;
    font-family: monospace;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
}

code {
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>