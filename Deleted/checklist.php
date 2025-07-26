<?php
/**
 * System Health Checklist
 * Check all pages and identify HTTP 500 errors
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>🔍 System Health Checklist</h1>";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "⚠️ <strong>Not logged in</strong> - Some tests may fail<br>";
    echo "<a href='pages/login.php'>Login first</a>";
    echo "</div><br>";
}

// Function to test page
function testPage($url, $name) {
    $full_url = "https://www.prima49.com/crm_system/Kiro_CRM_production/" . $url;
    
    echo "<tr>";
    echo "<td><strong>$name</strong></td>";
    echo "<td><code>$url</code></td>";
    
    // Test with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    // Include cookies if session exists
    if (isset($_SESSION['user_id'])) {
        $cookie = session_name() . '=' . session_id();
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Determine status
    if ($http_code == 200) {
        if (strpos($response, 'HTTP ERROR 500') !== false || strpos($response, 'Fatal error') !== false) {
            echo "<td style='background: #f8d7da; color: #721c24;'>❌ HTTP 500 (Content)</td>";
        } else {
            echo "<td style='background: #d4edda; color: #155724;'>✅ OK ($http_code)</td>";
        }
    } elseif ($http_code == 302 || $http_code == 301) {
        echo "<td style='background: #fff3cd; color: #856404;'>🔄 Redirect ($http_code)</td>";
    } elseif ($http_code == 500) {
        echo "<td style='background: #f8d7da; color: #721c24;'>❌ HTTP 500</td>";
    } else {
        echo "<td style='background: #f8d7da; color: #721c24;'>❌ Error ($http_code)</td>";
    }
    
    echo "<td><a href='$full_url' target='_blank' style='background: #007bff; color: white; padding: 4px 8px; text-decoration: none; border-radius: 3px; font-size: 12px;'>Test</a></td>";
    echo "</tr>";
}

// Test pages
echo "<h2>📊 Page Status Check</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Page</th><th>URL</th><th>Status</th><th>Test</th></tr>";

// Core pages
testPage("pages/dashboard.php", "Dashboard");
testPage("pages/login.php", "Login");
testPage("pages/customer_list_demo.php", "Customer List");
testPage("pages/daily_tasks_demo.php", "Daily Tasks");

// Problem pages
testPage("pages/order_history_demo.php", "Order History");
testPage("pages/sales_performance.php", "Sales Performance");
testPage("pages/admin/import_customers.php", "Import Customers");

// Other pages
testPage("pages/call_history_demo.php", "Call History");
testPage("pages/customer_detail.php?code=TEST001", "Customer Detail");

echo "</table>";

// System info
echo "<h2>🛠️ System Information</h2>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><td><strong>PHP Version</strong></td><td>" . phpversion() . "</td></tr>";
echo "<tr><td><strong>Session Status</strong></td><td>" . (session_status() == PHP_SESSION_ACTIVE ? "✅ Active" : "❌ Inactive") . "</td></tr>";
echo "<tr><td><strong>Session ID</strong></td><td>" . session_id() . "</td></tr>";
echo "<tr><td><strong>Memory Usage</strong></td><td>" . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB</td></tr>";
echo "<tr><td><strong>Document Root</strong></td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td><strong>Current Directory</strong></td><td>" . __DIR__ . "</td></tr>";
echo "</table>";

// Session data
echo "<h2>🏷️ Session Data</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'user') !== false || in_array($key, ['username', 'first_name', 'last_name', 'company_code'])) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No session data available</p>";
}

// File existence check
echo "<h2>📁 Critical File Check</h2>";
$critical_files = [
    'includes/functions.php',
    'includes/permissions.php', 
    'includes/main_layout.php',
    'includes/admin_layout.php',
    'config/config.php',
    'config/database.php'
];

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>File</th><th>Exists</th><th>Readable</th><th>Size</th></tr>";

foreach ($critical_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path);
    $readable = $exists ? is_readable($full_path) : false;
    $size = $exists ? filesize($full_path) : 0;
    
    $exists_status = $exists ? "✅ Yes" : "❌ No";
    $readable_status = $readable ? "✅ Yes" : "❌ No";
    $size_display = $exists ? round($size / 1024, 2) . " KB" : "N/A";
    
    $row_color = ($exists && $readable) ? "background: #d4edda;" : "background: #f8d7da;";
    
    echo "<tr style='$row_color'>";
    echo "<td>$file</td>";
    echo "<td>$exists_status</td>";
    echo "<td>$readable_status</td>";
    echo "<td>$size_display</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3;'>";
echo "<h3>💡 Next Steps:</h3>";
echo "<ul>";
echo "<li>If pages show ❌ HTTP 500 → Check PHP error logs</li>";
echo "<li>If files show ❌ → Check file permissions</li>";
echo "<li>If session issues → Check session configuration</li>";
echo "</ul>";
echo "</div>";
?>