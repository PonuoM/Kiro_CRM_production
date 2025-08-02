<?php
/**
 * Customer Intelligence System
 * Handles Grade and Temperature calculation with correct business logic
 * Updated according to requirements.md specifications
 */

require_once __DIR__ . '/BaseModel.php';

class CustomerIntelligence {
    
    private $pdo;
    
    public function __construct($pdo = null) {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
        }
    }
    
    /**
     * Calculate Customer Grade based on total purchase amount
     * Requirements: Grade A ≥ ฿810,000, B ≥ ฿85,000, C ≥ ฿2,000, D < ฿2,000
     * 
     * @param string $customerCode
     * @return string Grade (A, B, C, D)
     */
    public function calculateCustomerGrade($customerCode) {
        try {
            // Calculate total purchase from orders.Price field (as per requirements)
            $sql = "SELECT COALESCE(SUM(Price), 0) as total_purchase 
                    FROM orders 
                    WHERE CustomerCode = ? 
                    AND Price IS NOT NULL
                    AND Price > 0";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalPurchase = (float)$result['total_purchase'];
            
            // Apply correct Grade criteria from requirements
            if ($totalPurchase >= 810000) {
                return 'A'; // VIP Customer
            } elseif ($totalPurchase >= 85000) {
                return 'B'; // Premium Customer  
            } elseif ($totalPurchase >= 2000) {
                return 'C'; // Regular Customer
            } else {
                return 'D'; // New Customer
            }
            
        } catch (Exception $e) {
            error_log("Grade calculation failed for {$customerCode}: " . $e->getMessage());
            return 'D'; // Default to lowest grade on error
        }
    }
    
    /**
     * Calculate Customer Temperature based on status and interaction history
     * Requirements: HOT for new/positive customers, WARM for normal follow-up, 
     * COLD for multiple rejections, FROZEN for high assignment count
     * Special rule: Grade A,B should not be FROZEN
     * 
     * @param string $customerCode
     * @return string Temperature (HOT, WARM, COLD, FROZEN)
     */
    public function calculateCustomerTemperature($customerCode) {
        try {
            // Get customer data
            $customerSql = "SELECT CustomerStatus, CustomerGrade as Grade, CustomerTemperature, AssignmentCount, TotalPurchase
                           FROM customers 
                           WHERE CustomerCode = ?";
            $customerStmt = $this->pdo->prepare($customerSql);
            $customerStmt->execute([$customerCode]);
            $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$customer) {
                return 'WARM'; // Default for non-existent customer
            }
            
            // Get call history for analysis
            $callHistorySql = "SELECT TalkStatus, CallDate, CallResult
                              FROM call_logs 
                              WHERE CustomerCode = ? 
                              ORDER BY CallDate DESC";
            $callStmt = $this->pdo->prepare($callHistorySql);
            $callStmt->execute([$customerCode]);
            $callHistory = $callStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Special rule: Grade A,B with high purchase should not be FROZEN
            if (in_array($customer['Grade'], ['A', 'B']) && $customer['TotalPurchase'] > 50000) {
                if ($customer['CustomerTemperature'] == 'FROZEN') {
                    return 'WARM'; // Override FROZEN to WARM for high-value customers
                }
            }
            
            // Rule 1: New customers without call history → HOT
            if ($customer['CustomerStatus'] == 'ลูกค้าใหม่' && empty($callHistory)) {
                return 'HOT';
            }
            
            // Rule 2: Positive last call result → HOT
            if (!empty($callHistory)) {
                $lastCall = $callHistory[0];
                if ($lastCall['TalkStatus'] == 'คุยจบ' && $this->isPositiveResult($lastCall['CallResult'])) {
                    return 'HOT';
                }
            }
            
            // Rule 3: Multiple rejections → COLD
            $rejectionCount = $this->countRejections($callHistory);
            if ($rejectionCount >= 2) {
                return 'COLD';
            }
            
            // Rule 4: High assignment count (but not Grade A,B) → FROZEN
            $assignmentCount = (int)$customer['AssignmentCount'];
            if ($assignmentCount >= 3 && !in_array($customer['Grade'], ['A', 'B'])) {
                return 'FROZEN';
            }
            
            // Default: Normal follow-up → WARM
            return 'WARM';
            
        } catch (Exception $e) {
            error_log("Temperature calculation failed for {$customerCode}: " . $e->getMessage());
            return 'WARM'; // Default to WARM on error
        }
    }
    
    /**
     * Update customer Grade and Temperature in database
     * 
     * @param string $customerCode
     * @param bool $updateGrade
     * @param bool $updateTemperature
     * @return array Result with updated values
     */
    public function updateCustomerIntelligence($customerCode, $updateGrade = true, $updateTemperature = true) {
        try {
            $this->pdo->beginTransaction();
            
            $updates = [];
            $params = [];
            
            if ($updateGrade) {
                $newGrade = $this->calculateCustomerGrade($customerCode);
                $updates[] = "CustomerGrade = ?";
                $params[] = $newGrade;
            }
            
            if ($updateTemperature) {
                $newTemperature = $this->calculateCustomerTemperature($customerCode);
                $updates[] = "CustomerTemperature = ?";
                $params[] = $newTemperature;
            }
            
            if (!empty($updates)) {
                // Update total purchase for accuracy
                $totalPurchase = $this->getTotalPurchase($customerCode);
                $updates[] = "TotalPurchase = ?";
                $params[] = $totalPurchase;
                $params[] = $customerCode;
                
                $sql = "UPDATE customers SET " . implode(", ", $updates) . " WHERE CustomerCode = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            $this->pdo->commit();
            
            // Return updated customer data
            return $this->getCustomerIntelligenceData($customerCode);
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            error_log("Failed to update customer intelligence for {$customerCode}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get total purchase amount for a customer
     * 
     * @param string $customerCode
     * @return float
     */
    public function getTotalPurchase($customerCode) {
        $sql = "SELECT COALESCE(SUM(Price), 0) as total 
                FROM orders 
                WHERE CustomerCode = ? 
                AND Price IS NOT NULL 
                AND Price > 0";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (float)$result['total'];
    }
    
    /**
     * Get customer intelligence data for display
     * 
     * @param string $customerCode
     * @return array
     */
    public function getCustomerIntelligenceData($customerCode) {
        $sql = "SELECT CustomerCode, CustomerName, CustomerGrade as Grade, CustomerTemperature, 
                       TotalPurchase, AssignmentCount
                FROM customers 
                WHERE CustomerCode = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            $customer['grade_description'] = $this->getGradeDescription($customer['Grade']);
            $customer['temperature_description'] = $this->getTemperatureDescription($customer['CustomerTemperature']);
            $customer['total_purchase_formatted'] = '฿' . number_format($customer['TotalPurchase'], 2);
        }
        
        return $customer;
    }
    
    /**
     * Helper function to determine if call result is positive
     * 
     * @param string $callResult
     * @return bool
     */
    private function isPositiveResult($callResult) {
        $positiveResults = [
            'สนใจ', 'ให้โทรกลับ', 'นัดหมาย', 'ขายได้', 'สั่งซื้อ', 
            'เก็บเงินได้', 'รอการตัดสินใจ', 'ติดต่อภายหลัง'
        ];
        
        return in_array($callResult, $positiveResults);
    }
    
    /**
     * Count rejection instances in call history
     * 
     * @param array $callHistory
     * @return int
     */
    private function countRejections($callHistory) {
        $rejectionKeywords = ['ไม่สนใจ', 'ติดต่อไม่ได้', 'ปฏิเสธ', 'ไม่รับ', 'ไม่ต้องการ'];
        $rejectionCount = 0;
        
        foreach ($callHistory as $call) {
            foreach ($rejectionKeywords as $keyword) {
                if (strpos($call['CallResult'], $keyword) !== false || 
                    strpos($call['TalkStatus'], $keyword) !== false) {
                    $rejectionCount++;
                    break; // Count once per call
                }
            }
        }
        
        return $rejectionCount;
    }
    
    /**
     * Get Grade description
     * 
     * @param string $grade
     * @return string
     */
    public function getGradeDescription($grade) {
        $descriptions = [
            'A' => 'VIP Customer (≥฿810,000)',
            'B' => 'Premium Customer (฿85,000-809,999)',
            'C' => 'Regular Customer (฿2,000-84,999)',
            'D' => 'New Customer (<฿2,000)'
        ];
        
        return $descriptions[$grade] ?? 'Unknown Grade';
    }
    
    /**
     * Get Temperature description
     * 
     * @param string $temperature
     * @return string
     */
    public function getTemperatureDescription($temperature) {
        $descriptions = [
            'HOT' => 'สนใจมาก - ติดตามทันที',
            'WARM' => 'สนใจปกติ - ติดตามสม่ำเสมอ',
            'COLD' => 'ไม่ค่อยสนใจ - เปลี่ยนแนวทาง',
            'FROZEN' => 'หยุดติดตาม - มีปัญหา'
        ];
        
        return $descriptions[$temperature] ?? 'Unknown Temperature';
    }
    
    /**
     * Batch update all customers' intelligence data
     * Admin function for data migration/correction
     * 
     * @param int $limit Limit number of customers to process
     * @return array Statistics
     */
    public function updateAllCustomersIntelligence($limit = 1000) {
        try {
            $stats = [
                'processed' => 0,
                'errors' => 0,
                'grade_changes' => 0,
                'temperature_changes' => 0,
                'start_time' => microtime(true)
            ];
            
            // Get all customers to process
            $sql = "SELECT CustomerCode FROM customers LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($customers as $customerCode) {
                try {
                    // Get current values
                    $current = $this->getCustomerIntelligenceData($customerCode);
                    $oldGrade = $current['Grade'] ?? null;
                    $oldTemperature = $current['CustomerTemperature'] ?? null;
                    
                    // Update intelligence
                    $this->updateCustomerIntelligence($customerCode, true, true);
                    
                    // Get new values
                    $updated = $this->getCustomerIntelligenceData($customerCode);
                    $newGrade = $updated['Grade'];
                    $newTemperature = $updated['CustomerTemperature'];
                    
                    // Track changes
                    if ($oldGrade !== $newGrade) {
                        $stats['grade_changes']++;
                    }
                    if ($oldTemperature !== $newTemperature) {
                        $stats['temperature_changes']++;
                    }
                    
                    $stats['processed']++;
                    
                } catch (Exception $e) {
                    $stats['errors']++;
                    error_log("Failed to update customer {$customerCode}: " . $e->getMessage());
                }
            }
            
            $stats['execution_time'] = round(microtime(true) - $stats['start_time'], 2);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Batch update failed: " . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Helper function for backward compatibility
 * Auto-trigger when orders are created/updated
 * 
 * @param string $customerCode
 */
function updateCustomerIntelligenceAuto($customerCode) {
    try {
        $intelligence = new CustomerIntelligence();
        return $intelligence->updateCustomerIntelligence($customerCode);
    } catch (Exception $e) {
        error_log("Auto intelligence update failed for {$customerCode}: " . $e->getMessage());
        return false;
    }
}

/**
 * Grade calculation function for direct use
 * 
 * @param string $customerCode
 * @return string
 */
function calculateGrade($customerCode) {
    try {
        $intelligence = new CustomerIntelligence();
        return $intelligence->calculateCustomerGrade($customerCode);
    } catch (Exception $e) {
        error_log("Grade calculation failed: " . $e->getMessage());
        return 'D';
    }
}

/**
 * Temperature calculation function for direct use
 * 
 * @param string $customerCode
 * @return string
 */
function calculateTemperature($customerCode) {
    try {
        $intelligence = new CustomerIntelligence();
        return $intelligence->calculateCustomerTemperature($customerCode);
    } catch (Exception $e) {
        error_log("Temperature calculation failed: " . $e->getMessage());
        return 'WARM';
    }
}

?>