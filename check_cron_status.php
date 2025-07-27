<?php
// check_cron_status.php
// ตรวจสอบสถานะ Cron Jobs และประสิทธิภาพการทำงาน

session_start();

// Bypass auth for testing - remove in production
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>📊 Cron Job Status Monitor</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.status-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);border-left:4px solid #ddd;} 
.status-good{border-left-color:#28a745;background:linear-gradient(135deg,#e8f5e9,#f1f8e9);} 
.status-warning{border-left-color:#ffc107;background:linear-gradient(135deg,#fff8e1,#fffbf0);} 
.status-error{border-left-color:#dc3545;background:linear-gradient(135deg,#ffebee,#fff5f5);} 
.status-info{border-left-color:#17a2b8;background:linear-gradient(135deg,#e1f5fe,#f0f9ff);} 
.metric{background:white;border-radius:8px;padding:12px;margin:8px 0;border-left:3px solid #ddd;}
.metric.good{border-left-color:#28a745;} .metric.warning{border-left-color:#ffc107;} .metric.error{border-left-color:#dc3545;}
.status-icon{font-size:1.2em;margin-right:8px;}
pre{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;max-height:300px;overflow:auto;}
.cron-header{background:linear-gradient(135deg,#2196f3,#42a5f5);color:white;border-radius:8px 8px 0 0;padding:15px 20px;margin:-15px -20px 15px -20px;}
.refresh-btn{position:fixed;bottom:20px;right:20px;z-index:1000;}
.timeline{border-left:2px solid #ddd;padding-left:20px;margin-left:10px;}
.timeline-item{margin-bottom:15px;position:relative;}
.timeline-item::before{content:'';position:absolute;left:-25px;top:5px;width:10px;height:10px;border-radius:50%;background:#ddd;}
.timeline-item.success::before{background:#28a745;} .timeline-item.warning::before{background:#ffc107;} .timeline-item.error::before{background:#dc3545;}
.log-preview{max-height:150px;overflow:auto;font-size:11px;line-height:1.3;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='text-center mb-4'>";
echo "<h1 class='display-5 fw-bold text-primary'>📊 Cron Job Status Monitor</h1>";
echo "<p class='lead text-muted'>ตรวจสอบสถานะและประสิทธิภาพ Cron Jobs</p>";
echo "<small class='text-muted'>Last updated: " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Get current crontab
    $crontabOutput = [];
    exec('crontab -l 2>/dev/null', $crontabOutput);
    
    // Define expected cron jobs
    $expectedCrons = [
        'daily_cleanup' => [
            'name' => 'Daily Cleanup',
            'schedule' => '0 1 * * *',
            'description' => 'ทำความสะอาดข้อมูลทุกวันเวลา 01:00',
            'command_pattern' => 'production_auto_system.php daily',
            'log_file' => 'logs/cron_daily.log',
            'expected_frequency' => 24 * 3600, // 24 hours
            'log_type' => 'auto_system'
        ],
        'smart_update' => [
            'name' => 'Smart Update',
            'schedule' => '0 2 * * *',
            'description' => 'อัปเดต Temperature/Grade ทุกวันเวลา 02:00',
            'command_pattern' => 'production_auto_system.php smart',
            'log_file' => 'logs/cron_smart.log',
            'expected_frequency' => 24 * 3600, // 24 hours
            'log_type' => 'auto_system'
        ],
        'auto_reassign' => [
            'name' => 'Auto Reassign',
            'schedule' => '0 */6 * * *',
            'description' => 'Auto-reassign ลูกค้าทุก 6 ชั่วโมง',
            'command_pattern' => 'production_auto_system.php reassign',
            'log_file' => 'logs/cron_reassign.log',
            'expected_frequency' => 6 * 3600, // 6 hours
            'log_type' => 'auto_system'
        ],
        'full_system' => [
            'name' => 'Full System Check',
            'schedule' => '0 3 * * 0',
            'description' => 'ตรวจสอบระบบเต็มรูปแบบทุกวันอาทิตย์เวลา 03:00',
            'command_pattern' => 'production_auto_system.php all',
            'log_file' => 'logs/cron_full.log',
            'expected_frequency' => 7 * 24 * 3600, // 7 days
            'log_type' => 'auto_system'
        ],
        'health_check' => [
            'name' => 'Health Check',
            'schedule' => '*/30 8-18 * * 1-6',
            'description' => 'ตรวจสอบสุขภาพระบบทุก 30 นาที (เวลาทำงาน)',
            'command_pattern' => 'system_health_check.php',
            'log_file' => 'logs/health_check.log',
            'expected_frequency' => 30 * 60, // 30 minutes (during work hours)
            'log_type' => 'health_check'
        ]
    ];
    
    // Check which crons are installed
    echo "<div class='status-card status-info'>";
    echo "<div class='cron-header'><h3><i class='fas fa-list'></i> Installed Cron Jobs</h3></div>";
    
    $installedCrons = [];
    $hasKiroCrons = false;
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Schedule</th><th>Command</th><th>Status</th><th>Match</th></tr></thead><tbody>";
    
    foreach ($crontabOutput as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        
        echo "<tr>";
        $parts = preg_split('/\s+/', $line, 6);
        if (count($parts) >= 6) {
            $schedule = implode(' ', array_slice($parts, 0, 5));
            $command = $parts[5];
            
            echo "<td><code>$schedule</code></td>";
            echo "<td><small>" . htmlspecialchars($command) . "</small></td>";
            
            // Check if this matches any expected cron
            $matched = false;
            foreach ($expectedCrons as $key => $expectedCron) {
                if (strpos($command, $expectedCron['command_pattern']) !== false) {
                    $installedCrons[$key] = true;
                    $hasKiroCrons = true;
                    $matched = $key;
                    break;
                }
            }
            
            if ($matched) {
                echo "<td><span class='badge bg-success'>Active</span></td>";
                echo "<td><strong>" . $expectedCrons[$matched]['name'] . "</strong></td>";
            } else {
                echo "<td><span class='badge bg-secondary'>Other</span></td>";
                echo "<td>-</td>";
            }
        } else {
            echo "<td colspan='4'><code>$line</code></td>";
        }
        echo "</tr>";
    }
    
    if (empty($crontabOutput)) {
        echo "<tr><td colspan='4' class='text-center text-muted'>No crontab entries found</td></tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
    
    if (!$hasKiroCrons) {
        echo "<div class='alert alert-warning'>";
        echo "<h5><i class='fas fa-exclamation-triangle'></i> ไม่พบ Kiro CRM Cron Jobs</h5>";
        echo "<p>กรุณาติดตั้ง Cron Jobs โดยใช้คำสั่ง: <code>./cron/setup_cron.sh</code></p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Check status of each expected cron
    foreach ($expectedCrons as $key => $cron) {
        $isInstalled = isset($installedCrons[$key]);
        $statusClass = $isInstalled ? 'status-good' : 'status-error';
        
        echo "<div class='status-card $statusClass'>";
        echo "<div class='p-4'>";
        
        // Header
        $statusIcon = $isInstalled ? '✅' : '❌';
        echo "<h4><span class='status-icon'>$statusIcon</span>{$cron['name']}</h4>";
        echo "<p class='text-muted mb-3'>{$cron['description']}</p>";
        
        if ($isInstalled) {
            echo "<div class='row'>";
            
            // Check last execution from database
            echo "<div class='col-md-6'>";
            echo "<h6>📊 Execution History</h6>";
            
            $sql = "SELECT created_at, message FROM system_logs 
                    WHERE log_type = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cron['log_type']]);
            $executions = $stmt->fetchAll();
            
            if ($executions) {
                echo "<div class='timeline'>";
                foreach ($executions as $i => $exec) {
                    $timeDiff = time() - strtotime($exec['created_at']);
                    $timeAgo = '';
                    
                    if ($timeDiff < 3600) {
                        $timeAgo = floor($timeDiff / 60) . ' นาทีที่แล้ว';
                    } elseif ($timeDiff < 86400) {
                        $timeAgo = floor($timeDiff / 3600) . ' ชั่วโมงที่แล้ว';
                    } else {
                        $timeAgo = floor($timeDiff / 86400) . ' วันที่แล้ว';
                    }
                    
                    $timelineClass = 'success';
                    if (strpos($exec['message'], 'error') !== false || strpos($exec['message'], 'fail') !== false) {
                        $timelineClass = 'error';
                    } elseif ($timeDiff > $cron['expected_frequency'] * 2) {
                        $timelineClass = 'warning';
                    }
                    
                    echo "<div class='timeline-item $timelineClass'>";
                    echo "<strong>" . date('d/m H:i', strtotime($exec['created_at'])) . "</strong> ($timeAgo)<br>";
                    echo "<small>" . htmlspecialchars($exec['message']) . "</small>";
                    echo "</div>";
                    
                    if ($i >= 3) break; // Show only last 3
                }
                echo "</div>";
                
                // Check if overdue
                $lastExec = strtotime($executions[0]['created_at']);
                $timeSinceLastExec = time() - $lastExec;
                
                if ($timeSinceLastExec > $cron['expected_frequency'] * 2) {
                    echo "<div class='alert alert-warning py-2 px-3 mt-2'>";
                    echo "<small><i class='fas fa-clock'></i> เลยกำหนดเวลา: ควรรันทุก " . 
                         ($cron['expected_frequency'] / 3600) . " ชั่วโมง แต่ล่าสุดรันเมื่อ " . 
                         floor($timeSinceLastExec / 3600) . " ชั่วโมงที่แล้ว</small>";
                    echo "</div>";
                }
            } else {
                echo "<div class='alert alert-info py-2 px-3'>";
                echo "<small><i class='fas fa-info-circle'></i> ยังไม่มีประวัติการทำงาน</small>";
                echo "</div>";
            }
            echo "</div>";
            
            // Check log file
            echo "<div class='col-md-6'>";
            echo "<h6>📝 Log File Status</h6>";
            
            $logPath = __DIR__ . '/' . $cron['log_file'];
            if (file_exists($logPath)) {
                $logSize = filesize($logPath);
                $logModified = filemtime($logPath);
                $logAge = time() - $logModified;
                
                echo "<div class='metric " . ($logAge < $cron['expected_frequency'] * 2 ? 'good' : 'warning') . "'>";
                echo "<strong>Log File:</strong> " . basename($cron['log_file']) . "<br>";
                echo "<small>Size: " . number_format($logSize / 1024, 1) . " KB | ";
                echo "Modified: " . date('d/m H:i', $logModified) . "</small>";
                echo "</div>";
                
                // Show last few log lines
                $logLines = file($logPath);
                if ($logLines && count($logLines) > 0) {
                    $lastLines = array_slice($logLines, -10);
                    echo "<div class='log-preview'>";
                    foreach ($lastLines as $line) {
                        $line = trim($line);
                        if (!empty($line)) {
                            $lineClass = '';
                            if (strpos($line, 'ERROR') !== false || strpos($line, 'FAIL') !== false) {
                                $lineClass = 'text-danger';
                            } elseif (strpos($line, 'SUCCESS') !== false || strpos($line, 'completed') !== false) {
                                $lineClass = 'text-success';
                            }
                            echo "<div class='$lineClass'>" . htmlspecialchars($line) . "</div>";
                        }
                    }
                    echo "</div>";
                }
            } else {
                echo "<div class='metric warning'>";
                echo "<strong>Log File:</strong> ไม่พบไฟล์<br>";
                echo "<small>Path: " . $cron['log_file'] . "</small>";
                echo "</div>";
            }
            echo "</div>";
            
            echo "</div>"; // row
            
            // Manual trigger button
            echo "<div class='mt-3'>";
            echo "<a href='production_auto_system.php?task=" . str_replace('production_auto_system.php ', '', $cron['command_pattern']) . "' class='btn btn-sm btn-outline-primary' target='_blank'>";
            echo "<i class='fas fa-play'></i> Run Manually</a>";
            echo "</div>";
            
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h6><i class='fas fa-exclamation-circle'></i> Cron Job ไม่ได้ติดตั้ง</h6>";
            echo "<p><strong>Schedule:</strong> <code>{$cron['schedule']}</code></p>";
            echo "<p><strong>Command:</strong> <code>{$cron['command_pattern']}</code></p>";
            echo "</div>";
        }
        
        echo "</div>"; // p-4
        echo "</div>"; // status-card
    }
    
    // System Recommendations
    echo "<div class='status-card status-info'>";
    echo "<div class='cron-header'><h3><i class='fas fa-lightbulb'></i> Recommendations</h3></div>";
    echo "<div class='p-4'>";
    
    $recommendations = [];
    
    // Check for missing crons
    $missingCrons = array_diff(array_keys($expectedCrons), array_keys($installedCrons));
    if (!empty($missingCrons)) {
        $recommendations[] = [
            'type' => 'error',
            'title' => 'ติดตั้ง Cron Jobs ที่ขาดหาย',
            'description' => 'รัน <code>./cron/setup_cron.sh</code> เพื่อติดตั้ง: ' . implode(', ', $missingCrons)
        ];
    }
    
    // Check for overdue jobs
    foreach ($expectedCrons as $key => $cron) {
        if (isset($installedCrons[$key])) {
            $sql = "SELECT created_at FROM system_logs WHERE log_type = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cron['log_type']]);
            $lastRun = $stmt->fetchColumn();
            
            if ($lastRun) {
                $timeSinceLastRun = time() - strtotime($lastRun);
                if ($timeSinceLastRun > $cron['expected_frequency'] * 2) {
                    $recommendations[] = [
                        'type' => 'warning',
                        'title' => $cron['name'] . ' เลยกำหนดเวลา',
                        'description' => 'ควรตรวจสอบ cron service หรือรัน manual'
                    ];
                }
            } else {
                $recommendations[] = [
                    'type' => 'warning',
                    'title' => $cron['name'] . ' ยังไม่เคยทำงาน',
                    'description' => 'ลองรัน manual เพื่อทดสอบ'
                ];
            }
        }
    }
    
    // Check log directory permissions
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        $recommendations[] = [
            'type' => 'error',
            'title' => 'Log directory ไม่มี',
            'description' => 'สร้างโฟลเดอร์: <code>mkdir -p logs && chmod 755 logs</code>'
        ];
    } elseif (!is_writable($logDir)) {
        $recommendations[] = [
            'type' => 'error',
            'title' => 'Log directory ไม่สามารถเขียนได้',
            'description' => 'แก้ไข permissions: <code>chmod 755 logs</code>'
        ];
    }
    
    if (empty($recommendations)) {
        echo "<div class='alert alert-success'>";
        echo "<h5><i class='fas fa-check-circle'></i> ระบบทำงานปกติ</h5>";
        echo "<p class='mb-0'>Cron Jobs ทั้งหมดติดตั้งและทำงานถูกต้อง</p>";
        echo "</div>";
    } else {
        foreach ($recommendations as $rec) {
            $alertClass = $rec['type'] === 'error' ? 'danger' : 'warning';
            echo "<div class='alert alert-$alertClass py-2 px-3 mb-2'>";
            echo "<strong>{$rec['title']}</strong><br>";
            echo "<small>{$rec['description']}</small>";
            echo "</div>";
        }
    }
    
    // Quick Actions
    echo "<h6 class='mt-4'>🚀 Quick Actions:</h6>";
    echo "<div class='btn-group mb-2' role='group'>";
    echo "<a href='production_auto_system.php?task=all' class='btn btn-sm btn-outline-success' target='_blank'>Run All Tasks</a>";
    echo "<a href='system_health_check.php' class='btn btn-sm btn-outline-info' target='_blank'>Health Check</a>";
    echo "<a href='fix_production_data.php' class='btn btn-sm btn-outline-warning' target='_blank'>Production Fix</a>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='fas fa-exclamation-triangle'></i> Error</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<small class='text-muted'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</small>";
    echo "</div>";
}

echo "</div>"; // container

// Auto-refresh button
echo "<a href='javascript:location.reload()' class='btn btn-primary refresh-btn' title='Refresh'>";
echo "<i class='fas fa-sync-alt'></i>";
echo "</a>";

// Auto-refresh script
echo "<script>";
echo "// Auto-refresh every 5 minutes";
echo "setTimeout(function(){ location.reload(); }, 300000);";
echo "</script>";

echo "</body></html>";
?>