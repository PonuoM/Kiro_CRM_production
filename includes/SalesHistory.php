<?php
/**
 * Sales History Model
 * Handles sales history and assignment tracking
 */

require_once __DIR__ . '/BaseModel.php';

class SalesHistory extends BaseModel {
    protected $table = 'sales_histories';
    protected $primaryKey = 'id';
    
    /**
     * Get sales history for a customer
     * @param string $customerCode
     * @param string $orderBy
     * @return array
     */
    public function getCustomerSalesHistory($customerCode, $orderBy = 'StartDate DESC') {
        $sql = "SELECT sh.*, u.FirstName, u.LastName 
                FROM {$this->table} sh
                LEFT JOIN users u ON sh.SaleName = u.Username
                WHERE sh.CustomerCode = ?";
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        return $this->query($sql, [$customerCode]);
    }
    
    /**
     * Get current active sales assignment for customer
     * @param string $customerCode
     * @return array|false
     */
    public function getCurrentSalesAssignment($customerCode) {
        $sql = "SELECT sh.*, u.FirstName, u.LastName 
                FROM {$this->table} sh
                LEFT JOIN users u ON sh.SaleName = u.Username
                WHERE sh.CustomerCode = ? AND sh.EndDate IS NULL
                ORDER BY sh.StartDate DESC
                LIMIT 1";
        
        return $this->queryOne($sql, [$customerCode]);
    }
    
    /**
     * Create new sales assignment
     * @param string $customerCode
     * @param string $salesName
     * @param string $assignBy
     * @param string $startDate
     * @return string|false
     */
    public function createSalesAssignment($customerCode, $salesName, $assignBy = null, $startDate = null) {
        // Start transaction
        $this->beginTransaction();
        
        try {
            // End any existing active assignment
            $this->endCurrentAssignment($customerCode);
            
            // Create new assignment
            $assignmentData = [
                'CustomerCode' => $customerCode,
                'SaleName' => $salesName,
                'StartDate' => $startDate ?? date('Y-m-d H:i:s'),
                'AssignBy' => $assignBy ?? getCurrentUsername() ?? 'system',
                'CreatedBy' => getCurrentUsername() ?? 'system'
            ];
            
            $assignmentId = $this->insert($assignmentData);
            
            if (!$assignmentId) {
                throw new Exception('Failed to create sales assignment');
            }
            
            // Update customer's Sales field
            $customerModel = new Customer();
            $customerUpdateResult = $customerModel->updateCustomer($customerCode, [
                'Sales' => $salesName,
                'AssignDate' => $assignmentData['StartDate']
            ]);
            
            if (!$customerUpdateResult) {
                throw new Exception('Failed to update customer sales assignment');
            }
            
            // Commit transaction
            $this->commit();
            
            return $assignmentId;
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->rollback();
            error_log("Sales assignment creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * End current sales assignment
     * @param string $customerCode
     * @param string $endDate
     * @return bool
     */
    public function endCurrentAssignment($customerCode, $endDate = null) {
        $sql = "UPDATE {$this->table} 
                SET EndDate = ? 
                WHERE CustomerCode = ? AND EndDate IS NULL";
        
        $endDateTime = $endDate ?? date('Y-m-d H:i:s');
        
        return $this->execute($sql, [$endDateTime, $customerCode]);
    }
    
    /**
     * Get sales performance summary
     * @param string $salesName
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getSalesPerformance($salesName = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    sh.SaleName,
                    u.FirstName,
                    u.LastName,
                    COUNT(DISTINCT sh.CustomerCode) as TotalCustomers,
                    COUNT(DISTINCT CASE WHEN c.CustomerStatus = 'ลูกค้าเก่า' THEN sh.CustomerCode END) as ConvertedCustomers,
                    COUNT(DISTINCT o.id) as TotalOrders,
                    COALESCE(SUM(o.Price), 0) as TotalSales,
                    COALESCE(AVG(o.Price), 0) as AverageSales
                FROM {$this->table} sh
                LEFT JOIN users u ON sh.SaleName = u.Username
                LEFT JOIN customers c ON sh.CustomerCode = c.CustomerCode
                LEFT JOIN orders o ON sh.CustomerCode = o.CustomerCode 
                    AND o.CreatedDate BETWEEN sh.StartDate AND COALESCE(sh.EndDate, NOW())
                WHERE 1=1";
        
        $params = [];
        
        if ($salesName) {
            $sql .= " AND sh.SaleName = ?";
            $params[] = $salesName;
        }
        
        if ($dateFrom) {
            $sql .= " AND sh.StartDate >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $sql .= " AND sh.StartDate <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $sql .= " GROUP BY sh.SaleName, u.FirstName, u.LastName
                  ORDER BY TotalSales DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get customer assignment history with details
     * @param array $filters
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAssignmentHistory($filters = [], $orderBy = 'sh.StartDate DESC', $limit = 0, $offset = 0) {
        $sql = "SELECT 
                    sh.*,
                    u.FirstName as SalesFirstName,
                    u.LastName as SalesLastName,
                    c.CustomerName,
                    c.CustomerTel,
                    c.CustomerStatus,
                    assignedBy.FirstName as AssignedByFirstName,
                    assignedBy.LastName as AssignedByLastName
                FROM {$this->table} sh
                LEFT JOIN users u ON sh.SaleName = u.Username
                LEFT JOIN customers c ON sh.CustomerCode = c.CustomerCode
                LEFT JOIN users assignedBy ON sh.AssignBy = assignedBy.Username";
        
        $params = [];
        $whereConditions = [];
        
        // Apply filters
        if (!empty($filters['CustomerCode'])) {
            $whereConditions[] = "sh.CustomerCode = ?";
            $params[] = $filters['CustomerCode'];
        }
        
        if (!empty($filters['SaleName'])) {
            $whereConditions[] = "sh.SaleName = ?";
            $params[] = $filters['SaleName'];
        }
        
        if (!empty($filters['AssignBy'])) {
            $whereConditions[] = "sh.AssignBy = ?";
            $params[] = $filters['AssignBy'];
        }
        
        if (!empty($filters['active_only'])) {
            $whereConditions[] = "sh.EndDate IS NULL";
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "sh.StartDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "sh.StartDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
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
     * Count assignment history records
     * @param array $filters
     * @return int
     */
    public function countAssignmentHistory($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} sh";
        $params = [];
        $whereConditions = [];
        
        // Apply same filters as getAssignmentHistory
        if (!empty($filters['CustomerCode'])) {
            $whereConditions[] = "sh.CustomerCode = ?";
            $params[] = $filters['CustomerCode'];
        }
        
        if (!empty($filters['SaleName'])) {
            $whereConditions[] = "sh.SaleName = ?";
            $params[] = $filters['SaleName'];
        }
        
        if (!empty($filters['AssignBy'])) {
            $whereConditions[] = "sh.AssignBy = ?";
            $params[] = $filters['AssignBy'];
        }
        
        if (!empty($filters['active_only'])) {
            $whereConditions[] = "sh.EndDate IS NULL";
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "sh.StartDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "sh.StartDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get sales team summary
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getSalesTeamSummary($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    u.Username,
                    u.FirstName,
                    u.LastName,
                    u.Role,
                    COUNT(DISTINCT sh.CustomerCode) as AssignedCustomers,
                    COUNT(DISTINCT CASE WHEN c.CustomerStatus = 'ลูกค้าเก่า' THEN sh.CustomerCode END) as ConvertedCustomers,
                    COUNT(DISTINCT o.id) as TotalOrders,
                    COALESCE(SUM(o.Price), 0) as TotalSales,
                    CASE 
                        WHEN COUNT(DISTINCT sh.CustomerCode) > 0 
                        THEN ROUND((COUNT(DISTINCT CASE WHEN c.CustomerStatus = 'ลูกค้าเก่า' THEN sh.CustomerCode END) * 100.0 / COUNT(DISTINCT sh.CustomerCode)), 2)
                        ELSE 0 
                    END as ConversionRate
                FROM users u
                LEFT JOIN {$this->table} sh ON u.Username = sh.SaleName";
        
        if ($dateFrom || $dateTo) {
            $sql .= " AND (1=1";
            if ($dateFrom) {
                $sql .= " AND sh.StartDate >= '" . $dateFrom . " 00:00:00'";
            }
            if ($dateTo) {
                $sql .= " AND sh.StartDate <= '" . $dateTo . " 23:59:59'";
            }
            $sql .= ")";
        }
        
        $sql .= " LEFT JOIN customers c ON sh.CustomerCode = c.CustomerCode
                  LEFT JOIN orders o ON sh.CustomerCode = o.CustomerCode 
                      AND o.CreatedDate BETWEEN sh.StartDate AND COALESCE(sh.EndDate, NOW())
                  WHERE u.Role IN ('Sale', 'Supervisor')
                  GROUP BY u.Username, u.FirstName, u.LastName, u.Role
                  ORDER BY TotalSales DESC";
        
        return $this->query($sql);
    }
    
    /**
     * Validate sales assignment data
     * @param array $assignmentData
     * @return array Array of validation errors
     */
    public function validateAssignmentData($assignmentData) {
        $errors = [];
        
        // Required fields
        $required = ['CustomerCode', 'SaleName'];
        $missing = validateRequiredFields($assignmentData, $required);
        if (!empty($missing)) {
            $errors[] = 'ข้อมูลที่จำเป็น: ' . implode(', ', $missing);
        }
        
        // Check if customer exists
        if (!empty($assignmentData['CustomerCode'])) {
            $customerModel = new Customer();
            if (!$customerModel->findByCode($assignmentData['CustomerCode'])) {
                $errors[] = 'ไม่พบข้อมูลลูกค้าที่ระบุ';
            }
        }
        
        // Check if sales user exists and has correct role
        if (!empty($assignmentData['SaleName'])) {
            $sql = "SELECT * FROM users WHERE Username = ? AND Role IN ('Sale', 'Supervisor') AND Status = 1";
            $salesUser = $this->queryOne($sql, [$assignmentData['SaleName']]);
            if (!$salesUser) {
                $errors[] = 'ไม่พบผู้ใช้งานที่ระบุหรือไม่มีสิทธิ์เป็นพนักงานขาย';
            }
        }
        
        // Validate StartDate if provided
        if (!empty($assignmentData['StartDate'])) {
            if (!strtotime($assignmentData['StartDate'])) {
                $errors[] = 'รูปแบบวันที่เริ่มต้นไม่ถูกต้อง';
            }
        }
        
        // Validate EndDate if provided
        if (!empty($assignmentData['EndDate'])) {
            if (!strtotime($assignmentData['EndDate'])) {
                $errors[] = 'รูปแบบวันที่สิ้นสุดไม่ถูกต้อง';
            }
            
            // Check if EndDate is after StartDate
            if (!empty($assignmentData['StartDate']) && 
                strtotime($assignmentData['EndDate']) <= strtotime($assignmentData['StartDate'])) {
                $errors[] = 'วันที่สิ้นสุดต้องมาหลังวันที่เริ่มต้น';
            }
        }
        
        return $errors;
    }
    
    /**
     * Transfer customer to new sales person
     * @param string $customerCode
     * @param string $newSalesName
     * @param string $transferBy
     * @param string $transferDate
     * @return bool
     */
    public function transferCustomer($customerCode, $newSalesName, $transferBy = null, $transferDate = null) {
        // Validate the transfer
        $validationErrors = $this->validateAssignmentData([
            'CustomerCode' => $customerCode,
            'SaleName' => $newSalesName
        ]);
        
        if (!empty($validationErrors)) {
            return false;
        }
        
        return $this->createSalesAssignment($customerCode, $newSalesName, $transferBy, $transferDate);
    }
}

// Include Customer model if not already included
if (!class_exists('Customer')) {
    require_once __DIR__ . '/Customer.php';
}
?>