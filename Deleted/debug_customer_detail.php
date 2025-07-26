<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    echo "Please login first: <a href='universal_login.php'>Login</a>";
    exit;
}

$customerCode = $_GET['code'] ?? 'CUST001';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug Customer Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .debug-section h3 { margin: 0 0 10px 0; color: #333; }
        .test-btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px; }
        .result { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Debug Customer Detail for <?= htmlspecialchars($customerCode) ?></h1>
    <p>User: <?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)</p>
    
    <div class="debug-section">
        <h3>1. Test Customer Detail API Directly</h3>
        <button onclick="testCustomerAPI()">Test API</button>
        <div id="api-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h3>2. Check Database Customer Records</h3>
        <button onclick="checkDatabase()">Check Database</button>
        <div id="db-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h3>3. Test Different Customer Codes</h3>
        <button onclick="testCustomer('CUST001')">Test CUST001</button>
        <button onclick="testCustomer('CUST002')">Test CUST002</button>
        <button onclick="testCustomer('CUST003')">Test CUST003</button>
        <div id="test-results" class="result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h3>4. Check Customer Detail Page JavaScript</h3>
        <button onclick="checkJavaScript()">Check JS Loading</button>
        <div id="js-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="debug-section">
        <h3>5. Direct Links</h3>
        <a href="api/customers/detail.php?code=<?= $customerCode ?>" target="_blank" class="test-btn">View API Response</a>
        <a href="pages/customer_detail.php?code=<?= $customerCode ?>" target="_blank" class="test-btn">View Customer Page</a>
        <a href="check_tables_structure.php" target="_blank" class="test-btn">Check DB Structure</a>
    </div>

    <script>
        const customerCode = '<?= $customerCode ?>';
        
        async function testCustomerAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing API...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(`api/customers/detail.php?code=${customerCode}`);
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const text = await response.text();
                console.log('Raw response:', text);
                
                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    
                    if (data.status === 'success') {
                        resultDiv.className = 'result success';
                        resultDiv.innerHTML = `
                            <strong>✅ API Success!</strong><br>
                            Customer: ${data.data.customer?.CustomerName || 'No name'}<br>
                            Phone: ${data.data.customer?.CustomerTel || 'No phone'}<br>
                            Status: ${data.data.customer?.CustomerStatus || 'No status'}<br>
                            <details>
                                <summary>Full Response</summary>
                                <pre>${JSON.stringify(data, null, 2)}</pre>
                            </details>
                        `;
                    } else {
                        resultDiv.className = 'result error';
                        resultDiv.innerHTML = `
                            <strong>❌ API Error!</strong><br>
                            Message: ${data.message}<br>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        `;
                    }
                } catch (jsonError) {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `
                        <strong>❌ JSON Parse Error!</strong><br>
                        Raw response: <pre>${text}</pre>
                        Error: ${jsonError.message}
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
        
        async function checkDatabase() {
            const resultDiv = document.getElementById('db-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Checking database...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch('api/customers/list.php');
                const data = await response.json();
                
                if (data.status === 'success') {
                    const customers = data.data;
                    const customerCodes = customers.map(c => c.CustomerCode).join(', ');
                    
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ Database Check Success!</strong><br>
                        Total customers: ${customers.length}<br>
                        Customer codes: ${customerCodes}<br>
                        Looking for: ${customerCode}<br>
                        Found: ${customers.find(c => c.CustomerCode === customerCode) ? 'YES' : 'NO'}<br>
                        <details>
                            <summary>All Customers</summary>
                            <pre>${JSON.stringify(customers, null, 2)}</pre>
                        </details>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `<strong>❌ Database Error!</strong><br>${data.message}`;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `<strong>❌ Database Error!</strong><br>${error.message}`;
            }
        }
        
        async function testCustomer(code) {
            const resultDiv = document.getElementById('test-results');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = `Testing ${code}...`;
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(`api/customers/detail.php?code=${code}`);
                const data = await response.json();
                
                const status = data.status === 'success' ? '✅' : '❌';
                const customerName = data.data?.customer?.CustomerName || 'Not found';
                
                resultDiv.innerHTML += `<br>${status} ${code}: ${customerName}`;
                
                if (data.status === 'success') {
                    resultDiv.className = 'result success';
                } else {
                    resultDiv.className = 'result error';
                }
            } catch (error) {
                resultDiv.innerHTML += `<br>❌ ${code}: Error - ${error.message}`;
                resultDiv.className = 'result error';
            }
        }
        
        function checkJavaScript() {
            const resultDiv = document.getElementById('js-result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            
            // Check if customer-detail.js is loaded
            const scripts = Array.from(document.scripts).map(s => s.src);
            const hasCustomerDetailJS = scripts.some(src => src.includes('customer-detail.js'));
            
            resultDiv.innerHTML = `
                <strong>JavaScript Check:</strong><br>
                Customer Detail JS loaded: ${hasCustomerDetailJS ? '✅ YES' : '❌ NO'}<br>
                Current page: ${window.location.pathname}<br>
                All scripts: <br>${scripts.map(s => '- ' + s).join('<br>')}<br>
                <br>
                <strong>Note:</strong> This debug page doesn't load customer-detail.js.
                Check the actual customer detail page.
            `;
        }
        
        // Auto-run tests on page load
        window.onload = function() {
            testCustomerAPI();
            setTimeout(checkDatabase, 1000);
        };
    </script>
</body>
</html>