<?php
/**
 * Direct Fix - Set Roles by User ID
 * Fix specific users by ID instead of role matching
 */

require_once 'includes/functions.php';

try {
    $db = getDB();
    
    echo "<h2>🔧 Direct Role Fix by User ID</h2>";
    
    // Show current state
    echo "<h3>📋 Before Fix:</h3>";
    $users_before = $db->query("SELECT id, Username, Role, FirstName, LastName FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Current Role</th><th>Name</th></tr>";
    
    foreach ($users_before as $user) {
        $role_display = empty($user['Role']) ? "❌ EMPTY" : $user['Role'];
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td>$role_display</td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Direct fixes by ID
    echo "<hr><h3>🔨 Direct Fixes by User ID:</h3>";
    
    $direct_fixes = [
        1 => ['username' => 'admin', 'role' => 'Admin'],
        2 => ['username' => 'supervisor', 'role' => 'Supervisor'], 
        3 => ['username' => 'sale1', 'role' => 'Sales'],
        4 => ['username' => 'sale', 'role' => 'Sales'],
        5 => ['username' => 'manager', 'role' => 'Manager']
    ];
    
    foreach ($direct_fixes as $user_id => $fix_data) {
        $success = $db->execute(
            "UPDATE users SET Role = ?, ModifiedDate = NOW() WHERE id = ?",
            [$fix_data['role'], $user_id]
        );
        
        if ($success) {
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745;'>";
            echo "✅ <strong>ID {$user_id} ({$fix_data['username']}):</strong> Role = {$fix_data['role']}";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-left: 4px solid #dc3545;'>";
            echo "❌ <strong>Failed ID {$user_id} ({$fix_data['username']})</strong>";
            echo "</div>";
        }
    }
    
    // Show results
    echo "<hr><h3>📊 After Fix:</h3>";
    $users_after = $db->query("SELECT id, Username, Role, FirstName, LastName FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Role</th><th>Lowercase Key</th><th>Permissions Match</th><th>Test Credentials</th>";
    echo "</tr>";
    
    $valid_permissions = ['admin', 'supervisor', 'sales', 'manager', 'superadmin'];
    $test_creds = [
        'admin' => 'admin123',
        'supervisor' => 'supervisor123',
        'sale' => 'sale123',
        'sale1' => 'sale123', 
        'manager' => 'manager123'
    ];
    
    foreach ($users_after as $user) {
        $role = $user['Role'];
        $lowercase_key = strtolower($role);
        $has_permissions = in_array($lowercase_key, $valid_permissions);
        $username = $user['Username'];
        $password = $test_creds[$username] ?? '???';
        
        $perm_status = $has_permissions ? "✅ Yes" : "❌ No";
        $row_color = $has_permissions ? "background: #d4edda;" : "background: #f8d7da;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$username}</strong></td>";
        echo "<td>{$role}</td>";
        echo "<td>{$lowercase_key}</td>";
        echo "<td>{$perm_status}</td>";
        echo "<td style='font-family: monospace;'>{$username} / {$password}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8;'>";
    echo "<strong>✅ Direct Role Fixes Complete!</strong><br><br>";
    echo "All users should now have proper roles:<br>";
    foreach ($direct_fixes as $id => $data) {
        echo "🔹 ID {$id}: <strong>{$data['username']}</strong> → {$data['role']}<br>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<div style="margin: 20px 0;">
    <h3>🧪 Test Now:</h3>
    <p><a href="debug_login_api.php" style="background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔍 Debug API (should show ✅ permissions)</a></p>
    <p><strong>Then clear browser cache/cookies and try:</strong></p>
    <p><a href="pages/login.php" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔑 Login: sale/sale123</a></p>
</div>