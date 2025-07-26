<?php
/**
 * Check PHP Error Logs
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 Error Log Check</title>
    <style>body{font-family:Arial;margin:20px} .error{color:red;background:#ffe6e6;padding:10px;margin:10px 0} pre{background:#f5f5f5;padding:10px;overflow-x:auto}</style>
</head>
<body>

<h1>🔍 PHP Error Log Check</h1>

<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h3>Error Reporting Settings:</h3>";
echo "<p>Error Reporting: " . error_reporting() . "</p>";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>";
echo "<p>Log Errors: " . ini_get('log_errors') . "</p>";
echo "<p>Error Log: " . ini_get('error_log') . "</p>";

// Try to load customer_list_demo.php and catch any errors
echo "<h3>Testing customer_list_demo.php Load:</h3>";

try {
    ob_start();
    include 'pages/customer_list_demo.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "<div style='color:green'>✅ File loaded successfully!</div>";
    echo "<p>Output length: " . strlen($output) . " characters</p>";
    
} catch (ParseError $e) {
    echo "<div class='error'>";
    echo "<h4>❌ Parse Error:</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
} catch (Error $e) {
    echo "<div class='error'>";
    echo "<h4>❌ Fatal Error:</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h4>❌ Exception:</h4>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

// Check if error log file exists
$error_log_file = ini_get('error_log');
if ($error_log_file && file_exists($error_log_file)) {
    echo "<h3>Recent Error Log Entries:</h3>";
    $log_content = file_get_contents($error_log_file);
    $log_lines = explode("\n", $log_content);
    $recent_lines = array_slice($log_lines, -20); // Last 20 lines
    
    echo "<pre>";
    foreach ($recent_lines as $line) {
        if (trim($line)) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>No error log file found at: " . ($error_log_file ?: 'not set') . "</p>";
}
?>

</body>
</html>