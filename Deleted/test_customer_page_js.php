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
    <title>🔍 Test Customer Detail JS Loading</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .test-section h3 { margin: 0 0 10px 0; color: #333; }
        .test-btn { background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 3px; margin-right: 10px; }
        .result { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 3px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        iframe { width: 100%; height: 500px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔍 Test Customer Detail JavaScript Loading</h1>
    <p>User: <?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)</p>
    <p>Testing Customer: <?= htmlspecialchars($customerCode) ?></p>
    
    <div class="test-section">
        <h3>1. Test Customer Detail Page in Frame</h3>
        <p>This loads the actual customer detail page to see if JavaScript runs:</p>
        <iframe src="pages/customer_detail.php?code=<?= $customerCode ?>" id="customer-frame"></iframe>
    </div>
    
    <div class="test-section">
        <h3>2. Check JavaScript Files</h3>
        <button onclick="checkJSFiles()">Check JS Files</button>
        <div id="js-check-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h3>3. Test JavaScript Manually</h3>
        <button onclick="testCustomerDetailJS()">Test customer-detail.js</button>
        <div id="manual-test-result" class="result" style="display:none;"></div>
    </div>
    
    <div class="test-section">
        <h3>4. Direct Links</h3>
        <a href="pages/customer_detail.php?code=<?= $customerCode ?>" target="_blank" class="test-btn">Open Customer Detail</a>
        <a href="assets/js/customer-detail.js" target="_blank" class="test-btn">View JS File</a>
        <a href="api/customers/detail.php?code=<?= $customerCode ?>" target="_blank" class="test-btn">Test API</a>
    </div>
    
    <div class="test-section">
        <h3>5. Frame Communication Test</h3>
        <button onclick="testFrameJS()">Test Frame JavaScript</button>
        <div id="frame-test-result" class="result" style="display:none;"></div>
    </div>

    <script>
        async function checkJSFiles() {
            const resultDiv = document.getElementById('js-check-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Checking JavaScript files...';
            resultDiv.className = 'result';
            
            const jsFiles = [
                'assets/js/main.js',
                'assets/js/customer-detail.js'
            ];
            
            let results = '<h4>JavaScript Files Check:</h4>';
            
            for (const file of jsFiles) {
                try {
                    const response = await fetch(file);
                    if (response.ok) {
                        const content = await response.text();
                        const size = (content.length / 1024).toFixed(2);
                        results += `<p style="color: green;">✅ ${file}: Available (${size} KB)</p>`;
                    } else {
                        results += `<p style="color: red;">❌ ${file}: Not found (${response.status})</p>`;
                    }
                } catch (error) {
                    results += `<p style="color: red;">❌ ${file}: Error - ${error.message}</p>`;
                }
            }
            
            resultDiv.innerHTML = results;
            resultDiv.className = 'result success';
        }
        
        function testCustomerDetailJS() {
            const resultDiv = document.getElementById('manual-test-result');
            resultDiv.style.display = 'block';
            resultDiv.className = 'result';
            
            // Try to create CustomerDetail class manually
            try {
                // Load the script dynamically
                const script = document.createElement('script');
                script.src = 'assets/js/customer-detail.js';
                script.onload = function() {
                    if (typeof CustomerDetail !== 'undefined') {
                        resultDiv.innerHTML = `
                            <strong>✅ CustomerDetail class loaded successfully!</strong><br>
                            Class found: ${typeof CustomerDetail}<br>
                            Ready to initialize with customer code: <?= $customerCode ?>
                        `;
                        resultDiv.className = 'result success';
                        
                        // Try to initialize
                        try {
                            const customerDetail = new CustomerDetail('<?= $customerCode ?>', '<?= $_SESSION['username'] ?>');
                            resultDiv.innerHTML += '<br>✅ CustomerDetail initialized successfully!';
                        } catch (initError) {
                            resultDiv.innerHTML += `<br>❌ Initialization error: ${initError.message}`;
                        }
                    } else {
                        resultDiv.innerHTML = '❌ CustomerDetail class not found after loading script';
                        resultDiv.className = 'result error';
                    }
                };
                script.onerror = function() {
                    resultDiv.innerHTML = '❌ Failed to load customer-detail.js';
                    resultDiv.className = 'result error';
                };
                document.head.appendChild(script);
            } catch (error) {
                resultDiv.innerHTML = `❌ Error testing JavaScript: ${error.message}`;
                resultDiv.className = 'result error';
            }
        }
        
        function testFrameJS() {
            const resultDiv = document.getElementById('frame-test-result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = 'Testing frame JavaScript...';
            resultDiv.className = 'result';
            
            try {
                const frame = document.getElementById('customer-frame');
                const frameWindow = frame.contentWindow;
                const frameDocument = frame.contentDocument || frameWindow.document;
                
                // Check if frame loaded
                if (frameDocument.readyState === 'complete') {
                    // Check for scripts in frame
                    const scripts = frameDocument.getElementsByTagName('script');
                    const scriptSources = Array.from(scripts).map(s => s.src || 'inline').join(', ');
                    
                    // Check for CustomerDetail in frame
                    const hasCustomerDetail = frameWindow.CustomerDetail !== undefined;
                    
                    resultDiv.innerHTML = `
                        <strong>Frame JavaScript Test:</strong><br>
                        Frame loaded: ✅ YES<br>
                        Scripts found: ${scripts.length}<br>
                        Script sources: ${scriptSources}<br>
                        CustomerDetail class: ${hasCustomerDetail ? '✅ Found' : '❌ Not found'}<br>
                    `;
                    
                    if (hasCustomerDetail) {
                        resultDiv.className = 'result success';
                    } else {
                        resultDiv.className = 'result error';
                    }
                } else {
                    resultDiv.innerHTML = 'Frame still loading, try again in a moment...';
                    setTimeout(testFrameJS, 2000);
                }
            } catch (error) {
                resultDiv.innerHTML = `❌ Frame test error: ${error.message}`;
                resultDiv.className = 'result error';
            }
        }
        
        // Auto-check JS files on load
        window.onload = function() {
            checkJSFiles();
            setTimeout(testFrameJS, 3000); // Wait for frame to load
        };
    </script>
</body>
</html>