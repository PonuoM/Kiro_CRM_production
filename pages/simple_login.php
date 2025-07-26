<?php
/**
 * Simple Login Page - No Complex Permissions
 * Direct authentication without redirect loops
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/User.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            $userModel = new User();
            $user = $userModel->authenticate($username, $password);
            
            if ($user) {
                // Set session manually (no complex functions)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_role'] = $user['Role'];
                $_SESSION['first_name'] = $user['FirstName'];
                $_SESSION['last_name'] = $user['LastName'];
                $_SESSION['company_code'] = $user['CompanyCode'];
                
                // Redirect to simple dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Username หรือ Password ไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอก Username และ Password';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Login - CRM System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        .help-text {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Simple Login</h1>
            <p>CRM System</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
        </form>
        
        <div class="help-text">
            <strong>ทดสอบ:</strong> admin / admin123
        </div>
    </div>
</body>
</html>