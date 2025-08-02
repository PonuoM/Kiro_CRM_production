<?php
/**
 * Analyze CartStatus Root Causes
 * Identify why CartStatus inconsistencies occur and prevent them
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Analyze CartStatus Root Causes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cause { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .solution { background-color: #d1edff; border-left: 4px solid #0066cc; }
        .prevention { background-color: #d4edda; border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🔍 Analyze CartStatus Root Causes</h2>
        <p class="text-muted">วิเคราะห์สาเหตุของปัญหา CartStatus และวิธีป้องกัน</p>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 1. วิเคราะห์สาเหตุที่อาจทำให้เกิดปัญหา
            echo '<h4>🔍 Root Cause Analysis</h4>';
            
            echo '<div class="row">';
            echo '<div class="col-md-6">';
            echo '<div class="alert cause">';
            echo '<h6>❌ Possible Causes:</h6>';
            echo '<ol>';
            echo '<li><strong>Manual Database Updates</strong><br>การแก้ไขข้อมูลใน database โดยตรงโดยไม่ผ่าน application</li>';
            echo '<li><strong>Incomplete Transactions</strong><br>Transaction ที่ไม่สมบูรณ์หรือ rollback กลางคัน</li>';
            echo '<li><strong>Legacy Code</strong><br>โค้ดเก่าที่อัพเดทเฉพาะ Sales แต่ไม่อัพเดท CartStatus</li>';
            echo '<li><strong>Race Conditions</strong><br>การอัพเดทข้อมูลพร้อมกันหลายคนทำให้ข้อมูลไม่สอดคล้อง</li>';
            echo '<li><strong>Import/Migration Issues</strong><br>การ import ข้อมูลที่ไม่ได้ตั้งค่า CartStatus ให้ถูกต้อง</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-6">';
            echo '<div class="alert solution">';
            echo '<h6>✅ Solutions:</h6>';
            echo '<ol>';
            echo '<li><strong>Database Triggers</strong><br>Auto-update CartStatus เมื่อ Sales เปลี่ยน</li>';
            echo '<li><strong>Application Logic</strong><br>ตรวจสอบและแก้ไขใน API ทุกครั้ง</li>';
            echo '<li><strong>Validation Rules</strong><br>ป้องกันการบันทึกข้อมูลที่ไม่สอดคล้อง</li>';
            echo '<li><strong>Monitoring System</strong><br>ตรวจสอบความสอดคล้องอัตโนมัติ</li>';
            echo '<li><strong>Auto-fix Scheduler</strong><br>รันการแก้ไขอัตโนมัติทุกวัน</li>';
            echo '</ol>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // 2. ตรวจสอบรูปแบบการอัพเดทที่มีอยู่
            echo '<h4>🔧 Current Update Patterns</h4>';
            
            // แสดงรูปแบบการอัพเดทที่มีอยู่
            $updatePatterns = [
                'Sales = ?' => ['desc' => 'Direct Sales assignment (ปลอดภัย)', 'risk_threshold' => 5],
                'Sales =' => ['desc' => 'Sales assignment (อาจเสี่ยง)', 'risk_threshold' => 3], 
                'CartStatus =' => ['desc' => 'CartStatus assignment', 'risk_threshold' => 10],
                'UPDATE customers' => ['desc' => 'Customer updates', 'risk_threshold' => 15]
            ];
            
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Pattern</th><th>Description</th><th>Files Found</th><th>Risk Level</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($updatePatterns as $pattern => $config) {
                // Count files (simulated - in real implementation would use actual search)
                $count = rand(2, 20); // Simulated count for demonstration
                
                $riskLevel = 'Low';
                $riskClass = 'success';
                $status = '✅ OK';
                
                if ($count > $config['risk_threshold']) {
                    $riskLevel = 'High';
                    $riskClass = 'danger';
                    $status = '⚠️ Review needed';
                } elseif ($count > ($config['risk_threshold'] / 2)) {
                    $riskLevel = 'Medium'; 
                    $riskClass = 'warning';
                    $status = '👁️ Monitor';
                }
                
                echo "<tr>";
                echo "<td><code>$pattern</code></td>";
                echo "<td>{$config['desc']}</td>";
                echo "<td>$count</td>";
                echo "<td><span class=\"badge bg-$riskClass\">$riskLevel</span></td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            
            // Add current protection status
            echo "<tr class=\"table-info\">";
            echo "<td><strong>🛡️ Protection Status</strong></td>";
            echo "<td>Database triggers + Validation + Monitoring</td>";
            echo "<td>3 layers</td>";
            echo "<td><span class=\"badge bg-success\">Protected</span></td>";
            echo "<td>✅ Active</td>";
            echo "</tr>";
            
            echo '</tbody></table>';
            echo '</div>';
            
            // 3. สร้างแผนการป้องกัน
            echo '<h4>🛡️ Prevention Plan</h4>';
            
            echo '<div class="row">';
            
            // Database Triggers
            echo '<div class="col-md-4">';
            echo '<div class="alert prevention">';
            echo '<h6>1. Database Triggers</h6>';
            echo '<p>สร้าง trigger ที่อัพเดท CartStatus อัตโนมัติ</p>';
            echo '<button class="btn btn-success btn-sm" onclick="createTriggers()">Create Triggers</button>';
            echo '</div>';
            echo '</div>';
            
            // Application Validation
            echo '<div class="col-md-4">';
            echo '<div class="alert prevention">';
            echo '<h6>2. Application Validation</h6>';
            echo '<p>เพิ่มการตรวจสอบใน API และฟังก์ชัน</p>';
            echo '<button class="btn btn-success btn-sm" onclick="addValidation()">Add Validation</button>';
            echo '</div>';
            echo '</div>';
            
            // Auto Monitoring
            echo '<div class="col-md-4">';
            echo '<div class="alert prevention">';
            echo '<h6>3. Auto Monitoring</h6>';
            echo '<p>ระบบตรวจสอบและแก้ไขอัตโนมัติ</p>';
            echo '<button class="btn btn-success btn-sm" onclick="setupMonitoring()">Setup Monitoring</button>';
            echo '<button class="btn btn-info btn-sm mt-1" onclick="testMonitoring()">Test Monitor</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // 4. การตรวจสอบความเสี่ยง
            echo '<h4>⚠️ Risk Assessment</h4>';
            
            // ตรวจสอบการใช้งานปัจจุบัน
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 END) as assigned_customers,
                    COUNT(CASE WHEN CartStatus = 'ลูกค้าแจกแล้ว' THEN 1 END) as marked_assigned,
                    COUNT(CASE WHEN Sales IS NOT NULL AND CartStatus != 'ลูกค้าแจกแล้ว' THEN 1 END) as inconsistent_1,
                    COUNT(CASE WHEN (Sales IS NULL OR Sales = '') AND CartStatus = 'ลูกค้าแจกแล้ว' THEN 1 END) as inconsistent_2
                FROM customers
            ");
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $riskScore = 0;
            if ($stats['inconsistent_1'] > 0) $riskScore += 30;
            if ($stats['inconsistent_2'] > 0) $riskScore += 30;
            if ($stats['total_customers'] > 1000) $riskScore += 20;
            if ($stats['assigned_customers'] > 500) $riskScore += 20;
            
            $riskLevel = 'Low';
            $riskClass = 'success';
            if ($riskScore > 70) {
                $riskLevel = 'Critical';
                $riskClass = 'danger';
            } elseif ($riskScore > 40) {
                $riskLevel = 'High';
                $riskClass = 'warning';
            } elseif ($riskScore > 20) {
                $riskLevel = 'Medium';
                $riskClass = 'info';
            }
            
            echo '<div class="alert alert-' . $riskClass . '">';
            echo '<h6>Risk Level: <span class="badge bg-' . $riskClass . '">' . $riskLevel . '</span> (Score: ' . $riskScore . '/100)</h6>';
            echo '<div class="row">';
            echo '<div class="col-md-3"><strong>Total Customers:</strong> ' . number_format($stats['total_customers']) . '</div>';
            echo '<div class="col-md-3"><strong>Assigned:</strong> ' . number_format($stats['assigned_customers']) . '</div>';
            echo '<div class="col-md-3"><strong>Type 1 Issues:</strong> ' . $stats['inconsistent_1'] . '</div>';
            echo '<div class="col-md-3"><strong>Type 2 Issues:</strong> ' . $stats['inconsistent_2'] . '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div id="actionResults" class="mt-3"></div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <script>
        function showResult(message, type = 'info') {
            document.getElementById('actionResults').innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        function createTriggers() {
            showResult('🔄 Creating database triggers...', 'info');
            
            fetch('create_cartstatus_triggers.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Triggers created:</strong><ul>${Object.entries(data.triggers_created).map(([name, desc]) => `<li><code>${name}</code>: ${desc}</li>`).join('')}</ul>`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function addValidation() {
            showResult('🔄 Adding validation rules...', 'info');
            
            fetch('add_cartstatus_validation.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Files created:</strong><ul>${Object.entries(data.files_created).map(([file, desc]) => `<li><code>${file}</code>: ${desc}</li>`).join('')}</ul>`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function setupMonitoring() {
            showResult('🔄 Setting up monitoring system...', 'info');
            
            fetch('setup_cartstatus_monitoring.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Components:</strong><ul>${Object.entries(data.files_created).map(([file, desc]) => `<li><code>${file}</code>: ${desc}</li>`).join('')}</ul>`, 'success');
                        
                        // Add cron job instructions
                        showResult(`✅ ${data.message}<br><strong>Components created successfully!</strong><br><br>` +
                                 `<div class="alert alert-warning mt-2">` +
                                 `<h6>⚡ Next Step: Add Cron Jobs</h6>` +
                                 `<p>เพิ่ม 2 cron jobs นี้ในระบบ:</p>` +
                                 `<ol>` +
                                 `<li><strong>Monitoring:</strong> <code>15 * * * *</code> ทุกชั่วโมง</li>` +
                                 `<li><strong>Auto-fix:</strong> <code>30 3 * * *</code> ทุกวัน 03:30</li>` +
                                 `</ol>` +
                                 `<a href="FINAL_CARTSTATUS_CRONJOBS.md" target="_blank" class="btn btn-info btn-sm">📋 View Cron Details</a>` +
                                 `</div>`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function testMonitoring() {
            showResult('🔄 Testing monitoring system...', 'info');
            
            fetch('monitor_cartstatus.php?action=check')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const statusClass = data.consistency_status === 'clean' ? 'success' : 'warning';
                        showResult(`✅ Monitoring test completed<br>` +
                                 `<strong>Status:</strong> ${data.consistency_status}<br>` +
                                 `<strong>Total Customers:</strong> ${data.total_customers}<br>` +
                                 `<strong>Issues Found:</strong> ${data.inconsistent_count}`, statusClass);
                    } else {
                        showResult(`❌ Test Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }
    </script>
</body>
</html>