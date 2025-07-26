<?php
session_start();

// Mock session for testing (remove in production)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'test_user';
    $_SESSION['role'] = 'Admin';
    $_SESSION['username'] = 'admin';
}

echo "<h3>Testing Customer API</h3>";

$baseUrl = 'https://www.prima49.com/crm_system/Kiro_CRM_production/api/customers/list-simple.php';

$statuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];

foreach($statuses as $status) {
    $url = $baseUrl . '?customer_status=' . urlencode($status);
    echo "<h4>Testing: $status</h4>";
    echo "URL: $url<br>";
    
    // Use cURL instead of file_get_contents for HTTPS
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($response === false || !empty($error)) {
        echo "❌ Failed to connect to API<br>";
        echo "Error: $error<br>";
        echo "HTTP Code: $httpCode<br>";
    } else {
        echo "Raw response: " . substr($response, 0, 200) . "...<br>";
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "❌ JSON decode error: " . json_last_error_msg() . "<br>";
        } elseif ($data && $data['status'] === 'success') {
            echo "✅ Success - Found " . count($data['data']) . " customers<br>";
            if (count($data['data']) > 0) {
                echo "Sample: " . $data['data'][0]['CustomerName'] . "<br>";
            }
        } else {
            echo "❌ Error: " . ($data['message'] ?? 'Unknown error') . "<br>";
            if (isset($data['error'])) {
                echo "Details: " . $data['error'] . "<br>";
            }
        }
    }
    echo "<hr>";
}
?>