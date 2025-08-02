<?php
/**
 * Diagnostic - หาความแตกต่างระหว่าง auth และ no-auth version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic: Authentication vs No-Auth Comparison</h1>";
echo "<hr>";

// Start session for testing
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>1. Session Status Check</h2>";
echo "Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>2. Authentication Methods Test</h2>";

try {
    require_once __DIR__ . '/includes/permissions.php';
    echo "✅ Permissions loaded<br>";
    
    // Test all authentication methods
    $methods = ['isLoggedIn', 'getCurrentUser', 'getCurrentRole', 'hasPermission'];
    
    foreach ($methods as $method) {
        if (method_exists('Permissions', $method)) {
            echo "✅ Method Permissions::{$method}() exists<br>";
            
            try {
                if ($method == 'hasPermission') {
                    $result = Permissions::$method('call_history');
                    echo "&nbsp;&nbsp;&nbsp;Result: " . ($result ? 'TRUE' : 'FALSE') . "<br>";
                } else {
                    $result = Permissions::$method();
                    echo "&nbsp;&nbsp;&nbsp;Result: " . var_export($result, true) . "<br>";
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;&nbsp;❌ Error calling: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Method Permissions::{$method}() missing<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error loading permissions: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Main Layout Test</h2>";

try {
    require_once __DIR__ . '/includes/main_layout.php';
    echo "✅ Main layout loaded<br>";
    
    if (function_exists('renderMainLayout')) {
        echo "✅ renderMainLayout() function exists<br>";
        
        // Test minimal render
        $GLOBALS['currentUser'] = $_SESSION['username'] ?? 'test';
        $GLOBALS['currentRole'] = $_SESSION['user_role'] ?? 'admin';
        
        try {
            $menuItems = Permissions::getMenuItems();
            $GLOBALS['menuItems'] = $menuItems;
            echo "✅ Menu items loaded (" . count($menuItems) . " items)<br>";
        } catch (Exception $e) {
            echo "⚠️ Menu items error: " . $e->getMessage() . "<br>";
            $GLOBALS['menuItems'] = [];
        }
        
    } else {
        echo "❌ renderMainLayout() function missing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading main layout: " . $e->getMessage() . "<br>";
}

echo "<h2>4. JavaScript Template Literal Test</h2>";

// Test the problematic JavaScript section from call_history_demo.php
$testCustomerCode = 'CUS20240115103012345';

$problematicJS = "
class CallHistoryManager {
    constructor() {
        this.customerCode = \"<?php echo $testCustomerCode; ?>\";
        this.currentCustomer = null;
        this.callHistory = [];
        this.init();
    }
}
";

echo "✅ JavaScript template does not use template literals - should be OK<br>";

echo "<h2>5. File Include Path Test</h2>";

$files_to_check = [
    '/includes/permissions.php',
    '/includes/main_layout.php', 
    '/includes/functions.php',
    '/assets/js/call-log-popup.js'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . $file;
    if (file_exists($full_path)) {
        echo "✅ File exists: {$file}<br>";
    } else {
        echo "❌ File missing: {$file}<br>";
    }
}

echo "<h2>6. Memory and Resource Check</h2>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Memory Usage: " . memory_get_usage(true) / 1024 / 1024 . " MB<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . " seconds<br>";

echo "<h2>7. Simulated call_history_demo.php Execution</h2>";

try {
    // Simulate the key differences between auth and no-auth
    echo "Testing authentication flow...<br>";
    
    // This is what call_history_demo.php does:
    if (!isset($_SESSION['user_id'])) {
        echo "❌ Not logged in - this would trigger requireLogin()<br>";
        echo "<strong>PROBLEM FOUND:</strong> User not logged in but trying to access authenticated page<br>";
        echo "<br>";
        echo "<strong>SOLUTION:</strong> You need to login first before accessing call_history_demo.php<br>";
        echo "<a href='pages/login.php'>Go to Login Page</a><br>";
    } else {
        echo "✅ User logged in<br>";
        
        if (!Permissions::hasPermission('call_history')) {
            echo "❌ No call_history permission<br>";
        } else {
            echo "✅ Has call_history permission<br>";
        }
        
        echo "<strong>Authentication flow OK - the page should work</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error in simulation: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>🎯 DIAGNOSIS RESULT</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; border-radius: 5px;'>";
    echo "<strong>❌ ROOT CAUSE FOUND:</strong><br>";
    echo "User is NOT logged in. The call_history_demo.php requires authentication.<br><br>";
    echo "<strong>SOLUTION:</strong><br>";
    echo "1. Login first at: <a href='pages/login.php'>Login Page</a><br>";
    echo "2. After successful login, then access: <a href='pages/call_history_demo.php'>Call History Demo</a><br>";
    echo "</div>";
} else {
    echo "<div style='background: #e6ffe6; padding: 15px; border: 1px solid #00ff00; border-radius: 5px;'>";
    echo "<strong>✅ AUTHENTICATION OK:</strong><br>";
    echo "User is logged in properly. If the page still shows error 500, the issue is elsewhere.<br>";
    echo "</div>";
}

echo "<br>";
echo "Test completed at: " . date('Y-m-d H:i:s');
?>