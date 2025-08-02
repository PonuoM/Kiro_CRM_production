<?php
/**
 * Create Customer Activity Log Table
 * สร้างตารางเก็บ Log การเปลี่ยนแปลงลูกค้าทุกอย่างเพื่อทวนสอบได้
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>📋 Create Customer Activity Log</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:15px;} .section{margin:15px 0;padding:15px;border:2px solid #ddd;border-radius:8px;background:white;}</style>";
echo "</head><body>";

echo "<h1>📋 Create Customer Activity Log Table</h1>";
echo "<p>สร้างตารางเก็บ Log การเปลี่ยนแปลงลูกค้าทุกอย่างเพื่อทวนสอบได้</p>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='section'>";
    echo "<h3>🗄️ สร้าง Table: customer_activity_log</h3>";
    
    // สร้างตาราง customer_activity_log (ไม่มี Foreign Key เพื่อหลีกเลี่ยงปัญหา)
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS customer_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_code VARCHAR(20) NOT NULL,
            customer_name VARCHAR(200) NOT NULL,
            activity_type ENUM(
                'CART_STATUS_CHANGE',
                'CUSTOMER_STATUS_CHANGE', 
                'SALES_ASSIGNMENT',
                'SALES_REMOVAL',
                'TEMPERATURE_CHANGE',
                'GRADE_CHANGE',
                'AUTO_RETRIEVAL',
                'MANUAL_MOVE',
                'SYSTEM_UPDATE'
            ) NOT NULL,
            field_changed VARCHAR(50) NOT NULL,
            old_value VARCHAR(200),
            new_value VARCHAR(200),
            changed_from VARCHAR(100) COMMENT 'จาก (ตะกร้าไหน, สถานะไหน)',
            changed_to VARCHAR(100) COMMENT 'ไป (ตะกร้าไหน, สถานะไหน)',
            reason VARCHAR(200) COMMENT 'เหตุผลการเปลี่ยนแปลง',
            changed_by VARCHAR(100) NOT NULL COMMENT 'ใครเป็นคนเปลี่ยน (user, auto_rules, system)',
            automation_rule VARCHAR(100) COMMENT 'กฎอัตโนมัติไหนที่ทำให้เปลี่ยน',
            ip_address VARCHAR(45),
            user_agent TEXT,
            additional_data JSON COMMENT 'ข้อมูลเพิ่มเติม',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer_code (customer_code),
            INDEX idx_activity_type (activity_type),
            INDEX idx_created_at (created_at),
            INDEX idx_changed_by (changed_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='บันทึกการเปลี่ยนแปลงลูกค้าทุกอย่างเพื่อทวนสอบ - ไม่มี Foreign Key เพื่อความยืดหยุ่น'
    ";
    
    $pdo->exec($createTableSQL);
    
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ สร้างตาราง customer_activity_log สำเร็จ!</h4>";
    echo "</div>";
    
    // แสดง Structure
    echo "<h4>📋 Structure ของ customer_activity_log:</h4>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM customer_activity_log")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-sm table-bordered'>";
    echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Comment</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td><small>" . ($col['Comment'] ?? '-') . "</small></td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "</div>";
    
    // สร้าง Helper Class สำหรับบันทึก Log
    echo "<div class='section'>";
    echo "<h3>🔧 สร้าง CustomerActivityLogger Class</h3>";
    
    $loggerClass = '<?php
/**
 * Customer Activity Logger
 * Helper class สำหรับบันทึก Log การเปลี่ยนแปลงลูกค้า
 */

class CustomerActivityLogger {
    private $pdo;
    
    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            require_once __DIR__ . \'/config/database.php\';
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
        }
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง CartStatus
     */
    public function logCartStatusChange($customerCode, $customerName, $oldStatus, $newStatus, $changedBy, $reason = null, $automationRule = null) {
        return $this->log([
            \'customer_code\' => $customerCode,
            \'customer_name\' => $customerName,
            \'activity_type\' => \'CART_STATUS_CHANGE\',
            \'field_changed\' => \'CartStatus\',
            \'old_value\' => $oldStatus,
            \'new_value\' => $newStatus,
            \'changed_from\' => $oldStatus,
            \'changed_to\' => $newStatus,
            \'reason\' => $reason ?? "Cart status changed from $oldStatus to $newStatus",
            \'changed_by\' => $changedBy,
            \'automation_rule\' => $automationRule
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Sales Assignment
     */
    public function logSalesAssignment($customerCode, $customerName, $oldSales, $newSales, $changedBy, $reason = null) {
        $activityType = $newSales ? \'SALES_ASSIGNMENT\' : \'SALES_REMOVAL\';
        
        return $this->log([
            \'customer_code\' => $customerCode,
            \'customer_name\' => $customerName,
            \'activity_type\' => $activityType,
            \'field_changed\' => \'Sales\',
            \'old_value\' => $oldSales,
            \'new_value\' => $newSales,
            \'changed_from\' => $oldSales ? "Sales: $oldSales" : "No Sales",
            \'changed_to\' => $newSales ? "Sales: $newSales" : "No Sales",
            \'reason\' => $reason ?? ($newSales ? "Assigned to $newSales" : "Removed from assignment"),
            \'changed_by\' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Customer Status
     */
    public function logCustomerStatusChange($customerCode, $customerName, $oldStatus, $newStatus, $changedBy, $reason = null) {
        return $this->log([
            \'customer_code\' => $customerCode,
            \'customer_name\' => $customerName,
            \'activity_type\' => \'CUSTOMER_STATUS_CHANGE\',
            \'field_changed\' => \'CustomerStatus\',
            \'old_value\' => $oldStatus,
            \'new_value\' => $newStatus,
            \'changed_from\' => $oldStatus,
            \'changed_to\' => $newStatus,
            \'reason\' => $reason ?? "Customer status changed from $oldStatus to $newStatus",
            \'changed_by\' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Temperature
     */
    public function logTemperatureChange($customerCode, $customerName, $oldTemp, $newTemp, $changedBy, $reason = null) {
        return $this->log([
            \'customer_code\' => $customerCode,
            \'customer_name\' => $customerName,
            \'activity_type\' => \'TEMPERATURE_CHANGE\',
            \'field_changed\' => \'CustomerTemperature\',
            \'old_value\' => $oldTemp,
            \'new_value\' => $newTemp,
            \'changed_from\' => $oldTemp ?? \'No Temperature\',
            \'changed_to\' => $newTemp,
            \'reason\' => $reason ?? "Temperature changed from $oldTemp to $newTemp",
            \'changed_by\' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Grade
     */
    public function logGradeChange($customerCode, $customerName, $oldGrade, $newGrade, $changedBy, $reason = null) {
        return $this->log([
            \'customer_code\' => $customerCode,
            \'customer_name\' => $customerName,
            \'activity_type\' => \'GRADE_CHANGE\',
            \'field_changed\' => \'CustomerGrade\',
            \'old_value\' => $oldGrade,
            \'new_value\' => $newGrade,
            \'changed_from\' => $oldGrade ?? \'No Grade\',
            \'changed_to\' => $newGrade,
            \'reason\' => $reason ?? "Grade changed from $oldGrade to $newGrade",
            \'changed_by\' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log Auto Retrieval (การดึงคืนอัตโนมัติ)
     */
    public function logAutoRetrieval($customerCode, $customerName, $fromStatus, $toStatus, $automationRule, $reason) {
        return $this->log([
            \'customer_code\' => $customerCode,
            \'customer_name\' => $customerName,
            \'activity_type\' => \'AUTO_RETRIEVAL\',
            \'field_changed\' => \'CartStatus\',
            \'old_value\' => $fromStatus,
            \'new_value\' => $toStatus,
            \'changed_from\' => $fromStatus,
            \'changed_to\' => $toStatus,
            \'reason\' => $reason,
            \'changed_by\' => \'auto_rules_system\',
            \'automation_rule\' => $automationRule
        ]);
    }
    
    /**
     * บันทึก Log หลัก
     */
    private function log($data) {
        try {
            // เพิ่มข้อมูล IP และ User Agent
            $data[\'ip_address\'] = $_SERVER[\'REMOTE_ADDR\'] ?? $_SERVER[\'SERVER_ADDR\'] ?? \'CLI\';
            $data[\'user_agent\'] = $_SERVER[\'HTTP_USER_AGENT\'] ?? \'Auto Rules System\';
            
            $sql = "
                INSERT INTO customer_activity_log (
                    customer_code, customer_name, activity_type, field_changed,
                    old_value, new_value, changed_from, changed_to, reason,
                    changed_by, automation_rule, ip_address, user_agent, additional_data
                ) VALUES (
                    :customer_code, :customer_name, :activity_type, :field_changed,
                    :old_value, :new_value, :changed_from, :changed_to, :reason,
                    :changed_by, :automation_rule, :ip_address, :user_agent, :additional_data
                )
            ";
            
            $stmt = $this->pdo->prepare($sql);
            
            // เตรียม additional_data
            $additionalData = [];
            if (isset($data[\'additional_data\'])) {
                $additionalData = $data[\'additional_data\'];
            }
            $data[\'additional_data\'] = json_encode($additionalData);
            
            return $stmt->execute($data);
            
        } catch (Exception $e) {
            error_log("CustomerActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ดึง Activity Log ของลูกค้า
     */
    public function getCustomerActivity($customerCode, $limit = 50) {
        $sql = "
            SELECT * FROM customer_activity_log 
            WHERE customer_code = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerCode, $limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * ดึง Activity Log ทั้งหมด
     */
    public function getAllActivity($limit = 100, $activityType = null) {
        $sql = "SELECT * FROM customer_activity_log";
        $params = [];
        
        if ($activityType) {
            $sql .= " WHERE activity_type = ?";
            $params[] = $activityType;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>';
    
    // บันทึก Logger Class
    file_put_contents(__DIR__ . '/includes/CustomerActivityLogger.php', $loggerClass);
    
    echo "<div class='alert alert-success'>";
    echo "✅ สร้าง CustomerActivityLogger Class ที่ <code>includes/CustomerActivityLogger.php</code>";
    echo "</div>";
    
    echo "</div>";
    
    // ทดสอบระบบ
    echo "<div class='section'>";
    echo "<h3>🧪 ทดสอบระบบ Customer Activity Log</h3>";
    
    // สร้าง instance ของ Logger
    include_once __DIR__ . '/includes/CustomerActivityLogger.php';
    $logger = new CustomerActivityLogger($pdo);
    
    // ทดสอบบันทึก Log
    $testResult1 = $logger->logCartStatusChange(
        'TEST999', 
        'ทดสอบ Activity Log', 
        'กำลังดูแล', 
        'ตะกร้ารอ', 
        'system_test', 
        'ทดสอบระบบ Activity Log',
        'test_rule'
    );
    
    $testResult2 = $logger->logSalesAssignment(
        'TEST999',
        'ทดสอบ Activity Log',
        'sales01',
        null,
        'auto_rules_test',
        'ลบ Sales เมื่อย้ายไปตะกร้ารอ'
    );
    
    if ($testResult1 && $testResult2) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ ทดสอบสำเร็จ!</h4>";
        echo "<p>บันทึก Test Activity Log 2 รายการเรียบร้อย</p>";
        echo "</div>";
        
        // แสดงผลทดสอบ
        $testLogs = $logger->getCustomerActivity('TEST999');
        
        echo "<h5>📋 ผลการทดสอบ:</h5>";
        echo "<table class='table table-sm table-bordered'>";
        echo "<thead><tr><th>เวลา</th><th>Activity</th><th>Field</th><th>จาก</th><th>ไป</th><th>เหตุผล</th><th>โดย</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($testLogs as $log) {
            echo "<tr>";
            echo "<td>" . date('d/m H:i:s', strtotime($log['created_at'])) . "</td>";
            echo "<td><span class='badge bg-primary'>{$log['activity_type']}</span></td>";
            echo "<td>{$log['field_changed']}</td>";
            echo "<td>{$log['old_value']}</td>";
            echo "<td><strong>{$log['new_value']}</strong></td>";
            echo "<td><small>{$log['reason']}</small></td>";
            echo "<td>{$log['changed_by']}</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
    } else {
        echo "<div class='alert alert-warning'>⚠️ การทดสอบมีปัญหา</div>";
    }
    
    echo "</div>";
    
    // คำแนะนำการใช้งาน
    echo "<div class='section'>";
    echo "<h3>📖 คำแนะนำการใช้งาน</h3>";
    
    echo "<div class='alert alert-info'>";
    echo "<h4>🔧 วิธีการใช้งาน CustomerActivityLogger:</h4>";
    echo "<pre style='background:#f8f9fa;padding:10px;border-radius:5px;'>";
    echo htmlspecialchars('
// 1. สร้าง Logger
require_once "includes/CustomerActivityLogger.php";
$logger = new CustomerActivityLogger();

// 2. บันทึก Cart Status Change
$logger->logCartStatusChange(
    "CUST001", "ลูกค้าทดสอบ", 
    "กำลังดูแล", "ตะกร้ารอ", 
    "auto_rules_system", 
    "เลยเวลา 90 วัน ไม่มี Orders", 
    "existing_customer_time_rule"
);

// 3. บันทึก Sales Assignment/Removal
$logger->logSalesAssignment(
    "CUST001", "ลูกค้าทดสอบ",
    "sales01", null,
    "auto_rules_system",
    "ลบ Sales เมื่อย้ายไปตะกร้ารอ"
);

// 4. ดึงข้อมูล Activity ของลูกค้า
$activities = $logger->getCustomerActivity("CUST001");

// 5. ดึงข้อมูล Activity ทั้งหมด
$allActivities = $logger->getAllActivity(100, "CART_STATUS_CHANGE");
');
    echo "</pre>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ ขั้นตอนต่อไป:</h4>";
    echo "<ol>";
    echo "<li><strong>ปรับปรุง Auto Rules:</strong> เพิ่ม CustomerActivityLogger เข้าไป</li>";
    echo "<li><strong>ปรับปรุง Frontend:</strong> เพิ่มการบันทึก Log เมื่อมีการเปลี่ยนแปลง</li>";
    echo "<li><strong>สร้าง Activity Viewer:</strong> หน้าดู Activity Log ของลูกค้า</li>";
    echo "<li><strong>ทดสอบระบบ:</strong> ตรวจสอบว่า Log บันทึกครบถ้วน</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</body></html>";
?>