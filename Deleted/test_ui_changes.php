<?php
/**
 * Test UI Changes - Check if the new layout works
 */

echo "<h1>🧪 Test UI Changes - Admin Layout System</h1>";

// Test 1: Check if admin_layout.php exists
echo "<h2>📋 Test 1: ตรวจสอบไฟล์ admin_layout.php</h2>";
$layoutFile = '/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/admin_layout.php';
if (file_exists($layoutFile)) {
    echo "✅ admin_layout.php มีอยู่จริง<br>";
    echo "📁 Path: $layoutFile<br>";
} else {
    echo "❌ admin_layout.php ไม่พบ<br>";
}

// Test 2: Check admin files in /pages/admin/
echo "<h2>📋 Test 2: ตรวจสอบไฟล์ Admin ใน /pages/admin/</h2>";
$adminFiles = [
    'supervisor_dashboard.php' => 'แดชบอร์ดผู้ดูแล',
    'intelligence_system.php' => 'ระบบวิเคราะห์ลูกค้า', 
    'distribution_basket.php' => 'ตะกร้าแจกลูกค้า',
    'waiting_basket.php' => 'ตะกร้ารอ'
];

foreach ($adminFiles as $file => $description) {
    $filePath = "/mnt/c/xampp/htdocs/Kiro_CRM_production/pages/admin/$file";
    if (file_exists($filePath)) {
        // Check if file uses new layout
        $content = file_get_contents($filePath);
        if (strpos($content, 'admin_layout.php') !== false && 
            strpos($content, 'renderAdminLayout') !== false) {
            echo "✅ $file - $description ใช้ Layout ใหม่แล้ว<br>";
        } else {
            echo "⚠️ $file - $description ยังไม่ใช้ Layout ใหม่<br>";
        }
    } else {
        echo "❌ $file - ไม่พบไฟล์<br>";
    }
}

// Test 3: Check CSS Variables
echo "<h2>📋 Test 3: ตรวจสอบ CSS Variables</h2>";
$layoutContent = file_get_contents($layoutFile);
$cssVariables = [
    '--background: #ffffff' => 'สีพื้นหลังขาว',
    '--foreground: #0f172a' => 'สีข้อความดำ', 
    '--primary: #76BC43' => 'สีหลักเขียว',
    '--sidebar-width: 280px' => 'ความกว้าง Sidebar'
];

foreach ($cssVariables as $variable => $description) {
    if (strpos($layoutContent, $variable) !== false) {
        echo "✅ $description พบแล้ว<br>";
    } else {
        echo "❌ $description ไม่พบ<br>";
    }
}

// Test 4: Check Noto Sans Thai Font
echo "<h2>📋 Test 4: ตรวจสอบฟอนต์ Noto Sans Thai</h2>";
if (strpos($layoutContent, 'Noto Sans Thai') !== false) {
    echo "✅ ฟอนต์ Noto Sans Thai พบแล้ว<br>";
} else {
    echo "❌ ฟอนต์ Noto Sans Thai ไม่พบ<br>";
}

// Test 5: API Paths
echo "<h2>📋 Test 5: ตรวจสอบ API Paths</h2>";
$testFiles = ['supervisor_dashboard.php', 'intelligence_system.php'];
foreach ($testFiles as $file) {
    $filePath = "/mnt/c/xampp/htdocs/Kiro_CRM_production/pages/admin/$file";
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        // Check for correct API paths (should be ../../api/ not ../api/)
        if (strpos($content, "'../../api/") !== false) {
            echo "✅ $file ใช้ API path ที่ถูกต้อง (../../api/)<br>";
        } else if (strpos($content, "'../api/") !== false) {
            echo "⚠️ $file ใช้ API path เก่า (../api/) - ควรแก้ไข<br>";
        } else {
            echo "ℹ️ $file ไม่พบ API calls<br>";
        }
    }
}

echo "<h2>📊 สรุปผลการทดสอบ</h2>";
echo "<p><strong>📋 การทดสอบการเปลี่ยนแปลง UI เสร็จสิ้น</strong></p>";
echo "<p>ตรวจสอบรายการข้างต้นแล้ว หากทุกอย่างแสดง ✅ แสดงว่าการอัพเดตสำเร็จ</p>";
echo "<p><strong>🔗 ทดสอบการใช้งาน:</strong> เข้าไปที่หน้า Admin ต่างๆ ผ่านระบบ Login เพื่อดูการเปลี่ยนแปลง</p>";

// Test 6: Show what changed
echo "<h2>🔄 สิ่งที่เปลี่ยนแปลงไป</h2>";
echo "<ul>";
echo "<li><strong>Sidebar Navigation:</strong> เมนูแบบ Sidebar แทนการใช้ Header แบบเก่า</li>";
echo "<li><strong>สีสัน:</strong> ขาว-ดำ-เขียว (#76BC43) แทนสีเดิม</li>";
echo "<li><strong>ฟอนต์:</strong> Noto Sans Thai สำหรับการแสดงผลภาษาไทยที่ดีขึ้น</li>";
echo "<li><strong>การ์ด:</strong> Design แบบ ShadCN UI ที่ทันสมัย</li>";
echo "<li><strong>โครงสร้าง:</strong> ใช้ระบบ Layout แบบรวม แทนการเขียน HTML แยกในแต่ละไฟล์</li>";
echo "</ul>";

echo "<h3>🔧 ความแตกต่าง Supervisor vs Manager</h3>";
echo "<ul>";
echo "<li><strong>Supervisor (ผู้ดูแล):</strong> ดูแลทีมขาย แจกลูกค้า ติดตามผลงาน</li>";
echo "<li><strong>Manager (ผู้จัดการ):</strong> บริหารระบบ วิเคราะห์ข้อมูล จัดการผู้ใช้งาน</li>";
echo "</ul>";
?>