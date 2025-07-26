<?php
/**
 * Sales Assignment API Endpoint
 * Handles sales assignment operations (create, transfer, end)
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

// Include required files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/SalesHistory.php';

// Check if user is logged in
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check permissions - only Supervisor and Admin can manage assignments
if (!hasRole('Supervisor') && !hasRole('Admin')) {
    sendJsonResponse([
        'success' => false, 
        'message' => 'ไม่มีสิทธิ์ในการจัดการการมอบหมายงาน'
    ], 403);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    // Verify CSRF token if provided
    if (isset($input['csrf_token'])) {
        if (!verifyCSRFToken($input['csrf_token'])) {
            sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
        }
    }
    
    // Create SalesHistory instance
    $salesHistoryModel = new SalesHistory();
    
    // Get action type
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'assign':
            // Create new assignment
            $customerCode = $input['customer_code'] ?? '';
            $salesName = $input['sales_name'] ?? '';
            $startDate = $input['start_date'] ?? null;
            
            // Validate required fields
            if (empty($customerCode) || empty($salesName)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'กรุณาระบุรหัสลูกค้าและชื่อพนักงานขาย'
                ], 400);
            }
            
            // Validate assignment data
            $assignmentData = [
                'CustomerCode' => $customerCode,
                'SaleName' => $salesName,
                'StartDate' => $startDate
            ];
            
            $validationErrors = $salesHistoryModel->validateAssignmentData($assignmentData);
            
            if (!empty($validationErrors)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validationErrors
                ], 400);
            }
            
            // Create the assignment
            $assignmentId = $salesHistoryModel->createSalesAssignment(
                $customerCode, 
                $salesName, 
                getCurrentUsername(),
                $startDate
            );
            
            if ($assignmentId) {
                // Log the activity
                logActivity('ASSIGN_CUSTOMER', "Assigned customer {$customerCode} to {$salesName}");
                
                // Get the created assignment details
                $assignment = $salesHistoryModel->getCurrentSalesAssignment($customerCode);
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'มอบหมายลูกค้าสำเร็จ',
                    'data' => [
                        'assignment_id' => $assignmentId,
                        'assignment' => $assignment
                    ]
                ]);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'ไม่สามารถมอบหมายลูกค้าได้'
                ], 500);
            }
            break;
            
        case 'transfer':
            // Transfer customer to new sales person
            $customerCode = $input['customer_code'] ?? '';
            $newSalesName = $input['new_sales_name'] ?? '';
            $transferDate = $input['transfer_date'] ?? null;
            
            // Validate required fields
            if (empty($customerCode) || empty($newSalesName)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'กรุณาระบุรหัสลูกค้าและชื่อพนักงานขายใหม่'
                ], 400);
            }
            
            // Transfer the customer
            $transferResult = $salesHistoryModel->transferCustomer(
                $customerCode, 
                $newSalesName, 
                getCurrentUsername(),
                $transferDate
            );
            
            if ($transferResult) {
                // Log the activity
                logActivity('TRANSFER_CUSTOMER', "Transferred customer {$customerCode} to {$newSalesName}");
                
                // Get the new assignment details
                $newAssignment = $salesHistoryModel->getCurrentSalesAssignment($customerCode);
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'โอนย้ายลูกค้าสำเร็จ',
                    'data' => [
                        'new_assignment' => $newAssignment
                    ]
                ]);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'ไม่สามารถโอนย้ายลูกค้าได้'
                ], 500);
            }
            break;
            
        case 'end':
            // End current assignment
            $customerCode = $input['customer_code'] ?? '';
            $endDate = $input['end_date'] ?? null;
            
            // Validate required fields
            if (empty($customerCode)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'กรุณาระบุรหัสลูกค้า'
                ], 400);
            }
            
            // End the assignment
            $endResult = $salesHistoryModel->endCurrentAssignment($customerCode, $endDate);
            
            if ($endResult) {
                // Update customer to remove sales assignment
                $customerModel = new Customer();
                $customerModel->updateCustomer($customerCode, [
                    'Sales' => null,
                    'AssignDate' => null,
                    'CartStatus' => 'ตะกร้าแจก'
                ]);
                
                // Log the activity
                logActivity('END_ASSIGNMENT', "Ended assignment for customer {$customerCode}");
                
                sendJsonResponse([
                    'success' => true,
                    'message' => 'สิ้นสุดการมอบหมายสำเร็จ'
                ]);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'ไม่สามารถสิ้นสุดการมอบหมายได้'
                ], 500);
            }
            break;
            
        case 'bulk_assign':
            // Bulk assign multiple customers to a sales person
            $customerCodes = $input['customer_codes'] ?? [];
            $salesName = $input['sales_name'] ?? '';
            $startDate = $input['start_date'] ?? null;
            
            // Validate required fields
            if (empty($customerCodes) || !is_array($customerCodes) || empty($salesName)) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'กรุณาระบุรายการลูกค้าและชื่อพนักงานขาย'
                ], 400);
            }
            
            $successCount = 0;
            $failedCustomers = [];
            
            foreach ($customerCodes as $customerCode) {
                $assignmentId = $salesHistoryModel->createSalesAssignment(
                    $customerCode, 
                    $salesName, 
                    getCurrentUsername(),
                    $startDate
                );
                
                if ($assignmentId) {
                    $successCount++;
                } else {
                    $failedCustomers[] = $customerCode;
                }
            }
            
            // Log the activity
            logActivity('BULK_ASSIGN', "Bulk assigned {$successCount} customers to {$salesName}");
            
            $message = "มอบหมายลูกค้าสำเร็จ {$successCount} รายการ";
            if (!empty($failedCustomers)) {
                $message .= " ไม่สำเร็จ " . count($failedCustomers) . " รายการ";
            }
            
            sendJsonResponse([
                'success' => true,
                'message' => $message,
                'data' => [
                    'success_count' => $successCount,
                    'failed_customers' => $failedCustomers,
                    'total_processed' => count($customerCodes)
                ]
            ]);
            break;
            
        default:
            sendJsonResponse([
                'success' => false,
                'message' => 'ไม่พบการดำเนินการที่ระบุ'
            ], 400);
            break;
    }
    
} catch (Exception $e) {
    error_log("Sales assignment error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ], 500);
}
?>