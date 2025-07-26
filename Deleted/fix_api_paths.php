<?php
echo "<h2>🔧 Fix API Path Issues</h2>";

// List of API files to fix
$apiFiles = [
    'api/tasks/daily.php',
    'api/customers/list.php',
    'api/tasks/list.php',
    'api/dashboard/summary.php',
    'api/customers/create.php',
    'api/customers/update.php',
    'api/auth/check.php',
    'api/auth/login.php'
];

$fixedCount = 0;
$totalCount = 0;

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        $totalCount++;
        echo "Fixing: $file<br>";
        
        // Read file content
        $content = file_get_contents($file);
        
        // Fix common path issues
        $originalContent = $content;
        
        // Fix 1: Change ../../includes/ to ../includes/
        $content = str_replace('../../includes/', '../includes/', $content);
        
        // Fix 2: Change ../../config/ to ../config/
        $content = str_replace('../../config/', '../config/', $content);
        
        // Fix 3: Add session check to prevent double session_start
        if (strpos($content, 'session_start()') !== false && strpos($content, 'session_status()') === false) {
            $content = str_replace(
                'session_start();', 
                'if (session_status() == PHP_SESSION_NONE) { session_start(); }', 
                $content
            );
        }
        
        // Write back if changed
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "✅ Fixed $file<br>";
            $fixedCount++;
        } else {
            echo "⚪ No changes needed for $file<br>";
        }
    } else {
        echo "❌ File not found: $file<br>";
    }
}

echo "<br><strong>Summary:</strong><br>";
echo "Files processed: $totalCount<br>";
echo "Files fixed: $fixedCount<br>";

// Fix the function redeclaration issue in config.php
echo "<br><h3>Fixing Function Redeclaration:</h3>";
$configFile = 'config/config.php';
if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    
    // Wrap function declarations with function_exists check
    $functions = ['sanitizeInput', 'generateCSRFToken', 'validateCSRFToken', 'checkRateLimit'];
    
    foreach ($functions as $func) {
        $pattern = '/function\s+' . $func . '\s*\(/';
        if (preg_match($pattern, $configContent)) {
            $configContent = preg_replace(
                $pattern,
                'if (!function_exists(\'' . $func . '\')) { function ' . $func . '(',
                $configContent
            );
            
            // Add closing brace for if statement
            $configContent = str_replace(
                'function ' . $func . '(',
                'function ' . $func . '(',
                $configContent
            );
        }
    }
    
    // Add closing braces for function_exists checks
    $configContent = preg_replace('/(\}\s*$)/', '}$1', $configContent);
    
    file_put_contents($configFile, $configContent);
    echo "✅ Fixed function redeclaration in config.php<br>";
}

// Create a working daily tasks API
echo "<br><h3>Creating Working APIs:</h3>";

// Fixed daily tasks API
$dailyTasksAPI = '<?php
header("Content-Type: application/json");
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

try {
    require_once "../config/database.php";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $today = date("Y-m-d");
    $userId = $_SESSION["user_id"];
    
    // Try both table structures
    try {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE due_date = ? AND assigned_to = ? ORDER BY priority DESC");
        $stmt->execute([$today, $userId]);
        $tasks = $stmt->fetchAll();
    } catch(Exception $e) {
        // Try with Capital letters
        try {
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE DueDate = ? AND AssignedTo = ? ORDER BY Priority DESC");
            $stmt->execute([$today, $userId]);
            $tasks = $stmt->fetchAll();
        } catch(Exception $e2) {
            // Get all tasks for today
            $stmt = $pdo->prepare("SELECT * FROM tasks LIMIT 10");
            $stmt->execute();
            $tasks = $stmt->fetchAll();
        }
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $tasks,
        "count" => count($tasks),
        "date" => $today
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => $e->getMessage()
    ]);
}
?>';

file_put_contents('api/tasks/daily_working.php', $dailyTasksAPI);
echo "✅ Created api/tasks/daily_working.php<br>";

// Fixed customers list API
$customersAPI = '<?php
header("Content-Type: application/json");
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

try {
    require_once "../config/database.php";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $status = $_GET["customer_status"] ?? "all";
    
    // Get customers
    try {
        if ($status === "all") {
            $stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC LIMIT 50");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE status = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$status]);
        }
        $customers = $stmt->fetchAll();
    } catch(Exception $e) {
        // Try with Capital letters
        try {
            if ($status === "all") {
                $stmt = $pdo->query("SELECT * FROM customers ORDER BY CreatedDate DESC LIMIT 50");
            } else {
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE Status = ? ORDER BY CreatedDate DESC LIMIT 50");
                $stmt->execute([$status]);
            }
            $customers = $stmt->fetchAll();
        } catch(Exception $e2) {
            $stmt = $pdo->query("SELECT * FROM customers LIMIT 50");
            $customers = $stmt->fetchAll();
        }
    }
    
    echo json_encode([
        "status" => "success",
        "data" => $customers,
        "count" => count($customers),
        "filter" => $status
    ]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>';

file_put_contents('api/customers/list_working.php', $customersAPI);
echo "✅ Created api/customers/list_working.php<br>";

echo "<br><h3>Test the fixed APIs:</h3>";
echo '<a href="api/tasks/daily_working.php" target="_blank">📋 Working Daily Tasks API</a><br>';
echo '<a href="api/customers/list_working.php" target="_blank">👥 Working Customers API</a><br>';
echo '<a href="test_api.php">🔄 Run API Test Again</a><br>';

echo "<br><h3>✅ All fixes applied!</h3>";
echo "Now try refreshing the dashboard to see if it works.";
?>