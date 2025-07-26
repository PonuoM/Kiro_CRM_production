<?php
/**
 * Customer Model
 * Handles customer-related database operations
 */

require_once __DIR__ . '/BaseModel.php';

class Customer extends BaseModel {
    protected $table = 'customers';
    protected $primaryKey = 'CustomerCode';
    
    /**
     * Find customer by CustomerCode
     * @param string $customerCode
     * @return array|false
     */
    public function findByCode($customerCode) {
        return $this->findOne(['CustomerCode' => $customerCode]);
    }
    
    /**
     * Find customer by phone number
     * @param string $phone
     * @return array|false
     */
    public function findByPhone($phone) {
        return $this->findOne(['CustomerTel' => $phone]);
    }
    
    /**
     * Create new customer
     * @param array $customerData
     * @return string|false CustomerCode or false on failure
     */
    public function createCustomer($customerData) {
        // Generate unique CustomerCode if not provided
        if (!isset($customerData['CustomerCode']) || empty($customerData['CustomerCode'])) {
            $customerData['CustomerCode'] = $this->generateUniqueCustomerCode();
        }
        
        // Set default values
        $customerData['CreatedDate'] = date('Y-m-d H:i:s');
        $customerData['CreatedBy'] = getCurrentUsername() ?? 'system';
        
        // Set default status if not provided
        if (!isset($customerData['CustomerStatus'])) {
            $customerData['CustomerStatus'] = 'ลูกค้าใหม่';
        }
        
        if (!isset($customerData['CartStatus'])) {
            $customerData['CartStatus'] = 'กำลังดูแล';
        }
        
        // Set current user as Sales if not provided and user is Sale role
        if (!isset($customerData['Sales']) && getCurrentUserRole() === 'Sale') {
            $customerData['Sales'] = getCurrentUsername();
            $customerData['AssignDate'] = date('Y-m-d H:i:s');
        }
        
        if ($this->insert($customerData)) {
            return $customerData['CustomerCode'];
        }
        
        return false;
    }
    
    /**
     * Update customer data
     * @param string $customerCode
     * @param array $customerData
     * @return bool
     */
    public function updateCustomer($customerCode, $customerData) {
        // Set modified data
        $customerData['ModifiedDate'] = date('Y-m-d H:i:s');
        $customerData['ModifiedBy'] = getCurrentUsername() ?? 'system';
        
        return $this->updateWhere($customerData, ['CustomerCode' => $customerCode]);
    }
    
    /**
     * Get customers with filtering and pagination
     * @param array $filters
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getCustomers($filters = [], $orderBy = 'CreatedDate DESC', $limit = 0, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $whereConditions = [];
        
        // Apply filters
        if (!empty($filters['CustomerStatus'])) {
            $whereConditions[] = "CustomerStatus = ?";
            $params[] = $filters['CustomerStatus'];
        }
        
        if (!empty($filters['CartStatus'])) {
            $whereConditions[] = "CartStatus = ?";
            $params[] = $filters['CartStatus'];
        }
        
        if (!empty($filters['Sales'])) {
            $whereConditions[] = "Sales = ?";
            $params[] = $filters['Sales'];
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $whereConditions[] = "(CustomerName LIKE ? OR CustomerTel LIKE ? OR CustomerAddress LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['province'])) {
            $whereConditions[] = "CustomerProvince = ?";
            $params[] = $filters['province'];
        }
        
        // Date range filters
        if (!empty($filters['created_from'])) {
            $whereConditions[] = "CreatedDate >= ?";
            $params[] = $filters['created_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['created_to'])) {
            $whereConditions[] = "CreatedDate <= ?";
            $params[] = $filters['created_to'] . ' 23:59:59';
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
     * Count customers with filters
     * @param array $filters
     * @return int
     */
    public function countCustomers($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        $whereConditions = [];
        
        // Apply same filters as getCustomers
        if (!empty($filters['CustomerStatus'])) {
            $whereConditions[] = "CustomerStatus = ?";
            $params[] = $filters['CustomerStatus'];
        }
        
        if (!empty($filters['CartStatus'])) {
            $whereConditions[] = "CartStatus = ?";
            $params[] = $filters['CartStatus'];
        }
        
        if (!empty($filters['Sales'])) {
            $whereConditions[] = "Sales = ?";
            $params[] = $filters['Sales'];
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $whereConditions[] = "(CustomerName LIKE ? OR CustomerTel LIKE ? OR CustomerAddress LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['province'])) {
            $whereConditions[] = "CustomerProvince = ?";
            $params[] = $filters['province'];
        }
        
        if (!empty($filters['created_from'])) {
            $whereConditions[] = "CreatedDate >= ?";
            $params[] = $filters['created_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['created_to'])) {
            $whereConditions[] = "CreatedDate <= ?";
            $params[] = $filters['created_to'] . ' 23:59:59';
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Generate unique customer code
     * @return string
     */
    private function generateUniqueCustomerCode() {
        do {
            $code = generateCustomerCode();
        } while ($this->exists(['CustomerCode' => $code]));
        
        return $code;
    }
    
    /**
     * Check if phone number exists
     * @param string $phone
     * @param string $excludeCustomerCode
     * @return bool
     */
    public function phoneExists($phone, $excludeCustomerCode = null) {
        if (empty($phone)) return false;
        
        if ($excludeCustomerCode) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE CustomerTel = ? AND CustomerCode != ?";
            $result = $this->queryOne($sql, [$phone, $excludeCustomerCode]);
            return $result && $result['count'] > 0;
        }
        
        return $this->exists(['CustomerTel' => $phone]);
    }
    
    /**
     * Assign customer to sales person
     * @param string $customerCode
     * @param string $salesUsername
     * @return bool
     */
    public function assignToSales($customerCode, $salesUsername) {
        return $this->updateCustomer($customerCode, [
            'Sales' => $salesUsername,
            'AssignDate' => date('Y-m-d H:i:s'),
            'CartStatus' => 'กำลังดูแล'
        ]);
    }
    
    /**
     * Update customer status after order
     * @param string $customerCode
     * @return bool
     */
    public function updateStatusAfterOrder($customerCode) {
        return $this->updateCustomer($customerCode, [
            'CustomerStatus' => 'ลูกค้าเก่า',
            'OrderDate' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get customers for auto rules processing
     * @return array
     */
    public function getCustomersForAutoRules() {
        // Get customers that need status updates based on time rules
        $sql = "
            SELECT CustomerCode, CustomerStatus, CartStatus, ModifiedDate, OrderDate, AssignDate
            FROM {$this->table}
            WHERE 
                (CustomerStatus = 'ลูกค้าใหม่' AND ModifiedDate < DATE_SUB(NOW(), INTERVAL 30 DAY))
                OR (CustomerStatus = 'ลูกค้าติดตาม' AND (OrderDate IS NULL OR OrderDate < DATE_SUB(NOW(), INTERVAL 3 MONTH)))
                OR (CustomerStatus = 'ลูกค้าเก่า' AND OrderDate < DATE_SUB(NOW(), INTERVAL 3 MONTH))
        ";
        
        return $this->query($sql);
    }
    
    /**
     * Update cart status for auto rules
     * @param string $customerCode
     * @param string $newCartStatus
     * @return bool
     */
    public function updateCartStatus($customerCode, $newCartStatus) {
        return $this->updateCustomer($customerCode, [
            'CartStatus' => $newCartStatus
        ]);
    }
    
    /**
     * Validate customer data
     * @param array $customerData
     * @param bool $isUpdate
     * @return array Array of validation errors
     */
    public function validateCustomerData($customerData, $isUpdate = false) {
        $errors = [];
        
        // Required fields for new customer
        if (!$isUpdate) {
            $required = ['CustomerName', 'CustomerTel'];
            $missing = validateRequiredFields($customerData, $required);
            if (!empty($missing)) {
                $errors[] = 'ข้อมูลที่จำเป็น: ' . implode(', ', $missing);
            }
        }
        
        // Phone number validation
        if (isset($customerData['CustomerTel']) && !empty($customerData['CustomerTel'])) {
            if (!validatePhoneNumber($customerData['CustomerTel'])) {
                $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
            }
            
            $excludeCode = $isUpdate && isset($customerData['CustomerCode']) ? $customerData['CustomerCode'] : null;
            if ($this->phoneExists($customerData['CustomerTel'], $excludeCode)) {
                $errors[] = 'เบอร์โทรศัพท์นี้มีอยู่ในระบบแล้ว';
            }
        }
        
        // Customer name validation
        if (isset($customerData['CustomerName']) && !empty($customerData['CustomerName'])) {
            if (strlen($customerData['CustomerName']) < 2) {
                $errors[] = 'ชื่อลูกค้าต้องมีอย่างน้อย 2 ตัวอักษร';
            }
        }
        
        // Status validation
        if (isset($customerData['CustomerStatus'])) {
            $validStatuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];
            if (!in_array($customerData['CustomerStatus'], $validStatuses)) {
                $errors[] = 'สถานะลูกค้าไม่ถูกต้อง';
            }
        }
        
        if (isset($customerData['CartStatus'])) {
            $validCartStatuses = ['ตะกร้าแจก', 'ตะกร้ารอ', 'กำลังดูแล'];
            if (!in_array($customerData['CartStatus'], $validCartStatuses)) {
                $errors[] = 'สถานะตะกร้าไม่ถูกต้อง';
            }
        }
        
        return $errors;
    }
}
?>