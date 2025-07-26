<?php
/**
 * Call History Demo Page
 * Demonstrates the call history component functionality
 */

require_once '../includes/permissions.php';
require_once '../includes/main_layout.php';

// Check login and permission
Permissions::requireLogin();
Permissions::requirePermission('call_history');

$pageTitle = "ประวัติการโทร";

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for main_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// For demo purposes, we'll use a test customer code
$testCustomerCode = $_GET['customer'] ?? 'CUS20240115103012345';

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-phone"></i>
        ประวัติการโทร
    </h1>
    <p class="page-description">
        ดูประวัติและจัดการบันทึกการโทรเข้าหาลูกค้า
    </p>
</div>

<!-- Demo Info -->
<div class="alert alert-info border-start border-primary border-4 mb-4">
    <strong><i class="fas fa-info-circle"></i> หมายเหตุ:</strong> นี่เป็นหน้าทดสอบสำหรับ Call History Component ซึ่งจะถูกนำไปใช้ในหน้า Customer Detail ในภายหลัง
</div>

<!-- Customer Info -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-user"></i> ข้อมูลลูกค้า
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>รหัสลูกค้า:</strong><br>
                <span class="badge bg-primary"><?php echo htmlspecialchars($testCustomerCode); ?></span>
            </div>
            <div class="col-md-3">
                <strong>ชื่อลูกค้า:</strong><br>
                ลูกค้าทดสอบ
            </div>
            <div class="col-md-3">
                <strong>เบอร์โทร:</strong><br>
                <i class="fas fa-phone"></i> 081-234-5678
            </div>
            <div class="col-md-3">
                <strong>สถานะ:</strong><br>
                <span class="badge bg-success">ลูกค้าใหม่</span>
            </div>
        </div>
    </div>
</div>

<!-- Call History Component Container -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-history"></i> ประวัติการโทร
        </h5>
    </div>
    <div class="card-body">
        <div id="call-history-container"></div>
    </div>
</div>
        
<!-- Demo Controls -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-tools"></i> การทดสอบ
        </h5>
    </div>
    <div class="card-body">
        <p>คุณสามารถทดสอบฟังก์ชันต่างๆ ได้:</p>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-primary" onclick="testAddCallLog()">
                <i class="fas fa-plus"></i> เพิ่มบันทึกการโทรทดสอบ
            </button>
            <button class="btn btn-secondary" onclick="testRefresh()">
                <i class="fas fa-sync-alt"></i> รีเฟรชข้อมูล
            </button>
            <button class="btn btn-secondary" onclick="testChangeCustomer()">
                <i class="fas fa-user-edit"></i> เปลี่ยนลูกค้า
            </button>
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
$additionalCSS = '';

// Additional JavaScript
$additionalJS = '
    <script>
        class CallHistoryManager {
            constructor() {
                this.customerCode = "' . $testCustomerCode . '";
                this.currentCustomer = null;
                this.callHistory = [];
                this.init();
            }
            
            init() {
                this.loadCustomerInfo();
                this.loadCallHistory();
            }
            
            async loadCustomerInfo() {
                try {
                    const response = await fetch(`../api/customers/get.php?customer_code=${this.customerCode}`);
                    const data = await response.json();
                    
                    if (data.status === "success" && data.data) {
                        this.currentCustomer = data.data;
                        this.updateCustomerDisplay();
                    }
                } catch (error) {
                    console.error("Error loading customer info:", error);
                }
            }
            
            updateCustomerDisplay() {
                if (!this.currentCustomer) return;
                
                // Update customer info in the card
                const customerInfoCard = document.querySelector(".card-body .row");
                if (customerInfoCard) {
                    customerInfoCard.innerHTML = `
                        <div class="col-md-3">
                            <strong>รหัสลูกค้า:</strong><br>
                            <span class="badge bg-primary">${this.currentCustomer.CustomerCode}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>ชื่อลูกค้า:</strong><br>
                            ${this.escapeHtml(this.currentCustomer.CustomerName)}
                        </div>
                        <div class="col-md-3">
                            <strong>เบอร์โทร:</strong><br>
                            <i class="fas fa-phone"></i> ${this.escapeHtml(this.currentCustomer.CustomerTel || "ไม่ระบุ")}
                        </div>
                        <div class="col-md-3">
                            <strong>สถานะ:</strong><br>
                            <span class="badge ${this.getStatusBadgeClass(this.currentCustomer.CustomerStatus)}">${this.escapeHtml(this.currentCustomer.CustomerStatus || "ไม่ระบุ")}</span>
                        </div>
                    `;
                }
            }
            
            async loadCallHistory() {
                const container = document.getElementById("call-history-container");
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลดประวัติการโทร...
                    </div>
                `;
                
                try {
                    const response = await fetch(`../api/calls/history.php?customer_code=${this.customerCode}`);
                    const data = await response.json();
                    
                    if (data.status === "success") {
                        this.callHistory = data.data || [];
                        this.renderCallHistory();
                    } else {
                        this.renderError(data.message || "เกิดข้อผิดพลาดในการโหลดข้อมูล");
                    }
                } catch (error) {
                    console.error("Error loading call history:", error);
                    this.renderError("เกิดข้อผิดพลาดในการเชื่อมต่อกับระบบ");
                }
            }
            
            renderCallHistory() {
                const container = document.getElementById("call-history-container");
                
                if (!this.callHistory || this.callHistory.length === 0) {
                    container.innerHTML = this.renderEmptyState();
                    return;
                }
                
                const historyHtml = this.callHistory.map(call => this.renderCallItem(call)).join("");
                
                container.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">พบ ${this.callHistory.length} รายการ</h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-sm btn-primary" onclick="callHistoryManager.refreshHistory()">
                                <i class="fas fa-sync-alt"></i> รีเฟรช
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>วันที่/เวลา</th>
                                    <th>สถานะการโทร</th>
                                    <th>สถานะการคุย</th>
                                    <th>ระยะเวลา</th>
                                    <th>หมายเหตุ</th>
                                    <th>ผู้บันทึก</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${historyHtml}
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            renderCallItem(call) {
                return `
                    <tr>
                        <td>
                            <strong>${this.formatDate(call.CallDate)}</strong><br>
                            <small class="text-muted">${this.formatTime(call.CallDate)}</small>
                        </td>
                        <td>
                            <span class="badge ${this.getCallStatusBadgeClass(call.CallStatus)}">
                                ${this.escapeHtml(call.CallStatus || "ไม่ระบุ")}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${this.getTalkStatusBadgeClass(call.TalkStatus)}">
                                ${this.escapeHtml(call.TalkStatus || "ไม่ระบุ")}
                            </span>
                        </td>
                        <td>
                            ${call.CallMinutes ? `${call.CallMinutes} นาที` : "-"}
                        </td>
                        <td>
                            <small>${call.Remarks ? this.escapeHtml(call.Remarks) : "-"}</small>
                        </td>
                        <td>
                            <small class="text-muted">${this.escapeHtml(call.CreatedBy || "-")}</small>
                        </td>
                    </tr>
                `;
            }
            
            renderEmptyState() {
                return `
                    <div class="text-center py-5">
                        <i class="fas fa-phone-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">ยังไม่มีประวัติการโทร</h5>
                        <p class="text-muted">ลูกค้ารายนี้ยังไม่มีบันทึกการโทรในระบบ</p>
                        <button class="btn btn-primary" onclick="callHistoryManager.testAddCallLog()">
                            <i class="fas fa-plus"></i> เพิ่มบันทึกการโทรทดสอบ
                        </button>
                    </div>
                `;
            }
            
            renderError(message) {
                document.getElementById("call-history-container").innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>เกิดข้อผิดพลาด:</strong> ${message}
                        <br><button class="btn btn-sm btn-danger mt-2" onclick="callHistoryManager.loadCallHistory()">ลองใหม่</button>
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
            
            getCallStatusBadgeClass(status) {
                switch(status) {
                    case "ติดต่อได้": return "bg-success";
                    case "ติดต่อไม่ได้": return "bg-danger";
                    default: return "bg-secondary";
                }
            }
            
            getTalkStatusBadgeClass(status) {
                switch(status) {
                    case "คุยจบ": return "bg-success";
                    case "คุยไม่จบ": return "bg-warning";
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
            
            formatTime(dateString) {
                if (!dateString) return "-";
                const date = new Date(dateString);
                return date.toLocaleTimeString("th-TH", {
                    hour: "2-digit",
                    minute: "2-digit"
                });
            }
            
            escapeHtml(text) {
                if (!text) return "";
                const div = document.createElement("div");
                div.textContent = text;
                return div.innerHTML;
            }
            
            refreshHistory() {
                this.loadCallHistory();
            }
            
            async testAddCallLog() {
                const testCallData = {
                    customer_code: this.customerCode,
                    call_date: new Date().toISOString().slice(0, 19).replace("T", " "),
                    call_status: "ติดต่อได้",
                    talk_status: "คุยจบ",
                    call_minutes: "5",
                    remarks: "บันทึกทดสอบจาก Demo"
                };
                
                try {
                    const response = await fetch("../api/calls/log.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify(testCallData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success || data.status === "success") {
                        alert("เพิ่มบันทึกการโทรสำเร็จ");
                        this.refreshHistory();
                    } else {
                        alert("เกิดข้อผิดพลาด: " + (data.message || "ไม่สามารถเพิ่มบันทึกได้"));
                    }
                } catch (error) {
                    console.error("Error:", error);
                    alert("เกิดข้อผิดพลาดในการเชื่อมต่อ: " + error.message);
                }
            }
            
            setCustomer(customerCode) {
                this.customerCode = customerCode;
                this.loadCustomerInfo();
                this.loadCallHistory();
            }
        }
        
        // Global functions for compatibility
        function testAddCallLog() {
            if (window.callHistoryManager) {
                window.callHistoryManager.testAddCallLog();
            }
        }
        
        function testRefresh() {
            if (window.callHistoryManager) {
                window.callHistoryManager.refreshHistory();
            }
        }
        
        function testChangeCustomer() {
            const newCustomer = prompt("กรุณาใส่รหัสลูกค้าใหม่:", "' . $testCustomerCode . '");
            if (newCustomer && window.callHistoryManager) {
                window.callHistoryManager.setCustomer(newCustomer);
                // Update URL
                window.history.pushState({}, "", "?customer=" + encodeURIComponent(newCustomer));
            }
        }
        
        // Initialize when page loads
        document.addEventListener("DOMContentLoaded", function() {
            window.callHistoryManager = new CallHistoryManager();
        });
        
        // Handle browser back/forward
        window.addEventListener("popstate", function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const customer = urlParams.get("customer") || "' . $testCustomerCode . '";
            if (window.callHistoryManager) {
                window.callHistoryManager.setCustomer(customer);
            }
        });
    </script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>