<?php
echo "<h2>🔍 Database Tables Test</h2>";

$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'primacom_CRM',
    'username' => 'primacom_bloguser',
    'password' => 'pJnL53Wkhju2LaGPytw8',
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    
    echo "✅ Connected to database: {$db_config['dbname']}<br><br>";
    
    // Show tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(count($tables) > 0) {
        echo "<h3>Tables found (" . count($tables) . "):</h3>";
        foreach($tables as $table) {
            echo "✅ $table<br>";
            
            // Count records in each table
            try {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                $count = $countStmt->fetchColumn();
                echo "   → $count records<br>";
            } catch(Exception $e) {
                echo "   → Error counting records<br>";
            }
        }
        
        echo "<br><h3>✅ Database is ready!</h3>";
        echo "<a href='pages/login.php'>🚀 Go to Login Page</a>";
        
    } else {
        echo "❌ No tables found. Need to import SQL file.<br>";
        echo "<br><strong>Steps to fix:</strong><br>";
        echo "1. Go to phpMyAdmin<br>";
        echo "2. Select database 'primacom_CRM'<br>";
        echo "3. Import 'sql/production_setup.sql'<br>";
    }
    
} catch(PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}
?>