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
            $pdo = $db->getConnection();
            
            $user = null;
            
            // Method 1: Try NEW table structure (lowercase columns)
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user) {
                    echo "<!-- Found user in NEW table -->";
                    $userSource = 'new';
                }
            } catch(Exception $e) {
                // New table doesn't exist or failed
            }
            
            // Method 2: Try OLD table structure (Capital columns) if new table failed
            if (!$user) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ? AND Status = 1");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        echo "<!-- Found user in OLD table -->";
                        $userSource = 'old';
                    }
                } catch(Exception $e) {
                    // Old table doesn't exist or failed
                }
            }
            
            if ($user) {
                $passwordMatch = false;
                $passwordField = ($userSource === 'old') ? 'Password' : 'password';
                $usernameField = ($userSource === 'old') ? 'Username' : 'username';
                $roleField = ($userSource === 'old') ? 'Role' : 'role';
                $emailField = ($userSource === 'old') ? 'Email' : 'email';
                
                // Check password (multiple methods)
                
                // Method 1: bcrypt/password_hash
                if (password_verify($password, $user[$passwordField])) {
                    $passwordMatch = true;
                }
                // Method 2: Laravel default hash for 'password'
                elseif ($user[$passwordField] === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' && $password === 'password') {
                    $passwordMatch = true;
                }
                // Method 3: Plain text
                elseif ($user[$passwordField] === $password) {
                    $passwordMatch = true;
                }
                // Method 4: MD5
                elseif ($user[$passwordField] === md5($password)) {
                    $passwordMatch = true;
                }
                
                if ($passwordMatch) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user[$usernameField];
                    $_SESSION['user_role'] = $user[$roleField]; // Use consistent session key
                    $_SESSION['email'] = $user[$emailField] ?? '';
                    $_SESSION['table_source'] = $userSource;
                    
                    // Redirect to dashboard
                    header('Location: pages/dashboard.php');
                    exit;
                } else {
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                    $error .= "<!-- Password: '" . htmlspecialchars($password) . "', Hash: '" . substr($user[$passwordField], 0, 20) . "...' -->";
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
            max-width: 450px;
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
        
        .help-text {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .credentials {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .credential-box {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .credential-box:hover {
            background: #dee2e6;
        }
        
        .credential-box strong {
            display: block;
            color: #495057;
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
            <div class="error"><?= $error ?></div>
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
            <strong>ลองข้อมูลเข้าสู่ระบบเหล่านี้:</strong>
            
            <div class="credentials">
                <div class="credential-box" onclick="fillLogin('admin', 'admin123')">
                    <strong>Admin</strong>
                    <div>admin / admin123</div>
                </div>
                
                <div class="credential-box" onclick="fillLogin('manager', 'manager123')">
                    <strong>Manager</strong>
                    <div>manager / manager123</div>
                </div>
                
                <div class="credential-box" onclick="fillLogin('sales1', 'sales123')">
                    <strong>Sales</strong>
                    <div>sales1 / sales123</div>
                </div>
                
                <div class="credential-box" onclick="testExistingUsers()">
                    <strong>🔍 Test All Users</strong>
                    <div>Click to test all accounts</div>
                </div>
                
            </div>
        </div>
        
        <div class="debug-links">
            <a href="check_users_and_create.php">👥 ตรวจสอบผู้ใช้</a>
            <a href="debug_customer_detail.php">🔍 Debug Customer Detail</a>
            <a href="test_all_systems.php">🧪 Test All Systems</a>
        </div>
    </div>
    
    <script>
        function fillLogin(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
        
        async function testExistingUsers() {
            const users = [
                {username: 'admin', password: 'admin123'},
                {username: 'manager', password: 'manager123'},
                {username: 'sales1', password: 'sales123'}
            ];
            
            let results = 'Test Results:\n';
            
            for (const user of users) {
                try {
                    const formData = new FormData();
                    formData.append('username', user.username);
                    formData.append('password', user.password);
                    
                    const response = await fetch('universal_login.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.redirected) {
                        results += `✅ ${user.username}: Login Success\n`;
                    } else {
                        results += `❌ ${user.username}: Login Failed\n`;
                    }
                } catch (error) {
                    results += `❌ ${user.username}: Error - ${error.message}\n`;
                }
            }
            
            alert(results);
        }
    </script>
</body>
</html>