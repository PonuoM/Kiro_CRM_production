<?php
session_start();
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: pages/login.php');
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Daily API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 400px; overflow-y: auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>Debug Daily Tasks API</h1>";
echo "<p class='info'>User: {$_SESSION['username']} ({$_SESSION['user_role']})</p>";

echo "<div class='section'>";
echo "<h3>1. Test daily_enhanced.php API</h3>";
echo "<button onclick='testEnhancedAPI()'>Test Enhanced API</button>";
echo "<div id='enhanced-result'></div>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>2. Test Original daily.php API</h3>";
echo "<button onclick='testOriginalAPI()'>Test Original API</button>";
echo "<div id='original-result'></div>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>3. Test CustomerStatusManager</h3>";
echo "<button onclick='testCustomerManager()'>Test Customer Manager</button>";
echo "<div id='customer-result'></div>";
echo "</div>";

echo "<script>
async function testEnhancedAPI() {
    document.getElementById('enhanced-result').innerHTML = '<p class=\"info\">Testing...</p>';
    
    try {
        const response = await fetch('api/tasks/daily_enhanced.php');
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const contentType = response.headers.get('content-type');
        console.log('Content type:', contentType);
        
        if (!contentType || !contentType.includes('application/json')) {
            const textResponse = await response.text();
            console.log('Text response:', textResponse);
            document.getElementById('enhanced-result').innerHTML = `
                <h4>Enhanced API - Not JSON Response:</h4>
                <p class=\"error\">Content-Type: ${contentType}</p>
                <pre>${textResponse}</pre>
            `;
            return;
        }
        
        const data = await response.json();
        console.log('JSON data:', data);
        
        document.getElementById('enhanced-result').innerHTML = `
            <h4>Enhanced API Result:</h4>
            <p><strong>HTTP Status:</strong> ${response.status}</p>
            <p><strong>Success:</strong> <span class=\"${data.success ? 'success' : 'error'}\">${data.success}</span></p>
            <p><strong>Message:</strong> ${data.message || 'No message'}</p>
            ${data.data ? `
                <h5>Data Summary:</h5>
                <ul>
                    <li>Today Tasks: ${data.data.today ? data.data.today.count : 'undefined'}</li>
                    <li>Overdue Tasks: ${data.data.overdue ? data.data.overdue.count : 'undefined'}</li>
                    <li>Summary: ${data.data.summary ? JSON.stringify(data.data.summary) : 'undefined'}</li>
                </ul>
            ` : '<p class=\"error\">No data property found</p>'}
            <h5>Full Response:</h5>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('enhanced-result').innerHTML = `
            <h4>Enhanced API Error:</h4>
            <p class=\"error\">${error.message}</p>
        `;
    }
}

async function testOriginalAPI() {
    document.getElementById('original-result').innerHTML = '<p class=\"info\">Testing...</p>';
    
    try {
        const response = await fetch('api/tasks/daily.php');
        const data = await response.json();
        
        document.getElementById('original-result').innerHTML = `
            <h4>Original API Result:</h4>
            <p><strong>Status:</strong> ${data.status}</p>
            <p><strong>Tasks Count:</strong> ${data.data ? data.data.length : 0}</p>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    } catch (error) {
        document.getElementById('original-result').innerHTML = `
            <h4>Original API Error:</h4>
            <p class=\"error\">${error.message}</p>
        `;
    }
}

async function testCustomerManager() {
    document.getElementById('customer-result').innerHTML = '<p class=\"info\">Testing Customer Status Manager...</p>';
    
    // Test ลูกค้าใหม่ query
    try {
        const response = await fetch('api/customers/list-simple.php?customer_status=ลูกค้าใหม่');
        const data = await response.json();
        
        document.getElementById('customer-result').innerHTML = `
            <h4>ลูกค้าใหม่ Count:</h4>
            <p><strong>Status:</strong> ${data.status}</p>
            <p><strong>Count:</strong> ${data.data ? data.data.length : 0}</p>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    } catch (error) {
        document.getElementById('customer-result').innerHTML = `
            <h4>Customer Manager Error:</h4>
            <p class=\"error\">${error.message}</p>
        `;
    }
}
</script>";

echo "</body></html>";
?>