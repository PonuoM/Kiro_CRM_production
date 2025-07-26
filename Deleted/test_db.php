<?php
// Simple database connection test
echo "<h2>Database Connection Test</h2>";

// Test PHP Extensions
echo "<h3>1. PHP Extensions Check:</h3>";
$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
foreach($extensions as $ext) {
    if(extension_loaded($ext)) {
        echo "✅ $ext loaded<br>";
    } else {
        echo "❌ $ext NOT loaded<br>";
    }
}

echo "<h3>2. Database Connection Test:</h3>";

// Database configuration
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'primacom_CRM',
    'username' => 'primacom_bloguser',
    'password' => 'pJnL53Wkhju2LaGPytw8',
    'charset' => 'utf8mb4'
];

try {
    // Test 1: Basic PDO connection
    echo "Testing PDO connection...<br>";
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    echo "✅ MySQL server connection successful<br>";
    
    // Test 2: Database exists
    echo "Testing database existence...<br>";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'primacom_CRM'");
    $result = $stmt->fetch();
    
    if($result) {
        echo "✅ Database 'primacom_CRM' exists<br>";
        
        // Test 3: Connect to specific database
        $dsn_with_db = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo_db = new PDO($dsn_with_db, $db_config['username'], $db_config['password']);
        echo "✅ Connected to database 'primacom_CRM'<br>";
        
        // Test 4: Check tables
        echo "<h3>3. Tables in database:</h3>";
        $stmt = $pdo_db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if(count($tables) > 0) {
            echo "Found " . count($tables) . " tables:<br>";
            foreach($tables as $table) {
                echo "- $table<br>";
            }
        } else {
            echo "⚠️ No tables found. Need to import SQL file.<br>";
        }
        
    } else {
        echo "❌ Database 'primacom_CRM' does NOT exist<br>";
        echo "📝 Need to create database first<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "📝 Error Code: " . $e->getCode() . "<br>";
}

echo "<h3>4. File Existence Check:</h3>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Script location: " . __FILE__ . "<br><br>";

$files = [
    'config/database.php',
    'config/config.php',
    'includes/functions.php',
    'includes/BaseModel.php',
    'sql/production_setup.sql'
];

foreach($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    echo "Checking: $file<br>";
    echo "Full path: $fullPath<br>";
    
    if(file_exists($file)) {
        echo "✅ $file exists (relative)<br>";
    } else {
        echo "❌ $file missing (relative)<br>";
    }
    
    if(file_exists($fullPath)) {
        echo "✅ $fullPath exists (absolute)<br>";
        echo "Permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "<br>";
        echo "Readable: " . (is_readable($fullPath) ? "Yes" : "No") . "<br>";
    } else {
        echo "❌ $fullPath missing (absolute)<br>";
    }
    echo "<br>";
}

echo "<hr>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
?>