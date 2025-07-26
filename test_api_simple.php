<?php
/**
 * Simple API Test without CURL
 */

session_start();

// Set test session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>API Test</title></head><body>";
echo "<h1>API Test - Simple</h1>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Testing API Direct Call:</h3>";

try {
    // Capture output
    ob_start();
    include 'api/tasks/daily_simple_fast.php';
    $apiOutput = ob_get_clean();
    
    echo "<h4>API Response:</h4>";
    echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
    
    // Try to decode JSON
    $data = json_decode($apiOutput, true);
    if ($data) {
        echo "<h4>Parsed JSON:</h4>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<p style='color:red'>JSON Parse Error: " . json_last_error_msg() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>