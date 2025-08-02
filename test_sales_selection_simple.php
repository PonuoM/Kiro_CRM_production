<?php
/**
 * Simple Test for Sales Selection
 * Quick test to verify sales user selection functionality
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Sales Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Sales User Selection Styles */
        .list-group-item.list-group-item-action {
            transition: all 0.2s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }
        
        .list-group-item.list-group-item-action:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            transform: translateY(-1px);
        }
        
        .list-group-item.list-group-item-action.active {
            background-color: #007bff !important;
            border-color: #0056b3 !important;
            color: white !important;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        
        .list-group-item.list-group-item-action.active h6,
        .list-group-item.list-group-item-action.active small {
            color: white !important;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>🧪 Test Sales Selection</h2>
                <p class="text-muted">Simple test for sales user selection functionality</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h5>Test Controls</h5>
                <button class="btn btn-primary" onclick="loadTestData()">Load Test Data</button>
                <button class="btn btn-secondary" onclick="clearSelection()">Clear Selection</button>
                <button class="btn btn-info" onclick="showStatus()">Show Status</button>
            </div>
            <div class="col-md-6">
                <h5>Selection Status</h5>
                <div class="alert alert-info">
                    <strong>Selected:</strong> <span id="selectedUser">None</span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5>เลือกพนักงานขาย</h5>
                <div id="selectedSalesUserInfo" class="alert alert-success mb-3" style="display: none;">
                    <i class="fas fa-user"></i> <strong>เลือก:</strong> <span id="selectedSalesUserName">-</span>
                </div>
                <div id="salesUsersList">
                    <p class="text-muted">Click "Load Test Data" to show sales users</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedSalesUser = '';
        let salesUsers = [];

        // Sample data for testing
        const testSalesUsers = [
            {
                username: 'sales01',
                first_name: 'ทดสอบ',
                last_name: 'พนักงาน1',
                assigned_customers: 5,
                grade_a_customers: 2,
                hot_customers: 1
            },
            {
                username: 'sales02',
                first_name: 'ทดสอบ',
                last_name: 'พนักงาน2',
                assigned_customers: 8,
                grade_a_customers: 3,
                hot_customers: 2
            },
            {
                username: 'sales03',
                first_name: 'ทดสอบ',
                last_name: 'พนักงาน3',
                assigned_customers: 3,
                grade_a_customers: 1,
                hot_customers: 0
            }
        ];

        function loadTestData() {
            salesUsers = testSalesUsers;
            displaySalesUsers(salesUsers);
            console.log('Test data loaded');
        }

        function displaySalesUsers(users) {
            const container = document.getElementById('salesUsersList');
            console.log('Displaying sales users:', users.length, 'users');
            
            if (users.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">ไม่มีพนักงานขายที่ใช้งานได้</div>';
                return;
            }

            let html = '<div class="list-group">';
            
            users.forEach(user => {
                const isSelected = selectedSalesUser === user.username;
                const selectedClass = isSelected ? 'active' : '';
                
                html += `
                    <div class="list-group-item list-group-item-action ${selectedClass}" 
                         onclick="selectSalesUser('${user.username}')" 
                         style="cursor: pointer;"
                         data-username="${user.username}">
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
            console.log('Sales users displayed successfully');
            
            // Add event delegation for better reliability
            const listGroup = container.querySelector('.list-group');
            if (listGroup) {
                listGroup.addEventListener('click', function(e) {
                    const clickedItem = e.target.closest('.list-group-item');
                    if (clickedItem && clickedItem.dataset.username) {
                        selectSalesUser(clickedItem.dataset.username);
                    }
                });
            }
        }

        function selectSalesUser(username) {
            console.log('Selecting sales user:', username);
            selectedSalesUser = username;
            
            // Update display to show selection
            displaySalesUsers(salesUsers);
            
            // Show visual feedback
            const selectedUser = salesUsers.find(user => user.username === username);
            if (selectedUser) {
                const infoElement = document.getElementById('selectedSalesUserInfo');
                const nameElement = document.getElementById('selectedSalesUserName');
                
                nameElement.textContent = `${selectedUser.first_name} ${selectedUser.last_name} (@${username})`;
                infoElement.style.display = 'block';
                
                document.getElementById('selectedUser').textContent = `${selectedUser.first_name} ${selectedUser.last_name} (@${username})`;
                
                console.log(`✅ Selected: ${selectedUser.first_name} ${selectedUser.last_name} (@${username})`);
            }
        }

        function clearSelection() {
            selectedSalesUser = '';
            document.getElementById('selectedSalesUserInfo').style.display = 'none';
            document.getElementById('selectedUser').textContent = 'None';
            displaySalesUsers(salesUsers);
            console.log('Selection cleared');
        }

        function showStatus() {
            console.log('Current selection:', selectedSalesUser);
            console.log('Available users:', salesUsers.length);
            alert(`Selected: ${selectedSalesUser || 'None'}\nAvailable users: ${salesUsers.length}`);
        }
    </script>
</body>
</html>