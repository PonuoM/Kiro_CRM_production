<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='universal_login.php'>Login</a>";
    exit;
}

// Role-based permissions configuration
$rolePermissions = [
    'admin' => [
        'dashboard' => true,
        'customer_list' => true,
        'customer_detail' => true,
        'customer_edit' => true,
        'user_management' => true,
        'import_customers' => true,
        'call_history' => true,
        'order_history' => true,
        'sales_performance' => true,
        'daily_tasks' => true,
        'view_all_data' => true
    ],
    'manager' => [
        'dashboard' => true,
        'customer_list' => true,
        'customer_detail' => true,
        'customer_edit' => true,
        'user_management' => false,
        'import_customers' => false,
        'call_history' => true,
        'order_history' => true,
        'sales_performance' => true,
        'daily_tasks' => true,
        'view_all_data' => true
    ],
    'sales' => [
        'dashboard' => true,
        'customer_list' => true,
        'customer_detail' => true,
        'customer_edit' => false,
        'user_management' => false,
        'import_customers' => false,
        'call_history' => true,
        'order_history' => false,
        'sales_performance' => false,
        'daily_tasks' => true,
        'view_all_data' => false  // Only own assigned customers
    ]
];

$currentRole = $_SESSION['role'];
$currentUser = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>🔐 Role Permissions Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .user-info { background: #e9ecef; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .permissions { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .role-section { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .role-section h3 { margin: 0 0 10px 0; color: #333; }
        .permission { margin: 5px 0; padding: 5px; }
        .permission.allowed { background: #d4edda; color: #155724; }
        .permission.denied { background: #f8d7da; color: #721c24; }
        .current-role { border: 3px solid #007bff; background: #f8f9fa; }
        .page-list { margin-top: 20px; }
        .page-item { margin: 5px 0; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .page-item.accessible { background: #d4edda; }
        .page-item.restricted { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>🔐 Role-Based Access Control Check</h1>
    
    <div class="user-info">
        <strong>Current User:</strong> <?= $currentUser ?><br>
        <strong>Current Role:</strong> <?= $currentRole ?><br>
        <strong>Session Data:</strong> <?= json_encode($_SESSION) ?>
    </div>
    
    <h2>Role Permissions Matrix</h2>
    <div class="permissions">
        <?php foreach ($rolePermissions as $role => $permissions): ?>
            <div class="role-section <?= $role === $currentRole ? 'current-role' : '' ?>">
                <h3><?= ucfirst($role) ?> <?= $role === $currentRole ? '(Current)' : '' ?></h3>
                <?php foreach ($permissions as $permission => $allowed): ?>
                    <div class="permission <?= $allowed ? 'allowed' : 'denied' ?>">
                        <?= $allowed ? '✅' : '❌' ?> <?= $permission ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2>Available Pages & Access</h2>
    <div class="page-list">
        <?php
        $pages = [
            'dashboard.php' => 'dashboard',
            'customer_list.php' => 'customer_list', 
            'customer_detail.php' => 'customer_detail',
            'daily_tasks.php' => 'daily_tasks',
            'call_history_demo.php' => 'call_history',
            'order_history_demo.php' => 'order_history',
            'sales_performance.php' => 'sales_performance',
            'admin/user_management.php' => 'user_management',
            'admin/import_customers.php' => 'import_customers'
        ];
        
        $userPermissions = $rolePermissions[$currentRole] ?? [];
        
        foreach ($pages as $page => $permission):
            $hasAccess = $userPermissions[$permission] ?? false;
            $pageExists = file_exists($page);
        ?>
            <div class="page-item <?= $hasAccess ? 'accessible' : 'restricted' ?>">
                <strong><?= $page ?></strong>
                <?= $hasAccess ? '✅ Accessible' : '❌ Restricted' ?>
                <?= $pageExists ? '' : ' (⚠️ File not found)' ?>
                <?php if ($hasAccess && $pageExists): ?>
                    <a href="<?= $page ?>" target="_blank" style="margin-left: 10px;">🔗 Open</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <h2>Recommendations</h2>
    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <h4>Issues Found:</h4>
        <ul>
            <li>❌ <strong>No Role-Based Access Control:</strong> All users can access all pages</li>
            <li>❌ <strong>Missing Pages:</strong> Some referenced pages don't exist</li>
            <li>❌ <strong>Data Filtering:</strong> Sales users see all customer data instead of only assigned customers</li>
        </ul>
        
        <h4>Should Implement:</h4>
        <ul>
            <li>✅ <strong>Page-level access control</strong> in each PHP file</li>
            <li>✅ <strong>Data filtering by role:</strong> Sales see only assigned customers</li>
            <li>✅ <strong>UI elements hiding:</strong> Hide features based on permissions</li>
            <li>✅ <strong>API-level permissions:</strong> Restrict API access by role</li>
        </ul>
    </div>
    
    <p><a href="universal_login.php">🔙 Back to Login</a> | <a href="pages/dashboard.php">📊 Go to Dashboard</a></p>
</body>
</html>