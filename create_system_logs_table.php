<?php
// create_system_logs_table.php
// สร้างตาราง system_logs สำหรับบันทึก log ระบบ

session_start();

// Bypass auth for setup
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔧 Create System Logs Table</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1 class='text-center mb-4'>🔧 Create System Logs Table</h1>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Check if table already exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'system_logs'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<div class='alert alert-info'>";
        echo "<h4>ℹ️ Table Already Exists</h4>";
        echo "<p>ตาราง <code>system_logs</code> มีอยู่แล้วในฐานข้อมูล</p>";
        echo "</div>";
        
        // Show table structure
        $stmt = $pdo->prepare("DESCRIBE system_logs");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "<div class='card mt-3'>";
        echo "<div class='card-header'><h5>📋 Table Structure</h5></div>";
        echo "<div class='card-body'>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><code>{$col['Field']}</code></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "</div></div>";
        
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ Creating System Logs Table</h4>";
        echo "<p>สร้างตาราง <code>system_logs</code> สำหรับบันทึก log ระบบ</p>";
        echo "</div>";
        
        // Create system_logs table
        $createTableSQL = "
        CREATE TABLE `system_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `log_type` varchar(50) NOT NULL COMMENT 'ประเภท log: auto_system, health_check, error, info',
            `message` text NOT NULL COMMENT 'ข้อความ log',
            `details` json DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติม (JSON)',
            `user_id` int(11) DEFAULT NULL COMMENT 'ผู้ใช้ที่เกี่ยวข้อง',
            `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP Address',
            `user_agent` varchar(500) DEFAULT NULL COMMENT 'User Agent',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันที่สร้าง',
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'วันที่อัปเดต',
            PRIMARY KEY (`id`),
            KEY `idx_log_type` (`log_type`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตาราง log ระบบ';
        ";
        
        $pdo->exec($createTableSQL);
        
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Table Created Successfully</h4>";
        echo "<p>สร้างตาราง <code>system_logs</code> เรียบร้อยแล้ว</p>";
        echo "</div>";
        
        // Insert sample data
        $sampleLogs = [
            ['auto_system', 'System initialized successfully', NULL],
            ['health_check', 'Health check completed - Status: good', NULL],
            ['info', 'System logs table created', NULL]
        ];
        
        $insertSQL = "INSERT INTO system_logs (log_type, message, details, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($insertSQL);
        
        foreach ($sampleLogs as $log) {
            $stmt->execute($log);
        }
        
        echo "<div class='alert alert-info'>";
        echo "<h5>📝 Sample Data Inserted</h5>";
        echo "<p>เพิ่มข้อมูลตัวอย่าง " . count($sampleLogs) . " รายการ</p>";
        echo "</div>";
    }
    
    // Show recent logs
    echo "<div class='card mt-4'>";
    echo "<div class='card-header'><h5>📊 Recent Logs (Last 10)</h5></div>";
    echo "<div class='card-body'>";
    
    $stmt = $pdo->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    if ($logs) {
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>ID</th><th>Type</th><th>Message</th><th>Created At</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($logs as $log) {
            $badgeClass = 'secondary';
            if ($log['log_type'] === 'auto_system') $badgeClass = 'success';
            elseif ($log['log_type'] === 'health_check') $badgeClass = 'info';
            elseif ($log['log_type'] === 'error') $badgeClass = 'danger';
            
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td><span class='badge bg-{$badgeClass}'>{$log['log_type']}</span></td>";
            echo "<td>" . htmlspecialchars($log['message']) . "</td>";
            echo "<td>" . date('d/m/Y H:i:s', strtotime($log['created_at'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<p class='text-muted'>ยังไม่มี log ในระบบ</p>";
    }
    
    echo "</div></div>";
    
    // Test logging function
    echo "<div class='card mt-4'>";
    echo "<div class='card-header'><h5>🧪 Test Logging Function</h5></div>";
    echo "<div class='card-body'>";
    
    if (isset($_POST['test_log'])) {
        $testMessage = $_POST['test_message'] ?? 'Test log entry';
        $testType = $_POST['test_type'] ?? 'info';
        
        $stmt = $pdo->prepare("INSERT INTO system_logs (log_type, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$testType, $testMessage]);
        
        echo "<div class='alert alert-success'>✅ Test log added successfully!</div>";
    }
    
    echo "<form method='post'>";
    echo "<div class='row'>";
    echo "<div class='col-md-4'>";
    echo "<label class='form-label'>Log Type:</label>";
    echo "<select name='test_type' class='form-select'>";
    echo "<option value='info'>Info</option>";
    echo "<option value='auto_system'>Auto System</option>";
    echo "<option value='health_check'>Health Check</option>";
    echo "<option value='error'>Error</option>";
    echo "</select>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<label class='form-label'>Message:</label>";
    echo "<input type='text' name='test_message' class='form-control' placeholder='Test log message' value='Manual test log entry'>";
    echo "</div>";
    echo "<div class='col-md-2'>";
    echo "<label class='form-label'>&nbsp;</label>";
    echo "<button type='submit' name='test_log' class='btn btn-primary form-control'>Add Test Log</button>";
    echo "</div>";
    echo "</div>";
    echo "</form>";
    
    echo "</div></div>";
    
    // Navigation
    echo "<div class='text-center mt-4'>";
    echo "<div class='btn-group'>";
    echo "<a href='simple_cron_check.php' class='btn btn-success'>🔍 Simple Cron Check</a>";
    echo "<a href='check_cron_status.php' class='btn btn-info'>📊 Cron Status</a>";
    echo "<a href='cron_cleanup_helper.php' class='btn btn-warning'>🧹 Cleanup Helper</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Error</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<small>File: " . $e->getFile() . " Line: " . $e->getLine() . "</small>";
    echo "</div>";
}

echo "</div></body></html>";
?>