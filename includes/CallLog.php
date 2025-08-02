<?php
/**
 * CallLog Model
 * Handles call log related database operations
 */

require_once __DIR__ . '/BaseModel.php';

class CallLog extends BaseModel {
    protected $table = 'call_logs';
    protected $primaryKey = 'id';
    
    /**
     * Create new call log entry
     * @param array $callData
     * @return int|false Call log ID or false on failure
     */
    public function createCallLog($callData) {
        // Set default values
        $callData['CreatedDate'] = date('Y-m-d H:i:s');
        $callData['CreatedBy'] = getCurrentUsername() ?? 'system';
        
        // Validate call data and normalize
        $validationResult = $this->validateCallData($callData);
        if (!empty($validationResult['errors'])) {
            throw new InvalidArgumentException(implode(', ', $validationResult['errors']));
        }
        
        // Use normalized data
        $callData = $validationResult['data'];
        
        // Insert call log
        $callLogId = $this->insert($callData);
        
        if ($callLogId) {
            // Auto-update customer status based on business logic
            require_once __DIR__ . '/CustomerStatusManager.php';
            $statusManager = new CustomerStatusManager();
            $statusManager->updateCustomerStatusAfterCall($callData['CustomerCode'], $callData);
        }
        
        return $callLogId;
    }
    
    /**
     * Get call logs for a specific customer
     * @param string $customerCode
     * @param array $filters
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCallLogsByCustomer($customerCode, $filters = [], $orderBy = 'CallDate DESC', $limit = 0, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE CustomerCode = ?";
        $params = [$customerCode];
        
        // Apply date filters
        if (!empty($filters['date_from'])) {
            $sql .= " AND CallDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND CallDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Apply status filters
        if (!empty($filters['call_status'])) {
            $sql .= " AND CallStatus = ?";
            $params[] = $filters['call_status'];
        }
        
        if (!empty($filters['talk_status'])) {
            $sql .= " AND TalkStatus = ?";
            $params[] = $filters['talk_status'];
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get recent call logs across all customers
     * @param array $filters
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRecentCallLogs($filters = [], $orderBy = 'CallDate DESC', $limit = 50, $offset = 0) {
        $sql = "
            SELECT cl.*, c.CustomerName, c.CustomerTel 
            FROM {$this->table} cl
            LEFT JOIN customers c ON cl.CustomerCode = c.CustomerCode
            WHERE 1=1
        ";
        $params = [];
        
        // Apply filters
        if (!empty($filters['created_by'])) {
            $sql .= " AND cl.CreatedBy = ?";
            $params[] = $filters['created_by'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND cl.CallDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND cl.CallDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['call_status'])) {
            $sql .= " AND cl.CallStatus = ?";
            $params[] = $filters['call_status'];
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Count call logs for a customer
     * @param string $customerCode
     * @param array $filters
     * @return int
     */
    public function countCallLogsByCustomer($customerCode, $filters = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE CustomerCode = ?";
        $params = [$customerCode];
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND CallDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND CallDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['call_status'])) {
            $sql .= " AND CallStatus = ?";
            $params[] = $filters['call_status'];
        }
        
        if (!empty($filters['talk_status'])) {
            $sql .= " AND TalkStatus = ?";
            $params[] = $filters['talk_status'];
        }
        
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get latest call log for a customer
     * @param string $customerCode
     * @return array|false
     */
    public function getLatestCallLog($customerCode) {
        $sql = "SELECT * FROM {$this->table} WHERE CustomerCode = ? ORDER BY CallDate DESC LIMIT 1";
        return $this->queryOne($sql, [$customerCode]);
    }
    
    /**
     * Update customer's last contact information after call log
     * @param string $customerCode
     * @param array $callData
     * @return bool
     */
    public function updateCustomerLastContact($customerCode, $callData) {
        require_once __DIR__ . '/Customer.php';
        $customer = new Customer();
        
        $updateData = [
            'CallStatus' => $callData['CallStatus'],
            'ModifiedDate' => date('Y-m-d H:i:s'),
            'ModifiedBy' => getCurrentUsername() ?? 'system'
        ];
        
        // Add TalkStatus if call was successful
        if ($callData['CallStatus'] === 'ติดต่อได้' && !empty($callData['TalkStatus'])) {
            $updateData['TalkStatus'] = $callData['TalkStatus'];
        }
        
        return $customer->updateCustomer($customerCode, $updateData);
    }
    
    /**
     * Validate call log data
     * @param array $callData
     * @return array Array with 'errors' and 'data' keys
     */
    public function validateCallData($callData) {
        $errors = [];
        $normalizedData = $callData;
        
        // Required fields - CallStatus is not required, use default
        $required = ['CustomerCode', 'CallDate'];
        $missing = validateRequiredFields($callData, $required);
        if (!empty($missing)) {
            $errors[] = 'ข้อมูลที่จำเป็น: ' . implode(', ', $missing);
        }
        
        // Set default CallStatus if not provided
        if (empty($normalizedData['CallStatus'])) {
            $normalizedData['CallStatus'] = 'ติดต่อได้';
        }
        
        // Validate CustomerCode exists
        if (!empty($callData['CustomerCode'])) {
            require_once __DIR__ . '/Customer.php';
            $customer = new Customer();
            if (!$customer->findByCode($callData['CustomerCode'])) {
                $errors[] = 'ไม่พบรหัสลูกค้าในระบบ';
            }
        }
        
        // Validate CallStatus
        if (!empty($callData['CallStatus'])) {
            $validCallStatuses = ['ติดต่อได้', 'ติดต่อไม่ได้'];
            if (!in_array($callData['CallStatus'], $validCallStatuses)) {
                $errors[] = 'สถานะการโทรไม่ถูกต้อง';
            }
        }
        
        // Validate TalkStatus (set default if CallStatus is 'ติดต่อได้')
        if (!empty($normalizedData['CallStatus']) && $normalizedData['CallStatus'] === 'ติดต่อได้') {
            if (empty($normalizedData['TalkStatus'])) {
                $normalizedData['TalkStatus'] = 'คุยจบ'; // Set default
            } else {
                $validTalkStatuses = [
                    'คุยจบ',
                    'คุยไม่จบ',
                    'ได้คุย',
                    'ยังไม่สนใจ', 
                    'ขอคิดดูก่อน',
                    'ไม่สนใจแล้ว',
                    'ใช้สินค้าอื่น',
                    'อย่าโทรมาอีก'
                ];
                if (!in_array($normalizedData['TalkStatus'], $validTalkStatuses)) {
                    $errors[] = 'สถานะการคุยไม่ถูกต้อง';
                }
            }
        }
        
        // Validate CallReason (only required if CallStatus is 'ติดต่อไม่ได้')
        // Allow using either CallReason or Remarks as the reason
        if (!empty($normalizedData['CallStatus']) && $normalizedData['CallStatus'] === 'ติดต่อไม่ได้') {
            if (empty($normalizedData['CallReason']) && empty($normalizedData['Remarks'])) {
                $errors[] = 'จำเป็นต้องระบุเหตุผลเมื่อติดต่อไม่ได้ (ในช่องเหตุผลหรือหมายเหตุ)';
            }
        }
        
        // Validate TalkReason (only required if TalkStatus is 'คุยไม่จบ')
        // Allow using either TalkReason or Remarks as the reason
        if (!empty($normalizedData['TalkStatus']) && $normalizedData['TalkStatus'] === 'คุยไม่จบ') {
            if (empty($normalizedData['TalkReason']) && empty($normalizedData['Remarks'])) {
                $errors[] = 'จำเป็นต้องระบุเหตุผลเมื่อคุยไม่จบ (ในช่องเหตุผลหรือหมายเหตุ)';
            }
        }
        
        // Validate CallDate format and normalize - support multiple formats
        if (!empty($callData['CallDate'])) {
            $date = null;
            $originalDate = $callData['CallDate'];
            
            // List of supported date formats
            $dateFormats = [
                'Y-m-d H:i:s',      // Standard MySQL datetime: 2025-07-24 15:30:00
                'Y-m-d\TH:i:s',     // ISO format with T: 2025-07-24T15:30:00
                'Y-m-d\TH:i',       // HTML datetime-local: 2025-07-24T15:30
                'Y-m-d H:i',        // Without seconds: 2025-07-24 15:30
                'Y/m/d H:i:s',      // Alternative separator: 2025/07/24 15:30:00
                'Y/m/d H:i',        // Alternative separator without seconds
                'd/m/Y H:i:s',      // Thai format: 24/07/2025 15:30:00
                'd/m/Y H:i',        // Thai format without seconds
            ];
            
            // Try each format
            foreach ($dateFormats as $format) {
                $testDate = DateTime::createFromFormat($format, $originalDate);
                if ($testDate !== false && $testDate->format($format) === $originalDate) {
                    $date = $testDate;
                    break;
                }
            }
            
            // If still no valid date, try strtotime as final fallback
            if (!$date) {
                $timestamp = strtotime($originalDate);
                if ($timestamp !== false) {
                    $date = new DateTime();
                    $date->setTimestamp($timestamp);
                }
            }
            
            // Set normalized date or error
            if ($date) {
                $normalizedData['CallDate'] = $date->format('Y-m-d H:i:s');
            } else {
                $errors[] = 'รูปแบบวันที่และเวลาไม่ถูกต้อง (' . $originalDate . ')';
            }
        }
        
        // Validate CallMinutes (should be numeric if provided)
        if (!empty($callData['CallMinutes']) && !is_numeric($callData['CallMinutes'])) {
            $errors[] = 'จำนวนนาทีต้องเป็นตัวเลข';
        }
        
        return [
            'errors' => $errors,
            'data' => $normalizedData
        ];
    }
    
    /**
     * Get call statistics for a customer
     * @param string $customerCode
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getCallStatistics($customerCode, $dateFrom = null, $dateTo = null) {
        $sql = "
            SELECT 
                COUNT(*) as total_calls,
                SUM(CASE WHEN CallStatus = 'ติดต่อได้' THEN 1 ELSE 0 END) as successful_calls,
                SUM(CASE WHEN CallStatus = 'ติดต่อไม่ได้' THEN 1 ELSE 0 END) as failed_calls,
                SUM(CASE WHEN TalkStatus = 'คุยจบ' THEN 1 ELSE 0 END) as completed_talks,
                SUM(CASE WHEN TalkStatus = 'คุยไม่จบ' THEN 1 ELSE 0 END) as incomplete_talks,
                AVG(CASE WHEN CallMinutes IS NOT NULL AND CallMinutes > 0 THEN CAST(CallMinutes AS DECIMAL) ELSE NULL END) as avg_call_duration
            FROM {$this->table} 
            WHERE CustomerCode = ?
        ";
        $params = [$customerCode];
        
        if ($dateFrom) {
            $sql .= " AND CallDate >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $sql .= " AND CallDate <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $result = $this->queryOne($sql, $params);
        
        if ($result) {
            // Calculate success rate
            $result['success_rate'] = $result['total_calls'] > 0 
                ? round(($result['successful_calls'] / $result['total_calls']) * 100, 2) 
                : 0;
            
            // Format average duration
            $result['avg_call_duration'] = $result['avg_call_duration'] 
                ? round($result['avg_call_duration'], 2) 
                : 0;
        }
        
        return $result ?: [
            'total_calls' => 0,
            'successful_calls' => 0,
            'failed_calls' => 0,
            'completed_talks' => 0,
            'incomplete_talks' => 0,
            'avg_call_duration' => 0,
            'success_rate' => 0
        ];
    }
}
?>