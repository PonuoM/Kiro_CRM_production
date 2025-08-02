<?php
/**
 * Test Assignment After Fixes
 * Test the fixed assignment API with correct field names
 */

if (session_status() == PHP_SESSION_NONE) { session_start(); }

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Assignment Fixed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🧪 Test Assignment After Fixes</h2>
        <p class="text-muted">Test the corrected assignment API</p>
        
        <div class="row">
            <div class="col-md-6">
                <h5>Test Controls</h5>
                <button class="btn btn-primary" onclick="loadSalesUsers()">1. Load Sales Users</button>
                <button class="btn btn-info" onclick="loadUnassignedCustomers()">2. Load Customers</button>
                <button class="btn btn-success" onclick="testAssignment()">3. Test Assignment</button>
                <button class="btn btn-warning" onclick="checkCartStatus()">4. Check CartStatus</button>
                <button class="btn btn-secondary" onclick="clearLog()">Clear Log</button>
            </div>
            <div class="col-md-6">
                <h5>Test Status</h5>
                <div id="testStatus" class="alert alert-secondary">
                    Ready to test
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h5>Sales Users</h5>
                <div id="salesUsersList" style="max-height: 300px; overflow-y: auto;">
                    <p class="text-muted">Click "Load Sales Users" to test</p>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Unassigned Customers</h5>
                <div id="customersList" style="max-height: 300px; overflow-y: auto;">
                    <p class="text-muted">Click "Load Customers" to test</p>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <h5>Debug Log</h5>
                <div id="debugLog" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; min-height: 200px; font-family: monospace; font-size: 0.9rem; white-space: pre-wrap; overflow-y: auto; max-height: 400px;">
                    Click buttons above to start testing...
                </div>
            </div>
        </div>
    </div>

    <script>
        let salesUsers = [];
        let unassignedCustomers = [];

        function log(message) {
            const debugElement = document.getElementById('debugLog');
            const timestamp = new Date().toLocaleTimeString();
            debugElement.textContent += `[${timestamp}] ${message}\n`;
            debugElement.scrollTop = debugElement.scrollHeight;
        }

        function updateStatus(message, type = 'info') {
            const statusElement = document.getElementById('testStatus');
            statusElement.className = `alert alert-${type}`;
            statusElement.textContent = message;
        }

        function clearLog() {
            document.getElementById('debugLog').textContent = 'Debug log cleared...\n';
            updateStatus('Ready to test', 'secondary');
        }

        function loadSalesUsers() {
            log('🔄 Loading sales users...');
            updateStatus('Loading sales users...', 'warning');
            
            fetch('./api/distribution/basket.php?action=sales_users')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        salesUsers = data.data;
                        displaySalesUsers(data.data);
                        log(`✅ Loaded ${data.count} sales users`);
                        updateStatus(`✅ Found ${data.count} sales users`, 'success');
                    } else {
                        log(`❌ Sales users error: ${data.error}`);
                        updateStatus(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    log(`❌ Network error: ${error.message}`);
                    updateStatus(`❌ Network error`, 'danger');
                });
        }

        function displaySalesUsers(users) {
            const container = document.getElementById('salesUsersList');
            
            if (users.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">No active sales users found</div>';
                return;
            }

            let html = '<div class="list-group">';
            users.forEach(user => {
                html += `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong>${user.first_name} ${user.last_name}</strong><br>
                                <small>@${user.username}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary">${user.assigned_customers} ลูกค้า</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function loadUnassignedCustomers() {
            log('🔄 Loading unassigned customers...');
            updateStatus('Loading customers...', 'warning');
            
            fetch('./api/distribution/basket.php?action=unassigned&limit=5')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        unassignedCustomers = data.data;
                        displayCustomers(data.data);
                        log(`✅ Loaded ${data.data.length} unassigned customers`);
                        updateStatus(`✅ Found ${data.data.length} customers`, 'success');
                    } else {
                        log(`❌ Customers error: ${data.error}`);
                        updateStatus(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    log(`❌ Network error: ${error.message}`);
                    updateStatus(`❌ Network error`, 'danger');
                });
        }

        function displayCustomers(customers) {
            const container = document.getElementById('customersList');
            
            if (customers.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">No unassigned customers found</div>';
                return;
            }

            let html = '<div class="list-group">';
            customers.forEach(customer => {
                html += `
                    <div class="list-group-item">
                        <strong>${customer.CustomerCode}</strong><br>
                        <small>${customer.CustomerName}</small><br>
                        <span class="badge bg-info">${customer.CartStatus || 'No Status'}</span>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function testAssignment() {
            if (salesUsers.length === 0 || unassignedCustomers.length === 0) {
                log('❌ Please load sales users and customers first');
                updateStatus('❌ Load data first', 'danger');
                return;
            }

            const firstCustomer = unassignedCustomers[0];
            const firstSalesUser = salesUsers[0];

            log(`🔄 Testing assignment: ${firstCustomer.CustomerCode} → ${firstSalesUser.username}`);
            updateStatus('Testing assignment...', 'warning');

            const assignmentData = {
                customer_codes: [firstCustomer.CustomerCode],
                sales_username: firstSalesUser.username
            };

            log(`📤 Assignment request: ${JSON.stringify(assignmentData)}`);

            fetch('./api/distribution/basket.php?action=assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(assignmentData)
            })
            .then(response => response.json())
            .then(data => {
                log(`📥 Assignment response: ${JSON.stringify(data, null, 2)}`);
                
                if (data.status === 'success') {
                    log(`✅ Assignment successful! Assigned ${data.data.assigned_count} customers`);
                    updateStatus(`✅ Assignment successful!`, 'success');
                } else {
                    log(`❌ Assignment failed: ${data.error}`);
                    updateStatus(`❌ Assignment failed: ${data.error}`, 'danger');
                }
            })
            .catch(error => {
                log(`❌ Assignment error: ${error.message}`);
                updateStatus(`❌ Assignment error`, 'danger');
            });
        }

        function checkCartStatus() {
            log('🔄 Checking CartStatus consistency...');
            updateStatus('Checking CartStatus...', 'warning');
            
            fetch('./debug_cartstatus_logic.php')
                .then(response => response.text())
                .then(html => {
                    log('✅ CartStatus check completed - opening in new window');
                    updateStatus('✅ CartStatus check opened', 'info');
                    
                    // Open in new window
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(html);
                    newWindow.document.close();
                })
                .catch(error => {
                    log(`❌ CartStatus check error: ${error.message}`);
                    updateStatus(`❌ Check error`, 'danger');
                });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('🚀 Test page initialized');
            log('📋 Test sequence: 1. Load Sales Users → 2. Load Customers → 3. Test Assignment → 4. Check CartStatus');
        });
    </script>
</body>
</html>