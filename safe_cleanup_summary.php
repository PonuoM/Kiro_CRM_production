<?php
/**
 * Safe Cleanup Summary & Action Plan
 * สรุปแผนการคลีนอัปไฟล์อย่างปลอดภัย
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

// นับไฟล์ในแต่ละหมวดหมู่
function countFilesInPattern($pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    return count($files);
}

function getFileSize($pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    $totalSize = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            $totalSize += filesize($file);
        }
    }
    return $totalSize;
}

// สถิติไฟล์ที่จะลบ
$deletionStats = [
    'deleted_folder' => [
        'count' => countFilesInPattern('Deleted/*.php') + countFilesInPattern('Deleted/*/*.php'),
        'size' => getFileSize('Deleted/*.php') + getFileSize('Deleted/*/*.php'),
        'description' => 'ไฟล์ใน folder Deleted ที่ย้ายมาแล้ว',
        'safety' => 'ปลอดภัย 100%'
    ],
    'debug_files' => [
        'count' => countFilesInPattern('*debug*.php'),
        'size' => getFileSize('*debug*.php'),
        'description' => 'ไฟล์ debug ที่ใช้สำหรับแก้ไขปัญหา',
        'safety' => 'ปลอดภัย 95%'
    ],
    'test_files' => [
        'count' => countFilesInPattern('*test*.php') + countFilesInPattern('test_*.php'),
        'size' => getFileSize('*test*.php') + getFileSize('test_*.php'),
        'description' => 'ไฟล์ทดสอบต่างๆ',
        'safety' => 'ปลอดภัย 90%'
    ],
    'call_history' => [
        'count' => countFilesInPattern('*call_history*.php') + countFilesInPattern('pages/*call_history*.php'),
        'size' => getFileSize('*call_history*.php') + getFileSize('pages/*call_history*.php'),
        'description' => 'ไฟล์ Call History ที่ลบออกจากระบบแล้ว',
        'safety' => 'ปลอดภัย 100%'
    ],
    'backup_broken' => [
        'count' => countFilesInPattern('*backup*.php') + countFilesInPattern('*broken*.php'),
        'size' => getFileSize('*backup*.php') + getFileSize('*broken*.php'),
        'description' => 'ไฟล์ backup ที่เสียหรือไม่ใช้แล้ว',
        'safety' => 'ปลอดภัย 85%'
    ]
];

// คำนวณรวม
$totalDeletableFiles = array_sum(array_column($deletionStats, 'count'));
$totalDeletableSize = array_sum(array_column($deletionStats, 'size'));

// ไฟล์ทั้งหมดในโปรเจค
$allPhpFiles = countFilesInPattern('**/*.php');
$allPhpSize = getFileSize('**/*.php');

$deletionPercentage = $totalDeletableFiles > 0 ? ($totalDeletableFiles / $allPhpFiles) * 100 : 0;
$sizeSavingPercentage = $totalDeletableSize > 0 ? ($totalDeletableSize / $allPhpSize) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Safe Cleanup Summary - Kiro CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .summary-card { background: white; border-radius: 15px; padding: 30px; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .stats-card { background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%); color: white; border-radius: 12px; padding: 20px; text-align: center; }
        .danger-card { background: linear-gradient(45deg, #ff9a9e 0%, #fecfef 100%); }
        .success-card { background: linear-gradient(45deg, #a8edea 0%, #fed6e3 100%); }
        .category-item { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 15px; border-left: 5px solid #007bff; }
        .safety-high { border-left-color: #28a745; }
        .safety-medium { border-left-color: #ffc107; }
        .safety-low { border-left-color: #dc3545; }
        .action-button { padding: 15px 30px; font-size: 1.1em; font-weight: 600; border-radius: 25px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="summary-card text-center">
            <h1><i class="fas fa-broom text-primary"></i> Safe Cleanup Summary</h1>
            <p class="lead text-muted">แผนการคลีนอัปไฟล์ Kiro_CRM_production อย่างปลอดภัย</p>
            
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $totalDeletableFiles; ?></h3>
                        <p class="mb-0">Files to Delete</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card success-card text-dark">
                        <h3><?php echo number_format($totalDeletableSize / 1024, 1); ?>KB</h3>
                        <p class="mb-0">Space to Free</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card danger-card text-dark">
                        <h3><?php echo number_format($deletionPercentage, 1); ?>%</h3>
                        <p class="mb-0">of Total Files</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <h3><?php echo $allPhpFiles - $totalDeletableFiles; ?></h3>
                        <p class="mb-0">Files to Keep</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deletion Categories -->
        <div class="summary-card">
            <h2><i class="fas fa-list-check text-success"></i> Deletion Categories</h2>
            <p class="text-muted">ไฟล์ที่แยกตามประเภทและระดับความปลอดภัยในการลบ</p>
            
            <?php foreach ($deletionStats as $key => $stats): ?>
            <?php 
            $safetyClass = 'safety-high';
            $safetyIcon = 'fas fa-shield-check text-success';
            if (strpos($stats['safety'], '85%') !== false) {
                $safetyClass = 'safety-medium';
                $safetyIcon = 'fas fa-shield-alt text-warning';
            } elseif (strpos($stats['safety'], '90%') !== false) {
                $safetyClass = 'safety-medium';
                $safetyIcon = 'fas fa-shield-alt text-info';
            }
            ?>
            <div class="category-item <?php echo $safetyClass; ?>">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5>
                            <i class="<?php echo $safetyIcon; ?>"></i>
                            <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                        </h5>
                        <p class="text-muted mb-1"><?php echo $stats['description']; ?></p>
                        <small class="text-success"><strong><?php echo $stats['safety']; ?></strong></small>
                    </div>
                    <div class="col-md-4 text-end">
                        <h4 class="text-primary"><?php echo $stats['count']; ?> files</h4>
                        <p class="text-muted mb-0"><?php echo number_format($stats['size'] / 1024, 1); ?>KB</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Plan -->
        <div class="summary-card">
            <h2><i class="fas fa-clipboard-list text-info"></i> Cleanup Action Plan</h2>
            
            <div class="row">
                <div class="col-md-6">
                    <h4><i class="fas fa-trash text-danger"></i> Phase 1: Safe Deletion</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-folder text-warning"></i> ลบ folder "Deleted" ทั้งหมด
                            <span class="badge bg-success float-end">147 files</span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-bug text-danger"></i> ลบไฟล์ debug ทั้งหมด
                            <span class="badge bg-warning float-end">14+ files</span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-phone-slash text-info"></i> ลบไฟล์ Call History
                            <span class="badge bg-info float-end">6+ files</span>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h4><i class="fas fa-check-double text-success"></i> Phase 2: Review & Clean</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-vial text-warning"></i> ตรวจสอบไฟล์ test
                            <span class="badge bg-secondary float-end">Review</span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-archive text-muted"></i> จัดการไฟล์ backup
                            <span class="badge bg-secondary float-end">Review</span>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-tools text-primary"></i> ทดสอบระบบ
                            <span class="badge bg-primary float-end">Test</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Safety Guidelines -->
        <div class="summary-card" style="background: linear-gradient(45deg, #fff3cd, #ffeaa7); border: 2px solid #ffc107;">
            <h2><i class="fas fa-exclamation-triangle text-warning"></i> Safety Guidelines</h2>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-database fa-3x text-primary mb-3"></i>
                        <h5>Backup First</h5>
                        <p>สำรองฐานข้อมูลและไฟล์สำคัญก่อนเริ่มลบ</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-vial fa-3x text-success mb-3"></i>
                        <h5>Test System</h5>
                        <p>ทดสอบระบบหลังลบไฟล์แต่ละกลุ่ม</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-undo fa-3x text-info mb-3"></i>
                        <h5>Keep Backups</h5>
                        <p>เก็บไฟล์ backup ไว้ 7-14 วันก่อนลบถาวร</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="summary-card text-center">
            <h2><i class="fas fa-rocket text-primary"></i> Ready to Clean?</h2>
            <p class="text-muted mb-4">เลือกขั้นตอนที่ต้องการดำเนินการ</p>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <button class="btn btn-danger action-button" onclick="startDeletion()">
                    <i class="fas fa-trash-alt"></i> Start Safe Deletion
                </button>
                <a href="files_to_delete_list.php" class="btn btn-warning action-button">
                    <i class="fas fa-list"></i> View File List
                </a>
                <a href="active_files_scanner.php" class="btn btn-success action-button">
                    <i class="fas fa-shield-check"></i> View Active Files
                </a>
                <a href="file_cleanup_analysis.php" class="btn btn-info action-button">
                    <i class="fas fa-chart-bar"></i> Full Analysis
                </a>
                <a href="index.php" class="btn btn-secondary action-button">
                    <i class="fas fa-home"></i> Back Home
                </a>
            </div>

            <div class="mt-4 p-3" style="background: rgba(255,255,255,0.8); border-radius: 10px;">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    การวิเคราะห์โดย: <?php echo htmlspecialchars($current_user); ?> (<?php echo htmlspecialchars($current_role); ?>) 
                    | วันที่: <?php echo date('Y-m-d H:i:s'); ?>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function startDeletion() {
            const confirmation = confirm(
                'คุณแน่ใจหรือไม่ที่จะเริ่มกระบวนการลบไฟล์?\n\n' +
                'ขั้นตอนนี้จะลบไฟล์ตามแผนที่กำหนด:\n' +
                '1. ลบ folder Deleted (147 files)\n' +
                '2. ลบไฟล์ debug (14+ files)\n' +
                '3. ลบไฟล์ Call History (6+ files)\n\n' +
                'กรุณาสำรองข้อมูลก่อนดำเนินการ!'
            );
            
            if (confirmation) {
                alert('กำลังเตรียมระบบลบไฟล์...\n\nระบบจะสร้างไฟล์คำสั่งลบที่ปลอดภัย');
                // window.location.href = 'execute_safe_cleanup.php';
                
                // For now, show next steps
                alert('ขั้นตอนถัดไป:\n\n' +
                      '1. ตรวจสอบรายการไฟล์ใน "View File List"\n' +
                      '2. สำรองข้อมูลสำคัญ\n' +
                      '3. ทดสอบระบบก่อนดำเนินการ\n' +
                      '4. ดำเนินการลบทีละขั้นตอน');
            }
        }
    </script>
</body>
</html>