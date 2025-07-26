<?php
/**
 * Create Backup System - สำหรับ backup files ก่อนทำการแก้ไข UI
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>💾 Create Backup System</h2>";
echo "<p>สร้าง backup สำหรับไฟล์สำคัญก่อนทำการแก้ไข UI</p>";

$backupDir = 'backups';
$timestamp = date('Y-m-d_H-i-s');
$backupName = "ui_fixes_backup_{$timestamp}";
$fullBackupPath = "{$backupDir}/{$backupName}";

// รายการไฟล์ที่ต้อง backup
$criticalFiles = [
    // Dashboard files
    'pages/dashboard.php',
    'pages/admin/supervisor_dashboard.php',
    
    // Daily tasks
    'pages/daily_tasks_demo.php',
    'api/tasks/daily.php',
    'api/tasks/stats.php',
    
    // Import & Management
    'pages/admin/import_customers.php',
    'pages/admin/user_management.php',
    
    // Baskets
    'pages/admin/distribution_basket.php',
    'pages/admin/waiting_basket.php',
    
    // Analytics & Reports
    'pages/admin/intelligence_system.php',
    'pages/sales_performance.php',
    
    // Customer List
    'pages/customer_list_demo.php',
    
    // Layout files
    'includes/main_layout.php',
    'includes/admin_layout.php',
    
    // API files
    'api/customers/list.php',
    'api/dashboard/stats.php'
];

try {
    // สร้างโฟลเดอร์ backup
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
        echo "✅ Created backup directory: $backupDir<br>";
    }
    
    if (!file_exists($fullBackupPath)) {
        mkdir($fullBackupPath, 0755, true);
        echo "✅ Created backup folder: $fullBackupPath<br><br>";
    }
    
    echo "<h3>📁 Backing up critical files...</h3>";
    
    $backedUp = 0;
    $errors = 0;
    
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            $backupFile = $fullBackupPath . '/' . str_replace('/', '_', $file);
            
            if (copy($file, $backupFile)) {
                echo "<div style='background: #d4edda; padding: 3px 8px; margin: 2px 0; border-radius: 3px; font-size: 12px;'>";
                echo "✅ $file → " . basename($backupFile);
                echo "</div>";
                $backedUp++;
            } else {
                echo "<div style='background: #f8d7da; padding: 3px 8px; margin: 2px 0; border-radius: 3px; font-size: 12px;'>";
                echo "❌ Failed to backup: $file";
                echo "</div>";
                $errors++;
            }
        } else {
            echo "<div style='background: #fff3cd; padding: 3px 8px; margin: 2px 0; border-radius: 3px; font-size: 12px;'>";
            echo "⚠️ File not found: $file";
            echo "</div>";
        }
    }
    
    echo "<br><h3>📊 Backup Summary</h3>";
    echo "<div style='background: " . ($errors > 0 ? "#fff3cd" : "#d4edda") . "; padding: 15px; border-radius: 5px;'>";
    echo "📈 <strong>Backup Results:</strong><br>";
    echo "- Files backed up: <strong>$backedUp</strong><br>";
    echo "- Errors: <strong>$errors</strong><br>";
    echo "- Backup location: <strong>$fullBackupPath</strong><br>";
    echo "- Created: <strong>" . date('d/m/Y H:i:s') . "</strong><br>";
    echo "</div>";
    
    // สร้าง restore script
    $restoreScript = "<?php
/**
 * Restore Backup - กู้คืนไฟล์จาก backup
 * Created: " . date('Y-m-d H:i:s') . "
 */

echo '<h2>🔄 Restore Backup</h2>';
echo '<p>กู้คืนไฟล์จาก backup: $backupName</p>';

\$backupFiles = [
";
    
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            $backupFile = str_replace('/', '_', $file);
            $restoreScript .= "    '$backupFile' => '$file',\n";
        }
    }
    
    $restoreScript .= "];

\$restored = 0;
\$errors = 0;

foreach (\$backupFiles as \$backupFile => \$originalFile) {
    \$backupPath = '$fullBackupPath/' . \$backupFile;
    
    if (file_exists(\$backupPath)) {
        // สร้าง directory ถ้าไม่มี
        \$dir = dirname(\$originalFile);
        if (!file_exists(\$dir)) {
            mkdir(\$dir, 0755, true);
        }
        
        if (copy(\$backupPath, \$originalFile)) {
            echo \"✅ Restored: \$originalFile<br>\";
            \$restored++;
        } else {
            echo \"❌ Failed to restore: \$originalFile<br>\";
            \$errors++;
        }
    } else {
        echo \"⚠️ Backup file not found: \$backupFile<br>\";
    }
}

echo \"<br><strong>Restore Summary:</strong><br>\";
echo \"- Files restored: \$restored<br>\";
echo \"- Errors: \$errors<br>\";
echo \"- Restore completed: \" . date('Y-m-d H:i:s') . \"<br>\";
?>";
    
    file_put_contents($fullBackupPath . '/restore.php', $restoreScript);
    
    echo "<h3>🔧 Restore Instructions</h3>";
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>";
    echo "<strong>หากเกิดปัญหา สามารถกู้คืนได้โดย:</strong><br>";
    echo "1. เปิดไฟล์: <code>$fullBackupPath/restore.php</code><br>";
    echo "2. รันไฟล์เพื่อกู้คืนไฟล์ทั้งหมด<br>";
    echo "3. หรือ copy ไฟล์จาก <code>$fullBackupPath/</code> ด้วยตนเอง<br>";
    echo "</div>";
    
    // บันทึก log
    $logEntry = date('Y-m-d H:i:s') . " - UI Fixes Backup Created: $backedUp files backed up, $errors errors\n";
    file_put_contents('backup_log.txt', $logEntry, FILE_APPEND);
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Backup Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #cff4fc; padding: 15px; border-radius: 5px;'>";
echo "✅ <strong>Backup completed!</strong> ตอนนี้สามารถเริ่มแก้ไข UI ได้อย่างปลอดภัย:<br>";
echo "1. เริ่มจากการแก้ไข CustomerGrade column<br>";
echo "2. แก้ไขหน้า Daily Tasks สำหรับ sales02<br>";
echo "3. ปรับปรุง UI ทีละหน้าตาม TODO list<br>";
echo "</div>";

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='workflow_management_summary.php'>📋 System Summary</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📅 Daily Tasks</a> | ";
echo "<a href='pages/dashboard.php'>🏠 Dashboard</a>";

echo "<div style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #007bff;'>";
echo "<strong>📌 Backup Created Successfully!</strong><br>";
echo "Location: <code>$fullBackupPath</code><br>";
echo "Files: $backedUp backed up<br>";
echo "Ready to start UI fixes safely! 🚀";
echo "</div>";
?>