<?php
// fix_customer_logic.php
// แก้ไขข้อมูลลูกค้าให้ตรงกับ Business Logic

session_start();

// Simple auth bypass for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'test_admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔧 Fix Customer Logic Issues</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:15px;} .fix-section{margin:15px 0;padding:12px;border:2px solid #ddd;border-radius:8px;} .preview{border-color:#17a2b8;background:#f0f9ff;} .executed{border-color:#28a745;background:#f8fff8;} .warning{border-color:#ffc107;background:#fffbf0;} pre{background:#f8f9fa;padding:10px;border-radius:4px;}</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<h1>🔧 แก้ไขข้อมูลลูกค้าให้ตรง Business Logic</h1>";

$executeMode = isset($_GET['execute']) && $_GET['execute'] === 'true';

if (!$executeMode) {
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ โหมดแสดงตัวอย่าง (Preview Mode)</h4>";
    echo "<p>กำลังแสดงการเปลี่ยนแปลงที่จะเกิดขึ้น หากต้องการดำเนินการจริง ให้คลิก: ";
    echo "<a href='?execute=true' class='btn btn-danger'>🚀 Execute แก้ไขข้อมูลจริง</a></p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h4>🚀 กำลังดำเนินการแก้ไขข้อมูลจริง!</h4>";
    echo "</div>";
}

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    if ($executeMode) {
        $pdo->beginTransaction();
    }
    
    // Fix 1: ลูกค้าในตระกร้าที่มี Sales
    echo "<div class='fix-section " . ($executeMode ? 'executed' : 'preview') . "'>";
    echo "<h2>🔧 Fix 1: ลูกค้าในตระกร้าที่มี Sales</h2>";
    
    $sql = "SELECT CustomerCode, CustomerName, Sales FROM customers WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $basketWithSales = $stmt->fetchAll();
    
    if ($basketWithSales) {
        echo "<p><strong>พบปัญหา:</strong> " . count($basketWithSales) . " รายการ</p>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>Sales</th><th>การแก้ไข</th></tr></thead><tbody>";
        
        foreach ($basketWithSales as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Sales']) . "</td>";
            echo "<td><span class='badge bg-warning'>ลบ Sales, คงสถานะ ในตระกร้า</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        if ($executeMode) {
            $updateSql = "UPDATE customers SET Sales = NULL WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL";
            $stmt = $pdo->prepare($updateSql);
            $result = $stmt->execute();
            echo "<div class='alert alert-success'>✅ แก้ไขเรียบร้อย: " . $stmt->rowCount() . " รายการ</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ ไม่พบปัญหา</div>";
    }
    
    echo "</div>";
    
    // Fix 2: ลูกค้าใหม่ที่ไม่มี Sales
    echo "<div class='fix-section " . ($executeMode ? 'executed' : 'preview') . "'>";
    echo "<h2>🔧 Fix 2: ลูกค้าใหม่ที่ไม่มี Sales</h2>";
    
    $sql = "SELECT CustomerCode, CustomerName, AssignDate, CreatedDate FROM customers WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $newWithoutSales = $stmt->fetchAll();
    
    if ($newWithoutSales) {
        echo "<p><strong>พบปัญหา:</strong> " . count($newWithoutSales) . " รายการ</p>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>AssignDate</th><th>การแก้ไข</th></tr></thead><tbody>";
        
        foreach ($newWithoutSales as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . ($row['AssignDate'] ? date('d/m/Y', strtotime($row['AssignDate'])) : 'ไม่มี') . "</td>";
            echo "<td><span class='badge bg-info'>เปลี่ยนเป็น ในตระกร้า</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        if ($executeMode) {
            $updateSql = "UPDATE customers SET CustomerStatus = 'ในตระกร้า', AssignDate = NULL WHERE CustomerStatus = 'ลูกค้าใหม่' AND Sales IS NULL";
            $stmt = $pdo->prepare($updateSql);
            $result = $stmt->execute();
            echo "<div class='alert alert-success'>✅ แก้ไขเรียบร้อย: " . $stmt->rowCount() . " รายการ</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ ไม่พบปัญหา</div>";
    }
    
    echo "</div>";
    
    // Fix 3: ลูกค้าใหม่เลยเวลา 30 วัน
    echo "<div class='fix-section " . ($executeMode ? 'executed' : 'preview') . "'>";
    echo "<h2>🔧 Fix 3: ลูกค้าใหม่เลยเวลา 30 วัน</h2>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, Sales, AssignDate, CreatedDate,
        DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) as days_overdue
        FROM customers 
        WHERE CustomerStatus = 'ลูกค้าใหม่' 
        AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30
        ORDER BY days_overdue DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdueNew = $stmt->fetchAll();
    
    if ($overdueNew) {
        echo "<p><strong>พบปัญหา:</strong> " . count($overdueNew) . " รายการ</p>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>Sales</th><th>วันที่เลย</th><th>การแก้ไข</th></tr></thead><tbody>";
        
        foreach ($overdueNew as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Sales']) . "</td>";
            echo "<td><strong class='text-danger'>" . $row['days_overdue'] . " วัน</strong></td>";
            echo "<td><span class='badge bg-warning'>ส่งกลับ ในตระกร้า</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        if ($executeMode) {
            $updateSql = "UPDATE customers 
                SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL 
                WHERE CustomerStatus = 'ลูกค้าใหม่' 
                AND DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate)) > 30";
            $stmt = $pdo->prepare($updateSql);
            $result = $stmt->execute();
            echo "<div class='alert alert-success'>✅ ส่งกลับตระกร้า: " . $stmt->rowCount() . " รายการ</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ ไม่พบลูกค้าใหม่เลยเวลา</div>";
    }
    
    echo "</div>";
    
    // Fix 4: ลูกค้าติดตามเลยเวลา 14 วัน
    echo "<div class='fix-section " . ($executeMode ? 'executed' : 'preview') . "'>";
    echo "<h2>🔧 Fix 4: ลูกค้าติดตามเลยเวลา 14 วัน</h2>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, Sales, LastContactDate,
        DATEDIFF(CURDATE(), LastContactDate) as days_no_contact
        FROM customers 
        WHERE CustomerStatus = 'ลูกค้าติดตาม' 
        AND LastContactDate IS NOT NULL 
        AND DATEDIFF(CURDATE(), LastContactDate) > 14
        ORDER BY days_no_contact DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $overdueFollow = $stmt->fetchAll();
    
    if ($overdueFollow) {
        echo "<p><strong>พบปัญหา:</strong> " . count($overdueFollow) . " รายการ</p>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>Sales</th><th>วันไม่ติดต่อ</th><th>การแก้ไข</th></tr></thead><tbody>";
        
        foreach ($overdueFollow as $row) {
            $action = $row['days_no_contact'] > 30 ? 'เปลี่ยนเป็น ลูกค้าเก่า' : 'ส่งกลับ ในตระกร้า';
            $badgeClass = $row['days_no_contact'] > 30 ? 'bg-info' : 'bg-warning';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Sales']) . "</td>";
            echo "<td><strong class='text-danger'>" . $row['days_no_contact'] . " วัน</strong></td>";
            echo "<td><span class='badge $badgeClass'>$action</span></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        if ($executeMode) {
            // ลูกค้าที่ไม่ติดต่อ > 30 วัน เปลี่ยนเป็นลูกค้าเก่า
            $updateSql1 = "UPDATE customers 
                SET CustomerStatus = 'ลูกค้าเก่า' 
                WHERE CustomerStatus = 'ลูกค้าติดตาม' 
                AND LastContactDate IS NOT NULL 
                AND DATEDIFF(CURDATE(), LastContactDate) > 30";
            $stmt = $pdo->prepare($updateSql1);
            $result1 = $stmt->execute();
            
            // ลูกค้าที่ไม่ติดต่อ 14-30 วัน ส่งกลับตระกร้า
            $updateSql2 = "UPDATE customers 
                SET CustomerStatus = 'ในตระกร้า', Sales = NULL, AssignDate = NULL 
                WHERE CustomerStatus = 'ลูกค้าติดตาม' 
                AND LastContactDate IS NOT NULL 
                AND DATEDIFF(CURDATE(), LastContactDate) BETWEEN 15 AND 30";
            $stmt = $pdo->prepare($updateSql2);
            $result2 = $stmt->execute();
            
            echo "<div class='alert alert-success'>✅ เปลี่ยนเป็นลูกค้าเก่า: " . $result1 . " รายการ</div>";
            echo "<div class='alert alert-success'>✅ ส่งกลับตระกร้า: " . $result2 . " รายการ</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ ไม่พบลูกค้าติดตามเลยเวลา</div>";
    }
    
    echo "</div>";
    
    // Fix 5: Reset Grade และ Temperature ให้สมเหตุสมผล
    echo "<div class='fix-section " . ($executeMode ? 'executed' : 'preview') . "'>";
    echo "<h2>🔧 Fix 5: Reset Grade และ Temperature</h2>";
    
    echo "<h4>🌡️ Temperature Logic ใหม่:</h4>";
    echo "<ul>";
    echo "<li><strong>HOT:</strong> ลูกค้าที่ติดต่อภายใน 3 วัน</li>";
    echo "<li><strong>WARM:</strong> ลูกค้าที่ติดต่อภายใน 7 วัน</li>";
    echo "<li><strong>COLD:</strong> ลูกค้าที่ติดต่อภายใน 14 วัน</li>";
    echo "<li><strong>FROZEN:</strong> ลูกค้าที่ไม่ติดต่อเกิน 30 วัน หรือไม่มี Sales</li>";
    echo "</ul>";
    
    if ($executeMode) {
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
        $result = $stmt->execute();
        echo "<div class='alert alert-success'>✅ อัปเดต Temperature: " . $stmt->rowCount() . " รายการ</div>";
        
        echo "<h4>📊 Grade Logic ใหม่:</h4>";
        echo "<ul>";
        echo "<li><strong>A:</strong> ลูกค้าใหม่ที่มี Sales, HOT/WARM</li>";
        echo "<li><strong>B:</strong> ลูกค้าติดตาม, WARM/COLD</li>";
        echo "<li><strong>C:</strong> ลูกค้าเก่า, COLD</li>";
        echo "<li><strong>D:</strong> FROZEN หรือในตระกร้า</li>";
        echo "</ul>";
        
        $gradeUpdateSql = "UPDATE customers SET 
            CustomerGrade = CASE 
                WHEN CustomerStatus = 'ในตระกร้า' OR CustomerTemperature = 'FROZEN' THEN 'D'
                WHEN CustomerStatus = 'ลูกค้าใหม่' AND CustomerTemperature IN ('HOT', 'WARM') THEN 'A'
                WHEN CustomerStatus = 'ลูกค้าติดตาม' AND CustomerTemperature IN ('WARM', 'COLD') THEN 'B'
                WHEN CustomerStatus = 'ลูกค้าเก่า' THEN 'C'
                ELSE 'D'
            END";
        
        $stmt = $pdo->prepare($gradeUpdateSql);
        $result = $stmt->execute();
        echo "<div class='alert alert-success'>✅ อัปเดต Grade: " . $stmt->rowCount() . " รายการ</div>";
    } else {
        echo "<div class='alert alert-info'>📋 จะอัปเดต Temperature และ Grade ตาม Logic ใหม่</div>";
    }
    
    echo "</div>";
    
    // Summary
    echo "<div class='fix-section executed'>";
    echo "<h2>📊 สรุปการแก้ไข</h2>";
    
    if ($executeMode) {
        $pdo->commit();
        
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ แก้ไขข้อมูลเรียบร้อยแล้ว!</h4>";
        echo "<p>ข้อมูลลูกค้าได้รับการปรับปรุงให้ตรงกับ Business Logic</p>";
        echo "</div>";
        
        // Show updated stats
        $sql = "SELECT 
            CustomerStatus, 
            COUNT(*) as count,
            COUNT(CASE WHEN Sales IS NOT NULL THEN 1 END) as with_sales
            FROM customers 
            GROUP BY CustomerStatus 
            ORDER BY CustomerStatus";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $updatedStats = $stmt->fetchAll();
        
        echo "<h4>📈 สถิติหลังแก้ไข:</h4>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>สถานะ</th><th>จำนวน</th><th>มี Sales</th><th>ไม่มี Sales</th></tr></thead><tbody>";
        
        foreach ($updatedStats as $stat) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($stat['CustomerStatus']) . "</strong></td>";
            echo "<td>" . $stat['count'] . "</td>";
            echo "<td>" . $stat['with_sales'] . "</td>";
            echo "<td>" . ($stat['count'] - $stat['with_sales']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        
        echo "<div class='alert alert-warning'>";
        echo "<h5>📋 แนะนำขั้นตอนต่อไป:</h5>";
        echo "<ol>";
        echo "<li>ทดสอบระบบ Dashboard ใหม่</li>";
        echo "<li>ตรวจสอบการคำนวณ time_remaining_days</li>";
        echo "<li>แจ้ง Sales ให้รับลูกค้าจาก Pool ใหม่</li>";
        echo "<li>ติดตั้งระบบ Auto-reassign</li>";
        echo "</ol>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ โหมดแสดงตัวอย่าง</h4>";
        echo "<p>หากพร้อมแก้ไขข้อมูลจริง ให้คลิก: <a href='?execute=true' class='btn btn-danger'>🚀 Execute การแก้ไข</a></p>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    if ($executeMode) {
        $pdo->rollback();
    }
    echo "<div class='alert alert-danger'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>"; // container

echo "</body></html>";
?>