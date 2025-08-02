<?php
/**
 * Debug Distribution Sales Selection Issue
 * Tool to diagnose problems with sales user selection in distribution basket
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Debug Distribution Sales Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .debug-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }
        
        .list-group-item.active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .test-button {
            margin: 5px;
        }
        
        .log-output {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2>🔧 Debug Distribution Sales Selection</h2>
                <p class="text-muted">เครื่องมือตรวจสอบปัญหาการเลือกพนักงานขายในหน้า Distribution Basket</p>
            </div>
        </div>

        <!-- Test Controls -->
        <div class="row">
            <div class="col-12">
                <div class="debug-section">
                    <h5><i class="fas fa-play"></i> Test Controls</h5>
                    <button class="btn btn-primary test-button" onclick="testAPIConnection()">
                        <i class="fas fa-link"></i> Test API Connection
                    </button>
                    <button class="btn btn-info test-button" onclick="loadSalesUsers()">
                        <i class="fas fa-users"></i> Load Sales Users
                    </button>
                    <button class="btn btn-success test-button" onclick="testSelection()">
                        <i class="fas fa-mouse-pointer"></i> Test Selection Function
                    </button>
                    <button class="btn btn-secondary test-button" onclick="clearLog()">
                        <i class="fas fa-eraser"></i> Clear Log
                    </button>
                </div>
            </div>
        </div>

        <!-- Sales Users Display -->
        <div class="row">
            <div class="col-md-6">
                <div class="debug-section">
                    <h5><i class="fas fa-users"></i> Sales Users List</h5>
                    <div id="salesUsersList">
                        <p class="text-muted">Click "Load Sales Users" to test</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="debug-section">
                    <h5><i class="fas fa-bug"></i> Debug Log</h5>
                    <div id="debugLog" class="log-output">
                        Debug log will appear here...
                    </div>
                </div>
            </div>
        </div>

        <!-- Selection Status -->
        <div class="row">
            <div class="col-12">
                <div class="debug-section">
                    <h5><i class="fas fa-info-circle"></i> Selection Status</h5>
                    <div class="alert alert-info">
                        <strong>Selected Sales User:</strong> <span id="selectedUserDisplay">None</span>
                    </div>
                    <div class="alert alert-secondary">
                        <strong>Total Sales Users Loaded:</strong> <span id="totalUsersCount">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const apiPath = "./api/";
        let selectedSalesUser = '';
        let salesUsers = [];

        function log(message, type = 'info') {
            const logElement = document.getElementById('debugLog');
            const timestamp = new Date().toLocaleTimeString('th-TH');
            const logClass = type === 'error' ? 'text-danger' : type === 'success' ? 'text-success' : 'text-info';
            
            logElement.innerHTML += `<div class="${logClass}">[${timestamp}] ${message}</div>`;
            logElement.scrollTop = logElement.scrollHeight;
        }

        function clearLog() {
            document.getElementById('debugLog').innerHTML = 'Debug log cleared...<br>';
        }

        function testAPIConnection() {
            log('Testing API connection...', 'info');
            
            fetch(apiPath + 'distribution/basket.php?action=sales_users')
                .then(response => {
                    log(`API Response Status: ${response.status}`, response.ok ? 'success' : 'error');
                    return response.json();
                })
                .then(data => {
                    log(`API Response: ${JSON.stringify(data, null, 2)}`, 'success');
                    if (data.status === 'success') {
                        log(`Found ${data.count} sales users`, 'success');
                    } else {
                        log(`API Error: ${data.error || 'Unknown error'}`, 'error');
                    }
                })
                .catch(error => {
                    log(`Network Error: ${error.message}`, 'error');
                    console.error('API Error:', error);
                });
        }

        function loadSalesUsers() {
            log('Loading sales users...', 'info');
            
            fetch(apiPath + 'distribution/basket.php?action=sales_users')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        salesUsers = data.data;
                        displaySalesUsers(data.data);
                        document.getElementById('totalUsersCount').textContent = data.data.length;
                        log(`Successfully loaded ${data.data.length} sales users`, 'success');
                    } else {
                        log(`Error loading sales users: ${data.error}`, 'error');
                    }
                })
                .catch(error => {
                    log(`Error loading sales users: ${error.message}`, 'error');
                    console.error('Error loading sales users:', error);
                });
        }

        function displaySalesUsers(users) {
            const container = document.getElementById('salesUsersList');
            
            if (users.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">ไม่มีพนักงานขายที่ใช้งานได้</div>';
                log('No active sales users found', 'error');
                return;
            }

            let html = '<div class="list-group">';
            
            users.forEach(user => {
                const isSelected = selectedSalesUser === user.username;
                const selectedClass = isSelected ? 'active' : '';
                
                html += `
                    <div class="list-group-item list-group-item-action ${selectedClass}" onclick="selectSalesUser('${user.username}')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${user.first_name} ${user.last_name}</h6>
                                <small>@${user.username}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary">${user.assigned_customers} ลูกค้า</span><br>
                                <small class="text-muted">A:${user.grade_a_customers} HOT:${user.hot_customers}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            log(`Displayed ${users.length} sales users in the list`, 'success');
        }

        function selectSalesUser(username) {
            log(`Attempting to select user: ${username}`, 'info');
            
            selectedSalesUser = username;
            document.getElementById('selectedUserDisplay').textContent = username;
            
            // Re-display to update visual selection
            displaySalesUsers(salesUsers);
            
            log(`Successfully selected user: ${username}`, 'success');
        }

        function testSelection() {
            if (salesUsers.length === 0) {
                log('No sales users loaded. Load sales users first.', 'error');
                return;
            }
            
            const testUser = salesUsers[0];
            log(`Testing selection with first user: ${testUser.username}`, 'info');
            selectSalesUser(testUser.username);
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            log('Debug tool initialized', 'success');
            log('Click "Test API Connection" to start debugging', 'info');
        });
    </script>
</body>
</html>