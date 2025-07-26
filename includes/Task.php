<?php
/**
 * Task Model Class
 * Handles task management and follow-up scheduling
 */

require_once __DIR__ . '/BaseModel.php';

class Task extends BaseModel {
    protected $table = 'tasks';
    
    /**
     * Validation rules for task data
     */
    private $validationRules = [
        'CustomerCode' => ['required', 'max:50'],
        'FollowupDate' => ['required', 'datetime'],
        'Remarks' => ['max:500'],
        'Status' => ['in:รอดำเนินการ,เสร็จสิ้น']
    ];
    
    /**
     * Validate task data
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($data) {
        $errors = [];
        
        // Check required fields
        if (empty($data['CustomerCode'])) {
            $errors['CustomerCode'] = 'รหัสลูกค้าจำเป็นต้องระบุ';
        } elseif (strlen($data['CustomerCode']) > 50) {
            $errors['CustomerCode'] = 'รหัสลูกค้าต้องไม่เกิน 50 ตัวอักษร';
        }
        
        if (empty($data['FollowupDate'])) {
            $errors['FollowupDate'] = 'วันที่ติดตามจำเป็นต้องระบุ';
        } elseif (!$this->isValidDateTime($data['FollowupDate'])) {
            $errors['FollowupDate'] = 'รูปแบบวันที่ไม่ถูกต้อง';
        }
        
        // Check optional fields
        if (!empty($data['Remarks']) && strlen($data['Remarks']) > 500) {
            $errors['Remarks'] = 'หมายเหตุต้องไม่เกิน 500 ตัวอักษร';
        }
        
        if (!empty($data['Status']) && !in_array($data['Status'], ['รอดำเนินการ', 'เสร็จสิ้น'])) {
            $errors['Status'] = 'สถานะไม่ถูกต้อง';
        }
        
        // Validate customer exists
        if (!empty($data['CustomerCode']) && !$this->customerExists($data['CustomerCode'])) {
            $errors['CustomerCode'] = 'ไม่พบลูกค้าที่ระบุ';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Create new task
     * @param array $data
     * @param string $createdBy
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public function createTask($data, $createdBy) {
        // Validate data
        $validation = $this->validate($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validation['errors']
            ];
        }
        
        // Prepare data for insertion
        $taskData = [
            'CustomerCode' => $data['CustomerCode'],
            'FollowupDate' => $data['FollowupDate'],
            'Remarks' => $data['Remarks'] ?? null,
            'Status' => $data['Status'] ?? 'รอดำเนินการ',
            'CreatedBy' => $createdBy
        ];
        
        try {
            $id = $this->insert($taskData);
            if ($id) {
                return [
                    'success' => true,
                    'message' => 'สร้างงานสำเร็จ',
                    'id' => $id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการสร้างงาน'
                ];
            }
        } catch (Exception $e) {
            error_log("Task creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในระบบ'
            ];
        }
    }
    
    /**
     * Update task
     * @param int $id
     * @param array $data
     * @param string $modifiedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateTask($id, $data, $modifiedBy) {
        // Check if task exists
        $existingTask = $this->find($id);
        if (!$existingTask) {
            return [
                'success' => false,
                'message' => 'ไม่พบงานที่ระบุ'
            ];
        }
        
        // Validate data
        $validation = $this->validate($data);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'ข้อมูลไม่ถูกต้อง',
                'errors' => $validation['errors']
            ];
        }
        
        // Prepare data for update
        $updateData = [
            'CustomerCode' => $data['CustomerCode'],
            'FollowupDate' => $data['FollowupDate'],
            'Remarks' => $data['Remarks'] ?? null,
            'Status' => $data['Status'] ?? 'รอดำเนินการ',
            'ModifiedBy' => $modifiedBy
        ];
        
        try {
            $success = $this->update($id, $updateData);
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'อัปเดตงานสำเร็จ'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'เกิดข้อผิดพลาดในการอัปเดตงาน'
                ];
            }
        } catch (Exception $e) {
            error_log("Task update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในระบบ'
            ];
        }
    }
    
    /**
     * Get tasks with filtering options
     * @param array $filters
     * @return array
     */
    public function getTasks($filters = []) {
        $sql = "SELECT t.*, c.CustomerName, c.CustomerTel 
                FROM tasks t 
                LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                WHERE 1=1";
        $params = [];
        
        // Filter by customer
        if (!empty($filters['CustomerCode'])) {
            $sql .= " AND t.CustomerCode = ?";
            $params[] = $filters['CustomerCode'];
        }
        
        // Filter by status
        if (!empty($filters['Status'])) {
            $sql .= " AND t.Status = ?";
            $params[] = $filters['Status'];
        }
        
        // Filter by created by
        if (!empty($filters['CreatedBy'])) {
            $sql .= " AND t.CreatedBy = ?";
            $params[] = $filters['CreatedBy'];
        }
        
        // Filter by date range
        if (!empty($filters['DateFrom'])) {
            $sql .= " AND DATE(t.FollowupDate) >= ?";
            $params[] = $filters['DateFrom'];
        }
        
        if (!empty($filters['DateTo'])) {
            $sql .= " AND DATE(t.FollowupDate) <= ?";
            $params[] = $filters['DateTo'];
        }
        
        // Filter by specific date
        if (!empty($filters['Date'])) {
            $sql .= " AND DATE(t.FollowupDate) = ?";
            $params[] = $filters['Date'];
        }
        
        // Order by followup date
        $sql .= " ORDER BY t.FollowupDate ASC, t.CreatedDate DESC";
        
        // Add limit if specified
        if (!empty($filters['Limit'])) {
            $sql .= " LIMIT " . (int)$filters['Limit'];
        }
        
        try {
            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Get tasks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get today's tasks
     * @param string $createdBy Optional filter by creator
     * @return array
     */
    public function getTodayTasks($createdBy = null) {
        $filters = [
            'Date' => date('Y-m-d'),
            'Status' => 'รอดำเนินการ'
        ];
        
        if ($createdBy) {
            $filters['CreatedBy'] = $createdBy;
        }
        
        return $this->getTasks($filters);
    }
    
    /**
     * Get tasks by customer
     * @param string $customerCode
     * @return array
     */
    public function getTasksByCustomer($customerCode) {
        return $this->getTasks(['CustomerCode' => $customerCode]);
    }
    
    /**
     * Get overdue tasks
     * @param string $createdBy Optional filter by creator
     * @return array
     */
    public function getOverdueTasks($createdBy = null) {
        $sql = "SELECT t.*, c.CustomerName, c.CustomerTel 
                FROM tasks t 
                LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
                WHERE t.Status = 'รอดำเนินการ' 
                AND DATE(t.FollowupDate) < CURDATE()";
        $params = [];
        
        if ($createdBy) {
            $sql .= " AND t.CreatedBy = ?";
            $params[] = $createdBy;
        }
        
        $sql .= " ORDER BY t.FollowupDate ASC";
        
        try {
            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("Get overdue tasks error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark task as completed
     * @param int $id
     * @param string $modifiedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public function completeTask($id, $modifiedBy) {
        return $this->updateTask($id, ['Status' => 'เสร็จสิ้น'], $modifiedBy);
    }
    
    /**
     * Get task statistics
     * @param string $createdBy Optional filter by creator
     * @return array
     */
    public function getTaskStats($createdBy = null) {
        $conditions = [];
        if ($createdBy) {
            $conditions['CreatedBy'] = $createdBy;
        }
        
        $total = $this->count($conditions);
        
        $pendingConditions = array_merge($conditions, ['Status' => 'รอดำเนินการ']);
        $pending = $this->count($pendingConditions);
        
        $completedConditions = array_merge($conditions, ['Status' => 'เสร็จสิ้น']);
        $completed = $this->count($completedConditions);
        
        // Count today's tasks
        $todayTasks = count($this->getTodayTasks($createdBy));
        
        // Count overdue tasks
        $overdueTasks = count($this->getOverdueTasks($createdBy));
        
        return [
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
            'today' => $todayTasks,
            'overdue' => $overdueTasks
        ];
    }
    
    /**
     * Check if customer exists
     * @param string $customerCode
     * @return bool
     */
    private function customerExists($customerCode) {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE CustomerCode = ?";
        $result = $this->queryOne($sql, [$customerCode]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Validate datetime format
     * @param string $datetime
     * @return bool
     */
    private function isValidDateTime($datetime) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        if ($d && $d->format('Y-m-d H:i:s') === $datetime) {
            return true;
        }
        
        $d = DateTime::createFromFormat('Y-m-d H:i', $datetime);
        if ($d && $d->format('Y-m-d H:i') === $datetime) {
            return true;
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $datetime);
        return $d && $d->format('Y-m-d') === $datetime;
    }
}
?>