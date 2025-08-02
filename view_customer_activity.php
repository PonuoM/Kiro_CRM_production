<?php
/**
 * View Customer Activity Log
 * หน้าดู Activity Log ของลูกค้าเพื่อทวนสอบการทำงาน
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>📋 Customer Activity Log Viewer</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>";
echo "<style>
body{font-family:Arial,sans-serif;padding:15px;background:#f8f9fa;} 
.section{margin:15px 0;padding:15px;border:2px solid #ddd;border-radius:8px;background:white;}
.activity-item{border-left:4px solid #007bff;padding:10px;margin:5px 0;background:#f8f9fa;border-radius:5px;}
.activity-cart{border-left-color:#28a745;} .activity-sales{border-left-color:#ffc107;} 
.activity-temp{border-left-color:#17a2b8;} .activity-system{border-left-color:#6c757d;}
.search-box{background:white;padding:15px;border-radius:8px;margin-bottom:15px;border:1px solid #ddd;}
</style>";
echo "</head><body>";

echo "<h1>📋 Customer Activity Log Viewer</h1>";
echo "<p>ทวนสอบการเปลี่ยนแปลงลูกค้าทุกอย่าง</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // ตรวจสอบว่ามี table customer_activity_log หรือไม่
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'customer_activity_log'");
    
    if ($tableCheck->rowCount() == 0) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ ไม่พบตาราง customer_activity_log</h4>";
        echo "<p>กรุณารัน <a href='create_customer_activity_log.php'>create_customer_activity_log.php</a> ก่อน</p>";
        echo "</div>";
        exit;
    }
    
    // รับ parameters
    $customerCode = $_GET['customer'] ?? '';
    $activityType = $_GET['type'] ?? '';
    $limit = $_GET['limit'] ?? 50;
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    // Search Form
    echo "<div class='search-box'>";
    echo "<h5><i class='fas fa-search'></i> ค้นหา Activity Log</h5>";
    echo "<form method='GET' class='row g-3'>";
    
    echo "<div class='col-md-3'>";
    echo "<label class='form-label'>รหัสลูกค้า:</label>";
    echo "<input type='text' name='customer' class='form-control' value='$customerCode' placeholder='เช่น CUST001'>";
    echo "</div>";
    
    echo "<div class='col-md-3'>";
    echo "<label class='form-label'>ประเภท Activity:</label>";
    echo "<select name='type' class='form-control'>";
    echo "<option value=''>-- ทั้งหมด --</option>";
    
    $activityTypes = [
        'CART_STATUS_CHANGE' => 'เปลี่ยน Cart Status',
        'SALES_ASSIGNMENT' => 'มอบหมาย Sales',
        'SALES_REMOVAL' => 'ลบ Sales',
        'CUSTOMER_STATUS_CHANGE' => 'เปลี่ยน Customer Status',
        'TEMPERATURE_CHANGE' => 'เปลี่ยน Temperature',
        'GRADE_CHANGE' => 'เปลี่ยน Grade',
        'AUTO_RETRIEVAL' => 'ดึงคืนอัตโนมัติ',
        'SYSTEM_UPDATE' => 'System Update'
    ];
    
    foreach ($activityTypes as $value => $label) {
        $selected = ($activityType === $value) ? 'selected' : '';
        echo "<option value='$value' $selected>$label</option>";
    }
    
    echo "</select>";
    echo "</div>";
    
    echo "<div class='col-md-2'>";
    echo "<label class='form-label'>จำนวน:</label>";
    echo "<select name='limit' class='form-control'>";
    $limits = [20, 50, 100, 200, 500];
    foreach ($limits as $l) {
        $selected = ($limit == $l) ? 'selected' : '';
        echo "<option value='$l' $selected>$l รายการ</option>";
    }
    echo "</select>";
    echo "</div>";
    
    echo "<div class='col-md-2'>";
    echo "<label class='form-label'>จากวันที่:</label>";
    echo "<input type='date' name='date_from' class='form-control' value='$dateFrom'>";
    echo "</div>";
    
    echo "<div class='col-md-2'>";
    echo "<label class='form-label'>ถึงวันที่:</label>";
    echo "<input type='date' name='date_to' class='form-control' value='$dateTo'>";
    echo "</div>";
    
    echo "<div class='col-12'>";
    echo "<button type='submit' class='btn btn-primary'><i class='fas fa-search'></i> ค้นหา</button>";
    echo "<a href='?' class='btn btn-secondary ms-2'><i class='fas fa-refresh'></i> รีเซ็ต</a>";
    echo "</div>";
    
    echo "</form>";
    echo "</div>";
    
    // สร้าง SQL Query
    $sql = "SELECT * FROM customer_activity_log WHERE 1=1";
    $params = [];
    
    if ($customerCode) {
        $sql .= " AND customer_code LIKE ?";
        $params[] = "%$customerCode%";
    }
    
    if ($activityType) {
        $sql .= " AND activity_type = ?";
        $params[] = $activityType;
    }
    
    if ($dateFrom) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $dateTo;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = (int)$limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แสดงผลลัพธ์
    echo "<div class='section'>";
    echo "<h3><i class='fas fa-list'></i> Activity Log Results</h3>";
    
    if (!empty($activities)) {
        echo "<div class='alert alert-info'>";
        echo "<strong>📊 พบ Activity Log:</strong> " . count($activities) . " รายการ";
        if ($customerCode) echo " | <strong>ลูกค้า:</strong> $customerCode";
        if ($activityType) echo " | <strong>ประเภท:</strong> " . ($activityTypes[$activityType] ?? $activityType);
        echo "</div>";
        
        // สถิติสรุป
        $summary = [];
        foreach ($activities as $activity) {
            $type = $activity['activity_type'];
            $summary[$type] = ($summary[$type] ?? 0) + 1;
        }
        
        echo "<div class='row mb-3'>";
        foreach ($summary as $type => $count) {
            $label = $activityTypes[$type] ?? $type;
            echo "<div class='col-md-2'>";
            echo "<div class='text-center p-2 bg-primary text-white rounded'>";
            echo "<div class='h5 mb-0'>$count</div>";
            echo "<small>$label</small>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        
        // Timeline Activities
        $currentDate = '';
        
        foreach ($activities as $activity) {
            $date = date('d/m/Y', strtotime($activity['created_at']));
            $time = date('H:i:s', strtotime($activity['created_at']));
            
            // แสดง Date Header
            if ($currentDate !== $date) {
                if ($currentDate !== '') echo "</div>"; // ปิด date group ก่อนหน้า
                echo "<h5 class='mt-4 mb-3'><i class='fas fa-calendar'></i> $date</h5>";
                echo "<div class='date-group'>";
                $currentDate = $date;
            }
            
            // กำหนดสีและไอคอนตาม Activity Type
            $typeClass = 'activity-item';
            $icon = 'fas fa-info-circle';
            
            switch ($activity['activity_type']) {
                case 'CART_STATUS_CHANGE':
                    $typeClass .= ' activity-cart';
                    $icon = 'fas fa-shopping-cart';
                    break;
                case 'SALES_ASSIGNMENT':
                case 'SALES_REMOVAL':
                    $typeClass .= ' activity-sales';
                    $icon = 'fas fa-user-tie';
                    break;
                case 'TEMPERATURE_CHANGE':
                    $typeClass .= ' activity-temp';
                    $icon = 'fas fa-thermometer-half';
                    break;
                case 'SYSTEM_UPDATE':
                    $typeClass .= ' activity-system';
                    $icon = 'fas fa-cog';
                    break;
            }
            
            echo "<div class='$typeClass'>";
            echo "<div class='row align-items-center'>";
            
            // Time & Icon
            echo "<div class='col-md-1 text-center'>";
            echo "<div class='text-muted'><small>$time</small></div>";
            echo "<div class='text-primary'><i class='$icon'></i></div>";
            echo "</div>";
            
            // Customer Info
            echo "<div class='col-md-2'>";
            echo "<strong>{$activity['customer_code']}</strong><br>";
            echo "<small>" . substr($activity['customer_name'], 0, 15) . "...</small>";
            echo "</div>";
            
            // Activity Details
            echo "<div class='col-md-3'>";
            $typeLabel = $activityTypes[$activity['activity_type']] ?? $activity['activity_type'];
            echo "<span class='badge bg-primary'>$typeLabel</span>";
            echo "<div class='mt-1'><strong>{$activity['field_changed']}</strong></div>";
            echo "</div>";
            
            // Change Details
            echo "<div class='col-md-3'>";
            echo "<div class='d-flex align-items-center'>";
            if ($activity['old_value']) {
                echo "<span class='badge bg-secondary me-2'>{$activity['old_value']}</span>";
            }
            echo "<i class='fas fa-arrow-right mx-1'></i>";
            if ($activity['new_value']) {
                echo "<span class='badge bg-success'>{$activity['new_value']}</span>";
            } else {
                echo "<span class='badge bg-danger'>ลบ</span>";
            }
            echo "</div>";
            echo "</div>";
            
            // Reason & Changed By
            echo "<div class='col-md-3'>";
            echo "<div class='text-muted'><small>{$activity['reason']}</small></div>";
            echo "<div class='mt-1'>";
            echo "<i class='fas fa-user'></i> <small>{$activity['changed_by']}</small>";
            if ($activity['automation_rule']) {
                echo "<br><i class='fas fa-robot'></i> <small>{$activity['automation_rule']}</small>";
            }
            echo "</div>";
            echo "</div>";
            
            echo "</div>";
            echo "</div>";
        }
        
        if ($currentDate !== '') echo "</div>"; // ปิด date group ล่าสุด
        
        // Navigation
        echo "<div class='mt-4 text-center'>";
        if (count($activities) == $limit) {
            echo "<div class='alert alert-info'>";
            echo "แสดง $limit รายการแรก - อาจมีข้อมูลเพิ่มเติม";
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ ไม่พบ Activity Log</h4>";
        echo "<p>ไม่มีข้อมูลตามเงื่อนไขที่ค้นหา</p>";
        echo "<ul>";
        echo "<li>ลองเปลี่ยนเงื่อนไขการค้นหา</li>";
        echo "<li>ตรวจสอบว่า Auto Rules ทำงานแล้วหรือไม่</li>";
        echo "<li>ลองทดสอบด้วย <a href='run_auto_rules_web.php'>run_auto_rules_web.php</a></li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Quick Stats
    echo "<div class='section'>";
    echo "<h3><i class='fas fa-chart-bar'></i> Quick Statistics</h3>";
    
    // Total Activities Today
    $todayCount = $pdo->query("SELECT COUNT(*) FROM customer_activity_log WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // Total Activities This Week
    $weekCount = $pdo->query("SELECT COUNT(*) FROM customer_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // Most Active Customers
    $activeCustomers = $pdo->query("
        SELECT customer_code, customer_name, COUNT(*) as activity_count
        FROM customer_activity_log 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND customer_code != 'SYSTEM_SUMMARY'
        GROUP BY customer_code, customer_name
        ORDER BY activity_count DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='row'>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card text-center'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>วันนี้</h5>";
    echo "<div class='display-6 text-primary'>$todayCount</div>";
    echo "<p class='card-text'>Activity Logs</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card text-center'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>7 วันที่แล้ว</h5>";
    echo "<div class='display-6 text-success'>$weekCount</div>";
    echo "<p class='card-text'>Activity Logs</p>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='col-md-4'>";
    echo "<div class='card'>";
    echo "<div class='card-body'>";
    echo "<h5 class='card-title'>ลูกค้าที่มี Activity มากสุด</h5>";
    if (!empty($activeCustomers)) {
        echo "<ul class='list-unstyled'>";
        foreach ($activeCustomers as $customer) {
            echo "<li><strong>{$customer['customer_code']}</strong> ({$customer['activity_count']} ครั้ง)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='text-muted'>ไม่มีข้อมูล</p>";
    }
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<div class='text-center mt-4'>";
echo "<a href='create_customer_activity_log.php' class='btn btn-secondary'>🔧 จัดการ Activity Log Table</a>";
echo "<a href='system_logs_check.php' class='btn btn-info ms-2'>📊 ดู System Logs</a>";
echo "</div>";

echo "</body></html>";
?>