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
                        this.updateStats({});
                        contentEl.innerHTML = this.renderEmptyState("ไม่พบรายการขาย", "ไม่มีรายการขายในระบบ หรือคุณไม่มีสิทธิ์เข้าถึงข้อมูล");
                    }
                } catch (error) {
                    console.error("Error loading sales records:", error);
                    loadingEl.style.display = "none";
                    contentEl.innerHTML = this.renderErrorState("เกิดข้อผิดพลาดในการโหลดข้อมูลรายการขาย");
                }
            }
            
            updateStats(stats) {
                document.getElementById("totalOrders").textContent = stats.total_orders || 0;
                document.getElementById("todayOrders").textContent = stats.today_orders || 0;
                document.getElementById("monthOrders").textContent = stats.month_orders || 0;
                
                const totalSales = stats.total_sales || 0;
                document.getElementById("totalSales").textContent = this.formatCurrency(totalSales);
            }
            
            filterSales(searchTerm) {
                if (!searchTerm.trim()) {
                    this.filteredSales = [...this.salesRecords];
                } else {
                    const term = searchTerm.toLowerCase();
                    this.filteredSales = this.salesRecords.filter(sale => 
                        (sale.CustomerName && sale.CustomerName.toLowerCase().includes(term)) ||
                        (sale.OrderNumber && sale.OrderNumber.toLowerCase().includes(term)) ||
                        (sale.CustomerCode && sale.CustomerCode.toLowerCase().includes(term)) ||
                        (sale.Products && sale.Products.some(p => p.ProductName.toLowerCase().includes(term)))
                    );
                }
                
                const contentEl = document.getElementById("salesRecordsList");
                contentEl.innerHTML = this.renderSalesTable(this.filteredSales);
            }
            
            renderSalesTable(salesRecords) {
                if (!salesRecords || salesRecords.length === 0) {
                    return this.renderEmptyState("ไม่พบรายการขาย", "ไม่มีรายการขายที่ตรงกับเงื่อนไขการค้นหา");
                }
                
                return `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>หมายเลขคำสั่งซื้อ</th>
                                    <th>วันที่สั่งซื้อ</th>
                                    <th>ลูกค้า</th>
                                    <th>สินค้า</th>
                                    <th>มูลค่า</th>
                                    <th>สถานะ</th>
                                    <th>ผู้ขาย</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${salesRecords.map(sale => `
                                    <tr>
                                        <td>
                                            <strong>${this.escapeHtml(sale.OrderNumber || sale.OrderID)}</strong>
                                        </td>
                                        <td>${this.formatDateTime(sale.OrderDate)}</td>
                                        <td>
                                            <strong>${this.escapeHtml(sale.CustomerName || "ไม่ระบุ")}</strong>
                                            <br><small class="text-muted">${sale.CustomerCode}</small>
                                            ${sale.CustomerTel ? `<br><small><i class="fas fa-phone"></i> ${sale.CustomerTel}</small>` : ""}
                                        </td>
                                        <td>
                                            ${sale.Products && sale.Products.length > 0 ? 
                                                sale.Products.map(product => `
                                                    <div class="mb-1">
                                                        <strong>${this.escapeHtml(product.ProductName)}</strong>
                                                        <br><small class="text-muted">จำนวน: ${product.Quantity} | ราคา: ${this.formatCurrency(product.UnitPrice)}</small>
                                                    </div>
                                                `).join("") : "-"
                                            }
                                        </td>
                                        <td>
                                            <strong class="text-success">${this.formatCurrency(sale.TotalAmount)}</strong>
                                        </td>
                                        <td>
                                            <span class="badge ${this.getOrderStatusBadgeClass(sale.OrderStatus)}">${sale.OrderStatus}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">${this.escapeHtml(sale.SalesBy || sale.AssignedSales || "ไม่ระบุ")}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewOrderDetail(\'${sale.OrderID}\')" title="ดูรายละเอียด">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" onclick="viewCustomerDetail(\'${sale.CustomerCode}\')" title="ดูข้อมูลลูกค้า">
                                                    <i class="fas fa-user"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join("")}
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            renderEmptyState(title, message) {
                return `
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">${title}</h5>
                        <p class="text-muted">${message}</p>
                    </div>
                `;
            }
            
            renderErrorState(message) {
                return `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>เกิดข้อผิดพลาด:</strong> ${message}
                        <br><button class="btn btn-sm btn-danger mt-2" onclick="location.reload()">ลองใหม่</button>
                    </div>
                `;
            }
            
            getOrderStatusBadgeClass(status) {
                switch(status) {
                    case "รอดำเนินการ": return "bg-warning";
                    case "กำลังดำเนินการ": return "bg-info";
                    case "เสร็จสิ้น": return "bg-success";
                    case "ยกเลิก": return "bg-danger";
                    default: return "bg-secondary";
                }
            }
            
            formatDateTime(dateString) {
                if (!dateString) return "-";
                const date = new Date(dateString);
                return date.toLocaleDateString("th-TH", {
                    year: "numeric",
                    month: "short",
                    day: "numeric",
                    hour: "2-digit",
                    minute: "2-digit"
                });
            }
            
            formatCurrency(amount) {
                if (!amount) return "0.00";
                return parseFloat(amount).toLocaleString("th-TH", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
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
        function refreshSalesRecords() {
            if (window.salesManager) {
                window.salesManager.loadSalesRecords();
            }
        }
        
        function loadSalesRecords() {
            if (window.salesManager) {
                window.salesManager.loadSalesRecords();
            }
        }
        
        function searchSales() {
            const searchTerm = document.getElementById("search").value;
            if (window.salesManager) {
                window.salesManager.filterSales(searchTerm);
            }
        }
        
        function viewOrderDetail(orderId) {
            window.location.href = `order_detail.php?id=${encodeURIComponent(orderId)}`;
        }
        
        function viewCustomerDetail(customerCode) {
            window.location.href = `customer_detail.php?code=${encodeURIComponent(customerCode)}`;
        }
        
        // Initialize when page loads
        document.addEventListener("DOMContentLoaded", function() {
            window.salesManager = new SalesRecordsManager();
        });
    </script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, '', $additionalJS);
?>