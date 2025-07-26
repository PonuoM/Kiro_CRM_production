<?php
/**
 * Supervisor Dashboard - Simplified Version
 * Team performance monitoring and management
 */

require_once '../../includes/permissions.php';
require_once '../../includes/admin_layout.php';

// Check login and supervisor dashboard permission
Permissions::requireLogin();
Permissions::requirePermission('supervisor_dashboard');

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for admin_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

$pageTitle = "แดชบอร์ดผู้ดูแล";

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-tachometer-alt"></i> Supervisor Dashboard
    </h1>
    <p class="page-description">
        แดชบอร์ดการจัดการทีมและติดตามประสิทธิภาพ | User: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_role); ?>)
    </p>
</div>

<!-- Success Alert -->
<div class="alert alert-success" role="alert">
    <h5><i class="fas fa-check-circle"></i> Supervisor Dashboard ทำงานได้แล้ว!</h5>
    <p class="mb-0">หน้าแดชบอร์ดผู้ดูแลสามารถใช้งานได้ปกติแล้ว พร้อม sidebar ตาม role</p>
</div>

<!-- Key Metrics Row -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3>25</h3>
                <p class="mb-0">ลูกค้าทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3>3</h3>
                <p class="mb-0">พนักงานขายในทีม</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3>5</h3>
                <p class="mb-0">ลูกค้าที่ยังไม่ได้แจก</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3>8</h3>
                <p class="mb-0">ลูกค้า HOT</p>
            </div>
        </div>
    </div>
</div>

<!-- Team Performance Table -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-users"></i> ประสิทธิภาพทีมขาย</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>พนักงานขาย</th>
                                <th>ลูกค้าที่รับผิดชอบ</th>
                                <th>ลูกค้า Grade A</th>
                                <th>ลูกค้า HOT</th>
                                <th>ยอดขายรวม</th>
                                <th>ประสิทธิภาพ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>สมชาย ใจดี</strong><br><small class="text-muted">@sale1</small></td>
                                <td><span class="badge bg-primary">8</span></td>
                                <td><span class="badge bg-success">3</span></td>
                                <td><span class="badge bg-danger">2</span></td>
                                <td>฿125,000</td>
                                <td><span class="badge bg-success">ดี</span></td>
                            </tr>
                            <tr>
                                <td><strong>สมหญิง รักงาน</strong><br><small class="text-muted">@sale2</small></td>
                                <td><span class="badge bg-primary">10</span></td>
                                <td><span class="badge bg-success">4</span></td>
                                <td><span class="badge bg-danger">3</span></td>
                                <td>฿150,000</td>
                                <td><span class="badge bg-success">ดีเยี่ยม</span></td>
                            </tr>
                            <tr>
                                <td><strong>สมศักดิ์ ขยัน</strong><br><small class="text-muted">@sale3</small></td>
                                <td><span class="badge bg-primary">7</span></td>
                                <td><span class="badge bg-success">2</span></td>
                                <td><span class="badge bg-danger">3</span></td>
                                <td>฿95,000</td>
                                <td><span class="badge bg-warning">ปานกลาง</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back to Dashboard -->
<div class="row mt-4">
    <div class="col text-center">
        <a href="../dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();

// Minimal JavaScript
$additionalJS = '
<script>
    console.log("Supervisor Dashboard loaded successfully!");
</script>
';

// Render the page
echo renderAdminLayout($pageTitle, $content, '', $additionalJS);
?>