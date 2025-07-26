<?php
/**
 * Simple Login Test - Bypass API Issues
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';

echo "<h2>🔑 Simple Login Test</h2>";

// Handle login form submission
if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    echo "<h3>🔍 Login Attempt</h3>";
    echo "Username: <strong>$username</strong><br>";
    echo "Password: <strong>" . str_repeat('*', strlen($password)) . "</strong><br><br>";
    
    try {
        // Use User class for authentication
        $userModel = new User();
        $user = $userModel->authenticate($username, $password);
        
        if ($user) {
            // Set session data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['user_role'] = $user['Role'];
            $_SESSION['first_name'] = $user['FirstName'];
            $_SESSION['last_name'] = $user['LastName'];
            $_SESSION['login_time'] = time();
            
            echo "✅ <strong>LOGIN SUCCESS!</strong><br>";
            echo "User ID: {$user['id']}<br>";
            echo "Username: {$user['Username']}<br>";
            echo "Role: {$user['Role']}<br>";
            echo "Name: {$user['FirstName']} {$user['LastName']}<br><br>";
            
            echo "<h3>📋 Session Data Set</h3>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            // Test isLoggedIn function
            $isLoggedIn = isLoggedIn();
            echo "isLoggedIn() result: " . ($isLoggedIn ? "✅ TRUE" : "❌ FALSE") . "<br><br>";
            
            if ($isLoggedIn) {
                echo "<h3>🚀 Success! Ready to Redirect</h3>";
                echo "<a href='pages/dashboard.php' class='btn btn-success'>📊 Go to Dashboard</a><br><br>";
                echo "<a href='pages/customer_list_demo.php' class='btn btn-info'>👥 Customer List</a><br><br>";
                echo "<a href='pages/daily_tasks_demo.php' class='btn btn-warning'>📅 Daily Tasks</a><br><br>";
                
                // Show logout option
                echo "<a href='?logout=1' class='btn btn-danger'>🚪 Logout</a><br>";
            }
            
        } else {
            echo "❌ <strong>LOGIN FAILED</strong><br>";
            echo "Invalid username or password<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_start();
    echo "✅ Logged out successfully!<br><br>";
}

// Show current session status
echo "<h3>📊 Current Session Status</h3>";
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
echo "Logged In: " . ($isLoggedIn ? "✅ YES" : "❌ NO") . "<br>";

if ($isLoggedIn) {
    echo "User: {$_SESSION['username']} ({$_SESSION['user_role']})<br>";
    echo "Name: {$_SESSION['first_name']} {$_SESSION['last_name']}<br>";
    echo "<a href='?logout=1'>🚪 Logout</a><br><br>";
} else {
    // Show login form
    echo "<h3>🔐 Login Form</h3>";
    ?>
    <style>
        .login-form { 
            max-width: 400px; 
            margin: 20px 0; 
            padding: 20px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            background: #f9f9f9;
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        .form-group input { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ccc; 
            border-radius: 3px; 
            box-sizing: border-box;
        }
        .btn { 
            padding: 10px 20px; 
            margin: 5px; 
            text-decoration: none; 
            border-radius: 5px; 
            display: inline-block;
            border: none;
            cursor: pointer;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .user-options { margin: 10px 0; }
        .user-options button { margin: 5px; }
    </style>
    
    <form method="post" class="login-form">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" value="sales01" required>
        </div>
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" value="sale123" required>
        </div>
        <button type="submit" class="btn btn-primary">🔑 Login</button>
    </form>
    
    <div class="user-options">
        <h4>🧪 Quick Login Options:</h4>
        <form method="post" style="display: inline;">
            <input type="hidden" name="username" value="admin">
            <input type="hidden" name="password" value="admin123">
            <button type="submit" class="btn btn-danger">👑 Admin Login</button>
        </form>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="username" value="supervisor01">
            <input type="hidden" name="password" value="supervisor123">
            <button type="submit" class="btn btn-warning">👨‍💼 Supervisor Login</button>
        </form>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="username" value="sales01">
            <input type="hidden" name="password" value="sale123">
            <button type="submit" class="btn btn-success">👤 Sales01 Login</button>
        </form>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="username" value="sales02">
            <input type="hidden" name="password" value="sale123">
            <button type="submit" class="btn btn-info">👤 Sales02 Login</button>
        </form>
    </div>
    <?php
}

echo "<h3>💡 Debug Info</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Data:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>