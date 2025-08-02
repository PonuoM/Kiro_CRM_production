<?php
/**
 * Check Session Debug
 * Debug session variables and permission issues
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Session Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🔍 Session Debug</h2>
        
        <div class="row">
            <div class="col-md-6">
                <h4>📊 Current Session Data:</h4>
                <div class="alert alert-info">
                    <pre><?php print_r($_SESSION); ?></pre>
                </div>
            </div>
            <div class="col-md-6">
                <h4>🛠️ Fix Actions:</h4>
                
                <?php if (isset($_POST['action'])): ?>
                    <?php if ($_POST['action'] === 'fix_session'): ?>
                        <?php if (isset($_SESSION['role'])): ?>
                            <?php $_SESSION['user_role'] = $_SESSION['role']; ?>
                            <div class="alert alert-success">✅ เซ็ต user_role = <?php echo $_SESSION['role']; ?></div>
                        <?php endif; ?>
                    <?php elseif ($_POST['action'] === 'set_admin'): ?>
                        <?php 
                        $_SESSION['role'] = 'Admin';
                        $_SESSION['user_role'] = 'Admin';
                        ?>
                        <div class="alert alert-success">✅ เซ็ตเป็น Admin</div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="fix_session">
                    <button type="submit" class="btn btn-primary">Fix Session Variables</button>
                </form>
                
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="set_admin">
                    <button type="submit" class="btn btn-warning">Set as Admin (Temporary)</button>
                </form>
                
                <a href="test_sales_api_fixed.php" class="btn btn-success">Test API</a>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h4>🔧 Permission Test:</h4>
                <?php
                require_once 'includes/permissions.php';
                
                echo '<div class="table-responsive">';
                echo '<table class="table table-striped">';
                echo '<thead><tr><th>Permission</th><th>Status</th></tr></thead>';
                echo '<tbody>';
                
                $testPermissions = [
                    'dashboard',
                    'distribution_basket',
                    'waiting_basket',
                    'user_management',
                    'customer_list'
                ];
                
                foreach ($testPermissions as $perm) {
                    $hasPermission = Permissions::hasPermission($perm);
                    $statusClass = $hasPermission ? 'text-success' : 'text-danger';
                    $statusText = $hasPermission ? '✅ มี' : '❌ ไม่มี';
                    
                    echo "<tr>";
                    echo "<td><code>$perm</code></td>";
                    echo "<td><span class=\"$statusClass\">$statusText</span></td>";
                    echo "</tr>";
                }
                
                echo '</tbody></table>';
                echo '</div>';
                ?>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h4>📋 Session Requirements for Permissions:</h4>
                <div class="alert alert-info">
                    <strong>Required Session Variables:</strong><br>
                    - <code>$_SESSION['user_role']</code> (current: <?php echo $_SESSION['user_role'] ?? 'NOT SET'; ?>)<br>
                    - <code>$_SESSION['role']</code> (current: <?php echo $_SESSION['role'] ?? 'NOT SET'; ?>)<br>
                    - <code>$_SESSION['user_id']</code> (current: <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?>)<br>
                    - <code>$_SESSION['username']</code> (current: <?php echo $_SESSION['username'] ?? 'NOT SET'; ?>)
                </div>
            </div>
        </div>
    </div>
</body>
</html>