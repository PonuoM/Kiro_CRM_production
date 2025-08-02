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
require_once __DIR__ . '/../../includes/customer_intelligence.php';

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
    
    // Log input data for debugging
    error_log("=== ORDER CREATE DEBUG ===");
    error_log("Input data received: " . json_encode($input, JSON_UNESCAPED_UNICODE));
    
    // Prepare order data - handle multiple products
    $orderData = [
        'CustomerCode' => $input['CustomerCode'] ?? $input['customer_code'] ?? '',
        'DocumentDate' => $input['DocumentDate'] ?? date('Y-m-d H:i:s'),
        'PaymentMethod' => $input['PaymentMethod'] ?? '',
        'DiscountAmount' => $input['discount_amount'] ?? 0,
        'DiscountPercent' => $input['discount_percent'] ?? 0,
        'DiscountRemarks' => $input['discount_remarks'] ?? ''
    ];
    
    error_log("Order data prepared: " . json_encode($orderData, JSON_UNESCAPED_UNICODE));
    
    // Handle products - support both single and multiple products
    if (isset($input['products']) && is_array($input['products'])) {
        // Multiple products format
        $orderData['products'] = $input['products'];
        
        // Calculate totals from multiple products (ONLY FOR FALLBACK - NOT USED IN DIRECT MAPPING)
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
        
        // Debug: Show difference between calculated and frontend values
        error_log("=== CALCULATION DEBUG ===");
        error_log("Calculated from products: $totalAmount");
        error_log("Frontend subtotal_amount: " . ($input['subtotal_amount'] ?? 'NOT SET'));
        
        // SIMPLE DIRECT MAPPING - NO CALCULATIONS
        // Take values directly from frontend textboxes as calculated by frontend
        
        // Get values from frontend (already calculated by JavaScript)
        // PRIORITIZE FRONTEND VALUES - Only use calculated fallback if truly missing
        $frontendTotalQuantity = isset($input['total_quantity']) && $input['total_quantity'] !== '' && $input['total_quantity'] !== null
            ? (float)$input['total_quantity'] : $totalQuantity;
        // FORCE USE FRONTEND VALUE - NEVER USE CALCULATED FALLBACK FOR SUBTOTAL
        $frontendSubtotal = (float)($input['subtotal_amount'] ?? $totalAmount);
        $frontendDiscountAmount = isset($input['discount_amount']) && $input['discount_amount'] !== '' && $input['discount_amount'] !== null
            ? (float)$input['discount_amount'] : 0;
        $frontendDiscountPercent = isset($input['discount_percent']) && $input['discount_percent'] !== '' && $input['discount_percent'] !== null
            ? (float)$input['discount_percent'] : 0;
        $frontendDiscountRemarks = $input['discount_remarks'] ?? '';
        $frontendFinalTotal = isset($input['total_amount']) && $input['total_amount'] !== '' && $input['total_amount'] !== null
            ? (float)$input['total_amount'] : $totalAmount;
            
        // Debug which values are being used
        error_log("=== VALUE SELECTION DEBUG ===");
        error_log("Using frontendSubtotal: $frontendSubtotal (from " . (isset($input['subtotal_amount']) && $input['subtotal_amount'] !== '' && $input['subtotal_amount'] !== null ? "FRONTEND" : "CALCULATED") . ")");
        error_log("Using frontendTotalQuantity: $frontendTotalQuantity (from " . (isset($input['total_quantity']) && $input['total_quantity'] !== '' && $input['total_quantity'] !== null ? "FRONTEND" : "CALCULATED") . ")");
        
        // Debug what we got from frontend
        error_log("=== FRONTEND DIRECT MAPPING ===");
        error_log("Frontend Total Quantity: " . $frontendTotalQuantity);
        error_log("Frontend Subtotal Amount: " . $frontendSubtotal);
        error_log("Frontend Discount Amount: " . $frontendDiscountAmount);
        error_log("Frontend Discount Percent: " . $frontendDiscountPercent);
        error_log("Frontend Final Total: " . $frontendFinalTotal);
        
        // DIRECT MAPPING - USE FRONTEND CALCULATED VALUES:
        $orderData['Quantity'] = $frontendTotalQuantity; // total-quantity
        $orderData['SubtotalAmount'] = $frontendSubtotal; // subtotal-amount (old field)
        $orderData['Subtotal_amount2'] = $frontendSubtotal; // subtotal-amount (new correct field)
        $orderData['DiscountAmount'] = $frontendDiscountAmount; // discount-amount
        $orderData['DiscountPercent'] = $frontendDiscountPercent; // discount-percent
        $orderData['DiscountRemarks'] = $frontendDiscountRemarks; // discount-remarks
        $orderData['Price'] = $frontendFinalTotal; // total-amount
        
        // Set legacy fields for compatibility
        $orderData['Products'] = implode(', ', array_filter($productNames));
        
    } else {
        // Legacy single product format - also use direct mapping
        $orderData['Products'] = $input['Products'] ?? '';
        
        // Direct mapping for legacy format too
        $orderData['Quantity'] = (float)($input['total_quantity'] ?? $input['Quantity'] ?? 0);
        $orderData['SubtotalAmount'] = (float)($input['subtotal_amount'] ?? 0);
        $orderData['Subtotal_amount2'] = (float)($input['subtotal_amount'] ?? 0); // new correct field
        $orderData['DiscountAmount'] = (float)($input['discount_amount'] ?? 0);
        $orderData['DiscountPercent'] = (float)($input['discount_percent'] ?? 0);
        $orderData['DiscountRemarks'] = $input['discount_remarks'] ?? '';
        $orderData['Price'] = (float)($input['total_amount'] ?? $input['Price'] ?? 0);
        
        // Convert to products array format for consistency
        $orderData['products'] = [[
            'name' => $orderData['Products'],
            'quantity' => $orderData['Quantity'],
            'price' => $orderData['Price'] / max(1, $orderData['Quantity']) // unit price
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
        // NOTE: createOrderItems จะถูกเรียกใน Order.php->createOrder() แล้ว
        // ไม่ต้องเรียกซ้ำที่นี่
        
        // Log the activity
        logActivity('CREATE_ORDER', "Created order {$documentNo} for customer {$orderData['CustomerCode']}");
        
        // Get the created order details
        $createdOrder = $orderModel->findByDocumentNo($documentNo);
        
        // Auto-trigger: Update customer intelligence after order creation
        try {
            $customerCode = $orderData['CustomerCode'];
            if (!empty($customerCode)) {
                updateCustomerIntelligenceAuto($customerCode);
                error_log("Customer intelligence updated for {$customerCode} after order creation");
            }
        } catch (Exception $e) {
            error_log("Failed to update customer intelligence: " . $e->getMessage());
            // Continue with order creation success even if intelligence update fails
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'สร้างคำสั่งซื้อสำเร็จ',
            'data' => [
                'DocumentNo' => $documentNo,
                'order' => $createdOrder
            ]
        ]);
    } else {
        // Get last error for debugging
        $error_info = $orderModel->getLastError();
        error_log("Order creation failed - no document number returned. Last DB error: " . print_r($error_info, true));
        
        sendJsonResponse([
            'success' => false,
            'message' => 'ไม่สามารถสร้างคำสั่งซื้อได้',
            'debug' => [
                'db_error' => $error_info,
                'input_data' => $orderData
            ]
        ], 500);
    }
    
} catch (Exception $e) {
    error_log("Order creation error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendJsonResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ',
        'debug' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], 500);
}
?>