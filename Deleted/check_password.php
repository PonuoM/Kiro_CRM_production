<?php
echo "<h2>🔐 Password Hash Check</h2>";

// The hash from database
$hashFromDB = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Hash from database:<br>";
echo "<code>$hashFromDB</code><br><br>";

// Test common passwords
$testPasswords = ['password', 'admin123', 'admin', '123456', 'secret'];

echo "<h3>Testing common passwords:</h3>";
foreach($testPasswords as $pwd) {
    if(password_verify($pwd, $hashFromDB)) {
        echo "✅ <strong>Password '$pwd' matches!</strong><br>";
    } else {
        echo "❌ Password '$pwd' does not match<br>";
    }
}

echo "<br><h3>Laravel Default Hash Info:</h3>";
echo "This hash <code>$hashFromDB</code> is Laravel's default hash for the word 'password'<br>";
echo "It's commonly used in Laravel seeder files.<br><br>";

echo "<h3>🎯 Try These Login Combinations:</h3>";
echo "<strong>Option 1:</strong><br>";
echo "Username: admin<br>";
echo "Password: password<br><br>";

echo "<strong>Option 2:</strong><br>";
echo "Username: supervisor<br>";
echo "Password: password<br><br>";

echo "<strong>Option 3:</strong><br>";
echo "Username: sale1<br>";
echo "Password: password<br><br>";

echo '<a href="working_login.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🚀 Try Login</a>';
?>