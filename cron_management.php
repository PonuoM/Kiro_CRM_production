<?php
// cron_management.php
// จัดการ Cron Jobs - เพิ่ม ลบ แก้ไข

session_start();

// Bypass auth for testing - remove in production
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$messageType = '';

if ($action && $_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'install_all':
            $result = installAllCrons();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'remove_kiro_crons':
            $result = removeKiroCrons();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'add_single_cron':
            $schedule = $_POST['schedule'] ?? '';
            $command = $_POST['command'] ?? '';
            $result = addSingleCron($schedule, $command);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
            
        case 'backup_crontab':
            $result = backupCrontab();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            break;
    }
}

function installAllCrons() {
    $projectDir = __DIR__;
    
    $cronJobs = [
        "# Kiro CRM Auto System - Installed on " . date('Y-m-d H:i:s'),
        "0 1 * * * php $projectDir/production_auto_system.php daily >> $projectDir/logs/cron_daily.log 2>&1",
        "0 2 * * * php $projectDir/production_auto_system.php smart >> $projectDir/logs/cron_smart.log 2>&1", 
        "0 */6 * * * php $projectDir/production_auto_system.php reassign >> $projectDir/logs/cron_reassign.log 2>&1",
        "0 3 * * 0 php $projectDir/production_auto_system.php all >> $projectDir/logs/cron_full.log 2>&1",
        "*/30 8-18 * * 1-6 php $projectDir/system_health_check.php >> $projectDir/logs/health_check.log 2>&1"
    ];
    
    // Get current crontab
    $currentCrontab = [];
    exec('crontab -l 2>/dev/null', $currentCrontab);
    
    // Remove existing Kiro CRM crons
    $cleanCrontab = [];
    foreach ($currentCrontab as $line) {
        if (strpos($line, 'Kiro CRM') === false && 
            strpos($line, 'production_auto_system.php') === false && 
            strpos($line, 'system_health_check.php') === false) {
            $cleanCrontab[] = $line;
        }
    }
    
    // Add new crons
    $newCrontab = array_merge($cleanCrontab, [''], $cronJobs);
    
    // Write to temp file
    $tempFile = tempnam(sys_get_temp_dir(), 'kiro_crontab');
    file_put_contents($tempFile, implode("\n", $newCrontab) . "\n");
    
    // Install new crontab
    $output = [];
    $returnCode = 0;
    exec("crontab $tempFile 2>&1", $output, $returnCode);
    unlink($tempFile);
    
    if ($returnCode === 0) {
        return ['success' => true, 'message' => 'ติดตั้ง Cron Jobs เรียบร้อยแล้ว (' . count($cronJobs) . ' jobs)'];
    } else {
        return ['success' => false, 'message' => 'Error installing crons: ' . implode(' ', $output)];
    }
}

function removeKiroCrons() {
    // Get current crontab
    $currentCrontab = [];
    exec('crontab -l 2>/dev/null', $currentCrontab);
    
    // Remove Kiro CRM related crons
    $cleanCrontab = [];
    $removedCount = 0;
    
    foreach ($currentCrontab as $line) {
        if (strpos($line, 'Kiro CRM') !== false || 
            strpos($line, 'production_auto_system.php') !== false || 
            strpos($line, 'system_health_check.php') !== false) {
            $removedCount++;
        } else {
            $cleanCrontab[] = $line;
        }
    }
    
    if ($removedCount === 0) {
        return ['success' => true, 'message' => 'ไม่พบ Kiro CRM Cron Jobs ที่ต้องลบ'];
    }
    
    // Write cleaned crontab
    $tempFile = tempnam(sys_get_temp_dir(), 'kiro_crontab_clean');
    file_put_contents($tempFile, implode("\n", $cleanCrontab) . "\n");
    
    $output = [];
    $returnCode = 0;
    exec("crontab $tempFile 2>&1", $output, $returnCode);
    unlink($tempFile);
    
    if ($returnCode === 0) {
        return ['success' => true, 'message' => "ลบ Kiro CRM Cron Jobs เรียบร้อยแล้ว ($removedCount jobs)"];
    } else {
        return ['success' => false, 'message' => 'Error removing crons: ' . implode(' ', $output)];
    }
}

function addSingleCron($schedule, $command) {
    if (empty($schedule) || empty($command)) {
        return ['success' => false, 'message' => 'กรุณากรอก Schedule และ Command'];
    }
    
    // Validate cron schedule format
    $schedParts = explode(' ', trim($schedule));
    if (count($schedParts) !== 5) {
        return ['success' => false, 'message' => 'รูปแบบ Schedule ไม่ถูกต้อง (ต้องมี 5 ส่วน)'];
    }
    
    // Get current crontab
    $currentCrontab = [];
    exec('crontab -l 2>/dev/null', $currentCrontab);
    
    // Add new cron
    $newLine = "$schedule $command";
    $currentCrontab[] = $newLine;
    
    // Write new crontab
    $tempFile = tempnam(sys_get_temp_dir(), 'kiro_crontab_add');
    file_put_contents($tempFile, implode("\n", $currentCrontab) . "\n");
    
    $output = [];
    $returnCode = 0;
    exec("crontab $tempFile 2>&1", $output, $returnCode);
    unlink($tempFile);
    
    if ($returnCode === 0) {
        return ['success' => true, 'message' => 'เพิ่ม Cron Job เรียบร้อยแล้ว'];
    } else {
        return ['success' => false, 'message' => 'Error adding cron: ' . implode(' ', $output)];
    }
}

function backupCrontab() {
    $backupDir = __DIR__ . '/cron';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . '/crontab_backup_' . date('Ymd_His') . '.txt';
    
    $output = [];
    $returnCode = 0;
    exec("crontab -l > $backupFile 2>/dev/null", $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($backupFile)) {
        return ['success' => true, 'message' => 'สำรองข้อมูล Crontab แล้ว: ' . basename($backupFile)];
    } else {
        return ['success' => false, 'message' => 'ไม่สามารถสำรองข้อมูล Crontab ได้'];
    }
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>⚙️ Cron Management</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.management-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
.card-header-custom{background:linear-gradient(135deg,#6f42c1,#9c27b0);color:white;border-radius:8px 8px 0 0;padding:15px 20px;}
.action-card{border-left:4px solid #17a2b8;background:linear-gradient(135deg,#e1f5fe,#f0f9ff);}
.danger-card{border-left:4px solid #dc3545;background:linear-gradient(135deg,#ffebee,#fff5f5);}
.success-card{border-left:4px solid #28a745;background:linear-gradient(135deg,#e8f5e9,#f1f8e9);}
pre{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;max-height:200px;overflow:auto;}
.cron-example{background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;padding:8px;margin:5px 0;font-family:monospace;font-size:12px;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='text-center mb-4'>";
echo "<h1 class='display-5 fw-bold text-primary'>⚙️ Cron Job Management</h1>";
echo "<p class='lead text-muted'>จัดการ Cron Jobs สำหรับระบบ Kiro CRM</p>";
echo "</div>";

// Show message
if ($message) {
    echo "<div class='alert alert-$messageType alert-dismissible fade show'>";
    echo "<strong>" . ($messageType === 'success' ? 'Success!' : 'Error!') . "</strong> $message";
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
    echo "</div>";
}

// Current Status
echo "<div class='management-card action-card'>";
echo "<div class='card-header-custom'>";
echo "<h3><i class='fas fa-info-circle'></i> Current Crontab Status</h3>";
echo "</div>";
echo "<div class='p-4'>";

$crontabOutput = [];
exec('crontab -l 2>/dev/null', $crontabOutput);

if (empty($crontabOutput)) {
    echo "<div class='alert alert-warning'>";
    echo "<h5><i class='fas fa-exclamation-triangle'></i> ไม่มี Crontab</h5>";
    echo "<p>ไม่พบ Cron Jobs ในระบบ</p>";
    echo "</div>";
} else {
    echo "<h6>📋 Current Crontab (" . count($crontabOutput) . " lines):</h6>";
    echo "<pre>" . htmlspecialchars(implode("\n", $crontabOutput)) . "</pre>";
    
    // Count Kiro CRM crons
    $kiroCronCount = 0;
    foreach ($crontabOutput as $line) {
        if (strpos($line, 'production_auto_system.php') !== false || 
            strpos($line, 'system_health_check.php') !== false) {
            $kiroCronCount++;
        }
    }
    
    if ($kiroCronCount > 0) {
        echo "<div class='alert alert-success'>";
        echo "<i class='fas fa-check-circle'></i> พบ Kiro CRM Cron Jobs: <strong>$kiroCronCount</strong> รายการ";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<i class='fas fa-exclamation-triangle'></i> ไม่พบ Kiro CRM Cron Jobs";
        echo "</div>";
    }
}

echo "<div class='mt-3'>";
echo "<a href='check_cron_status.php' class='btn btn-info'><i class='fas fa-chart-line'></i> Check Status</a> ";
echo "<form method='post' class='d-inline'>";
echo "<input type='hidden' name='action' value='backup_crontab'>";
echo "<button type='submit' class='btn btn-secondary'><i class='fas fa-download'></i> Backup Crontab</button>";
echo "</form>";
echo "</div>";

echo "</div>";
echo "</div>";

// Quick Actions
echo "<div class='management-card success-card'>";
echo "<div class='card-header-custom' style='background:linear-gradient(135deg,#28a745,#20c997);'>";
echo "<h3><i class='fas fa-rocket'></i> Quick Actions</h3>";
echo "</div>";
echo "<div class='p-4'>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";

echo "<h5>🚀 Install All Kiro CRM Crons</h5>";
echo "<p>ติดตั้ง Cron Jobs ทั้งหมดสำหรับระบบ Kiro CRM</p>";
echo "<ul class='small text-muted'>";
echo "<li>Daily Cleanup (01:00 AM)</li>";
echo "<li>Smart Update (02:00 AM)</li>";
echo "<li>Auto Reassign (ทุก 6 ชั่วโมง)</li>";
echo "<li>Full System Check (วันอาทิตย์ 03:00 AM)</li>";
echo "<li>Health Check (ทุก 30 นาที เวลาทำงาน)</li>";
echo "</ul>";

echo "<form method='post' onsubmit='return confirm(\"ยืนยันการติดตั้ง Cron Jobs ทั้งหมด?\");'>";
echo "<input type='hidden' name='action' value='install_all'>";
echo "<button type='submit' class='btn btn-success'>";
echo "<i class='fas fa-download'></i> Install All Crons";
echo "</button>";
echo "</form>";

echo "</div>";
echo "<div class='col-md-6'>";

echo "<h5>📊 System Health</h5>";
echo "<p>ตรวจสอบสถานะและสุขภาพของระบบ</p>";
echo "<div class='btn-group-vertical w-100'>";
echo "<a href='check_cron_status.php' class='btn btn-outline-info'><i class='fas fa-chart-line'></i> Cron Status Monitor</a>";
echo "<a href='system_health_check.php' class='btn btn-outline-success'><i class='fas fa-heartbeat'></i> System Health Check</a>";
echo "<a href='production_auto_system.php?task=all' class='btn btn-outline-primary' target='_blank'><i class='fas fa-play'></i> Run All Tasks Now</a>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "</div>";
echo "</div>";

// Remove Crons
echo "<div class='management-card danger-card'>";
echo "<div class='card-header-custom' style='background:linear-gradient(135deg,#dc3545,#e91e63);'>";
echo "<h3><i class='fas fa-trash'></i> Remove Crons</h3>";
echo "</div>";
echo "<div class='p-4'>";

echo "<h5>🗑️ Remove All Kiro CRM Crons</h5>";
echo "<p class='text-danger'>ลบ Cron Jobs ทั้งหมดที่เกี่ยวข้องกับ Kiro CRM</p>";

echo "<form method='post' onsubmit='return confirm(\"⚠️ ยืนยันการลบ Cron Jobs ทั้งหมด? การดำเนินการนี้ไม่สามารถย้อนกลับได้\");'>";
echo "<input type='hidden' name='action' value='remove_kiro_crons'>";
echo "<button type='submit' class='btn btn-danger'>";
echo "<i class='fas fa-trash'></i> Remove All Kiro CRM Crons";
echo "</button>";
echo "</form>";

echo "</div>";
echo "</div>";

// Manual Cron Entry
echo "<div class='management-card action-card'>";
echo "<div class='card-header-custom'>";
echo "<h3><i class='fas fa-plus'></i> Add Single Cron Job</h3>";
echo "</div>";
echo "<div class='p-4'>";

echo "<h5>➕ เพิ่ม Cron Job เดี่ยว</h5>";
echo "<p>เพิ่ม Cron Job แบบกำหนดเอง</p>";

echo "<form method='post'>";
echo "<input type='hidden' name='action' value='add_single_cron'>";

echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<label class='form-label'>Schedule (Cron Format):</label>";
echo "<input type='text' name='schedule' class='form-control' placeholder='0 2 * * *' required>";
echo "<small class='form-text text-muted'>รูปแบบ: นาที ชั่วโมง วัน เดือน วันในสัปดาห์</small>";

echo "<div class='mt-2'>";
echo "<h6>ตัวอย่าง Schedule:</h6>";
echo "<div class='cron-example'>0 2 * * * → ทุกวันเวลา 02:00</div>";
echo "<div class='cron-example'>*/30 * * * * → ทุก 30 นาที</div>";
echo "<div class='cron-example'>0 */6 * * * → ทุก 6 ชั่วโมง</div>";
echo "<div class='cron-example'>0 0 * * 0 → ทุกวันอาทิตย์เที่ยงคืน</div>";
echo "</div>";

echo "</div>";
echo "<div class='col-md-6'>";
echo "<label class='form-label'>Command:</label>";
echo "<input type='text' name='command' class='form-control' placeholder='php /path/to/script.php' required>";
echo "<small class='form-text text-muted'>คำสั่งที่ต้องการรัน</small>";

echo "<div class='mt-2'>";
echo "<h6>ตัวอย่าง Commands:</h6>";
echo "<div class='cron-example'>php " . __DIR__ . "/production_auto_system.php daily</div>";
echo "<div class='cron-example'>php " . __DIR__ . "/system_health_check.php</div>";
echo "</div>";

echo "</div>";
echo "</div>";

echo "<button type='submit' class='btn btn-primary mt-3'>";
echo "<i class='fas fa-plus'></i> Add Cron Job";
echo "</button>";

echo "</form>";

echo "</div>";
echo "</div>";

// Backup Management
echo "<div class='management-card action-card'>";
echo "<div class='card-header-custom'>";
echo "<h3><i class='fas fa-archive'></i> Backup Management</h3>";
echo "</div>";
echo "<div class='p-4'>";

echo "<h5>💾 Crontab Backups</h5>";

$backupDir = __DIR__ . '/cron';
$backups = [];

if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (strpos($file, 'crontab_backup_') === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
            $filePath = $backupDir . '/' . $file;
            $backups[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath)
            ];
        }
    }
}

if (empty($backups)) {
    echo "<p class='text-muted'>ยังไม่มีไฟล์สำรองข้อมูล</p>";
} else {
    // Sort by modification time (newest first)
    usort($backups, function($a, $b) { return $b['modified'] - $a['modified']; });
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Backup File</th><th>Size</th><th>Created</th><th>Actions</th></tr></thead><tbody>";
    
    foreach (array_slice($backups, 0, 10) as $backup) { // Show only last 10
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($backup['name']) . "</code></td>";
        echo "<td>" . number_format($backup['size']) . " bytes</td>";
        echo "<td>" . date('d/m/Y H:i', $backup['modified']) . "</td>";
        echo "<td>";
        echo "<a href='cron/" . htmlspecialchars($backup['name']) . "' class='btn btn-sm btn-outline-primary' target='_blank'>";
        echo "<i class='fas fa-eye'></i> View</a>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
}

echo "<form method='post' class='mt-3'>";
echo "<input type='hidden' name='action' value='backup_crontab'>";
echo "<button type='submit' class='btn btn-secondary'>";
echo "<i class='fas fa-download'></i> Create New Backup";
echo "</button>";
echo "</form>";

echo "</div>";
echo "</div>";

echo "</div>"; // container

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";
?>