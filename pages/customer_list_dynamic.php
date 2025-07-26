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
        <div class="card border-0 shadow-sm kpi-card">
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
        <div class="card border-0 shadow-sm kpi-card">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">ยอดออเดอร์</h5>
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
        <div class="card border-0 shadow-sm kpi-card">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">ยอดจำนวนชิ้น (FER)</h5>
                        <h3 class="text-info mb-0" id="fertilizer-count">-</h3>
                        <small class="text-muted">ชิ้น</small>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-seedling fa-2x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm kpi-card">
            <div class="card-body text-center">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title text-muted">ยอดจำนวนชิ้น (BIO)</h5>
                        <h3 class="text-warning mb-0" id="bio-count">-</h3>
                        <small class="text-muted">ชิ้น</small>
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

// Fixed JavaScript with proper escaping
$dynamicJS = "
<style>
.kpi-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}
.filter-active {
    background: linear-gradient(135deg, #e3f2fd 0%, #f8f9ff 100%);
    border: 1px solid #007bff;
}
</style>
<script>
// Global state management
var appState = {
    filters: {
        month: null,
        product: null
    },
    currentData: null
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Enhanced dynamic customer list loaded');
    initializeFilters();
    loadSalesData();
    setupEventListeners();
});

// Initialize filters with current month
function initializeFilters() {
    var now = new Date();
    var currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    
    // Populate month filter
    var monthFilter = document.getElementById('monthFilter');
    monthFilter.innerHTML = '';
    
    for (var i = 0; i < 12; i++) {
        var date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        var value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        var text = date.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' });
        
        var option = document.createElement('option');
        option.value = value;
        option.textContent = text;
        
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
    var monthFilter = document.getElementById('monthFilter');
    var productFilter = document.getElementById('productFilter');
    
    appState.filters.month = monthFilter.value;
    appState.filters.product = productFilter.value;
}

// Apply filters
function applyFilters() {
    loadSalesData();
}

// Reset filters
function resetFilters() {
    var now = new Date();
    var currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    
    document.getElementById('monthFilter').value = currentMonth;
    document.getElementById('productFilter').value = '';
    
    appState.filters.month = currentMonth;
    appState.filters.product = null;
    
    loadSalesData();
}

// Enhanced load sales data with filters
function loadSalesData() {
    // Show loading
    document.getElementById('loading').classList.remove('d-none');
    document.getElementById('error-message').classList.add('d-none');
    document.getElementById('summary-cards').classList.add('d-none');
    document.getElementById('sales-table-container').classList.add('d-none');
    
    // Build API URL with filters
    var apiUrl = '../api/sales/sales_records_enhanced.php';
    var params = [];
    
    if (appState.filters.month) {
        params.push('month=' + encodeURIComponent(appState.filters.month));
    }
    if (appState.filters.product) {
        params.push('product=' + encodeURIComponent(appState.filters.product));
    }
    
    if (params.length > 0) {
        apiUrl += '?' + params.join('&');
    }
    
    // Fetch data from API
    fetch(apiUrl)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (!data.success) {
                throw new Error(data.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ');
            }
            
            // Store current data
            appState.currentData = data.data;
            
            // Hide loading
            document.getElementById('loading').classList.add('d-none');
            
            // Update summary cards with enhanced KPI
            updateEnhancedKPICards(data.data.summary, data.data.sales_records, data.data.product_stats);
            
            // Update product filter options
            updateProductFilter(data.data.available_products || []);
            
            // Update sales table
            updateSalesTable(data.data.sales_records);
            
            // Show content
            document.getElementById('summary-cards').classList.remove('d-none');
            document.getElementById('sales-table-container').classList.remove('d-none');
            
            console.log('Data loaded successfully:', data);
        })
        .catch(function(error) {
            console.error('Error loading sales data:', error);
            
            // Hide loading
            document.getElementById('loading').classList.add('d-none');
            
            // Show error
            document.getElementById('error-text').textContent = error.message;
            document.getElementById('error-message').classList.remove('d-none');
        });
}

// Enhanced KPI Cards with product stats from API
function updateEnhancedKPICards(summary, salesRecords, productStats) {
    // Update KPIs from product stats (calculated from filtered data)
    document.getElementById('total-sales').textContent = (productStats.total_sales_amount || 0).toLocaleString();
    document.getElementById('total-orders').textContent = (productStats.total_orders || 0).toLocaleString();
    document.getElementById('fertilizer-count').textContent = (productStats.fertilizer_count || 0).toLocaleString();
    document.getElementById('bio-count').textContent = (productStats.bio_count || 0).toLocaleString();
}


// Update product filter dropdown from Products table
function updateProductFilter(availableProducts) {
    var productFilter = document.getElementById('productFilter');
    var existingOptions = productFilter.querySelectorAll('option');
    
    // Remove existing dynamic options (keep first option)
    for (var i = existingOptions.length - 1; i > 0; i--) {
        existingOptions[i].remove();
    }
    
    // Add product categories first
    var categories = [
        { value: 'FER', text: 'สินค้าปุ๋ย (FER)' },
        { value: 'BIO', text: 'ชีวิภัณฑ์ (BIO)' }
    ];
    
    for (var i = 0; i < categories.length; i++) {
        var option = document.createElement('option');
        option.value = categories[i].value;
        option.textContent = categories[i].text;
        productFilter.appendChild(option);
    }
    
    // Add separator
    var separator = document.createElement('option');
    separator.disabled = true;
    separator.textContent = '--- สินค้าเฉพาะ ---';
    productFilter.appendChild(separator);
    
    // Add individual products from database
    for (var i = 0; i < availableProducts.length; i++) {
        var product = availableProducts[i];
        var option = document.createElement('option');
        option.value = product.product_code;
        option.textContent = product.product_code + ' - ' + product.product_name;
        productFilter.appendChild(option);
    }
}


function updateSalesTable(salesRecords) {
    var tbody = document.getElementById('sales-table-body');
    var recordCount = document.getElementById('record-count');
    
    tbody.innerHTML = '';
    recordCount.textContent = salesRecords.length;
    
    for (var i = 0; i < salesRecords.length; i++) {
        var record = salesRecords[i];
        var row = createSalesRow(record);
        tbody.appendChild(row);
    }
}

function createSalesRow(record) {
    var row = document.createElement('tr');
    
    // Format date
    var orderDate = new Date(record.OrderDate);
    var formattedDate = orderDate.toLocaleDateString('th-TH', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
    
    // Format products with product code
    var productsHtml = '';
    if (record.Products && record.Products.length > 0) {
        var product = record.Products[0]; // Show first product
        var productCode = product.ProductCode ? '<span class=\"badge bg-secondary me-1\">' + escapeHtml(product.ProductCode) + '</span>' : '';
        productsHtml = productCode + '<strong>' + escapeHtml(product.ProductName) + '</strong>' +
                      '<br><small class=\"text-muted\">จำนวน: ' + product.Quantity + 
                      ' | ราคา: ' + parseFloat(product.UnitPrice).toLocaleString() + ' ฿</small>';
    } else {
        productsHtml = '<em>ไม่ระบุสินค้า</em>';
    }
    
    // Format amount
    var amount = parseFloat(record.TotalAmount || 0);
    
    row.innerHTML = 
        '<td><strong>' + escapeHtml(record.OrderNumber) + '</strong></td>' +
        '<td>' + formattedDate + '</td>' +
        '<td>' +
        '<strong>' + escapeHtml(record.CustomerName || '-') + '</strong>' +
        '<br><small class=\"text-muted\">' + escapeHtml(record.CustomerCode || '-') + '</small>' +
        '<br><small><i class=\"fas fa-phone\"></i> ' + escapeHtml(record.CustomerTel || '-') + '</small>' +
        '</td>' +
        '<td>' + productsHtml + '</td>' +
        '<td><strong class=\"text-success\">' + amount.toLocaleString() + ' ฿</strong></td>' +
        '<td><span class=\"badge bg-success\">' + escapeHtml(record.OrderStatus) + '</span></td>' +
        '<td><span class=\"badge bg-info\">' + escapeHtml(record.SalesBy || '-') + '</span></td>' +
        '<td>' +
        '<div class=\"btn-group btn-group-sm\">' +
        '<button class=\"btn btn-outline-primary\" title=\"ดูรายละเอียด\" onclick=\"viewOrderDetail(' + record.OrderID + ')\">' +
        '<i class=\"fas fa-eye\"></i>' +
        '</button>' +
        '<button class=\"btn btn-outline-success\" title=\"ดูข้อมูลลูกค้า\" onclick=\"viewCustomer(\'' + record.CustomerCode + '\')\">' +
        '<i class=\"fas fa-user\"></i>' +
        '</button>' +
        '<button class=\"btn btn-outline-info\" title=\"โทรหาลูกค้า\" onclick=\"callCustomer(\'' + (record.CustomerTel || '') + '\')\">' +
        '<i class=\"fas fa-phone\"></i>' +
        '</button>' +
        '</div>' +
        '</td>';
    
    return row;
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Enhanced management functions
function viewOrderDetail(orderId) {
    alert('ดูรายละเอียด Order ID: ' + orderId + '\\n(ฟีเจอร์นี้จะเปิด modal ในเวอร์ชั่นจริง)');
}

function viewCustomer(customerCode) {
    alert('ดูข้อมูลลูกค้า: ' + customerCode + '\\n(ฟีเจอร์นี้จะเปิดหน้าใหม่ในเวอร์ชั่นจริง)');
}

function callCustomer(phone) {
    if (phone && phone !== '-' && phone.trim() !== '') {
        // Clean phone number
        var cleanPhone = phone.replace(/[^0-9+]/g, '');
        
        if (confirm('โทรหา ' + phone + ' หรือไม่?')) {
            // Try to use tel: protocol
            window.location.href = 'tel:' + cleanPhone;
        }
    } else {
        alert('ไม่มีหมายเลขโทรศัพท์');
    }
}
</script>
";

// Render the page
echo renderMainLayout($pageTitle, $content, '', $dynamicJS);
?>