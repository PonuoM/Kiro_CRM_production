<?php
/**
 * Fix Password Hashes for Test Users
 * Update all test users with correct password hashes
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>🔧 Fix Password Hashes</h2>";

// Get database connection
try {
    global $db_config;
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $db = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
    echo "✅ Database connected<br><br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Password updates needed
$passwordUpdates = [
    ['username' => 'sales01', 'password' => 'sale123'],
    ['username' => 'sales02', 'password' => 'sale123'], 
    ['username' => 'supervisor01', 'password' => 'supervisor123'],
    // Also fix existing users if needed
    ['username' => 'admin', 'password' => 'admin123'],
    ['username' => 'supervisor', 'password' => 'supervisor123'],
    ['username' => 'sale1', 'password' => 'sale123']
];

echo "<h3>🔐 Updating Password Hashes</h3>";

foreach ($passwordUpdates as $update) {
    $username = $update['username'];
    $password = $update['password'];
    
    // Generate new hash
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update in database
    try {
        $stmt = $db->prepare("UPDATE users SET Password = ? WHERE Username = ?");
        $result = $stmt->execute([$hash, $username]);
        
        if ($result) {
            // Verify the update worked
            $verifyStmt = $db->prepare("SELECT Username, Password FROM users WHERE Username = ?");
            $verifyStmt->execute([$username]);
            $user = $verifyStmt->fetch();
            
            if ($user && password_verify($password, $user['Password'])) {
                echo "✅ <strong>$username</strong> → password updated and verified<br>";
            } else {
                echo "⚠️ <strong>$username</strong> → password updated but verification failed<br>";
            }
        } else {
            echo "❌ <strong>$username</strong> → update failed<br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>$username</strong> → error: " . $e->getMessage() . "<br>";
    }
}

echo "<br><h3>🧪 Testing Updated Passwords</h3>";

// Test each user login
foreach ($passwordUpdates as $update) {
    $username = $update['username'];
    $password = $update['password'];
    
    echo "<h4>Testing: $username / $password</h4>";
    
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE Username = ? AND Status = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['Password'])) {
            echo "✅ <strong>LOGIN SUCCESS</strong> - {$user['FirstName']} {$user['LastName']} ({$user['Role']})<br>";
        } else {
            echo "❌ <strong>LOGIN FAILED</strong><br>";
        }
    } catch (Exception $e) {
        echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    }
    echo "<br>";
}

echo "<h3>✅ Password Fix Complete!</h3>";
echo "<p><strong>Now you can login with:</strong></p>";
echo "<ul>";
echo "<li><strong>admin</strong> / admin123</li>";
echo "<li><strong>supervisor</strong> / supervisor123</li>";
echo "<li><strong>sale1</strong> / sale123</li>";
echo "<li><strong>sales01</strong> / sale123</li>";
echo "<li><strong>sales02</strong> / sale123</li>";
echo "<li><strong>supervisor01</strong> / supervisor123</li>";
echo "</ul>";

echo "<p>🚀 <a href='login.php'>Try Login Now</a></p>";
?>