<?php
/**
 * Create Missing Users
 * Create sale and manager users that are missing
 */

require_once 'includes/functions.php';

if (!isset($_SESSION)) session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    echo "❌ Access denied. Admin login required.";
    echo "<br><a href='pages/login.php'>Login as Admin</a>";
    exit;
}

try {
    $db = getDB();
    
    echo "<h2>🔧 Creating Missing Users</h2>";
    echo "<p>Current user: " . $_SESSION['username'] . " (" . $_SESSION['user_role'] . ")</p>";
    
    // Check existing users first
    $existing_users = $db->query("SELECT Username, Role FROM users ORDER BY Username");
    
    echo "<h3>📋 Existing Users:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Username</th><th>Role</th></tr>";
    foreach ($existing_users as $user) {
        echo "<tr><td>{$user['Username']}</td><td>{$user['Role']}</td></tr>";
    }
    echo "</table>";
    
    // Users to create
    $users_to_create = [
        [
            'username' => 'sale',
            'password' => 'sale123',
            'role' => 'Sales',
            'first_name' => 'พนักงาน',
            'last_name' => 'ขาย',
            'email' => 'sale@company.com'
        ],
        [
            'username' => 'manager',
            'password' => 'manager123', 
            'role' => 'Manager',
            'first_name' => 'ผู้จัดการ',
            'last_name' => 'ฝ่าย',
            'email' => 'manager@company.com'
        ]
    ];
    
    echo "<hr><h3>➕ Creating New Users:</h3>";
    
    foreach ($users_to_create as $new_user) {
        // Check if user already exists
        $existing = $db->queryOne("SELECT id FROM users WHERE Username = ?", [$new_user['username']]);
        
        if ($existing) {
            echo "<p style='color: orange;'>⚠️ User '{$new_user['username']}' already exists (ID: {$existing['id']})</p>";
            continue;
        }
        
        // Hash password
        $hashed_password = password_hash($new_user['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "
            INSERT INTO users (
                Username, Password, Role, FirstName, LastName, 
                Email, Status, CreatedDate, ModifiedDate
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 1, NOW(), NOW()
            )
        ";
        
        $success = $db->execute($sql, [
            $new_user['username'],
            $hashed_password,
            $new_user['role'],
            $new_user['first_name'],
            $new_user['last_name'],
            $new_user['email']
        ]);
        
        if ($success) {
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-left: 4px solid #28a745;'>";
            echo "✅ <strong>Created user: {$new_user['username']}</strong><br>";
            echo "📧 Email: {$new_user['email']}<br>";
            echo "👤 Role: {$new_user['role']}<br>";
            echo "🔑 Password: {$new_user['password']}<br>";
            echo "👥 Name: {$new_user['first_name']} {$new_user['last_name']}";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create user: {$new_user['username']}</p>";
        }
    }
    
    // Show updated user list
    echo "<hr><h3>📊 Updated User List:</h3>";
    $all_users = $db->query("SELECT Username, Role, FirstName, LastName, Email, CreatedDate FROM users ORDER BY Role, Username");
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Username</th><th>Role</th><th>Name</th><th>Email</th><th>Created</th><th>Test Login</th>";
    echo "</tr>";
    
    $test_passwords = [
        'admin' => 'admin123',
        'supervisor' => 'supervisor123', 
        'sale' => 'sale123',
        'manager' => 'manager123'
    ];
    
    foreach ($all_users as $user) {
        $test_pass = $test_passwords[$user['Username']] ?? '???';
        echo "<tr>";
        echo "<td><strong>{$user['Username']}</strong></td>";
        echo "<td><span style='background: #e3f2fd; padding: 3px 8px; border-radius: 3px;'>{$user['Role']}</span></td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "<td>{$user['Email']}</td>";
        echo "<td>" . date('Y-m-d H:i', strtotime($user['CreatedDate'])) . "</td>";
        echo "<td style='font-family: monospace; background: #f8f9fa;'>{$user['Username']} / {$test_pass}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<hr>
<div style="margin: 20px 0;">
    <h3>🧪 Next Steps:</h3>
    <p><a href="test_all_roles.php" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔄 Test All Roles Again</a></p>
    <p><a href="pages/login.php" style="background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;">🔑 Login Page</a></p>
</div>