<?php
// Debug script สำหรับ supervisor_dashboard.php เต็มรูปแบบ
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Debug Supervisor Dashboard - Full Test</h2>";

try {
    echo "<h3>Step 1: Load permissions</h3>";
    require_once 'includes/permissions.php';
    echo "✅ Permissions loaded<br>";
    
    echo "<h3>Step 2: Check login</h3>";
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo "❌ Not logged in<br>";
        exit;
    }
    echo "✅ Logged in as: " . $_SESSION['username'] . "<br>";
    
    echo "<h3>Step 3: Check permissions</h3>";
    if (!Permissions::hasPermission('supervisor_dashboard')) {
        echo "❌ No supervisor_dashboard permission<br>";
        exit;
    }
    echo "✅ Has supervisor_dashboard permission<br>";
    
    echo "<h3>Step 4: Get user info</h3>";
    $user_name = Permissions::getCurrentUser();
    $user_role = Permissions::getCurrentRole();
    $menuItems = Permissions::getMenuItems();
    
    echo "- User: $user_name<br>";
    echo "- Role: $user_role<br>";
    echo "- Menu items: " . count($menuItems) . " items<br>";
    
    echo "<h3>Step 5: Set GLOBALS</h3>";
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = $menuItems;
    echo "✅ GLOBALS set<br>";
    
    echo "<h3>Step 6: Load admin layout</h3>";
    require_once 'includes/admin_layout.php';
    echo "✅ Admin layout loaded<br>";
    
    echo "<h3>Step 7: Test renderAdminLayout function</h3>";
    $pageTitle = "แดชบอร์ดผู้ดูแล (ทดสอบ)";
    $content = "<div class='alert alert-success'>✅ Content rendered successfully!</div>";
    $additionalCSS = "";
    $additionalJS = "";
    
    echo "✅ About to call renderAdminLayout...<br>";
    
    // This is where the error might happen
    echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
    
} catch (Exception $e) {
    echo "<div style='color: red; border: 2px solid red; padding: 10px; margin: 10px;'>";
    echo "<h3>❌ ERROR FOUND:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
} catch (Error $e) {
    echo "<div style='color: red; border: 2px solid red; padding: 10px; margin: 10px;'>";
    echo "<h3>❌ FATAL ERROR FOUND:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}
?>