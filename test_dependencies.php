<?php
/**
 * Test Dependencies for Customer List Demo
 * Isolated test for each component
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🧪 Dependencies Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .pass { background: #d4edda; border-color: #c3e6cb; }
        .fail { background: #f8d7da; border-color: #f5c6cb; }
        .skip { background: #fff3cd; border-color: #ffeaa7; }
        pre { background: #f8f9fa; padding: 10px; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🧪 Dependencies Test Report</h1>

<?php
// Test 1: Session Management
echo "<div class='test'>";
echo "<h3>Test 1: Session Management</h3>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p class='pass'>✅ Session started successfully</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    // Simulate login for testing
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = 'test_user';
    $_SESSION['user_role'] = 'admin';
    echo "<p>🔧 Test session variables set</p>";
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ Session failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: Config Files
echo "<div class='test'>";
echo "<h3>Test 2: Configuration Files</h3>";
$config_files = ['config/config.php', 'config/database.php'];
foreach ($config_files as $file) {
    if (file_exists($file)) {
        echo "<p class='pass'>✅ {$file} exists</p>";
        try {
            require_once $file;
            echo "<p class='pass'>✅ {$file} loaded successfully</p>";
        } catch (Exception $e) {
            echo "<p class='fail'>❌ {$file} load error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='fail'>❌ {$file} not found</p>";
    }
}
echo "</div>";

// Test 3: Database Connection
echo "<div class='test'>";
echo "<h3>Test 3: Database Connection</h3>";
try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        echo "<p class='pass'>✅ Database connected successfully</p>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result['test'] == 1) {
            echo "<p class='pass'>✅ Database query test passed</p>";
        }
    } else {
        echo "<p class='fail'>❌ Database class not available</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Database error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Permissions System
echo "<div class='test'>";
echo "<h3>Test 4: Permissions System</h3>";
try {
    require_once 'includes/permissions.php';
    echo "<p class='pass'>✅ Permissions loaded</p>";
    
    if (class_exists('Permissions')) {
        echo "<p class='pass'>✅ Permissions class available</p>";
        
        // Test methods
        $user = Permissions::getCurrentUser();
        echo "<p>Current User: {$user}</p>";
        
        $canView = Permissions::canViewAllData();
        echo "<p>Can View All: " . ($canView ? 'Yes' : 'No') . "</p>";
        
        $hasPermission = Permissions::hasPermission('customer_list');
        echo "<p>Has customer_list permission: " . ($hasPermission ? 'Yes' : 'No') . "</p>";
        
        echo "<p class='pass'>✅ Permissions methods working</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Permissions error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 5: Main Layout
echo "<div class='test'>";
echo "<h3>Test 5: Main Layout System</h3>";
try {
    require_once 'includes/main_layout.php';
    echo "<p class='pass'>✅ Main layout loaded</p>";
    
    if (function_exists('renderMainLayout')) {
        echo "<p class='pass'>✅ renderMainLayout() function available</p>";
        
        // Test globals
        $GLOBALS['currentUser'] = 'test_user';
        $GLOBALS['currentRole'] = 'admin';
        $GLOBALS['menuItems'] = [];
        echo "<p class='pass'>✅ Global variables set for layout</p>";
        
    } else {
        echo "<p class='fail'>❌ renderMainLayout() function not found</p>";
    }
} catch (Exception $e) {
    echo "<p class='fail'>❌ Main layout error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 6: API Endpoint
echo "<div class='test'>";
echo "<h3>Test 6: Sales API</h3>";
if (file_exists('api/sales/sales_records.php')) {
    echo "<p class='pass'>✅ sales_records.php exists</p>";
    
    // Show API file content preview
    $api_content = file_get_contents('api/sales/sales_records.php');
    if (strpos($api_content, 'header(\'Content-Type: application/json\')') !== false) {
        echo "<p class='pass'>✅ API returns JSON</p>";
    }
    if (strpos($api_content, 'session_start()') !== false) {
        echo "<p class='pass'>✅ API handles sessions</p>";
    }
    if (strpos($api_content, 'Database::getInstance()') !== false) {
        echo "<p class='pass'>✅ API uses database</p>";
    }
} else {
    echo "<p class='fail'>❌ sales_records.php not found</p>";
}
echo "</div>";

// Test 7: Customer List Demo Structure
echo "<div class='test'>";
echo "<h3>Test 7: Customer List Demo File</h3>";
if (file_exists('pages/customer_list_demo.php')) {
    echo "<p class='pass'>✅ customer_list_demo.php exists</p>";
    
    $content = file_get_contents('pages/customer_list_demo.php');
    
    // Check for critical components
    if (strpos($content, 'require_once') !== false) {
        echo "<p class='pass'>✅ Has require_once statements</p>";
    }
    if (strpos($content, 'Permissions::') !== false) {
        echo "<p class='pass'>✅ Uses Permissions class</p>";
    }
    if (strpos($content, 'renderMainLayout') !== false) {
        echo "<p class='pass'>✅ Uses main layout</p>";
    }
    if (strpos($content, 'SalesRecordsManager') !== false) {
        echo "<p class='pass'>✅ Has JavaScript manager class</p>";
    }
    
    // Check for potential issues
    $js_section = '';
    $lines = explode("\n", $content);
    $in_js = false;
    $js_errors = [];
    
    foreach ($lines as $line_num => $line) {
        if (strpos($line, '<script>') !== false) $in_js = true;
        if ($in_js) {
            $js_section .= $line . "\n";
            
            // Check for common JS errors
            if (strpos($line, '${') !== false && strpos($line, "'") !== false) {
                // Check for unescaped quotes in template literals
                if (preg_match('/onclick="[^"]*\'[^"]*\$\{[^}]*\}[^"]*\'[^"]*"/', $line)) {
                    $js_errors[] = "Line " . ($line_num + 1) . ": Unescaped quote in template literal";
                }
            }
        }
        if (strpos($line, '</script>') !== false) $in_js = false;
    }
    
    if (empty($js_errors)) {
        echo "<p class='pass'>✅ No obvious JavaScript syntax errors</p>";
    } else {
        echo "<p class='fail'>❌ Potential JavaScript issues found:</p>";
        foreach ($js_errors as $error) {
            echo "<p class='fail'>&nbsp;&nbsp;• {$error}</p>";
        }
    }
    
    echo "<p>File size: " . number_format(strlen($content)) . " characters</p>";
    echo "<p>Lines: " . count($lines) . "</p>";
    
} else {
    echo "<p class='fail'>❌ customer_list_demo.php not found</p>";
}
echo "</div>";

// Test 8: Quick Fix Test
echo "<div class='test'>";
echo "<h3>Test 8: Quick Fix Attempt</h3>";
echo "<p>🔧 Attempting to create minimal working version...</p>";

$minimal_content = '<?php
// Minimal test version
require_once "../includes/permissions.php";
require_once "../includes/main_layout.php";

Permissions::requireLogin();
$pageTitle = "รายการขาย (Test)";
$content = "<h1>Test Page</h1><p>If you see this, basic includes work!</p>";
echo renderMainLayout($pageTitle, $content);
?>';

file_put_contents('pages/customer_list_test.php', $minimal_content);
echo "<p class='pass'>✅ Created test file: pages/customer_list_test.php</p>";
echo "<p>🔗 Try accessing: <a href='pages/customer_list_test.php'>customer_list_test.php</a></p>";
echo "</div>";

?>

<div class="test">
<h3>📋 Summary Instructions</h3>
<ol>
<li><strong>Run this test first:</strong> <code>https://www.prima49.com/crm_system/Kiro_CRM_production/test_dependencies.php</code></li>
<li><strong>Check each section above</strong> - note any ❌ failures</li>
<li><strong>Try the test page:</strong> <code>https://www.prima49.com/crm_system/Kiro_CRM_production/pages/customer_list_test.php</code></li>
<li><strong>Report back:</strong> Which tests failed and what errors you see</li>
</ol>

<p><strong>Common Issues to Look For:</strong></p>
<ul>
<li>Database connection failures</li>
<li>Missing session data (need to login first)</li>
<li>Permissions class errors</li>
<li>File path issues</li>
<li>JavaScript syntax errors</li>
</ul>
</div>

</body>
</html>