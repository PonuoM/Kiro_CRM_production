<?php
/**
 * Customer List Page
 * Display customers with search and filtering functionality
 */

session_start();
require_once __DIR__ . '/../includes/functions.php';

// Check authentication
requireLogin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายชื่อลูกค้า - CRM System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .customer-card {
            transition: transform 0.2s;
        }
        .customer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8em;
        }
        .search-filters {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-users"></i> รายชื่อลูกค้า</h2>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                    <i class="fas fa-plus"></i> เพิ่มลูกค้าใหม่
                </button>
                <?php if (hasRole('Admin') || hasRole('Supervisor')): ?>
                <a href="/pages/admin/import_customers.php" class="btn btn-success">
                    <i class="fas fa-upload"></i> นำเข้าข้อมูล CSV
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filters">
            <form id="searchForm">
                <div class="row">
                    <div class="col-md-3">
                        <label for="searchTerm" class="form-label">ค้นหา</label>
                        <input type="text" class="form-control" id="searchTerm" name="search" placeholder="ชื่อ, เบอร์โทร, ที่อยู่">
                    </div>
                    <div class="col-md-2">
                        <label for="customerStatus" class="form-label">สถานะลูกค้า</label>
                        <select class="form-select" id="customerStatus" name="customer_status">
                            <option value="">ทั้งหมด</option>
                            <option value="ลูกค้าใหม่">ลูกค้าใหม่</option>
                            <option value="ลูกค้าติดตาม">ลูกค้าติดตาม</option>
                            <option value="ลูกค้าเก่า">ลูกค้าเก่า</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="cartStatus" class="form-label">สถานะตะกร้า</label>
                        <select class="form-select" id="cartStatus" name="cart_status">
                            <option value="">ทั้งหมด</option>
                            <option value="ตะกร้าแจก">ตะกร้าแจก</option>
                            <option value="ตะกร้ารอ">ตะกร้ารอ</option>
                            <option value="กำลังดูแล">กำลังดูแล</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="province" class="form-label">จังหวัด</label>
                        <input type="text" class="form-control" id="province" name="province" placeholder="จังหวัด">
                    </div>
                    <div class="col-md-2">
                        <label for="salesFilter" class="form-label">Sales</label>
                        <select class="form-select" id="salesFilter" name="sales">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="createdFrom" class="form-label">วันที่สร้างจาก</label>
                        <input type="date" class="form-control" id="createdFrom" name="created_from">
                    </div>
                    <div class="col-md-3">
                        <label for="createdTo" class="form-label">ถึงวันที่</label>
                        <input type="date" class="form-control" id="createdTo" name="created_to">
                    </div>
                    <div class="col-md-2">
                        <label for="recordsPerPage" class="form-label">แสดงต่อหน้า</label>
                        <select class="form-select" id="recordsPerPage" name="limit">
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-secondary w-100" onclick="clearFilters()">
                            <i class="fas fa-times"></i> ล้างตัวกรอง
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Summary -->
        <div id="resultsSummary" class="alert alert-info" style="display: none;">
            <i class="fas fa-info-circle"></i> <span id="summaryText"></span>
        </div>

        <!-- Customer Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="customersTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>รหัสลูกค้า</th>
                                <th>ชื่อลูกค้า</th>
                                <th>เบอร์โทรศัพท์</th>
                                <th>จังหวัด</th>
                                <th>สถานะลูกค้า</th>
                                <th>สถานะตะกร้า</th>
                                <th>Sales</th>
                                <th>วันที่สร้าง</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <!-- Data will be loaded here -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Loading indicator -->
                <div id="loadingIndicator" class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                </div>
                
                <!-- No results message -->
                <div id="noResults" class="text-center py-4" style="display: none;">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">ไม่พบข้อมูลลูกค้า</h5>
                    <p class="text-muted">ลองปรับเปลี่ยนเงื่อนไขการค้นหา</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Customer pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination">
                <!-- Pagination will be generated here -->
            </ul>
        </nav>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มลูกค้าใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCustomerForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customerName" class="form-label">ชื่อลูกค้า *</label>
                                    <input type="text" class="form-control" id="customerName" name="CustomerName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customerTel" class="form-label">เบอร์โทรศัพท์ *</label>
                                    <input type="tel" class="form-control" id="customerTel" name="CustomerTel" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="customerAddress" class="form-label">ที่อยู่</label>
                            <textarea class="form-control" id="customerAddress" name="CustomerAddress" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customerProvince" class="form-label">จังหวัด</label>
                                    <input type="text" class="form-control" id="customerProvince" name="CustomerProvince">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customerPostalCode" class="form-label">รหัสไปรษณีย์</label>
                                    <input type="text" class="form-control" id="customerPostalCode" name="CustomerPostalCode">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="agriculture" class="form-label">ประเภทการเกษตร</label>
                                    <input type="text" class="form-control" id="agriculture" name="Agriculture">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tags" class="form-label">แท็ก</label>
                                    <input type="text" class="form-control" id="tags" name="Tags" placeholder="แยกด้วยเครื่องหมายจุลภาค">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomer()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        let currentPage = 1;
        let currentFilters = {};
        
        // Initialize page
        $(document).ready(function() {
            loadSalesOptions();
            loadCustomers();
            
            // Search form submission
            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                currentPage = 1;
                loadCustomers();
            });
            
            // Auto-search on input change (debounced)
            let searchTimeout;
            $('#searchTerm').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    currentPage = 1;
                    loadCustomers();
                }, 500);
            });
        });
        
        // Load sales options for filter
        function loadSalesOptions() {
            // This would typically load from an API
            // For now, we'll add a placeholder
            $('#salesFilter').append('<option value="">กำลังโหลด...</option>');
        }
        
        // Load customers with current filters
        function loadCustomers() {
            const formData = new FormData(document.getElementById('searchForm'));
            const params = new URLSearchParams();
            
            // Add form data to params
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    params.append(key, value);
                }
            }
            
            // Add pagination
            params.append('page', currentPage);
            params.append('limit', $('#recordsPerPage').val() || 20);
            
            // Show loading
            $('#loadingIndicator').show();
            $('#customersTableBody').empty();
            $('#noResults').hide();
            $('#resultsSummary').hide();
            
            // Make API call
            fetch(`/api/customers/list.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    $('#loadingIndicator').hide();
                    
                    if (data.success) {
                        displayCustomers(data.data);
                        displayPagination(data.pagination);
                        displaySummary(data.pagination);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(error => {
                    $('#loadingIndicator').hide();
                    showError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                    console.error('Error:', error);
                });
        }
        
        // Display customers in table
        function displayCustomers(customers) {
            const tbody = $('#customersTableBody');
            tbody.empty();
            
            if (customers.length === 0) {
                $('#noResults').show();
                return;
            }
            
            customers.forEach(customer => {
                const row = `
                    <tr>
                        <td><code>${customer.CustomerCode}</code></td>
                        <td>${customer.CustomerName}</td>
                        <td>${customer.CustomerTel}</td>
                        <td>${customer.CustomerProvince || '-'}</td>
                        <td><span class="badge bg-${getStatusColor(customer.CustomerStatus)} status-badge">${customer.CustomerStatus}</span></td>
                        <td><span class="badge bg-${getCartStatusColor(customer.CartStatus)} status-badge">${customer.CartStatus}</span></td>
                        <td>${customer.Sales || '-'}</td>
                        <td>${formatDate(customer.CreatedDate)}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewCustomer('${customer.CustomerCode}')" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="editCustomer('${customer.CustomerCode}')" title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        
        // Display pagination
        function displayPagination(pagination) {
            const paginationEl = $('#pagination');
            paginationEl.empty();
            
            if (pagination.total_pages <= 1) return;
            
            // Previous button
            if (pagination.has_prev) {
                paginationEl.append(`
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">ก่อนหน้า</a>
                    </li>
                `);
            }
            
            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === pagination.current_page;
                paginationEl.append(`
                    <li class="page-item ${isActive ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                    </li>
                `);
            }
            
            // Next button
            if (pagination.has_next) {
                paginationEl.append(`
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">ถัดไป</a>
                    </li>
                `);
            }
        }
        
        // Display results summary
        function displaySummary(pagination) {
            const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
            
            $('#summaryText').text(`แสดง ${start}-${end} จาก ${pagination.total_records} รายการ`);
            $('#resultsSummary').show();
        }
        
        // Change page
        function changePage(page) {
            currentPage = page;
            loadCustomers();
        }
        
        // Clear filters
        function clearFilters() {
            document.getElementById('searchForm').reset();
            currentPage = 1;
            loadCustomers();
        }
        
        // Save new customer
        function saveCustomer() {
            const formData = new FormData(document.getElementById('addCustomerForm'));
            const customerData = {};
            
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    customerData[key] = value;
                }
            }
            
            fetch('/api/customers/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(customerData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#addCustomerModal').modal('hide');
                    document.getElementById('addCustomerForm').reset();
                    loadCustomers();
                    showSuccess('เพิ่มลูกค้าสำเร็จ');
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                showError('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                console.error('Error:', error);
            });
        }
        
        // View customer details
        function viewCustomer(customerCode) {
            window.location.href = `/pages/customer_detail.php?customer_code=${customerCode}`;
        }
        
        // Edit customer
        function editCustomer(customerCode) {
            // This would open an edit modal or navigate to edit page
            alert('ฟังก์ชันแก้ไขจะพัฒนาในขั้นตอนถัดไป');
        }
        
        // Utility functions
        function getStatusColor(status) {
            switch (status) {
                case 'ลูกค้าใหม่': return 'success';
                case 'ลูกค้าติดตาม': return 'warning';
                case 'ลูกค้าเก่า': return 'info';
                default: return 'secondary';
            }
        }
        
        function getCartStatusColor(status) {
            switch (status) {
                case 'ตะกร้าแจก': return 'danger';
                case 'ตะกร้ารอ': return 'warning';
                case 'กำลังดูแล': return 'primary';
                default: return 'secondary';
            }
        }
        
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('th-TH');
        }
        
        function showSuccess(message) {
            // Simple alert for now - could be replaced with toast notifications
            alert(message);
        }
        
        function showError(message) {
            alert('เกิดข้อผิดพลาด: ' + message);
        }
    </script>
</body>
</html>