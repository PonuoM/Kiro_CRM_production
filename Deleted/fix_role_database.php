<?php
/**
 * Fix Role Database Mismatch
 * Update database roles to match permissions.php
 */

require_once 'includes/functions.php';

try {
    $db = getDB();
    
    echo "<h2>🔧 Fix Role Database Mismatch</h2>";
    
    // Current database roles
    echo "<h3>📋 Current Database Roles:</h3>";
    $current_users = $db->query("SELECT id, Username, Role FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Username</th><th>Current Role</th><th>Should Be</th><th>Status</th></tr>";
    
    $role_mapping = [
        'Admin' => 'admin',      // Keep as Admin (matches permissions)
        'Supervisor' => 'supervisor', // Keep as Supervisor
        'Sale' => 'Sales',       // Fix: Sale → Sales  
        'Manager' => 'manager'   // Keep as Manager
    ];
    
    foreach ($current_users as $user) {
        $current_role = $user['Role'];
        $correct_role = $role_mapping[$current_role] ?? $current_role;
        $needs_fix = ($current_role !== $correct_role);
        
        $status = $needs_fix ? "❌ Needs Fix" : "✅ OK";
        $row_color = $needs_fix ? "background: #fff3cd;" : "background: #d4edda;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td>{$current_role}</td>";
        echo "<td>{$correct_role}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Apply fixes
    echo "<hr><h3>🔨 Applying Fixes:</h3>";
    
    foreach ($role_mapping as $old_role => $new_role) {
        if ($old_role !== $new_role) {
            $affected = $db->execute(
                "UPDATE users SET Role = ?, ModifiedDate = NOW() WHERE Role = ?",
                [$new_role, $old_role]
            );
            
            if ($affected) {
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745;'>";
                echo "✅ <strong>Updated:</strong> {$old_role} → {$new_role}";
                echo "</div>";
            } else {
                echo "<p style='color: orange;'>⚠️ No users with role: {$old_role}</p>";
            }
        }
    }
    
    // Show updated results
    echo "<hr><h3>📊 Updated Database Roles:</h3>";
    $updated_users = $db->query("SELECT id, Username, Role FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Role</th><th>Permissions Key</th><th>Match Status</th>";
    echo "</tr>";
    
    $permissions_keys = ['superadmin', 'admin', 'supervisor', 'manager', 'sales'];
    
    foreach ($updated_users as $user) {
        $role = $user['Role'];
        $lowercase_role = strtolower($role);
        $has_permissions = in_array($lowercase_role, $permissions_keys);
        
        $match_status = $has_permissions ? "✅ Match" : "❌ No Match";
        $row_color = $has_permissions ? "background: #d4edda;" : "background: #f8d7da;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td>{$role}</td>";
        echo "<td>{$lowercase_role}</td>";
        echo "<td>{$match_status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8;'>";
    echo "<strong>✅ Role Fixes Applied!</strong><br><br>";
    echo "Database roles now match permissions.php keys:<br>";
    echo "🔹 admin → admin ✅<br>";
    echo "🔹 supervisor → supervisor ✅<br>";
    echo "🔹 sales → sales ✅<br>";
    echo "🔹 manager → manager ✅<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<div style="margin: 20px 0;">
    <h3>🧪 Test Again:</h3>
    <p><a href="debug_login_api.php" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔄 Test Login API Again</a></p>
    <p><a href="pages/login.php" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔑 Try Real Login</a></p>
</div>