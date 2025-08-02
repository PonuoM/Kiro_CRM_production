<?php
/**
 * Test Fixed Sales API
 * Test the corrected getSalesUsers API endpoint
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Fixed Sales API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🧪 Test Fixed Sales API</h2>
        <p class="text-muted">Testing the corrected getSalesUsers API endpoint</p>
        
        <div class="row">
            <div class="col-md-6">
                <h5>Test Controls</h5>
                <button class="btn btn-primary" onclick="testAPI()">Test Sales Users API</button>
                <button class="btn btn-info" onclick="testDirect()">Test Direct Connection</button>
                <button class="btn btn-secondary" onclick="clearLog()">Clear Log</button>
            </div>
            <div class="col-md-6">
                <h5>API Status</h5>
                <div id="apiStatus" class="alert alert-secondary">
                    Ready to test
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <h5>API Response</h5>
                <div id="apiResponse" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; min-height: 200px; font-family: monospace; white-space: pre-wrap;">
                    Click "Test Sales Users API" to see response...
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h5>Debug Information</h5>
                <div id="debugInfo" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; min-height: 100px; font-family: monospace; font-size: 0.9rem;">
                    Debug information will appear here...
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateStatus(message, type = 'info') {
            const statusElement = document.getElementById('apiStatus');
            statusElement.className = `alert alert-${type}`;
            statusElement.textContent = message;
        }

        function log(message) {
            const debugElement = document.getElementById('debugInfo');
            const timestamp = new Date().toLocaleTimeString();
            debugElement.textContent += `[${timestamp}] ${message}\n`;
        }

        function clearLog() {
            document.getElementById('debugInfo').textContent = 'Debug log cleared...\n';
            document.getElementById('apiResponse').textContent = 'Response cleared...';
            updateStatus('Ready to test', 'secondary');
        }

        function testAPI() {
            updateStatus('Testing API...', 'warning');
            log('Starting API test...');
            
            fetch('./api/distribution/basket.php?action=sales_users')
                .then(response => {
                    log(`Response status: ${response.status} ${response.statusText}`);
                    log(`Response headers: ${JSON.stringify([...response.headers])}`);
                    return response.text();
                })
                .then(text => {
                    log(`Raw response length: ${text.length} characters`);
                    document.getElementById('apiResponse').textContent = text;
                    
                    try {
                        const data = JSON.parse(text);
                        if (data.status === 'success') {
                            updateStatus(`✅ Success! Found ${data.count} sales users`, 'success');
                            log(`Parsed JSON successfully. Found ${data.count} users.`);
                            
                            // Log first user details if available
                            if (data.data && data.data.length > 0) {
                                const firstUser = data.data[0];
                                log(`First user: ${firstUser.first_name} ${firstUser.last_name} (@${firstUser.username})`);
                            }
                        } else {
                            updateStatus(`❌ API Error: ${data.error || 'Unknown error'}`, 'danger');
                            log(`API returned error: ${data.error || 'Unknown error'}`);
                        }
                    } catch (e) {
                        updateStatus('❌ JSON Parse Error', 'danger');
                        log(`JSON parse error: ${e.message}`);
                    }
                })
                .catch(error => {
                    updateStatus(`❌ Network Error: ${error.message}`, 'danger');
                    log(`Network error: ${error.message}`);
                    document.getElementById('apiResponse').textContent = `Network Error: ${error.message}`;
                });
        }

        function testDirect() {
            updateStatus('Testing direct database connection...', 'warning');
            log('Testing direct database connection...');
            
            fetch('./check_users_table_structure.php')
                .then(response => response.text())
                .then(html => {
                    log('Direct database test completed');
                    
                    // Open in new window for full view
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(html);
                    newWindow.document.close();
                    
                    updateStatus('✅ Database structure opened in new window', 'info');
                })
                .catch(error => {
                    updateStatus(`❌ Database test error: ${error.message}`, 'danger');
                    log(`Database test error: ${error.message}`);
                });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('Test page loaded and ready');
        });
    </script>
</body>
</html>