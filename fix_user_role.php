<?php
/**
 * Quick Fix: Update User Role to Admin
 * Emergency tool to fix user permissions
 */

header('Content-Type: text/html; charset=utf-8');

require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix User Role</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🔧 Fix User Role to Admin</h2>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            if (isset($_POST['fix_role'])) {
                $username = $_POST['username'];
                
                // Update user role to admin
                $updateSql = "UPDATE users SET Role = 'admin' WHERE Username = ?";
                $stmt = $pdo->prepare($updateSql);
                $result = $stmt->execute([$username]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo "<div class='alert alert-success'>";
                    echo "<h4>✅ Success!</h4>";
                    echo "<p>User <strong>" . htmlspecialchars($username) . "</strong> has been promoted to admin role.</p>";
                    echo "<p>You can now access the distribution basket and other admin features.</p>";
                    echo "<p><strong>Next steps:</strong></p>";
                    echo "<ol>";
                    echo "<li>Go to <a href='pages/login.php'>Login Page</a> and log in again</li>";
                    echo "<li>Or refresh your browser if already logged in</li>";
                    echo "<li>Try accessing <a href='admin/distribution_basket.php'>Distribution Basket</a></li>";
                    echo "</ol>";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-warning'>";
                    echo "<h4>⚠️ No User Found</h4>";
                    echo "<p>No user found with username: <strong>" . htmlspecialchars($username) . "</strong></p>";
                    echo "</div>";
                }
            }
            
            // Show current users
            echo "<h4>Current Users:</h4>";
            $usersSql = "SELECT UserID, Username, Role, Status FROM users ORDER BY Username";
            $stmt = $pdo->query($usersSql);
            
            echo "<table class='table table-striped'>";
            echo "<thead><tr><th>ID</th><th>Username</th><th>Current Role</th><th>Status</th></tr></thead>";
            echo "<tbody>";
            
            while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $roleBadge = $user['Role'] === 'admin' ? 'bg-success' : ($user['Role'] === 'supervisor' ? 'bg-warning' : 'bg-info');
                $statusBadge = $user['Status'] === 'active' ? 'bg-success' : 'bg-danger';
                
                echo "<tr>";
                echo "<td>" . $user['UserID'] . "</td>";
                echo "<td><strong>" . htmlspecialchars($user['Username']) . "</strong></td>";
                echo "<td><span class='badge $roleBadge'>" . htmlspecialchars($user['Role']) . "</span></td>";
                echo "<td><span class='badge $statusBadge'>" . htmlspecialchars($user['Status']) . "</span></td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
            
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Database Error</h4>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Promote User to Admin</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username to promote:</label>
                        <input type="text" class="form-control" id="username" name="username" required 
                               placeholder="Enter username">
                        <div class="form-text">Enter the exact username from the table above</div>
                    </div>
                    <button type="submit" name="fix_role" class="btn btn-primary">
                        <i class="fas fa-user-shield"></i> Promote to Admin
                    </button>
                </form>
            </div>
        </div>
        
        <div class="mt-4 alert alert-info">
            <h6>📋 What this does:</h6>
            <ul>
                <li>Changes the selected user's role to 'admin'</li>
                <li>Grants access to distribution basket, waiting basket, and all admin features</li>
                <li>The user will need to log in again to get the new permissions</li>
            </ul>
        </div>
        
        <div class="mt-3">
            <a href="check_user_permissions.php" class="btn btn-secondary">Check Permissions</a>
            <a href="pages/login.php" class="btn btn-primary">Go to Login</a>
        </div>
    </div>
</body>
</html>