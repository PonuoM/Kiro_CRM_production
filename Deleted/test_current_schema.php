<?php
echo "<h2>🔍 Current Database Schema Check</h2>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    
    echo "✅ Database connected<br><br>";
    
    // Check users table structure
    echo "<h3>👥 Users Table Structure:</h3>";
    $stmt = $db->getConnection()->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check current users
    echo "<br><h3>👤 Current Users:</h3>";
    $stmt = $db->getConnection()->query("SELECT id, username, role, status FROM users");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th></tr>";
    
    foreach($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['role'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($user['status'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for supervisor_id field
    $has_supervisor_id = false;
    foreach($columns as $col) {
        if ($col['Field'] === 'supervisor_id') {
            $has_supervisor_id = true;
            break;
        }
    }
    
    echo "<br><h3>🔗 Team Structure Analysis:</h3>";
    if ($has_supervisor_id) {
        echo "✅ supervisor_id field exists<br>";
    } else {
        echo "❌ supervisor_id field missing - need to add<br>";
    }
    
    echo "<br><h3>📋 Action Required:</h3>";
    echo "<ul>";
    if (!$has_supervisor_id) {
        echo "<li>❗ Add supervisor_id column to users table</li>";
    }
    echo "<li>🔧 Update permissions.php to use 3 roles only</li>";
    echo "<li>🔄 Revert modified files to use permissions system</li>";
    echo "<li>✅ Test role-based access</li>";
    echo "</ul>";
    
} catch(Exception $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
}
?>