<?php
/**
 * Debug Redirect Issues
 * Check paths, redirects, and session states
 */

if (!isset($_SESSION)) session_start();

echo "<h2>🔍 Debug Redirect Issues</h2>";

// Current location info
echo "<h3>📍 Current Location Info:</h3>";
echo "<ul>";
echo "<li>Request URI: " . $_SERVER['REQUEST_URI'] . "</li>";
echo "<li>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li>HTTP Host: " . $_SERVER['HTTP_HOST'] . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Current Directory: " . __DIR__ . "</li>";
echo "</ul>";

// Session info
echo "<h3>🏷️ Session Info:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<div style='background: #d4edda; padding: 10px; border-left: 4px solid #28a745;'>";
    echo "✅ <strong>User Logged In:</strong><br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";
    echo "Session ID: " . session_id();
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;'>";
    echo "⚠️ <strong>No User Logged In</strong>";
    echo "</div>";
}

// Test redirect paths
echo "<h3>🔗 Test Redirect Paths:</h3>";
$test_paths = [
    'dashboard.php',
    'pages/dashboard.php', 
    '/crm_system/Kiro_CRM_production/pages/dashboard.php',
    '../pages/dashboard.php'
];

echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Path</th><th>File Exists?</th><th>Test Link</th></tr>";

foreach ($test_paths as $path) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/crm_system/Kiro_CRM_production/' . ltrim($path, '/');
    $exists = file_exists($full_path) ? "✅ Yes" : "❌ No";
    
    echo "<tr>";
    echo "<td><code>$path</code></td>";
    echo "<td>$exists</td>";
    echo "<td><a href='$path' target='_blank' style='background: #007bff; color: white; padding: 4px 8px; text-decoration: none; border-radius: 3px;'>Test</a></td>";
    echo "</tr>";
}
echo "</table>";

// Functions test
echo "<h3>🔧 Functions Test:</h3>";
require_once 'includes/functions.php';

echo "<ul>";
echo "<li>isLoggedIn(): " . (isLoggedIn() ? "✅ true" : "❌ false") . "</li>";
echo "<li>getCurrentUserId(): " . (getCurrentUserId() ?? 'null') . "</li>";
echo "<li>getCurrentUsername(): " . (getCurrentUsername() ?? 'null') . "</li>";
echo "<li>getCurrentUserRole(): " . (getCurrentUserRole() ?? 'null') . "</li>";
echo "</ul>";

// Browser location test
echo "<h3>🌐 Browser Location Test:</h3>";
echo "<script>";
echo "document.write('<p>Current URL: ' + window.location.href + '</p>');";
echo "document.write('<p>Hostname: ' + window.location.hostname + '</p>');";
echo "document.write('<p>Pathname: ' + window.location.pathname + '</p>');";
echo "</script>";

echo "<hr>";
echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3;'>";
echo "<h4>🧪 Quick Tests:</h4>";
echo "<p><a href='pages/login.php' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔑 Login Page</a></p>";
echo "<p><a href='pages/dashboard.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>📊 Dashboard Direct</a></p>";
echo "<p><a href='test_role_login.php' style='background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🎭 Role Test</a></p>";
echo "<p><a href='api/auth/logout.php' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🚪 Logout</a></p>";
echo "</div>";
?>