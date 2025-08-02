<?php
/**
 * File Cleanup Analysis Tool
 * เครื่องมือวิเคราะห์ไฟล์เพื่อการคลีนอัป Kiro_CRM_production
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

// สแกนไฟล์ทั้งหมด
$rootDir = __DIR__;
$allFiles = [];

function scanDirectory($dir, $baseDir) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $fullPath = $dir . '/' . $item;
        $relativePath = str_replace($baseDir . '/', '', $fullPath);
        
        if (is_dir($fullPath)) {
            $files = array_merge($files, scanDirectory($fullPath, $baseDir));
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $files[] = [
                'path' => $relativePath,
                'name' => $item,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath),
                'category' => categorizeFile($relativePath, $item)
            ];
        }
    }
    
    return $files;
}

function categorizeFile($path, $filename) {
    // Debug files
    if (strpos($filename, 'debug') !== false || strpos($path, 'debug') !== false) {
        return 'debug';
    }
    
    // Test files
    if (strpos($filename, 'test') !== false || strpos($path, 'test') !== false) {
        return 'test';
    }
    
    // Backup files
    if (strpos($filename, 'backup') !== false || strpos($path, 'backup') !== false) {
        return 'backup';
    }
    
    // Deleted folder
    if (strpos($path, 'Deleted/') !== false) {
        return 'deleted';
    }
    
    // Temporary/Demo files
    if (strpos($filename, 'demo') !== false || strpos($filename, 'temp') !== false || 
        strpos($filename, 'simple') !== false) {
        return 'demo';
    }
    
    // Fix files
    if (strpos($filename, 'fix') !== false || strpos($filename, 'fixed') !== false) {
        return 'fix';
    }
    
    // API files
    if (strpos($path, 'api/') !== false) {
        return 'api';
    }
    
    // Admin files
    if (strpos($path, 'admin/') !== false) {
        return 'admin';
    }
    
    // Pages
    if (strpos($path, 'pages/') !== false) {
        return 'pages';
    }
    
    // Core system files
    if (strpos($path, 'includes/') !== false || strpos($path, 'config/') !== false) {
        return 'core';
    }
    
    // Root level files
    if (strpos($path, '/') === false) {
        return 'root';
    }
    
    return 'other';
}

$allFiles = scanDirectory($rootDir, $rootDir);

// จัดกลุ่มไฟล์
$categories = [];
foreach ($allFiles as $file) {
    $cat = $file['category'];
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $file;
}

// คำนวณสถิติ
$stats = [];
foreach ($categories as $cat => $files) {
    $stats[$cat] = [
        'count' => count($files),
        'size' => array_sum(array_column($files, 'size'))
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Cleanup Analysis - Kiro CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .analysis-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .category-card { border-left: 5px solid #007bff; }
        .category-debug { border-left-color: #dc3545; }
        .category-test { border-left-color: #ffc107; }
        .category-backup { border-left-color: #6c757d; }
        .category-deleted { border-left-color: #fd7e14; }
        .category-demo { border-left-color: #20c997; }
        .category-fix { border-left-color: #e83e8c; }
        .category-api { border-left-color: #0dcaf0; }
        .category-admin { border-left-color: #6f42c1; }
        .category-pages { border-left-color: #198754; }
        .category-core { border-left-color: #495057; }
        .category-root { border-left-color: #0a58ca; }
        .file-item { padding: 8px 12px; margin: 3px 0; background: #f8f9fa; border-radius: 6px; font-size: 0.9em; }
        .file-path { color: #6c757d; font-family: monospace; }
        .file-size { color: #28a745; font-weight: 500; }
        .stats-badge { font-size: 0.8em; margin-left: 10px; }
        .cleanup-suggestion { background: linear-gradient(45deg, #fff3cd, #ffeaa7); border: 1px solid #ffeaa7; }
        .keep-suggestion { background: linear-gradient(45deg, #d4edda, #a8e6cf); border: 1px solid #a8e6cf; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="analysis-card">
            <h1><i class="fas fa-broom text-primary"></i> File Cleanup Analysis</h1>
            <p class="text-muted">วิเคราะห์ไฟล์ทั้งหมดใน Kiro_CRM_production เพื่อการคลีนอัป</p>
            
            <div class="row">
                <div class="col-md-4">
                    <strong>Total Files:</strong> <?php echo count($allFiles); ?> files<br>
                    <strong>Total Size:</strong> <?php echo number_format(array_sum(array_column($allFiles, 'size')) / 1024, 2); ?> KB<br>
                    <strong>Analyzed By:</strong> <?php echo htmlspecialchars($current_user); ?> (<?php echo htmlspecialchars($current_role); ?>)<br>
                    <strong>Analysis Date:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                </div>
                <div class="col-md-8">
                    <h5>Quick Stats:</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($stats as $cat => $stat): ?>
                        <span class="badge bg-secondary">
                            <?php echo ucfirst($cat); ?>: <?php echo $stat['count']; ?> files 
                            (<?php echo number_format($stat['size'] / 1024, 1); ?>KB)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Files that can be deleted -->
            <div class="col-lg-6">
                <div class="analysis-card cleanup-suggestion">
                    <h3><i class="fas fa-trash-alt text-danger"></i> Files Safe to Delete</h3>
                    <p class="text-muted">ไฟล์เหล่านี้สามารถลบได้อย่างปลอดภัย</p>
                    
                    <?php 
                    $deletableCategories = ['debug', 'test', 'deleted', 'demo'];
                    foreach ($deletableCategories as $cat):
                        if (!isset($categories[$cat])) continue;
                    ?>
                    <div class="category-card category-<?php echo $cat; ?> mb-3 p-3">
                        <h5>
                            <i class="fas fa-folder"></i> <?php echo ucfirst($cat); ?> Files
                            <span class="badge bg-danger stats-badge">
                                <?php echo $stats[$cat]['count']; ?> files, <?php echo number_format($stats[$cat]['size'] / 1024, 1); ?>KB
                            </span>
                        </h5>
                        
                        <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                            <?php foreach (array_slice($categories[$cat], 0, 15) as $file): ?>
                            <div class="file-item">
                                <strong><?php echo htmlspecialchars($file['name']); ?></strong><br>
                                <small class="file-path"><?php echo htmlspecialchars($file['path']); ?></small>
                                <small class="file-size float-end"><?php echo number_format($file['size'] / 1024, 1); ?>KB</small>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($categories[$cat]) > 15): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">... และอีก <?php echo count($categories[$cat]) - 15; ?> ไฟล์</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3">
                            <strong>Recommendation:</strong>
                            <?php if ($cat === 'debug'): ?>
                                <span class="text-danger">ลบได้ทั้งหมด - เป็นไฟล์ debug ที่ไม่จำเป็นในการทำงาน</span>
                            <?php elseif ($cat === 'test'): ?>
                                <span class="text-warning">ลบได้ส่วนใหญ่ - เก็บเฉพาะไฟล์ test ที่จำเป็น</span>
                            <?php elseif ($cat === 'deleted'): ?>
                                <span class="text-danger">ลบได้ทั้งหมด - อยู่ใน folder Deleted แล้ว</span>
                            <?php elseif ($cat === 'demo'): ?>
                                <span class="text-info">ตรวจสอบก่อนลบ - บางไฟล์อาจใช้งานจริง</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Files to keep -->
            <div class="col-lg-6">
                <div class="analysis-card keep-suggestion">
                    <h3><i class="fas fa-shield-alt text-success"></i> Files to Keep</h3>
                    <p class="text-muted">ไฟล์เหล่านี้ควรเก็บไว้เพื่อการทำงานของระบบ</p>
                    
                    <?php 
                    $keepCategories = ['core', 'api', 'admin', 'pages', 'root'];
                    foreach ($keepCategories as $cat):
                        if (!isset($categories[$cat])) continue;
                    ?>
                    <div class="category-card category-<?php echo $cat; ?> mb-3 p-3">
                        <h5>
                            <i class="fas fa-folder-open"></i> <?php echo ucfirst($cat); ?> Files
                            <span class="badge bg-success stats-badge">
                                <?php echo $stats[$cat]['count']; ?> files, <?php echo number_format($stats[$cat]['size'] / 1024, 1); ?>KB
                            </span>
                        </h5>
                        
                        <div class="mt-2" style="max-height: 150px; overflow-y: auto;">
                            <?php foreach (array_slice($categories[$cat], 0, 10) as $file): ?>
                            <div class="file-item">
                                <strong><?php echo htmlspecialchars($file['name']); ?></strong><br>
                                <small class="file-path"><?php echo htmlspecialchars($file['path']); ?></small>
                                <small class="file-size float-end"><?php echo number_format($file['size'] / 1024, 1); ?>KB</small>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($categories[$cat]) > 10): ?>
                            <div class="text-center mt-2">
                                <small class="text-muted">... และอีก <?php echo count($categories[$cat]) - 10; ?> ไฟล์</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-3">
                            <strong>Importance:</strong>
                            <?php if ($cat === 'core'): ?>
                                <span class="text-danger">สำคัญมาก - ระบบจะไม่ทำงานหากขาดไฟล์เหล่านี้</span>
                            <?php elseif ($cat === 'api'): ?>
                                <span class="text-warning">สำคัญ - API endpoints ที่ใช้งานจริง</span>
                            <?php elseif ($cat === 'admin'): ?>
                                <span class="text-info">สำคัญ - หน้าแอดมินที่ใช้งานจริง</span>
                            <?php elseif ($cat === 'pages'): ?>
                                <span class="text-info">สำคัญ - หน้าเว็บที่ผู้ใช้เข้าถึง</span>
                            <?php elseif ($cat === 'root'): ?>
                                <span class="text-warning">ตรวจสอบ - ไฟล์ระดับ root</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Detailed breakdown -->
        <div class="analysis-card">
            <h3><i class="fas fa-list-alt text-info"></i> Files Need Review</h3>
            <p class="text-muted">ไฟล์เหล่านี้ต้องตรวจสอบก่อนตัดสินใจ</p>
            
            <?php 
            $reviewCategories = ['backup', 'fix', 'other'];
            foreach ($reviewCategories as $cat):
                if (!isset($categories[$cat])) continue;
            ?>
            <div class="category-card category-<?php echo $cat; ?> mb-3 p-3">
                <h5>
                    <i class="fas fa-question-circle"></i> <?php echo ucfirst($cat); ?> Files
                    <span class="badge bg-warning stats-badge">
                        <?php echo $stats[$cat]['count']; ?> files, <?php echo number_format($stats[$cat]['size'] / 1024, 1); ?>KB
                    </span>
                </h5>
                
                <div class="row">
                    <?php foreach ($categories[$cat] as $file): ?>
                    <div class="col-md-6 col-lg-4 mb-2">
                        <div class="file-item">
                            <strong><?php echo htmlspecialchars($file['name']); ?></strong><br>
                            <small class="file-path"><?php echo htmlspecialchars($file['path']); ?></small><br>
                            <small class="file-size"><?php echo number_format($file['size'] / 1024, 1); ?>KB</small>
                            <small class="text-muted"><?php echo date('M j', $file['modified']); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary and actions -->
        <div class="analysis-card text-center">
            <h3><i class="fas fa-clipboard-check text-success"></i> Cleanup Summary</h3>
            
            <?php
            $deletableCount = 0;
            $deletableSize = 0;
            foreach (['debug', 'test', 'deleted'] as $cat) {
                if (isset($stats[$cat])) {
                    $deletableCount += $stats[$cat]['count'];
                    $deletableSize += $stats[$cat]['size'];
                }
            }
            ?>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Recommended Actions</h5>
                <p><strong>Safe to Delete:</strong> <?php echo $deletableCount; ?> files (<?php echo number_format($deletableSize / 1024, 2); ?>KB)</p>
                <p><strong>Space Savings:</strong> <?php echo number_format(($deletableSize / array_sum(array_column($allFiles, 'size'))) * 100, 1); ?>% of total project size</p>
            </div>
            
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <button class="btn btn-danger" onclick="alert('จะดำเนินการลบไฟล์ในขั้นตอนถัดไป')">
                    <i class="fas fa-trash"></i> Start Cleanup Process
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <button class="btn btn-info" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>