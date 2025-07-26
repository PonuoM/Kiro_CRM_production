<?php
session_start();

// Test if session is working
if (!isset($_SESSION['user_id'])) {
    die('Please login first at: <a href="universal_login.php">Login</a>');
}

echo "<h2>🔍 API Debug Test</h2>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Username: " . $_SESSION['username'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br><br>";

// Test API files
$apiFiles = [
    'api/tasks/daily.php',
    'api/customers/list.php',
    'api/tasks/list.php',
    'api/dashboard/summary.php'
];

foreach($apiFiles as $apiFile) {
    echo "<h3>Testing: $apiFile</h3>";
    
    if(file_exists($apiFile)) {
        echo "✅ File exists<br>";
        echo "File size: " . filesize($apiFile) . " bytes<br>";
        echo "Permissions: " . substr(sprintf('%o', fileperms($apiFile)), -4) . "<br>";
        
        // Try to capture the output
        try {
            ob_start();
            $oldDisplayErrors = ini_get('display_errors');
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
            
            // Include the file and capture output
            include $apiFile;
            
            $output = ob_get_clean();
            ini_set('display_errors', $oldDisplayErrors);
            
            if(empty($output)) {
                echo "❌ No output generated<br>";
            } else {
                echo "✅ Output generated (" . strlen($output) . " chars)<br>";
                
                // Check if it's valid JSON
                $decoded = json_decode($output);
                if(json_last_error() === JSON_ERROR_NONE) {
                    echo "✅ Valid JSON<br>";
                    echo "<details><summary>JSON Preview</summary><pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre></details>";
                } else {
                    echo "❌ Invalid JSON: " . json_last_error_msg() . "<br>";
                    echo "<details><summary>Raw Output</summary><pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre></details>";
                }
            }
            
        } catch(Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "<br>";
        } catch(Error $e) {
            echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ File not found<br>";
    }
    
    echo "<hr>";
}

echo "<h3>Quick API Test Links:</h3>";
echo '<a href="api/tasks/daily.php" target="_blank">📋 Daily Tasks API</a><br>';
echo '<a href="api/customers/list.php" target="_blank">👥 Customers API</a><br>';
echo '<a href="api/dashboard/summary.php" target="_blank">📊 Dashboard Summary API</a><br>';

echo "<h3>Simple Test API:</h3>";
// Create a simple test API
file_put_contents('api/test.php', '<?php
header("Content-Type: application/json");
session_start();

$response = [
    "status" => "success",
    "message" => "API is working",
    "user_id" => $_SESSION["user_id"] ?? null,
    "timestamp" => date("Y-m-d H:i:s"),
    "test_data" => [
        "item1" => "value1",
        "item2" => "value2"
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>');

echo '<a href="api/test.php" target="_blank">🧪 Simple Test API</a><br>';
echo "✅ Created simple test API<br>";
?>