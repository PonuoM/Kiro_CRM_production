<?php
echo "<h2>🔧 Testing Permissions System</h2>";

// Test 1: Replace old permissions with new one
echo "<h3>📝 Step 1: Replace Permissions File</h3>";

$oldPermissions = '/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/permissions.php';
$newPermissions = '/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/permissions_new.php';

if (file_exists($oldPermissions) && file_exists($newPermissions)) {
    // Backup old permissions
    copy($oldPermissions, '/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/permissions_backup.php');
    echo "✅ Backed up old permissions.php<br>";
    
    // Replace with new permissions
    copy($newPermissions, $oldPermissions);
    echo "✅ Replaced with new permissions system<br>";
} else {
    echo "❌ File not found<br>";
}

// Test 2: Test permissions loading
echo "<br><h3>🔐 Step 2: Test Permissions Loading</h3>";
try {
    require_once 'includes/permissions.php';
    echo "✅ Permissions class loaded successfully<br>";
    
    // Mock session data for testing
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'Admin';
    
    // Test permission checks
    echo "<br><strong>Admin Permissions Test:</strong><br>";
    echo "- Dashboard: " . (Permissions::hasPermission('dashboard') ? "✅" : "❌") . "<br>";
    echo "- User Management: " . (Permissions::hasPermission('user_management') ? "✅" : "❌") . "<br>";
    echo "- View All Data: " . (Permissions::canViewAllData() ? "✅" : "❌") . "<br>";
    
    // Test menu generation
    $menuItems = Permissions::getMenuItems();
    echo "<br><strong>Menu Items Generated:</strong> " . count($menuItems) . " items<br>";
    foreach ($menuItems as $item) {
        echo "- " . htmlspecialchars($item['title']) . " (" . htmlspecialchars($item['url']) . ")<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

// Test 3: Database connection for team hierarchy
echo "<br><h3>🔗 Step 3: Test Database Connection</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    echo "✅ Database connected<br>";
    
    // Check if supervisor_id field exists
    $stmt = $db->getConnection()->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    $hasSupervisorId = false;
    foreach($columns as $col) {
        if ($col['Field'] === 'supervisor_id') {
            $hasSupervisorId = true;
            break;
        }
    }
    
    if ($hasSupervisorId) {
        echo "✅ supervisor_id field exists in users table<br>";
    } else {
        echo "⚠️ supervisor_id field missing - please run add_supervisor_id_field.sql<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<br><h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>Run add_supervisor_id_field.sql in MySQL if not done</li>";
echo "<li>Test login with different roles</li>";
echo "<li>Verify role-based menu visibility</li>";
echo "<li>Check data access restrictions</li>";
echo "</ol>";

echo "<br><h3>🔗 Test Links:</h3>";
echo '<p><a href="pages/dashboard.php" target="_blank">Test Dashboard</a> (should use new permissions)</p>';
echo '<p><a href="pages/login.php" target="_blank">Login Page</a></p>';
?>