<?php
require_once '../includes/main_layout.php';

// Check login and permissions
Permissions::requireLogin('login.php');
Permissions::requirePermission('dashboard', 'login.php');

$pageTitle = "แดชบอร์ด";

// Get user information
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt"></i>
        แดชบอร์ด
    </h1>
    <p class="page-description">
        ศูนย์รวมการทำงานและข้อมูลสำคัญของระบบ CRM | User: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
    </p>
</div>

<!-- Summary Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-users fa-2x text-primary me-3"></i>
                    <h3 class="mb-0 text-dark" id="totalCustomers">0</h3>
                </div>
                <p class="mb-0 text-muted">ลูกค้าทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-tasks fa-2x text-success me-3"></i>
                    <h3 class="mb-0 text-dark" id="todayTasks">0</h3>
                </div>
                <p class="mb-0 text-muted">งานวันนี้</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-fire fa-2x text-danger me-3"></i>
                    <h3 class="mb-0 text-dark" id="hotCustomers">0</h3>
                </div>
                <p class="mb-0 text-muted">ลูกค้า HOT</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm" style="background: white; border: 1px solid #e5e7eb;">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-star fa-2x text-warning me-3"></i>
                    <h3 class="mb-0 text-dark" id="gradeACustomers">0</h3>
                </div>
                <p class="mb-0 text-muted">ลูกค้า Grade A</p>
            </div>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="do-tab" data-bs-toggle="tab" data-bs-target="#do" type="button" role="tab">
            <i class="fas fa-tasks"></i> DO (นัดหมายวันนี้)
        </button>
    </li>
    
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="new-customers-tab" data-bs-toggle="tab" data-bs-target="#new-customers" type="button" role="tab">
            <i class="fas fa-user-plus"></i> ลูกค้าใหม่
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="follow-customers-tab" data-bs-toggle="tab" data-bs-target="#follow-customers" type="button" role="tab">
            <i class="fas fa-user-check"></i> ลูกค้าติดตาม
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="old-customers-tab" data-bs-toggle="tab" data-bs-target="#old-customers" type="button" role="tab">
            <i class="fas fa-users"></i> ลูกค้าเก่า
        </button>
    </li>
    
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="follow-all-tab" data-bs-toggle="tab" data-bs-target="#follow-all" type="button" role="tab">
            <i class="fas fa-calendar-alt"></i> Follow ทั้งหมด
        </button>
    </li>
    
    <?php if ($user_role === 'admin' || $user_role === 'manager'): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="unassigned-tab" data-bs-toggle="tab" data-bs-target="#unassigned" type="button" role="tab">
                <i class="fas fa-user-clock"></i> รอมอบหมาย
            </button>
        </li>
    <?php endif; ?>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="dashboardTabContent">
    <!-- DO Tab Content -->
    <div class="tab-pane fade show active" id="do" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> งานประจำวันนี้</h5>
                <button class="btn btn-primary btn-sm" onclick="refreshTasks()">
                    <i class="fas fa-sync-alt"></i> รีเฟรช
                </button>
            </div>
            <div class="card-body">
                <div class="text-center py-4" id="do-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                </div>
                <div id="do-content"></div>
            </div>
        </div>
    </div>

    <!-- New Customers Tab Content -->
    <div class="tab-pane fade" id="new-customers" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-plus"></i> ลูกค้าใหม่</h5>
                <div>
                    <button class="btn btn-primary btn-sm me-2" onclick="refreshCustomers('new')">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                    <button class="btn btn-success btn-sm" onclick="addNewCustomer()">
                        <i class="fas fa-plus"></i> เพิ่มลูกค้าใหม่
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="new-search" placeholder="ค้นหาลูกค้า..." onkeyup="searchCustomers('new')">
                </div>
                <div class="text-center py-4" id="new-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                </div>
                <div id="new-content"></div>
            </div>
        </div>
    </div>

    <!-- Follow Customers Tab Content -->
    <div class="tab-pane fade" id="follow-customers" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-check"></i> ลูกค้าติดตาม</h5>
                <button class="btn btn-primary btn-sm" onclick="refreshCustomers('follow')">
                    <i class="fas fa-sync-alt"></i> รีเฟรช
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="follow-search" placeholder="ค้นหาลูกค้า..." onkeyup="searchCustomers('follow')">
                </div>
                <div class="text-center py-4" id="follow-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                </div>
                <div id="follow-content"></div>
            </div>
        </div>
    </div>

    <!-- Old Customers Tab Content -->
    <div class="tab-pane fade" id="old-customers" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users"></i> ลูกค้าเก่า</h5>
                <button class="btn btn-primary btn-sm" onclick="refreshCustomers('old')">
                    <i class="fas fa-sync-alt"></i> รีเฟรช
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="old-search" placeholder="ค้นหาลูกค้า..." onkeyup="searchCustomers('old')">
                </div>
                <div class="text-center py-4" id="old-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                </div>
                <div id="old-content"></div>
            </div>
        </div>
    </div>

    <!-- Follow All Tab Content -->
    <div class="tab-pane fade" id="follow-all" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Follow ทั้งหมด</h5>
                <div>
                    <input type="date" class="form-control d-inline-block me-2" id="task-date-filter" onchange="filterTasksByDate()" style="width: auto;">
                    <button class="btn btn-primary btn-sm" onclick="refreshAllTasks()">
                        <i class="fas fa-sync-alt"></i> รีเฟรช
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="text-center py-4" id="follow-all-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                </div>
                <div id="follow-all-content"></div>
            </div>
        </div>
    </div>
            
            <?php if ($user_role === 'admin' || $user_role === 'manager'): ?>
            <!-- Unassigned Customers Tab Content -->
            <div class="tab-pane fade" id="unassigned" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-clock"></i> รอมอบหมาย</h5>
                        <div>
                            <button class="btn btn-success btn-sm me-2" onclick="bulkAssign()">
                                <i class="fas fa-users"></i> มอบหมายแบบกลุ่ม
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="refreshUnassigned()">
                                <i class="fas fa-sync-alt"></i> รีเฟรช
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning border-start border-warning border-4 mb-3">
                            <strong><i class="fas fa-crown"></i> Admin/Manager:</strong> ลูกค้าเหล่านี้ยังไม่ได้มอบหมายให้ Sales ดูแล กรุณาเลือกและมอบหมายให้เหมาะสม
                        </div>
                        <div class="text-center py-4" id="unassigned-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                        </div>
                        <div id="unassigned-content"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php
$content = ob_get_clean();

// Set global variables for layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// Additional CSS for better table presentation
$additionalCSS = '
    <style>
    .task-details {
        line-height: 1.4;
    }
    
    .table td {
        vertical-align: middle;
        padding: 12px 8px;
    }
    
    .btn-group-vertical .btn {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    .table th[style*="width"] {
        white-space: nowrap;
    }
    
    @media (max-width: 768px) {
        .table th:nth-child(6),
        .table td:nth-child(6) {
            display: none;
        }
        
        .table th[style*="width"] {
            width: auto !important;
        }
    }
    </style>
';

// Additional JavaScript with real data integration
$additionalJS = '
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
    // Enhanced Dashboard with Summary Cards integration
    document.addEventListener("DOMContentLoaded", function() {
        loadDashboardSummary();
    });

    async function loadDashboardSummary() {
        try {
            // Load summary statistics from multiple sources
            const [customersResponse, tasksResponse, tempResponse, gradeResponse] = await Promise.all([
                fetch("../api/customers/list.php"),
                fetch("../api/tasks/daily.php"),
                fetch("../api/customers/intelligence.php?action=temperatures"),
                fetch("../api/customers/intelligence.php?action=grades")
            ]);
            
            const customersData = await customersResponse.json();
            const tasksData = await tasksResponse.json();
            const tempData = await tempResponse.json();
            const gradeData = await gradeResponse.json();
            
            // Update summary cards
            if (customersData.status === "success") {
                document.getElementById("totalCustomers").textContent = customersData.total || 0;
            }
            
            if (tasksData.status === "success") {
                document.getElementById("todayTasks").textContent = tasksData.data?.length || 0;
            }
            
            // Count HOT customers
            let hotCount = 0;
            if (tempData.status === "success" && tempData.data) {
                const hotData = tempData.data.find(t => t.CustomerTemperature === "HOT");
                hotCount = hotData ? hotData.count : 0;
            }
            document.getElementById("hotCustomers").textContent = hotCount;
            
            // Count Grade A customers
            let gradeACount = 0; 
            if (gradeData.status === "success" && gradeData.data) {
                const gradeAData = gradeData.data.find(g => g.CustomerGrade === "A");
                gradeACount = gradeAData ? gradeAData.count : 0;
            }
            document.getElementById("gradeACustomers").textContent = gradeACount;
            
        } catch (error) {
            console.error("Error loading dashboard summary:", error);
            // Set fallback values
            document.getElementById("totalCustomers").textContent = "-";
            document.getElementById("todayTasks").textContent = "-";
            document.getElementById("hotCustomers").textContent = "-";
            document.getElementById("gradeACustomers").textContent = "-";
        }
    }
    </script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>