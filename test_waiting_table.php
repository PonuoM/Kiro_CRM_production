<?php
/**
 * Test Waiting Basket Table View
 * Simple test to verify table view functionality works correctly
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Waiting Basket Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .waiting-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        /* Table styles for customer display */
        .customers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .customers-table th,
        .customers-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .customers-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-top: 1px solid #dee2e6;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .customers-table tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .customers-table tbody tr.priority-high {
            border-left: 4px solid #dc3545;
        }
        
        .customers-table tbody tr.priority-medium {
            border-left: 4px solid #ffc107;
        }
        
        .customers-table tbody tr.priority-low {
            border-left: 4px solid #6c757d;
        }
        
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        .customer-code-column {
            width: 100px;
            font-weight: 600;
        }
        
        .customer-name-column {
            min-width: 180px;
        }
        
        .phone-column {
            width: 130px;
        }
        
        .grade-column,
        .temp-column {
            width: 80px;
            text-align: center;
        }
        
        .status-column {
            width: 120px;
        }
        
        .purchase-column {
            width: 120px;
            text-align: right;
        }
        
        .contact-column {
            width: 130px;
        }
        
        .priority-column {
            width: 100px;
            text-align: center;
        }
        
        .attempts-column {
            width: 80px;
            text-align: center;
        }
        
        .actions-column {
            width: 80px;
            text-align: center;
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
        
        .priority-badge {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .priority-URGENT { background-color: #dc3545; color: white; }
        .priority-HIGH { background-color: #fd7e14; color: white; }
        .priority-NORMAL { background-color: #28a745; color: white; }
        
        .contact-status {
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .contact-never { background-color: #dc3545; color: white; }
        .contact-overdue { background-color: #fd7e14; color: white; }
        .contact-due { background-color: #ffc107; color: black; }
        .contact-recent { background-color: #28a745; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2>🧪 Test Waiting Basket Table View</h2>
                
                <div class="waiting-section">
                    <h5><i class="fas fa-clock"></i> ลูกค้าที่รออยู่ (แบบ Table ใหม่)</h5>
                    
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
                    <strong>Test Results:</strong> Click "Load Sample Data" to test the new table view for Waiting Basket.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sample customer data for testing
        const sampleCustomers = [
            {
                CustomerCode: 'W001',
                CustomerName: 'บริษัท เทสต์ รอลูกค้า จำกัด',
                CustomerTel: '02-111-2222',
                CustomerGrade: 'A',
                CustomerTemperature: 'HOT',
                CustomerStatus: 'สนใจสินค้า',
                TotalPurchase: 3500000,
                DaysSinceContact: 2,
                Priority: 'HIGH',
                ContactAttempts: 3
            },
            {
                CustomerCode: 'W002',
                CustomerName: 'ร้าน ABC รอติดต่อ',
                CustomerTel: '081-333-4444',
                CustomerGrade: 'B',
                CustomerTemperature: 'WARM',
                CustomerStatus: 'ลูกค้าใหม่',
                TotalPurchase: 1250000,
                DaysSinceContact: 8,
                Priority: 'MEDIUM',
                ContactAttempts: 1
            },
            {
                CustomerCode: 'W003',
                CustomerName: 'องค์กร XYZ รอคอล',
                CustomerTel: '089-555-6666',
                CustomerGrade: 'A',
                CustomerTemperature: 'HOT',
                CustomerStatus: 'ติดต่อกลับ',
                TotalPurchase: 2750000,
                DaysSinceContact: 5,
                Priority: 'HIGH',
                ContactAttempts: 2
            },
            {
                CustomerCode: 'W004',
                CustomerName: 'ห้างร้าน 456 รอดู',
                CustomerTel: null,
                CustomerGrade: 'C',
                CustomerTemperature: 'COLD',
                CustomerStatus: 'ไม่สนใจ',
                TotalPurchase: 225000,
                DaysSinceContact: 35,
                Priority: 'LOW',
                ContactAttempts: 8
            },
            {
                CustomerCode: 'W005',
                CustomerName: 'บริษัท โซลูชั่น จำกัด',
                CustomerTel: '02-777-8888',
                CustomerGrade: 'B',
                CustomerTemperature: 'WARM',
                CustomerStatus: 'รอเสนอราคา',
                TotalPurchase: 1850000,
                DaysSinceContact: 12,
                Priority: 'MEDIUM',
                ContactAttempts: 4
            }
        ];
        
        function loadSampleData() {
            displayWaitingCustomers(sampleCustomers);
            updateTestResults('✅ Sample data loaded successfully in table view');
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

            let html = `
                <div class="table-container">
                    <table class="customers-table table table-hover">
                        <thead>
                            <tr>
                                <th class="customer-code-column">รหัสลูกค้า</th>
                                <th class="customer-name-column">ชื่อลูกค้า</th>
                                <th class="phone-column">เบอร์โทร</th>
                                <th class="grade-column">เกรด</th>
                                <th class="temp-column">อุณหภูมิ</th>
                                <th class="status-column">สถานะ</th>
                                <th class="purchase-column">ยอดซื้อ</th>
                                <th class="contact-column">ติดต่อล่าสุด</th>
                                <th class="priority-column">ความสำคัญ</th>
                                <th class="attempts-column">ครั้ง</th>
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
                        <td class="customer-code-column">
                            <strong>${customer.CustomerCode}</strong>
                        </td>
                        <td class="customer-name-column">
                            <div>
                                <strong>${customer.CustomerName}</strong>
                                <br><small class="text-muted">${customer.CustomerStatus}</small>
                            </div>
                        </td>
                        <td class="phone-column">
                            ${customer.CustomerTel || '<span class="text-muted">ไม่ระบุ</span>'}
                        </td>
                        <td class="grade-column">
                            <span class="grade-badge grade-${customer.CustomerGrade}">${customer.CustomerGrade}</span>
                        </td>
                        <td class="temp-column">
                            <span class="temp-badge temp-${customer.CustomerTemperature}">${customer.CustomerTemperature}</span>
                        </td>
                        <td class="status-column">
                            <small>${customer.CustomerStatus}</small>
                        </td>
                        <td class="purchase-column">
                            <strong>฿${parseFloat(customer.TotalPurchase).toLocaleString()}</strong>
                        </td>
                        <td class="contact-column">
                            <span class="contact-status ${contactStatus.class}" style="font-size: 0.8rem;">${contactStatus.text}</span>
                        </td>
                        <td class="priority-column">
                            <span class="priority-badge priority-${priority}">${getPriorityLabel(priority)}</span>
                        </td>
                        <td class="attempts-column">
                            <span class="badge bg-secondary">${customer.ContactAttempts}</span>
                        </td>
                        <td class="actions-column">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="event.stopPropagation(); showCustomerDetail('${customer.CustomerCode}')" 
                                    title="ดูรายละเอียด">
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
                    <p class="text-muted">แสดง <strong>${customers.length}</strong> รายการ</p>
                </div>
            `;
            
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
        
        function showCustomerDetail(customerCode) {
            updateTestResults(`👁️ Viewing customer detail: ${customerCode}`);
        }
        
        function updateTestResults(message) {
            const timestamp = new Date().toLocaleTimeString('th-TH');
            document.getElementById('testResults').innerHTML = 
                `<strong>Test Results [${timestamp}]:</strong> ${message}`;
        }
    </script>
</body>
</html>