<?php
/**
 * Debug Permissions for All Roles
 */

require_once 'includes/permissions.php';

if (!isset($_SESSION)) session_start();

echo "<h2>🔍 Debug Permissions System</h2>";

// Test each role
$test_roles = ['Admin', 'Supervisor', 'Sales', 'Manager'];

foreach ($test_roles as $test_role) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px;'>";
    echo "<h3>🎭 Testing Role: $test_role</h3>";
    
    // Set test session
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = 'test_' . strtolower($test_role);
    $_SESSION['user_role'] = $test_role;
    
    echo "<p><strong>Session Data:</strong></p>";
    echo "<ul>";
    echo "<li>user_role: " . $_SESSION['user_role'] . "</li>";
    echo "<li>Lowercase: " . strtolower($_SESSION['user_role']) . "</li>";
    echo "</ul>";
    
    // Test permissions
    echo "<p><strong>Permission Tests:</strong></p>";
    $key_permissions = ['dashboard', 'customer_list', 'daily_tasks'];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Permission</th><th>Has Permission?</th></tr>";
    
    foreach ($key_permissions as $permission) {
        $has_permission = Permissions::hasPermission($permission);
        $result = $has_permission ? "✅ true" : "❌ false";
        $row_color = $has_permission ? "#d4edda" : "#f8d7da";
        
        echo "<tr style='background: $row_color;'>";
        echo "<td>$permission</td>";
        echo "<td>$result</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test current role function
    echo "<p><strong>Current Role Function:</strong> " . Permissions::getCurrentRole() . "</p>";
    
    echo "</div>";
}

// Clear test session
unset($_SESSION['user_id']);
unset($_SESSION['username']);  
unset($_SESSION['user_role']);

echo "<hr>";
echo "<h3>📋 Permissions Matrix (from permissions.php):</h3>";

// Show permissions array
$reflect = new ReflectionClass('Permissions');
$rolePermissions = $reflect->getStaticPropertyValue('rolePermissions');

echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Role (Key)</th><th>Dashboard</th><th>Customer List</th><th>Daily Tasks</th>";
echo "</tr>";

foreach ($rolePermissions as $role_key => $permissions) {
    $dashboard = $permissions['dashboard'] ? "✅" : "❌";
    $customer_list = $permissions['customer_list'] ? "✅" : "❌"; 
    $daily_tasks = $permissions['daily_tasks'] ? "✅" : "❌";
    
    echo "<tr>";
    echo "<td><strong>$role_key</strong></td>";
    echo "<td>$dashboard</td>";
    echo "<td>$customer_list</td>";
    echo "<td>$daily_tasks</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #e3f2fd; padding: 15px; margin: 20px 0; border-left: 4px solid #2196f3;'>";
echo "<h4>💡 Expected Behavior:</h4>";
echo "<ul>";
echo "<li>All roles should have <strong>dashboard = true</strong></li>";
echo "<li>Sales/Manager permissions should be found in the array</li>";
echo "<li>Role matching should work with lowercase conversion</li>";
echo "</ul>";
echo "</div>";
?>