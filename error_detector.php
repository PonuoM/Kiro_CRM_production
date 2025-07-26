<?php
/**
 * Error Detector Tool
 * Get detailed error messages from problematic pages
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Simple authentication
$auth_key = $_GET['key'] ?? '';
if ($auth_key !== 'debug2024') {
    die('Access Denied - Invalid key');
}

$test_url = $_GET['url'] ?? '';
if (empty($test_url)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error Detector</title>
        <style>
            body { font-family: Arial; padding: 20px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
            .url-list { list-style: none; padding: 0; }
            .url-list li { margin: 10px 0; }
            .url-list a { color: #007bff; text-decoration: none; padding: 10px; display: block; border: 1px solid #ddd; border-radius: 5px; }
            .url-list a:hover { background: #f8f9fa; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Error Detector</h1>
            <p>Click on any URL to see detailed error information:</p>
            
            <h3>❌ Problem Pages (Error 500):</h3>
            <ul class="url-list">
                <li><a href="?key=debug2024&url=pages/login.php">Login Page</a></li>
                <li><a href="?key=debug2024&url=pages/admin/user_management.php">User Management</a></li>
                <li><a href="?key=debug2024&url=pages/admin/distribution_basket.php">Distribution Basket</a></li>
                <li><a href="?key=debug2024&url=pages/admin/intelligence_system.php">Intelligence System</a></li>
                <li><a href="?key=debug2024&url=api/auth/login.php">Auth Login API</a></li>
                <li><a href="?key=debug2024&url=api/users/list.php">Users List API</a></li>
            </ul>
            
            <h3>✅ Working Pages (302 Redirects):</h3>
            <ul class="url-list">
                <li><a href="?key=debug2024&url=pages/dashboard.php">Dashboard</a></li>
                <li><a href="?key=debug2024&url=pages/customer_list_demo.php">Customer List Demo</a></li>
                <li><a href="?key=debug2024&url=pages/admin/import_customers.php">Import Customers</a></li>
            </ul>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Test the specified URL
$full_path = __DIR__ . '/' . ltrim($test_url, '/');

if (!file_exists($full_path)) {
    die("File not found: $full_path");
}

echo "<h1>🔍 Error Analysis for: $test_url</h1>";
echo "<p><strong>Full Path:</strong> $full_path</p>";

// Capture any output and errors
ob_start();

// Custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>PHP Error:</strong> $message<br>";
    echo "<strong>File:</strong> $file<br>";
    echo "<strong>Line:</strong> $line<br>";
    echo "<strong>Severity:</strong> $severity";
    echo "</div>";
    return true;
});

// Custom exception handler
set_exception_handler(function($exception) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>Exception:</strong> " . $exception->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><pre>" . $exception->getTraceAsString() . "</pre>";
    echo "</div>";
});

echo "<h2>📋 Attempting to include file...</h2>";

try {
    // Change to the correct directory
    $original_dir = getcwd();
    chdir(dirname($full_path));
    
    // Include the file
    include basename($full_path);
    
    // Restore directory
    chdir($original_dir);
    
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>✅ File included successfully!</strong>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>❌ Exception during include:</strong> " . $e->getMessage();
    echo "</div>";
} catch (Error $e) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>❌ Fatal Error during include:</strong> " . $e->getMessage();
    echo "</div>";
}

$output = ob_get_clean();

// Show the results
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Analysis</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .back-link { color: #007bff; text-decoration: none; }
        .output { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 20px 0; }
        pre { background: #f1f1f1; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <a href="?key=debug2024" class="back-link">← Back to Error Detector</a>
        
        <?php echo $output; ?>
        
        <h2>📄 File Contents Preview:</h2>
        <div class="output">
            <pre><?php echo htmlspecialchars(file_get_contents($full_path)); ?></pre>
        </div>
        
        <h2>📊 File Information:</h2>
        <ul>
            <li><strong>Size:</strong> <?php echo filesize($full_path); ?> bytes</li>
            <li><strong>Modified:</strong> <?php echo date('Y-m-d H:i:s', filemtime($full_path)); ?></li>
            <li><strong>Permissions:</strong> <?php echo substr(sprintf('%o', fileperms($full_path)), -4); ?></li>
            <li><strong>Owner:</strong> <?php echo function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($full_path))['name'] : 'Unknown'; ?></li>
        </ul>
        
        <a href="?key=debug2024" class="back-link">← Back to Error Detector</a>
    </div>
</body>
</html>