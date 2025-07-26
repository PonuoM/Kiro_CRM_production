<?php
/**
 * Fix All Universal Login Redirects
 * This script will replace all ../universal_login.php with login.php in pages directory
 */

echo "<h2>🔧 Fix All Login Redirects</h2>\n";

// Get all PHP files in pages directory
$files = glob(__DIR__ . '/pages/*.php');
$files = array_merge($files, glob(__DIR__ . '/pages/admin/*.php'));

$totalFiles = 0;
$modifiedFiles = 0;
$totalReplacements = 0;

foreach ($files as $file) {
    $totalFiles++;
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace all variations
    $patterns = [
        '../universal_login.php',
        "'../universal_login.php'",
        '"../universal_login.php"',
        'universal_login.php',
        "'universal_login.php'", 
        '"universal_login.php"'
    ];
    
    $replacements = [
        'login.php',
        "'login.php'",
        '"login.php"',
        'login.php',
        "'login.php'",
        '"login.php"'
    ];
    
    $content = str_replace($patterns, $replacements, $content, $count);
    
    if ($count > 0) {
        file_put_contents($file, $content);
        $modifiedFiles++;
        $totalReplacements += $count;
        
        $relativePath = str_replace(__DIR__ . '/', '', $file);
        echo "<p>✅ <strong>{$relativePath}</strong> - {$count} replacements</p>\n";
    }
}

echo "<hr>\n";
echo "<h3>📊 Summary:</h3>\n";
echo "<p><strong>Total files checked:</strong> {$totalFiles}</p>\n";
echo "<p><strong>Files modified:</strong> {$modifiedFiles}</p>\n";
echo "<p><strong>Total replacements:</strong> {$totalReplacements}</p>\n";

if ($totalReplacements > 0) {
    echo "<p>✅ <strong>All redirects fixed successfully!</strong></p>\n";
    echo "<p>Now all pages should redirect to <code>login.php</code> instead of <code>universal_login.php</code></p>\n";
} else {
    echo "<p>ℹ️ No replacements needed - all files already correct</p>\n";
}

// Test some important files
echo "<h3>🧪 Verification:</h3>\n";
$testFiles = [
    'pages/dashboard.php',
    'pages/customer_detail.php', 
    'pages/customer_intelligence.php',
    'pages/sales_performance.php'
];

foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        $content = file_get_contents($testFile);
        $hasUniversal = strpos($content, 'universal_login.php') !== false;
        $hasLogin = strpos($content, 'login.php') !== false;
        
        echo "<p><strong>{$testFile}:</strong> ";
        if ($hasUniversal) {
            echo "❌ Still has universal_login.php";
        } elseif ($hasLogin) {
            echo "✅ Uses login.php";
        } else {
            echo "⚠️ No login redirect found";
        }
        echo "</p>\n";
    }
}

echo "<hr>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ol>\n";
echo "<li>Test login at <a href='pages/login.php'>pages/login.php</a></li>\n";
echo "<li>Verify dashboard access after login</li>\n";
echo "<li>Delete this file after testing</li>\n";
echo "</ol>\n";

echo "<p><small>⚠️ Delete this file after use for security</small></p>\n";
?>