<?php
/**
 * Debug Check - ตรวจสอบ error และปัญหาต่างๆ ในระบบ
 */

echo "<!DOCTYPE html>\n<html><head><title>🔍 Debug Check - Error Detection</title>";
echo "<style>
body { font-family: Arial; margin: 20px; }
.section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.success { background: #d4edda; border-color: #c3e6cb; }
.error { background: #f8d7da; border-color: #f5c6cb; }
.warning { background: #fff3cd; border-color: #ffeaa7; }
.info { background: #d1ecf1; border-color: #bee5eb; }
pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
.test-item { margin: 10px 0; padding: 8px; border-left: 4px solid #007bff; }
</style>";
echo "</head><body>";

echo "<h1>🔍 Debug Check - Error Detection System</h1>";

// Test 1: PHP Syntax Check
echo "<div class='section info'>";
echo "<h2>📝 Test 1: PHP Syntax Check</h2>";

$files_to_check = [
    'pages/customer_list_dynamic.php',
    'api/sales/sales_records_enhanced.php',
    'api/sales/order_detail.php'
];

foreach ($files_to_check as $file) {
    echo "<div class='test-item'>";
    echo "<strong>Checking: {$file}</strong><br>";
    
    if (file_exists($file)) {
        // Check PHP syntax
        $output = [];
        $return_var = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "<span style='color: green;'>✅ Syntax OK</span>";
        } else {
            echo "<span style='color: red;'>❌ Syntax Error:</span><br>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
    } else {
        echo "<span style='color: orange;'>⚠️ File not found</span>";
    }
    echo "</div>";
}
echo "</div>";

// Test 2: File Permissions
echo "<div class='section info'>";
echo "<h2>🔐 Test 2: File Permissions</h2>";
foreach ($files_to_check as $file) {
    echo "<div class='test-item'>";
    echo "<strong>{$file}:</strong> ";
    
    if (file_exists($file)) {
        $perms = fileperms($file);
        $perms_str = sprintf('%o', $perms);
        echo "Permissions: {$perms_str} ";
        
        if (is_readable($file)) {
            echo "<span style='color: green;'>✅ Readable</span> ";
        } else {
            echo "<span style='color: red;'>❌ Not Readable</span> ";
        }
        
        if (is_writable($file)) {
            echo "<span style='color: green;'>✅ Writable</span>";
        } else {
            echo "<span style='color: orange;'>⚠️ Not Writable</span>";
        }
    } else {
        echo "<span style='color: red;'>❌ File not found</span>";
    }
    echo "</div>";
}
echo "</div>";

// Test 3: Database Connection
echo "<div class='section info'>";
echo "<h2>💾 Test 3: Database Connection</h2>";
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        if ($pdo) {
            echo "<div class='test-item success'>";
            echo "✅ Database connection successful<br>";
            echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
            echo "</div>";
            
            // Test database structure
            echo "<h3>📊 Database Structure Check:</h3>";
            
            // Check orders table
            try {
                $stmt = $pdo->query("DESCRIBE orders");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='test-item success'>";
                echo "<strong>✅ Orders table exists</strong><br>";
                echo "Columns: ";
                $col_names = array_column($columns, 'Field');
                echo implode(', ', $col_names);
                echo "</div>";
                
                // Check for required columns
                $required_columns = ['id', 'DocumentNo', 'CustomerCode', 'DocumentDate', 'Products', 'OrderBy', 'Price'];
                $missing_columns = array_diff($required_columns, $col_names);
                
                if (empty($missing_columns)) {
                    echo "<div class='test-item success'>✅ All required columns present</div>";
                } else {
                    echo "<div class='test-item error'>❌ Missing columns: " . implode(', ', $missing_columns) . "</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='test-item error'>❌ Orders table error: " . $e->getMessage() . "</div>";
            }
            
            // Check customers table
            try {
                $stmt = $pdo->query("DESCRIBE customers");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='test-item success'>";
                echo "<strong>✅ Customers table exists</strong><br>";
                echo "Columns: ";
                $col_names = array_column($columns, 'Field');
                echo implode(', ', $col_names);
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='test-item error'>❌ Customers table error: " . $e->getMessage() . "</div>";
            }
            
        } else {
            echo "<div class='test-item error'>❌ Failed to get database connection</div>";
        }
    } else {
        echo "<div class='test-item error'>❌ Database config file not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item error'>❌ Database connection error: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 4: Session & Authentication
echo "<div class='section info'>";
echo "<h2>🔑 Test 4: Session & Authentication</h2>";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<div class='test-item'>";
echo "<strong>Session Status:</strong> ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "<span style='color: red;'>❌ Sessions Disabled</span>";
        break;
    case PHP_SESSION_NONE:
        echo "<span style='color: orange;'>⚠️ No Session Started</span>";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<span style='color: green;'>✅ Session Active</span>";
        break;
}
echo "</div>";

echo "<div class='test-item'>";
echo "<strong>Session Data:</strong><br>";
if (!empty($_SESSION)) {
    echo "<pre>";
    foreach ($_SESSION as $key => $value) {
        if ($key !== 'password') { // Don't show passwords
            echo "{$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "No session data (user not logged in)";
}
echo "</div>";

// Test permissions
if (file_exists('includes/permissions.php')) {
    echo "<div class='test-item'>";
    echo "<strong>Permissions Check:</strong> ";
    try {
        require_once 'includes/permissions.php';
        echo "<span style='color: green;'>✅ Permissions file loaded</span>";
    } catch (Exception $e) {
        echo "<span style='color: red;'>❌ Permissions error: " . $e->getMessage() . "</span>";
    }
    echo "</div>";
} else {
    echo "<div class='test-item error'>❌ Permissions file not found</div>";
}

echo "</div>";

// Test 5: API Endpoint Testing
echo "<div class='section info'>";
echo "<h2>🌐 Test 5: API Endpoint Testing</h2>";

// Mock session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'testuser';
    $_SESSION['role'] = 'sales';
    echo "<div class='test-item warning'>⚠️ Mock session created for testing</div>";
}

$api_endpoints = [
    'api/sales/sales_records_enhanced.php',
    'api/sales/sales_records_enhanced.php?month=2025-01',
    'api/sales/sales_records_enhanced.php?product=ปุ๋ย',
    'api/sales/order_detail.php?id=1'
];

foreach ($api_endpoints as $endpoint) {
    echo "<div class='test-item'>";
    echo "<strong>Testing: {$endpoint}</strong><br>";
    
    if (file_exists($endpoint)) {
        try {
            // Capture output
            ob_start();
            $old_get = $_GET;
            
            // Parse query string
            $url_parts = parse_url($endpoint);
            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $_GET);
            }
            
            include $endpoint;
            $output = ob_get_clean();
            
            $_GET = $old_get; // Restore original GET
            
            // Try to decode JSON
            $json_data = json_decode($output, true);
            if ($json_data !== null) {
                if (isset($json_data['success']) && $json_data['success']) {
                    echo "<span style='color: green;'>✅ API Response OK</span><br>";
                    if (isset($json_data['data'])) {
                        echo "Data keys: " . implode(', ', array_keys($json_data['data']));
                    }
                } else {
                    echo "<span style='color: red;'>❌ API Error: " . ($json_data['message'] ?? 'Unknown error') . "</span>";
                }
            } else {
                echo "<span style='color: orange;'>⚠️ Invalid JSON response</span><br>";
                echo "<pre>" . htmlspecialchars(substr($output, 0, 200)) . "...</pre>";
            }
            
        } catch (Exception $e) {
            echo "<span style='color: red;'>❌ Exception: " . $e->getMessage() . "</span>";
        }
    } else {
        echo "<span style='color: red;'>❌ File not found</span>";
    }
    echo "</div>";
}
echo "</div>";

// Test 6: JavaScript Syntax Check
echo "<div class='section info'>";
echo "<h2>🟨 Test 6: Page Content Analysis</h2>";

if (file_exists('pages/customer_list_dynamic.php')) {
    $content = file_get_contents('pages/customer_list_dynamic.php');
    
    echo "<div class='test-item'>";
    echo "<strong>File Size:</strong> " . number_format(strlen($content)) . " bytes<br>";
    echo "<strong>Line Count:</strong> " . substr_count($content, "\n") . " lines<br>";
    echo "</div>";
    
    // Check for common issues
    $issues = [];
    
    // Check for syntax issues
    if (strpos($content, '<?php') === false) {
        $issues[] = "No PHP opening tag found";
    }
    
    // Check for unclosed PHP tags
    if (substr_count($content, '<?php') !== substr_count($content, '?>')) {
        $issues[] = "Mismatched PHP tags";
    }
    
    // Check for JavaScript syntax issues
    if (strpos($content, 'function') !== false) {
        $js_start = strpos($content, '<script>');
        $js_end = strpos($content, '</script>');
        
        if ($js_start !== false && $js_end !== false) {
            echo "<div class='test-item success'>✅ JavaScript section found</div>";
        }
    }
    
    // Check for missing semicolons in JavaScript
    $js_pattern = '/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[\'"][^\'"]*[\'"](?!\s*;)/';
    if (preg_match($js_pattern, $content)) {
        $issues[] = "Possible missing semicolons in JavaScript";
    }
    
    if (empty($issues)) {
        echo "<div class='test-item success'>✅ No obvious syntax issues found</div>";
    } else {
        echo "<div class='test-item warning'>";
        echo "<strong>⚠️ Potential Issues:</strong><br>";
        foreach ($issues as $issue) {
            echo "• {$issue}<br>";
        }
        echo "</div>";
    }
    
} else {
    echo "<div class='test-item error'>❌ Main page file not found</div>";
}
echo "</div>";

// Test 7: Error Log Check
echo "<div class='section info'>";
echo "<h2>📋 Test 7: Error Log Analysis</h2>";

$log_files = [
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    'error.log',
    '../error.log'
];

$found_logs = false;
foreach ($log_files as $log_file) {
    if (file_exists($log_file) && is_readable($log_file)) {
        $found_logs = true;
        echo "<div class='test-item'>";
        echo "<strong>Log file: {$log_file}</strong><br>";
        
        $log_content = tail($log_file, 10);
        if (!empty($log_content)) {
            echo "<pre>" . htmlspecialchars($log_content) . "</pre>";
        } else {
            echo "No recent errors in log";
        }
        echo "</div>";
        break; // Only show first found log
    }
}

if (!$found_logs) {
    echo "<div class='test-item warning'>⚠️ No accessible error logs found</div>";
}

echo "</div>";

// Summary and Recommendations
echo "<div class='section info'>";
echo "<h2>📊 Summary and Recommendations</h2>";

echo "<div class='test-item'>";
echo "<strong>🔍 Common Issues to Check:</strong><br>";
echo "<ul>";
echo "<li>1. Database connection credentials</li>";
echo "<li>2. Missing PHP extensions</li>";
echo "<li>3. File permissions (especially API files)</li>";
echo "<li>4. Session configuration</li>";
echo "<li>5. JavaScript syntax errors</li>";
echo "<li>6. Missing database tables/columns</li>";
echo "</ul>";
echo "</div>";

echo "<div class='test-item'>";
echo "<strong>🛠️ Quick Fixes:</strong><br>";
echo "<ul>";
echo "<li>• Clear browser cache and cookies</li>";
echo "<li>• Check Apache/Nginx error logs</li>";
echo "<li>• Verify database server is running</li>";
echo "<li>• Test with simple PHP file first</li>";
echo "<li>• Enable PHP error reporting temporarily</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

// Helper function for tailing log files
function tail($file, $lines = 10) {
    if (!file_exists($file) || !is_readable($file)) {
        return '';
    }
    
    $handle = fopen($file, 'r');
    if (!$handle) {
        return '';
    }
    
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $linecounter--;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines - $linecounter - 1] = fgets($handle);
        if ($beginning) break;
    }
    fclose($handle);
    
    return implode("", array_reverse($text));
}

echo "</body></html>";
?>