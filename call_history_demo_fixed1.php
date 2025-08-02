<?php
/**
 * Call History Production Page
 * แสดงประวัติการโทรของลูกค้าที่ Sales ดูแลอยู่ - Production Version 
 */

// Production error handling
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors in production
ini_set('log_errors', 1);

try {
    // Session management
    if (session_status() == PHP_SESSION_NONE) {
        session_start();  
    }

    // Include required files with error handling
    $required_files = [
        __DIR__ . '/../includes/permissions.php',
        __DIR__ . '/../includes/main_layout.php',
        __DIR__ . '/../config/database.php'
    ];

    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: " . basename($file));
        }
        require_once $file;
    }

    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    // Check permissions
    if (!class_exists('Permissions')) {
        throw new Exception("Permissions system not available");
    }

    Permissions::requireLogin();

    if (!Permissions::hasPermission('call_history')) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access Denied: ไม่มีสิทธิ์ในการเข้าถึงข้อมูลประวัติการโทร";
        exit;
    }

    // Get user information
    $user_name = Permissions::getCurrentUser();
    $user_role = Permissions::getCurrentRole();
    $menuItems = Permissions::getMenuItems();

    // Set globals for main_layout
    $GLOBALS['currentUser'] = $user_name;
    $GLOBALS['currentRole'] = $user_role;
    $GLOBALS['menuItems'] = $menuItems;

    // Get and validate customer code
    $customerCode = $_GET['customer'] ?? null;
    if (!$customerCode) {
        header('HTTP/1.1 400 Bad Request');
        echo "Error: ไม่พบรหัสลูกค้า กรุณาระบุ customer parameter";
        exit;
    }

    // Get customer data and validate access
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $sql = "SELECT CustomerCode, CustomerName, CustomerTel, CustomerStatus, CartStatus, Sales 
            FROM customers WHERE CustomerCode = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customerCode]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        header('HTTP/1.1 404 Not Found');
        echo "Error: ไม่พบข้อมูลลูกค้า รหัส: " . htmlspecialchars($customerCode);
        exit;
    }

    // Check if Sales role user can only view their own customers
    if ($user_role === 'Sale' && $customer['Sales'] !== $user_name) {
        header('HTTP/1.1 403 Forbidden');
        echo "Access Denied: คุณไม่มีสิทธิ์ดูข้อมูลลูกค้ารายนี้";
        exit;
    }

    // Get call statistics for this customer
    $sql = "SELECT 
                COUNT(*) as total_calls,
                SUM(CASE WHEN CallStatus = 'ติดต่อได้' THEN 1 ELSE 0 END) as successful_calls,
                SUM(CASE WHEN CallStatus = 'ติดต่อไม่ได้' THEN 1 ELSE 0 END) as failed_calls,
                SUM(CASE WHEN TalkStatus = 'คุยจบ' THEN 1 ELSE 0 END) as completed_talks,
                SUM(CASE WHEN TalkStatus = 'คุยไม่จบ' THEN 1 ELSE 0 END) as incomplete_talks,
                AVG(CAST(CallMinutes as UNSIGNED)) as avg_duration
            FROM call_logs 
            WHERE CustomerCode = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customerCode]);
    $callStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $pageTitle = "ประวัติการโทร - " . $customer['CustomerName'];

    // Start output buffering for content
    ob_start();
    ?>

    <!-- Page Header -->
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title mb-2">
                    <i class="fas fa-phone text-primary"></i>
                    ประวัติการโทร
                </h1>
                <p class="page-description text-muted">
                    ดูประวัติและจัดการบันทึกการโทรเข้าหาลูกค้า
                </p>
            </div>
            <div class="text-end">
                <div class="badge bg-primary fs-6 mb-1">
                    <i class="fas fa-phone"></i> <?php echo $callStats['total_calls']; ?> ครั้ง
                </div>
                <div class="small text-muted">รวมทั้งหมด</div>
            </div>
        </div>
    </div>

    <!-- Customer Info Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user"></i> ข้อมูลลูกค้า
                </h5>
                <div class="d-flex gap-2">
                    <?php if ($user_role === 'Admin' || $user_role === 'Supervisor'): ?>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-user-tie"></i> Sales: <?php echo htmlspecialchars($customer['Sales'] ?? 'ไม่ระบุ'); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($customer['CartStatus']): ?>
                        <span class="badge bg-<?php echo $customer['CartStatus'] === 'กำลังดูแล' ? 'success' : ($customer['CartStatus'] === 'ตะกร้าแจก' ? 'info' : 'warning'); ?>">
                            <?php echo htmlspecialchars($customer['CartStatus']); ?>  
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-hashtag"></i>
                        </div>
                        <div>
                            <small class="text-muted">รหัสลูกค้า</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($customer['CustomerCode']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div>
                            <small class="text-muted">ชื่อลูกค้า</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($customer['CustomerName']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <small class="text-muted">เบอร์โทร</small>
                            <div class="fw-bold"><?php echo htmlspecialchars($customer['CustomerTel']); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="d-flex align-items-center">
                        <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div>
                            <small class="text-muted">สถานะ</small>
                            <div>
                                <span class="badge bg-<?php echo $customer['CustomerStatus'] === 'ลูกค้าใหม่' ? 'success' : ($customer['CustomerStatus'] === 'ลูกค้าติดตาม' ? 'warning' : 'info'); ?>">
                                    <?php echo htmlspecialchars($customer['CustomerStatus']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call Statistics Dashboard -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar text-success"></i> สถิติการโทร
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-success btn-sm" onclick="callHistoryManager.showCallLogForm()">
                        <i class="fas fa-plus"></i> บันทึกใหม่
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="callHistoryManager.refreshHistory()">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Total Calls -->
                <div class="col-md-3">
                    <div class="stat-card bg-primary bg-opacity-10 border-primary">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-primary"><?php echo $callStats['total_calls'] ?? 0; ?></div>
                            <div class="stat-label">รวมโทรทั้งหมด</div>
                        </div>
                    </div>
                </div>
                
                <!-- Success Rate -->
                <div class="col-md-3">
                    <div class="stat-card bg-success bg-opacity-10 border-success">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-success">
                                <?php 
                                $successRate = $callStats['total_calls'] > 0 ? round(($callStats['successful_calls'] / $callStats['total_calls']) * 100) : 0;
                                echo $successRate; 
                                ?>%
                            </div>
                            <div class="stat-label">อัตราติดต่อได้</div>
                        </div>
                    </div>
                </div>
                
                <!-- Completed Talks -->
                <div class="col-md-3">
                    <div class="stat-card bg-info bg-opacity-10 border-info">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-info"><?php echo $callStats['completed_talks'] ?? 0; ?></div>
                            <div class="stat-label">คุยจบ</div>
                        </div>
                    </div>
                </div>
                
                <!-- Average Duration -->
                <div class="col-md-3">
                    <div class="stat-card bg-warning bg-opacity-10 border-warning">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number text-warning"><?php echo round($callStats['avg_duration'] ?? 0); ?></div>
                            <div class="stat-label">นาทีเฉลี่ย</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Stats -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="small-stat-card">
                        <span class="small-stat-label">
                            <i class="fas fa-check-circle text-success"></i> ติดต่อได้:
                        </span>
                        <span class="small-stat-value text-success fw-bold"><?php echo $callStats['successful_calls'] ?? 0; ?> ครั้ง</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="small-stat-card">
                        <span class="small-stat-label">
                            <i class="fas fa-times-circle text-danger"></i> ติดต่อไม่ได้:
                        </span>
                        <span class="small-stat-value text-danger fw-bold"><?php echo $callStats['failed_calls'] ?? 0; ?> ครั้ง</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call History Container -->
    <div class="card shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history text-info"></i> ประวัติการโทรรายละเอียด
                </h5>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-muted small">
                        <i class="fas fa-user"></i> 
                        <?php if ($user_role === 'Sale'): ?>
                            ประวัติของคุณ
                        <?php else: ?>
                            ประวัติทั้งหมด
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Enhanced Filter Section -->
            <div class="bg-light p-3 rounded mb-4">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small">
                            <i class="fas fa-calendar"></i> เดือน
                        </label>
                        <select class="form-select form-select-sm" id="monthFilter">
                            <option value="">ทุกเดือน</option>
                            <option value="2025-07">กรกฎาคม 2025</option>
                            <option value="2025-06">มิถุนายน 2025</option>
                            <option value="2025-05">พฤษภาคม 2025</option>
                            <option value="2025-04">เมษายน 2025</option>
                            <option value="2025-03">มีนาคม 2025</option>
                            <option value="2025-02">กุมภาพันธ์ 2025</option>
                            <option value="2025-01">มกราคม 2025</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">
                            <i class="fas fa-phone-alt"></i> สถานะการโทร
                        </label>
                        <select class="form-select form-select-sm" id="statusFilter">
                            <option value="">ทุกสถานะ</option>
                            <option value="ติดต่อได้">ติดต่อได้</option>
                            <option value="ติดต่อไม่ได้">ติดต่อไม่ได้</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">
                            <i class="fas fa-comments"></i> สถานะการคุย
                        </label>
                        <select class="form-select form-select-sm" id="talkStatusFilter">
                            <option value="">ทุกสถานะ</option>
                            <option value="คุยจบ">คุยจบ</option>
                            <option value="คุยไม่จบ">คุยไม่จบ</option>
                        </select>
                    </div>
                    <?php if ($user_role === 'Admin' || $user_role === 'Supervisor'): ?>
                    <div class="col-md-2">
                        <label class="form-label small">
                            <i class="fas fa-user-tie"></i> ผู้บันทึก
                        </label>
                        <select class="form-select form-select-sm" id="createdByFilter">
                            <option value="">ทุกคน</option>
                            <?php
                            // Get list of sales people for filter
                            $sql = "SELECT DISTINCT Username, FirstName, LastName FROM users WHERE Role IN ('Sale', 'Supervisor') AND Status = 1 ORDER BY FirstName";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute();
                            $salesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($salesList as $sales): ?>
                                <option value="<?php echo htmlspecialchars($sales['Username']); ?>">
                                    <?php echo htmlspecialchars($sales['FirstName'] . ' ' . $sales['LastName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label small">
                            <i class="fas fa-sort"></i> เรียง
                        </label>
                        <select class="form-select form-select-sm" id="sortOrder">
                            <option value="newest">ล่าสุดก่อน</option>
                            <option value="oldest">เก่าสุดก่อน</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group w-100" role="group">
                            <button class="btn btn-primary btn-sm" onclick="callHistoryManager.applyFilters()" title="กรองข้อมูล">
                                <i class="fas fa-filter"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="callHistoryManager.clearFilters()" title="ล้างตัวกรอง">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="call-history-container">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <div class="mt-2 text-muted">กำลังโหลดประวัติการโทร...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation & Actions -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-arrow-left text-primary"></i> การนำทาง
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center">
                    <div class="d-flex gap-2 flex-wrap w-100">
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
                        </a>
                        <a href="customer_list_dynamic.php" class="btn btn-outline-info">
                            <i class="fas fa-users"></i> รายการลูกค้า
                        </a>
                        <?php if ($user_role === 'Admin' || $user_role === 'Supervisor'): ?>
                        <a href="customer_detail.php?customer=<?php echo urlencode($customerCode); ?>" class="btn btn-outline-success">
                            <i class="fas fa-user-circle"></i> รายละเอียดลูกค้า
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs text-success"></i> การจัดการ
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center">
                    <div class="d-flex gap-2 flex-wrap w-100">
                        <button class="btn btn-primary" onclick="callHistoryManager.showCallLogForm()">
                            <i class="fas fa-phone-plus"></i> เพิ่มบันทึก
                        </button>
                        <button class="btn btn-success" onclick="callHistoryManager.refreshHistory()">
                            <i class="fas fa-sync-alt"></i> รีเฟรช
                        </button>
                        <button class="btn btn-info" onclick="callHistoryManager.exportCallHistory()">
                            <i class="fas fa-file-export"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $content = ob_get_clean();

    // Enhanced CSS for Production
    $additionalCSS = '
        <style>
            .page-header {
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #e9ecef;
            }
            
            .icon-box {
                width: 40px;
                height: 40px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.1rem;
            }
            
            .stat-card {
                background: white;
                border-radius: 12px;
                padding: 20px;
                border: 2px solid;
                display: flex;
                align-items: center;
                gap: 16px;
                height: 100%;
                transition: all 0.3s ease;
                position: relative;
                overflow: hidden;
            }
            
            .stat-card::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--bs-primary), var(--bs-success));
            }
            
            .stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 1.2rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .stat-number {
                font-size: 2.2rem;
                font-weight: 700;
                margin: 0;
                line-height: 1;
            }
            
            .stat-label {
                font-size: 0.875rem;
                color: #64748b;
                margin: 4px 0 0 0;
                font-weight: 500;
            }
            
            .small-stat-card {
                background: #f8fafc;
                border-radius: 8px;
                padding: 12px 16px;
                margin-bottom: 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: all 0.2s ease;
            }
            
            .small-stat-card:hover {
                background: #e2e8f0;
                transform: translateX(4px);
            }
            
            .small-stat-label {
                font-size: 0.875rem;
                color: #64748b;
                font-weight: 500;
            }
            
            .small-stat-value {
                font-size: 0.875rem;
            }
            
            .bg-gradient-primary {
                background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-info) 100%);
            }
            
            .form-label {
                font-weight: 500;
                color: #374151;
                margin-bottom: 4px;
            }
            
            .form-select:focus {
                border-color: #76BC43;
                box-shadow: 0 0 0 3px rgba(118, 188, 67, 0.1);
            }
            
            .card {
                border: none;
                border-radius: 12px;
            }
            
            .card-header {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                border-radius: 12px 12px 0 0 !important;
            }
            
            .shadow-sm {
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
            }
            
            @media (max-width: 768px) {
                .stat-card {
                    flex-direction: column;
                    text-align: center;
                    gap: 12px;
                    padding: 16px;
                }
                
                .stat-number {
                    font-size: 1.8rem;
                }
                
                .icon-box {
                    width: 35px;
                    height: 35px;
                }
            }
        </style>
    ';

    // Production JavaScript with real functionality
    $additionalJS = '
        <script src="../assets/js/call-log-popup.js"></script>
        <script>
            console.log("Call History Production - Initializing...");
            
            class CallHistoryManager {
                constructor() {
                    this.customerCode = "' . $customerCode . '";
                    this.currentCustomer = ' . json_encode($customer) . ';
                    this.callHistory = [];
                    this.userRole = "' . $user_role . '";
                    this.currentUser = "' . $user_name . '";
                    this.totalCallsCount = ' . ($callStats['total_calls'] ?? 0) . ';
                    
                    console.log("Production Mode Initialized:");
                    console.log("- Customer:", this.customerCode);
                    console.log("- User:", this.currentUser, "(" + this.userRole + ")");
                    console.log("- Total Calls:", this.totalCallsCount);
                }
                
                init() {
                    console.log("Loading call history data...");
                    this.loadCallHistory();
                }
                
                async loadCallHistory(filters = {}) {
                    const container = document.getElementById("call-history-container");
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">กำลังโหลด...</span>
                            </div>
                            <div class="mt-2 text-muted">กำลังโหลดประวัติการโทร...</div>
                        </div>
                    `;
                    
                    try {
                        let url = "../api/calls/log.php?customer_code=" + encodeURIComponent(this.customerCode);
                        
                        // Add filter parameters
                        if (filters.month) {
                            const [year, month] = filters.month.split("-");
                            url += "&date_from=" + year + "-" + month + "-01&date_to=" + year + "-" + month + "-31";
                        }
                        if (filters.call_status) {
                            url += "&call_status=" + encodeURIComponent(filters.call_status);
                        }
                        if (filters.talk_status) {
                            url += "&talk_status=" + encodeURIComponent(filters.talk_status);
                        }
                        if (filters.created_by) {
                            url += "&created_by=" + encodeURIComponent(filters.created_by);
                        }
                        if (filters.sort_order) {
                            url += "&sort=" + (filters.sort_order === "oldest" ? "ASC" : "DESC");
                        }
                        
                        console.log("Fetching:", url);
                        
                        const response = await fetch(url);
                        const data = await response.json();
                        
                        if (data.success || data.status === "success") {
                            this.callHistory = data.data || [];
                            console.log("Loaded", this.callHistory.length, "call records");
                            this.renderCallHistory();
                        } else {
                            throw new Error(data.message || "เกิดข้อผิดพลาดในการโหลดข้อมูล");
                        }
                    } catch (error) {
                        console.error("Error loading call history:", error);
                        this.renderError(error.message);
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="text-muted mb-0">
                                    <i class="fas fa-list"></i> พบ ${this.callHistory.length} รายการ
                                </h6>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-success btn-sm" onclick="callHistoryManager.showCallLogForm()">
                                    <i class="fas fa-plus"></i> เพิ่มบันทึก
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="callHistoryManager.refreshHistory()">
                                    <i class="fas fa-sync-alt"></i> รีเฟรช
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-calendar"></i> วันที่/เวลา</th>
                                        <th><i class="fas fa-phone-alt"></i> สถานะการโทร</th>
                                        <th><i class="fas fa-comments"></i> สถานะการคุย</th>
                                        <th><i class="fas fa-clock"></i> ระยะเวลา</th>
                                        <th><i class="fas fa-sticky-note"></i> หมายเหตุ</th>
                                        <th><i class="fas fa-user"></i> ผู้บันทึก</th>
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
                        <tr class="call-item" data-call-id="${call.id || \"\"}">
                            <td>
                                <div class="fw-bold">${this.formatDate(call.CallDate)}</div>
                                <small class="text-muted">${this.formatTime(call.CallDate)}</small>
                            </td>
                            <td>
                                <span class="badge ${this.getCallStatusBadgeClass(call.CallStatus)}">
                                    <i class="fas ${call.CallStatus === \"ติดต่อได้\" ? \"fa-check\" : \"fa-times\"}"></i>
                                    ${this.escapeHtml(call.CallStatus || \"ไม่ระบุ\")}
                                </span>
                            </td>
                            <td>
                                <span class="badge ${this.getTalkStatusBadgeClass(call.TalkStatus)}">
                                    ${call.TalkStatus ? this.escapeHtml(call.TalkStatus) : \"-\"}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold text-info">
                                    ${call.CallMinutes ? call.CallMinutes + \" นาที\" : \"-\"}
                                </span>
                            </td>
                            <td>
                                <small class="text-truncate d-block" style="max-width: 200px;" title="${this.escapeHtml(call.Remarks || \"\")}">
                                    ${call.Remarks ? this.escapeHtml(call.Remarks) : \"-\"}
                                </small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-user-circle text-muted"></i>
                                    <span class="fw-bold">${this.escapeHtml(call.CreatedBy || \"-\")}</span>
                                </small>
                            </td>
                        </tr>
                    `;
                }
                
                renderEmptyState() {
                    return `
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-phone-slash fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted">ยังไม่มีประวัติการโทร</h5>
                            <p class="text-muted">
                                ลูกค้ารายนี้ยังไม่มีบันทึกการโทรในระบบ<br>
                                <small>เริ่มต้นบันทึกการโทรครั้งแรกได้เลย</small>
                            </p>
                            <button class="btn btn-primary btn-lg" onclick="callHistoryManager.showCallLogForm()">
                                <i class="fas fa-plus"></i> เพิ่มบันทึกการโทรแรก
                            </button>
                        </div>
                    `;
                }
                
                renderError(message) {
                    document.getElementById("call-history-container").innerHTML = `
                        <div class="alert alert-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                <div>
                                    <h6 class="alert-heading">เกิดข้อผิดพลาด</h6>
                                    <p class="mb-2">${message}</p>
                                    <button class="btn btn-sm btn-outline-danger" onclick="callHistoryManager.loadCallHistory()">
                                        <i class="fas fa-redo"></i> ลองใหม่
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
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
                
                showCallLogForm() {
                    if (window.callLogPopup && this.currentCustomer) {
                        window.callLogPopup.show(this.currentCustomer);
                    } else {
                        alert("ไม่สามารถเปิดฟอร์มได้ กรุณาลองใหม่");
                    }
                }
                
                refreshHistory() {
                    console.log("Refreshing call history...");
                    this.loadCallHistory();
                }
                
                applyFilters() {
                    const filters = {
                        month: document.getElementById("monthFilter").value,
                        call_status: document.getElementById("statusFilter").value,
                        talk_status: document.getElementById("talkStatusFilter").value,
                        sort_order: document.getElementById("sortOrder").value
                    };
                    
                    ' . ($user_role === 'Admin' || $user_role === 'Supervisor' ? '
                    const createdByElement = document.getElementById("createdByFilter");
                    if (createdByElement) {
                        filters.created_by = createdByElement.value;
                    }
                    ' : '') . '
                    
                    console.log("Applying filters:", filters);
                    this.loadCallHistory(filters);
                }
                
                clearFilters() {
                    document.getElementById("monthFilter").value = "";
                    document.getElementById("statusFilter").value = "";
                    document.getElementById("talkStatusFilter").value = "";
                    document.getElementById("sortOrder").value = "newest";
                    
                    ' . ($user_role === 'Admin' || $user_role === 'Supervisor' ? '
                    const createdByElement = document.getElementById("createdByFilter");
                    if (createdByElement) {
                        createdByElement.value = "";
                    }
                    ' : '') . '
                    
                    this.loadCallHistory();
                }
                
                exportCallHistory() {
                    // TODO: Implement export functionality
                    alert("ฟีเจอร์ Export กำลังพัฒนา จะเปิดใช้งานในเร็วๆ นี้");
                }
            }
            
            // Initialize when page loads
            document.addEventListener("DOMContentLoaded", function() {
                console.log("DOM loaded - Initializing CallHistoryManager");
                window.callHistoryManager = new CallHistoryManager();
                window.callHistoryManager.init();
            });
        </script>
    ';

    // Render the page
    try {
        if (function_exists('renderMainLayout')) {
            echo renderMainLayout($pageTitle, $content, $additionalCSS, $additionalJS);
        } else {
            throw new Exception("Layout function not available");
        }
    } catch (Exception $e) {
        error_log("Layout error: " . $e->getMessage());
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>{$pageTitle}</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
            <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
            {$additionalCSS}
        </head>
        <body>
            <div class='container mt-4'>
                <div class='alert alert-info'>
                    <strong>Fallback Mode:</strong> Using basic HTML layout.
                </div>
                {$content}
            </div>
            {$additionalJS}
        </body>
        </html>";
    }

} catch (Exception $e) {
    // Global error handler
    error_log("Call History Production Error: " . $e->getMessage());
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Error - Call History</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    </head>
    <body>
        <div class='container mt-5'>
            <div class='row justify-content-center'>
                <div class='col-md-8'>
                    <div class='card'>
                        <div class='card-header bg-danger text-white'>
                            <h5><i class='fas fa-exclamation-triangle'></i> ระบบไม่สามารถทำงานได้</h5>
                        </div>
                        <div class='card-body'>
                            <p><strong>เกิดข้อผิดพลาด:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                            <p><strong>กรุณา:</strong></p>
                            <ul>
                                <li>ตรวจสอบการเชื่อมต่อกับระบบ</li>
                                <li>ลองเข้าสู่ระบบใหม่</li>
                                <li>ติดต่อผู้ดูแลระบบหากปัญหายังคงอยู่</li>
                            </ul>
                            <a href='../pages/dashboard.php' class='btn btn-primary'>
                                <i class='fas fa-home'></i> กลับหน้าแรก
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>";
}
?>