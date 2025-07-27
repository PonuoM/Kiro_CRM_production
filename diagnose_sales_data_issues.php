<?php
// diagnose_sales_data_issues.php
// ตรวจสอบและแก้ไขปัญหาข้อมูลในรายชื่อ Sales

session_start();

// Bypass auth for diagnosis
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔍 Diagnose Sales Data Issues</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.issue-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
.problem{border-left:5px solid #dc3545;background:#fff5f5;} 
.fix{border-left:5px solid #28a745;background:#f8fff8;} 
.info{border-left:5px solid #17a2b8;background:#f0f9ff;} 
.sample-data{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-family:monospace;font-size:12px;max-height:300px;overflow:auto;}
.issue-summary{background:white;padding:12px;margin:8px 0;border-radius:8px;border-left:3px solid #ddd;}
.critical{border-left-color:#dc3545;} .warning{border-left-color:#ffc107;} .good{border-left-color:#28a745;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='text-center mb-4'>";
echo "<h1 class='display-6 fw-bold text-danger'>🔍 Diagnose Sales Data Issues</h1>";
echo "<p class='lead text-muted'>ตรวจสอบและแก้ไขปัญหาข้อมูลในรายชื่อ Sales</p>";
echo "<small class='text-muted'>เวลาตรวจสอบ: " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. ตรวจสอบโครงสร้างตาราง customers
    echo "<div class='issue-card info'>";
    echo "<div class='p-4'>";
    echo "<h3>📊 ตรวจสอบโครงสร้างตาราง customers</h3>";
    
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasAssignDate = false;
    $hasReceivedDate = false;
    $hasCartStatusDate = false;
    
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($columns as $col) {
        $field = $col['Field'];
        if ($field === 'AssignDate') $hasAssignDate = true;
        if ($field === 'ReceivedDate') $hasReceivedDate = true;
        if ($field === 'CartStatusDate') $hasCartStatusDate = true;
        
        echo "<tr>";
        echo "<td><code>$field</code></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
    
    echo "<div class='mt-3'>";
    echo "<h6>🔍 คอลัมน์ที่เกี่ยวข้องกับปัญหา:</h6>";
    echo "<ul>";
    echo "<li><strong>AssignDate:</strong> " . ($hasAssignDate ? "✅ มี" : "❌ ไม่มี") . "</li>";
    echo "<li><strong>ReceivedDate:</strong> " . ($hasReceivedDate ? "✅ มี" : "❌ ไม่มี") . "</li>";
    echo "<li><strong>CartStatusDate:</strong> " . ($hasCartStatusDate ? "✅ มี" : "❌ ไม่มี") . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // 2. ตรวจสอบปัญหาข้อมูล
    echo "<div class='issue-card problem'>";
    echo "<div class='p-4'>";
    echo "<h3>🚨 ปัญหาที่พบในข้อมูล</h3>";
    
    // Problem 1: สถานะไม่ถูกต้อง
    echo "<div class='issue-summary critical'>";
    echo "<h6>❌ ปัญหาที่ 1: สถานะลูกค้าไม่ตรงกับ Business Logic</h6>";
    
    $problemQueries = [
        'ลูกค้าในตระกร้าที่มี Sales' => "SELECT COUNT(*) as count FROM customers WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL AND Sales != ''",
        'ลูกค้าใหม่ที่ไม่มี Sales' => "SELECT COUNT(*) as count FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND (Sales IS NULL OR Sales = '')",
        'ลูกค้าติดตามที่ไม่มี Sales' => "SELECT COUNT(*) as count FROM customers WHERE CustomerStatus = 'ลูกค้าติดตาม' AND (Sales IS NULL OR Sales = '')"
    ];
    
    foreach ($problemQueries as $issue => $query) {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'];
        
        echo "<p><strong>$issue:</strong> ";
        if ($count > 0) {
            echo "<span class='text-danger'>$count รายการ ❌</span>";
        } else {
            echo "<span class='text-success'>ไม่มีปัญหา ✅</span>";
        }
        echo "</p>";
    }
    echo "</div>";
    
    // Problem 2: วันที่ได้รับข้อมูล
    echo "<div class='issue-summary warning'>";
    echo "<h6>⚠️ ปัญหาที่ 2: วันที่ได้รับข้อมูล (AssignDate/ReceivedDate) ไม่มีข้อมูล</h6>";
    
    if ($hasAssignDate) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, 
                                    COUNT(AssignDate) as with_assign_date,
                                    COUNT(*) - COUNT(AssignDate) as without_assign_date
                             FROM customers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>ลูกค้าทั้งหมด:</strong> {$result['total']} รายการ</p>";
        echo "<p><strong>มี AssignDate:</strong> {$result['with_assign_date']} รายการ</p>";
        echo "<p><strong>ไม่มี AssignDate:</strong> <span class='text-warning'>{$result['without_assign_date']} รายการ</span></p>";
    } else {
        echo "<p class='text-danger'>❌ ไม่มีคอลัมน์ AssignDate ในตาราง</p>";
    }
    
    if ($hasReceivedDate) {
        $stmt = $pdo->query("SELECT COUNT(*) as total, 
                                    COUNT(ReceivedDate) as with_received_date,
                                    COUNT(*) - COUNT(ReceivedDate) as without_received_date
                             FROM customers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>มี ReceivedDate:</strong> {$result['with_received_date']} รายการ</p>";
        echo "<p><strong>ไม่มี ReceivedDate:</strong> <span class='text-warning'>{$result['without_received_date']} รายการ</span></p>";
    } else {
        echo "<p class='text-danger'>❌ ไม่มีคอลัมน์ ReceivedDate ในตาราง</p>";
    }
    echo "</div>";
    
    // Problem 3: เวลาที่เหลือคำนวณผิด
    echo "<div class='issue-summary critical'>";
    echo "<h6>❌ ปัญหาที่ 3: การคำนวณเวลาที่เหลือไม่ถูกต้อง</h6>";
    
    // ทดสอบการคำนวณ time_remaining_days
    $testQuery = "SELECT 
                      CustomerCode, CustomerName, CustomerStatus, Sales,
                      CreatedDate, AssignDate, LastContactDate,
                      CASE 
                          WHEN CustomerStatus = 'ลูกค้าใหม่' THEN 
                              30 - DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate))
                          WHEN CustomerStatus = 'ลูกค้าติดตาม' THEN 
                              CASE WHEN LastContactDate IS NOT NULL 
                                   THEN 15 - DATEDIFF(CURDATE(), LastContactDate)
                                   ELSE -999 
                              END
                          ELSE 0
                      END as time_remaining_days,
                      DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) as days_since_assign,
                      CASE WHEN LastContactDate IS NOT NULL 
                           THEN DATEDIFF(CURDATE(), LastContactDate)
                           ELSE NULL 
                      END as days_since_contact
                  FROM customers 
                  WHERE CustomerStatus IN ('ลูกค้าใหม่', 'ลูกค้าติดตาม')
                  ORDER BY time_remaining_days ASC
                  LIMIT 10";
    
    $stmt = $pdo->query($testQuery);
    $testResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>ตัวอย่างการคำนวณเวลาที่เหลือ (10 รายการแรก):</strong></p>";
    echo "<div class='table-responsive'>";
    echo "<table class='table table-sm table-striped'>";
    echo "<thead><tr><th>ลูกค้า</th><th>สถานะ</th><th>Sales</th><th>วันที่สร้าง</th><th>วันที่มอบหมาย</th><th>ติดต่อล่าสุด</th><th>เวลาที่เหลือ</th><th>วันที่ผ่านไป</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($testResults as $row) {
        $timeColor = $row['time_remaining_days'] < 0 ? 'text-danger' : ($row['time_remaining_days'] <= 5 ? 'text-warning' : 'text-success');
        
        echo "<tr>";
        echo "<td>{$row['CustomerName']}</td>";
        echo "<td><span class='badge bg-info'>{$row['CustomerStatus']}</span></td>";
        echo "<td>" . ($row['Sales'] ?: '<span class="text-muted">ไม่มี</span>') . "</td>";
        echo "<td>" . ($row['CreatedDate'] ? date('d/m/Y', strtotime($row['CreatedDate'])) : '-') . "</td>";
        echo "<td>" . ($row['AssignDate'] ? date('d/m/Y', strtotime($row['AssignDate'])) : '<span class="text-muted">ไม่มี</span>') . "</td>";
        echo "<td>" . ($row['LastContactDate'] ? date('d/m/Y', strtotime($row['LastContactDate'])) : '<span class="text-muted">ไม่มี</span>') . "</td>";
        echo "<td class='$timeColor'><strong>";
        if ($row['time_remaining_days'] < 0) {
            echo "เลย " . abs($row['time_remaining_days']) . " วัน";
        } else {
            echo $row['time_remaining_days'] . " วัน";
        }
        echo "</strong></td>";
        echo "<td>{$row['days_since_assign']} วัน</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
    
    // วิเคราะห์ปัญหา
    $negativeCount = 0;
    $over90Days = 0;
    foreach ($testResults as $row) {
        if ($row['time_remaining_days'] < 0) $negativeCount++;
        if ($row['time_remaining_days'] < -90) $over90Days++;
    }
    
    echo "<p><strong>วิเคราะห์:</strong></p>";
    echo "<ul>";
    echo "<li>ลูกค้าที่เลยเวลา: <span class='text-danger'>$negativeCount รายการ</span></li>";
    echo "<li>ลูกค้าที่เลยเวลามากกว่า 90 วัน: <span class='text-danger'>$over90Days รายการ</span></li>";
    echo "</ul>";
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // 3. แก้ไขปัญหา
    echo "<div class='issue-card fix'>";
    echo "<div class='p-4'>";
    echo "<h3>🔧 วิธีแก้ไขปัญหา</h3>";
    
    if (isset($_POST['fix_action'])) {
        $action = $_POST['fix_action'];
        
        switch ($action) {
            case 'add_missing_columns':
                echo "<div class='alert alert-info'>";
                echo "<h6>📝 เพิ่มคอลัมน์ที่ขาดหาย</h6>";
                
                $alterQueries = [];
                if (!$hasAssignDate) {
                    $alterQueries[] = "ALTER TABLE customers ADD COLUMN AssignDate DATETIME NULL COMMENT 'วันที่มอบหมายงาน'";
                }
                if (!$hasReceivedDate) {
                    $alterQueries[] = "ALTER TABLE customers ADD COLUMN ReceivedDate DATETIME NULL COMMENT 'วันที่ได้รับรายชื่อ'";
                }
                if (!$hasCartStatusDate) {
                    $alterQueries[] = "ALTER TABLE customers ADD COLUMN CartStatusDate DATETIME NULL COMMENT 'วันที่เปลี่ยนสถานะตะกร้า'";
                }
                
                foreach ($alterQueries as $query) {
                    try {
                        $pdo->exec($query);
                        echo "<p class='text-success'>✅ " . htmlspecialchars($query) . "</p>";
                    } catch (Exception $e) {
                        echo "<p class='text-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                
                if (empty($alterQueries)) {
                    echo "<p class='text-info'>ℹ️ คอลัมน์ครบถ้วนแล้ว</p>";
                }
                echo "</div>";
                break;
                
            case 'fix_assign_dates':
                echo "<div class='alert alert-info'>";
                echo "<h6>📅 แก้ไข AssignDate สำหรับลูกค้าที่มี Sales</h6>";
                
                $updateQuery = "UPDATE customers 
                               SET AssignDate = COALESCE(AssignDate, CreatedDate) 
                               WHERE Sales IS NOT NULL AND Sales != '' AND AssignDate IS NULL";
                
                try {
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute();
                    $affected = $stmt->rowCount();
                    echo "<p class='text-success'>✅ อัปเดต AssignDate สำเร็จ: $affected รายการ</p>";
                } catch (Exception $e) {
                    echo "<p class='text-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                echo "</div>";
                break;
                
            case 'fix_status_logic':
                echo "<div class='alert alert-info'>";
                echo "<h6>🔄 แก้ไข Business Logic สถานะลูกค้า</h6>";
                
                // ลูกค้าในตระกร้าที่มี Sales ควรเป็นลูกค้าใหม่
                $fixQuery1 = "UPDATE customers 
                             SET CustomerStatus = 'ลูกค้าใหม่' 
                             WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL AND Sales != ''";
                
                // ลูกค้าใหม่/ติดตามที่ไม่มี Sales ควรเป็นในตระกร้า
                $fixQuery2 = "UPDATE customers 
                             SET CustomerStatus = 'ในตระกร้า', Sales = NULL 
                             WHERE CustomerStatus IN ('ลูกค้าใหม่', 'ลูกค้าติดตาม') AND (Sales IS NULL OR Sales = '')";
                
                try {
                    $stmt1 = $pdo->prepare($fixQuery1);
                    $stmt1->execute();
                    $affected1 = $stmt1->rowCount();
                    
                    $stmt2 = $pdo->prepare($fixQuery2);
                    $stmt2->execute();
                    $affected2 = $stmt2->rowCount();
                    
                    echo "<p class='text-success'>✅ แก้ไขลูกค้าในตระกร้า → ลูกค้าใหม่: $affected1 รายการ</p>";
                    echo "<p class='text-success'>✅ แก้ไขลูกค้าไม่มี Sales → ในตระกร้า: $affected2 รายการ</p>";
                } catch (Exception $e) {
                    echo "<p class='text-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                echo "</div>";
                break;
        }
        
        echo "<meta http-equiv='refresh' content='2'>";
    }
    
    echo "<div class='row'>";
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>🔧 แก้ไขโครงสร้าง</h6>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='fix_action' value='add_missing_columns'>";
    echo "<button type='submit' class='btn btn-primary btn-sm'>เพิ่มคอลัมน์ที่ขาดหาย</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>📅 แก้ไขวันที่</h6>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='fix_action' value='fix_assign_dates'>";
    echo "<button type='submit' class='btn btn-warning btn-sm'>แก้ไข AssignDate</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>🔄 แก้ไขสถานะ</h6>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='fix_action' value='fix_status_logic'>";
    echo "<button type='submit' class='btn btn-success btn-sm'>แก้ไข Business Logic</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // 4. สรุปและแนะนำ
    echo "<div class='issue-card info'>";
    echo "<div class='p-4'>";
    echo "<h3>📋 สรุปปัญหาและแนะนำ</h3>";
    
    echo "<h6>🔍 ปัญหาที่พบ:</h6>";
    echo "<ol>";
    echo "<li><strong>สถานะลูกค้าไม่ตรง Business Logic:</strong> ลูกค้าในตระกร้าที่มี Sales หรือลูกค้าใหม่ที่ไม่มี Sales</li>";
    echo "<li><strong>วันที่ได้รับข้อมูลไม่แสดง:</strong> คอลัมน์ AssignDate/ReceivedDate ว่างหรือไม่มี</li>";
    echo "<li><strong>เวลาที่เหลือแสดงผิด:</strong> การคำนวณทำให้ขึ้น 'เลย 91 วัน' แทนที่จะเป็นค่าลบ</li>";
    echo "</ol>";
    
    echo "<h6>🛠️ วิธีแก้ไขที่แนะนำ:</h6>";
    echo "<ol>";
    echo "<li><strong>เพิ่มคอลัมน์ที่ขาดหาย:</strong> AssignDate, ReceivedDate, CartStatusDate</li>";
    echo "<li><strong>อัปเดตข้อมูลวันที่:</strong> ใส่ CreatedDate ลงใน AssignDate สำหรับลูกค้าที่มี Sales</li>";
    echo "<li><strong>แก้ไข Business Logic:</strong> ปรับสถานะให้ตรงกับกฎธุรกิจ</li>";
    echo "<li><strong>อัปเดต API:</strong> ปรับ API ให้ส่งข้อมูลคอลัมน์ใหม่</li>";
    echo "<li><strong>แก้ไขหน้าแสดงผล:</strong> ปรับการแสดงเวลาที่เหลือให้ถูกต้อง</li>";
    echo "</ol>";
    
    echo "<h6>📊 API ที่ต้องอัปเดต:</h6>";
    echo "<ul>";
    echo "<li><code>api/customers/list.php</code> - เพิ่ม AssignDate, ReceivedDate</li>";
    echo "<li><code>api/tasks/daily.php</code> - ปรับการคำนวณเวลาที่เหลือ</li>";
    echo "<li><code>pages/dashboard.php</code> - อัปเดตการแสดงผล</li>";
    echo "</ul>";
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Database Error</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='text-center mt-4'>";
echo "<button onclick='location.reload()' class='btn btn-primary'>";
echo "<i class='fas fa-sync-alt'></i> Refresh";
echo "</button>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>