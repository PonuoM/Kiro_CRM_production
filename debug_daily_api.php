<?php
/**
 * Debug script to test daily_simple.php API
 */

// Start session to simulate logged-in user
session_start();

// Set minimal session data for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Daily API</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffe6e6; border-left: 4px solid #ff0000; }
        .success { background: #e6ffe6; border-left: 4px solid #00aa00; }
        pre { background: white; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>Debug Daily Simple API</h1>";

// Test 1: Check if API file exists
echo "<div class='debug-box'>";
echo "<h3>Test 1: API File Check</h3>";
$apiFile = __DIR__ . '/api/tasks/daily_simple.php';
if (file_exists($apiFile)) {
    echo "<p class='success'>✓ API file exists: {$apiFile}</p>";
} else {
    echo "<p class='error'>✗ API file not found: {$apiFile}</p>";
    exit;
}
echo "</div>";

// Test 2: Check database connection
echo "<div class='debug-box'>";
echo "<h3>Test 2: Database Connection</h3>";
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<p class='success'>✓ Database connection successful</p>";
    
    // Test basic query
    $result = $pdo->query("SELECT COUNT(*) as count FROM customers")->fetch();
    echo "<p class='success'>✓ Database query test: Found {$result['count']} customers</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
    exit;
}
echo "</div>";

// Test 3: Check permissions
echo "<div class='debug-box'>";
echo "<h3>Test 3: Permissions Check</h3>";
try {
    require_once 'includes/permissions.php';
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    echo "<p class='success'>✓ Permissions loaded</p>";
    echo "<p>Current User: {$currentUser}</p>";
    echo "<p>Can View All: " . ($canViewAll ? 'Yes' : 'No') . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Permissions error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 4: Make API call
echo "<div class='debug-box'>";
echo "<h3>Test 4: API Call Test</h3>";
$apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/crm_system/Kiro_CRM_production/api/tasks/daily_simple.php';
echo "<p>Testing URL: {$apiUrl}</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "<p class='error'>✗ CURL Error: {$error}</p>";
} else {
    echo "<p>HTTP Code: {$httpCode}</p>";
    
    if ($httpCode == 200) {
        echo "<p class='success'>✓ API call successful</p>";
        
        $data = json_decode($response, true);
        if ($data) {
            echo "<p class='success'>✓ JSON response valid</p>";
            echo "<h4>API Response:</h4>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        } else {
            echo "<p class='error'>✗ Invalid JSON response</p>";
            echo "<h4>Raw Response:</h4>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ API call failed with HTTP {$httpCode}</p>";
        echo "<h4>Response:</h4>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}
echo "</div>";

// Test 5: Direct API inclusion test
echo "<div class='debug-box'>";
echo "<h3>Test 5: Direct API Test</h3>";
echo "<p>Testing API by direct inclusion...</p>";

ob_start();
try {
    // Capture the API output
    include 'api/tasks/daily_simple.php';
    $directResponse = ob_get_contents();
} catch (Exception $e) {
    echo "<p class='error'>✗ Direct API test failed: " . $e->getMessage() . "</p>";
    $directResponse = null;
}
ob_end_clean();

if ($directResponse) {
    echo "<p class='success'>✓ Direct API inclusion successful</p>";
    $directData = json_decode($directResponse, true);
    if ($directData) {
        echo "<p class='success'>✓ Direct API JSON valid</p>";
        echo "<h4>Direct API Response:</h4>";
        echo "<pre>" . json_encode($directData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<p class='error'>✗ Direct API JSON invalid</p>";
        echo "<h4>Direct API Raw Response:</h4>";
        echo "<pre>" . htmlspecialchars($directResponse) . "</pre>";
    }
}
echo "</div>";

echo "</body></html>";
?>