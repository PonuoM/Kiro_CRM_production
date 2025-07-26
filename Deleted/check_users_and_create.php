<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    echo "Access denied. Admin only.";
    exit;
}

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h1>🔍 Users Table Analysis & User Creation</h1>";
    echo "<p>Current user: " . $_SESSION['username'] . " (" . $_SESSION['user_role'] . ")</p>";
    
    // Check users table structure
    echo "<h2>📋 Users Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check existing users
    echo "<h2>👥 Existing Users:</h2>";
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr>";
        foreach (array_keys($users[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            foreach ($user as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found in database.</p>";
    }
    
    // Create sample users form
    echo "<h2>➕ Create Sample Users:</h2>";
    
    if ($_POST['create_users'] ?? false) {
        echo "<h3>Creating users...</h3>";
        
        $sampleUsers = [
            [
                'username' => 'sale1',
                'password' => password_hash('sale123', PASSWORD_DEFAULT),
                'first_name' => 'Sale',
                'last_name' => 'User 1',
                'role' => 'Sale',
                'email' => 'sale1@example.com',
                'status' => 'Active'
            ],
            [
                'username' => 'sale2', 
                'password' => password_hash('sale123', PASSWORD_DEFAULT),
                'first_name' => 'Sale',
                'last_name' => 'User 2',
                'role' => 'Sale',
                'email' => 'sale2@example.com',
                'status' => 'Active'
            ],
            [
                'username' => 'supervisor1',
                'password' => password_hash('super123', PASSWORD_DEFAULT),
                'first_name' => 'Supervisor',
                'last_name' => 'User 1',
                'role' => 'Supervisor',
                'email' => 'supervisor1@example.com',
                'status' => 'Active'
            ]
        ];
        
        foreach ($sampleUsers as $userData) {
            try {
                // Check if user already exists
                $checkStmt = $pdo->prepare("SELECT username FROM users WHERE username = ?");
                $checkStmt->execute([$userData['username']]);
                
                if ($checkStmt->fetch()) {
                    echo "<p style='color: orange;'>⚠️ User {$userData['username']} already exists, skipping.</p>";
                    continue;
                }
                
                // Determine column names based on table structure
                $columnNames = array_column($columns, 'Field');
                $passwordField = in_array('Password', $columnNames) ? 'Password' : 'password';
                $firstNameField = in_array('FirstName', $columnNames) ? 'FirstName' : 'first_name';
                $lastNameField = in_array('LastName', $columnNames) ? 'LastName' : 'last_name';
                $roleField = in_array('Role', $columnNames) ? 'Role' : 'role';
                $emailField = in_array('Email', $columnNames) ? 'Email' : 'email';
                $statusField = in_array('Status', $columnNames) ? 'Status' : 'status';
                $usernameField = in_array('Username', $columnNames) ? 'Username' : 'username';
                
                $sql = "INSERT INTO users ($usernameField, $passwordField, $firstNameField, $lastNameField, $roleField, $emailField, $statusField, CreatedDate) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $userData['username'],
                    $userData['password'],
                    $userData['first_name'],
                    $userData['last_name'],
                    $userData['role'],
                    $userData['email'],
                    $userData['status']
                ]);
                
                if ($result) {
                    echo "<p style='color: green;'>✅ Created user: {$userData['username']} ({$userData['role']})</p>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to create user: {$userData['username']}</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error creating user {$userData['username']}: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p><strong>Default passwords:</strong></p>";
        echo "<ul>";
        echo "<li>sale1 / sale123</li>";
        echo "<li>sale2 / sale123</li>";
        echo "<li>supervisor1 / super123</li>";
        echo "</ul>";
    }
    
    echo "<form method='post' style='margin: 20px 0;'>";
    echo "<button type='submit' name='create_users' value='1' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Create Sample Users</button>";
    echo "</form>";
    
    echo "<h2>🔗 Quick Links:</h2>";
    echo "<p>";
    echo "<a href='universal_login.php' style='margin-right: 10px; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>Login Page</a>";
    echo "<a href='debug_customer_detail.php' style='margin-right: 10px; padding: 5px 10px; background: #6c757d; color: white; text-decoration: none; border-radius: 3px;'>Debug Customer Detail</a>";
    echo "<a href='test_all_systems.php' style='padding: 5px 10px; background: #17a2b8; color: white; text-decoration: none; border-radius: 3px;'>Test All Systems</a>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Database Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Make sure database connection is working properly.</p>";
}
?>