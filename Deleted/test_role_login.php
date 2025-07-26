<?php
/**
 * Quick Role Login Test
 * Direct session setting for testing roles
 */

if (!isset($_SESSION)) session_start();

$role = $_GET['role'] ?? '';

if ($role) {
    // Set session directly for testing
    switch ($role) {
        case 'admin':
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['user_role'] = 'Admin';
            $_SESSION['first_name'] = 'ผู้ดูแลระบบ';
            $_SESSION['last_name'] = 'หลัก';
            break;
        case 'supervisor':
            $_SESSION['user_id'] = 2;
            $_SESSION['username'] = 'supervisor';
            $_SESSION['user_role'] = 'Supervisor';
            $_SESSION['first_name'] = 'หัวหน้า';
            $_SESSION['last_name'] = 'ขาย';
            break;
        case 'sale':
            $_SESSION['user_id'] = 4;
            $_SESSION['username'] = 'sale';
            $_SESSION['user_role'] = 'Sales';
            $_SESSION['first_name'] = 'พนักงาน';
            $_SESSION['last_name'] = 'ขาย';
            break;
        case 'manager':
            $_SESSION['user_id'] = 5;
            $_SESSION['username'] = 'manager';
            $_SESSION['user_role'] = 'Manager';
            $_SESSION['first_name'] = 'ผู้จัดการ';
            $_SESSION['last_name'] = 'ฝ่าย';
            break;
    }
    
    // Redirect to dashboard
    header('Location: pages/dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Role Test</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            margin: 10px; 
            background: #007bff; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
        }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>🧪 Quick Role Test</h2>
    <p>Click to test each role directly:</p>
    
    <a href="?role=admin" class="btn">👑 Test Admin</a>
    <a href="?role=supervisor" class="btn">👔 Test Supervisor</a>
    <a href="?role=sale" class="btn">💼 Test Sales</a>
    <a href="?role=manager" class="btn">🏢 Test Manager</a>
    
    <hr>
    <p><strong>Current Session:</strong></p>
    <?php if (isset($_SESSION['user_id'])): ?>
        <ul>
            <li>User: <?= $_SESSION['username'] ?></li>
            <li>Role: <?= $_SESSION['user_role'] ?></li>
            <li>Name: <?= $_SESSION['first_name'] ?> <?= $_SESSION['last_name'] ?></li>
        </ul>
        <p><a href="pages/dashboard.php" class="btn">📊 Go to Dashboard</a></p>
        <p><a href="api/auth/logout.php" class="btn" style="background: #dc3545;">🚪 Logout</a></p>
    <?php else: ?>
        <p>No user logged in</p>
    <?php endif; ?>
</body>
</html>