<?php
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/main_layout.php';

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
        รายการและสถิติการขายพร้อมระบบกรองข้อมูลขั้นสูง
    </p>
</div>

<!-- Filter Controls -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0">
            <i class="fas fa-filter"></i> ตัวกรองข้อมูล
        </h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="monthFilter" class="form-label">
                    <i class="fas fa-calendar-alt"></i> เลือกเดือน
                </label>
                <select id="monthFilter" class="form-select">
                    <option value="">กำลังโหลด...</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="productFilter" class="form-label">
                    <i class="fas fa-box"></i> กรองตามสินค้า
                </label>
                <select id="productFilter" class="form-select">
                    <option value="">สินค้าทั้งหมด</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button id="resetFilters" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-undo"></i> รีเซ็ต
                </button>
                <button id="applyFilters" class="btn btn-primary">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Indicator -->
<div id="loading" class="text-center my-4">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">กำลังโหลด...</span>
    </div>
    <p class="mt-2">กำลังโหลดข้อมูลจาก API...</p>
</div>

<!-- Error Message -->
<div id="error-message" class="alert alert-danger d-none">
    <h5><i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด</h5>
    <p id="error-text"></p>
    <button class="btn btn-outline-danger" onclick="loadSalesData()">
        <i class="fas fa-redo"></i> ลองใหม่
    </button>
</div>

<!-- Enhanced KPI Cards -->
<div id="summary-cards" class="row mb-4 d-none">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm kpi-card" data-kpi="total-orders" onclick="highlightKPI('total-orders')">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">คำสั่งซื้อทั้งหมด</h5>
                        <h3 class="text-primary mb-0" id="total-orders">-</h3>
                        <small class="text-muted">รายการ</small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm kpi-card" data-kpi="total-sales" onclick="highlightKPI('total-sales')">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">ยอดขายรวม</h5>
                        <h3 class="text-success mb-0" id="total-sales">-</h3>
                        <small class="text-muted">บาท</small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm kpi-card clickable" data-filter="fertilizer" onclick="filterByProduct('ปุ๋ย')">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">สินค้าปุ๋ย</h5>
                        <h3 class="text-info mb-0" id="fertilizer-count">-</h3>
                        <small class="text-muted">รายการ (คลิกกรอง)</small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-seedling fa-2x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm kpi-card clickable" data-filter="chemical" onclick="filterByProduct('เคมี')">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">สินค้าเคมี</h5>
                        <h3 class="text-warning mb-0" id="chemical-count">-</h3>
                        <small class="text-muted">รายการ (คลิกกรอง)</small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-flask fa-2x text-warning opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Records Table -->
<div id="sales-table-container" class="card shadow-sm d-none">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-list"></i> รายการขาย
            <span class="badge bg-primary ms-2" id="record-count">0</span>
        </h5>
    </div>
    <div class="card-body">
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
                <tbody id="sales-table-body">
                    <!-- Data will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Enhanced JavaScript with filtering and KPI
$dynamicJS = '
<style>
.kpi-card.clickable {
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card.clickable:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}
.kpi-card.active {
    border: 2px solid #007bff !important;
    background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
}
.filter-active {
    background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);
    border: 1px solid #007bff;
}
</style>
<script>
// Global state management
const appState = {
    filters: {
        month: null,
        product: null
    },
    currentData: null,
    activeKPI: null
};

document.addEventListener("DOMContentLoaded", function() {
    console.log("Enhanced dynamic customer list loaded");
    initializeFilters();
    loadSalesData();
    setupEventListeners();
});

// Initialize filters with current month
function initializeFilters() {
    const now = new Date();
    const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    
    // Populate month filter
    const monthFilter = document.getElementById('monthFilter');
    for (let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        const text = date.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
        
        const option = new Option(text, value);
        if (value === currentMonth) {
            option.selected = true;
            appState.filters.month = value;
        }
        monthFilter.appendChild(option);
    }
}

// Setup event listeners
function setupEventListeners() {
    document.getElementById('monthFilter').addEventListener('change', handleFilterChange);
    document.getElementById('productFilter').addEventListener('change', handleFilterChange);
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
}

// Handle filter changes
function handleFilterChange() {
    const monthFilter = document.getElementById('monthFilter');
    const productFilter = document.getElementById('productFilter');
    
    appState.filters.month = monthFilter.value;
    appState.filters.product = productFilter.value;
}

// Apply filters
function applyFilters() {
    loadSalesData();
}

// Reset filters
function resetFilters() {
    const now = new Date();
    const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    
    document.getElementById('monthFilter').value = currentMonth;
    document.getElementById('productFilter').value = '';
    
    appState.filters.month = currentMonth;
    appState.filters.product = null;
    appState.activeKPI = null;
    
    // Remove active states
    document.querySelectorAll('.kpi-card').forEach(card => {
        card.classList.remove('active');
    });
    
    loadSalesData();
}

// Enhanced load sales data with filters
async function loadSalesData() {
    try {
        // Show loading
        document.getElementById("loading").classList.remove("d-none");
        document.getElementById("error-message").classList.add("d-none");
        document.getElementById("summary-cards").classList.add("d-none");
        document.getElementById("sales-table-container").classList.add("d-none");
        
        // Build API URL with filters
        let apiUrl = "../api/sales/sales_records_enhanced.php";
        const params = new URLSearchParams();
        
        if (appState.filters.month) {
            params.append('month', appState.filters.month);
        }
        if (appState.filters.product) {
            params.append('product', appState.filters.product);
        }
        
        if (params.toString()) {
            apiUrl += '?' + params.toString();
        }
        
        // Fetch data from API
        const response = await fetch(apiUrl);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || "เกิดข้อผิดพลาดไม่ทราบสาเหตุ");
        }
        
        // Store current data
        appState.currentData = data.data;
        
        // Hide loading
        document.getElementById("loading").classList.add("d-none");
        
        // Update summary cards with enhanced KPI
        updateEnhancedKPICards(data.data.summary, data.data.sales_records);
        
        // Update product filter options
        updateProductFilter(data.data.sales_records);
        
        // Update sales table
        updateSalesTable(data.data.sales_records);
        
        // Show content
        document.getElementById("summary-cards").classList.remove("d-none");
        document.getElementById("sales-table-container").classList.remove("d-none");
        
        console.log("Data loaded successfully:", data);
        
    } catch (error) {
        console.error("Error loading sales data:", error);
        
        // Hide loading
        document.getElementById("loading").classList.add("d-none");
        
        // Show error
        document.getElementById("error-text").textContent = error.message;
        document.getElementById("error-message").classList.remove("d-none");
    }
}

// Enhanced KPI Cards with product counting
function updateEnhancedKPICards(summary, salesRecords) {
    // Update basic KPIs
    document.getElementById("total-orders").textContent = (summary.total_orders || 0).toLocaleString();
    document.getElementById("total-sales").textContent = (summary.total_sales || 0).toLocaleString();
    
    // Calculate product-specific KPIs
    const productCounts = calculateProductCounts(salesRecords);
    document.getElementById("fertilizer-count").textContent = productCounts.fertilizer.toLocaleString();
    document.getElementById("chemical-count").textContent = productCounts.chemical.toLocaleString();
}

// Calculate product counts from sales records
function calculateProductCounts(salesRecords) {
    const counts = {
        fertilizer: 0,
        chemical: 0,
        other: 0
    };
    
    salesRecords.forEach(record => {
        if (record.Products && record.Products.length > 0) {
            record.Products.forEach(product => {
                const productName = product.ProductName.toLowerCase();
                if (productName.includes('ปุ๋ย')) {
                    counts.fertilizer++;
                } else if (productName.includes('เคมี')) {
                    counts.chemical++;
                } else {
                    counts.other++;
                }
            });
        }
    });
    
    return counts;
}

// Update product filter dropdown
function updateProductFilter(salesRecords) {
    const productFilter = document.getElementById('productFilter');
    const existingOptions = Array.from(productFilter.options).slice(1); // Keep first option
    
    // Remove existing dynamic options
    existingOptions.forEach(option => option.remove());
    
    // Collect unique products
    const uniqueProducts = new Set();
    salesRecords.forEach(record => {
        if (record.Products && record.Products.length > 0) {
            record.Products.forEach(product => {
                uniqueProducts.add(product.ProductName);
            });
        }
    });
    
    // Add product categories
    const categories = [
        { value: 'ปุ๋ย', text: 'สินค้าปุ๋ย' },
        { value: 'เคมี', text: 'สินค้าเคมี' }
    ];
    
    categories.forEach(category => {
        const option = new Option(category.text, category.value);
        productFilter.appendChild(option);
    });
    
    // Add individual products
    Array.from(uniqueProducts).sort().forEach(product => {
        const option = new Option(product, product);
        productFilter.appendChild(option);
    });
}

// Filter by product (KPI card click)
function filterByProduct(productType) {
    document.getElementById('productFilter').value = productType;
    appState.filters.product = productType;
    
    // Highlight active KPI
    document.querySelectorAll('.kpi-card').forEach(card => {
        card.classList.remove('active');
    });
    
    const activeCard = document.querySelector(`[data-filter="${productType === 'ปุ๋ย' ? 'fertilizer' : 'chemical'}"]`);
    if (activeCard) {
        activeCard.classList.add('active');
    }
    
    appState.activeKPI = productType;
    loadSalesData();
}

// Highlight KPI (for non-filterable KPIs)
function highlightKPI(kpiType) {
    document.querySelectorAll('.kpi-card').forEach(card => {
        card.classList.remove('active');
    });
    
    const card = document.querySelector(`[data-kpi="${kpiType}"]`);
    if (card) {
        card.classList.add('active');
    }
    
    appState.activeKPI = kpiType;
}

function updateSalesTable(salesRecords) {
    const tbody = document.getElementById("sales-table-body");
    const recordCount = document.getElementById("record-count");
    
    tbody.innerHTML = "";
    recordCount.textContent = salesRecords.length;
    
    salesRecords.forEach(record => {
        const row = createSalesRow(record);
        tbody.appendChild(row);
    });
}

function createSalesRow(record) {
    const row = document.createElement("tr");
    
    // Format date
    const orderDate = new Date(record.OrderDate);
    const formattedDate = orderDate.toLocaleDateString("th-TH", {
        day: "numeric",
        month: "short",
        year: "numeric"
    });
    
    // Format products
    let productsHtml = "";
    if (record.Products && record.Products.length > 0) {
        const product = record.Products[0]; // Show first product
        productsHtml = `
            <strong>${escapeHtml(product.ProductName)}</strong>
            <br><small class="text-muted">จำนวน: ${product.Quantity} | ราคา: ${parseFloat(product.UnitPrice).toLocaleString()} ฿</small>
        `;
    } else {
        productsHtml = "<em>ไม่ระบุสินค้า</em>";
    }
    
    // Format amount
    const amount = parseFloat(record.TotalAmount || 0);
    
    row.innerHTML = `
        <td><strong>${escapeHtml(record.OrderNumber)}</strong></td>
        <td>${formattedDate}</td>
        <td>
            <strong>${escapeHtml(record.CustomerName || "-")}</strong>
            <br><small class="text-muted">${escapeHtml(record.CustomerCode || "-")}</small>
            <br><small><i class="fas fa-phone"></i> ${escapeHtml(record.CustomerTel || "-")}</small>
        </td>
        <td>${productsHtml}</td>
        <td><strong class="text-success">${amount.toLocaleString()} ฿</strong></td>
        <td><span class="badge bg-success">${escapeHtml(record.OrderStatus)}</span></td>
        <td><span class="badge bg-info">${escapeHtml(record.SalesBy || "-")}</span></td>
        <td>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" title="ดูรายละเอียด" onclick="viewOrderDetail(${record.OrderID})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-outline-success" title="ดูข้อมูลลูกค้า" onclick="viewCustomer(\'${record.CustomerCode}\')">
                    <i class="fas fa-user"></i>
                </button>
                <button class="btn btn-outline-info" title="โทรหาลูกค้า" onclick="callCustomer(\'${record.CustomerTel}\')">
                    <i class="fas fa-phone"></i>
                </button>
            </div>
        </td>
    `;
    
    return row;
}

function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
}

// Enhanced management functions
function viewOrderDetail(orderId) {
    // Create modal or new page for order details
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">รายละเอียดคำสั่งซื้อ #${orderId}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                        <p class="mt-2">กำลังโหลดรายละเอียด...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Load order details
    loadOrderDetails(orderId, modal.querySelector('.modal-body'));
    
    // Clean up on close
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

function viewCustomer(customerCode) {
    // Open customer details in new tab
    const customerUrl = `customer_detail.php?code=${encodeURIComponent(customerCode)}`;
    
    // Check if customer detail page exists, otherwise show info modal
    fetch(customerUrl, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                window.open(customerUrl, '_blank');
            } else {
                showCustomerInfoModal(customerCode);
            }
        })
        .catch(() => {
            showCustomerInfoModal(customerCode);
        });
}

function callCustomer(phone) {
    if (phone && phone !== "-" && phone.trim() !== "") {
        // Clean phone number
        const cleanPhone = phone.replace(/[^0-9+]/g, '');
        
        if (confirm(\`โทรหา \${phone} หรือไม่?\`)) {
            // Try to use tel: protocol
            window.location.href = \`tel:\${cleanPhone}\`;
            
            // Also log the call action
            logCallAction(phone);
        }
    } else {
        showAlert("ไม่มีหมายเลขโทรศัพท์", "warning");
    }
}

// Helper function to load order details
async function loadOrderDetails(orderId, container) {
    try {
        const response = await fetch(\`../api/sales/order_detail.php?id=\${orderId}\`);
        const data = await response.json();
        
        if (data.success) {
            container.innerHTML = formatOrderDetails(data.data);
        } else {
            container.innerHTML = \`<div class="alert alert-warning">ไม่พบข้อมูลคำสั่งซื้อ</div>\`;
        }
    } catch (error) {
        container.innerHTML = \`<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>\`;
    }
}

// Helper function to show customer info modal
function showCustomerInfoModal(customerCode) {
    const customerData = findCustomerInCurrentData(customerCode);
    
    if (customerData) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = \`
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">ข้อมูลลูกค้า</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table">
                            <tr><th>รหัสลูกค้า:</th><td>\${customerData.CustomerCode}</td></tr>
                            <tr><th>ชื่อลูกค้า:</th><td>\${customerData.CustomerName}</td></tr>
                            <tr><th>เบอร์โทร:</th><td>\${customerData.CustomerTel || '-'}</td></tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                </div>
            </div>
        \`;
        
        document.body.appendChild(modal);
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    } else {
        showAlert(\`ไม่พบข้อมูลลูกค้า: \${customerCode}\`, "warning");
    }
}

// Helper functions
function findCustomerInCurrentData(customerCode) {
    if (!appState.currentData || !appState.currentData.sales_records) return null;
    
    const record = appState.currentData.sales_records.find(r => r.CustomerCode === customerCode);
    return record || null;
}

function formatOrderDetails(orderData) {
    return \`
        <table class="table">
            <tr><th>หมายเลขคำสั่งซื้อ:</th><td>\${orderData.OrderNumber || '-'}</td></tr>
            <tr><th>วันที่สั่งซื้อ:</th><td>\${new Date(orderData.OrderDate).toLocaleDateString('th-TH')}</td></tr>
            <tr><th>ลูกค้า:</th><td>\${orderData.CustomerName || '-'}</td></tr>
            <tr><th>มูลค่ารวม:</th><td>\${parseFloat(orderData.TotalAmount || 0).toLocaleString()} ฿</td></tr>
            <tr><th>สถานะ:</th><td><span class="badge bg-success">\${orderData.OrderStatus || 'เสร็จสิ้น'}</span></td></tr>
        </table>
    \`;
}

function logCallAction(phone) {
    console.log(\`Call logged: \${phone} at \${new Date().toISOString()}\`);
    // Here you could send the call log to your API
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = \`alert alert-\${type} alert-dismissible fade show position-fixed\`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = \`
        \${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    \`;
    
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (document.body.contains(alertDiv)) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, '', $dynamicJS);
?>