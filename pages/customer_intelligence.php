<?php
require_once '../includes/main_layout.php';

// Check login and permissions
Permissions::requireLogin('login.php');
Permissions::requirePermission('customer_list', 'login.php');

$pageTitle = "Customer Intelligence System";

// Get user information
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();
$canManageCustomers = Permissions::hasPermission('manage_customers');
$canManageUsers = Permissions::hasPermission('manage_users');

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-brain"></i>
        Customer Intelligence System
    </h1>
    <p class="page-description">
        ระบบวิเคราะห์ความฉลาดทางธุรกิจของลูกค้า
    </p>
</div>

<!-- Admin Controls (Admin/Manager only) -->
<?php if ($canManageUsers): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-tools"></i> Admin Controls
        </h5>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3">
            ใช้สำหรับอัปเดต Grade และ Temperature ของลูกค้าทั้งหมดตามข้อมูลล่าสุด
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <button id="update-all-grades" class="btn btn-warning">
                <i class="fas fa-chart-bar"></i> อัปเดต Grade ทั้งหมด
            </button>
            <button id="update-all-temperatures" class="btn btn-info">
                <i class="fas fa-thermometer-half"></i> อัปเดต Temperature ทั้งหมด
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Intelligence Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-filter"></i> Customer Filters
        </h5>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <label class="form-label fw-bold">Customer Grade (ระดับลูกค้า)</label>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-success filter-btn grade-filter" data-grade="A">
                    Grade A - VIP (≥10,000฿)
                </button>
                <button class="btn btn-outline-primary filter-btn grade-filter" data-grade="B">
                    Grade B - Premium (5,000-9,999฿)
                </button>
                <button class="btn btn-outline-warning filter-btn grade-filter" data-grade="C">
                    Grade C - Regular (2,000-4,999฿)
                </button>
                <button class="btn btn-outline-secondary filter-btn grade-filter" data-grade="D">
                    Grade D - New (<2,000฿)
                </button>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Customer Temperature (อุณหภูมิลูกค้า)</label>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-danger filter-btn temperature-filter" data-temperature="HOT">
                    <i class="fas fa-fire"></i> HOT - Ready to Buy
                </button>
                <button class="btn btn-outline-warning filter-btn temperature-filter" data-temperature="WARM">
                    <i class="fas fa-sun"></i> WARM - In Progress
                </button>
                <button class="btn btn-outline-info filter-btn temperature-filter" data-temperature="COLD">
                    <i class="fas fa-snowflake"></i> COLD - Need Attention
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <button id="clear-filters" class="btn btn-outline-secondary">
                <i class="fas fa-undo"></i> Clear All Filters
            </button>
            <div id="filter-status" class="text-muted">
                No filters active
            </div>
        </div>
    </div>
</div>

        <!-- Intelligence Dashboard -->
        <div class="row">
            <div class="col-md-6">
                <div id="grade-distribution">
                    <div class="intelligence-loading">Loading grade distribution...</div>
                </div>
            </div>
            <div class="col-md-6">
                <div id="temperature-distribution">
                    <div class="intelligence-loading">Loading temperature distribution...</div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div id="top-customers">
            <div class="intelligence-loading">Loading top customers...</div>
        </div>

        <!-- Filtered Customers Results -->
        <div id="filtered-customers">
            <!-- This will be populated when filters are applied -->
        </div>

        <!-- Intelligence Summary (Admin/Manager only) -->
        <?php if ($canManageCustomers): ?>
        <div class="intelligence-section">
            <h3>📈 Intelligence Summary</h3>
            <div id="intelligence-summary">
                <div class="intelligence-loading">Loading summary...</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Intelligence Help -->
        <div class="intelligence-section">
            <h3>❓ How Customer Intelligence Works</h3>
            <div class="help-content">
                <div class="row">
                    <div class="col-md-6">
                        <h4>📊 Customer Grading System</h4>
                        <ul>
                            <li><strong>Grade A (VIP):</strong> ลูกค้ายอดซื้อ ≥ 10,000 บาท</li>
                            <li><strong>Grade B (Premium):</strong> ลูกค้ายอดซื้อ 5,000-9,999 บาท</li>
                            <li><strong>Grade C (Regular):</strong> ลูกค้ายอดซื้อ 2,000-4,999 บาท</li>
                            <li><strong>Grade D (New):</strong> ลูกค้ายอดซื้อ < 2,000 บาท</li>
                        </ul>
                        <p><em>Grade จะอัปเดตอัตโนมัติเมื่อลูกค้าสั่งซื้อสินค้า</em></p>
                    </div>
                    <div class="col-md-6">
                        <h4>🌡️ Customer Temperature System</h4>
                        <ul>
                            <li><strong>🔥 HOT:</strong> ลูกค้าใหม่, สถานะ "คุยจบ", ติดต่อภายใน 7 วัน</li>
                            <li><strong>☀️ WARM:</strong> ลูกค้าทั่วไปในกระบวนการติดตาม</li>
                            <li><strong>❄️ COLD:</strong> สถานะ "ไม่สนใจ" หรือติดต่อไม่ได้ >2 ครั้ง</li>
                        </ul>
                        <p><em>Temperature จะอัปเดตอัตโนมัติเมื่อมีการโทรหรือติดต่อลูกค้า</em></p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h4>💡 การใช้งาน Intelligence System</h4>
                    <ol>
                        <li><strong>ใช้ Filters:</strong> เลือก Grade และ Temperature เพื่อหาลูกค้าที่ต้องการ</li>
                        <li><strong>Priority Management:</strong> มุ่งเน้นลูกค้า Grade A + HOT ก่อน</li>
                        <li><strong>Follow-up Strategy:</strong> ลูกค้า COLD ต้องการวิธีการติดต่อใหม่</li>
                        <li><strong>Upselling:</strong> ลูกค้า Grade B + HOT มีโอกาสขึ้นเป็น Grade A</li>
                        <li><strong>Regular Updates:</strong> ให้ Admin อัปเดตข้อมูล Intelligence เป็นประจำ</li>
                    </ol>
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

// Set global variables for layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// Additional CSS
$additionalCSS = '
    <link rel="stylesheet" href="../assets/css/customer-intelligence.css">
';

// Additional JavaScript
$additionalJS = '
    <script src="../assets/js/customer-intelligence.js"></script>
    <script>
        // Add Bootstrap-like grid classes if not available
        if (!document.querySelector(\'.row\')) {
            const style = document.createElement(\'style\');
            style.textContent = `
                .row { display: flex; flex-wrap: wrap; margin: -10px; }
                .col-md-6 { flex: 0 0 50%; padding: 10px; }
                .col-md-4 { flex: 0 0 33.333%; padding: 10px; }
                .col-md-12 { flex: 0 0 100%; padding: 10px; }
                @media (max-width: 768px) {
                    .col-md-6, .col-md-4 { flex: 0 0 100%; }
                }
                .mt-3 { margin-top: 1rem; }
                .help-content ul { padding-left: 20px; }
                .help-content li { margin-bottom: 5px; }
                .help-content h4 { color: #495057; margin-bottom: 10px; }
                .help-content ol { padding-left: 20px; }
                .help-content ol li { margin-bottom: 8px; }
            `;
            document.head.appendChild(style);
        }
    </script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, $additionalCSS, $additionalJS);
?>