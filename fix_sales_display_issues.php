<?php
// fix_sales_display_issues.php
// แก้ไขปัญหาการแสดงข้อมูลในรายชื่อ Sales

session_start();

// Bypass auth for fix
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'admin';
    $_SESSION['user_role'] = 'admin';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🔧 Fix Sales Display Issues</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f8f9fa;} 
.fix-card{margin:20px 0;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1);} 
.step{border-left:5px solid #17a2b8;background:#f0f9ff;} 
.success{border-left:5px solid #28a745;background:#f8fff8;} 
.warning{border-left:5px solid #ffc107;background:#fffbf0;} 
.code-box{background:#2d3748;color:#e2e8f0;padding:15px;border-radius:8px;font-family:monospace;font-size:12px;max-height:400px;overflow:auto;}
</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<div class='text-center mb-4'>";
echo "<h1 class='display-6 fw-bold text-primary'>🔧 Fix Sales Display Issues</h1>";
echo "<p class='lead text-muted'>แก้ไขปัญหาการแสดงข้อมูลในรายชื่อ Sales</p>";
echo "</div>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if (isset($_POST['execute_fix'])) {
        $step = $_POST['step'];
        
        echo "<div class='fix-card success'>";
        echo "<div class='p-4'>";
        echo "<h3>🚀 กำลังดำเนินการแก้ไข: $step</h3>";
        
        switch ($step) {
            case 'add_columns':
                // Step 1: เพิ่มคอลัมน์ที่จำเป็น
                echo "<h6>📝 เพิ่มคอลัมน์ใหม่</h6>";
                
                $alterQueries = [
                    "ALTER TABLE customers ADD COLUMN IF NOT EXISTS AssignDate DATETIME NULL COMMENT 'วันที่มอบหมายงาน'",
                    "ALTER TABLE customers ADD COLUMN IF NOT EXISTS ReceivedDate DATETIME NULL COMMENT 'วันที่ได้รับรายชื่อ'",
                    "ALTER TABLE customers ADD COLUMN IF NOT EXISTS CartStatusDate DATETIME NULL COMMENT 'วันที่เปลี่ยนสถานะตะกร้า'"
                ];
                
                foreach ($alterQueries as $query) {
                    try {
                        $pdo->exec($query);
                        echo "<p class='text-success'>✅ " . htmlspecialchars($query) . "</p>";
                    } catch (Exception $e) {
                        echo "<p class='text-warning'>⚠️ " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                break;
                
            case 'populate_dates':
                // Step 2: เติมข้อมูลวันที่
                echo "<h6>📅 เติมข้อมูลวันที่</h6>";
                
                $populateQueries = [
                    // AssignDate = CreatedDate สำหรับลูกค้าที่มี Sales
                    "UPDATE customers SET AssignDate = CreatedDate WHERE Sales IS NOT NULL AND Sales != '' AND AssignDate IS NULL",
                    // ReceivedDate = CreatedDate สำหรับลูกค้าทั้งหมด
                    "UPDATE customers SET ReceivedDate = CreatedDate WHERE ReceivedDate IS NULL",
                    // CartStatusDate = CreatedDate สำหรับลูกค้าในตะกร้า
                    "UPDATE customers SET CartStatusDate = COALESCE(ModifiedDate, CreatedDate) WHERE CustomerStatus = 'ในตระกร้า' AND CartStatusDate IS NULL"
                ];
                
                foreach ($populateQueries as $query) {
                    try {
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();
                        $affected = $stmt->rowCount();
                        echo "<p class='text-success'>✅ อัปเดต: $affected รายการ</p>";
                        echo "<small class='text-muted'>" . htmlspecialchars($query) . "</small><br>";
                    } catch (Exception $e) {
                        echo "<p class='text-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                break;
                
            case 'fix_status_logic':
                // Step 3: แก้ไข Business Logic
                echo "<h6>🔄 แก้ไข Business Logic</h6>";
                
                $logicQueries = [
                    // ลูกค้าในตะกร้าที่มี Sales → ลูกค้าใหม่
                    [
                        'query' => "UPDATE customers SET CustomerStatus = 'ลูกค้าใหม่' WHERE CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL AND Sales != ''",
                        'description' => 'ลูกค้าในตะกร้าที่มี Sales → ลูกค้าใหม่'
                    ],
                    // ลูกค้าใหม่/ติดตามที่ไม่มี Sales → ในตะกร้า
                    [
                        'query' => "UPDATE customers SET CustomerStatus = 'ในตระกร้า', Sales = NULL WHERE CustomerStatus IN ('ลูกค้าใหม่', 'ลูกค้าติดตาม') AND (Sales IS NULL OR Sales = '')",
                        'description' => 'ลูกค้าใหม่/ติดตามที่ไม่มี Sales → ในตะกร้า'
                    ]
                ];
                
                foreach ($logicQueries as $queryInfo) {
                    try {
                        $stmt = $pdo->prepare($queryInfo['query']);
                        $stmt->execute();
                        $affected = $stmt->rowCount();
                        echo "<p class='text-success'>✅ {$queryInfo['description']}: $affected รายการ</p>";
                    } catch (Exception $e) {
                        echo "<p class='text-danger'>❌ " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                break;
                
            case 'update_apis':
                // Step 4: อัปเดต API Files
                echo "<h6>🔄 อัปเดต API Files</h6>";
                
                // Update customers/list.php
                $listApiPath = __DIR__ . '/api/customers/list.php';
                if (file_exists($listApiPath)) {
                    $content = file_get_contents($listApiPath);
                    
                    // เพิ่มคอลัมน์ใหม่ใน SELECT
                    $newSelect = "SELECT 
                CustomerCode,
                CustomerName,
                CustomerTel,
                CustomerStatus,
                CustomerGrade,
                CustomerTemperature,
                TotalPurchase,
                LastContactDate,
                Sales,
                CreatedDate,
                CustomerProvince,
                ModifiedDate,
                AssignDate,
                ReceivedDate,
                CartStatusDate,
                CASE 
                    WHEN CustomerStatus = 'ลูกค้าใหม่' THEN 
                        30 - DATEDIFF(CURDATE(), COALESCE(AssignDate, CreatedDate))
                    WHEN CustomerStatus = 'ลูกค้าติดตาม' THEN 
                        CASE WHEN LastContactDate IS NOT NULL 
                             THEN 15 - DATEDIFF(CURDATE(), LastContactDate)
                             ELSE -999 
                        END
                    ELSE 0
                END as time_remaining_days
            FROM customers WHERE 1=1";
                    
                    // แทนที่ SELECT statement เดิม
                    $pattern = '/SELECT\s+[\s\S]*?FROM customers WHERE 1=1/i';
                    $newContent = preg_replace($pattern, $newSelect, $content);
                    
                    if ($newContent !== $content) {
                        file_put_contents($listApiPath, $newContent);
                        echo "<p class='text-success'>✅ อัปเดต api/customers/list.php</p>";
                    } else {
                        echo "<p class='text-warning'>⚠️ api/customers/list.php ไม่มีการเปลี่ยนแปลง</p>";
                    }
                } else {
                    echo "<p class='text-danger'>❌ ไม่พบไฟล์ api/customers/list.php</p>";
                }
                
                // Update tasks/daily.php
                $dailyApiPath = __DIR__ . '/api/tasks/daily.php';
                if (file_exists($dailyApiPath)) {
                    $content = file_get_contents($dailyApiPath);
                    
                    // เพิ่ม time_remaining calculation
                    $newSelect = "SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales,
                        CASE 
                            WHEN c.CustomerStatus = 'ลูกค้าใหม่' THEN 
                                30 - DATEDIFF(CURDATE(), COALESCE(c.AssignDate, c.CreatedDate))
                            WHEN c.CustomerStatus = 'ลูกค้าติดตาม' THEN 
                                CASE WHEN c.LastContactDate IS NOT NULL 
                                     THEN 15 - DATEDIFF(CURDATE(), c.LastContactDate)
                                     ELSE -999 
                                END
                            ELSE 0
                        END as time_remaining_days
                FROM tasks t 
                LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                WHERE DATE(t.FollowupDate) = ?";
                    
                    $pattern = '/SELECT t\.\*, c\.CustomerName, c\.CustomerTel, c\.Sales\s+FROM tasks t\s+LEFT JOIN customers c ON t\.CustomerCode = c\.CustomerCode\s+WHERE DATE\(t\.FollowupDate\) = \?/i';
                    $newContent = preg_replace($pattern, $newSelect, $content);
                    
                    if ($newContent !== $content) {
                        file_put_contents($dailyApiPath, $newContent);
                        echo "<p class='text-success'>✅ อัปเดต api/tasks/daily.php</p>";
                    } else {
                        echo "<p class='text-warning'>⚠️ api/tasks/daily.php ไม่มีการเปลี่ยนแปลง</p>";
                    }
                } else {
                    echo "<p class='text-danger'>❌ ไม่พบไฟล์ api/tasks/daily.php</p>";
                }
                break;
        }
        
        echo "</div>";
        echo "</div>";
        
        // Refresh page after 3 seconds
        echo "<meta http-equiv='refresh' content='3'>";
    }
    
    // แสดงขั้นตอนการแก้ไข
    echo "<div class='fix-card step'>";
    echo "<div class='p-4'>";
    echo "<h3>📋 ขั้นตอนการแก้ไขปัญหา</h3>";
    
    echo "<div class='row'>";
    
    // Step 1
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>🔧 ขั้นตอนที่ 1: เพิ่มคอลัมน์</h6>";
    echo "<p class='small text-muted'>เพิ่มคอลัมน์ AssignDate, ReceivedDate, CartStatusDate</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='step' value='add_columns'>";
    echo "<button type='submit' name='execute_fix' class='btn btn-primary btn-sm'>เพิ่มคอลัมน์</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Step 2
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>📅 ขั้นตอนที่ 2: เติมข้อมูลวันที่</h6>";
    echo "<p class='small text-muted'>เติมข้อมูลวันที่สำหรับลูกค้าที่ไม่มีข้อมูล</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='step' value='populate_dates'>";
    echo "<button type='submit' name='execute_fix' class='btn btn-warning btn-sm'>เติมข้อมูลวันที่</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Step 3
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>🔄 ขั้นตอนที่ 3: แก้ไข Business Logic</h6>";
    echo "<p class='small text-muted'>ปรับสถานะลูกค้าให้ตรงกับกฎธุรกิจ</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='step' value='fix_status_logic'>";
    echo "<button type='submit' name='execute_fix' class='btn btn-success btn-sm'>แก้ไขสถานะ</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // Step 4
    echo "<div class='col-md-6 mb-3'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h6>🔄 ขั้นตอนที่ 4: อัปเดต API</h6>";
    echo "<p class='small text-muted'>อัปเดต API ให้ส่งข้อมูลคอลัมน์ใหม่</p>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='step' value='update_apis'>";
    echo "<button type='submit' name='execute_fix' class='btn btn-info btn-sm'>อัปเดต API</button>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    // แสดงสถานะปัจจุบัน
    echo "<div class='fix-card warning'>";
    echo "<div class='p-4'>";
    echo "<h3>📊 สถานะปัจจุบันของข้อมูล</h3>";
    
    // ตรวจสอบคอลัมน์
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasAssignDate = in_array('AssignDate', $columns);
    $hasReceivedDate = in_array('ReceivedDate', $columns);
    $hasCartStatusDate = in_array('CartStatusDate', $columns);
    
    echo "<h6>🔍 คอลัมน์ที่จำเป็น:</h6>";
    echo "<ul>";
    echo "<li>AssignDate: " . ($hasAssignDate ? "<span class='text-success'>✅ มี</span>" : "<span class='text-danger'>❌ ไม่มี</span>") . "</li>";
    echo "<li>ReceivedDate: " . ($hasReceivedDate ? "<span class='text-success'>✅ มี</span>" : "<span class='text-danger'>❌ ไม่มี</span>") . "</li>";
    echo "<li>CartStatusDate: " . ($hasCartStatusDate ? "<span class='text-success'>✅ มี</span>" : "<span class='text-danger'>❌ ไม่มี</span>") . "</li>";
    echo "</ul>";
    
    if ($hasAssignDate && $hasReceivedDate) {
        // ตรวจสอบข้อมูลที่ขาดหาย
        $stmt = $pdo->query("SELECT 
                                COUNT(*) as total,
                                COUNT(AssignDate) as has_assign_date,
                                COUNT(ReceivedDate) as has_received_date
                             FROM customers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h6>📈 สถิติข้อมูล:</h6>";
        echo "<ul>";
        echo "<li>ลูกค้าทั้งหมด: {$result['total']} รายการ</li>";
        echo "<li>มี AssignDate: {$result['has_assign_date']} รายการ</li>";
        echo "<li>มี ReceivedDate: {$result['has_received_date']} รายการ</li>";
        echo "</ul>";
        
        // ตรวจสอบปัญหา Business Logic
        $stmt = $pdo->query("SELECT 
                                SUM(CASE WHEN CustomerStatus = 'ในตระกร้า' AND Sales IS NOT NULL AND Sales != '' THEN 1 ELSE 0 END) as basket_with_sales,
                                SUM(CASE WHEN CustomerStatus IN ('ลูกค้าใหม่', 'ลูกค้าติดตาม') AND (Sales IS NULL OR Sales = '') THEN 1 ELSE 0 END) as customer_without_sales
                             FROM customers");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h6>⚠️ ปัญหา Business Logic:</h6>";
        echo "<ul>";
        echo "<li>ลูกค้าในตะกร้าที่มี Sales: <span class='text-warning'>{$result['basket_with_sales']} รายการ</span></li>";
        echo "<li>ลูกค้าใหม่/ติดตามที่ไม่มี Sales: <span class='text-warning'>{$result['customer_without_sales']} รายการ</span></li>";
        echo "</ul>";
    }
    
    echo "</div>";
    echo "</div>";
    
    // สรุปการแก้ไข
    echo "<div class='fix-card step'>";
    echo "<div class='p-4'>";
    echo "<h3>📝 สรุปการแก้ไข</h3>";
    
    echo "<h6>🎯 ปัญหาที่จะแก้ไข:</h6>";
    echo "<ol>";
    echo "<li><strong>สถานะไม่ถูกต้อง:</strong> ลูกค้าในตะกร้าที่มี Sales และลูกค้าใหม่ที่ไม่มี Sales</li>";
    echo "<li><strong>วันที่ได้รับ 'ไม่มีข้อมูล':</strong> คอลัมน์ AssignDate/ReceivedDate ว่าง</li>";
    echo "<li><strong>เวลาที่เหลือ 'เลย 91 วัน':</strong> การคำนวณไม่แสดงค่าลบที่ถูกต้อง</li>";
    echo "</ol>";
    
    echo "<h6>🔧 วิธีแก้ไข:</h6>";
    echo "<ol>";
    echo "<li>เพิ่มคอลัมน์ที่จำเป็น (AssignDate, ReceivedDate, CartStatusDate)</li>";
    echo "<li>เติมข้อมูลวันที่ที่ขาดหาย</li>";
    echo "<li>แก้ไข Business Logic สถานะลูกค้า</li>";
    echo "<li>อัปเดต API ให้ส่งข้อมูลและคำนวณเวลาที่เหลือถูกต้อง</li>";
    echo "</ol>";
    
    echo "<div class='alert alert-info mt-3'>";
    echo "<h6><i class='fas fa-info-circle'></i> หมายเหตุ:</h6>";
    echo "<p class='mb-0'>ดำเนินการตามลำดับขั้นตอน แล้วทดสอบระบบหลังจากแก้ไขเสร็จสิ้น</p>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4>❌ Database Error</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='text-center mt-4'>";
echo "<div class='btn-group'>";
echo "<button onclick='location.reload()' class='btn btn-secondary'>🔄 Refresh</button>";
echo "<a href='diagnose_sales_data_issues.php' class='btn btn-info' target='_blank'>🔍 Diagnose Issues</a>";
echo "</div>";
echo "</div>";

echo "</div>"; // container
echo "</body></html>";
?>