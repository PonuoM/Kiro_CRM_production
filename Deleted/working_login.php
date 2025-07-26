<?php
session_start();

// Include database
require_once 'config/database.php';

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if ($username && $password) {
        try {
            $db = Database::getInstance();
            
            // Get user from database (use correct column names with capitals)
            $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE Username = ? AND Status = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Check password using secure bcrypt hash verification
                $passwordMatch = false;
                
                // Primary method: bcrypt/password_hash verification (recommended)
                if (password_verify($password, $user['Password'])) {
                    $passwordMatch = true;
                }
                // Fallback for emergency access (remove in production)
                elseif ($user['Password'] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' && $password === 'password') {
                    $passwordMatch = true;
                }
                
                if ($passwordMatch) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['user_role'] = $user['Role']; // Use consistent session key
                    $_SESSION['email'] = $user['Email'];
                    
                    // Redirect to dashboard
                    header('Location: pages/dashboard.php');
                    exit;
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            } else {
                $error = 'ไม่พบผู้ใช้ หรือ บัญชีถูกระงับ';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    } else {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    }
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - CRM System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            transition: border-color 0.3s;
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
            transition: transform 0.2s;
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
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }
        
        .help-text {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .debug-links {
            margin-top: 20px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .debug-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 CRM System</h1>
            <p>เข้าสู่ระบบ</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้:</label>
                <input type="text" id="username" name="username" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
        </form>
        
        <div class="help-text">
            <strong>ข้อมูลเข้าสู่ระบบเริ่มต้น:</strong><br>
            <strong>ชื่อผู้ใช้:</strong> admin<br>
            <strong>รหัสผ่าน:</strong> admin123
        </div>
        
        <div class="debug-links">
            <a href="check_users.php">👥 ตรวจสอบผู้ใช้</a>
            <a href="test_tables.php">🔍 ตรวจสอบฐานข้อมูล</a>
        </div>
    </div>
</body>
</html>