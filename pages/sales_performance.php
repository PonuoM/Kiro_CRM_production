<?php
/**
 * Sales Performance Report - Integrated with Permissions System
 * Shows sales performance metrics and analytics
 */

require_once '../includes/permissions.php';
require_once '../includes/main_layout.php';

// Check login and permission
Permissions::requireLogin();
Permissions::requirePermission('sales_performance');

$pageTitle = "รายงานประสิทธิภาพการขาย";

// Get user information for layout
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

// Set globals for main_layout
$GLOBALS['currentUser'] = $user_name;
$GLOBALS['currentRole'] = $user_role;
$GLOBALS['menuItems'] = $menuItems;

// Page content
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-chart-line"></i>
        รายงานประสิทธิภาพการขาย
    </h1>
    <p class="page-description">
        ดูประสิทธิภาพการขายและการวิเคราะห์ข้อมูลตามสิทธิ์การเข้าถึง
    </p>
</div>

<!-- Summary Cards -->
<div class="row mb-4" id="summary-cards">
    <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-chart-line me-2" style="font-size: 1.5rem; color: #10B981;"></i>
                    <h3 class="mb-0" style="color: #10B981;" id="total-sales">-</h3>
                </div>
                <p class="mb-0 text-muted">ยอดขายรวม (บาท)</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-shopping-cart me-2" style="font-size: 1.5rem; color: #3B82F6;"></i>
                    <h3 class="mb-0" style="color: #3B82F6;" id="total-orders">-</h3>
                </div>
                <p class="mb-0 text-muted">จำนวนคำสั่งซื้อ</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-percentage me-2" style="font-size: 1.5rem; color: #8B5CF6;"></i>
                    <h3 class="mb-0" style="color: #8B5CF6;" id="avg-conversion">-</h3>
                </div>
                <p class="mb-0 text-muted">อัตราการแปลงเฉลี่ย</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-body text-center">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="fas fa-users me-2" style="font-size: 1.5rem; color: #F59E0B;"></i>
                    <h3 class="mb-0" style="color: #F59E0B;" id="active-sales">-</h3>
                </div>
                <p class="mb-0 text-muted">พนักงานขายที่ทำงาน</p>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row mb-4">
    <div class="col">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0"><i class="fas fa-filter"></i> กรองข้อมูล</h5>
            </div>
            <div class="card-body">
                <form id="filter-form" class="row g-3">
                    <div class="col-md-4">
                        <label for="date-from" class="form-label">จากวันที่</label>
                        <input type="date" class="form-control" id="date-from" name="date_from">
                    </div>
                    <div class="col-md-4">
                        <label for="date-to" class="form-label">ถึงวันที่</label>
                        <input type="date" class="form-control" id="date-to" name="date_to">
                    </div>
                    <div class="col-md-4">
                        <label for="sales-filter" class="form-label">พนักงานขาย</label>
                        <select class="form-select" id="sales-filter" name="sales_name">
                            <option value="">ทั้งหมด</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetFilters()">
                            <i class="fas fa-refresh"></i> รีเซ็ต
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> ส่งออก CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Performance Table -->
<div class="row mb-4">
    <div class="col">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users"></i> ประสิทธิภาพการขายรายบุคคล</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshData()">
                        <i class="fas fa-refresh"></i> รีเฟรชข้อมูล
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>พนักงานขาย</th>
                                <th class="text-center">ลูกค้าที่ได้รับมอบหมาย</th>
                                <th class="text-center">ลูกค้าที่แปลงสถานะ</th>
                                <th class="text-center">จำนวนคำสั่งซื้อ</th>
                                <th class="text-center">ยอดขายรวม</th>
                                <th class="text-center">ยอดขายเฉลี่ย</th>
                                <th class="text-center">อัตราการแปลง (%)</th>
                                <th class="text-center">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="performance-table-body">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                        <div class="mt-2 text-muted">กำลังโหลดข้อมูลประสิทธิภาพการขาย...</div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Team Summary -->
<div class="row mb-4">
    <div class="col">
        <div class="card bg-white shadow-sm border-0">
            <div class="card-header bg-transparent border-0">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> สรุปประสิทธิภาพทีม</h5>
            </div>
            <div class="card-body">
                <div id="team-stats" class="row text-center">
                    <div class="col-md-2">
                        <div class="py-3">
                            <div class="h4 text-primary mb-1">-</div>
                            <small class="text-muted">ลูกค้าทั้งหมด</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="py-3">
                            <div class="h4 text-success mb-1">-</div>
                            <small class="text-muted">ลูกค้าที่แปลงสถานะ</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="py-3">
                            <div class="h4 text-info mb-1">-</div>
                            <small class="text-muted">คำสั่งซื้อทั้งหมด</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="py-3">
                            <div class="h4 text-warning mb-1">-</div>
                            <small class="text-muted">ยอดขายรวม</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="py-3">
                            <div class="h4 text-primary mb-1">-</div>
                            <small class="text-muted">อัตราการแปลงเฉลี่ย</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="py-3">
                            <div class="h4 text-secondary mb-1">-</div>
                            <small class="text-muted">สมาชิกในทีม</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Detail Modal -->
<div id="sales-detail-modal" class="modal" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดการมอบหมายลูกค้า</h5>
                <button type="button" class="btn-close" onclick="closeSalesDetailModal()"></button>
            </div>
            <div class="modal-body">
                <div id="sales-detail-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSalesDetailModal()">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Back to Dashboard -->
<div class="row mt-4">
    <div class="col text-center">
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left"></i> กลับหน้าแดชบอร์ด
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for sales performance
$additionalJS = '
<script src="../assets/js/sales-performance.js"></script>
<style>
/* Custom styles for Sales Performance */
.summary-card {
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.summary-card.primary {
    border-left-color: #10B981;
}

.summary-card.success {
    border-left-color: #3B82F6;
}

.summary-card.info {
    border-left-color: #8B5CF6;
}

.summary-card.warning {
    border-left-color: #F59E0B;
}

.summary-value {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.summary-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.conversion-rate.high {
    color: #10B981;
    font-weight: bold;
    background-color: rgba(16, 185, 129, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.conversion-rate.medium {
    color: #F59E0B;
    font-weight: bold;
    background-color: rgba(245, 158, 11, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.conversion-rate.low {
    color: #EF4444;
    font-weight: bold;
    background-color: rgba(239, 68, 68, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.modal {
    background-color: rgba(0,0,0,0.5);
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1050;
}

.modal-dialog {
    margin: 3rem auto;
    max-width: 900px;
}

.performance-table {
    width: 100%;
    margin-top: 1rem;
}

.performance-table th,
.performance-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #E5E7EB;
}

.performance-table th {
    background: linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%);
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #E5E7EB;
}

.performance-table tbody tr:hover {
    background-color: #F9FAFB;
    transition: background-color 0.2s ease;
}

.number {
    text-align: center !important;
}

.percentage {
    text-align: center !important;
}

.team-stat {
    text-align: center;
    padding: 1rem;
}

.team-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.team-stat-label {
    font-size: 0.8rem;
    color: #6c757d;
}

.empty-state, .error-state {
    padding: 3rem 1rem;
    text-align: center;
}

.empty-state-icon, .error-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.empty-state-title, .error-state-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #495057;
}

.empty-state-message, .error-state-message {
    color: #6c757d;
}

/* Enhanced button styles */
.btn-primary {
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-outline-primary {
    border-color: #3B82F6;
    color: #3B82F6;
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background-color: #3B82F6;
    border-color: #3B82F6;
    transform: translateY(-1px);
}

.btn-outline-success {
    border-color: #10B981;
    color: #10B981;
    transition: all 0.3s ease;
}

.btn-outline-success:hover {
    background-color: #10B981;
    border-color: #10B981;
    transform: translateY(-1px);
}

.btn-info {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
    border: none;
}

.btn-info:hover {
    background: linear-gradient(135deg, #7C3AED 0%, #6D28D9 100%);
}
</style>
<script>
// Initialize Sales Performance
document.addEventListener("DOMContentLoaded", function() {
    const currentUser = "' . addslashes($user_name) . '";
    const userRole = "' . addslashes($user_role) . '";
    
    // Create instance
    window.salesPerformance = new SalesPerformance(currentUser, userRole);
    window.salesPerformanceInstance = window.salesPerformance;
});
</script>
';

// Render the page using main layout
echo renderMainLayout($pageTitle, $content, $additionalJS);
?>