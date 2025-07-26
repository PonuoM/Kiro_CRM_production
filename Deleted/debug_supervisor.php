<?php
// Debug script for supervisor_dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Debug Supervisor Dashboard</h2>";

echo "<h3>1. Session Test</h3>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "✅ Session OK: " . ($_SESSION['username'] ?? 'unknown') . " (" . ($_SESSION['user_role'] ?? 'unknown') . ")<br>";
} else {
    echo "❌ No session - redirecting to login would happen<br>";
}

echo "<h3>2. Permissions File Test</h3>";
try {
    require_once 'includes/permissions.php';
    echo "✅ Permissions.php loaded successfully<br>";
    
    if (isset($_SESSION['user_id'])) {
        echo "- Current user: " . Permissions::getCurrentUser() . "<br>";
        echo "- Current role: " . Permissions::getCurrentRole() . "<br>";
        echo "- Has supervisor_dashboard permission: " . (Permissions::hasPermission('supervisor_dashboard') ? 'Yes' : 'No') . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Permissions error: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Admin Layout Test</h3>";
try {
    // Set test GLOBALS
    $GLOBALS['currentUser'] = 'Test User';
    $GLOBALS['currentRole'] = 'supervisor';
    $GLOBALS['menuItems'] = [
        ['url' => 'dashboard.php', 'title' => 'แดชบอร์ด', 'icon' => 'fas fa-tachometer-alt'],
        ['url' => 'test.php', 'title' => 'ทดสอบ', 'icon' => 'fas fa-test']
    ];
    
    require_once 'includes/admin_layout.php';
    echo "✅ Admin layout loaded successfully<br>";
    
    // Test renderAdminLayout function exists
    if (function_exists('renderAdminLayout')) {
        echo "✅ renderAdminLayout function exists<br>";
    } else {
        echo "❌ renderAdminLayout function NOT found<br>";
    }
} catch (Exception $e) {
    echo "❌ Admin layout error: " . $e->getMessage() . "<br>";
}

echo "<h3>4. File Path Test</h3>";
$files_to_check = [
    'includes/permissions.php',
    'includes/admin_layout.php',
    'pages/admin/supervisor_dashboard.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}

echo "<p><strong>Next:</strong> เปิด https://www.prima49.com/crm_system/Kiro_CRM_production/debug_supervisor.php</p>";
?>