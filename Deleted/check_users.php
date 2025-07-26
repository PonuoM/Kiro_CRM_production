<?php
echo "<h2>👥 Check Users in Database</h2>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    
    echo "✅ Database connected<br><br>";
    
    // Get all users
    $stmt = $db->getConnection()->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h3>Users in database (" . count($users) . "):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Password Hash</th></tr>";
    
    foreach($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['email'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($user['role'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($user['status'] ?? 'N/A') . "</td>";
        echo "<td>" . substr(htmlspecialchars($user['password'] ?? 'N/A'), 0, 20) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><h3>🔐 Password Test:</h3>";
    
    // Test password verification
    $testPassword = 'admin123';
    foreach($users as $user) {
        if($user['username'] === 'admin') {
            echo "Testing password for admin user...<br>";
            
            // Check if password is hashed or plain text
            $storedPassword = $user['password'];
            
            if(password_verify($testPassword, $storedPassword)) {
                echo "✅ Password verification successful (hashed)<br>";
            } elseif($storedPassword === $testPassword) {
                echo "✅ Password matches (plain text)<br>";
            } elseif(md5($testPassword) === $storedPassword) {
                echo "✅ Password matches (MD5 hash)<br>";
            } elseif(sha1($testPassword) === $storedPassword) {
                echo "✅ Password matches (SHA1 hash)<br>";
            } else {
                echo "❌ Password does not match<br>";
                echo "Stored: " . htmlspecialchars($storedPassword) . "<br>";
                echo "Expected (plain): " . $testPassword . "<br>";
                echo "Expected (MD5): " . md5($testPassword) . "<br>";
                echo "Expected (bcrypt): " . password_hash($testPassword, PASSWORD_DEFAULT) . "<br>";
            }
            break;
        }
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>