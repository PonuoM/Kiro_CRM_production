<?php
/**
 * Order Detail API - Get specific order information
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/permissions.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    // Get order ID from parameter
    $orderId = $_GET['id'] ?? null;
    
    if (!$orderId) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        exit;
    }
    
    // Base query with permissions
    $baseWhere = '';
    $baseParams = [$orderId];
    
    if (!$canViewAll) {
        $baseWhere = " AND (o.OrderBy = ? OR c.Sales = ?)";
        $baseParams[] = $currentUser;
        $baseParams[] = $currentUser;
    }
    
    // Get detailed order information
    $orderQuery = "SELECT 
                    o.id as OrderID,
                    o.DocumentDate as OrderDate,
                    o.DocumentNo as OrderNumber,
                    o.Price as TotalAmount,
                    o.PaymentMethod,
                    'เสร็จสิ้น' as OrderStatus,
                    COALESCE(o.OrderBy, o.CreatedBy) as SalesBy,
                    o.CreatedDate,
                    c.CustomerCode,
                    c.CustomerName,
                    c.CustomerTel,
                    c.CustomerAddress,
                    c.Sales as AssignedSales,
                    o.Products,
                    o.Quantity,
                    o.Price as UnitPrice
                   FROM orders o
                   LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                   WHERE o.id = ? {$baseWhere}";
    
    $stmt = $pdo->prepare($orderQuery);
    $stmt->execute($baseParams);
    $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$orderData) {
        echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
        exit;
    }
    
    // Process products
    $products = [];
    if ($orderData['Products']) {
        $productData = $orderData['Products'];
        
        // Check if it's JSON
        $decoded = json_decode($productData, true);
        if ($decoded && is_array($decoded)) {
            foreach ($decoded as $product) {
                $products[] = [
                    'ProductName' => $product['name'] ?? $product['ProductName'] ?? 'สินค้า',
                    'Quantity' => $product['quantity'] ?? $product['Quantity'] ?? $orderData['Quantity'] ?? 1,
                    'UnitPrice' => $product['price'] ?? $product['UnitPrice'] ?? $orderData['UnitPrice'] ?? 0,
                    'LineTotal' => ($product['quantity'] ?? 1) * ($product['price'] ?? $orderData['UnitPrice'] ?? 0)
                ];
            }
        } else {
            // Simple string, create single product
            $products[] = [
                'ProductName' => $productData,
                'Quantity' => $orderData['Quantity'] ?? 1,
                'UnitPrice' => $orderData['UnitPrice'] ?? 0,
                'LineTotal' => ($orderData['Quantity'] ?? 1) * ($orderData['UnitPrice'] ?? 0)
            ];
        }
    }
    
    // Format detailed order response
    $orderDetail = [
        'OrderID' => $orderData['OrderID'],
        'OrderDate' => $orderData['OrderDate'],
        'OrderNumber' => $orderData['OrderNumber'],
        'TotalAmount' => $orderData['TotalAmount'],
        'PaymentMethod' => $orderData['PaymentMethod'],
        'OrderStatus' => $orderData['OrderStatus'],
        'SalesBy' => $orderData['SalesBy'],
        'CreatedDate' => $orderData['CreatedDate'],
        'Customer' => [
            'CustomerCode' => $orderData['CustomerCode'],
            'CustomerName' => $orderData['CustomerName'],
            'CustomerTel' => $orderData['CustomerTel'],
            'CustomerAddress' => $orderData['CustomerAddress'],
            'AssignedSales' => $orderData['AssignedSales']
        ],
        'Products' => $products,
        'ProductSummary' => [
            'total_items' => count($products),
            'total_quantity' => array_sum(array_column($products, 'Quantity')),
            'subtotal' => array_sum(array_column($products, 'LineTotal'))
        ]
    ];
    
    $response = [
        'success' => true,
        'message' => 'Order details loaded successfully',
        'data' => $orderDetail,
        'user' => $currentUser,
        'permissions' => [
            'can_view_all' => $canViewAll
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__,
        'order_id' => $_GET['id'] ?? null
    ], JSON_PRETTY_PRINT);
}
?>