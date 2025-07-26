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
    <title>🐛 Debug Customer Detail Direct Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test-section h3 { margin: 0 0 10px 0; color: #333; }
        .test-btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px; }
        .result { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .console-log { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🐛 Debug Customer Detail - Direct Tests</h1>
    <p>User: <?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)</p>
    <p>Testing Customer: <?= htmlspecialchars($customerCode) ?></p>
    
    <div class="test-section">
        <h3>1. Test Customer API Directly</h3>
        <button onclick="testCustomerAPI()">Test Customer API</button>
        <div id="api-test-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h3>2. Test Customer Detail JavaScript (Same Origin)</h3>
        <button onclick="testCustomerDetailManual()">Test CustomerDetail Class</button>
        <div id="js-test-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Simulate Customer Detail Page Elements</h3>
        <button onclick="createTestElements()">Create Test DOM</button>
        <button onclick="testWithTestDOM()" style="margin-left: 10px;">Test with DOM</button>
        <div id="dom-test-result" class="result" style="display:none;"></div>
        
        <!-- Test DOM elements -->
        <div id="test-customer-info-content" style="border: 1px solid #ccc; padding: 10px; margin-top: 10px; display: none;">
            <p>Customer info will appear here...</p>
        </div>
    </div>
    
    <div class="test-section">
        <h3>4. Console Log Capture</h3>
        <button onclick="startConsoleCapture()">Start Console Capture</button>
        <button onclick="stopConsoleCapture()" style="margin-left: 10px;">Stop & Show Logs</button>
        <div id="console-logs" class="console-log" style="display:none;">
            Console logs will appear here...
        </div>
    </div>
    
    <div class="test-section">
        <h3>5. Direct Links for Manual Testing</h3>
        <a href="pages/customer_detail.php?code=<?= $customerCode ?>" target="_blank" class="test-btn">🔗 Open Customer Detail (New Tab)</a>
        <a href="api/customers/detail.php?code=<?= $customerCode ?>" target="_blank" class="test-btn">🔗 View API Response</a>
        <a href="assets/js/customer-detail.js" target="_blank" class="test-btn">🔗 View JS Source</a>
    </div>

    <script>
        let consoleCapture = [];
        let originalConsole = {};
        
        // Console capture functions
        function startConsoleCapture() {
            consoleCapture = [];
            
            ['log', 'error', 'warn', 'info'].forEach(method => {
                originalConsole[method] = console[method];
                console[method] = function(...args) {
                    consoleCapture.push({
                        type: method,
                        message: args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : arg).join(' '),
                        timestamp: new Date().toISOString()
                    });
                    originalConsole[method].apply(console, args);
                };
            });
            
            document.getElementById('console-logs').innerHTML = 'Console capture started...';
            document.getElementById('console-logs').style.display = 'block';
        }
        
        function stopConsoleCapture() {
            // Restore original console
            Object.keys(originalConsole).forEach(method => {
                console[method] = originalConsole[method];
            });
            
            const logsDiv = document.getElementById('console-logs');
            if (consoleCapture.length === 0) {
                logsDiv.innerHTML = 'No console messages captured.';
            } else {
                let html = '<h4>Captured Console Messages:</h4>';
                consoleCapture.forEach(log => {
                    const color = {
                        'error': 'red',
                        'warn': 'orange', 
                        'info': 'blue',
                        'log': 'black'
                    }[log.type] || 'black';
                    
                    html += `<div style="color: ${color}; margin: 5px 0;">`;
                    html += `<strong>[${log.type.toUpperCase()}]</strong> ${log.message}`;
                    html += `</div>`;
                });
                logsDiv.innerHTML = html;
            }
        }
        
        async function testCustomerAPI() {
            const resultDiv = document.getElementById('api-test-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing Customer API...';
            resultDiv.className = 'result';
            
            try {
                const response = await fetch(`api/customers/detail.php?code=<?= $customerCode ?>`);
                const data = await response.json();
                
                if (data.status === 'success' && data.data && data.data.customer) {
                    const customer = data.data.customer;
                    resultDiv.innerHTML = `
                        <h4>✅ API Response Success</h4>
                        <p><strong>Customer:</strong> ${customer.CustomerName} (${customer.CustomerCode})</p>
                        <p><strong>Phone:</strong> ${customer.CustomerTel}</p>
                        <p><strong>Status:</strong> ${customer.CustomerStatus}</p>
                        <p><strong>Response Time:</strong> ${response.headers.get('X-Response-Time') || 'N/A'}</p>
                    `;
                    resultDiv.className = 'result success';
                } else {
                    resultDiv.innerHTML = `<h4>❌ API returned unexpected format</h4><pre>${JSON.stringify(data, null, 2)}</pre>`;
                    resultDiv.className = 'result error';
                }
            } catch (error) {
                resultDiv.innerHTML = `<h4>❌ API Test Failed</h4><p>${error.message}</p>`;
                resultDiv.className = 'result error';
            }
        }
        
        function testCustomerDetailManual() {
            const resultDiv = document.getElementById('js-test-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Loading and testing customer-detail.js...';
            resultDiv.className = 'result';
            
            // Load customer-detail.js if not already loaded
            if (typeof CustomerDetail === 'undefined') {
                const script = document.createElement('script');
                script.src = 'assets/js/customer-detail.js';
                script.onload = function() {
                    continueJSTest(resultDiv);
                };
                script.onerror = function() {
                    resultDiv.innerHTML = '❌ Failed to load customer-detail.js';
                    resultDiv.className = 'result error';
                };
                document.head.appendChild(script);
            } else {
                continueJSTest(resultDiv);
            }
        }
        
        function continueJSTest(resultDiv) {
            try {
                if (typeof CustomerDetail !== 'undefined') {
                    resultDiv.innerHTML = `
                        <h4>✅ CustomerDetail class loaded successfully!</h4>
                        <p>Class type: ${typeof CustomerDetail}</p>
                        <p>Constructor: ${CustomerDetail.toString().substring(0, 100)}...</p>
                    `;
                    resultDiv.className = 'result success';
                } else {
                    resultDiv.innerHTML = '❌ CustomerDetail class not found after loading script';
                    resultDiv.className = 'result error';
                }
            } catch (error) {
                resultDiv.innerHTML = `❌ Error testing JavaScript: ${error.message}`;
                resultDiv.className = 'result error';
            }
        }
        
        function createTestElements() {
            // Create minimal DOM elements that CustomerDetail expects
            const testContainer = document.getElementById('test-customer-info-content');
            testContainer.style.display = 'block';
            testContainer.id = 'customer-info-content'; // Use actual ID that JS expects
            
            // Add other elements that might be needed
            if (!document.getElementById('loading-overlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'loading-overlay';
                overlay.style.display = 'none';
                document.body.appendChild(overlay);
            }
            
            const domResult = document.getElementById('dom-test-result');
            domResult.style.display = 'block';
            domResult.innerHTML = '✅ Test DOM elements created';
            domResult.className = 'result success';
        }
        
        async function testWithTestDOM() {
            const resultDiv = document.getElementById('dom-test-result');
            resultDiv.innerHTML = 'Testing CustomerDetail with DOM elements...';
            
            try {
                // Start console capture for this test
                startConsoleCapture();
                
                // Wait for JS to load if needed
                if (typeof CustomerDetail === 'undefined') {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'assets/js/customer-detail.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }
                
                // Create CustomerDetail instance
                const customerDetail = new CustomerDetail('<?= $customerCode ?>', '<?= $_SESSION['username'] ?>');
                
                // Wait a moment for any async operations
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                stopConsoleCapture();
                
                const customerInfoDiv = document.getElementById('customer-info-content');
                if (customerInfoDiv && customerInfoDiv.innerHTML.trim() !== '') {
                    resultDiv.innerHTML = `
                        <h4>✅ CustomerDetail initialized and ran successfully!</h4>
                        <p>Customer info populated: ${customerInfoDiv.innerHTML.length} characters</p>
                        <p>Check console logs above for any errors.</p>
                    `;
                    resultDiv.className = 'result success';
                } else {
                    resultDiv.innerHTML = `
                        <h4>⚠️ CustomerDetail ran but no customer info appeared</h4>
                        <p>This might indicate an API issue or timing problem.</p>
                        <p>Check console logs above for errors.</p>
                    `;
                    resultDiv.className = 'result warning';
                }
                
            } catch (error) {
                stopConsoleCapture();
                resultDiv.innerHTML = `
                    <h4>❌ Error creating CustomerDetail instance</h4>
                    <p>${error.message}</p>
                    <p>Check console logs above for details.</p>
                `;
                resultDiv.className = 'result error';
            }
        }
        
        // Auto-run API test on load
        window.onload = function() {
            testCustomerAPI();
        };
    </script>
</body>
</html>