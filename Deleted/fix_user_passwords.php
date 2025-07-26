<?php
/**
 * Fix User Passwords - Generate and Update Correct Password Hashes
 * This script will fix the password issues by generating proper hashes
 */

require_once 'config/database.php';

echo "<h2>🔧 Fix User Passwords Script</h2>\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Test connection first
    echo "<p>✅ Database connection successful</p>\n";
    
    // Check current users
    echo "<h3>📋 Current Users in Database:</h3>\n";
    $stmt = $pdo->query("SELECT id, Username, FirstName, LastName, Role, Status FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>❌ No users found in database. Please run production_setup.sql first.</p>\n";
        exit;
    }
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Role</th><th>Status</th></tr>\n";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['Username']}</td>";
        echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
        echo "<td>{$user['Role']}</td>";
        echo "<td>{$user['Status']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Password update configuration
    $password_updates = [
        'admin' => 'admin123',
        'supervisor' => 'supervisor123',
        'sale1' => 'sale123'
    ];
    
    echo "<h3>🔐 Generating New Password Hashes:</h3>\n";
    echo "<pre>\n";
    
    $update_queries = [];
    foreach ($password_updates as $username => $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        echo "User: {$username}\n";
        echo "Password: {$password}\n"; 
        echo "Hash: {$hash}\n";
        
        // Verify the hash works
        $verify = password_verify($password, $hash);
        echo "Verification: " . ($verify ? '✅ PASS' : '❌ FAIL') . "\n";
        echo str_repeat('-', 60) . "\n";
        
        $update_queries[] = [
            'username' => $username,
            'password' => $password,
            'hash' => $hash
        ];
    }
    echo "</pre>\n";
    
    // Ask for confirmation
    if (isset($_POST['update_passwords'])) {
        echo "<h3>🚀 Updating Database...</h3>\n";
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($update_queries as $update) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET Password = ? WHERE Username = ?");
                $result = $stmt->execute([$update['hash'], $update['username']]);
                
                if ($result) {
                    echo "<p>✅ Updated password for user: {$update['username']}</p>\n";
                    $success_count++;
                } else {
                    echo "<p>❌ Failed to update password for user: {$update['username']}</p>\n";
                    $error_count++;
                }
            } catch (Exception $e) {
                echo "<p>❌ Error updating {$update['username']}: " . $e->getMessage() . "</p>\n";
                $error_count++;
            }
        }
        
        echo "<h3>📊 Update Summary:</h3>\n";
        echo "<p>✅ Successful updates: {$success_count}</p>\n";
        echo "<p>❌ Failed updates: {$error_count}</p>\n";
        
        if ($success_count > 0) {
            echo "<h3>🧪 Test Login Credentials:</h3>\n";
            echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>\n";
            foreach ($password_updates as $username => $password) {
                echo "<p><strong>{$username}</strong> / {$password}</p>\n";
            }
            echo "</div>\n";
            
            echo "<p><a href='universal_login.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔑 Test Login Now</a></p>\n";
        }
        
        // Show updated database state
        echo "<h3>✅ Updated Users in Database:</h3>\n";
        $stmt = $pdo->query("SELECT Username, FirstName, LastName, Role, Status FROM users");
        $updated_users = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Username</th><th>Name</th><th>Role</th><th>Status</th><th>Test Login</th></tr>\n";
        foreach ($updated_users as $user) {
            $test_password = $password_updates[$user['Username']] ?? 'N/A';
            echo "<tr>";
            echo "<td>{$user['Username']}</td>";
            echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
            echo "<td>{$user['Role']}</td>";
            echo "<td>" . ($user['Status'] == 1 ? '✅ Active' : '❌ Inactive') . "</td>";
            echo "<td>{$user['Username']} / {$test_password}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
    } else {
        // Show confirmation form
        echo "<h3>⚠️ Ready to Update Database</h3>\n";
        echo "<p>This will update the password hashes for the following users:</p>\n";
        echo "<ul>\n";
        foreach ($password_updates as $username => $password) {
            echo "<li><strong>{$username}</strong> → {$password}</li>\n";
        }
        echo "</ul>\n";
        
        echo "<form method='POST'>\n";
        echo "<input type='hidden' name='update_passwords' value='1'>\n";
        echo "<button type='submit' style='background: #ff6b6b; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;'>🔧 Update Passwords Now</button>\n";
        echo "</form>\n";
        
        echo "<p><small>⚠️ <strong>Warning:</strong> This will overwrite existing passwords. Make sure this is what you want to do.</small></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Database Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p>Please check your database configuration in config/database.php</p>\n";
}

echo "<hr>\n";
echo "<p><small>🔒 <strong>Security Note:</strong> Please delete this file after use for security purposes.</small></p>\n";
?>