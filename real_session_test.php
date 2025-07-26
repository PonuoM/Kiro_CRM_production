<?php
/**
 * Real Session Test - ใช้ session จริงของผู้ใช้
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🧪 Real Session Test</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .pass { background: #d4edda; border-color: #c3e6cb; }
        .fail { background: #f8d7da; border-color: #f5c6cb; }
        .warn { background: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
<h1>🧪 Real Session Test (ไม่สร้าง test session)</h1>

<?php
// ไม่ต้องสร้าง test session - ใช้ session จริงที่มีอยู่
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<div class='test'>";
echo "<h3>1. Session Information</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? '❌ NOT SET') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? '❌ NOT SET') . "</p>";
echo "<p><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? '❌ NOT SET') . "</p>";

if (!isset($_SESSION['user_id'])) {
    echo "<div class='fail'>";
    echo "<h4>❌ ไม่มี session - กรุณาล็อกอินก่อน</h4>";
    echo "<p><a href='login.php'>ไปหน้าล็อกอิน</a></p>";
    echo "</div>";
    echo "</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// ทดสอบโหลด permissions ด้วย session จริง
echo "<div class='test'>";
echo "<h3>2. Permissions Test (Real Session)</h3>";
try {
    require_once 'includes/permissions.php';
    echo "<p class='pass'>✅ Permissions loaded</p>";
    
    $currentUser = Permissions::getCurrentUser();
    $currentRole = Permissions::getCurrentRole();
    $canViewAll = Permissions::canViewAllData();
    $hasCustomerList = Permissions::hasPermission('customer_list');
    
    echo "<p><strong>Current User:</strong> {$currentUser}</p>";
    echo "<p><strong>Current Role:</strong> {$currentRole}</p>";
    echo "<p><strong>Can View All Data:</strong> " . ($canViewAll ? 'YES' : 'NO') . "</p>";
    echo "<p><strong>Has customer_list permission:</strong> " . ($hasCustomerList ? 'YES' : 'NO') . "</p>";
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ Permissions error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// ทดสอบโหลด main layout
echo "<div class='test'>";
echo "<h3>3. Main Layout Test</h3>";
try {
    require_once 'includes/main_layout.php';
    echo "<p class='pass'>✅ Main layout loaded</p>";
    
    if (function_exists('renderMainLayout')) {
        echo "<p class='pass'>✅ renderMainLayout() function available</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Main layout error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// ทดสอบสร้างหน้าแบบง่ายๆ
echo "<div class='test'>";
echo "<h3>4. Simple Page Test</h3>";
try {
    // เตรียมข้อมูลแบบเดียวกับ customer_list_demo.php
    $user_name = Permissions::getCurrentUser();
    $user_role = Permissions::getCurrentRole(); 
    $menuItems = Permissions::getMenuItems();
    
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = $menuItems;
    
    echo "<p class='pass'>✅ Global variables set successfully</p>";
    echo "<p><strong>User:</strong> {$user_name}</p>";
    echo "<p><strong>Role:</strong> {$user_role}</p>";
    echo "<p><strong>Menu Items:</strong> " . count($menuItems) . "</p>";
    
    // ทดสอบ render layout
    ob_start();
    $testContent = "<div class='alert alert-success'>หน้าทดสอบทำงานได้!</div>";
    $output = renderMainLayout("ทดสอบหน้า", $testContent);
    ob_end_clean();
    
    echo "<p class='pass'>✅ Layout rendering successful</p>";
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ Simple page test failed: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

?>

<div class="test">
<h3>📋 Test Summary</h3>
<p>✅ หากทุกอย่างเป็นสีเขียว = ระบบพร้อมใช้งาน</p>
<p>❌ หากมีข้อผิดพลาด = ปัญหาอยู่ที่จุดนั้น</p>

<h4>🔗 ทดสอบหน้าจริง:</h4>
<p><a href="pages/customer_list_demo.php" target="_blank">เปิด customer_list_demo.php</a></p>
</div>

</div>
</body>
</html>