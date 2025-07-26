<?php
/**
 * Simple Debug - No exec() function
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Simple Debug Check</h1>";
echo "<style>body{font-family:Arial;margin:20px} .error{color:red;background:#ffe6e6;padding:10px;margin:10px 0} .success{color:green;background:#e6ffe6;padding:10px;margin:10px 0}</style>";

// Test actual execution
echo "<h3>1. Direct Execution Test</h3>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set test session if needed
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 7;
        $_SESSION['username'] = 'sales01';
        $_SESSION['user_role'] = 'Sales';
    }
    
    echo "<div class='success'>✅ Session setup OK</div>";
    
    // Test includes first
    echo "<h3>2. Testing Includes</h3>";
    try {
        require_once 'includes/permissions.php';
        echo "<div class='success'>✅ permissions.php loaded</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ permissions.php error: " . $e->getMessage() . "</div>";
    }
    
    try {
        require_once 'includes/main_layout.php';  
        echo "<div class='success'>✅ main_layout.php loaded</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ main_layout.php error: " . $e->getMessage() . "</div>";
    }
    
    // Test permissions
    echo "<h3>3. Testing Permissions</h3>";
    try {
        Permissions::requireLogin();
        echo "<div class='success'>✅ requireLogin() OK</div>";
        
        Permissions::requirePermission('customer_list');
        echo "<div class='success'>✅ requirePermission() OK</div>";
        
        $user_name = Permissions::getCurrentUser();
        echo "<div class='success'>✅ getCurrentUser(): $user_name</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Permissions error: " . $e->getMessage() . "</div>";
    }
    
    // Test the actual problem file line by line
    echo "<h3>4. Testing Problem File Step by Step</h3>";
    
    $pageTitle = "รายการขาย";
    echo "<div class='success'>✅ pageTitle set</div>";
    
    // Test problematic lines
    $file_content = file_get_contents('pages/customer_list_demo.php');
    
    // Look for the specific problematic JavaScript section
    if (strpos($file_content, '${sale.CustomerTel ?') !== false) {
        echo "<div class='error'>❌ Found problematic JavaScript template literal</div>";
        
        // Extract the problematic section
        $start = strpos($file_content, '${sale.CustomerTel ?');
        $section = substr($file_content, $start, 200);
        echo "<div class='error'>Problematic section:<br><code>" . htmlspecialchars($section) . "</code></div>";
    } else {
        echo "<div class='success'>✅ No obvious template literal issues</div>";
    }
    
    // Try to execute just the content generation part
    ob_start();
    $user_name = Permissions::getCurrentUser();
    $user_role = Permissions::getCurrentRole();
    $menuItems = Permissions::getMenuItems();
    
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = $menuItems;
    
    $canEdit = Permissions::hasPermission('customer_edit');
    $canViewAll = Permissions::canViewAllData();
    
    echo "<div class='success'>✅ Variables setup OK</div>";
    
    // Test the specific HTML content part (without JavaScript)
    $test_content = '<div class="page-header"><h1>Test Page</h1></div>';
    
    // Test renderMainLayout
    $output = renderMainLayout($pageTitle, $test_content, '', '');
    echo "<div class='success'>✅ renderMainLayout() works</div>";
    
    ob_end_clean();
    
} catch (ParseError $e) {
    echo "<div class='error'>❌ Parse Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";  
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
} catch (Error $e) {
    echo "<div class='error'>❌ Fatal Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
}

// Show specific lines that might have issues
echo "<h3>5. Check Specific Lines</h3>";
$lines = file('pages/customer_list_demo.php');
$check_lines = [308, 309, 420, 421];

foreach ($check_lines as $line_num) {
    if (isset($lines[$line_num - 1])) {
        $line = trim($lines[$line_num - 1]);
        echo "<strong>Line $line_num:</strong> " . htmlspecialchars($line) . "<br>";
    }
}
?>

<h3>📋 Manual Check:</h3>
<p>If this debug works but the main page doesn't, the issue is in the JavaScript section.</p>
<p>Try opening: <a href="pages/customer_list_demo.php">customer_list_demo.php directly</a></p>