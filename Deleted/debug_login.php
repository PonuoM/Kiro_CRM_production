<?php
echo "<h2>🔍 Login Page Debug</h2>";

// Test 1: Check if login.php exists
echo "<h3>1. File Existence:</h3>";
$loginFile = 'pages/login.php';
if(file_exists($loginFile)) {
    echo "✅ $loginFile exists<br>";
    echo "File size: " . filesize($loginFile) . " bytes<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($loginFile)), -4) . "<br>";
    echo "Readable: " . (is_readable($loginFile) ? "Yes" : "No") . "<br>";
} else {
    echo "❌ $loginFile missing<br>";
}

// Test 2: Check required files for login
echo "<h3>2. Required Files Check:</h3>";
$requiredFiles = [
    'config/config.php',
    'config/database.php',
    'includes/functions.php',
    'includes/User.php'
];

foreach($requiredFiles as $file) {
    if(file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 3: Try to include config files and check for errors
echo "<h3>3. Config Files Test:</h3>";
try {
    ob_start();
    include 'config/config.php';
    $configOutput = ob_get_clean();
    echo "✅ config.php loaded successfully<br>";
    if($configOutput) {
        echo "Output: " . htmlspecialchars($configOutput) . "<br>";
    }
} catch(Exception $e) {
    echo "❌ config.php error: " . $e->getMessage() . "<br>";
} catch(Error $e) {
    echo "❌ config.php fatal error: " . $e->getMessage() . "<br>";
}

try {
    ob_start();
    include 'config/database.php';
    $dbOutput = ob_get_clean();
    echo "✅ database.php loaded successfully<br>";
    if($dbOutput) {
        echo "Output: " . htmlspecialchars($dbOutput) . "<br>";
    }
} catch(Exception $e) {
    echo "❌ database.php error: " . $e->getMessage() . "<br>";
} catch(Error $e) {
    echo "❌ database.php fatal error: " . $e->getMessage() . "<br>";
}

// Test 4: Check session functionality
echo "<h3>4. Session Test:</h3>";
if(session_status() == PHP_SESSION_NONE) {
    if(session_start()) {
        echo "✅ Session started successfully<br>";
        echo "Session ID: " . session_id() . "<br>";
    } else {
        echo "❌ Failed to start session<br>";
    }
} else {
    echo "✅ Session already active<br>";
}

// Test 5: Test simple include
echo "<h3>5. Simple Login File Test:</h3>";
if(file_exists($loginFile)) {
    try {
        // Capture any output or errors
        ob_start();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        echo "Attempting to include login.php...<br>";
        
        // Try to read first few lines to check for syntax
        $content = file_get_contents($loginFile, false, null, 0, 500);
        echo "First 500 chars of login.php:<br>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
        
    } catch(Exception $e) {
        echo "❌ Error reading login.php: " . $e->getMessage() . "<br>";
    }
    $output = ob_get_clean();
    echo $output;
}

// Test 6: PHP error log check
echo "<h3>6. Recent PHP Errors:</h3>";
$errorLog = ini_get('error_log');
if($errorLog && file_exists($errorLog)) {
    $errors = file_get_contents($errorLog);
    $recentErrors = array_slice(explode("\n", $errors), -10);
    foreach($recentErrors as $error) {
        if(trim($error)) {
            echo htmlspecialchars($error) . "<br>";
        }
    }
} else {
    echo "No error log found or configured<br>";
}

// Test 7: Create minimal login test
echo "<h3>7. Minimal Login Test:</h3>";
echo '<a href="minimal_login_test.php">🧪 Test Minimal Login</a><br>';

?>

<!-- Create minimal login test file -->
<?php
file_put_contents('minimal_login_test.php', '<?php
echo "<h2>Minimal Login Test</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Current time: " . date("Y-m-d H:i:s") . "<br>";

// Test basic includes
try {
    require_once "config/config.php";
    echo "✅ Config loaded<br>";
} catch(Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
}

try {
    require_once "config/database.php";
    echo "✅ Database config loaded<br>";
} catch(Exception $e) {
    echo "❌ Database config error: " . $e->getMessage() . "<br>";
}

// Test database connection
try {
    $db = Database::getInstance();
    echo "✅ Database connected<br>";
} catch(Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>If all green, the problem is in login.php itself</strong>";
?>');

echo "✅ Created minimal_login_test.php<br>";
?>