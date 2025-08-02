<?php
/**
 * Files Ready for Deletion - Safe Cleanup List
 * รายการไฟล์ที่พร้อมลบได้อย่างปลอดภัย
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

// สร้างรายการไฟล์ที่สามารถลบได้
$deletableFiles = [];

// 1. ไฟล์ใน Deleted folder (ลบได้ทั้งหมด)
function addDeletedFiles(&$deletableFiles) {
    $deletedDir = __DIR__ . '/Deleted';
    if (is_dir($deletedDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($deletedDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $deletableFiles[] = [
                    'path' => $file->getPathname(),
                    'relative' => str_replace(__DIR__ . '/', '', $file->getPathname()),
                    'reason' => 'In Deleted folder - safe to remove',
                    'priority' => 'high',
                    'size' => $file->getSize()
                ];
            }
        }
    }
}

// 2. Debug files (ลบได้เกือบทั้งหมด)
function addDebugFiles(&$deletableFiles) {
    $debugFiles = [
        'debug_call_history.php',
        'debug_call_history_500.php', 
        'debug_call_history_simple.php',
        'debug_cust005.php',
        'debug_cust005_detailed.php',
        'debug_login_api.php',
        'debug_login_direct.php', 
        'debug_sales_assignment.php',
        'debug_simple.php',
        'call_history_demo_debug.php',
        'csrf_debug.php',
        'api/orders/debug.php'
    ];
    
    foreach ($debugFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $deletableFiles[] = [
                'path' => $fullPath,
                'relative' => $file,
                'reason' => 'Debug file - not needed in production',
                'priority' => 'high',
                'size' => filesize($fullPath)
            ];
        }
    }
}

// 3. Test files (ตรวจสอบและลบได้ส่วนใหญ่)
function addTestFiles(&$deletableFiles) {
    $testFiles = [
        'test_menu_removal_complete.php',
        'test_navigation_fixed.php',
        'file_cleanup_analysis.php',
        'files_to_delete_list.php', // ไฟล์นี้เองก็ลบได้หลังใช้เสร็จ
        'api/orders/test_create.php',
        'database/test_migration_setup.php',
        // เพิ่มไฟล์ test อื่นๆ ที่ปลอดภัย
        'test_90_day_logic.php',
        'test_activity_logger.php',
        'test_auto_rules_direct.php',
        'test_auto_rules_manual.php',
        'test_cron_setup.php',
        'test_final.php',
        'test_fixed_auto_rules.php',
        'test_fixed_login.php',
        'test_include_demo.php',
        'test_real_login.php',
        'test_renderMainLayout.php',
        'test_simple_login.php',
        'test_ultimate_login.php',
        'test_working_login.php'
    ];
    
    foreach ($testFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $deletableFiles[] = [
                'path' => $fullPath,
                'relative' => $file,
                'reason' => 'Test file - can be removed after testing complete',
                'priority' => 'medium',
                'size' => filesize($fullPath)
            ];
        }
    }
}

// 4. Backup files ที่ไม่จำเป็น
function addUnnecessaryBackupFiles(&$deletableFiles) {
    $unnecessaryBackups = [
        'pages/call_history_demo_backup.php', // ไฟล์ call history ที่ลบออกแล้ว
        'pages/customer_list_demo_backup.php',
        'pages/customer_list_dynamic_backup.php',
        'pages/order_history_demo_broken_backup.php',
        'pages/admin/import_customers_broken_backup.php',
        'pages/admin/supervisor_dashboard_broken_backup.php'
    ];
    
    foreach ($unnecessaryBackups as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $deletableFiles[] = [
                'path' => $fullPath,
                'relative' => $file,
                'reason' => 'Broken backup file - not needed',
                'priority' => 'medium',
                'size' => filesize($fullPath)
            ];
        }
    }
}

// 5. Call History files ที่ลบออกแล้ว
function addCallHistoryFiles(&$deletableFiles) {
    $callHistoryFiles = [
        'call_history_selector.php',
        'pages/call_history_demo.php',
        'pages/call_history_demo_fixed.php',
        'pages/call_history_demo_production_safe.php',
        'pages/call_history_demo_no_auth.php',
        'pages/call_history_step_by_step.php'
    ];
    
    foreach ($callHistoryFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $deletableFiles[] = [
                'path' => $fullPath,
                'relative' => $file,
                'reason' => 'Call history feature removed per user request',
                'priority' => 'high',
                'size' => filesize($fullPath)
            ];
        }
    }
}

// 6. Fix files ที่ใช้เสร็จแล้ว
function addCompletedFixFiles(&$deletableFiles) {
    $fixFiles = [
        'fix_debug_cust005.php',
        'fix_existing_sales_column.php',
        'fixed_login_api.php',
        'ultimate_fixed_login.php',
        'working_login_api.php'
    ];
    
    foreach ($fixFiles as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $deletableFiles[] = [
                'path' => $fullPath,
                'relative' => $file,
                'reason' => 'Fix completed - temporary file can be removed',
                'priority' => 'medium',
                'size' => filesize($fullPath)
            ];
        }
    }
}

// รวบรวมไฟล์ทั้งหมด
addDeletedFiles($deletableFiles);
addDebugFiles($deletableFiles);
addTestFiles($deletableFiles);
addUnnecessaryBackupFiles($deletableFiles);
addCallHistoryFiles($deletableFiles);
addCompletedFixFiles($deletableFiles);

// จัดเรียงตาม priority และ size
usort($deletableFiles, function($a, $b) {
    $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
    $aPriority = $priorityOrder[$a['priority']] ?? 0;
    $bPriority = $priorityOrder[$b['priority']] ?? 0;
    
    if ($aPriority !== $bPriority) {
        return $bPriority - $aPriority; // High priority first
    }
    
    return $b['size'] - $a['size']; // Larger files first within same priority
});

// คำนวณสถิติ
$totalFiles = count($deletableFiles);
$totalSize = array_sum(array_column($deletableFiles, 'size'));
$highPriorityCount = count(array_filter($deletableFiles, fn($f) => $f['priority'] === 'high'));
$mediumPriorityCount = count(array_filter($deletableFiles, fn($f) => $f['priority'] === 'medium'));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Files Ready for Deletion - Kiro CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .cleanup-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .file-item { padding: 10px 15px; margin: 5px 0; border-radius: 8px; border-left: 4px solid #ccc; }
        .priority-high { border-left-color: #dc3545; background: #fff5f5; }
        .priority-medium { border-left-color: #ffc107; background: #fffdf0; }
        .priority-low { border-left-color: #28a745; background: #f0fff4; }
        .file-path { font-family: 'Courier New', monospace; font-size: 0.9em; color: #6c757d; }
        .file-size { color: #28a745; font-weight: 500; }
        .danger-zone { background: linear-gradient(45deg, #ffebee, #ffcdd2); border: 2px solid #f44336; }
        .safe-zone { background: linear-gradient(45deg, #e8f5e8, #c8e6c8); border: 2px solid #4caf50; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="cleanup-card">
            <h1><i class="fas fa-trash-alt text-danger"></i> Files Ready for Deletion</h1>
            <p class="text-muted">รายการไฟล์ที่วิเคราะห์แล้วว่าสามารถลบได้อย่างปลอดภัย</p>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="text-center p-3 bg-danger text-white rounded">
                        <h3><?php echo $totalFiles; ?></h3>
                        <small>Total Files</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-warning text-dark rounded">
                        <h3><?php echo number_format($totalSize / 1024, 1); ?>KB</h3>
                        <small>Total Size</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-primary text-white rounded">
                        <h3><?php echo $highPriorityCount; ?></h3>
                        <small>High Priority</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center p-3 bg-info text-white rounded">
                        <h3><?php echo $mediumPriorityCount; ?></h3>
                        <small>Medium Priority</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="cleanup-card safe-zone">
            <h3><i class="fas fa-shield-check text-success"></i> Safe Deletion Process</h3>
            <p>ขั้นตอนการลบไฟล์อย่างปลอดภัย:</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-backup fa-3x text-info mb-3"></i>
                        <h5>Step 1: Backup</h5>
                        <p>สำรองข้อมูลสำคัญก่อน</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-list-check fa-3x text-warning mb-3"></i>
                        <h5>Step 2: Review</h5>
                        <p>ตรวจสอบรายการไฟล์</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                        <h5>Step 3: Delete</h5>
                        <p>ลบไฟล์ตามลำดับความสำคัญ</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="cleanup-card">
            <h3><i class="fas fa-list text-primary"></i> Detailed File List</h3>
            
            <div class="mb-3">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-danger" onclick="showPriority('high')">
                        High Priority (<?php echo $highPriorityCount; ?>)
                    </button>
                    <button type="button" class="btn btn-outline-warning" onclick="showPriority('medium')">
                        Medium Priority (<?php echo $mediumPriorityCount; ?>)
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="showPriority('all')">
                        Show All
                    </button>
                </div>
            </div>

            <div id="filesList" style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($deletableFiles as $index => $file): ?>
                <div class="file-item priority-<?php echo $file['priority']; ?> file-priority-<?php echo $file['priority']; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-<?php echo $file['priority'] === 'high' ? 'danger' : ($file['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                    <?php echo strtoupper($file['priority']); ?>
                                </span>
                                <strong><?php echo basename($file['relative']); ?></strong>
                            </div>
                            
                            <div class="file-path mb-1">
                                <?php echo htmlspecialchars($file['relative']); ?>
                            </div>
                            
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($file['reason']); ?>
                            </small>
                        </div>
                        
                        <div class="text-end">
                            <div class="file-size mb-1">
                                <?php echo number_format($file['size'] / 1024, 1); ?>KB
                            </div>
                            <small class="text-muted">
                                #<?php echo $index + 1; ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="cleanup-card danger-zone">
            <h3><i class="fas fa-exclamation-triangle text-danger"></i> Before You Delete</h3>
            
            <div class="alert alert-warning">
                <h5><i class="fas fa-warning"></i> Important Warning</h5>
                <ul class="mb-0">
                    <li>ตรวจสอบให้แน่ใจว่าระบบทำงานปกติก่อนลบไฟล์</li>
                    <li>สำรองไฟล์สำคัญก่อนดำเนินการ</li>
                    <li>ลบทีละกลุ่มและทดสอบหลังแต่ละขั้นตอน</li>
                    <li>เก็บไฟล์ backup ไว้สักระยะก่อนลบถาวร</li>
                </ul>
            </div>
            
            <div class="text-center">
                <button class="btn btn-danger btn-lg" onclick="confirmDeletion()">
                    <i class="fas fa-trash-alt"></i> Proceed with Deletion
                </button>
                <a href="file_cleanup_analysis.php" class="btn btn-info btn-lg">
                    <i class="fas fa-arrow-left"></i> Back to Analysis
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showPriority(priority) {
            const allFiles = document.querySelectorAll('.file-item');
            
            allFiles.forEach(file => {
                if (priority === 'all') {
                    file.style.display = 'block';
                } else {
                    if (file.classList.contains('file-priority-' + priority)) {
                        file.style.display = 'block';
                    } else {
                        file.style.display = 'none';
                    }
                }
            });
            
            // Update active button
            document.querySelectorAll('.btn-group button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
        
        function confirmDeletion() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการลบไฟล์เหล่านี้?\n\nการดำเนินการนี้ไม่สามารถย้อนกลับได้!')) {
                alert('การลบไฟล์จะดำเนินการในขั้นตอนถัดไป\n\nกรุณาสำรองข้อมูลก่อนดำเนินการจริง');
                // window.location.href = 'execute_cleanup.php';
            }
        }
        
        // Show high priority by default
        document.addEventListener('DOMContentLoaded', function() {
            showPriority('high');
            document.querySelector('.btn-outline-danger').classList.add('active');
        });
    </script>
</body>
</html>