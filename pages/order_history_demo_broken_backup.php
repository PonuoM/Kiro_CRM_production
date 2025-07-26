<?php
/**
 * Order History Demo Page
 * Demonstrates integration of order history and sales history in customer detail
 */

require_once '../includes/permissions.php';
require_once '../includes/main_layout.php';

// Check login and permission
Permissions::requireLogin();
Permissions::requirePermission('order_history');

$pageTitle = "ประวัติคำสั่งซื้อ";

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for main_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// Get customer code from URL parameter, use sample data if none provided
$customerCode = $_GET['customer'] ?? 'CUS' . date('Ymd') . '001';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-shopping-cart"></i>
        ประวัติคำสั่งซื้อ
    </h1>
    <p class="page-description">
        ดูประวัติการสั่งซื้อและพนักงานขายของลูกค้า: <?php echo htmlspecialchars($customerCode); ?>
    </p>
</div>

<!-- Customer Info Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลลูกค้า</h5>
    </div>
    <div class="card-body" id="customerInfo">
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i> กำลังโหลดข้อมูล...
        </div>
    </div>
</div>

<!-- Tabs for Order History and Sales History -->
<ul class="nav nav-tabs mb-4" id="historyTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
            <i class="fas fa-shopping-cart"></i> ประวัติคำสั่งซื้อ
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">
            <i class="fas fa-user-tie"></i> ประวัติพนักงานขาย
        </button>
    </li>
</ul>

<div class="tab-content" id="historyTabContent">
    <!-- Order History Tab -->
    <div class="tab-pane fade show active" id="orders" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> ประวัติคำสั่งซื้อ</h5>
            </div>
            <div class="card-body">
                <div id="orderHistory">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลดประวัติคำสั่งซื้อ...
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales History Tab -->
    <div class="tab-pane fade" id="sales" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-tie"></i> ประวัติพนักงานขาย</h5>
            </div>
            <div class="card-body">
                <div id="salesHistory">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลดประวัติพนักงานขาย...
                    </div>
                </div>
            </div>
        </div>
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

// Layout will get user info from session directly

// Additional CSS
$additionalCSS = '
    <style>
        .badge-active {
            background-color: #28a745;
        }
        .badge-inactive {
            background-color: #6c757d;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            padding: 1rem;
        }
    </style>
';

// Additional JavaScript
$additionalJS = '
    <script>
        const customerCode = "' . htmlspecialchars($customerCode) . '";
        
        class OrderHistoryManager {
            constructor() {
                this.customerCode = customerCode;
                this.customerData = null;
                this.orderHistory = [];
                this.salesHistory = [];
            }
            
            async loadCustomerInfo() {
                try {
                    const response = await fetch(`../api/customers/detail.php?code=${this.customerCode}`);
                    const data = await response.json();
                    
                    if (data.status === "success" && data.data) {
                        this.customerData = data.data.customer;
                        this.orderHistory = data.data.orders || [];
                        this.updateCustomerDisplay();
                    } else {
                        document.getElementById("customerInfo").innerHTML = `
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลลูกค้า: ${this.customerCode}
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error("Error loading customer info:", error);
                    document.getElementById("customerInfo").innerHTML = `
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-times-circle"></i> เกิดข้อผิดพลาดในการโหลดข้อมูล
                        </div>
                    `;
                }
            }
            
            updateCustomerDisplay() {
                if (!this.customerData) return;
                
                const customer = this.customerData;
                document.getElementById("customerInfo").innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>รหัสลูกค้า:</strong> <span class="badge bg-primary">${customer.CustomerCode}</span></p>
                            <p><strong>ชื่อ:</strong> ${this.escapeHtml(customer.CustomerName)}</p>
                            <p><strong>เบอร์โทร:</strong> <i class="fas fa-phone text-primary"></i> ${this.escapeHtml(customer.CustomerTel || "ไม่ระบุ")}</p>
                            <p><strong>ที่อยู่:</strong> ${this.escapeHtml(customer.CustomerAddress || "ไม่ระบุ")}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>สถานะลูกค้า:</strong> <span class="badge ${this.getCustomerStatusBadgeClass(customer.CustomerStatus)}">${this.escapeHtml(customer.CustomerStatus || "ไม่ระบุ")}</span></p>
                            <p><strong>สถานะตะกร้า:</strong> <span class="badge ${this.getCartStatusBadgeClass(customer.CartStatus)}">${this.escapeHtml(customer.CartStatus || "ไม่ระบุ")}</span></p>
                            <p><strong>พนักงานขาย:</strong> ${customer.Sales ? `<span class="badge bg-info">${this.escapeHtml(customer.Sales)}</span>` : `<span class="text-muted">ยังไม่ได้มอบหมาย</span>`}</p>
                            <p><strong>วันที่สร้าง:</strong> ${this.formatDate(customer.CreatedDate)}</p>
                        </div>
                    </div>
                `;
            }
            
            getCustomerStatusBadgeClass(status) {
                switch(status) {
                    case "ลูกค้าใหม่": return "bg-success";
                    case "ลูกค้าติดตาม": return "bg-warning";
                    case "ลูกค้าเก่า": return "bg-info";
                    default: return "bg-secondary";
                }
            }
            
            getCartStatusBadgeClass(status) {
                switch(status) {
                    case "ตะกร้าแจก": return "bg-danger";
                    case "ตะกร้ารอ": return "bg-warning";
                    case "กำลังดูแล": return "bg-primary";
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
            
            async loadOrderHistory() {
                const container = document.getElementById("orderHistory");
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลดประวัติคำสั่งซื้อ...
                    </div>
                `;
                
                try {
                    const response = await fetch(`../api/orders/history.php?customer_code=${this.customerCode}`);
                    const data = await response.json();
                    
                    if (data.status === "success") {
                        const orders = data.data || [];
                        this.renderOrderHistory(orders);
                    } else {
                        this.renderOrderError(data.message || "เกิดข้อผิดพลาดในการโหลดข้อมูล");
                    }
                } catch (error) {
                    console.error("Error loading order history:", error);
                    this.renderOrderError("เกิดข้อผิดพลาดในการเชื่อมต่อกับระบบ");
                }
            }
            
            renderOrderHistory(orders) {
                const container = document.getElementById("orderHistory");
                
                if (!orders || orders.length === 0) {
                    container.innerHTML = this.renderOrderEmptyState();
                    return;
                }
                
                const ordersHtml = orders.map(order => this.renderOrderItem(order)).join("");
                
                // Calculate summary
                const totalOrders = orders.length;
                const totalSales = orders.reduce((sum, order) => {
                    const price = parseFloat(order.TotalAmount || order.Price || 0);
                    return sum + price;
                }, 0);
                
                container.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">พบ ${totalOrders} รายการ</h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-sm btn-primary" onclick="orderHistoryManager.loadOrderHistory()">
                                <i class="fas fa-sync-alt"></i> รีเฟรช
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>เลขที่เอกสาร</th>
                                    <th>วันที่</th>
                                    <th>สินค้า</th>
                                    <th>จำนวน</th>
                                    <th>ยอดรวม</th>
                                    <th>วิธีชำระ</th>
                                    <th>ผู้บันทึก</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${ordersHtml}
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card bg-light shadow-sm">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-muted">จำนวนคำสั่งซื้อ</h5>
                                    <h3 class="text-primary mb-0">${totalOrders}</h3>
                                    <small class="text-muted">รายการ</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light shadow-sm">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-muted">ยอดขายรวม</h5>
                                    <h3 class="text-success mb-0">${totalSales.toLocaleString()}</h3>
                                    <small class="text-muted">บาท</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            renderOrderItem(order) {
                return `
                    <tr>
                        <td><span class="badge bg-primary">${this.escapeHtml(order.DocumentNo || order.OrderNo || "ไม่ระบุ")}</span></td>
                        <td>
                            <strong>${this.formatDate(order.DocumentDate || order.OrderDate)}</strong><br>
                            <small class="text-muted">${this.formatTime(order.DocumentDate || order.OrderDate)}</small>
                        </td>
                        <td>${this.escapeHtml(order.ProductName || order.Products || "ไม่ระบุ")}</td>
                        <td>${order.Quantity || "-"}</td>
                        <td class="text-end">
                            <strong>${parseFloat(order.TotalAmount || order.Price || 0).toLocaleString()}</strong>
                            <small class="text-muted d-block">บาท</small>
                        </td>
                        <td>
                            <span class="badge bg-info">${this.escapeHtml(order.PaymentMethod || "ไม่ระบุ")}</span>
                        </td>
                        <td>
                            <small class="text-muted">${this.escapeHtml(order.CreatedBy || order.OrderBy || "-")}</small>
                        </td>
                    </tr>
                `;
            }
            
            renderOrderEmptyState() {
                return `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">ยังไม่มีประวัติคำสั่งซื้อ</h5>
                        <p class="text-muted">ลูกค้ารายนี้ยังไม่มีการสั่งซื้อสินค้าในระบบ</p>
                    </div>
                `;
            }
            
            renderOrderError(message) {
                document.getElementById("orderHistory").innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>เกิดข้อผิดพลาด:</strong> ${message}
                        <br><button class="btn btn-sm btn-danger mt-2" onclick="orderHistoryManager.loadOrderHistory()">ลองใหม่</button>
                    </div>
                `;
            }
            
            formatTime(dateString) {
                if (!dateString) return "-";
                const date = new Date(dateString);
                return date.toLocaleTimeString("th-TH", {
                    hour: "2-digit",
                    minute: "2-digit"
                });
            }
        }
        
        // Create global instance
        const orderHistoryManager = new OrderHistoryManager();
        
        // Show real data for order history
        function loadOrderHistory() {
            orderHistoryManager.loadOrderHistory();
        }
        
        // Show sales assignment history (simplified implementation)
        function loadSalesHistory() {
            const container = document.getElementById("salesHistory");
            
            if (!orderHistoryManager.customerData) {
                container.innerHTML = `
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i> กรุณาโหลดข้อมูลลูกค้าก่อน
                    </div>
                `;
                return;
            }
            
            const customer = orderHistoryManager.customerData;
            
            // Since we don't have a sales assignment history table, 
            // we'll show current assignment and create a simplified history
            let contentHTML = "";
            
            if (customer.Sales) {
                contentHTML += `
                    <div class="alert alert-info border-start border-info border-4">
                        <h6><i class="fas fa-user-check"></i> พนักงานขายปัจจุบัน</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>ชื่อ:</strong> <span class="badge bg-info">${orderHistoryManager.escapeHtml(customer.Sales)}</span></p>
                                <p class="mb-1"><strong>วันที่มอบหมาย:</strong> ${orderHistoryManager.formatDate(customer.ModifiedDate || customer.CreatedDate)}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>สถานะ:</strong> <span class="badge bg-success">กำลังดูแล</span></p>
                                <p class="mb-0"><strong>รหัสลูกค้า:</strong> ${customer.CustomerCode}</p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentHTML += `
                    <div class="alert alert-warning border-start border-warning border-4">
                        <h6><i class="fas fa-user-times"></i> ยังไม่ได้มอบหมายพนักงานขาย</h6>
                        <p class="mb-0">ลูกค้ารายนี้ยังไม่ได้รับการมอบหมายให้พนักงานขายดูแล</p>
                    </div>
                `;
            }
            
            contentHTML += `
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history"></i> ประวัติการมอบหมาย</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>พนักงานขาย</th>
                                        <th>วันที่เริ่ม</th>
                                        <th>วันที่อัปเดต</th>
                                        <th>สถานะ</th>
                                        <th>หมายเหตุ</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            if (customer.Sales) {
                const assignmentDate = orderHistoryManager.formatDate(customer.CreatedDate);
                const lastUpdate = orderHistoryManager.formatDate(customer.ModifiedDate);
                
                contentHTML += `
                    <tr>
                        <td>
                            <strong>${orderHistoryManager.escapeHtml(customer.Sales)}</strong>
                        </td>
                        <td>${assignmentDate}</td>
                        <td>${lastUpdate}</td>
                        <td><span class="badge bg-success">กำลังดูแล</span></td>
                        <td><small class="text-muted">มอบหมายปัจจุบัน</small></td>
                    </tr>
                `;
            } else {
                contentHTML += `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="fas fa-inbox"></i> ยังไม่มีประวัติการมอบหมาย
                        </td>
                    </tr>
                `;
            }
            
            contentHTML += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // Add customer creation info
            contentHTML += `
                <div class="mt-3 p-3 bg-light rounded">
                    <h6><i class="fas fa-info-circle"></i> ข้อมูลเพิ่มเติม</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small><strong>วันที่สร้างลูกค้า:</strong> ${orderHistoryManager.formatDate(customer.CreatedDate)}</small>
                        </div>
                        <div class="col-md-6">
                            <small><strong>อัปเดตล่าสุด:</strong> ${orderHistoryManager.formatDate(customer.ModifiedDate)}</small>
                        </div>
                    </div>
                </div>
            `;
            
            contentHTML += `</div></div>`;
            
            container.innerHTML = contentHTML;
        }
        
        // Load data when page loads
        document.addEventListener("DOMContentLoaded", function() {
            orderHistoryManager.loadCustomerInfo();
            orderHistoryManager.loadOrderHistory();
        });
        
        // Load sales history when tab is clicked
        document.getElementById("sales-tab").addEventListener("click", function() {
            if (document.getElementById("salesHistory").innerHTML.includes("กำลังโหลด")) {
                loadSalesHistory();
            }
        });
    </script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>