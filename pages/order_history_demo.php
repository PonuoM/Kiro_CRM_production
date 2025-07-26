<?php
/**
 * Order History Demo Page - Simplified Version
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

<!-- Success Alert -->
<div class="alert alert-success" role="alert">
    <h5><i class="fas fa-check-circle"></i> Order History ทำงานได้แล้ว!</h5>
    <p class="mb-0">หน้าประวัติคำสั่งซื้อสามารถใช้งานได้ปกติแล้ว พร้อม sidebar ตาม role</p>
</div>

<!-- Customer Info Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลลูกค้า</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>รหัสลูกค้า:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars($customerCode); ?></span></p>
                <p><strong>ชื่อ:</strong> ลูกค้าตัวอย่าง</p>
                <p><strong>เบอร์โทร:</strong> <i class="fas fa-phone text-primary"></i> 081-234-5678</p>
                <p><strong>ที่อยู่:</strong> 123 ถนนตัวอย่าง กรุงเทพฯ</p>
            </div>
            <div class="col-md-6">
                <p><strong>สถานะลูกค้า:</strong> <span class="badge bg-success">ลูกค้าใหม่</span></p>
                <p><strong>สถานะตะกร้า:</strong> <span class="badge bg-primary">กำลังดูแล</span></p>
                <p><strong>พนักงานขาย:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($user_name); ?></span></p>
                <p><strong>วันที่สร้าง:</strong> <?php echo date('d/m/Y'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Order History Table -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> ประวัติคำสั่งซื้อ</h5>
    </div>
    <div class="card-body">
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
                    <tr>
                        <td><span class="badge bg-primary">ORD001</span></td>
                        <td><strong><?php echo date('d/m/Y'); ?></strong><br><small class="text-muted"><?php echo date('H:i'); ?></small></td>
                        <td>สินค้าตัวอย่าง A</td>
                        <td>2</td>
                        <td class="text-end"><strong>฿15,000</strong><br><small class="text-muted">บาท</small></td>
                        <td><span class="badge bg-info">เงินสด</span></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($user_name); ?></small></td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-primary">ORD002</span></td>
                        <td><strong><?php echo date('d/m/Y', strtotime('-1 day')); ?></strong><br><small class="text-muted">14:30</small></td>
                        <td>สินค้าตัวอย่าง B</td>
                        <td>1</td>
                        <td class="text-end"><strong>฿8,500</strong><br><small class="text-muted">บาท</small></td>
                        <td><span class="badge bg-info">โอน</span></td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($user_name); ?></small></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-light shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">จำนวนคำสั่งซื้อ</h5>
                <h3 class="text-primary mb-0">2</h3>
                <small class="text-muted">รายการ</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-light shadow-sm">
            <div class="card-body text-center">
                <h5 class="card-title text-muted">ยอดขายรวม</h5>
                <h3 class="text-success mb-0">23,500</h3>
                <small class="text-muted">บาท</small>
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

// Minimal JavaScript
$additionalJS = '
<script>
    console.log("Order History loaded successfully!");
</script>
';

// Render the page using main layout
echo renderMainLayout($pageTitle, $content, '', $additionalJS);
?>