<?php
/**
 * Find all JavaScript syntax issues
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 JavaScript Syntax Check</title>
    <style>body{font-family:Arial;margin:20px} .error{color:red;background:#ffe6e6;padding:10px;margin:10px 0} .line{margin:5px 0}</style>
</head>
<body>

<h1>🔍 JavaScript Syntax Check</h1>

<?php
$file = 'pages/customer_list_demo.php';
$content = file_get_contents($file);
$lines = explode("\n", $content);

// Find JavaScript section
$js_start = false;
$js_lines = [];
$line_numbers = [];

foreach ($lines as $num => $line) {
    if (strpos($line, '$additionalJS = \'') !== false || strpos($line, '<script>') !== false) {
        $js_start = true;
    }
    
    if ($js_start) {
        $js_lines[] = $line;
        $line_numbers[] = $num + 1;
    }
    
    if (strpos($line, '</script>') !== false || strpos($line, '\';') !== false) {
        break;
    }
}

echo "<h3>🔍 JavaScript Code Analysis:</h3>";

// Check for problematic patterns
$issues = [];

foreach ($js_lines as $i => $line) {
    $line_num = $line_numbers[$i];
    
    // Check for nested template literals
    if (preg_match('/`[^`]*\$\{[^}]*`[^`]*\$\{[^}]*\}[^`]*`/', $line)) {
        $issues[] = "Line {$line_num}: Nested template literal detected";
    }
    
    // Check for mixed quotes in template literals
    if (preg_match('/`[^`]*\'[^`]*\$\{[^}]*\}[^`]*\'[^`]*`/', $line)) {
        $issues[] = "Line {$line_num}: Mixed quotes in template literal";
    }
    
    // Check for backtick issues
    $backtick_count = substr_count($line, '`');
    if ($backtick_count % 2 !== 0 && strpos($line, '${') !== false) {
        $issues[] = "Line {$line_num}: Unmatched backticks with template literal";
    }
    
    // Show problematic lines
    if (strpos($line, '${sale.CustomerTel') !== false) {
        echo "<div class='error'>";
        echo "<strong>Line {$line_num} (PROBLEMATIC):</strong><br>";
        echo "<code>" . htmlspecialchars($line) . "</code>";
        echo "</div>";
    }
}

if (!empty($issues)) {
    echo "<h3>❌ Issues Found:</h3>";
    foreach ($issues as $issue) {
        echo "<div class='error'>{$issue}</div>";
    }
} else {
    echo "<h3>✅ No obvious syntax issues detected</h3>";
}

// Show suggestion
echo "<h3>💡 Suggested Fix:</h3>";
echo "<div class='line'>";
echo "<strong>Replace Line 308-309 with:</strong><br>";
echo "<code>";
echo htmlspecialchars('${sale.CustomerTel ? ');
echo "<br>";
echo htmlspecialchars('  "<button class=\"btn btn-outline-info\" onclick=\"callCustomer(\'" + sale.CustomerTel + "\')\"><i class=\"fas fa-phone\"></i></button>" : ""}');
echo "</code>";
echo "</div>";
?>

</body>
</html>