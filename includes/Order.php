<?php
/**
 * Order Model
 * Handles order-related database operations
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/Customer.php';

class Order extends BaseModel {
    protected $table = 'orders';
    protected $primaryKey = 'id';
    
    /**
     * Find order by DocumentNo
     * @param string $documentNo
     * @return array|false
     */
    public function findByDocumentNo($documentNo) {
        return $this->findOne(['DocumentNo' => $documentNo]);
    }
    
    /**
     * Create new order
     * @param array $orderData
     * @return string|false DocumentNo or false on failure
     */
    public function createOrder($orderData) {
        // Start transaction
        $this->beginTransaction();
        
        try {
            // Generate unique DocumentNo if not provided
            if (!isset($orderData['DocumentNo']) || empty($orderData['DocumentNo'])) {
                $orderData['DocumentNo'] = $this->generateUniqueDocumentNo();
            }
            
            // Set default values
            $orderData['CreatedDate'] = date('Y-m-d H:i:s');
            
            // Get current username safely
            $currentUser = 'system';
            if (function_exists('getCurrentUsername')) {
                $currentUser = getCurrentUsername() ?? 'system';
            } elseif (isset($_SESSION['username'])) {
                $currentUser = $_SESSION['username'];
            }
            
            $orderData['CreatedBy'] = $currentUser;
            $orderData['OrderBy'] = $currentUser;
            
            // Set DocumentDate if not provided
            if (!isset($orderData['DocumentDate']) || empty($orderData['DocumentDate'])) {
                $orderData['DocumentDate'] = date('Y-m-d H:i:s');
            }
            
            // Store detailed products information as JSON if provided (if column exists)
            $productsForItems = null; // เก็บไว้สำหรับ createOrderItems
            if (isset($orderData['products']) && is_array($orderData['products'])) {
                $productsForItems = $orderData['products']; // เก็บไว้ใช้ภายหลัง
                
                // Check if ProductsDetail column exists
                if ($this->columnExists('ProductsDetail')) {
                    $orderData['ProductsDetail'] = json_encode($orderData['products'], JSON_UNESCAPED_UNICODE);
                }
                unset($orderData['products']); // Remove from main data to avoid database error
            }
            
            // ⚠️ IMPORTANT: Order.php should NOT override values that are already calculated
            // The API has already done Direct Mapping, so don't set defaults that would override
            
            // Only set defaults for truly missing values (not zero values)
            if (!isset($orderData['DiscountAmount'])) {
                $orderData['DiscountAmount'] = 0.00;
            }
            if (!isset($orderData['DiscountPercent'])) {
                $orderData['DiscountPercent'] = 0.00;
            }
            if (!isset($orderData['DiscountRemarks'])) {
                $orderData['DiscountRemarks'] = '';
            }
            if (!isset($orderData['SubtotalAmount'])) {
                $orderData['SubtotalAmount'] = 0.00;
            }
            if (!isset($orderData['Subtotal_amount2'])) {
                $orderData['Subtotal_amount2'] = 0.00;
            }
            
            // Log what we received to verify Direct Mapping is working
            error_log("=== ORDER.PHP RECEIVED DATA ===");
            error_log("Quantity: " . ($orderData['Quantity'] ?? 'NOT SET'));
            error_log("SubtotalAmount (old): " . ($orderData['SubtotalAmount'] ?? 'NOT SET'));
            error_log("Subtotal_amount2 (new): " . ($orderData['Subtotal_amount2'] ?? 'NOT SET'));
            error_log("DiscountAmount: " . ($orderData['DiscountAmount'] ?? 'NOT SET'));
            error_log("DiscountPercent: " . ($orderData['DiscountPercent'] ?? 'NOT SET'));
            error_log("Price: " . ($orderData['Price'] ?? 'NOT SET'));
            
            error_log("Discount fields included with values - Amount: " . $orderData['DiscountAmount'] . ", Percent: " . $orderData['DiscountPercent']);
            
            error_log("Final order data before insert: " . print_r($orderData, true));
            
            // Insert order
            error_log("About to insert order with data: " . print_r($orderData, true));
            $orderId = $this->insert($orderData);
            error_log("Insert result - Order ID: " . ($orderId ? $orderId : 'FALSE'));
            
            if (!$orderId) {
                // Get PDO error info
                $pdo = $this->db->getConnection();
                $errorInfo = $pdo->errorInfo();
                error_log("PDO Error Info: " . print_r($errorInfo, true));
                throw new Exception('Failed to create order - PDO Error: ' . implode(', ', $errorInfo));
            }
            
            // Update customer status after order using new business logic
            try {
                require_once __DIR__ . '/CustomerStatusManager.php';
                $statusManager = new CustomerStatusManager();
                $updateResult = $statusManager->updateCustomerStatusAfterOrder($orderData['CustomerCode']);
                
                if (!$updateResult) {
                    error_log("Warning: Failed to update customer status after order, but order was created successfully");
                }
            } catch (Exception $e) {
                error_log("Error updating customer status after order: " . $e->getMessage() . " - Order created successfully anyway");
            }
            
            // Create order items if we have products data
            if ($productsForItems && is_array($productsForItems)) {
                $itemsResult = $this->createOrderItems($orderData['DocumentNo'], $productsForItems);
                if (!$itemsResult) {
                    error_log("Warning: Failed to create order items, but order was created successfully");
                }
            }
            
            // Create sales history record
            $this->createSalesHistoryRecord($orderData['CustomerCode']);
            
            // Commit transaction
            $this->commit();
            
            return $orderData['DocumentNo'];
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->rollback();
            error_log("Order creation failed: " . $e->getMessage());
            error_log("Order creation error file: " . $e->getFile() . " line: " . $e->getLine());
            error_log("Order data: " . print_r($orderData, true));
            return false;
        }
    }
    
    /**
     * Get orders by customer
     * @param string $customerCode
     * @param string $orderBy
     * @param int $limit
     * @return array
     */
    public function getOrdersByCustomer($customerCode, $orderBy = 'DocumentDate DESC', $limit = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE CustomerCode = ?";
        $params = [$customerCode];
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Get orders with filtering and pagination
     * @param array $filters
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getOrders($filters = [], $orderBy = 'DocumentDate DESC', $limit = 0, $offset = 0) {
        $sql = "SELECT o.*, c.CustomerName, c.CustomerTel 
                FROM {$this->table} o 
                LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode";
        $params = [];
        $whereConditions = [];
        
        // Apply filters
        if (!empty($filters['CustomerCode'])) {
            $whereConditions[] = "o.CustomerCode = ?";
            $params[] = $filters['CustomerCode'];
        }
        
        if (!empty($filters['DocumentNo'])) {
            $whereConditions[] = "o.DocumentNo LIKE ?";
            $params[] = '%' . $filters['DocumentNo'] . '%';
        }
        
        if (!empty($filters['PaymentMethod'])) {
            $whereConditions[] = "o.PaymentMethod = ?";
            $params[] = $filters['PaymentMethod'];
        }
        
        if (!empty($filters['Products'])) {
            $whereConditions[] = "o.Products LIKE ?";
            $params[] = '%' . $filters['Products'] . '%';
        }
        
        if (!empty($filters['OrderBy'])) {
            $whereConditions[] = "o.OrderBy = ?";
            $params[] = $filters['OrderBy'];
        }
        
        // Date range filters
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "o.DocumentDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "o.DocumentDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Price range filters
        if (!empty($filters['price_min'])) {
            $whereConditions[] = "o.Price >= ?";
            $params[] = $filters['price_min'];
        }
        
        if (!empty($filters['price_max'])) {
            $whereConditions[] = "o.Price <= ?";
            $params[] = $filters['price_max'];
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
     * Count orders with filters
     * @param array $filters
     * @return int
     */
    public function countOrders($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} o";
        $params = [];
        $whereConditions = [];
        
        // Apply same filters as getOrders
        if (!empty($filters['CustomerCode'])) {
            $whereConditions[] = "o.CustomerCode = ?";
            $params[] = $filters['CustomerCode'];
        }
        
        if (!empty($filters['DocumentNo'])) {
            $whereConditions[] = "o.DocumentNo LIKE ?";
            $params[] = '%' . $filters['DocumentNo'] . '%';
        }
        
        if (!empty($filters['PaymentMethod'])) {
            $whereConditions[] = "o.PaymentMethod = ?";
            $params[] = $filters['PaymentMethod'];
        }
        
        if (!empty($filters['Products'])) {
            $whereConditions[] = "o.Products LIKE ?";
            $params[] = '%' . $filters['Products'] . '%';
        }
        
        if (!empty($filters['OrderBy'])) {
            $whereConditions[] = "o.OrderBy = ?";
            $params[] = $filters['OrderBy'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "o.DocumentDate >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "o.DocumentDate <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($filters['price_min'])) {
            $whereConditions[] = "o.Price >= ?";
            $params[] = $filters['price_min'];
        }
        
        if (!empty($filters['price_max'])) {
            $whereConditions[] = "o.Price <= ?";
            $params[] = $filters['price_max'];
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Generate unique document number
     * @return string
     */
    private function generateUniqueDocumentNo() {
        do {
            $documentNo = generateDocumentNo();
        } while ($this->exists(['DocumentNo' => $documentNo]));
        
        return $documentNo;
    }
    
    /**
     * Check if DocumentNo exists
     * @param string $documentNo
     * @param int $excludeId
     * @return bool
     */
    public function documentNoExists($documentNo, $excludeId = null) {
        if (empty($documentNo)) return false;
        
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DocumentNo = ? AND id != ?";
            $result = $this->queryOne($sql, [$documentNo, $excludeId]);
            return $result && $result['count'] > 0;
        }
        
        return $this->exists(['DocumentNo' => $documentNo]);
    }
    
    /**
     * Create sales history record when order is created
     * @param string $customerCode
     * @return bool
     */
    private function createSalesHistoryRecord($customerCode) {
        // Get current customer data to find assigned sales person
        $customerModel = new Customer();
        $customer = $customerModel->findByCode($customerCode);
        
        if (!$customer || empty($customer['Sales'])) {
            return true; // No sales person assigned, skip history record
        }
        
        // Check if there's already an active sales history record
        $sql = "SELECT * FROM sales_histories WHERE CustomerCode = ? AND EndDate IS NULL ORDER BY StartDate DESC LIMIT 1";
        $existingHistory = $this->queryOne($sql, [$customerCode]);
        
        if (!$existingHistory) {
            // Create new sales history record
            $historyData = [
                'CustomerCode' => $customerCode,
                'SaleName' => $customer['Sales'],
                'StartDate' => $customer['AssignDate'] ?? date('Y-m-d H:i:s'),
                'AssignBy' => getCurrentUsername() ?? 'system',
                'CreatedBy' => getCurrentUsername() ?? 'system'
            ];
            
            $sql = "INSERT INTO sales_histories (CustomerCode, SaleName, StartDate, AssignBy, CreatedBy, CreatedDate) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            return $this->execute($sql, [
                $historyData['CustomerCode'],
                $historyData['SaleName'],
                $historyData['StartDate'],
                $historyData['AssignBy'],
                $historyData['CreatedBy']
            ]);
        }
        
        return true;
    }
    
    /**
     * Get sales summary by sales person
     * @param string $salesName
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getSalesSummary($salesName = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    o.OrderBy as SalesName,
                    COUNT(*) as TotalOrders,
                    SUM(o.Price) as TotalSales,
                    AVG(o.Price) as AverageSales,
                    MIN(o.Price) as MinSales,
                    MAX(o.Price) as MaxSales
                FROM {$this->table} o
                WHERE 1=1";
        
        $params = [];
        
        if ($salesName) {
            $sql .= " AND o.OrderBy = ?";
            $params[] = $salesName;
        }
        
        if ($dateFrom) {
            $sql .= " AND o.DocumentDate >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $sql .= " AND o.DocumentDate <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $sql .= " GROUP BY o.OrderBy ORDER BY TotalSales DESC";
        
        return $this->query($sql, $params);
    }
    
    /**
     * Validate order data
     * @param array $orderData
     * @param bool $isUpdate
     * @return array Array of validation errors
     */
    public function validateOrderData($orderData, $isUpdate = false) {
        $errors = [];
        
        // Required fields for new order - check for either legacy format or new products array
        if (!$isUpdate) {
            if (isset($orderData['products']) && is_array($orderData['products'])) {
                // New multiple products format
                $required = ['CustomerCode', 'products'];
                
                // Safe validation without external function
                $missing = [];
                foreach ($required as $field) {
                    if (!isset($orderData[$field]) || empty($orderData[$field])) {
                        $missing[] = $field;
                    }
                }
                
                if (!empty($missing)) {
                    $errors[] = 'ข้อมูลที่จำเป็น: ' . implode(', ', $missing);
                }
                
                // Validate products array
                if (empty($orderData['products'])) {
                    $errors[] = 'ต้องมีสินค้าอย่างน้อย 1 รายการ';
                } else {
                    foreach ($orderData['products'] as $index => $product) {
                        $productErrors = [];
                        
                        if (empty($product['name'])) {
                            $productErrors[] = 'ชื่อสินค้า';
                        }
                        if (!isset($product['quantity']) || !is_numeric($product['quantity']) || $product['quantity'] <= 0) {
                            $productErrors[] = 'จำนวน (ต้องเป็นตัวเลขมากกว่า 0)';
                        }
                        if (!isset($product['price']) || !is_numeric($product['price']) || $product['price'] < 0) {
                            $productErrors[] = 'ราคา (ต้องเป็นตัวเลขไม่ติดลบ)';
                        }
                        
                        if (!empty($productErrors)) {
                            $errors[] = "สินค้าลำดับที่ " . ($index + 1) . " ข้อมูลไม่ถูกต้อง: " . implode(', ', $productErrors);
                        }
                    }
                }
            } else {
                // Legacy single product format
                $required = ['CustomerCode', 'Products', 'Quantity', 'Price'];
                
                // Safe validation without external function (same as above)
                $missing = [];
                foreach ($required as $field) {
                    if (!isset($orderData[$field]) || empty($orderData[$field])) {
                        $missing[] = $field;
                    }
                }
                
                if (!empty($missing)) {
                    $errors[] = 'ข้อมูลที่จำเป็น: ' . implode(', ', $missing);
                }
            }
        }
        
        // CustomerCode validation
        if (isset($orderData['CustomerCode']) && !empty($orderData['CustomerCode'])) {
            $customerModel = new Customer();
            if (!$customerModel->findByCode($orderData['CustomerCode'])) {
                $errors[] = 'ไม่พบข้อมูลลูกค้าที่ระบุ';
            }
        }
        
        // DocumentNo validation (if provided)
        if (isset($orderData['DocumentNo']) && !empty($orderData['DocumentNo'])) {
            $excludeId = $isUpdate && isset($orderData['id']) ? $orderData['id'] : null;
            if ($this->documentNoExists($orderData['DocumentNo'], $excludeId)) {
                $errors[] = 'เลขที่เอกสารนี้มีอยู่ในระบบแล้ว';
            }
        }
        
        // Quantity validation
        if (isset($orderData['Quantity'])) {
            if (!is_numeric($orderData['Quantity']) || $orderData['Quantity'] <= 0) {
                $errors[] = 'จำนวนสินค้าต้องเป็นตัวเลขที่มากกว่า 0';
            }
        }
        
        // Price validation - allow 0 for free products
        if (isset($orderData['Price'])) {
            if (!is_numeric($orderData['Price']) || $orderData['Price'] < 0) {
                $errors[] = 'ราคาต้องเป็นตัวเลขไม่ติดลบ';
            }
        }
        
        // DocumentDate validation
        if (isset($orderData['DocumentDate']) && !empty($orderData['DocumentDate'])) {
            if (!strtotime($orderData['DocumentDate'])) {
                $errors[] = 'รูปแบบวันที่ไม่ถูกต้อง';
            }
        }
        
        // Products validation
        if (isset($orderData['Products']) && !empty($orderData['Products'])) {
            if (strlen($orderData['Products']) < 2) {
                $errors[] = 'ชื่อสินค้าต้องมีอย่างน้อย 2 ตัวอักษร';
            }
        }
        
        return $errors;
    }
    
    /**
     * Update order
     * @param int $orderId
     * @param array $orderData
     * @return bool
     */
    public function updateOrder($orderId, $orderData) {
        // Remove fields that shouldn't be updated
        unset($orderData['id']);
        unset($orderData['DocumentNo']); // DocumentNo should not be changed after creation
        unset($orderData['CreatedDate']);
        unset($orderData['CreatedBy']);
        
        return $this->update($orderId, $orderData);
    }
    
    /**
     * Delete order
     * @param int $orderId
     * @return bool
     */
    public function deleteOrder($orderId) {
        return $this->delete($orderId);
    }
    
    /**
     * Create order items for the order
     * @param string $documentNo
     * @param array $products
     * @return bool
     */
    public function createOrderItems($documentNo, $products) {
        try {
            error_log("=== CREATING ORDER ITEMS ===");
            error_log("DocumentNo: " . $documentNo);
            error_log("Products: " . print_r($products, true));
            
            foreach ($products as $index => $product) {
                $productCode = isset($product['code']) ? trim($product['code']) : '';
                $productName = isset($product['name']) ? trim($product['name']) : '';
                $quantity = (float)($product['quantity'] ?? 0);
                $price = (float)($product['price'] ?? 0);
                $lineTotal = $quantity * $price;
                
                // ถ้าไม่มี ProductCode ให้พยายามดึงจาก ProductName
                if (empty($productCode) && !empty($productName)) {
                    // ตัดเอา ProductCode จาก ProductName ถ้ามีรูปแบบ "CODE - NAME"
                    if (strpos($productName, ' - ') !== false) {
                        $parts = explode(' - ', $productName, 2);
                        if (count($parts) == 2) {
                            $productCode = trim($parts[0]);
                            $productName = trim($parts[1]);
                        }
                    }
                }
                
                $itemData = [
                    'DocumentNo' => $documentNo,
                    'ProductCode' => $productCode,
                    'ProductName' => $productName,
                    'UnitPrice' => $price,
                    'Quantity' => $quantity,
                    'LineTotal' => $lineTotal,
                    'ItemDiscount' => 0.00,
                    'ItemDiscountPercent' => 0.00,
                    'CreatedBy' => getCurrentUsername() ?? 'system'
                ];
                
                error_log("Creating order item " . ($index + 1) . ": " . print_r($itemData, true));
                
                // Insert order item
                $sql = "INSERT INTO order_items (DocumentNo, ProductCode, ProductName, UnitPrice, Quantity, LineTotal, ItemDiscount, ItemDiscountPercent, CreatedBy) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $result = $this->execute($sql, [
                    $itemData['DocumentNo'],
                    $itemData['ProductCode'],
                    $itemData['ProductName'],
                    $itemData['UnitPrice'],
                    $itemData['Quantity'],
                    $itemData['LineTotal'],
                    $itemData['ItemDiscount'],
                    $itemData['ItemDiscountPercent'],
                    $itemData['CreatedBy']
                ]);
                
                if (!$result) {
                    error_log("Failed to create order item: " . print_r($itemData, true));
                    return false;
                }
            }
            
            error_log("=== ORDER ITEMS CREATED SUCCESSFULLY ===");
            return true;
            
        } catch (Exception $e) {
            error_log("Error creating order items: " . $e->getMessage());
            return false;
        }
    }
}
?>