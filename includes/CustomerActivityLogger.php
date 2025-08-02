<?php
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
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
        }
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง CartStatus
     */
    public function logCartStatusChange($customerCode, $customerName, $oldStatus, $newStatus, $changedBy, $reason = null, $automationRule = null) {
        return $this->log([
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'activity_type' => 'CART_STATUS_CHANGE',
            'field_changed' => 'CartStatus',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'changed_from' => $oldStatus,
            'changed_to' => $newStatus,
            'reason' => $reason ?? "Cart status changed from $oldStatus to $newStatus",
            'changed_by' => $changedBy,
            'automation_rule' => $automationRule
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Sales Assignment
     */
    public function logSalesAssignment($customerCode, $customerName, $oldSales, $newSales, $changedBy, $reason = null) {
        $activityType = $newSales ? 'SALES_ASSIGNMENT' : 'SALES_REMOVAL';
        
        return $this->log([
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'activity_type' => $activityType,
            'field_changed' => 'Sales',
            'old_value' => $oldSales,
            'new_value' => $newSales,
            'changed_from' => $oldSales ? "Sales: $oldSales" : "No Sales",
            'changed_to' => $newSales ? "Sales: $newSales" : "No Sales",
            'reason' => $reason ?? ($newSales ? "Assigned to $newSales" : "Removed from assignment"),
            'changed_by' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Customer Status
     */
    public function logCustomerStatusChange($customerCode, $customerName, $oldStatus, $newStatus, $changedBy, $reason = null) {
        return $this->log([
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'activity_type' => 'CUSTOMER_STATUS_CHANGE',
            'field_changed' => 'CustomerStatus',
            'old_value' => $oldStatus,
            'new_value' => $newStatus,
            'changed_from' => $oldStatus,
            'changed_to' => $newStatus,
            'reason' => $reason ?? "Customer status changed from $oldStatus to $newStatus",
            'changed_by' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Temperature
     */
    public function logTemperatureChange($customerCode, $customerName, $oldTemp, $newTemp, $changedBy, $reason = null) {
        return $this->log([
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'activity_type' => 'TEMPERATURE_CHANGE',
            'field_changed' => 'CustomerTemperature',
            'old_value' => $oldTemp,
            'new_value' => $newTemp,
            'changed_from' => $oldTemp ?? 'No Temperature',
            'changed_to' => $newTemp,
            'reason' => $reason ?? "Temperature changed from $oldTemp to $newTemp",
            'changed_by' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log การเปลี่ยนแปลง Grade
     */
    public function logGradeChange($customerCode, $customerName, $oldGrade, $newGrade, $changedBy, $reason = null) {
        return $this->log([
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'activity_type' => 'GRADE_CHANGE',
            'field_changed' => 'CustomerGrade',
            'old_value' => $oldGrade,
            'new_value' => $newGrade,
            'changed_from' => $oldGrade ?? 'No Grade',
            'changed_to' => $newGrade,
            'reason' => $reason ?? "Grade changed from $oldGrade to $newGrade",
            'changed_by' => $changedBy
        ]);
    }
    
    /**
     * บันทึก Log Auto Retrieval (การดึงคืนอัตโนมัติ)
     */
    public function logAutoRetrieval($customerCode, $customerName, $fromStatus, $toStatus, $automationRule, $reason) {
        return $this->log([
            'customer_code' => $customerCode,
            'customer_name' => $customerName,
            'activity_type' => 'AUTO_RETRIEVAL',
            'field_changed' => 'CartStatus',
            'old_value' => $fromStatus,
            'new_value' => $toStatus,
            'changed_from' => $fromStatus,
            'changed_to' => $toStatus,
            'reason' => $reason,
            'changed_by' => 'auto_rules_system',
            'automation_rule' => $automationRule
        ]);
    }
    
    /**
     * บันทึก Log หลัก
     */
    private function log($data) {
        try {
            // เพิ่มข้อมูล IP และ User Agent
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['SERVER_ADDR'] ?? 'CLI';
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Auto Rules System';
            
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
            if (isset($data['additional_data'])) {
                $additionalData = $data['additional_data'];
            }
            $data['additional_data'] = json_encode($additionalData);
            
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
?>