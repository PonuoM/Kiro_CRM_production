<?php
/**
 * Fix Sales Role - Manual Session Test
 * Same approach as working Admin/Supervisor
 */

if (!isset($_SESSION)) session_start();

echo "<h2>🔧 Fix Sales Role</h2>";

// Test what makes Admin/Supervisor work vs Sales fail
echo "<h3>📊 Working vs Broken Comparison:</h3>";

// Working roles data
$working_roles = [
    'admin' => ['user_role' => 'Admin', 'permissions' => 'All'],
    'supervisor' => ['user_role' => 'Supervisor', 'permissions' => 'Most']
];

$broken_roles = [
    'sale' => ['user_role' => 'Sales', 'permissions' => 'Limited']
];

echo "<h4>✅ Working Roles:</h4>";
foreach ($working_roles as $username => $data) {
    echo "<p>🔹 <strong>$username</strong> → Role: {$data['user_role']} → {$data['permissions']}</p>";
}

echo "<h4>❌ Broken Roles:</h4>";
foreach ($broken_roles as $username => $data) {
    echo "<p>🔹 <strong>$username</strong> → Role: {$data['user_role']} → {$data['permissions']}</p>";
}

// Manual Sales login test
echo "<hr><h3>🧪 Manual Sales Login Test:</h3>";

if (isset($_GET['test']) && $_GET['test'] === 'sales') {
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<strong>Setting Sales session manually (like working roles):</strong><br>";
    
    // Set session exactly like working roles
    $_SESSION['user_id'] = 4;
    $_SESSION['username'] = 'sale';
    $_SESSION['user_role'] = 'Sales';  // Exact case like database
    $_SESSION['first_name'] = 'พนักงาน';
    $_SESSION['last_name'] = 'ขาย';
    $_SESSION['company_code'] = 'COMP001';
    
    echo "✅ Session set successfully<br>";
    echo "User ID: {$_SESSION['user_id']}<br>";
    echo "Username: {$_SESSION['username']}<br>";
    echo "Role: {$_SESSION['user_role']}<br>";
    echo "</div>";
    
    echo "<p><a href='pages/dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📊 Test Dashboard Access</a></p>";
} else {
    echo "<p><a href='?test=sales' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Set Sales Session & Test</a></p>";
}

// Show current session
echo "<hr><h3>🏷️ Current Session:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<strong>Current User:</strong><br>";
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'user') !== false || in_array($key, ['username', 'first_name', 'last_name', 'company_code'])) {
            echo "$key: $value<br>";
        }
    }
    echo "</div>";
    
    // Test permissions
    echo "<h4>🔐 Permission Tests:</h4>";
    require_once 'includes/permissions.php';
    
    $test_permissions = ['dashboard', 'customer_list', 'daily_tasks'];
    foreach ($test_permissions as $permission) {
        $has = Permissions::hasPermission($permission) ? "✅" : "❌";
        echo "<p>$has $permission</p>";
    }
} else {
    echo "<p style='color: #dc3545;'>❌ No session data</p>";
}

echo "<hr>";
echo "<div style='background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3;'>";
echo "<h4>💡 Next Steps:</h4>";
echo "<p>1. Click 'Set Sales Session & Test' above</p>";
echo "<p>2. Try accessing dashboard</p>";
echo "<p>3. If it works → problem is in login process</p>";
echo "<p>4. If it fails → problem is in permissions</p>";
echo "</div>";

echo "<p><a href='test_role_login.php' style='background: #6c757d; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔙 Back to Role Test</a></p>";
?>