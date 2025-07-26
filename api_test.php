<?php
/**
 * Test API Response
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 API Test</title>
    <style>body{font-family:Arial;margin:20px} pre{background:#f5f5f5;padding:10px;overflow-x:auto}</style>
</head>
<body>

<h1>🔍 Sales API Test</h1>

<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>Session Info:</h3>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "</p>";
echo "<p>Username: " . ($_SESSION['username'] ?? 'NOT SET') . "</p>";
echo "<p>User Role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "</p>";

// Test API directly
echo "<h3>API Response Test:</h3>";

try {
    // Load and execute API
    ob_start();
    include 'api/sales/sales_records.php';
    $api_output = ob_get_clean();
    
    echo "<h4>✅ API executed successfully</h4>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
    
    // Parse JSON
    $data = json_decode($api_output, true);
    if ($data) {
        echo "<h4>📊 Parsed Data:</h4>";
        echo "<p><strong>Success:</strong> " . ($data['success'] ? 'YES' : 'NO') . "</p>";
        
        if (isset($data['data']['summary'])) {
            $summary = $data['data']['summary'];
            echo "<p><strong>Total Orders:</strong> " . $summary['total_orders'] . "</p>";
            echo "<p><strong>Total Sales:</strong> " . $summary['total_sales'] . "</p>";
            echo "<p><strong>Today Orders:</strong> " . $summary['today_orders'] . "</p>";
            echo "<p><strong>Month Orders:</strong> " . $summary['month_orders'] . "</p>";
        }
        
        if (isset($data['data']['sales_records'])) {
            echo "<p><strong>Sales Records Count:</strong> " . count($data['data']['sales_records']) . "</p>";
        }
    } else {
        echo "<h4>❌ Failed to parse JSON</h4>";
    }
    
} catch (Exception $e) {
    echo "<h4>❌ API Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test direct URL call
echo "<h3>🌐 Direct URL Test:</h3>";
$api_url = "https://www.prima49.com/crm_system/Kiro_CRM_production/api/sales/sales_records.php";
echo "<p><a href='{$api_url}' target='_blank'>Open API directly: {$api_url}</a></p>";
?>

<h3>📝 Instructions:</h3>
<ol>
<li>Check if API response shows success: true</li>
<li>Verify summary data has numbers > 0</li>
<li>Check sales_records array has data</li>
<li>Try opening API URL directly in new tab</li>
</ol>

</body>
</html>