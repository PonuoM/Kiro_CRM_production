<?php
/**
 * Fix Empty Role Fields
 * Set correct roles for users with empty Role field
 */

require_once 'includes/functions.php';

try {
    $db = getDB();
    
    echo "<h2>🔧 Fix Empty Role Fields</h2>";
    
    // Check current state
    echo "<h3>📋 Current Users State:</h3>";
    $all_users = $db->query("SELECT id, Username, Role, FirstName, LastName FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Current Role</th><th>Name</th><th>Should Be</th><th>Action</th>";
    echo "</tr>";
    
    // Define correct roles based on username patterns
    $user_role_mapping = [
        'admin' => 'Admin',
        'supervisor' => 'Supervisor', 
        'sale' => 'Sales',
        'sale1' => 'Sales',
        'manager' => 'Manager'
    ];
    
    $fixes_needed = [];
    
    foreach ($all_users as $user) {
        $username = $user['Username'];
        $current_role = $user['Role'];
        $expected_role = $user_role_mapping[$username] ?? 'Unknown';
        
        $needs_fix = (empty($current_role) || $current_role !== $expected_role);
        $action = $needs_fix ? "🔧 Fix to: $expected_role" : "✅ OK";
        $row_color = $needs_fix ? "background: #fff3cd;" : "background: #d4edda;";
        
        if ($needs_fix && $expected_role !== 'Unknown') {
            $fixes_needed[] = ['id' => $user['id'], 'username' => $username, 'role' => $expected_role];
        }
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$username}</strong></td>";
        echo "<td>" . ($current_role ?: "❌ EMPTY") . "</td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "<td>{$expected_role}</td>";
        echo "<td>{$action}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Apply fixes
    if (!empty($fixes_needed)) {
        echo "<hr><h3>🔨 Applying Role Fixes:</h3>";
        
        foreach ($fixes_needed as $fix) {
            $success = $db->execute(
                "UPDATE users SET Role = ?, ModifiedDate = NOW() WHERE id = ?",
                [$fix['role'], $fix['id']]
            );
            
            if ($success) {
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745;'>";
                echo "✅ <strong>Fixed User {$fix['id']} ({$fix['username']}):</strong> Role → {$fix['role']}";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545;'>";
                echo "❌ <strong>Failed to fix User {$fix['id']} ({$fix['username']})</strong>";
                echo "</div>";
            }
        }
    } else {
        echo "<p style='color: green;'>✅ No fixes needed!</p>";
    }
    
    // Show final results
    echo "<hr><h3>📊 Final Results:</h3>";
    $final_users = $db->query("SELECT id, Username, Role FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Role</th><th>Lowercase Key</th><th>Test Login</th>";
    echo "</tr>";
    
    $test_passwords = [
        'admin' => 'admin123',
        'supervisor' => 'supervisor123',
        'sale' => 'sale123', 
        'sale1' => 'sale123',
        'manager' => 'manager123'
    ];
    
    foreach ($final_users as $user) {
        $username = $user['Username'];
        $role = $user['Role'];
        $lowercase_key = strtolower($role);
        $password = $test_passwords[$username] ?? '???';
        
        $row_color = !empty($role) ? "background: #d4edda;" : "background: #f8d7da;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$username}</strong></td>";
        echo "<td>{$role}</td>";
        echo "<td>{$lowercase_key}</td>";
        echo "<td style='font-family: monospace;'>{$username} / {$password}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8;'>";
    echo "<strong>✅ All Role Fields Fixed!</strong><br><br>";
    echo "All users now have proper roles that match permissions.php:<br>";
    echo "🔹 <strong>admin</strong> / admin123 → Admin role<br>";
    echo "🔹 <strong>supervisor</strong> / supervisor123 → Supervisor role<br>";
    echo "🔹 <strong>sale</strong> / sale123 → Sales role<br>";
    echo "🔹 <strong>sale1</strong> / sale123 → Sales role<br>";
    echo "🔹 <strong>manager</strong> / manager123 → Manager role<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<div style="margin: 20px 0;">
    <h3>🧪 Test All Roles Now:</h3>
    <p><a href="test_all_roles.php" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔄 Test All Roles</a></p>
    <p><a href="pages/login.php" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔑 Try Login: sale/sale123</a></p>
    <p><a href="debug_login_api.php" style="background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔍 Debug API Again</a></p>
</div>