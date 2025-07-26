<?php
/**
 * Minimal test - step by step execution
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Minimal Step-by-Step Test</h1>";
echo "<style>body{font-family:Arial;margin:20px} .error{color:red;background:#ffe6e6;padding:10px;margin:10px 0} .success{color:green;background:#e6ffe6;padding:10px;margin:10px 0}</style>";

try {
    echo "<h3>Step 1: Session</h3>";
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = 7;
    $_SESSION['username'] = 'sales01';
    $_SESSION['user_role'] = 'Sales';
    echo "<div class='success'>✅ Session OK</div>";
    
    echo "<h3>Step 2: Includes</h3>";
    require_once __DIR__ . '/includes/permissions.php';
    echo "<div class='success'>✅ Permissions loaded</div>";
    
    require_once __DIR__ . '/includes/main_layout.php';
    echo "<div class='success'>✅ Main layout loaded</div>";
    
    echo "<h3>Step 3: Basic Variables</h3>";
    $pageTitle = "รายการขาย";
    $user_name = Permissions::getCurrentUser();
    $user_role = Permissions::getCurrentRole();
    $menuItems = Permissions::getMenuItems();
    
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = $menuItems;
    
    $canEdit = Permissions::hasPermission('customer_edit');
    $canViewAll = Permissions::canViewAllData();
    echo "<div class='success'>✅ Variables set</div>";
    
    echo "<h3>Step 4: Simple Content</h3>";
    $content = '
    <div class="page-header">
        <h1>Test Page Works!</h1>
        <p>If you see this, the basic structure works.</p>
    </div>
    ';
    echo "<div class='success'>✅ Content created</div>";
    
    echo "<h3>Step 5: Simple JavaScript (No Template Literals)</h3>";
    $simpleJS = '
    <script>
    console.log("Simple JS works");
    document.addEventListener("DOMContentLoaded", function() {
        console.log("DOM loaded");
    });
    </script>
    ';
    echo "<div class='success'>✅ Simple JS created</div>";
    
    echo "<h3>Step 6: Render Layout</h3>";
    $output = renderMainLayout($pageTitle, $content, '', $simpleJS);
    echo "<div class='success'>✅ Layout rendered</div>";
    
    echo "<h3>Step 7: Final Test</h3>";
    echo "<p>If all steps above are green, the basic page should work.</p>";
    echo "<p><a href='minimal_page.php'>Try minimal page</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error at Step: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
}
?>