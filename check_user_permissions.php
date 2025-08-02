<?php
/**
 * Check Current User Permissions
 * Debug tool to see current user role and permissions
 */

session_start();
header('Content-Type: text/html; charset=utf-8');

require_once 'includes/permissions.php';
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check User Permissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .permission-granted { color: #28a745; }
        .permission-denied { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>🔍 Current User Permissions Check</h2>
        
        <?php
        if (isset($_SESSION['user_role']) && isset($_SESSION['username'])) {
            echo "<div class='alert alert-info'>";
            echo "<strong>Current Session:</strong><br>";
            echo "Username: " . htmlspecialchars($_SESSION['username']) . "<br>";
            echo "Role: " . htmlspecialchars($_SESSION['user_role']) . "<br>";
            if (isset($_SESSION['user_id'])) {
                echo "User ID: " . htmlspecialchars($_SESSION['user_id']) . "<br>";
            }
            echo "</div>";
            
            // Check specific permissions
            $permissionsToCheck = [
                'dashboard',
                'customer_list', 
                'waiting_basket',
                'distribution_basket',
                'supervisor_dashboard',
                'intelligence_system',
                'user_management',
                'import_customers',
                'sales_performance'
            ];
            
            echo "<h4>Permission Status:</h4>";
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>Permission</th><th>Status</th></tr></thead>";
            echo "<tbody>";
            
            foreach ($permissionsToCheck as $permission) {
                $hasPermission = Permissions::hasPermission($permission);
                $statusClass = $hasPermission ? 'permission-granted' : 'permission-denied';
                $statusText = $hasPermission ? '✅ Granted' : '❌ Denied';
                
                echo "<tr>";
                echo "<td><code>$permission</code></td>";
                echo "<td class='$statusClass'><strong>$statusText</strong></td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
            
        } else {
            echo "<div class='alert alert-warning'>";
            echo "<strong>No Active Session</strong><br>";
            echo "You are not logged in or session has expired.";
            echo "</div>";
        }
        
        // Show all users in database for reference
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            echo "<h4>All Users in Database:</h4>";
            $usersSql = "SELECT UserID, Username, Role, Status FROM users ORDER BY Role, Username";
            $stmt = $pdo->query($usersSql);
            
            echo "<table class='table table-sm'>";
            echo "<thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>";
            echo "<tbody>";
            
            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $statusBadge = $user['Status'] === 'active' ? 'bg-success' : 'bg-danger';
                echo "<tr>";
                echo "<td>" . $user['UserID'] . "</td>";
                echo "<td><strong>" . htmlspecialchars($user['Username']) . "</strong></td>";
                echo "<td><span class='badge bg-primary'>" . htmlspecialchars($user['Role']) . "</span></td>";
                echo "<td><span class='badge $statusBadge'>" . htmlspecialchars($user['Status']) . "</span></td>";
                echo "<td>";
                if ($user['Role'] !== 'admin') {
                    echo "<a href='?action=promote&user_id=" . $user['UserID'] . "' class='btn btn-sm btn-warning'>Promote to Admin</a>";
                }
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        // Handle promotion action
        if (isset($_GET['action']) && $_GET['action'] === 'promote' && isset($_GET['user_id'])) {
            try {
                $userId = (int)$_GET['user_id'];
                $updateSql = "UPDATE users SET Role = 'admin' WHERE UserID = ?";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([$userId]);
                
                echo "<div class='alert alert-success'>✅ User promoted to admin successfully! Please refresh the page.</div>";
                echo "<script>setTimeout(function(){ window.location.href = 'check_user_permissions.php'; }, 2000);</script>";
                
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>❌ Error promoting user: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        ?>
        
        <div class="mt-4">
            <h5>Quick Actions:</h5>
            <a href="pages/login.php" class="btn btn-primary">Go to Login</a>
            <a href="admin/waiting_basket.php" class="btn btn-success">Test Waiting Basket</a>
            <a href="admin/distribution_basket.php" class="btn btn-warning">Test Distribution Basket</a>
            <a href="index.php" class="btn btn-secondary">Go to Dashboard</a>
        </div>
        
        <div class="mt-4 alert alert-info">
            <h6>Permission Requirements:</h6>
            <ul>
                <li><strong>Distribution Basket:</strong> admin role only</li>
                <li><strong>Waiting Basket:</strong> admin role only</li>
                <li><strong>Supervisor Dashboard:</strong> admin or supervisor role</li>
                <li><strong>User Management:</strong> admin role only</li>
            </ul>
        </div>
    </div>
</body>
</html>