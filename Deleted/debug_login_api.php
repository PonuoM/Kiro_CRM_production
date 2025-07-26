<?php
/**
 * Debug Login API for Sales User
 * Test what happens during actual login process
 */

require_once 'includes/functions.php';
require_once 'includes/User.php';

if (!isset($_SESSION)) session_start();

echo "<h2>🔍 Debug Login API for Sales User</h2>";

// Test authentication
echo "<h3>🔐 Authentication Test:</h3>";

try {
    $userModel = new User();
    $user = $userModel->authenticate('sale', 'sale123');
    
    if ($user) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<strong>✅ Authentication Successful</strong><br>";
        echo "User Data from Database:<br>";
        foreach ($user as $key => $value) {
            echo "- $key: $value<br>";
        }
        echo "</div>";
        
        // Test setUserSession
        echo "<h3>🏷️ Testing setUserSession():</h3>";
        
        echo "<p><strong>Before setUserSession:</strong></p>";
        echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
        echo "Session user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";
        
        // Call setUserSession like API does
        setUserSession($user);
        
        echo "<p><strong>After setUserSession:</strong></p>";
        echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
        echo "Session user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";
        
        // Test permissions after setUserSession
        echo "<h3>🔐 Permissions After setUserSession:</h3>";
        require_once 'includes/permissions.php';
        
        $test_permissions = ['dashboard', 'customer_list', 'daily_tasks'];
        foreach ($test_permissions as $permission) {
            $has = Permissions::hasPermission($permission) ? "✅" : "❌";
            echo "<p>$has $permission</p>";
        }
        
        // Test isLoggedIn function
        echo "<h3>✅ Login Status Tests:</h3>";
        echo "isLoggedIn(): " . (isLoggedIn() ? "✅ true" : "❌ false") . "<br>";
        echo "getCurrentUserId(): " . (getCurrentUserId() ?? 'null') . "<br>";
        echo "getCurrentUsername(): " . (getCurrentUsername() ?? 'null') . "<br>";
        echo "getCurrentUserRole(): " . (getCurrentUserRole() ?? 'null') . "<br>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
        echo "<strong>❌ Authentication Failed</strong><br>";
        echo "User not found or password incorrect";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

// Test what API login.php would return
echo "<hr><h3>🔗 Simulate API Response:</h3>";

if (isset($_SESSION['user_id'])) {
    $api_response = [
        'success' => true,
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'user' => [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'firstName' => $_SESSION['first_name'],
            'lastName' => $_SESSION['last_name'],
            'role' => $_SESSION['user_role'],
            'companyCode' => $_SESSION['company_code']
        ],
        'redirect' => 'dashboard.php'
    ];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
    echo "<strong>API would return:</strong><br>";
    echo "<pre>" . json_encode($api_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
}

// Compare with working user (admin)
echo "<hr><h3>🔄 Compare with Working Admin:</h3>";

echo "<p><a href='?compare=admin' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Compare Admin Login</a></p>";

if (isset($_GET['compare']) && $_GET['compare'] === 'admin') {
    try {
        $admin_user = $userModel->authenticate('admin', 'admin123');
        if ($admin_user) {
            echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3;'>";
            echo "<strong>Admin User Data:</strong><br>";
            foreach ($admin_user as $key => $value) {
                echo "- $key: $value<br>";
            }
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<h4>🧪 Test Dashboard Access:</h4>";
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ Session is set - try dashboard:</p>";
    echo "<p><a href='pages/dashboard.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Test Dashboard</a></p>";
} else {
    echo "<p>❌ No session set</p>";
}
echo "</div>";

echo "<p><a href='api/auth/logout.php' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🚪 Logout</a></p>";
?>