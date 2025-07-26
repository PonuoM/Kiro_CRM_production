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
        รายการและสถิติการขายของคุณและทีม (Static Version)
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
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-primary bg-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-primary mb-1">4</h3>
                        <p class="card-text text-muted mb-0">ยอดขายทั้งหมด</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x text-primary opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-success bg-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-success mb-1">0</h3>
                        <p class="card-text text-muted mb-0">ยอดขายวันนี้</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x text-success opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-info bg-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-info mb-1">4</h3>
                        <p class="card-text text-muted mb-0">ยอดขายเดือนนี้</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x text-info opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-warning bg-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="card-title text-warning mb-1">75,590.00 ฿</h3>
                        <p class="card-text text-muted mb-0">มูลค่ารวม (บาท)</p>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-warning opacity-75"></i>
                    </div>
                </div>
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
                <tbody>
                    <!-- OrderID 9: DOC202507242128474465 -->
                    <tr>
                        <td><strong>DOC202507242128474465</strong></td>
                        <td>24 ก.ค. 2025</td>
                        <td>
                            <strong>บริษัท เก่าแก่ มั่นคง จำกัด</strong>
                            <br><small class="text-muted">TEST011</small>
                            <br><small><i class="fas fa-phone"></i> 02-111-1011</small>
                        </td>
                        <td>
                            <strong>ปุ๋ยน้ำ สูตร 4-24-24</strong>
                            <br><small class="text-muted">จำนวน: 1 | ราคา: 35.00 ฿</small>
                        </td>
                        <td><strong class="text-success">35.00 ฿</strong></td>
                        <td><span class="badge bg-success">เสร็จสิ้น</span></td>
                        <td><span class="badge bg-info">sales01</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="ดูข้อมูลลูกค้า">
                                    <i class="fas fa-user"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="alert('โทรหา 02-111-1011')" title="โทรหาลูกค้า">
                                    <i class="fas fa-phone"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- OrderID 10: DOC202507242131172211 -->
                    <tr>
                        <td><strong>DOC202507242131172211</strong></td>
                        <td>24 ก.ค. 2025</td>
                        <td>
                            <strong>ห้างหุ้นส่วน ทรัพย์เจริญ</strong>
                            <br><small class="text-muted">TEST007</small>
                            <br><small><i class="fas fa-phone"></i> 08-7777-7777</small>
                        </td>
                        <td>
                            <strong>ปุ๋ยเคมี สูตร 15-15-15</strong>
                            <br><small class="text-muted">จำนวน: 1 | ราคา: 55.00 ฿</small>
                        </td>
                        <td><strong class="text-success">55.00 ฿</strong></td>
                        <td><span class="badge bg-success">เสร็จสิ้น</span></td>
                        <td><span class="badge bg-info">sales01</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="ดูข้อมูลลูกค้า">
                                    <i class="fas fa-user"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="alert('โทรหา 08-7777-7777')" title="โทรหาลูกค้า">
                                    <i class="fas fa-phone"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- OrderID 4: TEST-ORD-001 -->
                    <tr>
                        <td><strong>TEST-ORD-001</strong></td>
                        <td>22 ก.ค. 2025</td>
                        <td>
                            <strong>บริษัท เก่าแก่ มั่นคง จำกัด</strong>
                            <br><small class="text-muted">TEST011</small>
                            <br><small><i class="fas fa-phone"></i> 02-111-1011</small>
                        </td>
                        <td>
                            <strong>TEST: ชุดสินค้า Premium Package A</strong>
                            <br><small class="text-muted">จำนวน: 2 | ราคา: 50,000.00 ฿</small>
                        </td>
                        <td><strong class="text-success">50,000.00 ฿</strong></td>
                        <td><span class="badge bg-success">เสร็จสิ้น</span></td>
                        <td><span class="badge bg-info">sales01</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="ดูข้อมูลลูกค้า">
                                    <i class="fas fa-user"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="alert('โทรหา 02-111-1011')" title="โทรหาลูกค้า">
                                    <i class="fas fa-phone"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- OrderID 6: TEST-ORD-003 -->
                    <tr>
                        <td><strong>TEST-ORD-003</strong></td>
                        <td>20 ก.ค. 2025</td>
                        <td>
                            <strong>บริษัท ทดสอบ 6 จำกัด</strong>
                            <br><small class="text-muted">TEST006</small>
                            <br><small><i class="fas fa-phone"></i> 06-666-6666</small>
                        </td>
                        <td>
                            <strong>TEST: Advanced Package C</strong>
                            <br><small class="text-muted">จำนวน: 1 | ราคา: 25,500.00 ฿</small>
                        </td>
                        <td><strong class="text-success">25,500.00 ฿</strong></td>
                        <td><span class="badge bg-success">เสร็จสิ้น</span></td>
                        <td><span class="badge bg-info">sales01</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" title="ดูข้อมูลลูกค้า">
                                    <i class="fas fa-user"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="alert('โทรหา 06-666-6666')" title="โทรหาลูกค้า">
                                    <i class="fas fa-phone"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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

// Simple JavaScript - NO complex template literals
$simpleJS = '
<script>
console.log("Static customer list loaded successfully");
document.addEventListener("DOMContentLoaded", function() {
    console.log("Page ready!");
});

function callCustomer(phone) {
    if (confirm("โทรหา " + phone + " หรือไม่?")) {
        window.open("tel:" + phone, "_self");
    }
}
</script>
';

// Render the page
echo renderMainLayout($pageTitle, $content, '', $simpleJS);
?>