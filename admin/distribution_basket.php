<?php
/**
 * Distribution Basket - SuperAdmin/Admin Feature
 * Lead assignment and distribution management
 * Phase 2: SuperAdmin Role and Admin Workflows
 */

require_once '../includes/admin_layout.php';

// Check login and distribution basket permission
Permissions::requireLogin();
Permissions::requirePermission('distribution_basket');

$pageTitle = "ตะกร้าแจกลูกค้า";

// Additional CSS for this page
$additionalCSS = '
    <style>
        .stat-card {
            background: var(--card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-left: 4px solid;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card.primary { border-left-color: var(--primary); }
        .stat-card.success { border-left-color: #22c55e; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.danger { border-left-color: #ef4444; }
        .stat-card.info { border-left-color: #3b82f6; }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--foreground);
            margin: 0 0 0.5rem 0;
        }
        
        .stat-card p {
            color: var(--muted-foreground);
            margin: 0;
            font-weight: 500;
        }
        
        .customer-card {
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background: var(--card);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .customer-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
            border-color: var(--primary);
        }
        
        .customer-card.border-primary {
            border-color: var(--primary);
            background-color: rgba(118, 188, 67, 0.05);
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
        
        .assignment-section {
            background: var(--card);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
            color: var(--muted-foreground);
        }
        
        .filter-controls {
            background: var(--card);
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
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
            border-left: 4px solid var(--primary);
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
        
        .form-label {
            font-weight: 600;
            color: var(--foreground);
            margin-bottom: 0.5rem;
        }
        
        .form-select, .form-control {
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem;
            background-color: var(--background);
            color: var(--foreground);
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(118, 188, 67, 0.2);
        }
    </style>
';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-inbox"></i>
        ตะกร้าแจกลูกค้า
    </h1>
    <p class="page-description">
        จัดการและแจกจ่ายลูกค้าให้กับทีมขาย - ระบบกระจายลูกค้าอัตโนมัติและแบบเลือก
    </p>
</div>

        <!-- Statistics Dashboard -->
        <div class="row" id="statsSection">
            <div class="col-md-2">
                <div class="stat-card warning">
                    <h3 id="unassignedCount">-</h3>
                    <p class="mb-0">ลูกค้าที่ยังไม่ได้แจก</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card success">
                    <h3 id="activeSalesCount">-</h3>
                    <p class="mb-0">พนักงานขายที่ใช้งานได้</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card danger">
                    <h3 id="hotUnassignedCount">-</h3>
                    <p class="mb-0">ลูกค้า HOT ที่ยังไม่ได้แจก</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card primary">
                    <h3 id="gradeAUnassignedCount">-</h3>
                    <p class="mb-0">ลูกค้าเกรด A ที่ยังไม่ได้แจก</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card info">
                    <h3 id="recentAssignmentsCount">-</h3>
                    <p class="mb-0">แจกใน 7 วันที่ผ่านมา</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="text-end">
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Tabs -->
        <ul class="nav nav-tabs" id="distributionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="unassigned-tab" data-bs-toggle="tab" data-bs-target="#unassigned" type="button" role="tab">
                    <i class="fas fa-users"></i> ลูกค้าที่ยังไม่ได้แจก
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button" role="tab">
                    <i class="fas fa-hand-point-right"></i> แจกลูกค้า
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="auto-distribute-tab" data-bs-toggle="tab" data-bs-target="#auto-distribute" type="button" role="tab">
                    <i class="fas fa-magic"></i> แจกอัตโนมัติ
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i> สถิติการแจก
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="distributionTabContent">
            <!-- Unassigned Customers Tab -->
            <div class="tab-pane fade show active" id="unassigned" role="tabpanel">
                <div class="filter-controls">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">กรอง Grade:</label>
                            <select class="form-select" id="gradeFilter">
                                <option value="">ทุก Grade</option>
                                <option value="A">Grade A</option>
                                <option value="B">Grade B</option>
                                <option value="C">Grade C</option>
                                <option value="D">Grade D</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">กรอง Temperature:</label>
                            <select class="form-select" id="tempFilter">
                                <option value="">ทุก Temperature</option>
                                <option value="HOT">HOT</option>
                                <option value="WARM">WARM</option>
                                <option value="COLD">COLD</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">จำนวนที่แสดง:</label>
                            <select class="form-select" id="limitFilter">
                                <option value="50">50 รายการ</option>
                                <option value="100">100 รายการ</option>
                                <option value="200">200 รายการ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button class="btn btn-primary" onclick="loadUnassignedCustomers()">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                                <button class="btn btn-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> ล้าง
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="assignment-section">
                    <h5><i class="fas fa-users"></i> ลูกค้าที่ยังไม่ได้แจก</h5>
                    <div class="loading-spinner" id="unassignedLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="unassignedCustomers"></div>
                </div>
            </div>

            <!-- Manual Assignment Tab -->
            <div class="tab-pane fade" id="assign" role="tabpanel">
                <div class="assignment-section">
                    <h5><i class="fas fa-hand-point-right"></i> แจกลูกค้าแบบเลือก</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>เลือกลูกค้าที่จะแจก</h6>
                            <div id="selectedCustomersList"></div>
                        </div>
                        <div class="col-md-6">
                            <h6>เลือกพนักงานขาย</h6>
                            <div id="salesUsersList"></div>
                            <div class="mt-3">
                                <button class="btn btn-success" onclick="assignSelectedCustomers()" id="assignButton" disabled>
                                    <i class="fas fa-check"></i> แจกลูกค้าที่เลือก
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Auto Distribution Tab -->
            <div class="tab-pane fade" id="auto-distribute" role="tabpanel">
                <div class="assignment-section">
                    <h5><i class="fas fa-magic"></i> แจกลูกค้าอัตโนมัติ</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">จำนวนลูกค้าสูงสุดต่อคน:</label>
                            <input type="number" class="form-control" id="maxPerSales" value="20" min="1" max="50">
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="prioritizeHot" checked>
                                <label class="form-check-label" for="prioritizeHot">
                                    ให้ความสำคัญกับลูกค้า HOT
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-warning" onclick="autoDistributeCustomers()">
                            <i class="fas fa-magic"></i> เริ่มแจกอัตโนมัติ
                        </button>
                    </div>
                    <div id="autoDistributeResults" class="mt-3"></div>
                </div>
            </div>

            <!-- Statistics Tab -->
            <div class="tab-pane fade" id="stats" role="tabpanel">
                <div class="assignment-section">
                    <h5><i class="fas fa-chart-bar"></i> สถิติการแจกลูกค้า</h5>
                    <div class="loading-spinner" id="statsLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="assignmentStats"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-circle text-success"></i> แจกลูกค้าสำเร็จ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="successModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Additional JavaScript
$additionalJS = '
    <script>
        let selectedCustomers = new Set();
        let selectedSalesUser = '';
        let salesUsers = [];

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshDashboard();
            loadUnassignedCustomers();
            loadSalesUsers();
        });

        // Dashboard functions
        function refreshDashboard() {
            fetch('../api/distribution/basket.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const stats = data.data.stats;
                        document.getElementById('unassignedCount').textContent = stats.unassigned;
                        document.getElementById('activeSalesCount').textContent = stats.active_sales;
                        document.getElementById('hotUnassignedCount').textContent = stats.hot_unassigned;
                        document.getElementById('gradeAUnassignedCount').textContent = stats.grade_a_unassigned;
                        document.getElementById('recentAssignmentsCount').textContent = stats.recent_assignments;
                    }
                })
                .catch(error => console.error('Error loading dashboard:', error));
        }

        function loadUnassignedCustomers() {
            const grade = document.getElementById('gradeFilter').value;
            const temp = document.getElementById('tempFilter').value;
            const limit = document.getElementById('limitFilter').value;
            
            const params = new URLSearchParams({
                action: 'unassigned',
                ...(grade && { grade }),
                ...(temp && { temperature: temp }),
                limit
            });

            document.getElementById('unassignedLoading').style.display = 'block';
            
            fetch(`../api/distribution/basket.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('unassignedLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayUnassignedCustomers(data.data);
                    } else {
                        document.getElementById('unassignedCustomers').innerHTML = 
                            `<div class="alert alert-warning">${data.error || 'ไม่สามารถโหลดข้อมูลได้'}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('unassignedLoading').style.display = 'none';
                    console.error('Error loading unassigned customers:', error);
                    document.getElementById('unassignedCustomers').innerHTML = 
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
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
            updateSelectedCustomersList();
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
            updateSelectedCustomersList();
            updateAssignButton();
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
            updateSelectedCustomersList();
            updateAssignButton();
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
        
        function viewCustomerDetail(customerCode) {
            // Placeholder function for viewing customer details
            alert(`ดูรายละเอียดลูกค้า: ${customerCode}`);
            // TODO: Implement modal or redirect to customer detail page
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
            updateSelectedCustomersList();
            updateAssignButton();
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
            updateSelectedCustomersList();
            updateAssignButton();
            updateSelectAllCheckbox();
        }

        function clearFilters() {
            document.getElementById('gradeFilter').value = '';
            document.getElementById('tempFilter').value = '';
            document.getElementById('limitFilter').value = '50';
            loadUnassignedCustomers();
        }

        function updateSelectedCustomersList() {
            const container = document.getElementById('selectedCustomersList');
            
            if (selectedCustomers.size === 0) {
                container.innerHTML = '<p class="text-muted">ยังไม่ได้เลือกลูกค้า</p>';
                return;
            }

            let html = `<div class="alert alert-info">เลือกลูกค้าแล้ว ${selectedCustomers.size} รายการ</div>`;
            html += '<div class="list-group" style="max-height: 300px; overflow-y: auto;">';
            
            selectedCustomers.forEach(customerCode => {
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${customerCode}</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromSelection('${customerCode}')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        function removeFromSelection(customerCode) {
            selectedCustomers.delete(customerCode);
            updateSelectedCustomersList();
            loadUnassignedCustomers();
            updateAssignButton();
        }

        function loadSalesUsers() {
            fetch('../api/distribution/basket.php?action=sales_users')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        salesUsers = data.data;
                        displaySalesUsers(data.data);
                    }
                })
                .catch(error => console.error('Error loading sales users:', error));
        }

        function displaySalesUsers(users) {
            const container = document.getElementById('salesUsersList');
            
            if (users.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">ไม่มีพนักงานขายที่ใช้งานได้</div>';
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
        }

        function selectSalesUser(username) {
            selectedSalesUser = username;
            displaySalesUsers(salesUsers);
            updateAssignButton();
        }

        function updateAssignButton() {
            const button = document.getElementById('assignButton');
            if (selectedCustomers.size > 0 && selectedSalesUser) {
                button.disabled = false;
            } else {
                button.disabled = true;
            }
        }

        function assignSelectedCustomers() {
            if (selectedCustomers.size === 0 || !selectedSalesUser) {
                alert('กรุณาเลือกลูกค้าและพนักงานขาย');
                return;
            }

            const data = {
                customer_codes: Array.from(selectedCustomers),
                sales_username: selectedSalesUser
            };

            fetch('../api/distribution/basket.php?action=assign', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessModal(data);
                    selectedCustomers.clear();
                    selectedSalesUser = '';
                    refreshDashboard();
                    loadUnassignedCustomers();
                    loadSalesUsers();
                    updateAssignButton();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error assigning customers:', error);
                alert('เกิดข้อผิดพลาดในการแจกลูกค้า');
            });
        }

        function autoDistributeCustomers() {
            const maxPerSales = document.getElementById('maxPerSales').value;
            const prioritizeHot = document.getElementById('prioritizeHot').checked;

            const data = {
                max_per_sales: parseInt(maxPerSales),
                prioritize_hot: prioritizeHot
            };

            fetch('../api/distribution/basket.php?action=auto_distribute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    displayAutoDistributeResults(data.data);
                    refreshDashboard();
                    loadUnassignedCustomers();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error auto distributing:', error);
                alert('เกิดข้อผิดพลาดในการแจกอัตโนมัติ');
            });
        }

        function displayAutoDistributeResults(data) {
            const container = document.getElementById('autoDistributeResults');
            
            let html = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-check-circle"></i> แจกอัตโนมัติสำเร็จ</h6>
                    <p>แจกลูกค้าได้ ${data.total_assigned} รายจาก ${data.total_customers} รายที่มี</p>
                </div>
            `;

            if (Object.keys(data.assignments).length > 0) {
                html += '<h6>รายละเอียดการแจก:</h6>';
                html += '<div class="row">';
                
                Object.entries(data.assignments).forEach(([username, count]) => {
                    html += `
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5>${username}</h5>
                                    <p class="text-primary">${count} ลูกค้า</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
            }

            container.innerHTML = html;
        }

        function showSuccessModal(data) {
            const modalBody = document.getElementById('successModalBody');
            
            let html = `
                <p><strong>แจกลูกค้าสำเร็จ ${data.data.assigned_count} รายจาก ${data.data.total_requested} รายที่เลือก</strong></p>
                <p>แจกให้: <span class="badge bg-primary">${data.data.sales_username}</span></p>
                <p>โดย: ${data.data.assigned_by}</p>
            `;

            if (data.data.errors && data.data.errors.length > 0) {
                html += '<div class="alert alert-warning"><strong>ข้อผิดพลาด:</strong><ul>';
                data.data.errors.forEach(error => {
                    html += `<li>${error}</li>`;
                });
                html += '</ul></div>';
            }

            modalBody.innerHTML = html;
            new bootstrap.Modal(document.getElementById('successModal')).show();
        }

        // Load assignment stats when tab is shown
        document.getElementById('stats-tab').addEventListener('shown.bs.tab', function() {
            loadAssignmentStats();
        });

        function loadAssignmentStats() {
            document.getElementById('statsLoading').style.display = 'block';
            
            fetch('../api/distribution/basket.php?action=assignment_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('statsLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayAssignmentStats(data.data);
                    }
                })
                .catch(error => {
                    document.getElementById('statsLoading').style.display = 'none';
                    console.error('Error loading assignment stats:', error);
                });
        }

        function displayAssignmentStats(stats) {
            const container = document.getElementById('assignmentStats');
            
            if (stats.length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่มีข้อมูลสถิติ</div>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>พนักงานขาย</th>
                                <th>ลูกค้าทั้งหมด</th>
                                <th>Grade A</th>
                                <th>HOT</th>
                                <th>ยอดขายเฉลี่ย</th>
                                <th>ยอดขายรวม</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            stats.forEach(stat => {
                html += `
                    <tr>
                        <td>${stat.first_name} ${stat.last_name}<br><small class="text-muted">@${stat.username}</small></td>
                        <td><span class="badge bg-primary">${stat.total_customers}</span></td>
                        <td><span class="badge bg-success">${stat.grade_a_count}</span></td>
                        <td><span class="badge bg-danger">${stat.hot_count}</span></td>
                        <td>฿${parseFloat(stat.avg_purchase || 0).toLocaleString()}</td>
                        <td>฿${parseFloat(stat.total_revenue || 0).toLocaleString()}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;
        }
    </script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>