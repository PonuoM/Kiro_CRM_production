<?php
/**
 * Create Order API Endpoint
 * Handles order creation requests
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

// Include required files
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/Order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
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
    
    // Create Order instance
    $orderModel = new Order();
    
    // Prepare order data - handle multiple products
    $orderData = [
        'CustomerCode' => $input['CustomerCode'] ?? $input['customer_code'] ?? '',
        'DocumentDate' => $input['DocumentDate'] ?? date('Y-m-d H:i:s'),
        'PaymentMethod' => $input['PaymentMethod'] ?? '',
        'DiscountAmount' => $input['discount_amount'] ?? 0,
        'DiscountPercent' => $input['discount_percent'] ?? 0,
        'DiscountRemarks' => $input['discount_remarks'] ?? ''
    ];
    
    // Handle products - support both single and multiple products
    if (isset($input['products']) && is_array($input['products'])) {
        // Multiple products format
        $orderData['products'] = $input['products'];
        
        // Calculate totals from multiple products
        $totalQuantity = 0;
        $totalAmount = 0;
        $productNames = [];
        
        foreach ($input['products'] as $product) {
            $quantity = (float)($product['quantity'] ?? 0);
            $price = (float)($product['price'] ?? 0);
            $totalQuantity += $quantity;
            $totalAmount += ($quantity * $price);
            $productNames[] = $product['name'] ?? '';
        }
        
        // Store subtotal before discount
        $orderData['SubtotalAmount'] = $totalAmount;
        
        // Apply discount to final total
        $discountAmount = (float)($orderData['DiscountAmount'] ?? 0);
        $finalTotal = max(0, $totalAmount - $discountAmount);
        
        // Set legacy fields for compatibility
        $orderData['Products'] = implode(', ', array_filter($productNames));
        $orderData['Quantity'] = $totalQuantity;
        $orderData['Price'] = $finalTotal; // Final total after discount
        
    } else {
        // Legacy single product format
        $orderData['Products'] = $input['Products'] ?? '';
        $orderData['Quantity'] = $input['Quantity'] ?? 0;
        $orderData['Price'] = $input['Price'] ?? 0;
        
        // Convert to products array format for consistency
        $orderData['products'] = [[
            'name' => $orderData['Products'],
            'quantity' => $orderData['Quantity'],
            'price' => $orderData['Price']
        ]];
    }
    
    // Optional fields
    if (isset($input['DocumentNo']) && !empty($input['DocumentNo'])) {
        $orderData['DocumentNo'] = $input['DocumentNo'];
    }
    
    // Validate order data
    $validationErrors = $orderModel->validateOrderData($orderData);
    
    if (!empty($validationErrors)) {
        // Create a more helpful message
        $errorCount = count($validationErrors);
        $message = $errorCount === 1 ? 
            'พบข้อผิดพลาด 1 รายการ' : 
            "พบข้อผิดพลาด {$errorCount} รายการ";
            
        sendJsonResponse([
            'success' => false, 
            'message' => $message,
            'errors' => $validationErrors
        ], 400);
    }
    
    // Create the order
    $documentNo = $orderModel->createOrder($orderData);
    
    if ($documentNo) {
        // Log the activity
        logActivity('CREATE_ORDER', "Created order {$documentNo} for customer {$orderData['CustomerCode']}");
        
        // Get the created order details
        $createdOrder = $orderModel->findByDocumentNo($documentNo);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'สร้างคำสั่งซื้อสำเร็จ',
            'data' => [
                'DocumentNo' => $documentNo,
                'order' => $createdOrder
            ]
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'ไม่สามารถสร้างคำสั่งซื้อได้'
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Order creation error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ'
    ], 500);
}
?>