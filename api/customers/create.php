<?php
/**
 * Customer Create API Endpoint
 * POST /api/customers/create.php
 * 
 * Required fields:
 * - CustomerName: Customer name
 * - CustomerTel: Customer phone number
 * 
 * Optional fields:
 * - CustomerAddress: Customer address
 * - CustomerProvince: Customer province
 * - CustomerPostalCode: Customer postal code
 * - Agriculture: Agriculture type
 * - CustomerStatus: Customer status (default: ลูกค้าใหม่)
 * - CartStatus: Cart status (default: กำลังดูแล)
 * - Sales: Sales person (auto-assigned for Sale role)
 * - Tags: Customer tags
 */

session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Customer.php';

// Check authentication
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Check permissions - Admin, Supervisor, and Sale can create customers
if (!hasRole('Admin') && !hasRole('Supervisor') && !hasRole('Sale')) {
    sendJsonResponse(['success' => false, 'message' => 'คุณไม่มีสิทธิ์สร้างลูกค้า'], 403);
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง'], 400);
    }
    
    $customer = new Customer();
    
    // Sanitize input data
    $customerData = [];
    $allowedFields = [
        'CustomerName', 'CustomerTel', 'CustomerAddress', 'CustomerProvince', 
        'CustomerPostalCode', 'Agriculture', 'CustomerStatus', 'CartStatus', 
        'Sales', 'Tags'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $customerData[$field] = sanitizeInput($input[$field]);
        }
    }
    
    // Validate customer data
    $validationErrors = $customer->validateCustomerData($customerData, false);
    
    if (!empty($validationErrors)) {
        sendJsonResponse([
            'success' => false, 
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $validationErrors
        ], 400);
    }
    
    // Create customer
    $customerCode = $customer->createCustomer($customerData);
    
    if ($customerCode) {
        // Get the created customer data
        $createdCustomer = $customer->findByCode($customerCode);
        
        // Log activity
        logActivity('CREATE_CUSTOMER', "Created customer: {$customerCode}");
        
        sendJsonResponse([
            'success' => true,
            'message' => 'สร้างลูกค้าสำเร็จ',
            'data' => [
                'customer_code' => $customerCode,
                'customer' => $createdCustomer
            ]
        ], 201);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'ไม่สามารถสร้างลูกค้าได้'], 500);
    }
    
} catch (Exception $e) {
    error_log("Customer create API error: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ'], 500);
}
?>