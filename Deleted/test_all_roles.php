<?php
/**
 * Test All Role Logins
 * Test login functionality for all roles
 */

require_once 'includes/functions.php';
require_once 'includes/User.php';

if (!isset($_SESSION)) session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test All Roles - CRM System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .btn {
            background: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #333; }
    </style>
</head>
<body>

<h1>🧪 Test All Role Logins</h1>

<?php
$test_users = [
    ['username' => 'admin', 'password' => 'admin123', 'expected_role' => 'Admin'],
    ['username' => 'supervisor', 'password' => 'supervisor123', 'expected_role' => 'Supervisor'],
    ['username' => 'sale', 'password' => 'sale123', 'expected_role' => 'Sales'],
    ['username' => 'manager', 'password' => 'manager123', 'expected_role' => 'Manager'],
];

// Test login for each user
foreach ($test_users as $test_user) {
    echo "<div class='test-container'>";
    echo "<h3>Testing: {$test_user['username']} / {$test_user['password']} (Expected: {$test_user['expected_role']})</h3>";
    
    try {
        $userModel = new User();
        $user = $userModel->authenticate($test_user['username'], $test_user['password']);
        
        if ($user) {
            echo "<p class='success'>✅ Authentication: SUCCESS</p>";
            echo "<p class='info'>📊 User Data:</p>";
            echo "<ul>";
            echo "<li>ID: {$user['id']}</li>";
            echo "<li>Username: {$user['Username']}</li>";
            echo "<li>Role: {$user['Role']}</li>";
            echo "<li>Name: {$user['FirstName']} {$user['LastName']}</li>";
            echo "<li>Email: {$user['Email']}</li>";
            echo "</ul>";
            
            // Check if role matches expected
            if ($user['Role'] === $test_user['expected_role']) {
                echo "<p class='success'>✅ Role Match: CORRECT</p>";
            } else {
                echo "<p class='error'>❌ Role Mismatch: Expected {$test_user['expected_role']}, Got {$user['Role']}</p>";
            }
            
            // Test login URL for this user
            echo "<p><a href='pages/login.php' class='btn' target='_blank'>🔗 Test Login Page</a></p>";
            
        } else {
            echo "<p class='error'>❌ Authentication: FAILED</p>";
            echo "<p class='warning'>⚠️ User not found or password incorrect</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "</div>";
}
?>

<div class='test-container'>
    <h3>🔧 Quick Actions</h3>
    <a href="pages/login.php" class="btn">🔑 Login Page</a>
    <a href="check_all_users.php" class="btn btn-success">👥 Check All Users</a>
    <a href="pages/dashboard.php" class="btn btn-warning">📊 Dashboard</a>
    <a href="api/auth/logout.php" class="btn btn-danger">🚪 Logout</a>
</div>

<div class='test-container'>
    <h3>📋 Current Session Info</h3>
    <?php if (isset($_SESSION['user_id'])): ?>
        <p class='success'>✅ User logged in:</p>
        <ul>
            <li>User ID: <?= $_SESSION['user_id'] ?></li>
            <li>Username: <?= $_SESSION['username'] ?></li>
            <li>Role: <?= $_SESSION['user_role'] ?? 'Not set' ?></li>
            <li>Session ID: <?= session_id() ?></li>
        </ul>
    <?php else: ?>
        <p class='warning'>⚠️ No user logged in</p>
    <?php endif; ?>
</div>

<div class='test-container'>
    <h3>🎯 Expected Login Credentials</h3>
    <ul>
        <li><strong>admin</strong> / admin123 → Admin role</li>
        <li><strong>supervisor</strong> / supervisor123 → Supervisor role</li>
        <li><strong>sale</strong> / sale123 → Sales role</li>
        <li><strong>manager</strong> / manager123 → Manager role (if exists)</li>
    </ul>
</div>

</body>
</html>