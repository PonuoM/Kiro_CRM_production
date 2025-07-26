<?php
/**
 * Login Page
 * User authentication interface
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Generate CSRF token (with error handling)
try {
    $csrf_token = generateCSRFToken();
    error_log("Login Page CSRF Debug - Generated: " . $csrf_token);
    error_log("Login Page CSRF Debug - Session ID: " . session_id());
} catch (Exception $e) {
    error_log("CSRF token generation failed: " . $e->getMessage());
    $csrf_token = bin2hex(random_bytes(32)); // Fallback
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - CRM System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group.error input {
            border-color: #e74c3c;
        }
        
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .login-btn {
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
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
        }
        
        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>เข้าสู่ระบบ</h1>
            <p>CRM System</p>
        </div>
        
        <div id="alert" class="alert"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                <div class="error-message" id="username-error"></div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <div class="error-message" id="password-error"></div>
            </div>
            
            <button type="submit" class="login-btn" id="loginBtn">เข้าสู่ระบบ</button>
            
            <div class="loading" id="loading"></div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            const alert = document.getElementById('alert');
            
            // Form validation
            function validateForm() {
                let isValid = true;
                
                // Clear previous errors
                document.querySelectorAll('.form-group').forEach(group => {
                    group.classList.remove('error');
                });
                document.querySelectorAll('.error-message').forEach(error => {
                    error.style.display = 'none';
                });
                
                // Validate username
                const username = document.getElementById('username').value.trim();
                if (!username) {
                    showFieldError('username', 'กรุณากรอก Username');
                    isValid = false;
                } else if (username.length < 3) {
                    showFieldError('username', 'Username ต้องมีอย่างน้อย 3 ตัวอักษร');
                    isValid = false;
                }
                
                // Validate password
                const password = document.getElementById('password').value;
                if (!password) {
                    showFieldError('password', 'กรุณากรอก Password');
                    isValid = false;
                } else if (password.length < 6) {
                    showFieldError('password', 'Password ต้องมีอย่างน้อย 6 ตัวอักษร');
                    isValid = false;
                }
                
                return isValid;
            }
            
            function showFieldError(fieldName, message) {
                const field = document.getElementById(fieldName);
                const errorElement = document.getElementById(fieldName + '-error');
                
                field.parentElement.classList.add('error');
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
            
            function showAlert(message, type = 'error') {
                alert.textContent = message;
                alert.className = 'alert ' + type;
                alert.style.display = 'block';
                
                // Auto hide after 5 seconds
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            }
            
            function setLoading(isLoading) {
                if (isLoading) {
                    loginBtn.disabled = true;
                    loginBtn.textContent = 'กำลังเข้าสู่ระบบ...';
                    loading.style.display = 'block';
                } else {
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'เข้าสู่ระบบ';
                    loading.style.display = 'none';
                }
            }
            
            // Handle form submission
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (!validateForm()) {
                    return;
                }
                
                setLoading(true);
                alert.style.display = 'none';
                
                try {
                    const formData = {
                        username: document.getElementById('username').value.trim(),
                        password: document.getElementById('password').value,
                        csrf_token: '<?php echo $csrf_token; ?>'
                    };
                    
                    const response = await fetch('../api/auth/login.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert(result.message, 'success');
                        
                        // Debug redirect path
                        console.log('Login success! Redirect to:', result.redirect || 'dashboard.php');
                        console.log('Current location:', window.location.href);
                        
                        // Redirect after short delay
                        setTimeout(() => {
                            const redirectUrl = result.redirect || 'dashboard.php';
                            console.log('Redirecting to:', redirectUrl);
                            // Ensure full path for dashboard
                            window.location.href = redirectUrl.includes('/') ? redirectUrl : 'dashboard.php';
                        }, 1000);
                    } else {
                        showAlert(result.message, 'error');
                        setLoading(false);
                    }
                    
                } catch (error) {
                    console.error('Login error:', error);
                    showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง', 'error');
                    setLoading(false);
                }
            });
            
            // Clear errors on input
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    this.parentElement.classList.remove('error');
                    const errorElement = document.getElementById(this.id + '-error');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>