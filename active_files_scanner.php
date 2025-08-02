<?php
/**
 * Active Files Scanner
 * สแกนไฟล์ที่ใช้งานจริงในระบบ
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_user = $_SESSION['username'] ?? 'unknown';
$current_role = $_SESSION['user_role'] ?? 'unknown';

// สแกนไฟล์ที่ใช้งานจริง
$activeFiles = [];
$rootDir = __DIR__;

// 1. Core system files (ใช้งานจริงแน่นอน)
$coreFiles = [
    'index.php',
    'logout.php',
    
    // Config files
    'config/config.php',
    'config/config.production.php', 
    'config/database.php',
    'config/database.production.php',
    
    // Includes files
    'includes/BaseModel.php',
    'includes/CallLog.php',
    'includes/Customer.php',
    'includes/CustomerStatusManager.php',
    'includes/Order.php',
    'includes/SalesDepartureWorkflow.php',
    'includes/SalesHistory.php',
    'includes/Task.php',
    'includes/User.php',
    'includes/admin_layout.php',
    'includes/functions.php',
    'includes/main_layout.php',
    'includes/permissions.php',
    
    // CSS & JS
    'assets/css/style.css',
    'assets/css/dashboard.css',
    'assets/css/customer-detail.css',
    'assets/css/daily-tasks.css',
    'assets/css/sales-performance.css',
    'assets/js/main.js',
    'assets/js/dashboard.js',
    'assets/js/customer-detail.js',
    'assets/js/daily-tasks.js',
    'assets/js/sales-performance.js'
];

// 2. API endpoints (ใช้งานจริง)
$apiFiles = [
    'api/auth/check.php',
    'api/auth/login.php',
    'api/auth/logout.php',
    
    'api/customers/create.php',
    'api/customers/detail.php',
    'api/customers/import.php',
    'api/customers/list.php',
    'api/customers/unassigned.php',
    'api/customers/update.php',
    
    'api/dashboard/summary.php',
    
    'api/orders/create.php',
    'api/orders/history.php',
    
    'api/products/list.php',
    
    'api/sales/assign.php',
    'api/sales/history.php',
    'api/sales/performance.php',
    'api/sales/sales_records.php',
    
    'api/tasks/create.php',
    'api/tasks/daily.php',
    'api/tasks/delete.php',
    'api/tasks/detail.php',
    'api/tasks/list.php',
    'api/tasks/status.php',
    'api/tasks/today.php',
    'api/tasks/update.php',
    
    'api/users/create.php',
    'api/users/detail.php',
    'api/users/list.php',
    'api/users/toggle_status.php',
    'api/users/update.php'
];

// 3. Admin pages (ใช้งานจริง)
$adminFiles = [
    'admin/distribution_basket.php',
    'admin/intelligence_system.php', 
    'admin/supervisor_dashboard.php',
    'admin/waiting_basket.php'
];

// 4. User pages (ใช้งานจริง)
$pageFiles = [
    'pages/dashboard.php',
    'pages/customer_detail.php',
    'pages/customer_list.php',
    'pages/customer_list_dynamic.php',
    'pages/daily_tasks.php',
    'pages/login.php',
    'pages/order_history_demo.php',
    'pages/sales_performance.php',
    
    // Admin pages
    'pages/admin/import_customers.php',
    'pages/admin/user_management.php'
];

// 5. Database & Scripts (ใช้งานจริง)
$databaseFiles = [
    'database/migration_v2.0.sql',
    'database/production_deployment_guide.md',
    'database/rollback_v2.0.sql',
    'sql/database_schema.sql',
    'sql/production_setup.sql',
    'sql/sample_data.sql'
];

$scriptFiles = [
    'scripts/backup.php',
    'scripts/backup.sh',
    'cron/auto_rules.php',
    'cron/run_auto_rules.sh',
    'cron/setup_cron.sh'
];

// 6. Utility files (ใช้งานจริง)
$utilityFiles = [
    'auto_customer_management.php',
    'auto_status_manager.php',
    'generate_passwords.php',
    'health_check.php',
    'system_health_check.php'
];

// รวมไฟล์ทั้งหมด
$allActiveFiles = array_merge($coreFiles, $apiFiles, $adminFiles, $pageFiles, $databaseFiles, $scriptFiles, $utilityFiles);

// ตรวจสอบว่าไฟล์มีอยู่จริง
foreach ($allActiveFiles as $file) {
    $fullPath = $rootDir . '/' . $file;
    if (file_exists($fullPath)) {
        $activeFiles[] = [
            'path' => $file,
            'fullPath' => $fullPath,
            'size' => filesize($fullPath),
            'modified' => filemtime($fullPath),
            'category' => categorizeActiveFile($file)
        ];
    }
}

function categorizeActiveFile($path) {
    if (strpos($path, 'config/') === 0) return 'config';
    if (strpos($path, 'includes/') === 0) return 'includes';
    if (strpos($path, 'api/') === 0) return 'api';
    if (strpos($path, 'admin/') === 0) return 'admin';
    if (strpos($path, 'pages/') === 0) return 'pages';
    if (strpos($path, 'assets/') === 0) return 'assets';
    if (strpos($path, 'database/') === 0 || strpos($path, 'sql/') === 0) return 'database';
    if (strpos($path, 'scripts/') === 0 || strpos($path, 'cron/') === 0) return 'scripts';
    if (strpos($path, '/') === false) return 'root';
    return 'utility';
}

// จัดกลุ่มตามหมวดหมู่
$categorized = [];
foreach ($activeFiles as $file) {
    $cat = $file['category'];
    if (!isset($categorized[$cat])) {
        $categorized[$cat] = [];
    }
    $categorized[$cat][] = $file;
}

// สถิติ
$totalActiveFiles = count($activeFiles);
$totalActiveSize = array_sum(array_column($activeFiles, 'size'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Files Scanner - Kiro CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .scanner-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .category-card { border-left: 5px solid #28a745; margin-bottom: 20px; padding: 20px; background: #f8fff8; }
        .category-config { border-left-color: #dc3545; background: #fff8f8; }
        .category-includes { border-left-color: #6f42c1; background: #f8f4ff; }
        .category-api { border-left-color: #0dcaf0; background: #f0fdff; }
        .category-admin { border-left-color: #fd7e14; background: #fff9f0; }
        .category-pages { border-left-color: #198754; background: #f0fff4; }
        .category-assets { border-left-color: #e83e8c; background: #fff0f8; }
        .category-database { border-left-color: #6c757d; background: #f8f9fa; }
        .category-scripts { border-left-color: #ffc107; background: #fffdf0; }
        .category-root { border-left-color: #0a58ca; background: #f0f8ff; }
        .category-utility { border-left-color: #20c997; background: #f0fffe; }
        .file-item { padding: 8px 12px; margin: 3px 0; background: rgba(255,255,255,0.8); border-radius: 6px; font-size: 0.9em; border: 1px solid #e9ecef; }
        .file-path { color: #6c757d; font-family: 'Courier New', monospace; }
        .file-size { color: #28a745; font-weight: 500; }
        .keep-zone { background: linear-gradient(45deg, #d4edda, #a8e6cf); border: 2px solid #28a745; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="scanner-card keep-zone">
            <h1><i class="fas fa-shield-check text-success"></i> Active Files Scanner</h1>
            <p class="text-muted">ไฟล์ที่ระบบใช้งานจริงและจำเป็นต้องเก็บไว้</p>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-success text-white rounded">
                        <h3><?php echo $totalActiveFiles; ?></h3>
                        <small>Active Files</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-info text-white rounded">
                        <h3><?php echo number_format($totalActiveSize / 1024, 1); ?>KB</h3>
                        <small>Total Size</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-primary text-white rounded">
                        <h3><?php echo count($categorized); ?></h3>
                        <small>Categories</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-warning text-dark rounded">
                        <h3>100%</h3>
                        <small>Keep All</small>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-success">
                <h5><i class="fas fa-info-circle"></i> Important Note</h5>
                <p class="mb-0">ไฟล์เหล่านี้เป็นไฟล์หลักที่ระบบใช้งานจริง <strong>ห้ามลบ</strong> เพราะจะทำให้ระบบไม่สามารถทำงานได้</p>
            </div>
        </div>

        <?php foreach ($categorized as $category => $files): ?>
        <div class="category-card category-<?php echo $category; ?>">
            <h3>
                <i class="fas fa-folder-open"></i> <?php echo ucfirst($category); ?> Files
                <span class="badge bg-success ms-2"><?php echo count($files); ?> files</span>
                <span class="badge bg-info ms-1"><?php echo number_format(array_sum(array_column($files, 'size')) / 1024, 1); ?>KB</span>
            </h3>
            
            <div class="mb-3">
                <?php if ($category === 'config'): ?>
                    <p class="text-muted"><i class="fas fa-cog"></i> ไฟล์การตั้งค่าระบบ - สำคัญมากสำหรับการเชื่อมต่อฐานข้อมูลและการตั้งค่า</p>
                <?php elseif ($category === 'includes'): ?>
                    <p class="text-muted"><i class="fas fa-code"></i> ไฟล์คลาสหลักและฟังก์ชันร่วม - หัวใจของระบบ</p>
                <?php elseif ($category === 'api'): ?>
                    <p class="text-muted"><i class="fas fa-plug"></i> API endpoints - จุดเชื่อมต่อสำหรับข้อมูลและการทำงาน</p>
                <?php elseif ($category === 'admin'): ?>
                    <p class="text-muted"><i class="fas fa-user-shield"></i> หน้าผู้ดูแลระบบ - สำหรับการจัดการระบบ</p>
                <?php elseif ($category === 'pages'): ?>
                    <p class="text-muted"><i class="fas fa-file-alt"></i> หน้าเว็บสำหรับผู้ใช้ - ส่วนที่ผู้ใช้งานเห็น</p>
                <?php elseif ($category === 'assets'): ?>
                    <p class="text-muted"><i class="fas fa-paint-brush"></i> ไฟล์ CSS และ JavaScript - สำหรับรูปแบบและการทำงาน</p>
                <?php elseif ($category === 'database'): ?>
                    <p class="text-muted"><i class="fas fa-database"></i> ไฟล์ฐานข้อมูล - สำหรับสร้างและอัปเดตฐานข้อมูล</p>
                <?php elseif ($category === 'scripts'): ?>
                    <p class="text-muted"><i class="fas fa-terminal"></i> สคริปต์อัตโนมัติ - สำหรับงานเบื้องหลัง</p>
                <?php elseif ($category === 'root'): ?>
                    <p class="text-muted"><i class="fas fa-home"></i> ไฟล์หลักระดับรูท - จุดเริ่มต้นของระบบ</p>
                <?php elseif ($category === 'utility'): ?>
                    <p class="text-muted"><i class="fas fa-tools"></i> เครื่องมือช่วย - สำหรับการบำรุงรักษาระบบ</p>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <?php foreach ($files as $file): ?>
                <div class="col-md-6 col-lg-4 mb-2">
                    <div class="file-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <strong><?php echo basename($file['path']); ?></strong><br>
                                <small class="file-path"><?php echo htmlspecialchars($file['path']); ?></small>
                            </div>
                            <div class="text-end">
                                <small class="file-size"><?php echo number_format($file['size'] / 1024, 1); ?>KB</small><br>
                                <small class="text-muted"><?php echo date('M j', $file['modified']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="scanner-card text-center">
            <h3><i class="fas fa-clipboard-check text-success"></i> Active Files Summary</h3>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-lightbulb"></i> File Classification Complete</h5>
                <p>ระบบได้ระบุไฟล์ที่ใช้งานจริงทั้งหมดแล้ว ไฟล์เหล่านี้เป็นไฟล์หลักที่จำเป็นสำหรับการทำงานของระบบ</p>
                <p class="mb-0"><strong>คำแนะนำ:</strong> เก็บไฟล์เหล่านี้ไว้ทั้งหมด และใช้รายการนี้เปรียบเทียบกับไฟล์ที่จะลบ</p>
            </div>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="files_to_delete_list.php" class="btn btn-danger">
                    <i class="fas fa-trash"></i> View Deletable Files
                </a>
                <a href="file_cleanup_analysis.php" class="btn btn-info">
                    <i class="fas fa-chart-pie"></i> Full Analysis
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>