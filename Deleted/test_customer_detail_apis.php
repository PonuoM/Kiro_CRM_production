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
    <title>🧪 Test Customer Detail APIs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .api-test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .api-test h3 { margin: 0 0 10px 0; color: #333; }
        .test-btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px; }
        .result { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .form-group { margin: 10px 0; }
        input[type="text"], input[type="datetime-local"], textarea { 
            width: 300px; padding: 5px; border: 1px solid #ccc; border-radius: 3px; 
        }
        textarea { height: 80px; width: 400px; }
    </style>
</head>
<body>
    <h1>🧪 Test Customer Detail APIs</h1>
    <p>User: <?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)</p>
    
    <div class="api-test">
        <h3>👤 Customer Detail API</h3>
        <div class="form-group">
            <label>Customer Code:</label>
            <input type="text" id="customer-code" value="CUST003" placeholder="e.g., CUST001">
        </div>
        <button onclick="testCustomerDetail()">Test Customer Detail</button>
        <div id="customer-detail-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>📞 Call History API</h3>
        <button onclick="testCallHistory()">Test Call History</button>
        <div id="call-history-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>📦 Order History API</h3>
        <button onclick="testOrderHistory()">Test Order History</button>
        <div id="order-history-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>💰 Sales History API</h3>
        <button onclick="testSalesHistory()">Test Sales History</button>
        <div id="sales-history-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>➕ Create Task API</h3>
        <div class="form-group">
            <label>Follow-up Date:</label>
            <input type="datetime-local" id="followup-date" value="<?= date('Y-m-d\TH:i') ?>">
        </div>
        <div class="form-group">
            <label>Remarks:</label>
            <textarea id="task-remarks" placeholder="Task remarks...">ทดสอบสร้าง task จากระบบ</textarea>
        </div>
        <button onclick="testCreateTask()">Create Task</button>
        <div id="create-task-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="api-test">
        <h3>🎯 Ready to use</h3>
        <p>✅ All Customer Detail APIs have been fixed</p>
        <a href="pages/customer_detail.php?code=CUST003" class="test-btn">Go to Customer Detail Page</a>
        <a href="pages/dashboard.php" class="test-btn">Back to Dashboard</a>
    </div>

    <script>
        function getCustomerCode() {
            return document.getElementById('customer-code').value || 'CUST003';
        }
        
        async function testCustomerDetail() {
            const customerCode = getCustomerCode();
            const resultDiv = document.getElementById('customer-detail-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(`api/customers/detail.php?code=${customerCode}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ Success!</strong><br>
                        Customer: ${data.data.customer.CustomerName}<br>
                        Phone: ${data.data.customer.CustomerTel}<br>
                        Status: ${data.data.customer.CustomerStatus}<br>
                        Tasks: ${data.data.tasks.length}<br>
                        Orders: ${data.data.orders.length}<br>
                        Call Logs: ${data.data.call_logs.length}<br>
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
        
        async function testCallHistory() {
            await testGenericAPI('api/calls/history.php', 'call-history-result', 'Call History');
        }
        
        async function testOrderHistory() {
            await testGenericAPI('api/orders/history.php', 'order-history-result', 'Order History');
        }
        
        async function testSalesHistory() {
            await testGenericAPI('api/sales/history.php?action=customer', 'sales-history-result', 'Sales History');
        }
        
        async function testGenericAPI(url, resultId, apiName) {
            const customerCode = getCustomerCode();
            const resultDiv = document.getElementById(resultId);
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(`${url}${url.includes('?') ? '&' : '?'}customer=${customerCode}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ ${apiName} Success!</strong><br>
                        Count: ${data.count || data.data.length || 'N/A'}<br>
                        Message: ${data.message}<br>
                        <details>
                            <summary>Raw Data</summary>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </details>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <strong>❌ ${apiName} Error!</strong><br>
                        Message: ${data.message}<br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <strong>❌ ${apiName} Network Error!</strong><br>
                    ${error.message}
                `;
            }
        }
        
        async function testCreateTask() {
            const customerCode = getCustomerCode();
            const followupDate = document.getElementById('followup-date').value;
            const remarks = document.getElementById('task-remarks').value;
            const resultDiv = document.getElementById('create-task-result');
            
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Creating task...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch('api/tasks/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        CustomerCode: customerCode,
                        FollowupDate: followupDate,
                        Remarks: remarks,
                        Status: 'รอดำเนินการ'
                    })
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ Task Created!</strong><br>
                        Task ID: ${data.data.task_id}<br>
                        Customer: ${data.data.CustomerCode}<br>
                        Follow-up: ${data.data.FollowupDate}<br>
                        <details>
                            <summary>Raw Data</summary>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </details>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <strong>❌ Create Task Error!</strong><br>
                        Message: ${data.message}<br>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <strong>❌ Create Task Network Error!</strong><br>
                    ${error.message}
                `;
            }
        }
        
        // Auto-test customer detail on page load
        window.onload = function() {
            testCustomerDetail();
        };
    </script>
</body>
</html>