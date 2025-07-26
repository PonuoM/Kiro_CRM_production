<?php
/**
 * Check Permissions System
 * Verify that all permission-related functionality works
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔐 Permissions Check</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .check { margin: 15px 0; padding: 15px; border-radius: 5px; }
        .pass { background: #d4edda; border-left: 5px solid #28a745; }
        .fail { background: #f8d7da; border-left: 5px solid #dc3545; }
        .warn { background: #fff3cd; border-left: 5px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 5px solid #17a2b8; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .method-test { margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 3px; }
    </style>
</head>
<body>

<div class="container">
<h1>🔐 Permissions System Check</h1>

<?php
// Check if session exists
echo "<div class='check info'>";
echo "<h3>Session Status</h3>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "<p>✅ Session started</p>";
} else {
    echo "<p>✅ Session already active</p>";
}

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? '❌ NOT SET') . "</p>";
echo "<p><strong>Username:</strong> " . ($_SESSION['username'] ?? '❌ NOT SET') . "</p>";
echo "<p><strong>User Role:</strong> " . ($_SESSION['user_role'] ?? '❌ NOT SET') . "</p>";

if (!isset($_SESSION['user_id'])) {
    echo "<div class='warn'>";
    echo "<h4>⚠️ No User Session Found</h4>";
    echo "<p>You need to login first. Setting test session...</p>";
    $_SESSION['user_id'] = 999;
    $_SESSION['username'] = 'test_user';
    $_SESSION['user_role'] = 'admin';
    echo "<p>✅ Test session created</p>";
    echo "</div>";
}
echo "</div>";

// Test Permissions class
echo "<div class='check'>";
echo "<h3>Permissions Class Test</h3>";

try {
    require_once 'includes/permissions.php';
    echo "<p class='pass'>✅ Permissions file loaded successfully</p>";
    
    if (class_exists('Permissions')) {
        echo "<p class='pass'>✅ Permissions class exists</p>";
        
        // Test each method
        $methods_to_test = [
            'getCurrentUser',
            'getCurrentRole', 
            'getCurrentUserId',
            'canViewAllData',
            'canViewTeamData',
            'getMenuItems'
        ];
        
        foreach ($methods_to_test as $method) {
            echo "<div class='method-test'>";
            echo "<strong>Testing: {$method}()</strong><br>";
            
            if (method_exists('Permissions', $method)) {
                try {
                    $result = call_user_func(['Permissions', $method]);
                    echo "✅ Method exists and callable<br>";
                    echo "Result type: " . gettype($result) . "<br>";
                    
                    if (is_array($result)) {
                        echo "Array length: " . count($result) . "<br>";
                    } elseif (is_string($result)) {
                        echo "Value: " . htmlspecialchars($result) . "<br>";
                    } elseif (is_bool($result)) {
                        echo "Value: " . ($result ? 'true' : 'false') . "<br>";
                    } else {
                        echo "Value: " . var_export($result, true) . "<br>";
                    }
                    
                } catch (Exception $e) {
                    echo "❌ Method failed: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "❌ Method does not exist<br>";
            }
            echo "</div>";
        }
        
        // Test permission checking
        echo "<div class='method-test'>";
        echo "<strong>Testing: hasPermission()</strong><br>";
        
        $permissions_to_test = [
            'dashboard',
            'customer_list', 
            'customer_edit',
            'view_all_data',
            'admin_panel'
        ];
        
        foreach ($permissions_to_test as $permission) {
            try {
                $has_perm = Permissions::hasPermission($permission);
                echo "Permission '{$permission}': " . ($has_perm ? '✅ YES' : '❌ NO') . "<br>";
            } catch (Exception $e) {
                echo "Permission '{$permission}': ❌ ERROR - " . $e->getMessage() . "<br>";
            }
        }
        echo "</div>";
        
    } else {
        echo "<p class='fail'>❌ Permissions class not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ Failed to load Permissions: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
echo "</div>";

// Test role permissions matrix
echo "<div class='check'>";
echo "<h3>Role Permissions Matrix</h3>";

if (class_exists('Permissions')) {
    $roles_to_test = ['admin', 'supervisor', 'sales'];
    
    foreach ($roles_to_test as $role) {
        echo "<h4>Testing Role: {$role}</h4>";
        
        // Temporarily set role
        $original_role = $_SESSION['user_role'] ?? null;
        $_SESSION['user_role'] = $role;
        
        echo "<div class='method-test'>";
        echo "<strong>Role set to: {$role}</strong><br>";
        
        $test_permissions = [
            'dashboard' => 'Dashboard access',
            'customer_list' => 'Customer list access', 
            'customer_edit' => 'Customer edit access',
            'view_all_data' => 'View all data',
            'user_management' => 'User management',
            'system_settings' => 'System settings'
        ];
        
        foreach ($test_permissions as $perm => $desc) {
            try {
                $has_perm = Permissions::hasPermission($perm);
                $status = $has_perm ? '✅' : '❌';
                echo "{$status} {$desc}<br>";
            } catch (Exception $e) {
                echo "❌ {$desc}: ERROR<br>";
            }
        }
        echo "</div>";
        
        // Restore original role
        if ($original_role) {
            $_SESSION['user_role'] = $original_role;
        }
    }
} else {
    echo "<p class='fail'>❌ Cannot test - Permissions class not available</p>";
}
echo "</div>";

// Test menu generation
echo "<div class='check'>";
echo "<h3>Menu Generation Test</h3>";

if (class_exists('Permissions')) {
    try {
        $menu_items = Permissions::getMenuItems();
        echo "<p class='pass'>✅ Menu items generated successfully</p>";
        echo "<p><strong>Total menu items:</strong> " . count($menu_items) . "</p>";
        
        if (!empty($menu_items)) {
            echo "<h4>Menu Items:</h4>";
            echo "<ul>";
            foreach ($menu_items as $item) {
                $title = $item['title'] ?? 'No title';
                $url = $item['url'] ?? 'No URL';
                $icon = $item['icon'] ?? 'No icon';
                echo "<li><strong>{$title}</strong> - {$url} ({$icon})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='warn'>⚠️ No menu items generated</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='fail'>❌ Menu generation failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='fail'>❌ Cannot test - Permissions class not available</p>";
}
echo "</div>";

// Test require functions
echo "<div class='check'>";
echo "<h3>Require Functions Test</h3>";

if (class_exists('Permissions')) {
    echo "<h4>Testing requireLogin()</h4>";
    try {
        // This should not redirect since we have session
        ob_start();
        Permissions::requireLogin();
        $output = ob_get_clean();
        echo "<p class='pass'>✅ requireLogin() passed (no redirect)</p>";
    } catch (Exception $e) {
        echo "<p class='fail'>❌ requireLogin() error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h4>Testing requirePermission()</h4>";
    try {
        ob_start();
        Permissions::requirePermission('dashboard');
        $output = ob_get_clean();
        echo "<p class='pass'>✅ requirePermission('dashboard') passed</p>";
    } catch (Exception $e) {
        echo "<p class='fail'>❌ requirePermission() error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p class='fail'>❌ Cannot test - Permissions class not available</p>";
}
echo "</div>";

// Database dependency test
echo "<div class='check'>";
echo "<h3>Database Dependency Test</h3>";

try {
    require_once 'config/database.php';
    
    if (class_exists('Database')) {
        echo "<p class='pass'>✅ Database class available</p>";
        
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        if ($connection) {
            echo "<p class='pass'>✅ Database connection successful</p>";
            
            // Test users table (if used by permissions)
            try {
                $stmt = $connection->prepare("SHOW TABLES LIKE 'users'");
                $stmt->execute();
                $users_table = $stmt->fetch();
                
                if ($users_table) {
                    echo "<p class='pass'>✅ Users table exists</p>";
                    
                    // Check users table structure
                    $stmt = $connection->prepare("DESCRIBE users");
                    $stmt->execute();
                    $columns = $stmt->fetchAll();
                    
                    echo "<p><strong>Users table columns:</strong></p>";
                    echo "<ul>";
                    foreach ($columns as $column) {
                        echo "<li>{$column['Field']} ({$column['Type']})</li>";
                    }
                    echo "</ul>";
                    
                } else {
                    echo "<p class='warn'>⚠️ Users table not found</p>";
                }
                
            } catch (Exception $e) {
                echo "<p class='warn'>⚠️ Cannot check users table: " . $e->getMessage() . "</p>";
            }
            
        } else {
            echo "<p class='fail'>❌ Database connection failed</p>";
        }
        
    } else {
        echo "<p class='fail'>❌ Database class not available</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='fail'>❌ Database test failed: " . $e->getMessage() . "</p>";
}
echo "</div>";

?>

<div class="check info">
<h3>📋 Summary & Next Steps</h3>
<ol>
<li><strong>Check all sections above</strong> - Look for any ❌ failures</li>
<li><strong>Make sure you're logged in</strong> - Many permissions depend on valid session</li>
<li><strong>Verify database connection</strong> - Permissions may query user data</li>
<li><strong>Report specific errors</strong> - Copy any error messages you see</li>
</ol>

<p><strong>Test completed at:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
</div>

</div>
</body>
</html>