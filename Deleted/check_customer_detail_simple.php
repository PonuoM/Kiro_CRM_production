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
    <title>🔍 Simple Customer Detail Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .success { background: #d4edda; }
        .error { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>🔍 Simple Customer Detail Check</h1>
    <p>User: <?= $_SESSION['username'] ?> | Customer: <?= $customerCode ?></p>
    
    <div class="test">
        <h3>1. Direct API Test</h3>
        <div id="api-result">Loading...</div>
    </div>
    
    <div class="test">
        <h3>2. Minimal Customer Info HTML</h3>
        <div id="customer-info-content">
            <p>Customer info will load here...</p>
        </div>
    </div>
    
    <div class="test">
        <h3>3. Console Messages</h3>
        <div id="console-messages" style="background: #f8f9fa; padding: 10px; font-family: monospace;"></div>
    </div>
    
    <div class="test">
        <h3>4. Manual Tests</h3>
        <p><a href="pages/customer_detail.php?code=<?= $customerCode ?>" target="_blank">🔗 Open Customer Detail (New Tab)</a></p>
        <p><strong>Instructions:</strong> Open the link above, then press F12 → Console tab and tell me what errors you see.</p>
    </div>

    <script>
        // Capture console messages
        const originalLog = console.log;
        const originalError = console.error;
        const messages = [];
        
        function addMessage(type, args) {
            const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : arg).join(' ');
            messages.push(`[${type}] ${message}`);
            updateConsoleDisplay();
        }
        
        console.log = function(...args) {
            addMessage('LOG', args);
            originalLog.apply(console, args);
        };
        
        console.error = function(...args) {
            addMessage('ERROR', args);
            originalError.apply(console, args);
        };
        
        function updateConsoleDisplay() {
            document.getElementById('console-messages').innerHTML = messages.join('<br>') || 'No console messages yet.';
        }
        
        // Test API directly
        async function testAPI() {
            try {
                console.log('Testing API...');
                const response = await fetch(`api/customers/detail.php?code=<?= $customerCode ?>`);
                const data = await response.json();
                
                console.log('API Response:', data);
                
                if (data.status === 'success' && data.data && data.data.customer) {
                    const customer = data.data.customer;
                    document.getElementById('api-result').innerHTML = `
                        <div class="success">
                            <strong>✅ API Success!</strong><br>
                            Customer: ${customer.CustomerName}<br>
                            Code: ${customer.CustomerCode}<br>
                            Phone: ${customer.CustomerTel}
                        </div>
                    `;
                    
                    // Now test the customer detail rendering
                    testCustomerDetail(customer);
                } else {
                    document.getElementById('api-result').innerHTML = `
                        <div class="error">❌ API Error: ${JSON.stringify(data)}</div>
                    `;
                }
            } catch (error) {
                console.error('API Test Error:', error);
                document.getElementById('api-result').innerHTML = `
                    <div class="error">❌ Fetch Error: ${error.message}</div>
                `;
            }
        }
        
        function testCustomerDetail(customer) {
            console.log('Testing customer detail rendering...');
            
            // Test if we can render customer info manually
            const content = document.getElementById('customer-info-content');
            
            try {
                content.innerHTML = `
                    <div style="border: 1px solid #ccc; padding: 10px;">
                        <h4>ข้อมูลลูกค้า</h4>
                        <p><strong>รหัส:</strong> ${customer.CustomerCode}</p>
                        <p><strong>ชื่อ:</strong> ${customer.CustomerName}</p>
                        <p><strong>เบอร์:</strong> ${customer.CustomerTel}</p>
                        <p><strong>สถานะ:</strong> ${customer.CustomerStatus}</p>
                        <p><strong>Sales:</strong> ${customer.Sales || 'ไม่ได้กำหนด'}</p>
                    </div>
                `;
                console.log('✅ Manual rendering successful');
            } catch (error) {
                console.error('Manual rendering error:', error);
            }
        }
        
        // Load customer-detail.js and test it
        function loadAndTestJS() {
            console.log('Loading customer-detail.js...');
            
            const script = document.createElement('script');
            script.src = 'assets/js/customer-detail.js';
            script.onload = function() {
                console.log('customer-detail.js loaded');
                
                if (typeof CustomerDetail !== 'undefined') {
                    console.log('CustomerDetail class found');
                    
                    try {
                        const customerDetail = new CustomerDetail('<?= $customerCode ?>', '<?= $_SESSION['username'] ?>');
                        console.log('CustomerDetail instance created successfully');
                    } catch (error) {
                        console.error('Error creating CustomerDetail:', error);
                    }
                } else {
                    console.error('CustomerDetail class not found');
                }
            };
            script.onerror = function() {
                console.error('Failed to load customer-detail.js');
            };
            document.head.appendChild(script);
        }
        
        // Run tests
        window.onload = function() {
            testAPI();
            setTimeout(loadAndTestJS, 1000);
        };
    </script>
</body>
</html>