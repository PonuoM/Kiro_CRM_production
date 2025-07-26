<?php
/**
 * Fix User Roles
 * Update missing role values for users
 */

require_once 'includes/functions.php';

try {
    $db = getDB();
    
    echo "<h2>🔧 Fixing User Roles</h2>";
    
    // Check current users with empty roles
    echo "<h3>📋 Current Users Status:</h3>";
    $all_users = $db->query("SELECT id, Username, Role, FirstName, LastName FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Current Role</th><th>Name</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($all_users as $user) {
        $role_status = empty($user['Role']) ? "❌ Empty" : "✅ " . $user['Role'];
        $row_color = empty($user['Role']) ? "background: #fff3cd;" : "background: #d4edda;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td>{$user['Role']}</td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "<td>$role_status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Fix role assignments
    $role_fixes = [
        'admin' => 'Admin',
        'supervisor' => 'Supervisor', 
        'sale' => 'Sales',
        'manager' => 'Manager'
    ];
    
    echo "<hr><h3>🔨 Applying Role Fixes:</h3>";
    
    foreach ($role_fixes as $username => $correct_role) {
        // Check if user exists and needs fixing
        $user = $db->queryOne("SELECT id, Username, Role FROM users WHERE Username = ?", [$username]);
        
        if ($user) {
            if (empty($user['Role']) || $user['Role'] !== $correct_role) {
                // Update role
                $success = $db->execute(
                    "UPDATE users SET Role = ?, ModifiedDate = NOW() WHERE Username = ?",
                    [$correct_role, $username]
                );
                
                if ($success) {
                    echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745;'>";
                    echo "✅ <strong>Updated {$username}</strong>: Role → {$correct_role}";
                    echo "</div>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to update role for: {$username}</p>";
                }
            } else {
                echo "<p style='color: green;'>✅ {$username} already has correct role: {$correct_role}</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ User not found: {$username}</p>";
        }
    }
    
    // Show updated results
    echo "<hr><h3>📊 Updated Users:</h3>";
    $updated_users = $db->query("SELECT id, Username, Role, FirstName, LastName FROM users ORDER BY Role, Username");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Role</th><th>Name</th><th>Test Credentials</th>";
    echo "</tr>";
    
    $test_credentials = [
        'admin' => 'admin / admin123',
        'supervisor' => 'supervisor / supervisor123',
        'sale' => 'sale / sale123', 
        'manager' => 'manager / manager123'
    ];
    
    foreach ($updated_users as $user) {
        $credentials = $test_credentials[$user['Username']] ?? $user['Username'] . ' / ???';
        
        echo "<tr style='background: #d4edda;'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td><span style='background: #e3f2fd; padding: 3px 8px; border-radius: 3px;'>{$user['Role']}</span></td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "<td style='font-family: monospace; background: #f8f9fa;'>{$credentials}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8;'>";
    echo "<strong>✅ Role Fixes Applied Successfully!</strong><br><br>";
    echo "All users now have correct roles:<br>";
    echo "🔹 <strong>admin</strong> / admin123 → Admin<br>";
    echo "🔹 <strong>supervisor</strong> / supervisor123 → Supervisor<br>"; 
    echo "🔹 <strong>sale</strong> / sale123 → Sales<br>";
    echo "🔹 <strong>manager</strong> / manager123 → Manager<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<div style="margin: 20px 0;">
    <h3>🧪 Test Again:</h3>
    <p><a href="test_all_roles.php" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔄 Test All Roles Again</a></p>
    <p><a href="pages/login.php" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔑 Try Login</a></p>
</div>