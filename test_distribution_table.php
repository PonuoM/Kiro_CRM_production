<?php
/**
 * Test Distribution Basket Table View
 * Simple test to verify table view functionality works correctly
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Distribution Basket Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .assignment-section {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
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
        
        .customers-table tbody tr.selected {
            background-color: rgba(118, 188, 67, 0.1);
            border-left: 4px solid #76bc43;
        }
        
        .table-container {
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        
        .checkbox-column {
            width: 50px;
            text-align: center;
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
        
        .actions-column {
            width: 80px;
            text-align: center;
        }
        
        .grade-badge, .temp-badge {
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .grade-A { background-color: #22c55e; color: white; }
        .grade-B { background-color: #3b82f6; color: white; }
        .grade-C { background-color: #f59e0b; color: white; }
        .grade-D { background-color: #6b7280; color: white; }
        
        .temp-HOT { background-color: #ef4444; color: white; }
        .temp-WARM { background-color: #f97316; color: white; }
        .temp-COLD { background-color: #6b7280; color: white; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2>🧪 Test Distribution Basket Table View</h2>
                
                <div class="assignment-section">
                    <h5><i class="fas fa-users"></i> ลูกค้าที่ยังไม่ได้แจก (แบบ Table ใหม่)</h5>
                    
                    <div id="unassignedCustomers">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>Test Actions:</h6>
                    <button class="btn btn-info" onclick="loadSampleData()">Load Sample Data</button>
                    <button class="btn btn-secondary" onclick="clearData()">Clear Data</button>
                    <button class="btn btn-success" onclick="testSelectAll()">Test Select All</button>
                    <button class="btn btn-warning" onclick="testClearSelection()">Test Clear Selection</button>
                </div>
                
                <div class="mt-3 alert alert-info" id="testResults">
                    <strong>Test Results:</strong> Click "Load Sample Data" to test the new table view.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedCustomers = new Set();
        
        // Sample customer data for testing
        const sampleCustomers = [
            {
                CustomerCode: 'D001',
                CustomerName: 'บริษัท เทสต์ จำกัด',
                CustomerTel: '02-123-4567',
                CustomerGrade: 'A',
                CustomerTemperature: 'HOT',
                CustomerStatus: 'สนใจสินค้า',
                TotalPurchase: 2500000
            },
            {
                CustomerCode: 'D002',
                CustomerName: 'ร้าน ABC การค้า',
                CustomerTel: '081-234-5678',
                CustomerGrade: 'B',
                CustomerTemperature: 'WARM',
                CustomerStatus: 'ลูกค้าใหม่',
                TotalPurchase: 850000
            },
            {
                CustomerCode: 'D003',
                CustomerName: 'องค์กร XYZ',
                CustomerTel: '089-345-6789',
                CustomerGrade: 'A',
                CustomerTemperature: 'HOT',
                CustomerStatus: 'ติดต่อกลับ',
                TotalPurchase: 1750000
            },
            {
                CustomerCode: 'D004',
                CustomerName: 'ห้างร้าน 123',
                CustomerTel: null,
                CustomerGrade: 'C',
                CustomerTemperature: 'COLD',
                CustomerStatus: 'ไม่สนใจ',
                TotalPurchase: 125000
            },
            {
                CustomerCode: 'D005',
                CustomerName: 'บริษัท นิวเทค จำกัด',
                CustomerTel: '02-987-6543',
                CustomerGrade: 'B',
                CustomerTemperature: 'WARM',
                CustomerStatus: 'รอเสนอราคา',
                TotalPurchase: 950000
            }
        ];
        
        function loadSampleData() {
            displayUnassignedCustomers(sampleCustomers);
            updateTestResults('✅ Sample data loaded successfully in table view');
        }
        
        function clearData() {
            document.getElementById('unassignedCustomers').innerHTML = '';
            selectedCustomers.clear();
            updateTestResults('🗑️ Data cleared');
        }
        
        function testSelectAll() {
            selectAllVisible();
            updateTestResults('✅ Select all function tested - Selected: ' + selectedCustomers.size + ' customers');
        }
        
        function testClearSelection() {
            clearSelection();
            updateTestResults('✅ Clear selection function tested - Selected: ' + selectedCustomers.size + ' customers');
        }
        
        function displayUnassignedCustomers(customers) {
            const container = document.getElementById('unassignedCustomers');
            
            if (customers.length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าที่ยังไม่ได้แจกตามเงื่อนไขที่กำหนด</div>';
                return;
            }

            let html = `
                <div class="table-container">
                    <table class="customers-table table table-hover">
                        <thead>
                            <tr>
                                <th class="checkbox-column">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" title="เลือก/ยกเลิกทั้งหมด">
                                </th>
                                <th class="customer-code-column">รหัสลูกค้า</th>
                                <th class="customer-name-column">ชื่อลูกค้า</th>
                                <th class="phone-column">เบอร์โทร</th>
                                <th class="grade-column">เกรด</th>
                                <th class="temp-column">อุณหภูมิ</th>
                                <th class="status-column">สถานะ</th>
                                <th class="purchase-column">ยอดซื้อ</th>
                                <th class="actions-column">การดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            customers.forEach(customer => {
                const isSelected = selectedCustomers.has(customer.CustomerCode);
                const selectedClass = isSelected ? 'selected' : '';
                
                html += `
                    <tr class="${selectedClass}" onclick="toggleCustomerSelection('${customer.CustomerCode}')" data-customer-code="${customer.CustomerCode}">
                        <td class="checkbox-column">
                            <input type="checkbox" ${isSelected ? 'checked' : ''} 
                                   onclick="event.stopPropagation(); toggleCustomerSelection('${customer.CustomerCode}')"
                                   class="customer-checkbox">
                        </td>
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
                        <td class="actions-column">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="event.stopPropagation(); viewCustomerDetail('${customer.CustomerCode}')" 
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
                <div class="mt-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="text-muted mb-0">
                                แสดง <strong>${customers.length}</strong> รายการ | 
                                เลือกแล้ว <strong><span id="selectedCount">${selectedCustomers.size}</span></strong> รายการ
                            </p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-outline-primary btn-sm" onclick="selectAllVisible()">
                                <i class="fas fa-check-square"></i> เลือกทั้งหมดในหน้านี้
                            </button>
                            <button class="btn btn-outline-secondary btn-sm ms-2" onclick="clearSelection()">
                                <i class="fas fa-square"></i> ยกเลิกการเลือก
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
            updateSelectAllCheckbox();
        }
        
        function toggleCustomerSelection(customerCode) {
            if (selectedCustomers.has(customerCode)) {
                selectedCustomers.delete(customerCode);
            } else {
                selectedCustomers.add(customerCode);
            }
            
            // Update visual selection without full reload
            const row = document.querySelector(`tr[data-customer-code="${customerCode}"]`);
            const checkbox = row.querySelector('.customer-checkbox');
            
            if (selectedCustomers.has(customerCode)) {
                row.classList.add('selected');
                checkbox.checked = true;
            } else {
                row.classList.remove('selected');
                checkbox.checked = false;
            }
            
            updateSelectedCount();
            updateSelectAllCheckbox();
        }
        
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
            
            if (selectAllCheckbox.checked) {
                // Select all visible customers
                customerCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    const customerCode = row.getAttribute('data-customer-code');
                    selectedCustomers.add(customerCode);
                    row.classList.add('selected');
                    checkbox.checked = true;
                });
            } else {
                // Deselect all visible customers
                customerCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    const customerCode = row.getAttribute('data-customer-code');
                    selectedCustomers.delete(customerCode);
                    row.classList.remove('selected');
                    checkbox.checked = false;
                });
            }
            
            updateSelectedCount();
        }
        
        function updateSelectAllCheckbox() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
            
            if (customerCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
                return;
            }
            
            const checkedBoxes = document.querySelectorAll('.customer-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedBoxes.length === customerCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
        
        function updateSelectedCount() {
            const countElement = document.getElementById('selectedCount');
            if (countElement) {
                countElement.textContent = selectedCustomers.size;
            }
        }
        
        function selectAllVisible() {
            const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
            customerCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                const customerCode = row.getAttribute('data-customer-code');
                selectedCustomers.add(customerCode);
                row.classList.add('selected');
                checkbox.checked = true;
            });
            
            updateSelectedCount();
            updateSelectAllCheckbox();
        }

        function clearSelection() {
            selectedCustomers.clear();
            
            // Update visual selection
            const customerCheckboxes = document.querySelectorAll('.customer-checkbox');
            customerCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                row.classList.remove('selected');
                checkbox.checked = false;
            });
            
            updateSelectedCount();
            updateSelectAllCheckbox();
        }
        
        function viewCustomerDetail(customerCode) {
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