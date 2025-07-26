<?php
echo "<h2>🔍 Database Debug Analysis</h2>";

// Step 1: Read config file content
echo "<h3>1. Config File Content:</h3>";
if(file_exists('config/database.php')) {
    echo "<pre>";
    echo htmlspecialchars(file_get_contents('config/database.php'));
    echo "</pre>";
} else {
    echo "❌ config/database.php not found<br>";
}

echo "<h3>2. Manual Database Test:</h3>";

// Manual config (exactly as in the file)
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'primacom_CRM',
    'username' => 'primacom_bloguser',
    'password' => 'pJnL53Wkhju2LaGPytw8',
    'charset' => 'utf8mb4'
];

try {
    // Test 1: Basic connection without database
    echo "Testing MySQL connection (no database)...<br>";
    $dsn1 = "mysql:host={$db_config['host']};port={$db_config['port']};charset={$db_config['charset']}";
    $pdo1 = new PDO($dsn1, $db_config['username'], $db_config['password']);
    echo "✅ MySQL server connection successful<br>";
    
    // Test 2: List all databases this user can access
    echo "<br>Databases accessible by user '{$db_config['username']}':<br>";
    $stmt = $pdo1->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($databases as $db) {
        echo "- $db<br>";
        if($db === $db_config['dbname']) {
            echo "  ✅ Target database found!<br>";
        }
    }
    
    // Test 3: Check user privileges
    echo "<br>User privileges:<br>";
    $stmt = $pdo1->query("SHOW GRANTS FOR '{$db_config['username']}'@'%'");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach($grants as $grant) {
        echo "- " . htmlspecialchars($grant) . "<br>";
    }
    
    // Test 4: Try to select the specific database
    echo "<br>Testing database selection...<br>";
    try {
        $pdo1->exec("USE `{$db_config['dbname']}`");
        echo "✅ Successfully selected database '{$db_config['dbname']}'<br>";
        
        // Test 5: Show tables
        $stmt = $pdo1->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables in database (" . count($tables) . "):<br>";
        foreach($tables as $table) {
            echo "- $table<br>";
        }
        
    } catch(PDOException $e) {
        echo "❌ Failed to select database: " . $e->getMessage() . "<br>";
    }
    
    // Test 6: Try direct connection with database
    echo "<br>Testing direct connection with database...<br>";
    try {
        $dsn2 = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo2 = new PDO($dsn2, $db_config['username'], $db_config['password']);
        echo "✅ Direct database connection successful<br>";
        
        $stmt = $pdo2->query("SELECT DATABASE()");
        $currentDB = $stmt->fetchColumn();
        echo "Current database: $currentDB<br>";
        
    } catch(PDOException $e) {
        echo "❌ Direct database connection failed: " . $e->getMessage() . "<br>";
        echo "Error Code: " . $e->getCode() . "<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ MySQL connection failed: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
}

echo "<h3>3. Alternative Database Names:</h3>";
echo "Sometimes DirectAdmin adds prefixes. Try these variations:<br>";
$variations = [
    'primacom_CRM',
    'primacom_crm', 
    'prima49_CRM',
    'prima49_crm'
];

foreach($variations as $dbname) {
    try {
        $dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
        echo "✅ Connection successful with: $dbname<br>";
    } catch(PDOException $e) {
        echo "❌ Failed with: $dbname<br>";
    }
}

echo "<h3>4. DirectAdmin Database Info:</h3>";
echo "To check in DirectAdmin:<br>";
echo "1. Go to MySQL Management<br>";
echo "2. Look for database that starts with your username<br>";
echo "3. Note the exact database name<br>";
echo "4. Check that user has access to that database<br>";
?>