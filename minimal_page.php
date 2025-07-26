<?php
// Minimal working page
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/main_layout.php';

Permissions::requireLogin();
Permissions::requirePermission('customer_list');

$pageTitle = "รายการขาย (ทดสอบ)";
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// Simple content without complex JavaScript
$content = '
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-chart-line"></i>
        รายการขาย (หน้าทดสอบ)
    </h1>
    <p class="page-description">
        หน้าทดสอบการทำงานของระบบ
    </p>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-primary bg-white">
            <div class="card-body">
                <h3 class="card-title text-primary mb-1">4</h3>
                <p class="card-text text-muted mb-0">ยอดขายทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-success bg-white">
            <div class="card-body">
                <h3 class="card-title text-success mb-1">0</h3>
                <p class="card-text text-muted mb-0">ยอดขายวันนี้</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-info bg-white">
            <div class="card-body">
                <h3 class="card-title text-info mb-1">4</h3>
                <p class="card-text text-muted mb-0">ยอดขายเดือนนี้</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm border border-warning bg-white">
            <div class="card-body">
                <h3 class="card-title text-warning mb-1">75,590.00 ฿</h3>
                <p class="card-text text-muted mb-0">มูลค่ารวม</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-chart-line"></i> รายการขายของคุณ
        </h5>
    </div>
    <div class="card-body">
        <p>🎉 <strong>หน้าทดสอบทำงานได้!</strong></p>
        <p>หาก KPI cards แสดงเป็นสีขาวขอบสี แสดงว่าระบบพร้อมใช้งาน</p>
        <div class="alert alert-success">
            <strong>ข้อมูลจาก API:</strong> ยอดขาย 4 รายการ มูลค่า 75,590 บาท
        </div>
    </div>
</div>
';

// Simple JavaScript without template literals
$simpleJS = '
<script>
console.log("Page loaded successfully");
document.addEventListener("DOMContentLoaded", function() {
    console.log("DOM ready - everything works!");
});
</script>
';

echo renderMainLayout($pageTitle, $content, '', $simpleJS);
?>