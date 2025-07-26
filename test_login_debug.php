<?php
/**
 * Debug Login Issues - Check Users and Password Hashing
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';

echo "<h2>🔍 Debug Login Issues</h2>";

// Test database connection using production config
echo "<h3>1. Database Connection</h3>";
try {
    // Use production database config
    global $db_config;
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $db = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
    
    echo "✅ Database connection successful<br>";
    echo "Database: <strong>{$db_config['dbname']}</strong><br>";
    echo "Host: {$db_config['host']}:{$db_config['port']}<br>";
    echo "Username: {$db_config['username']}<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<h4>🔍 Connection Details:</h4>";
    echo "Database: <strong>{$db_config['dbname']}</strong><br>";
    echo "Host: {$db_config['host']}:{$db_config['port']}<br>";
    echo "Username: {$db_config['username']}<br>";
    exit;
}

// Check if test users exist
echo "<h3>2. Test Users in Database</h3>";
$stmt = $db->prepare("SELECT Username, Password, FirstName, LastName, Role, Status FROM users WHERE Username IN ('sales01', 'sales02', 'supervisor01')");
$stmt->execute();
$testUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($testUsers)) {
    echo "❌ <strong>No test users found! Need to import mockup_data_fixed.sql</strong><br>";
} else {
    echo "✅ Found " . count($testUsers) . " test users:<br>";
    foreach ($testUsers as $user) {
        echo "- {$user['Username']} ({$user['Role']}) - Status: {$user['Status']}<br>";
    }
}

// Check password hashing
echo "<h3>3. Password Hash Testing</h3>";
$testPassword = "sale123";
$productionHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Test Password: <strong>$testPassword</strong><br>";
echo "Production Hash: <code>$productionHash</code><br>";

// Test password verification
$isValid = password_verify($testPassword, $productionHash);
echo "Password Verify Result: " . ($isValid ? "✅ VALID" : "❌ INVALID") . "<br>";

// Check functions.php for password functions
echo "<h3>4. Password Functions Check</h3>";
if (function_exists('verifyPassword')) {
    echo "✅ verifyPassword() function exists<br>";
    $customVerify = verifyPassword($testPassword, $productionHash);
    echo "Custom verifyPassword() result: " . ($customVerify ? "✅ VALID" : "❌ INVALID") . "<br>";
} else {
    echo "❌ verifyPassword() function not found<br>";
}

if (function_exists('hashPassword')) {
    echo "✅ hashPassword() function exists<br>";
    $newHash = hashPassword($testPassword);
    echo "New hash generated: <code>$newHash</code><br>";
    $newHashVerify = password_verify($testPassword, $newHash);
    echo "New hash verify: " . ($newHashVerify ? "✅ VALID" : "❌ INVALID") . "<br>";
} else {
    echo "❌ hashPassword() function not found<br>";
}

// Test User class authentication
echo "<h3>5. User Class Authentication Test</h3>";
if (!empty($testUsers)) {
    $userModel = new User();
    
    foreach ($testUsers as $testUser) {
        echo "<h4>Testing {$testUser['Username']}</h4>";
        
        // Test with different passwords
        $passwords = ['sale123', 'supervisor123', 'admin123'];
        
        foreach ($passwords as $pwd) {
            $authResult = $userModel->authenticate($testUser['Username'], $pwd);
            if ($authResult) {
                echo "✅ <strong>{$testUser['Username']} + $pwd = SUCCESS</strong><br>";
                echo "User Role: {$authResult['Role']}<br>";
                break;
            } else {
                echo "❌ {$testUser['Username']} + $pwd = FAILED<br>";
            }
        }
        echo "<br>";
    }
}

// Test actual login API simulation
echo "<h3>6. Login API Simulation</h3>";
if (!empty($testUsers)) {
    foreach ($testUsers as $testUser) {
        $username = $testUser['Username'];
        $password = ($testUser['Role'] === 'Supervisor') ? 'supervisor123' : 'sale123';
        
        echo "<h4>Simulating login: $username / $password</h4>";
        
        // Simulate the User class authentication
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            echo "✅ <strong>LOGIN SUCCESS</strong><br>";
            echo "User ID: {$user['id']}<br>";
            echo "Role: {$user['Role']}<br>";
            echo "Status: {$user['Status']}<br>";
        } else {
            echo "❌ <strong>LOGIN FAILED</strong><br>";
            
            // Debug: Check individual steps
            $userRecord = $userModel->findByUsername($username);
            if (!$userRecord) {
                echo "- User not found in database<br>";
            } else {
                echo "- User found: {$userRecord['Username']}<br>";
                echo "- Status: {$userRecord['Status']}<br>";
                echo "- Password hash: " . substr($userRecord['Password'], 0, 20) . "...<br>";
                
                $passwordCheck = verifyPassword($password, $userRecord['Password']);
                echo "- Password verification: " . ($passwordCheck ? "✅ PASS" : "❌ FAIL") . "<br>";
            }
        }
        echo "<br>";
    }
}

echo "<h3>7. Recommendations</h3>";
if (empty($testUsers)) {
    echo "🔥 <strong>URGENT:</strong> Import mockup_data_fixed.sql to create test users<br>";
}
echo "💡 Check if database name is 'crm_system'<br>";
echo "💡 Verify XAMPP MySQL is running<br>";
echo "💡 Check includes/config.php for correct database credentials<br>";
?>