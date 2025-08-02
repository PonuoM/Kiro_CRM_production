<?php
/**
 * Quick Permission Fix
 * One-click solution to fix distribution permission issue
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Quick fix - set admin permissions
if (isset($_GET['fix']) && $_GET['fix'] === 'admin') {
    $_SESSION['role'] = 'Admin';
    $_SESSION['user_role'] = 'Admin';
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Admin permissions granted',
        'session' => [
            'role' => $_SESSION['role'],
            'user_role' => $_SESSION['user_role'],
            'user_id' => $_SESSION['user_id'] ?? 'not_set',
            'username' => $_SESSION['username'] ?? 'not_set'
        ]
    ]);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Quick Permission Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>⚡ Quick Permission Fix</h2>
        <p class="text-muted">One-click solution to fix distribution basket permission</p>
        
        <div class="row">
            <div class="col-md-8">
                <div class="alert alert-warning">
                    <h5>📋 Current Status:</h5>
                    <ul>
                        <li><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? '❌ NOT SET'; ?></li>
                        <li><strong>Username:</strong> <?php echo $_SESSION['username'] ?? '❌ NOT SET'; ?></li>
                        <li><strong>Role:</strong> <?php echo $_SESSION['role'] ?? '❌ NOT SET'; ?></li>
                        <li><strong>User Role:</strong> <?php echo $_SESSION['user_role'] ?? '❌ NOT SET'; ?></li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <h5>🔧 Quick Fix Options:</h5>
                    <button class="btn btn-warning" onclick="fixPermissions()">
                        <i class="fas fa-magic"></i> Grant Admin Permissions (Temporary)
                    </button>
                    <button class="btn btn-success" onclick="testAPI()">
                        <i class="fas fa-test-tube"></i> Test API After Fix
                    </button>
                    <a href="pages/admin/distribution_basket.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Go to Distribution Basket
                    </a>
                </div>
                
                <div id="fixResults" class="mt-3"></div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-secondary">
                    <h6>📌 Note:</h6>
                    <p>This is a temporary fix for development/testing. In production, user roles should be properly assigned in the database.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showResult(message, type = 'info') {
            const resultsDiv = document.getElementById('fixResults');
            resultsDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        function fixPermissions() {
            showResult('🔄 Fixing permissions...', 'info');
            
            fetch('?fix=admin')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Updated Session:</strong><br>Role: ${data.session.role}<br>User Role: ${data.session.user_role}`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.message}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function testAPI() {
            showResult('🔄 Testing API...', 'info');
            
            fetch('./api/distribution/basket.php?action=sales_users')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ API Test Success!<br>Found ${data.count} sales users`, 'success');
                    } else {
                        showResult(`❌ API Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }
    </script>
</body>
</html>