<?php
/**
 * Customer Update API Endpoint
 * PUT /api/customers/update.php
 * 
 * Required fields:
 * - CustomerCode: Customer code to update
 * 
 * Optional fields:
 * - CustomerName: Customer name
 * - CustomerTel: Customer phone number
 * - CustomerAddress: Customer address
 * - CustomerProvince: Customer province
 * - CustomerPostalCode: Customer postal code
 * - Agriculture: Agriculture type
 * - CustomerStatus: Customer status
 * - CartStatus: Cart status
 * - Sales: Sales person (Admin/Supervisor only)
 * - Tags: Customer tags
 */

session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Customer.php';

// Check authentication
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check permissions - Admin, Supervisor, and Sale can update customers
if (!hasRole('Admin') && !hasRole('Supervisor') && !hasRole('Sale')) {
    sendJsonResponse(['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขลูกค้า'], 403);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['CustomerCode'])) {
        sendJsonResponse(['success' => false, 'message' => 'กรุณาระบุรหัสลูกค้า'], 400);
    }
    
    $customer = new Customer();
    $customerCode = sanitizeInput($input['CustomerCode']);
    
    // Check if customer exists
    $existingCustomer = $customer->findByCode($customerCode);
    if (!$existingCustomer) {
        sendJsonResponse(['success' => false, 'message' => 'ไม่พบข้อมูลลูกค้า'], 404);
    }
    
    // Check access permissions for Sale role
    if (getCurrentUserRole() === 'Sale') {
        if ($existingCustomer['Sales'] !== getCurrentUsername()) {
            sendJsonResponse(['success' => false, 'message' => 'คุณไม่มีสิทธิ์แก้ไขข้อมูลลูกค้านี้'], 403);
        }
    }
    
    // Sanitize input data
    $customerData = [];
    $allowedFields = [
        'CustomerName', 'CustomerTel', 'CustomerAddress', 'CustomerProvince', 
        'CustomerPostalCode', 'Agriculture', 'CustomerStatus', 'CartStatus', 
        'Tags'
    ];
    
    // Sales assignment is only allowed for Admin and Supervisor
    if (hasRole('Admin') || hasRole('Supervisor')) {
        $allowedFields[] = 'Sales';
    }
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $customerData[$field] = sanitizeInput($input[$field]);
        }
    }
    
    // Add CustomerCode for validation
    $customerData['CustomerCode'] = $customerCode;
    
    // Validate customer data
    $validationErrors = $customer->validateCustomerData($customerData, true);
    
    if (!empty($validationErrors)) {
        sendJsonResponse([
            'success' => false, 
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $validationErrors
        ], 400);
    }
    
    // Remove CustomerCode from update data
    unset($customerData['CustomerCode']);
    
    // Handle sales assignment
    if (isset($customerData['Sales']) && $customerData['Sales'] !== $existingCustomer['Sales']) {
        $customerData['AssignDate'] = date('Y-m-d H:i:s');
        
        // Create sales history record
        $db = getDB();
        
        // End previous assignment if exists
        if (!empty($existingCustomer['Sales'])) {
            $db->execute(
                "UPDATE sales_histories SET EndDate = ? WHERE CustomerCode = ? AND EndDate IS NULL",
                [date('Y-m-d H:i:s'), $customerCode]
            );
        }
        
        // Create new assignment record
        if (!empty($customerData['Sales'])) {
            $db->execute(
                "INSERT INTO sales_histories (CustomerCode, SaleName, StartDate, AssignBy, CreatedDate, CreatedBy) VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $customerCode,
                    $customerData['Sales'],
                    date('Y-m-d H:i:s'),
                    getCurrentUsername(),
                    date('Y-m-d H:i:s'),
                    getCurrentUsername()
                ]
            );
        }
    }
    
    // Update customer
    $success = $customer->updateCustomer($customerCode, $customerData);
    
    if ($success) {
        // Get updated customer data
        $updatedCustomer = $customer->findByCode($customerCode);
        
        // Log activity
        logActivity('UPDATE_CUSTOMER', "Updated customer: {$customerCode}");
        
        sendJsonResponse([
            'success' => true,
            'message' => 'อัปเดตข้อมูลลูกค้าสำเร็จ',
            'data' => [
                'customer' => $updatedCustomer
            ]
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'ไม่สามารถอัปเดตข้อมูลลูกค้าได้'], 500);
    }
    
} catch (Exception $e) {
    error_log("Customer update API error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ'], 500);
}
?>