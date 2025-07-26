<?php
/**
 * Check Current Roles - Simple Check
 * No modifications, just check what's in database
 */

require_once 'includes/functions.php';

if (!isset($_SESSION)) session_start();

try {
    $db = getDB();
    
    echo "<h2>🔍 Check Current Roles (Read Only)</h2>";
    
    // Show current user session
    echo "<h3>🏷️ Current Session:</h3>";
    if (isset($_SESSION['user_id'])) {
        echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
        echo "<strong>Current User:</strong><br>";
        echo "User ID: " . $_SESSION['user_id'] . "<br>";
        echo "Username: " . $_SESSION['username'] . "<br>";
        echo "Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "<br>";
        echo "First Name: " . ($_SESSION['first_name'] ?? 'NOT SET') . "<br>";
        echo "Last Name: " . ($_SESSION['last_name'] ?? 'NOT SET') . "<br>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ No session active</p>";
    }
    
    // Show database users
    echo "<hr><h3>📊 Database Users (READ ONLY):</h3>";
    $all_users = $db->query("SELECT id, Username, Role, FirstName, LastName, Email FROM users ORDER BY id");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Username</th><th>Role</th><th>Name</th><th>Status</th>";
    echo "</tr>";
    
    foreach ($all_users as $user) {
        $role = $user['Role'];
        $role_display = empty($role) ? "❌ EMPTY" : "✅ " . $role;
        $row_color = empty($role) ? "background: #f8d7da;" : "background: #d4edda;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td>{$role_display}</td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "<td>" . (empty($role) ? "Needs Role" : "OK") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test menu permissions for current user
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        echo "<hr><h3>🔐 Menu Permission Test:</h3>";
        
        require_once 'includes/permissions.php';
        
        echo "<p><strong>Testing permissions for role: " . $_SESSION['user_role'] . "</strong></p>";
        
        $menu_permissions = [
            'dashboard' => 'Dashboard',
            'customer_list' => 'Customer List', 
            'daily_tasks' => 'Daily Tasks',
            'call_history' => 'Call History',
            'order_history' => 'Order History',
            'sales_performance' => 'Sales Performance'
        ];
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>Permission</th><th>Has Access?</th></tr>";
        
        foreach ($menu_permissions as $permission => $label) {
            $has_access = Permissions::hasPermission($permission);
            $access_display = $has_access ? "✅ Yes" : "❌ No";
            $row_color = $has_access ? "background: #d4edda;" : "background: #f8d7da;";
            
            echo "<tr style='$row_color'>";
            echo "<td>{$label}</td>";
            echo "<td>{$access_display}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Show menu items that would be generated
        echo "<h4>🧭 Menu Items Generated:</h4>";
        $menu_items = Permissions::getMenuItems();
        
        if (empty($menu_items)) {
            echo "<p style='color: red;'>❌ No menu items generated!</p>";
        } else {
            echo "<ul>";
            foreach ($menu_items as $item) {
                echo "<li>📄 {$item['title']} → {$item['url']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;">
    <h4>💡 If Role is EMPTY:</h4>
    <p>Go to phpMyAdmin or database admin and manually set:</p>
    <ul>
        <li><strong>sale1</strong> → Role = <strong>Sales</strong></li>
        <li><strong>admin</strong> → Role = <strong>Admin</strong></li>
        <li><strong>supervisor</strong> → Role = <strong>Supervisor</strong></li>
    </ul>
    <p>Then refresh dashboard to see menus appear.</p>
</div>

<p><a href="pages/dashboard.php" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔙 Back to Dashboard</a></p>