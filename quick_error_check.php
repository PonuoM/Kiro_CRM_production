<?php
/**
 * Quick Error Check - ตรวจสอบปัญหาอย่างรวดเร็ว
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><title>🔍 Quick Error Check</title>";
echo "<style>body{font-family:Arial;margin:20px;} .error{color:red;background:#f8d7da;padding:10px;margin:5px 0;border-radius:3px;} .success{color:green;background:#d4edda;padding:10px;margin:5px 0;border-radius:3px;} .warning{color:orange;background:#fff3cd;padding:10px;margin:5px 0;border-radius:3px;} pre{background:#f8f9fa;padding:10px;}</style>";
echo "</head><body>";

echo "<h1>🔍 Quick Error Check</h1>";

// Test 1: PHP Syntax Issues
echo "<h2>1. Database Connection Test</h2>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        echo "<div class='success'>✅ Database config loaded</div>";
        
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        if ($pdo) {
            echo "<div class='success'>✅ Database connected successfully</div>";
            
            // Test tables
            $tables = ['orders', 'customers'];
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
                    $count = $stmt->fetchColumn();
                    echo "<div class='success'>✅ Table {$table}: {$count} records</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Table {$table} error: " . $e->getMessage() . "</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ Database connection failed</div>";
        }
    } else {
        echo "<div class='error'>❌ Database config file not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

// Test 2: Permissions
echo "<h2>2. Permissions Test</h2>";
try {
    if (file_exists('includes/permissions.php')) {
        require_once 'includes/permissions.php';
        echo "<div class='success'>✅ Permissions file loaded</div>";
        
        // Mock session for testing
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'testuser';
            $_SESSION['role'] = 'sales';
            echo "<div class='warning'>⚠️ Mock session created for testing</div>";
        }
        
        $currentUser = Permissions::getCurrentUser();
        $currentRole = Permissions::getCurrentRole();
        
        echo "<div class='success'>✅ Current user: {$currentUser} (Role: {$currentRole})</div>";
        
    } else {
        echo "<div class='error'>❌ Permissions file not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Permissions error: " . $e->getMessage() . "</div>";
}

// Test 3: Main Layout
echo "<h2>3. Main Layout Test</h2>";
try {
    if (file_exists('includes/main_layout.php')) {
        require_once 'includes/main_layout.php';
        echo "<div class='success'>✅ Main layout file loaded</div>";
    } else {
        echo "<div class='error'>❌ Main layout file not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Main layout error: " . $e->getMessage() . "</div>";
}

// Test 4: API Files
echo "<h2>4. API Files Test</h2>";
$api_files = [
    'api/sales/sales_records_fixed.php',
    'api/sales/sales_records_enhanced.php',
    'api/sales/order_detail.php'
];

foreach ($api_files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ {$file} exists</div>";
        
        // Test if file can be included without syntax errors
        try {
            $content = file_get_contents($file);
            if (strpos($content, '<?php') !== false) {
                echo "<div class='success'>✅ {$file} has PHP opening tag</div>";
            } else {
                echo "<div class='warning'>⚠️ {$file} may not be a valid PHP file</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ {$file} error: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ {$file} not found</div>";
    }
}

// Test 5: Page Files
echo "<h2>5. Page Files Test</h2>";
$page_files = [
    'pages/customer_list_dynamic.php',
    'pages/customer_list_static.php'
];

foreach ($page_files as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ {$file} exists</div>";
        
        $size = filesize($file);
        echo "<div class='success'>✅ {$file} size: " . number_format($size) . " bytes</div>";
        
        if (is_readable($file)) {
            echo "<div class='success'>✅ {$file} is readable</div>";
        } else {
            echo "<div class='error'>❌ {$file} is not readable</div>";
        }
    } else {
        echo "<div class='error'>❌ {$file} not found</div>";
    }
}

// Test 6: Directory Structure
echo "<h2>6. Directory Structure Test</h2>";
$required_dirs = [
    'includes',
    'config',
    'api',
    'api/sales',
    'pages'
];

foreach ($required_dirs as $dir) {
    if (is_dir($dir)) {
        echo "<div class='success'>✅ Directory {$dir} exists</div>";
        
        if (is_readable($dir)) {
            echo "<div class='success'>✅ Directory {$dir} is readable</div>";
        } else {
            echo "<div class='error'>❌ Directory {$dir} is not readable</div>";
        }
    } else {
        echo "<div class='error'>❌ Directory {$dir} not found</div>";
    }
}

// Test 7: Simple API Test
echo "<h2>7. Simple API Test</h2>";
if (isset($_SESSION['user_id'])) {
    try {
        if (file_exists('api/sales/sales_records_fixed.php')) {
            echo "<div class='success'>✅ Attempting to load API...</div>";
            
            ob_start();
            include 'api/sales/sales_records_fixed.php';
            $api_output = ob_get_clean();
            
            if (!empty($api_output)) {
                $api_data = json_decode($api_output, true);
                if ($api_data !== null) {
                    if (isset($api_data['success']) && $api_data['success']) {
                        echo "<div class='success'>✅ API returned success response</div>";
                        echo "<div class='success'>✅ Records found: " . count($api_data['data']['sales_records'] ?? []) . "</div>";
                    } else {
                        echo "<div class='error'>❌ API returned error: " . ($api_data['message'] ?? 'Unknown error') . "</div>";
                    }
                } else {
                    echo "<div class='error'>❌ API returned invalid JSON</div>";
                    echo "<pre>" . htmlspecialchars(substr($api_output, 0, 500)) . "...</pre>";
                }
            } else {
                echo "<div class='error'>❌ API returned empty response</div>";
            }
        } else {
            echo "<div class='error'>❌ API file not found</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ API test error: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='warning'>⚠️ No session data for API test</div>";
}

// Test 8: Error Log Check
echo "<h2>8. Error Logs</h2>";
$possible_logs = [
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    'error_log',
    '../error_log'
];

$found_log = false;
foreach ($possible_logs as $log_file) {
    if ($log_file && file_exists($log_file) && is_readable($log_file)) {
        $found_log = true;
        echo "<div class='success'>✅ Found error log: {$log_file}</div>";
        
        $log_content = file_get_contents($log_file);
        $recent_lines = array_slice(explode("\n", $log_content), -5);
        
        if (!empty(trim(implode('', $recent_lines)))) {
            echo "<div class='warning'>Recent errors:</div>";
            echo "<pre>" . htmlspecialchars(implode("\n", $recent_lines)) . "</pre>";
        } else {
            echo "<div class='success'>✅ No recent errors in log</div>";
        }
        break;
    }
}

if (!$found_log) {
    echo "<div class='warning'>⚠️ No accessible error logs found</div>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. Check the URL: <code>https://www.prima49.com/crm_system/Kiro_CRM_production/pages/customer_list_dynamic.php</code></li>";
echo "<li>2. Ensure you're logged in to the system first</li>";
echo "<li>3. Check browser console for JavaScript errors</li>";
echo "<li>4. Try accessing the old static version: <code>customer_list_static.php</code></li>";
echo "</ul>";

echo "</body></html>";
?>