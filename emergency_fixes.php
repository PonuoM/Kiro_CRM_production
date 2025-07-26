<?php
/**
 * Emergency Quick Fixes for Common Issues
 * Run this file to apply immediate fixes for 500 errors
 */

echo "<h2>🚨 Emergency Quick Fixes</h2>";
echo "<div style='background:#fff3cd; padding:15px; margin:10px 0; border-radius:8px; border-left: 4px solid #ffc107;'>";
echo "<strong>⚠️ Warning:</strong> This script applies emergency fixes to resolve 500 errors quickly.";
echo "</div>";

// Fix 1: Database connection test and repair
echo "<h3>🔧 Fix 1: Database Connection</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Test basic query
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database queries working<br>";
    
} catch (Exception $e) {
    echo "❌ Database issue: " . $e->getMessage() . "<br>";
    echo "<strong>Manual fix required:</strong><br>";
    echo "1. Check config/database.php credentials<br>";
    echo "2. Verify MySQL server is running<br>";
    echo "3. Check database user permissions<br>";
}

// Fix 2: Session and permissions
echo "<h3>🔧 Fix 2: Session Management</h3>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started successfully<br>";
    } else {
        echo "✅ Session already active<br>";
    }
    
    // Check if permissions file exists
    if (file_exists('includes/permissions.php')) {
        echo "✅ Permissions file exists<br>";
    } else {
        echo "❌ Permissions file missing<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Session issue: " . $e->getMessage() . "<br>";
}

// Fix 3: Critical file checks
echo "<h3>🔧 Fix 3: Critical Files Check</h3>";
$criticalFiles = [
    'config/config.php',
    'config/database.php', 
    'includes/functions.php',
    'includes/BaseModel.php',
    'includes/User.php',
    'includes/Customer.php',
    'includes/permissions.php',
    'includes/main_layout.php',
    'includes/admin_layout.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Fix 4: Create missing directories
echo "<h3>🔧 Fix 4: Directory Structure</h3>";
$directories = ['logs', 'uploads', 'backups'];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir<br>";
        } else {
            echo "❌ Failed to create directory: $dir<br>";
        }
    } else {
        echo "✅ Directory exists: $dir<br>";
    }
}

// Fix 5: Test page loading
echo "<h3>🔧 Fix 5: Page Access Test</h3>";
$testPages = [
    'index.php' => 'Main index',
    'pages/login.php' => 'Login page',
    'pages/dashboard.php' => 'Dashboard',
    'universal_login.php' => 'Universal login'
];

foreach ($testPages as $page => $description) {
    if (file_exists($page)) {
        echo "✅ $description ($page) exists<br>";
    } else {
        echo "❌ $description ($page) missing<br>";
    }
}

// Fix 6: Quick database table check
echo "<h3>🔧 Fix 6: Database Tables</h3>";
try {
    if (isset($pdo)) {
        $tables = ['users', 'customers', 'call_logs', 'tasks', 'orders', 'sales_histories'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "✅ Table '$table' exists ($count records)<br>";
            } catch (Exception $e) {
                echo "❌ Table '$table' issue: " . $e->getMessage() . "<br>";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Table check failed: " . $e->getMessage() . "<br>";
}

echo "<h3>📋 Summary & Next Steps</h3>";
echo "<div style='background:#d1ecf1; padding:15px; border-radius:8px;'>";
echo "<h4>If you still see 500 errors:</h4>";
echo "<ol>";
echo "<li><strong>Run these URLs in order:</strong></li>";
echo "<ul>";
echo "<li>https://www.prima49.com/crm_system/Kiro_CRM_production/fix_database_issues.php</li>";
echo "<li>https://www.prima49.com/crm_system/Kiro_CRM_production/create_sample_data.php</li>";
echo "<li>https://www.prima49.com/crm_system/Kiro_CRM_production/system_health_check.php</li>";
echo "</ul>";
echo "<li><strong>Check server error logs</strong> for detailed error messages</li>";
echo "<li><strong>Enable error reporting</strong> temporarily in config/config.php</li>";
echo "<li><strong>Verify file permissions</strong> for all PHP files</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background:#d4edda; padding:15px; margin-top:15px; border-radius:8px;'>";
echo "<h4>✅ Emergency fixes completed!</h4>";
echo "<p>Try accessing your problematic pages again:</p>";
echo "<ul>";
echo "<li><a href='pages/order_history_demo.php' target='_blank'>Order History Demo</a></li>";
echo "<li><a href='pages/admin/import_customers.php' target='_blank'>Import Customers</a></li>";
echo "<li><a href='pages/sales_performance.php' target='_blank'>Sales Performance</a></li>";
echo "<li><a href='create_sample_data.php' target='_blank'>Create Sample Data</a></li>";
echo "</ul>";
echo "</div>";
?>