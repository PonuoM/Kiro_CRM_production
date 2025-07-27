<?php
// check_customer_data.php
// ตรวจสอบข้อมูลลูกค้าอย่างละเอียดเพื่อหาจุดที่ไม่ตรง Logic

session_start();

// Simple auth bypass for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'test_user';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔍 Check Customer Data Issues</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:15px;font-size:14px;} 
.test-section{margin:15px 0;padding:12px;border:2px solid #ddd;border-radius:8px;} 
.issue{border-color:#dc3545;background:#fff5f5;} 
.warning{border-color:#ffc107;background:#fffbf0;}
.success{border-color:#28a745;background:#f8fff8;}
table{font-size:12px;}
.highlight{background:#ffeb3b;font-weight:bold;}
.problem{color:#dc3545;font-weight:bold;}
.status-mismatch{background:#ffcdd2;}
.time-overdue{background:#ffecb3;}
.assignment-issue{background:#e1f5fe;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<h1>🔍 ตรวจสอบความถูกต้องข้อมูลลูกค้า</h1>";
echo "<p class='text-muted'>วิเคราะห์ข้อมูลลูกค้าที่ไม่ตรงกับ Business Logic</p>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Test 1: ตรวจสอบสถานะลูกค้าที่ไม่สมเหตุสมผล
    echo "<div class='test-section issue'>";
    echo "<h2>⚠️ Issue 1: สถานะลูกค้าที่ไม่สมเหตุสมผล</h2>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, CustomerStatus, Sales,
        AssignDate, CreatedDate, LastContactDate,
        DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) as days_since_assigned,
        DATEDIFF(CURDATE(), LastContactDate) as days_since_contact,
        CASE 
            WHEN CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL THEN 'มีปัญหา: ในตระกร้าแต่มี Sales ดูแล'
            WHEN CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL THEN 'มีปัญหา: ลูกค้าใหม่แต่ไม่มี Sales'
            WHEN CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30 THEN 'มีปัญหา: ลูกค้าใหม่เลยเวลา 30 วัน'
            WHEN CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14 THEN 'มีปัญหา: ลูกค้าติดตามเลยเวลา 14 วัน'
            WHEN CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90 THEN 'มีปัญหา: ลูกค้าเก่าเลยเวลา 90 วัน'
            ELSE 'สถานะปกติ'
        END as status_issue
        FROM customers 
        WHERE 1=1
        ORDER BY 
            CASE WHEN CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL THEN 1
                 WHEN CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL THEN 2
                 WHEN CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30 THEN 3
                 WHEN CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14 THEN 4
                 WHEN CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90 THEN 5
                 ELSE 6
            END,
            days_since_assigned DESC
        LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $issues = $stmt->fetchAll();
    
    if ($issues) {
        echo "<table class='table table-sm table-striped'>";
        echo "<thead>";
        echo "<tr><th>รหัส</th><th>ชื่อ</th><th>สถานะ</th><th>Sales</th><th>วันที่มอบหมาย</th><th>ติดตามล่าสุด</th><th>วันที่ผ่านมา</th><th>ปัญหา</th></tr>";
        echo "</thead><tbody>";
        
        $problemCount = 0;
        foreach ($issues as $issue) {
            $isProblem = strpos($issue['status_issue'], 'มีปัญหา') !== false;
            $rowClass = $isProblem ? 'status-mismatch' : '';
            if ($isProblem) $problemCount++;
            
            echo "<tr class='$rowClass'>";
            echo "<td><strong>" . htmlspecialchars($issue['CustomerCode']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($issue['CustomerName']) . "</td>";
            echo "<td><span class='badge bg-info'>" . htmlspecialchars($issue['CustomerStatus']) . "</span></td>";
            echo "<td>" . ($issue['Sales'] ? htmlspecialchars($issue['Sales']) : '<span class="text-muted">ไม่มี</span>') . "</td>";
            echo "<td>" . ($issue['AssignDate'] ? date('d/m/Y', strtotime($issue['AssignDate'])) : '<span class="text-muted">-</span>') . "</td>";
            echo "<td>" . ($issue['LastContactDate'] ? date('d/m/Y', strtotime($issue['LastContactDate'])) : '<span class="text-muted">-</span>') . "</td>";
            echo "<td>" . $issue['days_since_assigned'] . " วัน</td>";
            echo "<td class='" . ($isProblem ? 'problem' : '') . "'>" . htmlspecialchars($issue['status_issue']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        echo "<div class='alert alert-danger'>";
        echo "<h5>🚨 พบปัญหา " . $problemCount . " รายการ จากที่แสดง</h5>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Test 2: สถิติรวมปัญหา
    echo "<div class='test-section warning'>";
    echo "<h2>📊 สถิติปัญหาในระบบ</h2>";
    
    $problemStats = [
        'ในตระกร้าแต่มี Sales' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL",
        'ลูกค้าใหม่ไม่มี Sales' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL",
        'ลูกค้าใหม่เลยเวลา 30 วัน' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30",
        'ลูกค้าติดตามเลยเวลา 14 วัน' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 14",
        'ลูกค้าเก่าเลยเวลา 90 วัน' => "SELECT COUNT(*) FROM customers WHERE CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90",
        'ไม่มี AssignDate และ CreatedDate' => "SELECT COUNT(*) FROM customers WHERE AssignDate IS NULL AND CreatedDate IS NULL"
    ];
    
    echo "<div class='row'>";
    $totalProblems = 0;
    
    foreach ($problemStats as $description => $query) {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $totalProblems += $count;
        
        $alertClass = $count > 0 ? 'alert-warning' : 'alert-success';
        echo "<div class='col-md-6 mb-2'>";
        echo "<div class='alert $alertClass p-2'>";
        echo "<strong>$description:</strong> <span class='badge bg-danger'>$count</span> รายการ";
        echo "</div>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='alert alert-danger'>";
    echo "<h4>🚨 รวมปัญหาทั้งหมด: <span class='highlight'>$totalProblems</span> รายการ</h4>";
    echo "</div>";
    
    echo "</div>";
    
    // Test 3: ตรวจสอบ Grade และ Temperature ที่ไม่สมเหตุสมผล
    echo "<div class='test-section warning'>";
    echo "<h2>🌡️ ตรวจสอบ Grade และ Temperature</h2>";
    
    $sql = "SELECT 
        CustomerGrade, CustomerTemperature, COUNT(*) as count,
        AVG(DATEDIFF(CURDATE(), COALESCE(LastContactDate, AssignDate, CreatedDate))) as avg_days_inactive,
        COUNT(CASE WHEN Sales IS NULL THEN 1 END) as unassigned_count
        FROM customers 
        GROUP BY CustomerGrade, CustomerTemperature
        ORDER BY CustomerGrade, CustomerTemperature";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $gradeTemp = $stmt->fetchAll();
    
    echo "<table class='table table-sm'>";
    echo "<thead>";
    echo "<tr><th>Grade</th><th>Temperature</th><th>จำนวน</th><th>เฉลี่ยวันไม่ติดต่อ</th><th>ไม่มี Sales</th><th>สังเกต</th></tr>";
    echo "</thead><tbody>";
    
    foreach ($gradeTemp as $row) {
        $observation = [];
        if ($row['CustomerTemperature'] == 'HOT' && $row['avg_days_inactive'] > 7) {
            $observation[] = "HOT แต่ไม่ติดต่อนาน";
        }
        if ($row['CustomerGrade'] == 'A' && $row['unassigned_count'] > 0) {
            $observation[] = "Grade A แต่ไม่มี Sales";
        }
        if ($row['CustomerTemperature'] == 'FROZEN' && $row['unassigned_count'] == 0) {
            $observation[] = "FROZEN แต่ยังมี Sales";
        }
        
        $rowClass = !empty($observation) ? 'assignment-issue' : '';
        
        echo "<tr class='$rowClass'>";
        echo "<td><strong>" . ($row['CustomerGrade'] ?: 'NULL') . "</strong></td>";
        echo "<td><strong>" . ($row['CustomerTemperature'] ?: 'NULL') . "</strong></td>";
        echo "<td>" . $row['count'] . "</td>";
        echo "<td>" . round($row['avg_days_inactive'], 1) . " วัน</td>";
        echo "<td>" . $row['unassigned_count'] . "</td>";
        echo "<td class='problem'>" . implode(', ', $observation) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    echo "</div>";
    
    // Test 4: ตรวจสอบลูกค้าที่ต้องถูก Auto-reassign
    echo "<div class='test-section issue'>";
    echo "<h2>🔄 ลูกค้าที่ควรถูก Auto-reassign</h2>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, CustomerStatus, Sales,
        AssignDate, LastContactDate,
        DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) as days_assigned,
        DATEDIFF(CURDATE(), LastContactDate) as days_no_contact,
        CASE 
            WHEN CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 45 THEN 'ควรส่งกลับ Pool'
            WHEN CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 21 THEN 'ควรเปลี่ยน Sales'
            WHEN CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 120 THEN 'ควรเปลี่ยนเป็น FROZEN'
            ELSE NULL
        END as action_needed
        FROM customers 
        WHERE (
            (CustomerStatus = 'ลูกค้าใหม่' AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 45)
            OR (CustomerStatus = 'ลูกค้าติดตาม' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 21)
            OR (CustomerStatus = 'ลูกค้าเก่า' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 120)
        )
        ORDER BY days_assigned DESC, days_no_contact DESC
        LIMIT 15";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $autoReassign = $stmt->fetchAll();
    
    if ($autoReassign) {
        echo "<table class='table table-sm'>";
        echo "<thead>";
        echo "<tr><th>รหัส</th><th>ชื่อ</th><th>สถานะ</th><th>Sales</th><th>วันที่มอบหมาย</th><th>ติดตามล่าสุด</th><th>วันที่ผ่าน</th><th>การดำเนินการที่แนะนำ</th></tr>";
        echo "</thead><tbody>";
        
        foreach ($autoReassign as $row) {
            echo "<tr class='time-overdue'>";
            echo "<td><strong>" . htmlspecialchars($row['CustomerCode']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerStatus']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Sales']) . "</td>";
            echo "<td>" . ($row['AssignDate'] ? date('d/m/Y', strtotime($row['AssignDate'])) : '-') . "</td>";
            echo "<td>" . ($row['LastContactDate'] ? date('d/m/Y', strtotime($row['LastContactDate'])) : '-') . "</td>";
            echo "<td><strong>" . $row['days_assigned'] . " วัน</strong></td>";
            echo "<td class='problem'><strong>" . htmlspecialchars($row['action_needed']) . "</strong></td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        echo "<div class='alert alert-warning'>";
        echo "<h5>⚠️ พบลูกค้า " . count($autoReassign) . " รายการที่ต้องการการดำเนินการ</h5>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-success'>✅ ไม่พบลูกค้าที่ต้อง Auto-reassign</div>";
    }
    
    echo "</div>";
    
    // Test 5: แนะนำการแก้ไข
    echo "<div class='test-section success'>";
    echo "<h2>💡 แนะนำการแก้ไขปัญหา</h2>";
    
    echo "<h4>🔧 ปัญหาหลักที่พบ:</h4>";
    echo "<ol>";
    echo "<li><strong>สถานะไม่ตรงกับ Business Logic</strong> - ลูกค้าเลยเวลาแต่ยังอยู่กับ Sales เดิม</li>";
    echo "<li><strong>Grade/Temperature ไม่สอดคล้อง</strong> - HOT แต่ไม่ติดต่อนาน, FROZEN แต่ยังมี Sales</li>";
    echo "<li><strong>ขาดระบบ Auto-reassign</strong> - ไม่มีการจัดการลูกค้าค้างคาวอัตโนมัติ</li>";
    echo "</ol>";
    
    echo "<h4>🚀 ข้อเสนอแนะ:</h4>";
    echo "<div class='row'>";
    
    echo "<div class='col-md-6'>";
    echo "<h5>📋 ระยะสั้น (Immediate):</h5>";
    echo "<ul>";
    echo "<li>สร้างสคริปต์ทำความสะอาดข้อมูล</li>";
    echo "<li>แก้ไขสถานะลูกค้าที่ไม่ตรง Logic</li>";
    echo "<li>Reset Grade/Temperature ให้ถูกต้อง</li>";
    echo "<li>ส่งลูกค้าค้างคาวกลับ Pool</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='col-md-6'>";
    echo "<h5>🔄 ระยะยาว (Systematic):</h5>";
    echo "<ul>";
    echo "<li>พัฒนาระบบ Auto-reassign</li>";
    echo "<li>กำหนด Business Rules ที่ชัดเจน</li>";
    echo "<li>สร้างระบบแจ้งเตือนอัตโนมัติ</li>";
    echo "<li>Dashboard สำหรับติดตามปัญหา</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h5>📝 สรุป:</h5>";
    echo "<p>ข้อมูลในระบบมีปัญหาไม่ตรง Business Logic หลายจุด ต้องการการทำความสะอาดข้อมูลและพัฒนาระบบจัดการอัตโนมัติ</p>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>"; // container

echo "</body></html>";
?>