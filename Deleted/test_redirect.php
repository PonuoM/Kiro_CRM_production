<?php
/**
 * Test Redirect Issues
 * This file helps debug redirect loops and session issues
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>🔄 Redirect Debug Test</h2>\n";

// Check session status
echo "<h3>Session Information:</h3>\n";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>\n";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>\n";
echo "<p><strong>User ID in Session:</strong> " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "</p>\n";
echo "<p><strong>Username in Session:</strong> " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'Not set') . "</p>\n";

// Show all session data
echo "<h3>All Session Data:</h3>\n";
echo "<pre>" . print_r($_SESSION, true) . "</pre>\n";

// Test URLs
echo "<h3>URL Testing:</h3>\n";
require_once __DIR__ . '/includes/functions.php';

echo "<p><strong>BASE_URL:</strong> " . BASE_URL . "</p>\n";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>\n";
echo "<p><strong>HTTP Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>\n";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>\n";

// Test baseUrl function
echo "<p><strong>baseUrl(''):</strong> " . baseUrl('') . "</p>\n";
echo "<p><strong>pageUrl('login.php'):</strong> " . pageUrl('login.php') . "</p>\n";
echo "<p><strong>apiUrl('auth/login.php'):</strong> " . apiUrl('auth/login.php') . "</p>\n";

// Test redirect logic
echo "<h3>Redirect Logic Test:</h3>\n";
if (!isset($_SESSION['user_id'])) {
    echo "<p>❌ <strong>Not logged in</strong> - Should redirect to login</p>\n";
    echo "<p>Redirect URL: <code>pages/login.php</code></p>\n";
} else {
    echo "<p>✅ <strong>Logged in</strong> - Should redirect to dashboard</p>\n";
    echo "<p>Redirect URL: <code>pages/dashboard.php</code></p>\n";
}

// Manual login test
if (isset($_POST['test_login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['user_role'] = 'Admin';
    echo "<p>✅ <strong>Test session created!</strong></p>\n";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

if (isset($_POST['clear_session'])) {
    session_destroy();
    echo "<p>🗑️ <strong>Session cleared!</strong></p>\n";
    echo "<script>setTimeout(() => location.reload(), 1000);</script>";
}

?>

<h3>🧪 Manual Tests:</h3>
<form method="POST" style="display: inline;">
    <button type="submit" name="test_login" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin: 5px;">
        🔑 Create Test Session
    </button>
</form>

<form method="POST" style="display: inline;">
    <button type="submit" name="clear_session" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin: 5px;">
        🗑️ Clear Session
    </button>
</form>

<h3>📱 Quick Navigation:</h3>
<p>
    <a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">
        🏠 Go to Index
    </a>
    <a href="pages/login.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">
        🔐 Go to Login
    </a>
    <a href="working_login.php" style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">
        🔑 Working Login
    </a>
    <a href="pages/dashboard.php" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block;">
        📊 Dashboard
    </a>
</p>

<h3>⚠️ Common Issues:</h3>
<ul>
    <li><strong>Redirect Loop:</strong> Check if index.php and .htaccess redirect to different pages</li>
    <li><strong>Session Issues:</strong> Make sure session_start() is called before any redirects</li>
    <li><strong>Path Problems:</strong> Verify BASE_URL matches your actual server path</li>
    <li><strong>Cookie Issues:</strong> Check if cookies are being set/read properly</li>
</ul>

<p><small>⚠️ Delete this file after debugging</small></p>