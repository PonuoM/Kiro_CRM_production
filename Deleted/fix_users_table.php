<?php
echo "<h2>🔧 Fix Users Table</h2>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "✅ Database connected<br><br>";
    
    // Check current table structure
    echo "<h3>1. Current Users Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Check if we have proper columns
    $hasUsername = false;
    $hasPassword = false;
    $hasRole = false;
    
    foreach($columns as $col) {
        if($col['Field'] === 'username') $hasUsername = true;
        if($col['Field'] === 'password') $hasPassword = true;
        if($col['Field'] === 'role') $hasRole = true;
    }
    
    echo "<h3>2. Column Check:</h3>";
    echo ($hasUsername ? "✅" : "❌") . " username column<br>";
    echo ($hasPassword ? "✅" : "❌") . " password column<br>";
    echo ($hasRole ? "✅" : "❌") . " role column<br><br>";
    
    // Show current data
    echo "<h3>3. Current Data:</h3>";
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    echo "<pre>";
    print_r($users);
    echo "</pre>";
    
    // If table structure is wrong, recreate it
    if (!$hasUsername || !$hasPassword || !$hasRole) {
        echo "<h3>4. 🔧 Fixing Table Structure:</h3>";
        
        // Drop and recreate users table
        echo "Dropping existing users table...<br>";
        $pdo->exec("DROP TABLE IF EXISTS users");
        
        echo "Creating new users table...<br>";
        $createTable = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role ENUM('admin', 'manager', 'sales', 'user') DEFAULT 'user',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTable);
        echo "✅ Table created successfully<br>";
        
        // Insert default users
        echo "Inserting default users...<br>";
        $insertUsers = "
        INSERT INTO users (username, password, email, role, status) VALUES
        ('admin', 'admin123', 'admin@example.com', 'admin', 'active'),
        ('manager', 'manager123', 'manager@example.com', 'manager', 'active'),
        ('sales1', 'sales123', 'sales1@example.com', 'sales', 'active')";
        
        $pdo->exec($insertUsers);
        echo "✅ Default users inserted<br>";
        
        // Show new data
        echo "<h3>5. New Users Data:</h3>";
        $stmt = $pdo->query("SELECT id, username, email, role, status FROM users");
        $newUsers = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th></tr>";
        foreach($newUsers as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        echo "<h3>✅ Users Table Fixed!</h3>";
        echo "<strong>Login Credentials:</strong><br>";
        echo "Username: admin | Password: admin123<br>";
        echo "Username: manager | Password: manager123<br>";
        echo "Username: sales1 | Password: sales123<br><br>";
        
        echo '<a href="working_login.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🚀 Try Login Now</a>';
        
    } else {
        echo "<h3>4. Table structure looks correct, but data might be corrupted.</h3>";
        echo "Consider running the fix anyway if login doesn't work.<br>";
        echo '<a href="?fix=force" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔧 Force Fix Table</a>';
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

// Force fix if requested
if(isset($_GET['fix']) && $_GET['fix'] === 'force') {
    echo "<script>window.location.reload();</script>";
}
?>