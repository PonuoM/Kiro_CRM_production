<?php
/**
 * Sales Departure Workflow
 * Story 2.1: Implement Lead Re-assignment Logic
 * 
 * Handles automatic lead reassignment when a sales user is deactivated
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/Task.php';

class SalesDepartureWorkflow extends BaseModel {
    
    /**
     * Main orchestrator for sales departure workflow
     * @param int $salesUserId
     * @return array|false Workflow results or false on failure
     */
    public function triggerSalesDepartureWorkflow($salesUserId) {
        try {
            // Validate input first (before transaction)
            if (!$salesUserId || $salesUserId <= 0) {
                error_log("SalesDepartureWorkflow: Invalid sales user ID: {$salesUserId}");
                return false;
            }
            
            // Start transaction for data integrity
            $this->beginTransaction();
            
            // Get sales user details
            $userModel = new User();
            $salesUser = $userModel->find($salesUserId);
            
            if (!$salesUser) {
                $this->rollback();
                error_log("SalesDepartureWorkflow: Sales user not found: {$salesUserId}");
                return false;
            }
            
            if ($salesUser['Role'] !== 'Sale') {
                $this->rollback();
                error_log("SalesDepartureWorkflow: User {$salesUserId} is not a sales role: {$salesUser['Role']}");
                return false;
            }
            
            // Log departure workflow start
            $this->logDepartureEvent($salesUserId, 'WORKFLOW_START', "Starting departure workflow for {$salesUser['Username']}");
            
            // Get supervisor for reassignment
            $supervisorId = $salesUser['supervisor_id'] ?? null;
            $supervisorUsername = null;
            
            if ($supervisorId) {
                $supervisor = $userModel->find($supervisorId);
                $supervisorUsername = $supervisor ? $supervisor['Username'] : null;
            }
            
            // Execute 3-tier reassignment logic
            $results = [
                'sales_user' => $salesUser['Username'],
                'supervisor' => $supervisorUsername,
                'categories' => []
            ];
            
            // Category 1: Active Tasks → Reassign to Supervisor
            $category1Result = $this->reassignActiveTaskLeads($salesUser['Username'], $supervisorUsername);
            $results['categories']['active_tasks'] = $category1Result;
            
            // Category 2: Follow-up Customers → Move to Waiting Basket
            $category2Result = $this->moveFollowUpLeadsToWaiting($salesUser['Username']);
            $results['categories']['followup_leads'] = $category2Result;
            
            // Category 3: New Uncontacted Customers → Move to Distribution Basket
            $category3Result = $this->moveNewLeadsToDistribution($salesUser['Username']);
            $results['categories']['new_leads'] = $category3Result;
            
            // Calculate totals
            $results['totals'] = [
                'active_tasks' => $category1Result['count'],
                'followup_leads' => $category2Result['count'],
                'new_leads' => $category3Result['count'],
                'total_processed' => $category1Result['count'] + $category2Result['count'] + $category3Result['count']
            ];
            
            // Log successful workflow completion
            $this->logDepartureEvent($salesUserId, 'WORKFLOW_COMPLETE', "Processed {$results['totals']['total_processed']} leads");
            
            // Commit transaction
            $this->commit();
            
            return $results;
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->rollback();
            
            // Log error
            $this->logDepartureEvent($salesUserId, 'WORKFLOW_ERROR', $e->getMessage());
            error_log("Sales departure workflow error: " . $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Category 1: Reassign active task leads to supervisor
     * @param string $salesUsername
     * @param string $supervisorUsername
     * @return array
     */
    public function reassignActiveTaskLeads($salesUsername, $supervisorUsername) {
        try {
            if (!$supervisorUsername) {
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'No supervisor assigned - cannot reassign active tasks',
                    'customers' => []
                ];
            }
            
            // Find customers with active tasks assigned to this sales person
            $sql = "SELECT DISTINCT c.CustomerCode, c.CustomerName, c.Sales
                    FROM customers c
                    INNER JOIN tasks t ON c.CustomerCode = t.CustomerCode
                    WHERE c.Sales = ? AND t.Status = 'รอดำเนินการ'";
            
            $customersWithActiveTasks = $this->query($sql, [$salesUsername]);
            
            if (empty($customersWithActiveTasks)) {
                return [
                    'success' => true,
                    'count' => 0,
                    'message' => 'No customers with active tasks found',
                    'customers' => []
                ];
            }
            
            $customerModel = new Customer();
            $reassignedCount = 0;
            $reassignedCustomers = [];
            
            foreach ($customersWithActiveTasks as $customer) {
                // Reassign customer to supervisor
                $updateResult = $customerModel->updateCustomer($customer['CustomerCode'], [
                    'Sales' => $supervisorUsername,
                    'AssignDate' => date('Y-m-d H:i:s'),
                    'ModifiedBy' => 'system_departure_workflow'
                ]);
                
                if ($updateResult) {
                    $reassignedCount++;
                    $reassignedCustomers[] = [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'from_sales' => $salesUsername,
                        'to_supervisor' => $supervisorUsername
                    ];
                    
                    // Log individual reassignment
                    $this->logDepartureEvent(null, 'ACTIVE_TASK_REASSIGN', 
                        "Customer {$customer['CustomerCode']} reassigned from {$salesUsername} to {$supervisorUsername}");
                }
            }
            
            return [
                'success' => true,
                'count' => $reassignedCount,
                'message' => "Reassigned {$reassignedCount} customers with active tasks to supervisor",
                'customers' => $reassignedCustomers
            ];
            
        } catch (Exception $e) {
            error_log("Active task reassignment error: " . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Error reassigning active tasks: ' . $e->getMessage(),
                'customers' => []
            ];
        }
    }
    
    /**
     * Category 2: Move follow-up customers to waiting basket
     * @param string $salesUsername
     * @return array
     */
    public function moveFollowUpLeadsToWaiting($salesUsername) {
        try {
            // Find follow-up customers without active tasks
            $sql = "SELECT c.CustomerCode, c.CustomerName, c.Sales
                    FROM customers c
                    LEFT JOIN tasks t ON c.CustomerCode = t.CustomerCode AND t.Status = 'รอดำเนินการ'
                    WHERE c.Sales = ? 
                    AND c.CustomerStatus = 'ลูกค้าติดตาม'
                    AND t.CustomerCode IS NULL";
            
            $followUpCustomers = $this->query($sql, [$salesUsername]);
            
            if (empty($followUpCustomers)) {
                return [
                    'success' => true,
                    'count' => 0,
                    'message' => 'No follow-up customers without active tasks found',
                    'customers' => []
                ];
            }
            
            $customerModel = new Customer();
            $movedCount = 0;
            $movedCustomers = [];
            
            foreach ($followUpCustomers as $customer) {
                // Move to waiting basket and clear sales assignment
                $updateResult = $customerModel->updateCustomer($customer['CustomerCode'], [
                    'CartStatus' => 'ตะกร้ารอ',
                    'Sales' => null,
                    'AssignDate' => null,
                    'ModifiedBy' => 'system_departure_workflow'
                ]);
                
                if ($updateResult) {
                    $movedCount++;
                    $movedCustomers[] = [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'from_sales' => $salesUsername,
                        'to_status' => 'ตะกร้ารอ'
                    ];
                    
                    // Log individual move
                    $this->logDepartureEvent(null, 'FOLLOWUP_TO_WAITING', 
                        "Customer {$customer['CustomerCode']} moved from {$salesUsername} to waiting basket");
                }
            }
            
            return [
                'success' => true,
                'count' => $movedCount,
                'message' => "Moved {$movedCount} follow-up customers to waiting basket",
                'customers' => $movedCustomers
            ];
            
        } catch (Exception $e) {
            error_log("Follow-up leads processing error: " . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Error processing follow-up leads: ' . $e->getMessage(),
                'customers' => []
            ];
        }
    }
    
    /**
     * Category 3: Move new uncontacted customers to distribution basket
     * @param string $salesUsername
     * @return array
     */
    public function moveNewLeadsToDistribution($salesUsername) {
        try {
            // Find new customers with no contact attempts
            $sql = "SELECT c.CustomerCode, c.CustomerName, c.Sales
                    FROM customers c
                    WHERE c.Sales = ? 
                    AND c.CustomerStatus = 'ลูกค้าใหม่'
                    AND (c.ContactAttempts = 0 OR c.ContactAttempts IS NULL)";
            
            $newCustomers = $this->query($sql, [$salesUsername]);
            
            if (empty($newCustomers)) {
                return [
                    'success' => true,
                    'count' => 0,
                    'message' => 'No new uncontacted customers found',
                    'customers' => []
                ];
            }
            
            $customerModel = new Customer();
            $movedCount = 0;
            $movedCustomers = [];
            
            foreach ($newCustomers as $customer) {
                // Move to distribution basket and clear sales assignment
                $updateResult = $customerModel->updateCustomer($customer['CustomerCode'], [
                    'CartStatus' => 'ตะกร้าแจก',
                    'Sales' => null,
                    'AssignDate' => null,
                    'ModifiedBy' => 'system_departure_workflow'
                ]);
                
                if ($updateResult) {
                    $movedCount++;
                    $movedCustomers[] = [
                        'customer_code' => $customer['CustomerCode'],
                        'customer_name' => $customer['CustomerName'],
                        'from_sales' => $salesUsername,
                        'to_status' => 'ตะกร้าแจก'
                    ];
                    
                    // Log individual move
                    $this->logDepartureEvent(null, 'NEW_TO_DISTRIBUTION', 
                        "Customer {$customer['CustomerCode']} moved from {$salesUsername} to distribution basket");
                }
            }
            
            return [
                'success' => true,
                'count' => $movedCount,
                'message' => "Moved {$movedCount} new customers to distribution basket",
                'customers' => $movedCustomers
            ];
            
        } catch (Exception $e) {
            error_log("New leads processing error: " . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Error processing new leads: ' . $e->getMessage(),
                'customers' => []
            ];
        }
    }
    
    /**
     * Validate sales user and get supervisor information
     * @param int $salesUserId
     * @return array|false
     */
    public function validateSalesUser($salesUserId) {
        try {
            $userModel = new User();
            $salesUser = $userModel->find($salesUserId);
            
            if (!$salesUser) {
                return false;
            }
            
            if ($salesUser['Role'] !== 'Sale') {
                return false;
            }
            
            $supervisor = null;
            if ($salesUser['supervisor_id']) {
                $supervisor = $userModel->find($salesUser['supervisor_id']);
            }
            
            return [
                'sales_user' => $salesUser,
                'supervisor' => $supervisor
            ];
            
        } catch (Exception $e) {
            error_log("Sales user validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log departure workflow events for audit trail
     * @param int $salesUserId
     * @param string $eventType
     * @param string $message
     * @return bool
     */
    private function logDepartureEvent($salesUserId, $eventType, $message) {
        try {
            // Use system log table if available, otherwise log to error log
            if (function_exists('logActivity')) {
                logActivity($eventType, $message);
            } else {
                error_log("DEPARTURE_WORKFLOW [{$eventType}]: {$message}");
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to log departure event: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get departure workflow statistics for a sales user
     * @param string $salesUsername
     * @return array
     */
    public function getDepartureStatistics($salesUsername) {
        try {
            $stats = [
                'active_tasks_count' => 0,
                'followup_leads_count' => 0,
                'new_leads_count' => 0,
                'total_leads' => 0
            ];
            
            // Count customers with active tasks
            $sql = "SELECT COUNT(DISTINCT c.CustomerCode) as count
                    FROM customers c
                    INNER JOIN tasks t ON c.CustomerCode = t.CustomerCode
                    WHERE c.Sales = ? AND t.Status = 'รอดำเนินการ'";
            $result = $this->queryOne($sql, [$salesUsername]);
            $stats['active_tasks_count'] = $result['count'] ?? 0;
            
            // Count follow-up customers without active tasks
            $sql = "SELECT COUNT(c.CustomerCode) as count
                    FROM customers c
                    LEFT JOIN tasks t ON c.CustomerCode = t.CustomerCode AND t.Status = 'รอดำเนินการ'
                    WHERE c.Sales = ? 
                    AND c.CustomerStatus = 'ลูกค้าติดตาม'
                    AND t.CustomerCode IS NULL";
            $result = $this->queryOne($sql, [$salesUsername]);
            $stats['followup_leads_count'] = $result['count'] ?? 0;
            
            // Count new uncontacted customers
            $sql = "SELECT COUNT(c.CustomerCode) as count
                    FROM customers c
                    WHERE c.Sales = ? 
                    AND c.CustomerStatus = 'ลูกค้าใหม่'
                    AND (c.ContactAttempts = 0 OR c.ContactAttempts IS NULL)";
            $result = $this->queryOne($sql, [$salesUsername]);
            $stats['new_leads_count'] = $result['count'] ?? 0;
            
            $stats['total_leads'] = $stats['active_tasks_count'] + $stats['followup_leads_count'] + $stats['new_leads_count'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error getting departure statistics: " . $e->getMessage());
            return [
                'active_tasks_count' => 0,
                'followup_leads_count' => 0,
                'new_leads_count' => 0,
                'total_leads' => 0
            ];
        }
    }
}
?>