<?php
require_once '../includes/permissions.php';
require_once '../includes/main_layout.php';

// Check login and permission
Permissions::requireLogin();
Permissions::requirePermission('customer_list');

$pageTitle = "รายการขาย";

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for main_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// Permissions for this page
$canEdit = Permissions::hasPermission('customer_edit');
$canViewAll = Permissions::canViewAllData();

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-chart-line"></i>
        รายการขาย
    </h1>
    <p class="page-description">
        รายการและสถิติการขายของคุณและทีม
    </p>
</div>

<!-- Role Notice -->
<div class="alert alert-info border-start border-primary border-4 mb-4">
    <?php if ($canViewAll): ?>
        <strong><i class="fas fa-chart-bar"></i> สิทธิ์การเข้าถึง:</strong> คุณสามารถดูรายการขายของทีมทั้งหมดในระบบ
    <?php else: ?>
        <strong><i class="fas fa-user"></i> สิทธิ์การเข้าถึง:</strong> คุณจะเห็นเฉพาะรายการขายของคุณเท่านั้น
    <?php endif; ?>
    
    <?php if ($canEdit): ?>
        | <i class="fas fa-eye"></i> คุณสามารถดูรายละเอียดการขายได้
    <?php else: ?>
        | <i class="fas fa-eye"></i> คุณสามารถดูข้อมูลเท่านั้น ไม่สามารถแก้ไขได้
    <?php endif; ?>
</div>

<!-- Statistics Cards -->
<div class="row mb-4" id="statsCards">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title mb-1" id="totalOrders">-</h3>
                        <p class="card-text mb-0">ยอดขายทั้งหมด</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-success text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title mb-1" id="todayOrders">-</h3>
                        <p class="card-text mb-0">ยอดขายวันนี้</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-info text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title mb-1" id="monthOrders">-</h3>
                        <p class="card-text mb-0">ยอดขายเดือนนี้</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border-0 bg-warning text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title mb-1" id="totalSales">-</h3>
                        <p class="card-text mb-0">มูลค่ารวม (บาท)</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Section -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <input type="text" class="form-control" id="search" placeholder="ค้นหาชื่อลูกค้า, หมายเลขคำสั่งซื้อ, หรือสินค้า..." onkeyup="searchSales()">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary" onclick="loadSalesRecords()">
                    <i class="fas fa-sync-alt"></i> รีเฟรช
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sales Records List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-chart-line"></i> รายการขายของคุณ
        </h5>
        <div>
            <button class="btn btn-primary btn-sm" onclick="refreshSalesRecords()">
                <i class="fas fa-sync-alt"></i> รีเฟรช
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Loading indicator -->
        <div class="text-center py-4" id="salesLoading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูลรายการขาย...
        </div>
        
        <!-- Sales records table -->
        <div id="salesRecordsList"></div>
    </div>
</div>

<!-- Back to Dashboard -->
<div class="mt-4">
    <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
    </a>
</div>

<?php
$content = ob_get_clean();

// Additional JavaScript
$additionalJS = '
    <script>
        class SalesRecordsManager {
            constructor() {
                this.salesRecords = [];
                this.filteredSales = [];
                this.init();
            }
            
            init() {
                this.loadSalesRecords();
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                const searchInput = document.getElementById("search");
                if (searchInput) {
                    searchInput.addEventListener("input", (e) => {
                        this.filterSales(e.target.value);
                    });
                }
            }
            
            async loadSalesRecords() {
                const loadingEl = document.getElementById("salesLoading");
                const contentEl = document.getElementById("salesRecordsList");
                
                loadingEl.style.display = "flex";
                contentEl.innerHTML = "";
                
                try {
                    const response = await fetch("../api/sales/sales_records.php");
                    const data = await response.json();
                    
                    loadingEl.style.display = "none";
                    
                    if (data.success && data.data && data.data.sales_records.length > 0) {
                        this.salesRecords = data.data.sales_records;
                        this.filteredSales = [...this.salesRecords];
                        this.updateStats(data.data.summary);
                        contentEl.innerHTML = this.renderSalesTable(this.filteredSales);
                    } else {
                        contentEl.innerHTML = this.renderEmptyState("ไม่พบข้อมูลลูกค้า", "ยังไม่มีลูกค้าในระบบ หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูลลูกค้า");
                    }
                } catch (error) {
                    console.error("Error loading customers:", error);
                    loadingEl.style.display = "none";
                    contentEl.innerHTML = this.renderErrorState("เกิดข้อผิดพลาดในการโหลดข้อมูลลูกค้า");
                }
            }
            
            filterCustomers(searchTerm) {
                if (!searchTerm.trim()) {
                    this.filteredCustomers = [...this.customers];
                } else {
                    const term = searchTerm.toLowerCase();
                    this.filteredCustomers = this.customers.filter(customer => 
                        customer.CustomerName.toLowerCase().includes(term) ||
                        customer.CustomerCode.toLowerCase().includes(term) ||
                        (customer.CustomerTel && customer.CustomerTel.includes(term)) ||
                        (customer.CustomerProvince && customer.CustomerProvince.toLowerCase().includes(term))
                    );
                }
                
                const contentEl = document.getElementById("customersList");
                contentEl.innerHTML = this.renderCustomersTable(this.filteredCustomers);
            }
            
            renderCustomersTable(customers) {
                if (!customers || customers.length === 0) {
                    return this.renderEmptyState("ไม่พบลูกค้า", "ไม่มีลูกค้าที่ตรงกับเงื่อนไขการค้นหา");
                }
                
                return `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>รหัสลูกค้า</th>
                                    <th>ชื่อลูกค้า</th>
                                    <th>เบอร์โทร</th>
                                    <th>จังหวัด</th>
                                    <th>สถานะ</th>
                                    <th>Sales</th>
                                    <th>วันที่สร้าง</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${customers.map(customer => `
                                    <tr>
                                        <td><span class="badge bg-primary">${customer.CustomerCode}</span></td>
                                        <td><strong>${this.escapeHtml(customer.CustomerName)}</strong></td>
                                        <td>
                                            ${customer.CustomerTel ? 
                                                `<i class="fas fa-phone text-primary"></i> ${this.escapeHtml(customer.CustomerTel)}` : 
                                                `<span class="text-muted">ไม่ระบุ</span>`
                                            }
                                        </td>
                                        <td>${customer.CustomerProvince ? this.escapeHtml(customer.CustomerProvince) : "-"}</td>
                                        <td><span class="badge ${this.getStatusBadgeClass(customer.CustomerStatus)}">${this.escapeHtml(customer.CustomerStatus || "ไม่ระบุ")}</span></td>
                                        <td>
                                            ${customer.Sales ? 
                                                `<span class="badge bg-info">${this.escapeHtml(customer.Sales)}</span>` : 
                                                `<span class="text-muted">ยังไม่มอบหมาย</span>`
                                            }
                                        </td>
                                        <td>${this.formatDate(customer.CreatedDate)}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewCustomerDetail(\'${customer.CustomerCode}\')" title="ดูรายละเอียด">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" onclick="callCustomer(\'${customer.CustomerTel || ""}\')" title="โทรหาลูกค้า" ${!customer.CustomerTel ? "disabled" : ""}>
                                                    <i class="fas fa-phone"></i>
                                                </button>
                                                <?php if ($canEdit): ?>
                                                <button class="btn btn-outline-warning" onclick="editCustomer(\'${customer.CustomerCode}\')" title="แก้ไข">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php else: ?>
                                                <span class="btn btn-outline-secondary btn-sm disabled" title="ไม่มีสิทธิ์แก้ไข">
                                                    <i class="fas fa-lock"></i>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                `).join("")}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">แสดง ${customers.length} รายการ จาก ${this.customers.length} ลูกค้าทั้งหมด</span>
                            <?php if (!$canViewAll): ?>
                                <div class="alert alert-info mb-0 py-1 px-2 small">
                                    <i class="fas fa-info-circle"></i> แสดงเฉพาะลูกค้าที่ได้รับมอบหมาย
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                `;
            }
            
            renderEmptyState(title, message) {
                return `
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">${title}</h5>
                        <p class="text-muted">${message}</p>
                        <?php if ($canEdit): ?>
                            <button class="btn btn-primary" onclick="addNewCustomer()">
                                <i class="fas fa-plus"></i> เพิ่มลูกค้าใหม่
                            </button>
                        <?php endif; ?>
                    </div>
                `;
            }
            
            renderErrorState(message) {
                return `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>เกิดข้อผิดพลาด:</strong> ${message}
                        <br><button class="btn btn-sm btn-danger mt-2" onclick="customerManager.loadCustomers()">ลองใหม่</button>
                    </div>
                `;
            }
            
            getStatusBadgeClass(status) {
                switch(status) {
                    case "ลูกค้าใหม่": return "bg-success";
                    case "ลูกค้าติดตาม": return "bg-warning";
                    case "ลูกค้าเก่า": return "bg-info";
                    default: return "bg-secondary";
                }
            }
            
            formatDate(dateString) {
                if (!dateString) return "-";
                const date = new Date(dateString);
                return date.toLocaleDateString("th-TH", {
                    year: "numeric",
                    month: "short",
                    day: "numeric"
                });
            }
            
            escapeHtml(text) {
                if (!text) return "";
                const div = document.createElement("div");
                div.textContent = text;
                return div.innerHTML;
            }
        }
        
        // Global functions
        function searchCustomers() {
            const searchTerm = document.getElementById("search").value;
            if (window.customerManager) {
                window.customerManager.filterCustomers(searchTerm);
            }
        }
        
        function refreshCustomers() {
            if (window.customerManager) {
                window.customerManager.loadCustomers();
            }
        }
        
        function addNewCustomer() {
            alert("ฟังก์ชันเพิ่มลูกค้าใหม่จะพัฒนาในขั้นตอนถัดไป");
        }
        
        function viewCustomerDetail(customerCode) {
            window.location.href = `customer_detail.php?code=${encodeURIComponent(customerCode)}`;
        }
        
        function editCustomer(customerCode) {
            alert("ฟังก์ชันแก้ไขลูกค้าจะพัฒนาในขั้นตอนถัดไป");
        }
        
        function callCustomer(phoneNumber) {
            if (phoneNumber) {
                window.open(`tel:${phoneNumber}`, "_self");
            } else {
                alert("ไม่พบเบอร์โทรศัพท์");
            }
        }
        
        // Initialize when page loads
        document.addEventListener("DOMContentLoaded", function() {
            window.customerManager = new CustomerListManager();
        });
    </script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, '', $additionalJS);
?>