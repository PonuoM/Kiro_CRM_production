<?php
/**
 * Test Table View Implementation
 * Simple test to verify table view functionality works correctly
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Table View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        .waiting-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-view {
            display: none;
        }
        .table-view.active {
            display: block;
        }
        .card-view.active {
            display: block;
        }
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .customer-table th,
        .customer-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        .customer-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-top: 1px solid #dee2e6;
            color: #495057;
        }
        .customer-table tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        .grade-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .grade-A { background-color: #28a745; color: white; }
        .grade-B { background-color: #007bff; color: white; }
        .grade-C { background-color: #ffc107; color: black; }
        .grade-D { background-color: #6c757d; color: white; }
        .temp-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .temp-HOT { background-color: #dc3545; color: white; }
        .temp-WARM { background-color: #fd7e14; color: white; }
        .temp-COLD { background-color: #6c757d; color: white; }
        .contact-status {
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 3px;
        }
        .contact-never { background-color: #dc3545; color: white; }
        .contact-overdue { background-color: #fd7e14; color: white; }
        .contact-due { background-color: #ffc107; color: black; }
        .contact-recent { background-color: #28a745; color: white; }
        .badge-priority {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #6c757d; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2>🧪 Test Table View Implementation</h2>
                
                <div class="waiting-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5><i class="fas fa-clock"></i> ลูกค้าที่รออยู่</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="cardViewBtn" onclick="switchView('card')">
                                <i class="fas fa-th-large"></i> การ์ด
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="tableViewBtn" onclick="switchView('table')">
                                <i class="fas fa-table"></i> ตาราง
                            </button>
                        </div>
                    </div>
                    
                    <div id="waitingCustomers">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Test Actions:</h6>
                    <button class="btn btn-info" onclick="loadSampleData()">Load Sample Data</button>
                    <button class="btn btn-secondary" onclick="clearData()">Clear Data</button>
                </div>
                
                <div class="mt-3 alert alert-info" id="testResults">
                    <strong>Test Results:</strong> Click "Load Sample Data" to test the table view functionality.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentView = 'table'; // Default view
        
        // Sample customer data for testing
        const sampleCustomers = [
            {
                CustomerCode: 'C001',
                CustomerName: 'ทดสอบ ลูกค้า 1',
                CustomerTel: '081-234-5678',
                CustomerGrade: 'A',
                CustomerTemperature: 'HOT',
                CustomerStatus: 'สนใจ',
                TotalPurchase: 150000,
                DaysSinceContact: 3,
                Priority: 'HIGH',
                ContactAttempts: 2
            },
            {
                CustomerCode: 'C002',
                CustomerName: 'ทดสอบ ลูกค้า 2',
                CustomerTel: '082-345-6789',
                CustomerGrade: 'B',
                CustomerTemperature: 'WARM',
                CustomerStatus: 'ลูกค้าใหม่',
                TotalPurchase: 75000,
                DaysSinceContact: 7,
                Priority: 'MEDIUM',
                ContactAttempts: 1
            },
            {
                CustomerCode: 'C003',
                CustomerName: 'ทดสอบ ลูกค้า 3',
                CustomerTel: '083-456-7890',
                CustomerGrade: 'C',
                CustomerTemperature: 'COLD',
                CustomerStatus: 'ไม่สนใจ',
                TotalPurchase: 25000,
                DaysSinceContact: 45,
                Priority: 'LOW',
                ContactAttempts: 5
            }
        ];
        
        // Initialize with table view
        document.addEventListener('DOMContentLoaded', function() {
            updateViewButtons();
        });
        
        function loadSampleData() {
            displayWaitingCustomers(sampleCustomers);
            updateTestResults('✅ Sample data loaded successfully in ' + currentView + ' view');
        }
        
        function clearData() {
            document.getElementById('waitingCustomers').innerHTML = '';
            updateTestResults('🗑️ Data cleared');
        }
        
        function displayWaitingCustomers(customers) {
            const container = document.getElementById('waitingCustomers');
            
            if (customers.length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าที่รออยู่ตามเงื่อนไขที่กำหนด</div>';
                return;
            }

            if (currentView === 'table') {
                displayTableView(customers, container);
            } else {
                displayCardView(customers, container);
            }
        }
        
        function displayTableView(customers, container) {
            let html = `
                <div class="table-view active">
                    <div class="table-responsive">
                        <table class="customer-table table">
                            <thead>
                                <tr>
                                    <th>รหัสลูกค้า</th>
                                    <th>ชื่อลูกค้า</th>
                                    <th>เบอร์โทร</th>
                                    <th>เกรด</th>
                                    <th>อุณหภูมิ</th>
                                    <th>สถานะ</th>
                                    <th>ยอดซื้อ</th>
                                    <th>ติดต่อล่าสุด</th>
                                    <th>ความสำคัญ</th>
                                    <th class="actions-column">การดำเนินการ</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            customers.forEach(customer => {
                const priority = customer.Priority || 'MEDIUM';
                const priorityClass = `priority-${priority.toLowerCase()}`;
                const contactStatus = getContactStatus(customer.DaysSinceContact);
                
                html += `
                    <tr class="${priorityClass}" onclick="showCustomerDetail('${customer.CustomerCode}')">
                        <td><strong>${customer.CustomerCode}</strong></td>
                        <td>${customer.CustomerName}</td>
                        <td>${customer.CustomerTel || 'ไม่ระบุ'}</td>
                        <td><span class="grade-badge grade-${customer.CustomerGrade}">${customer.CustomerGrade}</span></td>
                        <td><span class="temp-badge temp-${customer.CustomerTemperature}">${customer.CustomerTemperature}</span></td>
                        <td><small>${customer.CustomerStatus}</small></td>
                        <td>฿${parseFloat(customer.TotalPurchase).toLocaleString()}</td>
                        <td><span class="contact-status ${contactStatus.class}" style="font-size: 0.8rem;">${contactStatus.text}</span></td>
                        <td><span class="badge badge-priority bg-${getPriorityColor(priority)}">${getPriorityLabel(priority)}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showCustomerDetail('${customer.CustomerCode}')" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 text-center">
                        <p class="text-muted">แสดง ${customers.length} รายการ</p>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        }
        
        function displayCardView(customers, container) {
            let html = `<div class="card-view active"><div class="row">`;
            
            customers.forEach(customer => {
                const priority = customer.Priority || 'MEDIUM';
                const priorityClass = `priority-${priority.toLowerCase()}`;
                const contactStatus = getContactStatus(customer.DaysSinceContact);
                
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card ${priorityClass}" onclick="showCustomerDetail('${customer.CustomerCode}')">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${customer.CustomerName}</h6>
                                <span class="badge bg-${getPriorityColor(priority)}">${getPriorityLabel(priority)}</span>
                            </div>
                            <p class="text-muted mb-1">รหัส: ${customer.CustomerCode}</p>
                            <p class="text-muted mb-2">โทร: ${customer.CustomerTel || 'ไม่ระบุ'}</p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <span class="grade-badge grade-${customer.CustomerGrade}">${customer.CustomerGrade}</span>
                                    <span class="temp-badge temp-${customer.CustomerTemperature} ms-1">${customer.CustomerTemperature}</span>
                                </div>
                                <small class="text-muted">฿${parseFloat(customer.TotalPurchase).toLocaleString()}</small>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">สถานะ: ${customer.CustomerStatus}</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="contact-status ${contactStatus.class}">${contactStatus.text}</span>
                                <small class="text-muted">พยายาม: ${customer.ContactAttempts} ครั้ง</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `</div><div class="mt-3 text-center">
                        <p class="text-muted">แสดง ${customers.length} รายการ</p>
                     </div></div>`;
            
            container.innerHTML = html;
        }

        function getContactStatus(daysSinceContact) {
            if (daysSinceContact === null || daysSinceContact >= 999) {
                return { class: 'contact-never', text: 'ไม่เคยติดต่อ' };
            } else if (daysSinceContact > 30) {
                return { class: 'contact-never', text: `${daysSinceContact} วันที่แล้ว` };
            } else if (daysSinceContact > 14) {
                return { class: 'contact-overdue', text: `${daysSinceContact} วันที่แล้ว` };
            } else if (daysSinceContact > 7) {
                return { class: 'contact-due', text: `${daysSinceContact} วันที่แล้ว` };
            } else {
                return { class: 'contact-recent', text: `${daysSinceContact} วันที่แล้ว` };
            }
        }

        function getPriorityLabel(priority) {
            switch(priority) {
                case 'HIGH': return 'สูง';
                case 'MEDIUM': return 'กลาง';
                case 'LOW': return 'ต่ำ';
                default: return 'กลาง';
            }
        }
        
        function getPriorityColor(priority) {
            switch(priority) {
                case 'HIGH': return 'danger';
                case 'MEDIUM': return 'warning';
                case 'LOW': return 'secondary';
                default: return 'warning';
            }
        }
        
        // View switching functions
        function switchView(viewType) {
            currentView = viewType;
            updateViewButtons();
            
            // Reload data if exists
            const container = document.getElementById('waitingCustomers');
            if (container.innerHTML.trim() !== '') {
                displayWaitingCustomers(sampleCustomers);
            }
            
            updateTestResults(`🔄 Switched to ${viewType} view`);
        }
        
        function updateViewButtons() {
            const cardBtn = document.getElementById('cardViewBtn');
            const tableBtn = document.getElementById('tableViewBtn');
            
            if (currentView === 'table') {
                cardBtn.classList.remove('btn-primary');
                cardBtn.classList.add('btn-outline-primary');
                tableBtn.classList.remove('btn-outline-primary');
                tableBtn.classList.add('btn-primary');
            } else {
                tableBtn.classList.remove('btn-primary');
                tableBtn.classList.add('btn-outline-primary');
                cardBtn.classList.remove('btn-outline-primary');
                cardBtn.classList.add('btn-primary');
            }
        }

        function showCustomerDetail(customerCode) {
            updateTestResults(`👁️ Clicked on customer: ${customerCode}`);
        }
        
        function updateTestResults(message) {
            const timestamp = new Date().toLocaleTimeString('th-TH');
            document.getElementById('testResults').innerHTML = 
                `<strong>Test Results [${timestamp}]:</strong> ${message}`;
        }
    </script>
</body>
</html>