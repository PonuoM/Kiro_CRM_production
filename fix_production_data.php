<?php
// fix_production_data.php
// แก้ไขข้อมูลลูกค้าให้ตรงกับ Business Logic ฉบับ Production
// รันเมื่อปัญหาแล้วเท่านั้น - ไม่ใช่ script ประจำ

session_start();

// Enhanced auth check
if (!isset($_SESSION['user_login']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied. Admin only.");
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔧 Production Data Fix</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.fix-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
.preview{border-left:4px solid #17a2b8;background:linear-gradient(135deg,#e1f5fe,#f0f9ff);} 
.execute{border-left:4px solid #28a745;background:linear-gradient(135deg,#e8f5e9,#f1f8e9);} 
.warning{border-left:4px solid #ffc107;background:linear-gradient(135deg,#fff8e1,#fffbf0);} 
.danger{border-left:4px solid #dc3545;background:linear-gradient(135deg,#ffebee,#fff5f5);} 
.step-header{background:linear-gradient(135deg,#1976d2,#42a5f5);color:white;border-radius:8px 8px 0 0;padding:15px 20px;margin:-15px -20px 15px -20px;}
.metric{background:white;border-radius:8px;padding:12px;margin:8px 0;border-left:3px solid #ddd;}
.metric.success{border-left-color:#28a745;} .metric.warning{border-left-color:#ffc107;} .metric.danger{border-left-color:#dc3545;}
pre{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-size:12px;max-height:300px;overflow:auto;}
.progress-ring{width:60px;height:60px;} .progress-ring circle{fill:none;stroke-width:6;transform:rotate(-90deg);transform-origin:50% 50%;}
.btn-group-custom{gap:10px;margin:15px 0;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='row'>";
echo "<div class='col-md-12'>";

echo "<div class='text-center mb-4'>";
echo "<h1 class='display-5 fw-bold text-primary'>🔧 Production Data Fix</h1>";
echo "<p class='lead text-muted'>แก้ไขข้อมูลลูกค้าให้ตรงกับ Business Logic</p>";
echo "</div>";

$executeMode = isset($_GET['execute']) && $_GET['execute'] === 'true';
$confirmCode = $_GET['confirm'] ?? '';
$validConfirmCode = "KIRO-FIX-" . date('Ymd');

if ($executeMode && $confirmCode !== $validConfirmCode) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>🚨 Invalid Confirmation Code</h4>";
    echo "<p>กรุณาใส่รหัสยืนยันที่ถูกต้อง: <strong>$validConfirmCode</strong></p>";
    echo "<a href='?' class='btn btn-secondary'>กลับไป Preview Mode</a>";
    echo "</div>";
    echo "</div></div></div></body></html>";
    exit;
}

if (!$executeMode) {
    echo "<div class='alert alert-warning fix-card'>";
    echo "<div class='step-header'><h4><i class='fas fa-exclamation-triangle'></i> Preview Mode - ไม่มีการเปลี่ยนแปลงจริง</h4></div>";
    echo "<p class='mb-3'>กำลังแสดงการเปลี่ยนแปลงที่จะเกิดขึ้น</p>";
    echo "<div class='btn-group-custom d-flex'>";
    echo "<a href='?execute=true&confirm=$validConfirmCode' class='btn btn-danger btn-lg flex-fill'>";
    echo "<i class='fas fa-rocket'></i> Execute Production Fix";
    echo "</a>";
    echo "</div>";
    echo "<div class='alert alert-info mt-3'>";
    echo "<strong>⚠️ คำเตือน:</strong> การดำเนินการนี้จะเปลี่ยนแปลงข้อมูลจริงในฐานข้อมูล<br>";
    echo "<strong>รหัสยืนยัน:</strong> <code>$validConfirmCode</code>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success fix-card'>";
    echo "<div class='step-header'><h4><i class='fas fa-cogs'></i> Execute Mode - กำลังดำเนินการแก้ไขข้อมูลจริง</h4></div>";
    echo "<p>รหัสยืนยัน: <code>$confirmCode</code> ✅</p>";
    echo "</div>";
}

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    if ($executeMode) {
        $pdo->beginTransaction();
        $startTime = microtime(true);
    }
    
    // Summary Statistics First
    echo "<div class='fix-card warning'>";
    echo "<div class='step-header'><h3><i class='fas fa-chart-bar'></i> Current Data Analysis</h3></div>";
    
    $issues = [
        'basket_with_sales' => [
            'name' => 'ลูกค้าในตระกร้าที่มี Sales',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL",
            'severity' => 'danger'
        ],
        'new_without_sales' => [
            'name' => 'ลูกค้าใหม่ที่ไม่มี Sales',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL",
            'severity' => 'warning'
        ],
        'new_overdue_30' => [
            'name' => 'ลูกค้าใหม่เลยเวลา 30+ วัน',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30",
            'severity' => 'danger'
        ],
        'follow_overdue_14' => [
            'name' => 'ลูกค้าติดตามเลยเวลา 14+ วัน',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14",
            'severity' => 'warning'
        ],
        'old_overdue_90' => [
            'name' => 'ลูกค้าเก่าเลยเวลา 90+ วัน',
            'query' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90",
            'severity' => 'warning'
        ],
        'invalid_temp' => [
            'name' => 'Temperature ไม่สอดคล้อง',
            'query' => "SELECT COUNT(*) FROM customers WHERE (CustomerTemperature = 'HOT' AND COALESCE(DATEDIFF(CURDATE(), LastContactDate), 999) > 7) OR (CustomerTemperature = 'FROZEN' AND Sales IS NOT NULL AND CustomerStatus != 'ในตระกร้า')",
            'severity' => 'warning'
        ]
    ];
    
    echo "<div class='row'>";
    $totalIssues = 0;
    foreach ($issues as $key => $issue) {
        $stmt = $pdo->prepare($issue['query']);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $totalIssues += $count;
        
        $metricClass = $count > 0 ? $issue['severity'] : 'success';
        
        echo "<div class='col-md-4 mb-3'>";
        echo "<div class='metric $metricClass'>";
        echo "<h6 class='mb-1'>{$issue['name']}</h6>";
        echo "<h4 class='mb-0'>$count รายการ</h4>";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='alert " . ($totalIssues > 0 ? 'alert-danger' : 'alert-success') . "'>";
    echo "<h5><i class='fas fa-exclamation-circle'></i> รวมปัญหาทั้งหมด: <strong>$totalIssues</strong> รายการ</h5>";
    echo "</div>";
    
    echo "</div>";
    
    // Fix 1: ลูกค้าในตระกร้าที่มี Sales
    echo "<div class='fix-card " . ($executeMode ? 'execute' : 'preview') . "'>";
    echo "<div class='step-header'><h3><i class='fas fa-shopping-basket'></i> Fix 1: ลูกค้าในตระกร้าที่มี Sales</h3></div>";
    
    $sql = "SELECT CustomerCode, CustomerName, Sales, AssignDate FROM customers WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $basketWithSales = $stmt->fetchAll();
    
    if ($basketWithSales) {
        echo "<div class='alert alert-info'>";
        echo "<strong>Logic:</strong> ลูกค้าในตระกร้า = ไม่มี Sales ดูแล → ลบ Sales ออก";
        echo "</div>";
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm'>";
        echo "<thead class='table-dark'><tr><th>CustomerCode</th><th>CustomerName</th><th>Sales</th><th>AssignDate</th><th>Action</th></tr></thead><tbody>";
        
        foreach (array_slice($basketWithSales, 0, 10) as $row) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($row['CustomerCode']) . "</code></td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td><span class='badge bg-warning'>" . htmlspecialchars($row['Sales']) . "</span></td>";
            echo "<td>" . ($row['AssignDate'] ? date('d/m/Y', strtotime($row['AssignDate'])) : '-') . "</td>";
            echo "<td><span class='badge bg-danger'>ลบ Sales</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
        
        if (count($basketWithSales) > 10) {
            echo "<div class='alert alert-secondary'>... และอีก " . (count($basketWithSales) - 10) . " รายการ</div>";
        }
        
        if ($executeMode) {
            $updateSql = "UPDATE customers SET Sales = NULL WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL";
            $stmt = $pdo->prepare($updateSql);
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> แก้ไขเรียบร้อย: <strong>$affected</strong> รายการ</div>";
        }
    } else {
        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> ไม่พบปัญหา</div>";
    }
    
    echo "</div>";
    
    // Fix 2: ลูกค้าใหม่ที่ไม่มี Sales
    echo "<div class='fix-card " . ($executeMode ? 'execute' : 'preview') . "'>";
    echo "<div class='step-header'><h3><i class='fas fa-user-plus'></i> Fix 2: ลูกค้าใหม่ที่ไม่มี Sales</h3></div>";
    
    $sql = "SELECT CustomerCode, CustomerName, AssignDate, CreatedDate FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL LIMIT 15";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $newWithoutSales = $stmt->fetchAll();
    
    if ($newWithoutSales) {
        echo "<div class='alert alert-info'>";
        echo "<strong>Logic:</strong> ลูกค้าใหม่ต้องมี Sales → ส่งกลับตระกร้า";
        echo "</div>";
        
        echo "<div class='table-responsive'>";
        echo "<table class='table table-sm'>";
        echo "<thead class='table-dark'><tr><th>CustomerCode</th><th>CustomerName</th><th>AssignDate</th><th>CreatedDate</th><th>Action</th></tr></thead><tbody>";
        
        foreach (array_slice($newWithoutSales, 0, 10) as $row) {
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($row['CustomerCode']) . "</code></td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . ($row['AssignDate'] ? date('d/m/Y', strtotime($row['AssignDate'])) : '-') . "</td>";
            echo "<td>" . ($row['CreatedDate'] ? date('d/m/Y', strtotime($row['CreatedDate'])) : '-') . "</td>";
            echo "<td><span class='badge bg-info'>→ ในตระกร้า</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        echo "</div>";
        
        if ($executeMode) {
            $updateSql = "UPDATE customers SET CustomerStatus = 'ในตระกร้า', AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL";
            $stmt = $pdo->prepare($updateSql);
            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<div class='alert alert-success'><i class='fas fa-check'></i> ส่งกลับตระกร้า: <strong>$affected</strong> รายการ</div>";
        }
    } else {
        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> ไม่พบปัญหา</div>";
    }
    
    echo "</div>";
    
    // Fix 3: Auto-reassign ลูกค้าเลยเวลา
    echo "<div class='fix-card " . ($executeMode ? 'execute' : 'preview') . "'>";
    echo "<div class='step-header'><h3><i class='fas fa-clock'></i> Fix 3: Auto-reassign ลูกค้าเลยเวลา</h3></div>";
    
    echo "<div class='row'>";
    
    // New customers > 30 days
    echo "<div class='col-md-6'>";
    echo "<h5>ลูกค้าใหม่เลย 30 วัน</h5>";
    $sql = "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdueNew = $stmt->fetchColumn();
    
    if ($overdueNew > 0) {
        echo "<div class='metric danger'><h4>$overdueNew รายการ</h4><small>→ ส่งกลับตระกร้า</small></div>";
        
        if ($executeMode) {
            $updateSql = "UPDATE customers SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30";
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute();
            $affected = $stmt->rowCount();
            echo "<div class='alert alert-success'>✅ ส่งกลับตระกร้า: $affected รายการ</div>";
        }
    } else {
        echo "<div class='metric success'><h4>0 รายการ</h4><small>ไม่มีปัญหา</small></div>";
    }
    echo "</div>";
    
    // Follow customers > 14 days
    echo "<div class='col-md-6'>";
    echo "<h5>ลูกค้าติดตามเลย 14 วัน</h5>";
    $sql = "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdueFollow = $stmt->fetchColumn();
    
    if ($overdueFollow > 0) {
        echo "<div class='metric warning'><h4>$overdueFollow รายการ</h4><small>→ ปรับสถานะ</small></div>";
        
        if ($executeMode) {
            // > 30 days -> old customers
            $updateSql1 = "UPDATE customers SET CustomerStatus = 'ลูกค้าเก่า' WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 30";
            $stmt = $pdo->prepare($updateSql1);
            $stmt->execute();
            $toOld = $stmt->rowCount();
            
            // 14-30 days -> basket
            $updateSql2 = "UPDATE customers SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) BETWEEN 15 AND 30";
            $stmt = $pdo->prepare($updateSql2);
            $stmt->execute();
            $toBasket = $stmt->rowCount();
            
            echo "<div class='alert alert-success'>✅ เปลี่ยนเป็นลูกค้าเก่า: $toOld รายการ</div>";
            echo "<div class='alert alert-success'>✅ ส่งกลับตระกร้า: $toBasket รายการ</div>";
        }
    } else {
        echo "<div class='metric success'><h4>0 รายการ</h4><small>ไม่มีปัญหา</small></div>";
    }
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    // Fix 4: Smart Grade/Temperature Reset
    echo "<div class='fix-card " . ($executeMode ? 'execute' : 'preview') . "'>";
    echo "<div class='step-header'><h3><i class='fas fa-thermometer-half'></i> Fix 4: Smart Grade/Temperature Reset</h3></div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h6><i class='fas fa-brain'></i> Smart Logic:</h6>";
    echo "<ul class='mb-0'>";
    echo "<li><strong>Temperature:</strong> ตามระยะเวลาติดต่อล่าสุด (≤3=HOT, ≤7=WARM, ≤14=COLD, >14=FROZEN)</li>";
    echo "<li><strong>Grade:</strong> ตามสถานะและ Temperature (ลูกค้าใหม่+HOT/WARM=A, ติดตาม+WARM/COLD=B, เก่า=C, อื่นๆ=D)</li>";
    echo "</ul>";
    echo "</div>";
    
    if ($executeMode) {
        // Update Temperature
        $tempUpdateSql = "UPDATE customers SET 
            CustomerTemperature = CASE 
                WHEN Sales IS NULL OR CustomerStatus = 'ในตระกร้า' THEN 'FROZEN'
                WHEN LastContactDate IS NULL OR DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 'FROZEN'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 3 THEN 'HOT'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 7 THEN 'WARM'
                WHEN DATEDIFF(CURDATE(), LastContactDate) <= 14 THEN 'COLD'
                ELSE 'FROZEN'
            END";
        
        $stmt = $pdo->prepare($tempUpdateSql);
        $stmt->execute();
        $tempUpdated = $stmt->rowCount();
        
        // Update Grade
        $gradeUpdateSql = "UPDATE customers SET 
            CustomerGrade = CASE 
                WHEN CustomerStatus = 'ในตระกร้า' OR CustomerTemperature = 'FROZEN' THEN 'D'
                WHEN CustomerStatus = 'ลูกค้าใหม่' AND CustomerTemperature IN ('HOT', 'WARM') THEN 'A'
                WHEN CustomerStatus = 'ลูกค้าติดตาม' AND CustomerTemperature IN ('WARM', 'COLD') THEN 'B'
                WHEN CustomerStatus = 'ลูกค้าเก่า' THEN 'C'
                ELSE 'D'
            END";
        
        $stmt = $pdo->prepare($gradeUpdateSql);
        $stmt->execute();
        $gradeUpdated = $stmt->rowCount();
        
        echo "<div class='row'>";
        echo "<div class='col-md-6'>";
        echo "<div class='metric success'><h4>$tempUpdated</h4><small>Temperature Updated</small></div>";
        echo "</div>";
        echo "<div class='col-md-6'>";
        echo "<div class='metric success'><h4>$gradeUpdated</h4><small>Grade Updated</small></div>";
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-secondary'><i class='fas fa-info-circle'></i> จะอัปเดต Temperature และ Grade ตาม Smart Logic</div>";
    }
    
    echo "</div>";
    
    // Final Summary
    echo "<div class='fix-card execute'>";
    echo "<div class='step-header'><h3><i class='fas fa-chart-line'></i> Summary & Results</h3></div>";
    
    if ($executeMode) {
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        $pdo->commit();
        
        echo "<div class='alert alert-success'>";
        echo "<h4><i class='fas fa-check-circle'></i> Production Fix เสร็จสมบูรณ์!</h4>";
        echo "<p>ข้อมูลลูกค้าได้รับการปรับปรุงให้ตรงกับ Business Logic</p>";
        echo "<small class='text-muted'>Execution time: {$executionTime}ms</small>";
        echo "</div>";
        
        // Log the activity
        $logSql = "INSERT INTO system_logs (log_type, message, created_at) VALUES (?, ?, NOW())";
        $stmt = $pdo->prepare($logSql);
        $stmt->execute(['production_fix', "Production data fix executed successfully in {$executionTime}ms"]);
        
        // Show updated stats
        echo "<h5>📊 สถิติหลังแก้ไข:</h5>";
        
        $postFixStats = [];
        foreach ($issues as $key => $issue) {
            $stmt = $pdo->prepare($issue['query']);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            $postFixStats[$key] = $count;
        }
        
        echo "<div class='row'>";
        foreach ($issues as $key => $issue) {
            $count = $postFixStats[$key];
            $metricClass = $count > 0 ? 'warning' : 'success';
            
            echo "<div class='col-md-4 mb-3'>";
            echo "<div class='metric $metricClass'>";
            echo "<h6 class='mb-1'>{$issue['name']}</h6>";
            echo "<h4 class='mb-0'>$count รายการ</h4>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        
        $totalRemainingIssues = array_sum($postFixStats);
        
        echo "<div class='alert alert-" . ($totalRemainingIssues > 0 ? 'warning' : 'success') . "'>";
        echo "<h5><i class='fas fa-info-circle'></i> ปัญหาคงเหลือ: <strong>$totalRemainingIssues</strong> รายการ</h5>";
        if ($totalRemainingIssues > 0) {
            echo "<p>บางปัญหาอาจต้องการการจัดการเพิ่มเติม หรืออยู่นอกเหนือ Business Logic</p>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-info'>";
        echo "<h5><i class='fas fa-eye'></i> Preview Mode</h5>";
        echo "<p>การแก้ไขข้างต้นจะดำเนินการเมื่อกด Execute</p>";
        echo "</div>";
    }
    
    echo "<div class='alert alert-warning'>";
    echo "<h6><i class='fas fa-lightbulb'></i> แนะนำขั้นตอนต่อไป:</h6>";
    echo "<ol class='mb-0'>";
    echo "<li>ติดตั้งระบบ Auto-reassign (Cron Job)</li>";
    echo "<li>ทดสอบ Dashboard ใหม่</li>";
    echo "<li>แจ้ง Sales ให้รับลูกค้าจาก Pool</li>";
    echo "<li>Monitor ระบบ 1-2 วัน</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    if ($executeMode) {
        $pdo->rollback();
    }
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='fas fa-exclamation-triangle'></i> Error</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<small class='text-muted'>File: " . $e->getFile() . " Line: " . $e->getLine() . "</small>";
    echo "</div>";
}

echo "</div>"; // col
echo "</div>"; // row
echo "</div>"; // container

echo "</body></html>";
?>