<?php
/**
 * Debug Redirect Loop Issues
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>🔄 Debug Redirect Loop</h2>";

// Show session information
echo "<h3>1. Session Information</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Data:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if user is considered logged in
echo "<h3>2. Login Status Check</h3>";
require_once 'includes/functions.php';

$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
echo "isLoggedIn() result: " . ($isLoggedIn ? "✅ TRUE" : "❌ FALSE") . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "user_id exists: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "user_id not set<br>";
}

if (isset($_SESSION['username'])) {
    echo "username exists: " . $_SESSION['username'] . "<br>";
} else {
    echo "username not set<br>";
}

if (isset($_SESSION['user_role'])) {
    echo "user_role exists: " . $_SESSION['user_role'] . "<br>";
} else {
    echo "user_role not set<br>";
}

// Test login with a user manually
echo "<h3>3. Manual Login Test</h3>";

if (isset($_GET['test_login'])) {
    // Simulate a successful login
    $_SESSION['user_id'] = '1';
    $_SESSION['username'] = 'sales01';
    $_SESSION['user_role'] = 'Sale';
    
    echo "✅ Simulated login set:<br>";
    echo "- user_id: " . $_SESSION['user_id'] . "<br>";
    echo "- username: " . $_SESSION['username'] . "<br>";
    echo "- user_role: " . $_SESSION['user_role'] . "<br>";
    
    $newIsLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    echo "- isLoggedIn() now: " . ($newIsLoggedIn ? "✅ TRUE" : "❌ FALSE") . "<br>";
    
    echo "<br><a href='dashboard.php'>🚀 Try Dashboard Now</a><br>";
    echo "<a href='?clear_session=1'>🧹 Clear Session</a><br>";
} else {
    echo "<a href='?test_login=1'>🧪 Test Manual Login</a><br>";
}

// Clear session option
if (isset($_GET['clear_session'])) {
    session_destroy();
    session_start();
    echo "✅ Session cleared!<br>";
    echo "<a href='debug_redirect_loop.php'>🔄 Refresh Page</a><br>";
}

// Check redirect paths
echo "<h3>4. Path Analysis</h3>";
echo "Current URL: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";

require_once 'config/config.php';
echo "BASE_URL: " . BASE_URL . "<br>";
echo "PAGES_URL: " . PAGES_URL . "<br>";

// Check if files exist
echo "<h3>5. File Existence Check</h3>";
$files_to_check = [
    'pages/login.php',
    'pages/dashboard.php',
    'pages/index.php',
    'index.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    echo "$file: " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
}

// Show login form for direct testing
echo "<h3>6. Direct Login Test</h3>";
if (!$isLoggedIn) {
    echo '<form method="post" action="api/auth/login.php">';
    echo '<input type="text" name="username" placeholder="Username" value="sales01"><br><br>';
    echo '<input type="password" name="password" placeholder="Password" value="sale123"><br><br>';
    echo '<input type="hidden" name="csrf_token" value="test_token"><br>';
    echo '<button type="submit">🔑 Test Direct Login</button>';
    echo '</form>';
} else {
    echo "Already logged in!<br>";
    echo "<a href='pages/dashboard.php'>📊 Go to Dashboard</a><br>";
    echo "<a href='?clear_session=1'>🚪 Logout</a><br>";
}

echo "<h3>7. Recommendations</h3>";
echo "💡 If redirect loop continues:<br>";
echo "- Clear browser cookies and cache<br>";
echo "- Check .htaccess for redirect rules<br>";
echo "- Verify login.php and dashboard.php redirect logic<br>";
echo "- Test with incognito/private browser window<br>";
?>