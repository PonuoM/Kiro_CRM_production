<?php
/**
 * Fix Distribution Permission
 * Grant distribution_basket permission to current user
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once 'config/database.php';
require_once 'includes/permissions.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Fix Distribution Permission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🔧 Fix Distribution Permission</h2>
        
        <?php
        if (!isset($_SESSION['user_id'])) {
            echo '<div class="alert alert-warning">ยังไม่ได้ล็อกอิน กรุณาล็อกอินก่อน</div>';
            echo '<a href="pages/login.php" class="btn btn-primary">ไปหน้าล็อกอิน</a>';
        } else {
            echo '<div class="alert alert-info">';
            echo "<strong>Current User:</strong> {$_SESSION['username']}<br>";
            echo "<strong>User ID:</strong> {$_SESSION['user_id']}<br>";
            echo "<strong>Role:</strong> {$_SESSION['role']}";
            echo '</div>';
            
            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection();
                
                // Check current permissions
                echo '<h4>📋 Current Permissions Check:</h4>';
                
                $hasDistributionPermission = Permissions::hasPermission('distribution_basket');
                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<div class="alert alert-' . ($hasDistributionPermission ? 'success' : 'danger') . '">';
                echo '<strong>Distribution Basket Permission:</strong> ' . ($hasDistributionPermission ? '✅ มี' : '❌ ไม่มี');
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                // Show user details
                $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ?");
                $stmt->execute([$_SESSION['username']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo '<h4>👤 User Details:</h4>';
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-bordered">';
                    echo "<tr><th>ID</th><td>{$user['id']}</td></tr>";
                    echo "<tr><th>Username</th><td>{$user['Username']}</td></tr>";
                    echo "<tr><th>Name</th><td>{$user['FirstName']} {$user['LastName']}</td></tr>";
                    echo "<tr><th>Role</th><td>{$user['Role']}</td></tr>";
                    echo "<tr><th>Status</th><td>" . ($user['Status'] ? 'Active' : 'Inactive') . "</td></tr>";
                    echo '</table>';
                    echo '</div>';
                    
                    // Action buttons
                    echo '<h4>🛠️ Fix Actions:</h4>';
                    
                    if ($_POST['action'] ?? '' === 'grant_admin') {
                        // Grant admin role
                        $stmt = $pdo->prepare("UPDATE users SET Role = 'Admin' WHERE Username = ?");
                        $stmt->execute([$_SESSION['username']]);
                        
                        // Update session
                        $_SESSION['role'] = 'Admin';
                        
                        echo '<div class="alert alert-success">✅ Updated user role to Admin. Please refresh the page.</div>';
                        echo '<meta http-equiv="refresh" content="2">';
                    } elseif ($_POST['action'] ?? '' === 'test_permission') {
                        // Test permission after changes
                        echo '<div class="alert alert-info">🔄 Testing permission...</div>';
                        $hasPermission = Permissions::hasPermission('distribution_basket');
                        echo '<div class="alert alert-' . ($hasPermission ? 'success' : 'danger') . '">';
                        echo 'Distribution Basket Permission: ' . ($hasPermission ? '✅ มี' : '❌ ไม่มี');
                        echo '</div>';
                    }
                    
                    if (!$hasDistributionPermission) {
                        echo '<form method="POST" style="display: inline;">';
                        echo '<input type="hidden" name="action" value="grant_admin">';
                        echo '<button type="submit" class="btn btn-warning" onclick="return confirm(\'แน่ใจหรือไม่ที่จะเปลี่ยนเป็น Admin?\')">เปลี่ยนเป็น Admin Role</button>';
                        echo '</form> ';
                    }
                    
                    echo '<form method="POST" style="display: inline;">';
                    echo '<input type="hidden" name="action" value="test_permission">';
                    echo '<button type="submit" class="btn btn-info">ทดสอบสิทธิ์</button>';
                    echo '</form> ';
                    
                    echo '<a href="test_sales_api_fixed.php" class="btn btn-success">ทดสอบ API</a> ';
                    echo '<a href="pages/admin/distribution_basket.php" class="btn btn-primary">ไป Distribution Basket</a>';
                    
                } else {
                    echo '<div class="alert alert-danger">ไม่พบข้อมูลผู้ใช้</div>';
                }
                
                // Show all admin users
                echo '<h4>👨‍💼 Admin Users in System:</h4>';
                $stmt = $pdo->prepare("SELECT Username, FirstName, LastName, Role, Status FROM users WHERE Role = 'Admin'");
                $stmt->execute();
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($admins) > 0) {
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped">';
                    echo '<thead><tr><th>Username</th><th>Name</th><th>Role</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($admins as $admin) {
                        $statusText = $admin['Status'] ? 'Active' : 'Inactive';
                        $statusClass = $admin['Status'] ? 'text-success' : 'text-danger';
                        echo "<tr>";
                        echo "<td><strong>{$admin['Username']}</strong></td>";
                        echo "<td>{$admin['FirstName']} {$admin['LastName']}</td>";
                        echo "<td><span class=\"badge bg-primary\">{$admin['Role']}</span></td>";
                        echo "<td><span class=\"{$statusClass}\">{$statusText}</span></td>";
                        echo "</tr>";
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-warning">ไม่มี Admin users ในระบบ</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>
</body>
</html>