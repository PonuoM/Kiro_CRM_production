<?php
/**
 * Final Debug - Check exact error
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Final Debug Check</h1>";
echo "<style>body{font-family:Arial;margin:20px} .error{color:red;background:#ffe6e6;padding:10px;margin:10px 0} .success{color:green;background:#e6ffe6;padding:10px;margin:10px 0}</style>";

// Test PHP syntax first
echo "<h3>1. PHP Syntax Check</h3>";
$output = [];
$return_var = 0;
exec('php -l pages/customer_list_demo.php 2>&1', $output, $return_var);

if ($return_var === 0) {
    echo "<div class='success'>✅ PHP Syntax OK</div>";
} else {
    echo "<div class='error'>❌ PHP Syntax Error:</div>";
    foreach ($output as $line) {
        echo "<div class='error'>$line</div>";
    }
}

// Test actual execution
echo "<h3>2. Execution Test</h3>";
try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set test session if needed
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 7;
        $_SESSION['username'] = 'sales01';
        $_SESSION['user_role'] = 'Sales';
    }
    
    ob_start();
    include 'pages/customer_list_demo.php';
    $content = ob_get_contents();
    ob_end_clean();
    
    echo "<div class='success'>✅ File executed successfully</div>";
    echo "<p>Generated content length: " . strlen($content) . " characters</p>";
    
    // Check for JavaScript errors in output
    if (strpos($content, 'SyntaxError') !== false) {
        echo "<div class='error'>❌ JavaScript syntax error detected in output</div>";
    }
    
} catch (ParseError $e) {
    echo "<div class='error'>❌ Parse Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";  
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
} catch (Error $e) {
    echo "<div class='error'>❌ Fatal Error: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
    echo "<div class='error'>File: " . $e->getFile() . "</div>";
    echo "<div class='error'>Line: " . $e->getLine() . "</div>";
}

// Check specific lines that had issues
echo "<h3>3. Problem Line Check</h3>";
$content = file_get_contents('pages/customer_list_demo.php');
$lines = explode("\n", $content);

$problem_lines = [308, 309, 310, 420, 421];
foreach ($problem_lines as $line_num) {
    if (isset($lines[$line_num - 1])) {
        echo "<strong>Line $line_num:</strong><br>";
        echo "<code>" . htmlspecialchars($lines[$line_num - 1]) . "</code><br><br>";
    }
}

// Test JavaScript extraction
echo "<h3>4. JavaScript Section Test</h3>";
preg_match('/\$additionalJS = \'(.*?)\';/s', $content, $matches);
if ($matches) {
    $js_content = $matches[1];
    echo "<p>JavaScript length: " . strlen($js_content) . " characters</p>";
    
    // Check for problematic patterns
    if (strpos($js_content, '${') !== false && strpos($js_content, '`') !== false) {
        echo "<div class='error'>❌ Template literal detected in JavaScript</div>";
    } else {
        echo "<div class='success'>✅ No template literal issues</div>";
    }
}
?>

<h3>📋 Next Steps:</h3>
<ol>
<li>Check the results above for specific errors</li>
<li>If PHP syntax is OK but execution fails, it's a runtime error</li>
<li>If JavaScript issues, check the JavaScript section</li>
</ol>
