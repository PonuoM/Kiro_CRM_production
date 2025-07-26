<?php
/**
 * Debug file for customer_list_demo.php
 * Check all dependencies and potential issues
 */

echo "<h1>🔍 Customer List Debug Report</h1>";
echo "<style>body{font-family:Arial;margin:20px} .error{color:red} .success{color:green} .warning{color:orange} .section{margin:20px 0;padding:15px;border:1px solid #ccc}</style>";

echo "<div class='section'>";
echo "<h2>📋 1. Basic PHP Information</h2>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
echo "<strong>Server Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Memory Limit:</strong> " . ini_get('memory_limit') . "<br>";
echo "<strong>Error Reporting:</strong> " . error_reporting() . "<br>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>🗂️ 2. File Structure Check</h2>";

$files_to_check = [
    'includes/permissions.php',
    'includes/main_layout.php', 
    'includes/functions.php',
    'config/database.php',
    'config/config.php',
    'api/sales/sales_records.php',
    'pages/customer_list_demo.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<span class='success'>✅ {$file} - EXISTS</span><br>";
    } else {
        echo "<span class='error'>❌ {$file} - NOT FOUND</span><br>";
    }
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🔧 3. Session Check</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "<span class='warning'>⚠️ Session started by debug script</span><br>";
} else {
    echo "<span class='success'>✅ Session already active</span><br>";
}

echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";  
echo "<strong>Username:</strong> " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";
echo "<strong>User Role:</strong> " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>🔐 4. Permissions Class Test</h2>";
try {
    require_once 'includes/permissions.php';
    echo "<span class='success'>✅ Permissions.php loaded successfully</span><br>";
    
    // Test class methods
    if (class_exists('Permissions')) {
        echo "<span class='success'>✅ Permissions class exists</span><br>";
        
        try {
            $currentUser = Permissions::getCurrentUser();
            echo "<strong>Current User:</strong> {$currentUser}<br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ getCurrentUser() failed: " . $e->getMessage() . "</span><br>";
        }
        
        try {
            $canViewAll = Permissions::canViewAllData();
            echo "<strong>Can View All Data:</strong> " . ($canViewAll ? 'YES' : 'NO') . "<br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ canViewAllData() failed: " . $e->getMessage() . "</span><br>";
        }
        
        try {
            $menuItems = Permissions::getMenuItems();
            echo "<strong>Menu Items Count:</strong> " . count($menuItems) . "<br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ getMenuItems() failed: " . $e->getMessage() . "</span><br>";
        }
        
    } else {
        echo "<span class='error'>❌ Permissions class not found</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Failed to load permissions.php: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🎨 5. Main Layout Test</h2>";
try {
    require_once 'includes/main_layout.php';
    echo "<span class='success'>✅ main_layout.php loaded successfully</span><br>";
    
    if (function_exists('renderMainLayout')) {
        echo "<span class='success'>✅ renderMainLayout() function exists</span><br>";
    } else {
        echo "<span class='error'>❌ renderMainLayout() function not found</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Failed to load main_layout.php: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🗄️ 6. Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    echo "<span class='success'>✅ database.php loaded successfully</span><br>";
    
    if (class_exists('Database')) {
        echo "<span class='success'>✅ Database class exists</span><br>";
        
        try {
            $db = Database::getInstance();
            echo "<span class='success'>✅ Database instance created</span><br>";
            
            $connection = $db->getConnection();
            if ($connection) {
                echo "<span class='success'>✅ Database connection successful</span><br>";
            } else {
                echo "<span class='error'>❌ Database connection failed</span><br>";
            }
        } catch (Exception $e) {
            echo "<span class='error'>❌ Database connection error: " . $e->getMessage() . "</span><br>";
        }
    } else {
        echo "<span class='error'>❌ Database class not found</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Failed to load database.php: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🌐 7. API Endpoint Test</h2>";
$api_file = 'api/sales/sales_records.php';
if (file_exists($api_file)) {
    echo "<span class='success'>✅ sales_records.php exists</span><br>";
    
    // Test API endpoint by making a request
    $api_url = '/crm_system/Kiro_CRM_production/api/sales/sales_records.php';
    echo "<strong>API URL:</strong> {$api_url}<br>";
    
    // Show first few lines of API file
    $api_content = file_get_contents($api_file);
    $first_lines = implode("\n", array_slice(explode("\n", $api_content), 0, 10));
    echo "<pre style='background:#f0f0f0;padding:10px;font-size:12px'>" . htmlspecialchars($first_lines) . "...</pre>";
} else {
    echo "<span class='error'>❌ sales_records.php not found</span><br>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🚨 8. Customer List Demo File Analysis</h2>";
$demo_file = 'pages/customer_list_demo.php';
if (file_exists($demo_file)) {
    echo "<span class='success'>✅ customer_list_demo.php exists</span><br>";
    
    $content = file_get_contents($demo_file);
    $lines = explode("\n", $content);
    echo "<strong>Total Lines:</strong> " . count($lines) . "<br>";
    echo "<strong>File Size:</strong> " . number_format(filesize($demo_file)) . " bytes<br>";
    
    // Check for potential syntax issues
    $issues = [];
    
    // Check for unmatched quotes in JavaScript
    $js_content = '';
    $in_js = false;
    foreach ($lines as $line_num => $line) {
        if (strpos($line, '<script>') !== false) $in_js = true;
        if ($in_js) $js_content .= $line . "\n";
        if (strpos($line, '</script>') !== false) $in_js = false;
    }
    
    // Check for template literal issues
    if (strpos($js_content, '${') !== false) {
        $template_literals = substr_count($js_content, '`');
        if ($template_literals % 2 !== 0) {
            $issues[] = "Unmatched template literal backticks detected";
        }
    }
    
    // Check for quote issues
    $single_quotes = substr_count($js_content, "'");
    $double_quotes = substr_count($js_content, '"');
    echo "<strong>Single quotes in JS:</strong> {$single_quotes}<br>";
    echo "<strong>Double quotes in JS:</strong> {$double_quotes}<br>";
    
    if (!empty($issues)) {
        echo "<h3 class='error'>⚠️ Potential Issues Found:</h3>";
        foreach ($issues as $issue) {
            echo "<span class='error'>• {$issue}</span><br>";
        }
    } else {
        echo "<span class='success'>✅ No obvious syntax issues detected</span><br>";
    }
    
} else {
    echo "<span class='error'>❌ customer_list_demo.php not found</span><br>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>📊 9. Summary & Recommendations</h2>";
echo "<h3>🔍 Next Steps:</h3>";
echo "<ol>";
echo "<li>Run this debug script and check each section</li>";
echo "<li>If Database connection fails, check config/database.php credentials</li>";
echo "<li>If Permissions class fails, check includes/permissions.php</li>";
echo "<li>If Session issues, make sure user is logged in first</li>";
echo "<li>Check server error logs in /logs/ directory</li>";
echo "</ol>";

echo "<h3>🛠️ Quick Fixes to Try:</h3>";
echo "<ul>";
echo "<li>Clear browser cache and cookies</li>";
echo "<li>Try accessing from different browser/incognito mode</li>";
echo "<li>Check if other pages work (dashboard.php, login.php)</li>";
echo "<li>Verify file permissions (should be 644 or 755)</li>";
echo "</ul>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>📧 Report This Information</h2>";
echo "<p>Please copy the above information and report which sections show ❌ errors.</p>";
echo "<p><strong>Debug completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";
?>