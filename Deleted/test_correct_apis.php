<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='universal_login.php'>Login</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Correct APIs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .api-test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .api-test h3 { margin: 0 0 10px 0; color: #333; }
        .test-btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px; }
        .result { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>🧪 Test Correct APIs</h1>
    <p>User: <?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)</p>
    
    <div class="api-test">
        <h3>📋 Daily Tasks API</h3>
        <a href="api/tasks/daily_correct.php" target="_blank" class="test-btn">Test Daily Tasks</a>
        <button onclick="testAPI('api/tasks/daily_correct.php', 'daily-result')">Test with AJAX</button>
        <div id="daily-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>📋 All Tasks API</h3>
        <a href="api/tasks/list_correct.php" target="_blank" class="test-btn">Test All Tasks</a>
        <button onclick="testAPI('api/tasks/list_correct.php', 'tasks-result')">Test with AJAX</button>
        <div id="tasks-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>👥 Customers API</h3>
        <a href="api/customers/list_correct.php" target="_blank" class="test-btn">All Customers</a>
        <a href="api/customers/list_correct.php?customer_status=ลูกค้าใหม่" target="_blank" class="test-btn">New Customers</a>
        <button onclick="testAPI('api/customers/list_correct.php', 'customers-result')">Test with AJAX</button>
        <div id="customers-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>📊 Dashboard Summary API</h3>
        <a href="api/dashboard/summary_correct.php" target="_blank" class="test-btn">Test Summary</a>
        <button onclick="testAPI('api/dashboard/summary_correct.php', 'summary-result')">Test with AJAX</button>
        <div id="summary-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>🔧 Next Step</h3>
        <p>If all APIs work correctly, we can update the dashboard.js to use the correct endpoints.</p>
        <a href="pages/dashboard.php" class="test-btn">Back to Dashboard</a>
    </div>

    <script>
        async function testAPI(url, resultId) {
            const resultDiv = document.getElementById(resultId);
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ Success!</strong><br>
                        Count: ${data.count || 'N/A'}<br>
                        Message: ${data.message}<br>
                        <details>
                            <summary>Raw Data</summary>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </details>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <strong>❌ Error!</strong><br>
                        Message: ${data.message}<br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <strong>❌ Network Error!</strong><br>
                    ${error.message}
                `;
            }
        }
    </script>
</body>
</html>