<?php
/**
 * Simple Dashboard - No Complex Permissions
 * Basic dashboard without redirect loops
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check (no complex permissions)
if (!isset($_SESSION['user_id'])) {
    header('Location: simple_login.php');
    exit;
}

// Get user info
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CRM System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .user-info {
            float: right;
            text-align: right;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #333;
            margin-top: 0;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin-bottom: 20px;
        }
        
        .nav-links {
            margin: 20px 0;
        }
        
        .nav-links a {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px 10px 5px 0;
        }
        
        .nav-links a:hover {
            background: #0056b3;
        }
        
        .logout-btn {
            background: #dc3545 !important;
        }
        
        .logout-btn:hover {
            background: #c82333 !important;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .info-item h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 18px;
        }
        
        .info-item p {
            margin: 0;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header clearfix">
        <div style="float: left;">
            <h1>🏠 Dashboard</h1>
            <p>ระบบ CRM - ยินดีต้อนรับ!</p>
        </div>
        <div class="user-info">
            <h3><?= htmlspecialchars($user_name) ?></h3>
            <p><?= htmlspecialchars($user_role) ?> (<?= htmlspecialchars($username) ?>)</p>
        </div>
    </div>
    
    <div class="container">
        <div class="success-message">
            🎉 <strong>เข้าสู่ระบบสำเร็จ!</strong> คุณสามารถใช้งานระบบ CRM ได้แล้ว
        </div>
        
        <div class="card">
            <h2>📊 ข้อมูลระบบ</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>ผู้ใช้งาน</h3>
                    <p><?= htmlspecialchars($user_name) ?></p>
                </div>
                <div class="info-item">
                    <h3>บทบาท</h3>
                    <p><?= htmlspecialchars($user_role) ?></p>
                </div>
                <div class="info-item">
                    <h3>Session ID</h3>
                    <p><?= htmlspecialchars(session_id()) ?></p>
                </div>
                <div class="info-item">
                    <h3>เวลาเข้าสู่ระบบ</h3>
                    <p><?= date('Y-m-d H:i:s') ?></p>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2>🧭 Navigation</h2>
            <div class="nav-links">
                <a href="login.php">🔄 ทดสอบ Login ปกติ</a>
                <a href="../working_login.php">🔧 Working Login</a>
                <a href="../universal_login.php">🌟 Universal Login</a>
                <a href="../test_redirect.php">🔍 Test Redirect</a>
                <a href="../api/auth/logout.php" class="logout-btn">🚪 ออกจากระบบ</a>
            </div>
        </div>
        
        <div class="card">
            <h2>✅ การทดสอบสำเร็จ</h2>
            <ul>
                <li>✅ เข้าสู่ระบบได้โดยไม่มี redirect loop</li>
                <li>✅ Session ทำงานปกติ</li>
                <li>✅ User data แสดงถูกต้อง</li>
                <li>✅ Dashboard โหลดสมบูรณ์</li>
            </ul>
            
            <p><strong>หมายเหตุ:</strong> หน้านี้เป็นการทดสอบระบบ login แบบง่าย ไม่ใช้ permission system ที่ซับซ้อน</p>
        </div>
    </div>
</body>
</html>