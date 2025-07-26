<?php
/**
 * Test Order Creation - Simplified version for debugging
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable for JSON output

header('Content-Type: application/json; charset=utf-8');

function sendTestResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    // Skip authentication for testing
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendTestResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendTestResponse(['success' => false, 'message' => 'Invalid JSON data: ' . json_last_error_msg()], 400);
    }
    
    // Include required files
    require_once __DIR__ . '/../../includes/functions.php';
    require_once __DIR__ . '/../../includes/Order.php';
    
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
        sendTestResponse(['success' => false, 'message' => 'No products provided'], 400);
    }
    
    // Validate order data
    $validationErrors = $orderModel->validateOrderData($orderData);
    
    if (!empty($validationErrors)) {
        sendTestResponse([
            'success' => false, 
            'message' => 'ข้อมูลไม่ถูกต้อง',
            'errors' => $validationErrors
        ], 400);
    }
    
    // Create the order
    $documentNo = $orderModel->createOrder($orderData);
    
    if ($documentNo) {
        // Get the created order details
        $createdOrder = $orderModel->findByDocumentNo($documentNo);
        
        sendTestResponse([
            'success' => true,
            'message' => 'สร้างคำสั่งซื้อสำเร็จ',
            'data' => [
                'DocumentNo' => $documentNo,
                'order' => $createdOrder
            ]
        ]);
    } else {
        sendTestResponse([
            'success' => false,
            'message' => 'ไม่สามารถสร้างคำสั่งซื้อได้'
        ], 500);
    }
    
} catch (Exception $e) {
    sendTestResponse([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
?>