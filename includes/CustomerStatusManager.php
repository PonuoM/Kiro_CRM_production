<?php
/**
 * Customer Status Manager
 * Handles automatic customer status updates based on business logic
 */

require_once __DIR__ . '/BaseModel.php';

class CustomerStatusManager extends BaseModel {
    
    /**
     * Update customer status after call log
     * @param string $customerCode
     * @param array $callData
     * @return bool
     */
    public function updateCustomerStatusAfterCall($customerCode, $callData) {
        try {
            require_once __DIR__ . '/Customer.php';
            $customer = new Customer();
            
            // Get current customer data
            $customerData = $customer->findByCode($customerCode);
            if (!$customerData) {
                return false;
            }
            
            $currentStatus = $customerData['CustomerStatus'] ?? '';
            $callStatus = $callData['CallStatus'] ?? '';
            $talkStatus = $callData['TalkStatus'] ?? '';
            
            // Business Logic for status update
            $newStatus = $this->determineNewCustomerStatus($currentStatus, $callStatus, $talkStatus);
            
            if ($newStatus && $newStatus !== $currentStatus) {
                $updateData = [
                    'CustomerStatus' => $newStatus,
                    'ModifiedDate' => date('Y-m-d H:i:s'),
                    'ModifiedBy' => getCurrentUsername() ?? 'system'
                ];
                
                // Add additional fields based on status
                if ($callStatus === 'ติดต่อได้') {
                    $updateData['LastContactDate'] = $callData['CallDate'] ?? date('Y-m-d H:i:s');
                    $updateData['TalkStatus'] = $talkStatus;
                }
                
                return $customer->updateCustomer($customerCode, $updateData);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating customer status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Determine new customer status based on business rules
     * @param string $currentStatus
     * @param string $callStatus
     * @param string $talkStatus
     * @return string|null
     */
    private function determineNewCustomerStatus($currentStatus, $callStatus, $talkStatus) {
        // Business Logic Rules:
        
        // 1. ลูกค้าใหม่ที่ติดต่อครั้งแรก → เปลี่ยนเป็น ลูกค้าติดตาม
        if ($currentStatus === 'ลูกค้าใหม่' && $callStatus === 'ติดต่อได้') {
            return 'ลูกค้าติดตาม';
        }
        
        // 2. ลูกค้าติดตาม + สถานะอันตราย → เป็น ลูกค้าเก่า (หยุดติดตาม)
        if ($currentStatus === 'ลูกค้าติดตาม' && $this->isDangerousTalkStatus($talkStatus)) {
            return 'ลูกค้าเก่า';
        }
        
        // 3. ลูกค้าใหม่ที่ติดต่อไม่ได้ → ยังคงเป็น ลูกค้าใหม่
        // 4. ลูกค้าติดตาม + สถานะปกติ → ยังคงเป็น ลูกค้าติดตาม
        
        return null; // ไม่เปลี่ยนสถานะ
    }
    
    /**
     * Check if talk status is dangerous (should stop contacting)
     * @param string $talkStatus
     * @return bool
     */
    private function isDangerousTalkStatus($talkStatus) {
        $dangerousStatuses = [
            'ไม่สนใจแล้ว',
            'ใช้สินค้าอื่น', 
            'อย่าโทรมาอีก'
        ];
        
        return in_array($talkStatus, $dangerousStatuses);
    }
    
    /**
     * Check if talk status allows follow-up
     * @param string $talkStatus
     * @return bool
     */
    public function canFollowUp($talkStatus) {
        $followUpStatuses = [
            'ได้คุย',
            'ยังไม่สนใจ',
            'ขอคิดดูก่อน'
        ];
        
        return in_array($talkStatus, $followUpStatuses);
    }
    
    /**
     * Get talk status score for prioritization
     * @param string $talkStatus
     * @return int
     */
    public function getTalkStatusScore($talkStatus) {
        $scores = [
            'ได้คุย' => 100,
            'ขอคิดดูก่อน' => 80,
            'ยังไม่สนใจ' => 60,
            'ไม่สนใจแล้ว' => 20,
            'ใช้สินค้าอื่น' => 10,
            'อย่าโทรมาอีก' => 0
        ];
        
        return $scores[$talkStatus] ?? 50;
    }
    
    /**
     * Update customer status when order is created (customer bought something)
     * @param string $customerCode
     * @return bool
     */
    public function updateCustomerStatusAfterOrder($customerCode) {
        try {
            require_once __DIR__ . '/Customer.php';
            $customer = new Customer();
            
            $updateData = [
                'CustomerStatus' => 'ลูกค้าเก่า', // Bought = becomes old customer
                'ModifiedDate' => date('Y-m-d H:i:s'),
                'ModifiedBy' => getCurrentUsername() ?? 'system'
            ];
            
            // Skip LastPurchaseDate for now - column doesn't exist in current schema
            // TODO: Add LastPurchaseDate column to customers table if needed
            error_log("CustomerStatusManager: updateData = " . print_r($updateData, true));
            
            return $customer->updateCustomer($customerCode, $updateData);
            
        } catch (Exception $e) {
            error_log("Error updating customer status after order: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get customers that should be in daily tasks (ลูกค้าใหม่ that need to be contacted)
     * @param string $salesPerson
     * @return array
     */
    public function getCustomersForDailyTasks($salesPerson = null) {
        $sql = "SELECT c.*, 
                       COUNT(cl.id) as contact_count,
                       MAX(cl.CallDate) as last_contact_date,
                       cl2.TalkStatus as last_talk_status
                FROM customers c
                LEFT JOIN call_logs cl ON c.CustomerCode = cl.CustomerCode
                LEFT JOIN call_logs cl2 ON c.CustomerCode = cl2.CustomerCode 
                    AND cl2.CallDate = (SELECT MAX(CallDate) FROM call_logs WHERE CustomerCode = c.CustomerCode)
                WHERE c.CustomerStatus = 'ลูกค้าใหม่'";
        
        $params = [];
        
        if ($salesPerson) {
            $sql .= " AND c.Sales = ?";
            $params[] = $salesPerson;
        }
        
        $sql .= " GROUP BY c.CustomerCode 
                  ORDER BY c.CreatedDate ASC, contact_count ASC";
        
        return $this->query($sql, $params);
    }
}
?>