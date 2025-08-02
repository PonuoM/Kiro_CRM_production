<?php
/**
 * Waiting Basket - Customer Management
 * Manage customers in waiting status
 * Phase 2: SuperAdmin Role and Admin Workflows
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check (avoid permission system redirect loops)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../../includes/admin_layout.php';

$pageTitle = "ตะกร้ารอ";

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
        
        .priority-high { border-left: 4px solid #dc3545; }
        .priority-medium { border-left: 4px solid #ffc107; }
        .priority-low { border-left: 4px solid #6c757d; }
        
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
        
        .filter-controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .waiting-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .contact-status {
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .contact-never { background-color: #dc3545; color: white; }
        .contact-overdue { background-color: #fd7e14; color: white; }
        .contact-due { background-color: #ffc107; color: black; }
        .contact-recent { background-color: #28a745; color: white; }
        
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
    </style>
';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-hourglass-half"></i>
        ตะกร้ารอ
    </h1>
    <p class="page-description">
        จัดการลูกค้าที่อยู่ในสถานะรอการติดต่อ - ความสำคัญและการจัดการลูกค้าตามลำดับความสำคัญ
    </p>
</div>

        <!-- Statistics Dashboard -->
        <div class="row" id="statsSection">
            <div class="col-md-2">
                <div class="stat-card warning">
                    <h3 id="totalWaitingCount">-</h3>
                    <p class="mb-0">ลูกค้าที่รออยู่</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card danger">
                    <h3 id="highPriorityCount">-</h3>
                    <p class="mb-0">ลูกค้าลำดับสูง</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card info">
                    <h3 id="coldCustomersCount">-</h3>
                    <p class="mb-0">ลูกค้า COLD</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card primary">
                    <h3 id="stagnantCount">-</h3>
                    <p class="mb-0">ไม่ติดต่อ 30+ วัน</p>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card success">
                    <h3 id="newCustomersCount">-</h3>
                    <p class="mb-0">ลูกค้าใหม่ 7 วัน</p>
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
        <ul class="nav nav-tabs" id="waitingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="waiting-customers-tab" data-bs-toggle="tab" data-bs-target="#waiting-customers" type="button" role="tab">
                    <i class="fas fa-clock"></i> ลูกค้าที่รอ
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="priority-customers-tab" data-bs-toggle="tab" data-bs-target="#priority-customers" type="button" role="tab">
                    <i class="fas fa-exclamation-triangle"></i> ลูกค้าลำดับสูง
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
                    <i class="fas fa-chart-pie"></i> สถิติการรอ
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="waitingTabContent">
            <!-- Waiting Customers Tab -->
            <div class="tab-pane fade show active" id="waiting-customers" role="tabpanel">
                <div class="filter-controls">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="form-label">กรอง Grade:</label>
                            <select class="form-select" id="gradeFilter">
                                <option value="">ทุก Grade</option>
                                <option value="A">Grade A</option>
                                <option value="B">Grade B</option>
                                <option value="C">Grade C</option>
                                <option value="D">Grade D</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">กรอง Temperature:</label>
                            <select class="form-select" id="tempFilter">
                                <option value="">ทุก Temperature</option>
                                <option value="HOT">HOT</option>
                                <option value="WARM">WARM</option>
                                <option value="COLD">COLD</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">สถานะ:</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">ทุกสถานะ</option>
                                <option value="ลูกค้าใหม่">ลูกค้าใหม่</option>
                                <option value="สนใจ">สนใจ</option>
                                <option value="ไม่สนใจ">ไม่สนใจ</option>
                                <option value="ติดต่อไม่ได้">ติดต่อไม่ได้</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">ลำดับความสำคัญ:</label>
                            <select class="form-select" id="priorityFilter">
                                <option value="">ทุกลำดับ</option>
                                <option value="HIGH">สูง</option>
                                <option value="MEDIUM">กลาง</option>
                                <option value="LOW">ต่ำ</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">จำนวนที่แสดง:</label>
                            <select class="form-select" id="limitFilter">
                                <option value="50">50 รายการ</option>
                                <option value="100">100 รายการ</option>
                                <option value="200">200 รายการ</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button class="btn btn-primary" onclick="loadWaitingCustomers()">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                                <button class="btn btn-secondary" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> ล้าง
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="waiting-section">
                    <h5><i class="fas fa-clock"></i> ลูกค้าที่รออยู่</h5>
                    <div class="loading-spinner" id="waitingLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="waitingCustomers"></div>
                </div>
            </div>

            <!-- Priority Customers Tab -->
            <div class="tab-pane fade" id="priority-customers" role="tabpanel">
                <div class="waiting-section">
                    <h5><i class="fas fa-exclamation-triangle"></i> ลูกค้าลำดับสูงที่ต้องดูแลด่วน</h5>
                    <div class="loading-spinner" id="priorityLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="priorityCustomers"></div>
                </div>
            </div>

            <!-- Statistics Tab -->
            <div class="tab-pane fade" id="stats" role="tabpanel">
                <div class="waiting-section">
                    <h5><i class="fas fa-chart-pie"></i> สถิติการรอของลูกค้า</h5>
                    <div class="loading-spinner" id="statsLoading">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                    <div id="waitingStats"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Detail Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user"></i> รายละเอียดลูกค้า</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="customerModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Additional JavaScript - Fixed heredoc syntax 2025-07-21 11:25:00
$additionalJS = <<<'JS'
    <script>
        // Use absolute API path
        const apiPath = "../../api/";
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            refreshDashboard();
            loadWaitingCustomers();
        });

        // Dashboard functions
        function refreshDashboard() {
            fetch(apiPath + 'waiting/basket.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const stats = data.data.stats;
                        document.getElementById('totalWaitingCount').textContent = stats.total_waiting;
                        document.getElementById('highPriorityCount').textContent = stats.high_priority;
                        document.getElementById('coldCustomersCount').textContent = stats.cold_customers;
                        document.getElementById('stagnantCount').textContent = stats.stagnant;
                        document.getElementById('newCustomersCount').textContent = stats.new_customers;
                    }
                })
                .catch(error => console.error('Error loading dashboard:', error));
        }

        function loadWaitingCustomers() {
            const grade = document.getElementById('gradeFilter').value;
            const temp = document.getElementById('tempFilter').value;
            const status = document.getElementById('statusFilter').value;
            const priority = document.getElementById('priorityFilter').value;
            const limit = document.getElementById('limitFilter').value;
            
            const params = new URLSearchParams({
                action: 'waiting_customers',
                ...(grade && { grade }),
                ...(temp && { temperature: temp }),
                ...(status && { status }),
                ...(priority && { priority }),
                limit
            });

            document.getElementById('waitingLoading').style.display = 'block';
            
            fetch(`${apiPath}waiting/basket.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('waitingLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayWaitingCustomers(data.data);
                    } else {
                        document.getElementById('waitingCustomers').innerHTML = 
                            `<div class="alert alert-warning">${data.error || 'ไม่สามารถโหลดข้อมูลได้'}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('waitingLoading').style.display = 'none';
                    console.error('Error loading waiting customers:', error);
                    document.getElementById('waitingCustomers').innerHTML = 
                        '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                });
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

        function clearFilters() {
            document.getElementById('gradeFilter').value = '';
            document.getElementById('tempFilter').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('priorityFilter').value = '';
            document.getElementById('limitFilter').value = '50';
            loadWaitingCustomers();
        }

        function showCustomerDetail(customerCode) {
            fetch(`${apiPath}waiting/basket.php?action=customer_history&customer_code=${customerCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        displayCustomerDetail(data.data);
                        new bootstrap.Modal(document.getElementById('customerModal')).show();
                    } else {
                        alert('ไม่สามารถโหลดข้อมูลลูกค้าได้');
                    }
                })
                .catch(error => {
                    console.error('Error loading customer detail:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                });
        }

        function displayCustomerDetail(data) {
            const customer = data.customer;
            const recommendations = data.recommendations;
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>ข้อมูลลูกค้า</h6>
                        <table class="table table-sm">
                            <tr><td><strong>รหัสลูกค้า:</strong></td><td>${customer.CustomerCode}</td></tr>
                            <tr><td><strong>ชื่อ:</strong></td><td>${customer.CustomerName}</td></tr>
                            <tr><td><strong>เบอร์โทร:</strong></td><td>${customer.CustomerTel || 'ไม่ระบุ'}</td></tr>
                            <tr><td><strong>สถานะ:</strong></td><td>${customer.CustomerStatus}</td></tr>
                            <tr><td><strong>เกรด:</strong></td><td><span class="grade-badge grade-${customer.CustomerGrade}">${customer.CustomerGrade}</span></td></tr>
                            <tr><td><strong>อุณหภูมิ:</strong></td><td><span class="temp-badge temp-${customer.CustomerTemperature}">${customer.CustomerTemperature}</span></td></tr>
                            <tr><td><strong>ยอดซื้อรวม:</strong></td><td>฿${parseFloat(customer.TotalPurchase).toLocaleString()}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>ข้อมูลการติดต่อ</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ติดต่อครั้งล่าสุด:</strong></td><td>${customer.LastContactDate || 'ไม่เคยติดต่อ'}</td></tr>
                            <tr><td><strong>จำนวนครั้งที่พยายาม:</strong></td><td>${customer.ContactAttempts} ครั้ง</td></tr>
                            <tr><td><strong>สรุปการติดต่อ:</strong></td><td>${customer.ContactSummary}</td></tr>
                            <tr><td><strong>สถานะการรอ:</strong></td><td>${data.waiting_status}</td></tr>
                            <tr><td><strong>วันที่สร้าง:</strong></td><td>${new Date(customer.CreatedAt).toLocaleDateString('th-TH')}</td></tr>
                            <tr><td><strong>อัปเดตล่าสุด:</strong></td><td>${new Date(customer.UpdatedAt).toLocaleDateString('th-TH')}</td></tr>
                        </table>
                    </div>
                </div>
            `;

            if (recommendations.length > 0) {
                html += `
                    <div class="mt-3">
                        <h6>คำแนะนำ</h6>
                        <div class="alert alert-info">
                            <ul class="mb-0">
                `;
                recommendations.forEach(rec => {
                    html += `<li>${rec}</li>`;
                });
                html += `
                            </ul>
                        </div>
                    </div>
                `;
            }

            document.getElementById('customerModalBody').innerHTML = html;
        }

        // Load priority customers when tab is shown
        document.getElementById('priority-customers-tab').addEventListener('shown.bs.tab', function() {
            loadPriorityCustomers();
        });

        function loadPriorityCustomers() {
            document.getElementById('priorityLoading').style.display = 'block';
            
            fetch('../api/waiting/basket.php?action=priority_customers')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('priorityLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayPriorityCustomers(data.data);
                    }
                })
                .catch(error => {
                    document.getElementById('priorityLoading').style.display = 'none';
                    console.error('Error loading priority customers:', error);
                });
        }

        function displayPriorityCustomers(customers) {
            const container = document.getElementById('priorityCustomers');
            
            if (customers.length === 0) {
                container.innerHTML = '<div class="alert alert-info">ไม่มีลูกค้าลำดับสูงในขณะนี้</div>';
                return;
            }

            let html = '<div class="row">';
            
            customers.forEach(customer => {
                const priorityLevel = customer.PriorityLevel;
                const contactStatus = customer.ContactStatus;
                
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="customer-card priority-${priorityLevel.toLowerCase()}" onclick="showCustomerDetail('${customer.CustomerCode}')">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${customer.CustomerName}</h6>
                                <span class="priority-badge priority-${priorityLevel}">${priorityLevel}</span>
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
                            <div class="alert alert-warning py-1 px-2 mb-0">
                                <small><strong>การติดต่อ:</strong> ${contactStatus}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        // Load waiting stats when tab is shown
        document.getElementById('stats-tab').addEventListener('shown.bs.tab', function() {
            loadWaitingStats();
        });

        function loadWaitingStats() {
            document.getElementById('statsLoading').style.display = 'block';
            
            fetch('../api/waiting/basket.php?action=waiting_stats')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('statsLoading').style.display = 'none';
                    
                    if (data.status === 'success') {
                        displayWaitingStats(data.data);
                    }
                })
                .catch(error => {
                    document.getElementById('statsLoading').style.display = 'none';
                    console.error('Error loading waiting stats:', error);
                });
        }

        function displayWaitingStats(stats) {
            const container = document.getElementById('waitingStats');
            
            let html = '<div class="row">';
            
            // Grade distribution
            if (stats.grade_distribution && stats.grade_distribution.length > 0) {
                html += `
                    <div class="col-md-6">
                        <h6>การกระจายตามเกรด</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>เกรด</th><th>จำนวน</th><th>ยอดซื้อเฉลี่ย</th></tr></thead>
                                <tbody>
                `;
                stats.grade_distribution.forEach(item => {
                    html += `
                        <tr>
                            <td><span class="grade-badge grade-${item.grade}">${item.grade}</span></td>
                            <td>${item.count}</td>
                            <td>฿${parseFloat(item.avg_purchase).toLocaleString()}</td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
            }
            
            // Temperature distribution
            if (stats.temperature_distribution && stats.temperature_distribution.length > 0) {
                html += `
                    <div class="col-md-6">
                        <h6>การกระจายตามอุณหภูมิ</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>อุณหภูมิ</th><th>จำนวน</th><th>เฉลี่ยวันไม่ติดต่อ</th></tr></thead>
                                <tbody>
                `;
                stats.temperature_distribution.forEach(item => {
                    html += `
                        <tr>
                            <td><span class="temp-badge temp-${item.temperature}">${item.temperature}</span></td>
                            <td>${item.count}</td>
                            <td>${Math.round(item.avg_days_no_contact)} วัน</td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
            }
            
            html += '</div><div class="row">';
            
            // Contact status
            if (stats.contact_status && stats.contact_status.length > 0) {
                html += `
                    <div class="col-md-6">
                        <h6>สถานะการติดต่อ</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>สถานะการติดต่อ</th><th>จำนวน</th></tr></thead>
                                <tbody>
                `;
                stats.contact_status.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.contact_category}</td>
                            <td><span class="badge bg-primary">${item.count}</span></td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
            }
            
            // Status distribution
            if (stats.status_distribution && stats.status_distribution.length > 0) {
                html += `
                    <div class="col-md-6">
                        <h6>การกระจายตามสถานะ</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>สถานะลูกค้า</th><th>จำนวน</th></tr></thead>
                                <tbody>
                `;
                stats.status_distribution.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.CustomerStatus}</td>
                            <td><span class="badge bg-secondary">${item.count}</span></td>
                        </tr>
                    `;
                });
                html += '</tbody></table></div></div>';
            }
            
            html += '</div>';
            container.innerHTML = html;
        }
    </script>
JS;

// Render the page
echo renderAdminLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>