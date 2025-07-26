<?php
session_start();

// Simple login page for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Test login (admin/admin123)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['user_role'] = 'Admin'; // Use consistent session key
        
        header('Location: pages/dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Login</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h2>🔐 CRM System Login</h2>
    
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <p><strong>Default Login:</strong><br>
    Username: admin<br>
    Password: admin123</p>
    
    <hr>
    <p><a href="debug_login.php">🔍 Debug Login Issues</a></p>
</body>
</html>