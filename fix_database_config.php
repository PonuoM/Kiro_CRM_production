<?php
// fix_database_config.php
// สร้างไฟล์ config ที่ใช้งานได้จริง

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Database Config</title></head><body>";
echo "<h1>🔧 Fix Database Configuration</h1>";

// สร้าง config/database.php ใหม่ที่ทำงานได้
$newDatabaseConfig = '<?php
// Database Configuration - Fixed Version
// Compatible with existing APIs

// Production database configuration
$host = "localhost";
$port = "3306";
$dbname = "primacom_CRM";
$username = "primacom_bloguser";
$password = "pJnL53Wkhju2LaGPytw8";
$charset = "utf8mb4";

// Create DSN string
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

// PDO Options
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_PERSISTENT => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Test connection
try {
    $testConnection = new PDO($dsn, $username, $password, $options);
    // Connection successful - variables are ready for use
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    throw new Exception("Database connection failed: " . $e->getMessage());
}

// Backward compatibility with Class-based approach
$db_config = [
    "host" => $host,
    "port" => $port,
    "dbname" => $dbname,
    "username" => $username,
    "password" => $password,
    "charset" => $charset,
    "options" => $options
];

/**
 * Get database instance (backward compatibility)
 * @return PDO
 */
function getDBConnection() {
    global $dsn, $username, $password, $options;
    static $pdo = null;
    
    if ($pdo === null) {
        $pdo = new PDO($dsn, $username, $password, $options);
    }
    
    return $pdo;
}
?>';

// ทดสอบ configuration ใหม่
echo "<h2>🧪 Testing New Configuration</h2>";

// ทดสอบการเชื่อมต่อ
try {
    $testHost = "localhost";
    $testDb = "primacom_CRM";
    $testUser = "primacom_bloguser";
    $testPass = "pJnL53Wkhju2LaGPytw8";
    
    $testDsn = "mysql:host=$testHost;dbname=$testDb;charset=utf8mb4";
    $testPdo = new PDO($testDsn, $testUser, $testPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<p style='color:green;'>✅ Database connection test successful!</p>";
    
    // ทดสอบตาราง customers
    $stmt = $testPdo->query("SHOW TABLES LIKE 'customers'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✅ Table 'customers' exists</p>";
        
        // ทดสอบคอลัมน์
        $stmt = $testPdo->query("DESCRIBE customers");
        $columns = $stmt->fetchAll();
        
        $hasTemp = false;
        $hasGrade = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'CustomerTemperature') $hasTemp = true;
            if ($col['Field'] === 'CustomerGrade') $hasGrade = true;
        }
        
        echo "<p>CustomerTemperature column: " . ($hasTemp ? "✅ EXISTS" : "❌ MISSING") . "</p>";
        echo "<p>CustomerGrade column: " . ($hasGrade ? "✅ EXISTS" : "❌ MISSING") . "</p>";
        
        if ($hasTemp && $hasGrade) {
            // ทดสอบ Enhanced Query
            $sql = "SELECT 
                CustomerCode, CustomerName, CustomerTemperature, CustomerGrade,
                CASE 
                    WHEN CustomerStatus = 'ลูกค้าใหม่' THEN 
                        DATEDIFF(DATE_ADD(COALESCE(AssignDate, CreatedDate), INTERVAL 30 DAY), CURDATE())
                    ELSE 
                        DATEDIFF(DATE_ADD(COALESCE(CreatedDate), INTERVAL 30 DAY), CURDATE())
                END as time_remaining_days
                FROM customers 
                LIMIT 3";
            
            $stmt = $testPdo->prepare($sql);
            $stmt->execute();
            $samples = $stmt->fetchAll();
            
            if ($samples) {
                echo "<p style='color:green;'>✅ Enhanced query works!</p>";
                echo "<h3>📊 Sample Data with Enhanced Fields:</h3>";
                echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
                echo "<tr style='background:#f0f0f0;'><th>Code</th><th>Name</th><th>Temperature</th><th>Grade</th><th>Days Remaining</th></tr>";
                
                foreach ($samples as $sample) {
                    $tempColor = ($sample['CustomerTemperature'] === 'HOT') ? 'color:red;font-weight:bold;' : '';
                    $daysColor = ($sample['time_remaining_days'] <= 5) ? 'color:red;' : (($sample['time_remaining_days'] <= 14) ? 'color:orange;' : 'color:green;');
                    
                    echo "<tr>";
                    echo "<td>{$sample['CustomerCode']}</td>";
                    echo "<td>{$sample['CustomerName']}</td>";
                    echo "<td style='$tempColor'>{$sample['CustomerTemperature']}</td>";
                    echo "<td>{$sample['CustomerGrade']}</td>";
                    echo "<td style='$daysColor'>{$sample['time_remaining_days']} days</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        // นับจำนวนลูกค้า
        $stmt = $testPdo->query("SELECT COUNT(*) as count FROM customers");
        $result = $stmt->fetch();
        echo "<p>📊 Total customers: <strong>{$result['count']}</strong></p>";
        
    } else {
        echo "<p style='color:red;'>❌ Table 'customers' not found</p>";
    }
    
    echo "<h3>💾 Fixed Database Configuration:</h3>";
    echo "<textarea rows='20' cols='100' readonly>";
    echo htmlspecialchars($newDatabaseConfig);
    echo "</textarea>";
    
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Backup current config:</strong> Rename current config/database.php to config/database.php.backup</li>";
    echo "<li><strong>Create new config:</strong> Copy the configuration above to config/database.php</li>";
    echo "<li><strong>Test APIs:</strong> Try loading dashboard again</li>";
    echo "<li><strong>Verify Premium UI:</strong> Should see progress bars and temperature badges</li>";
    echo "</ol>";
    
    // สร้างไฟล์ config ใหม่
    $configPath = 'config/database_fixed.php';
    if (file_put_contents($configPath, $newDatabaseConfig)) {
        echo "<p style='color:green;'>✅ Created fixed config file: <strong>$configPath</strong></p>";
        echo "<p><strong>📋 Manual step:</strong> Rename $configPath to config/database.php</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database test failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>Database name: primacom_CRM</li>";
    echo "<li>Username: primacom_bloguser</li>";
    echo "<li>Password: [check if correct]</li>";
    echo "<li>MySQL service is running</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>📍 URL:</strong> <a href='fix_database_config.php'>fix_database_config.php</a></p>";
echo "<p><strong>⏰ เวลา:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>