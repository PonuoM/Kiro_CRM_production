<?php
/**
 * CSRF Debug Helper
 * Use this to test CSRF token generation and verification
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/functions.php';

echo "<h2>🔍 CSRF Token Debug</h2>\n";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>\n";

// Test 1: Generate token
echo "<h3>Test 1: Generate CSRF Token</h3>\n";
try {
    $csrf_token = generateCSRFToken();
    echo "<p>✅ Generated Token: <code>" . $csrf_token . "</code></p>\n";
    echo "<p>Session Token: <code>" . ($_SESSION['csrf_token'] ?? 'not set') . "</code></p>\n";
    echo "<p>Match: " . (($csrf_token === $_SESSION['csrf_token']) ? '✅ YES' : '❌ NO') . "</p>\n";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}

// Test 2: Verify token
echo "<h3>Test 2: Verify CSRF Token</h3>\n";
if (isset($csrf_token)) {
    $verify = verifyCSRFToken($csrf_token);
    echo "<p>Verification Result: " . ($verify ? '✅ PASS' : '❌ FAIL') . "</p>\n";
    
    // Test with wrong token
    $wrong_token = 'wrong_token_123';
    $verify_wrong = verifyCSRFToken($wrong_token);
    echo "<p>Wrong Token Test: " . ($verify_wrong ? '❌ BAD (should fail)' : '✅ GOOD (correctly failed)') . "</p>\n";
}

// Test 3: Session status
echo "<h3>Test 3: Session Information</h3>\n";
echo "<p>Session Status: " . session_status() . " (1=disabled, 2=active, 3=none)</p>\n";
echo "<p>Session ID: " . session_id() . "</p>\n";
echo "<p>Session Save Path: " . session_save_path() . "</p>\n";
echo "<p>Session Cookie Params:</p>\n";
echo "<pre>" . print_r(session_get_cookie_params(), true) . "</pre>\n";

// Test 4: Manual API call simulation
if ($_POST) {
    echo "<h3>Test 4: API Call Simulation</h3>\n";
    
    $test_data = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'csrf_token' => $_POST['csrf_token'] ?? ''
    ];
    
    echo "<p>Received CSRF: <code>" . $test_data['csrf_token'] . "</code></p>\n";
    echo "<p>Session CSRF: <code>" . ($_SESSION['csrf_token'] ?? 'not set') . "</code></p>\n";
    
    $verify = verifyCSRFToken($test_data['csrf_token']);
    echo "<p>Verification: " . ($verify ? '✅ PASS' : '❌ FAIL') . "</p>\n";
}

?>

<h3>🧪 Manual Test Form</h3>
<form method="POST">
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
        <input type="text" name="csrf_token" value="<?php echo $csrf_token ?? ''; ?>" required style="width: 400px;">
    </p>
    <p>
        <button type="submit">🔬 Test CSRF Verification</button>
    </p>
</form>

<hr>
<h3>🎯 JavaScript Test</h3>
<script>
console.log('CSRF Token from PHP:', '<?php echo $csrf_token ?? ''; ?>');

async function testLogin() {
    const formData = {
        username: 'admin',
        password: 'admin123',
        csrf_token: '<?php echo $csrf_token ?? ''; ?>'
    };
    
    console.log('Sending data:', formData);
    
    try {
        const response = await fetch('api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData),
            credentials: 'same-origin'
        });
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        try {
            const result = JSON.parse(text);
            console.log('API Response:', result);
            alert('API Response: ' + JSON.stringify(result, null, 2));
        } catch (e) {
            console.error('JSON parse error:', e);
            alert('JSON parse error: ' + e.message + '\n\nRaw response: ' + text.substring(0, 500));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    }
}
</script>

<button onclick="testLogin()" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
    🚀 Test API Call
</button>

<p><small>⚠️ Delete this file after debugging</small></p>