<?php
/**
 * Direct API Test - Test login API without browser complications
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

echo "<h2>🧪 Direct API Test</h2>\n";

// Generate CSRF token
$csrf_token = generateCSRFToken();
echo "<p><strong>CSRF Token:</strong> <code>" . $csrf_token . "</code></p>\n";
echo "<p><strong>Session ID:</strong> <code>" . session_id() . "</code></p>\n";

// Test data
$test_data = [
    'username' => 'admin',
    'password' => 'admin123',
    'csrf_token' => $csrf_token
];

echo "<h3>Test Data:</h3>\n";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";

// Make API call using cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.prima49.com/crm_system/Kiro_CRM_production/api/auth/login.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($test_data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Cookie: ' . session_name() . '=' . session_id()
    ],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h3>API Response:</h3>\n";
echo "<p><strong>HTTP Code:</strong> " . $http_code . "</p>\n";

if ($curl_error) {
    echo "<p><strong>cURL Error:</strong> " . $curl_error . "</p>\n";
}

echo "<p><strong>Raw Response:</strong></p>\n";
echo "<textarea rows='10' cols='100' style='font-family: monospace;'>" . htmlspecialchars($response) . "</textarea>\n";

// Try to decode JSON
$json_data = json_decode($response, true);
$json_error = json_last_error();

echo "<h3>JSON Analysis:</h3>\n";
if ($json_error === JSON_ERROR_NONE && $json_data !== null) {
    echo "<p>✅ <strong>Valid JSON</strong></p>\n";
    echo "<pre>" . json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
    
    if (isset($json_data['success'])) {
        if ($json_data['success']) {
            echo "<p>✅ <strong>Login Successful!</strong></p>\n";
        } else {
            echo "<p>❌ <strong>Login Failed:</strong> " . ($json_data['message'] ?? 'Unknown error') . "</p>\n";
        }
    }
} else {
    echo "<p>❌ <strong>Invalid JSON</strong></p>\n";
    echo "<p><strong>JSON Error:</strong> " . json_last_error_msg() . "</p>\n";
    
    // Show first 500 characters for debugging
    echo "<p><strong>Response Preview:</strong></p>\n";
    echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>\n";
}

// Test alternative approach - include the API file directly
echo "<hr>\n";
echo "<h3>🔄 Direct Include Test</h3>\n";

// Capture output
ob_start();

// Simulate POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = []; // Clear POST
file_put_contents('php://input', json_encode($test_data));

try {
    // Include API file directly
    include __DIR__ . '/api/auth/login.php';
} catch (Exception $e) {
    echo "<p>❌ <strong>Exception:</strong> " . $e->getMessage() . "</p>\n";
}

$direct_output = ob_get_clean();

echo "<p><strong>Direct Include Output:</strong></p>\n";
echo "<textarea rows='10' cols='100' style='font-family: monospace;'>" . htmlspecialchars($direct_output) . "</textarea>\n";

?>

<h3>📋 Manual Test Form</h3>
<form action="api/auth/login.php" method="post" target="_blank">
    <input type="hidden" name="test_mode" value="1">
    <p>
        <label>Username: </label>
        <input type="text" name="username" value="admin" required>
    </p>
    <p>
        <label>Password: </label>
        <input type="password" name="password" value="admin123" required>
    </p>
    <p>
        <label>CSRF Token: </label>
        <input type="text" name="csrf_token" value="<?php echo $csrf_token; ?>" required style="width: 400px;">
    </p>
    <p>
        <button type="submit">📤 Submit to API (Form)</button>
    </p>
</form>

<script>
// JavaScript test
async function testApiCall() {
    const testData = {
        username: 'admin',
        password: 'admin123',
        csrf_token: '<?php echo $csrf_token; ?>'
    };
    
    console.log('Sending:', testData);
    
    try {
        const response = await fetch('api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(testData),
            credentials: 'same-origin'
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        try {
            const json = JSON.parse(text);
            console.log('Parsed JSON:', json);
            alert('Success! Check console for details');
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('JSON parse error: ' + e.message + '\n\nRaw response: ' + text.substring(0, 200));
        }
        
    } catch (error) {
        console.error('Fetch error:', error);
        alert('Fetch error: ' + error.message);
    }
}
</script>

<p>
    <button onclick="testApiCall()" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        🚀 Test JavaScript Fetch
    </button>
</p>

<p><small>⚠️ Delete this file after testing</small></p>