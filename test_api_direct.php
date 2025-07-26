<?php
/**
 * Test API Direct - ทดสอบ API โดยตรง
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><title>🧪 Test API Direct</title>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;background:#d4edda;padding:10px;margin:5px 0;border-radius:3px;} .error{color:red;background:#f8d7da;padding:10px;margin:5px 0;border-radius:3px;} pre{background:#f8f9fa;padding:10px;}</style>";
echo "</head><body>";

echo "<h1>🧪 Test API Direct</h1>";

// Start session and mock user
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'sales02';
    $_SESSION['role'] = 'Sales';
    echo "<div class='success'>✅ Mock session created for sales02</div>";
}

echo "<h2>Testing API Files</h2>";

// Test 1: sales_records_fixed.php
echo "<h3>1. Testing sales_records_fixed.php</h3>";
try {
    // Change directory to api/sales
    $old_cwd = getcwd();
    chdir(__DIR__ . '/api/sales');
    
    ob_start();
    include 'sales_records_fixed.php';
    $output = ob_get_clean();
    
    chdir($old_cwd);
    
    if (!empty($output)) {
        $data = json_decode($output, true);
        if ($data !== null) {
            if (isset($data['success']) && $data['success']) {
                echo "<div class='success'>✅ API Response Success</div>";
                echo "<div class='success'>✅ Records: " . count($data['data']['sales_records']) . "</div>";
                echo "<div class='success'>✅ Total orders: " . $data['data']['summary']['total_orders'] . "</div>";
                echo "<div class='success'>✅ User: " . $data['user'] . "</div>";
            } else {
                echo "<div class='error'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Invalid JSON response</div>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>";
        }
    } else {
        echo "<div class='error'>❌ Empty response</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
}

// Test 2: sales_records_enhanced.php
echo "<h3>2. Testing sales_records_enhanced.php</h3>";
try {
    $old_cwd = getcwd();
    chdir(__DIR__ . '/api/sales');
    
    ob_start();
    include 'sales_records_enhanced.php';
    $output = ob_get_clean();
    
    chdir($old_cwd);
    
    if (!empty($output)) {
        $data = json_decode($output, true);
        if ($data !== null) {
            if (isset($data['success']) && $data['success']) {
                echo "<div class='success'>✅ Enhanced API Success</div>";
                echo "<div class='success'>✅ Records: " . count($data['data']['sales_records']) . "</div>";
                echo "<div class='success'>✅ Product stats available: " . (isset($data['data']['product_stats']) ? 'Yes' : 'No') . "</div>";
            } else {
                echo "<div class='error'>❌ Enhanced API Error: " . ($data['message'] ?? 'Unknown error') . "</div>";
            }
        } else {
            echo "<div class='error'>❌ Invalid JSON response</div>";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>";
        }
    } else {
        echo "<div class='error'>❌ Empty response</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
}

echo "<h2>Path Testing</h2>";

// Test file paths
$files_to_test = [
    'config/database.php',
    'includes/permissions.php',
    'api/sales/sales_records_fixed.php',
    'api/sales/sales_records_enhanced.php'
];

foreach ($files_to_test as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ File exists: {$file}</div>";
    } else {
        echo "<div class='error'>❌ File missing: {$file}</div>";
    }
}

echo "<h2>Directory Context</h2>";
echo "<div class='success'>Current working directory: " . getcwd() . "</div>";
echo "<div class='success'>Script location: " . __DIR__ . "</div>";

// Test relative paths from api/sales
echo "<h3>From api/sales perspective:</h3>";
$api_sales_dir = __DIR__ . '/api/sales';
if (is_dir($api_sales_dir)) {
    echo "<div class='success'>✅ api/sales directory exists</div>";
    
    $config_from_api = $api_sales_dir . '/../../config/database.php';
    $includes_from_api = $api_sales_dir . '/../../includes/permissions.php';
    
    if (file_exists($config_from_api)) {
        echo "<div class='success'>✅ Config reachable from api/sales: ../../config/database.php</div>";
    } else {
        echo "<div class='error'>❌ Config NOT reachable from api/sales: ../../config/database.php</div>";
    }
    
    if (file_exists($includes_from_api)) {
        echo "<div class='success'>✅ Includes reachable from api/sales: ../../includes/permissions.php</div>";
    } else {
        echo "<div class='error'>❌ Includes NOT reachable from api/sales: ../../includes/permissions.php</div>";
    }
} else {
    echo "<div class='error'>❌ api/sales directory missing</div>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p>หากทดสอบทุกอย่างผ่าน ให้ลองเข้าหน้าหลัก:</p>";
echo "<p><a href='pages/customer_list_dynamic.php' target='_blank'>pages/customer_list_dynamic.php</a></p>";

echo "</body></html>";
?>