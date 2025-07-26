<?php
/**
 * Backup and Revert System
 * ระบบสำรองข้อมูลและเรียกคืนสำหรับงานค้าง 3 งาน
 * วันที่สร้าง: 2025-07-24
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Backup & Revert System</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #27ae60; margin-top: 30px; }
        .section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .backup-section { border-left-color: #28a745; }
        .revert-section { border-left-color: #dc3545; }
        .command-box { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; margin: 10px 0; overflow-x: auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; color: white; }
        .btn-backup { background: #28a745; }
        .btn-revert { background: #dc3545; }
        .btn-test { background: #17a2b8; }
        .file-list { background: #f8f9fa; padding: 10px; border-radius: 3px; margin: 5px 0; }
        .timestamp { color: #6c757d; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🔐 Backup & Revert System</h1>";
echo "<div class='timestamp'>สร้างเมื่อ: " . date('Y-m-d H:i:s') . "</div>";

// Create backup directory
$backupDir = __DIR__ . '/backups/before_pending_tasks_' . date('Y-m-d_H-i-s');
$logFile = __DIR__ . '/backup_log.txt';

try {
    if (!is_dir(__DIR__ . '/backups')) {
        mkdir(__DIR__ . '/backups', 0755, true);
    }
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    echo "<div class='success'>✅ สร้างโฟลเดอร์ backup: " . basename($backupDir) . "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ไม่สามารถสร้างโฟลเดอร์ backup: " . $e->getMessage() . "</div>";
    exit;
}

// Files to backup for each task
$backupTargets = [
    'user_management' => [
        'files' => [
            'pages/admin/user_management.php',
            'api/users/list.php',
            'api/users/create.php',
            'api/users/update.php',
            'api/users/detail.php',
            'api/users/toggle_status.php'
        ],
        'description' => '👥 User Management - หน้าจัดการผู้ใช้และ API'
    ],
    'sales_performance' => [
        'files' => [
            'pages/sales_performance.php',
            'assets/js/sales-performance.js',
            'assets/css/sales-performance.css',
            'api/sales/performance.php'
        ],
        'description' => '📈 Sales Performance - หน้ารายงานและ API'
    ],
    'layouts_and_shared' => [
        'files' => [
            'includes/main_layout.php',
            'includes/admin_layout.php',
            'includes/permissions.php',
            'includes/functions.php'
        ],
        'description' => '🏗️ Layout & Shared Files - ไฟล์ร่วมและ logout'
    ]
];

// Function to backup files
function backupFiles($files, $backupDir, $description) {
    echo "<div class='section backup-section'>";
    echo "<h3>📁 {$description}</h3>";
    
    $backedUp = [];
    $missing = [];
    
    foreach ($files as $file) {
        $fullPath = __DIR__ . '/' . $file;
        
        if (file_exists($fullPath)) {
            $backupPath = $backupDir . '/' . dirname($file);
            if (!is_dir($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $destFile = $backupDir . '/' . $file;
            if (copy($fullPath, $destFile)) {
                $backedUp[] = $file;
                echo "<div class='file-list'>✅ {$file}</div>";
            } else {
                echo "<div class='file-list'>❌ ไม่สามารถ backup: {$file}</div>";
            }
        } else {
            $missing[] = $file;
            echo "<div class='file-list'>⚠️ ไม่พบไฟล์: {$file}</div>";
        }
    }
    
    echo "<div class='success'>สำเร็จ: " . count($backedUp) . " ไฟล์ | ไม่พบ: " . count($missing) . " ไฟล์</div>";
    echo "</div>";
    
    return ['backed_up' => $backedUp, 'missing' => $missing];
}

// Create backup
echo "<h2>🗄️ กำลังสร้าง Backup</h2>";

$allBackedUp = [];
$allMissing = [];

foreach ($backupTargets as $task => $data) {
    $result = backupFiles($data['files'], $backupDir, $data['description']);
    $allBackedUp = array_merge($allBackedUp, $result['backed_up']);
    $allMissing = array_merge($allMissing, $result['missing']);
}

// Generate revert commands
echo "<h2>🔄 Revert Commands</h2>";

echo "<div class='section revert-section'>";
echo "<h3>📋 คำสั่ง Revert (วิธีที่ 1: Manual Copy)</h3>";
echo "<div class='command-box'>";
echo "# คัดลอกไฟล์กลับจาก backup<br>";
echo "cd /mnt/c/xampp/htdocs/Kiro_CRM_production<br><br>";

foreach ($allBackedUp as $file) {
    $backupFile = str_replace(__DIR__ . '/', '', $backupDir) . '/' . $file;
    echo "cp \"{$backupFile}\" \"{$file}\"<br>";
}
echo "</div>";
echo "</div>";

// Generate PHP revert script
$revertScriptPath = $backupDir . '/revert_changes.php';
$revertScript = '<?php
/**
 * Auto-generated Revert Script
 * วันที่สร้าง: ' . date('Y-m-d H:i:s') . '
 * Backup location: ' . basename($backupDir) . '
 */

echo "🔄 กำลัง Revert การเปลี่ยนแปลง...\n";

$backupDir = __DIR__;
$projectDir = "' . __DIR__ . '";

$files = [
';

foreach ($allBackedUp as $file) {
    $revertScript .= "    '{$file}',\n";
}

$revertScript .= '];

$success = 0;
$failed = 0;

foreach ($files as $file) {
    $backupFile = $backupDir . "/" . $file;
    $originalFile = $projectDir . "/" . $file;
    
    if (file_exists($backupFile)) {
        if (copy($backupFile, $originalFile)) {
            echo "✅ Restored: {$file}\n";
            $success++;
        } else {
            echo "❌ Failed to restore: {$file}\n";
            $failed++;
        }
    } else {
        echo "⚠️ Backup not found: {$file}\n";
        $failed++;
    }
}

echo "\n📊 สรุป: สำเร็จ {$success} ไฟล์ | ล้มเหลว {$failed} ไฟล์\n";
echo "✅ Revert เสร็จสิ้น\n";
?>';

file_put_contents($revertScriptPath, $revertScript);

echo "<div class='section revert-section'>";
echo "<h3>🔧 คำสั่ง Revert (วิธีที่ 2: Auto Script)</h3>";
echo "<div class='command-box'>";
echo "# รัน PHP script เพื่อ revert อัตโนมัติ<br>";
echo "cd /mnt/c/xampp/htdocs/Kiro_CRM_production<br>";
echo "php \"" . str_replace(__DIR__ . '/', '', $revertScriptPath) . "\"<br>";
echo "</div>";
echo "</div>";

// Generate database backup
echo "<h2>🗄️ Database Backup</h2>";

try {
    $db = Database::getInstance();
    $dbName = $db->getConnection()->query("SELECT DATABASE() as db_name")->fetch()['db_name'];
    
    $dbBackupFile = $backupDir . '/database_backup.sql';
    
    echo "<div class='section backup-section'>";
    echo "<h3>💾 Database: {$dbName}</h3>";
    
    // Note: This would require mysqldump
    echo "<div class='warning'>";
    echo "⚠️ <strong>Database Backup Manual:</strong><br>";
    echo "ใช้คำสั่งนี้ในเครื่อง hosting:<br>";
    echo "<div class='command-box'>";
    echo "mysqldump -u [username] -p {$dbName} > " . basename($backupDir) . "/database_backup.sql";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='file-list'>📝 สำคัญ: Tables ที่จะได้รับผลกระทบ: users, orders, sales_histories</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ ไม่สามารถเชื่อมต่อฐานข้อมูล: " . $e->getMessage() . "</div>";
}

// Create log file
$logContent = "=== BACKUP LOG ===\n";
$logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
$logContent .= "Backup Directory: " . $backupDir . "\n";
$logContent .= "Files Backed Up: " . count($allBackedUp) . "\n";
$logContent .= "Files Missing: " . count($allMissing) . "\n\n";

$logContent .= "BACKED UP FILES:\n";
foreach ($allBackedUp as $file) {
    $logContent .= "✅ " . $file . "\n";
}

$logContent .= "\nMISSING FILES:\n";
foreach ($allMissing as $file) {
    $logContent .= "❌ " . $file . "\n";
}

file_put_contents($logFile, $logContent);

// Summary
echo "<h2>📋 สรุป Backup System</h2>";

echo "<div class='success'>";
echo "<h3>✅ Backup เสร็จสิ้น</h3>";
echo "<p><strong>Location:</strong> " . str_replace(__DIR__ . '/', '', $backupDir) . "</p>";
echo "<p><strong>Files Backed Up:</strong> " . count($allBackedUp) . " ไฟล์</p>";
echo "<p><strong>Missing Files:</strong> " . count($allMissing) . " ไฟล์</p>";
echo "<p><strong>Revert Script:</strong> " . str_replace(__DIR__ . '/', '', $revertScriptPath) . "</p>";
echo "<p><strong>Log File:</strong> backup_log.txt</p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ ขั้นตอนการใช้งาน</h3>";
echo "<ol>";
echo "<li><strong>ก่อนแก้ไข:</strong> Backup เสร็จแล้ว ✅</li>";
echo "<li><strong>ระหว่างแก้ไข:</strong> ทดสอบในแต่ละขั้นตอน</li>";
echo "<li><strong>หากเกิดปัญหา:</strong> รัน revert script ทันที</li>";
echo "<li><strong>หลังเสร็จ:</strong> ทดสอบทุกฟีเจอร์</li>";
echo "</ol>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>🎯 Ready for Tasks</h3>";
echo "<p>✅ <strong>User Management:</strong> API connections</p>";
echo "<p>⚠️ <strong>Sales Performance:</strong> สี + ข้อมูลจริง</p>";
echo "<p>✅ <strong>Logout Button:</strong> ตรวจสอบแล้ว (ไม่ต้องแก้)</p>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>