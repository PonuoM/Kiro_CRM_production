<?php
require_once '../includes/permissions.php';

// Check login and permissions
Permissions::requireLogin('login.php');
Permissions::requirePermission('customer_detail', 'login.php');

// Get customer code from URL parameter
$customerCode = $_GET['code'] ?? '';
if (empty($customerCode)) {
    header('Location: dashboard.php');
    exit();
}

// Get user information
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$username = Permissions::getCurrentUser();
$canEdit = Permissions::hasPermission('customer_edit');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดลูกค้า - CRM System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/customer-detail.css">
    <link rel="stylesheet" href="../assets/css/customer-intelligence.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="customer-detail-container">
        <!-- Header -->
        <header class="page-header">
            <div class="header-content">
                <div class="header-left">
                    <button class="back-btn" onclick="goBack()">← กลับ</button>
                    <h1 class="page-title">รายละเอียดลูกค้า</h1>
                </div>
                <div class="header-right">
                    <span class="user-info"><?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)</span>
                    <a href="../api/auth/logout.php" class="logout-btn">ออกจากระบบ</a>
                </div>
            </div>
        </header>

        <!-- Loading State -->
        <div class="loading-overlay" id="loading-overlay">
            <div class="loading-spinner">กำลังโหลดข้อมูล...</div>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Customer Information Card -->
            <div class="info-card" id="customer-info-card">
                <div class="card-header">
                    <h2>ข้อมูลลูกค้า</h2>
                    <div class="card-actions">
                        <?php if ($canEdit): ?>
                            <button class="btn btn-secondary" onclick="editCustomer()">แก้ไขข้อมูล</button>
                            <button class="btn btn-info" onclick="updateIntelligence()">🧠 อัปเดต Intelligence</button>
                        <?php else: ?>
                            <span class="text-muted">👁️ ดูเท่านั้น (<?= $user_role ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-content" id="customer-info-content">
                    <!-- Customer info will be loaded here -->
                </div>
            </div>

            <!-- Customer Intelligence Card -->
            <div class="info-card" id="customer-intelligence-card">
                <div class="card-header">
                    <h2>🧠 Customer Intelligence</h2>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshIntelligence()">รีเฟรช</button>
                    </div>
                </div>
                <div class="card-content" id="customer-intelligence-content">
                    <!-- Customer intelligence will be loaded here -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="showCallLogForm()">บันทึกการโทร</button>
                <button class="btn btn-success" onclick="showTaskForm()">สร้างนัดหมาย</button>
                <button class="btn btn-info" onclick="showOrderForm()">สร้างคำสั่งซื้อ</button>
            </div>

            <!-- Tabs for History -->
            <div class="history-tabs">
                <div class="tab-nav">
                    <button class="tab-btn active" data-tab="call-history">ประวัติการโทร</button>
                    <button class="tab-btn" data-tab="order-history">ประวัติคำสั่งซื้อ</button>
                    <button class="tab-btn" data-tab="sales-history">ประวัติ Sales</button>
                    <button class="tab-btn" data-tab="task-history">ประวัติงาน</button>
                </div>

                <!-- Call History Tab -->
                <div class="tab-content active" id="call-history-tab">
                    <div class="tab-header">
                        <h3>ประวัติการโทร</h3>
                        <button class="btn btn-sm btn-primary" onclick="refreshCallHistory()">รีเฟรช</button>
                    </div>
                    <div class="history-content" id="call-history-content">
                        <!-- Call history will be loaded here -->
                    </div>
                </div>

                <!-- Order History Tab -->
                <div class="tab-content" id="order-history-tab">
                    <div class="tab-header">
                        <h3>ประวัติคำสั่งซื้อ</h3>
                        <button class="btn btn-sm btn-primary" onclick="refreshOrderHistory()">รีเฟรช</button>
                    </div>
                    <div class="history-content" id="order-history-content">
                        <!-- Order history will be loaded here -->
                    </div>
                </div>

                <!-- Sales History Tab -->
                <div class="tab-content" id="sales-history-tab">
                    <div class="tab-header">
                        <h3>ประวัติ Sales ที่เคยดูแล</h3>
                        <button class="btn btn-sm btn-primary" onclick="refreshSalesHistory()">รีเฟรช</button>
                    </div>
                    <div class="history-content" id="sales-history-content">
                        <!-- Sales history will be loaded here -->
                    </div>
                </div>

                <!-- Task History Tab -->
                <div class="tab-content" id="task-history-tab">
                    <div class="tab-header">
                        <h3>ประวัติงานและนัดหมาย</h3>
                        <button class="btn btn-sm btn-primary" onclick="refreshTaskHistory()">รีเฟรช</button>
                    </div>
                    <div class="history-content" id="task-history-content">
                        <!-- Task history will be loaded here -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Call Log Modal -->
    <div class="modal" id="call-log-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>บันทึกการโทร</h3>
                <button class="modal-close" onclick="closeModal('call-log-modal')">&times;</button>
            </div>
            <form id="call-log-form" class="modal-body">
                <input type="hidden" id="call-customer-code" value="<?php echo htmlspecialchars($customerCode); ?>">
                
                <div class="form-group">
                    <label for="call-date">วันที่โทร *</label>
                    <input type="datetime-local" id="call-date" name="call_date" required>
                </div>
                
                <div class="form-group">
                    <label for="call-minutes">ระยะเวลา (นาที)</label>
                    <input type="number" id="call-minutes" name="call_minutes" min="0">
                </div>
                
                <div class="form-group">
                    <label for="call-status">สถานะการโทร *</label>
                    <select id="call-status" name="call_status" required onchange="toggleCallFields()">
                        <option value="">เลือกสถานะ</option>
                        <option value="ติดต่อได้">ติดต่อได้</option>
                        <option value="ติดต่อไม่ได้">ติดต่อไม่ได้</option>
                    </select>
                </div>
                
                <div class="form-group" id="call-reason-group" style="display: none;">
                    <label for="call-reason">เหตุผลที่ติดต่อไม่ได้ <small>(หรือกรอกในหมายเหตุด้านล่าง)</small></label>
                    <input type="text" id="call-reason" name="call_reason" placeholder="เช่น เบอร์ไม่รับสาย, เครื่องปิด">
                </div>
                
                <div class="form-group" id="talk-status-group" style="display: none;">
                    <label for="talk-status">สถานะการคุย</label>
                    <select id="talk-status" name="talk_status" onchange="toggleTalkReason()">
                        <option value="">เลือกสถานะ</option>
                        <option value="ได้คุย">ได้คุย</option>
                        <option value="ยังไม่สนใจ">ยังไม่สนใจ</option>
                        <option value="ขอคิดดูก่อน">ขอคิดดูก่อน</option>
                        <option value="ไม่สนใจแล้ว">ไม่สนใจแล้ว</option>
                        <option value="ใช้สินค้าอื่น">ใช้สินค้าอื่น</option>
                        <option value="อย่าโทรมาอีก">อย่าโทรมาอีก</option>
                    </select>
                </div>
                
                <div class="form-group" id="talk-reason-group" style="display: none;">
                    <label for="talk-reason">เหตุผลที่คุยไม่จบ <small>(หรือกรอกในหมายเหตุด้านล่าง)</small></label>
                    <input type="text" id="talk-reason" name="talk_reason" placeholder="เช่น ลูกค้าไม่มีเวลา, ต้องการคิดก่อน">
                </div>
                
                <div class="form-group">
                    <label for="call-remarks">หมายเหตุ <small>(สามารถใช้เป็นเหตุผลเมื่อติดต่อไม่ได้หรือคุยไม่จบได้)</small></label>
                    <textarea id="call-remarks" name="remarks" rows="3" placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับการโทรครั้งนี้..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('call-log-modal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Task Modal -->
    <div class="modal" id="task-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>สร้างนัดหมาย</h3>
                <button class="modal-close" onclick="closeModal('task-modal')">&times;</button>
            </div>
            <form id="task-form" class="modal-body">
                <input type="hidden" id="task-customer-code" value="<?php echo htmlspecialchars($customerCode); ?>">
                
                <div class="form-group">
                    <label for="followup-date">วันที่นัดหมาย *</label>
                    <input type="datetime-local" id="followup-date" name="followup_date" required>
                </div>
                
                <div class="form-group">
                    <label for="task-remarks">หมายเหตุ</label>
                    <textarea id="task-remarks" name="remarks" rows="3" placeholder="รายละเอียดการนัดหมาย..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('task-modal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">สร้างนัดหมาย</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Modal -->
    <div class="modal" id="order-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>สร้างคำสั่งซื้อ</h3>
                <button class="modal-close" onclick="closeModal('order-modal')">&times;</button>
            </div>
            <form id="order-form" class="modal-body" onkeydown="return preventEnterSubmit(event)">
                <input type="hidden" id="order-customer-code" value="<?php echo htmlspecialchars($customerCode); ?>">
                
                <div class="form-group">
                    <label for="document-date">วันที่เอกสาร *</label>
                    <input type="date" id="document-date" name="document_date" required>
                </div>
                
                <div class="form-group">
                    <label for="payment-method">วิธีการชำระเงิน *</label>
                    <select id="payment-method" name="payment_method" required>
                        <option value="">เลือกวิธีการชำระเงิน</option>
                        <option value="เงินสด">เงินสด</option>
                        <option value="โอนเงิน">โอนเงิน</option>
                        <option value="เช็ค">เช็ค</option>
                        <option value="บัตรเครดิต">บัตรเครดิต</option>
                        <option value="เก็บเงินปลายทาง">เก็บเงินปลายทาง</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>รายการสินค้า *</label>
                    <div id="products-container">
                        <div class="product-row" data-product-index="0">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>เลือกสินค้า</label>
                                    <input type="text" name="product_search[]" class="product-search" 
                                           placeholder="พิมพ์เพื่อค้นหาสินค้า..." 
                                           autocomplete="off">
                                    <div class="product-suggestions" style="display: none;"></div>
                                    <input type="hidden" name="product_code[]" value="" required>
                                    <input type="hidden" name="product_name[]" value="">
                                </div>
                                <div class="form-group">
                                    <label>จำนวน</label>
                                    <input type="number" name="product_quantity[]" min="1" step="1" required value="1" onchange="calculateProductTotal(this)">
                                </div>
                                <div class="form-group">
                                    <label>ราคาต่อหน่วย</label>
                                    <input type="number" name="product_price[]" min="0" step="0.01" required placeholder="0.00" onchange="calculateProductTotal(this)">
                                </div>
                                <div class="form-group">
                                    <label>จำนวนเงิน</label>
                                    <input type="number" class="product-total" readonly placeholder="0.00">
                                </div>
                                <div class="form-group">
                                    <button type="button" class="btn btn-danger btn-sm remove-product" onclick="removeProduct(this)" style="margin-top: 25px;">ลบ</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" onclick="addProduct()">+ เพิ่มสินค้า</button>
                </div>
                
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-group">
                            <label><strong>จำนวนรวม</strong></label>
                            <input type="number" id="total-quantity" readonly>
                        </div>
                        <div class="form-group">
                            <label><strong>ยอดรวม (ก่อนหักส่วนลด)</strong></label>
                            <input type="number" id="subtotal-amount" readonly>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>ส่วนลด (บาท)</label>
                            <input type="number" id="discount-amount" name="discount_amount" min="0" step="0.01" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>ส่วนลด (%)</label>
                            <div class="input-group">
                                <input type="number" id="discount-percent" name="discount_percent" min="0" max="100" step="0.01" placeholder="0.00">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>หมายเหตุส่วนลด</label>
                            <input type="text" id="discount-remarks" name="discount_remarks" placeholder="เช่น โปรโมชั่นพิเศษ, ลูกค้าVIP">
                        </div>
                        <div class="form-group">
                            <label><strong>ยอดรวมสุทธิ</strong></label>
                            <input type="number" id="total-amount" readonly style="font-weight: bold; background-color: #e9ecef;">
                        </div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('order-modal')">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">สร้างคำสั่งซื้อ</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/customer-detail.js"></script>
    <script src="../assets/js/customer-intelligence.js"></script>
    <script>
        // Initialize customer detail page
        const customerCode = '<?php echo htmlspecialchars($customerCode); ?>';
        const currentUser = '<?php echo htmlspecialchars($username); ?>';
        
        // Initialize customer detail instance
        document.addEventListener('DOMContentLoaded', function() {
            window.customerDetail = new CustomerDetail(customerCode, currentUser);
        });
        
        // Global functions for customer detail
        function updateIntelligence() {
            if (window.customerDetail) {
                window.customerDetail.updateIntelligence();
            }
        }
        
        function refreshIntelligence() {
            if (window.customerDetail) {
                window.customerDetail.refreshIntelligence();
            }
        }
        
        // Override editCustomer function to include navigation
        function editCustomer() {
            // Navigate to edit customer page
            window.location.href = 'customer_edit.php?code=' + encodeURIComponent(customerCode);
        }
    </script>
</body>
</html>