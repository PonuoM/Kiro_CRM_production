<?php
// test_database_simple.php
// ทดสอบ Database connection แบบง่าย

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Connection Test</title></head><body>";
echo "<h1>🔍 Database Connection Test</h1>";

// Method 1: ใช้ config จากไฟล์ config
echo "<h2>📋 Method 1: Using config/database.php</h2>";
if (file_exists('config/database.php')) {
    echo "<p>✅ config/database.php exists</p>";
    try {
        require_once 'config/database.php';
        
        // แสดงตัวแปรที่ใช้
        echo "<pre>";
        echo "Available variables:\n";
        echo "- \$dsn: " . (isset($dsn) ? $dsn : 'NOT SET') . "\n";
        echo "- \$username: " . (isset($username) ? $username : 'NOT SET') . "\n";
        echo "- \$password: " . (isset($password) ? '[HIDDEN]' : 'NOT SET') . "\n";
        echo "- \$options: " . (isset($options) ? 'SET' : 'NOT SET') . "\n";
        echo "</pre>";
        
        if (isset($dsn) && isset($username)) {
            $pdo = new PDO($dsn, $username, $password, $options ?? []);
            echo "<p style='color:green;'>✅ Connection successful with config file!</p>";
            
            // ทดสอบ query
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
            $result = $stmt->fetch();
            echo "<p>📊 Total customers: " . $result['count'] . "</p>";
            
        } else {
            echo "<p style='color:red;'>❌ Required variables not found in config</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Config method failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ config/database.php not found</p>";
}

// Method 2: ตรวจสอบไฟล์ config ทั้งหมด
echo "<h2>📁 Method 2: Check all config files</h2>";
$configFiles = [
    'config/database.php',
    'config/config.php', 
    'config/database.production.php',
    'config/config.production.php'
];

foreach ($configFiles as $file) {
    if (file_exists($file)) {
        echo "<p>✅ Found: $file</p>";
        echo "<pre style='background:#f5f5f5;padding:10px;max-height:200px;overflow:auto;'>";
        echo htmlspecialchars(file_get_contents($file));
        echo "</pre>";
    } else {
        echo "<p>❌ Missing: $file</p>";
    }
}

// Method 3: ทดสอบการเชื่อมต่อโดยตรง
echo "<h2>🔧 Method 3: Direct connection test</h2>";

// Common database configurations to try
$testConfigs = [
    [
        'host' => 'localhost',
        'dbname' => 'kiro_crm',
        'username' => 'root',
        'password' => ''
    ],
    [
        'host' => 'localhost', 
        'dbname' => 'prima49_crm',
        'username' => 'prima49_user',
        'password' => ''
    ],
    [
        'host' => 'localhost',
        'dbname' => 'crm_system',
        'username' => 'root', 
        'password' => ''
    ]
];

foreach ($testConfigs as $i => $config) {
    echo "<h3>Test Config " . ($i + 1) . ":</h3>";
    echo "<p>Host: {$config['host']}, DB: {$config['dbname']}, User: {$config['username']}</p>";
    
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<p style='color:green;'>✅ Connection successful!</p>";
        
        // ทดสอบตาราง customers
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Table 'customers' exists</p>";
                
                // ทดสอบคอลัมน์
                $stmt = $pdo->query("DESCRIBE customers");
                $columns = $stmt->fetchAll();
                
                $hasTemp = false;
                $hasGrade = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === 'CustomerTemperature') $hasTemp = true;
                    if ($col['Field'] === 'CustomerGrade') $hasGrade = true;
                }
                
                echo "<p>CustomerTemperature: " . ($hasTemp ? "✅" : "❌") . "</p>";
                echo "<p>CustomerGrade: " . ($hasGrade ? "✅" : "❌") . "</p>";
                
                // นับลูกค้า
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
                $result = $stmt->fetch();
                echo "<p>📊 Total customers: " . $result['count'] . "</p>";
                
                // ตัวอย่างข้อมูล
                if ($hasTemp && $hasGrade) {
                    $stmt = $pdo->query("SELECT CustomerCode, CustomerName, CustomerTemperature, CustomerGrade FROM customers LIMIT 3");
                    $samples = $stmt->fetchAll();
                    echo "<h4>📋 Sample data:</h4>";
                    echo "<table border='1' style='border-collapse:collapse;'>";
                    echo "<tr><th>Code</th><th>Name</th><th>Temp</th><th>Grade</th></tr>";
                    foreach ($samples as $sample) {
                        echo "<tr>";
                        echo "<td>{$sample['CustomerCode']}</td>";
                        echo "<td>{$sample['CustomerName']}</td>";
                        echo "<td>{$sample['CustomerTemperature']}</td>";
                        echo "<td>{$sample['CustomerGrade']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                // สร้างไฟล์ config ที่ถูกต้อง
                echo "<h4>💾 Correct config for this database:</h4>";
                echo "<pre style='background:#e8f5e8;padding:10px;'>";
                echo "&lt;?php\n";
                echo "\$host = '{$config['host']}';\n";
                echo "\$dbname = '{$config['dbname']}';\n";
                echo "\$username = '{$config['username']}';\n";
                echo "\$password = '{$config['password']}';\n";
                echo "\$dsn = \"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\";\n";
                echo "\$options = [\n";
                echo "    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n";
                echo "    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC\n";
                echo "];\n";
                echo "?&gt;";
                echo "</pre>";
                
                break; // พอเจอที่ทำงานแล้ว หยุด
                
            } else {
                echo "<p style='color:orange;'>⚠️ Table 'customers' not found</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:orange;'>⚠️ Table test failed: " . $e->getMessage() . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>📍 URL:</strong> <a href='test_database_simple.php'>test_database_simple.php</a></p>";
echo "<p><strong>⏰ เวลา:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>