<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

echo "<h2>🔍 Database Tables Structure</h2>";

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='universal_login.php'>Login</a>";
    exit;
}

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "✅ Database connected<br><br>";
    
    // Check all tables
    $tables = ['tasks', 'customers', 'users', 'call_logs', 'orders', 'sales_histories'];
    
    foreach ($tables as $table) {
        echo "<h3>📋 Table: $table</h3>";
        
        try {
            // Get table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Get sample data
            echo "<strong>Sample data (first 3 records):</strong><br>";
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
            $data = $stmt->fetchAll();
            
            if (count($data) > 0) {
                echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
                print_r($data);
                echo "</pre>";
            } else {
                echo "<em>No data found</em><br>";
            }
            
        } catch (Exception $e) {
            echo "❌ Error accessing table $table: " . $e->getMessage() . "<br>";
        }
        
        echo "<hr>";
    }
    
    // Create super simple API test
    echo "<h3>🧪 Creating Super Simple API Test</h3>";
    
    $simpleAPI = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (session_status() == PHP_SESSION_NONE) { session_start(); }

try {
    require_once "../config/database.php";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Just get raw data from tasks table
    $stmt = $pdo->query("SELECT * FROM tasks LIMIT 5");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "API is working",
        "table_name" => "tasks",
        "data_count" => count($tasks),
        "sample_data" => $tasks,
        "timestamp" => date("Y-m-d H:i:s")
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "file" => __FILE__,
        "line" => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>';
    
    file_put_contents('api/simple_test.php', $simpleAPI);
    echo "✅ Created super simple API: <a href='api/simple_test.php' target='_blank'>api/simple_test.php</a><br>";
    
    // Create customers simple API
    $customersSimpleAPI = '<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (session_status() == PHP_SESSION_NONE) { session_start(); }

try {
    require_once "../config/database.php";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Just get raw data from customers table
    $stmt = $pdo->query("SELECT * FROM customers LIMIT 5");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "message" => "Customers API is working",
        "table_name" => "customers",
        "data_count" => count($customers),
        "sample_data" => $customers,
        "timestamp" => date("Y-m-d H:i:s")
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "file" => __FILE__,
        "line" => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>';
    
    file_put_contents('api/customers_simple_test.php', $customersSimpleAPI);
    echo "✅ Created customers simple API: <a href='api/customers_simple_test.php' target='_blank'>api/customers_simple_test.php</a><br>";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
?>