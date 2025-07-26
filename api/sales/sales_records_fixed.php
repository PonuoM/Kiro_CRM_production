<?php
/**
 * Sales Records API - FIXED for actual database structure
 * Returns sales data for the sales records page
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
    
    // Base query for sales records with permissions
    $baseWhere = '';
    $baseParams = [];
    
    if (!$canViewAll) {
        $baseWhere = " AND (o.OrderBy = ? OR c.Sales = ?)";
        $baseParams = [$currentUser, $currentUser];
    }
    
    // Get sales records from orders table (using actual structure)
    $salesQuery = "SELECT 
                    o.id as OrderID,
                    o.DocumentDate as OrderDate,
                    o.DocumentNo as OrderNumber,
                    o.Price as TotalAmount,
                    'เสร็จสิ้น' as OrderStatus,
                    COALESCE(o.OrderBy, o.CreatedBy) as SalesBy,
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
                   WHERE 1=1 {$baseWhere}
                   ORDER BY o.DocumentDate DESC";
    
    $stmt = $pdo->prepare($salesQuery);
    $stmt->execute($baseParams);
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each record and parse Products JSON if needed
    $salesRecords = [];
    foreach ($salesData as $row) {
        $products = [];
        
        // Try to parse Products field (might be JSON or simple string)
        if ($row['Products']) {
            $productData = $row['Products'];
            
            // Check if it's JSON
            $decoded = json_decode($productData, true);
            if ($decoded && is_array($decoded)) {
                foreach ($decoded as $product) {
                    $products[] = [
                        'ProductName' => $product['name'] ?? $product['ProductName'] ?? 'สินค้า',
                        'Quantity' => $product['quantity'] ?? $product['Quantity'] ?? $row['Quantity'] ?? 1,
                        'UnitPrice' => $product['price'] ?? $product['UnitPrice'] ?? $row['UnitPrice'] ?? 0,
                        'LineTotal' => ($product['quantity'] ?? 1) * ($product['price'] ?? $row['UnitPrice'] ?? 0)
                    ];
                }
            } else {
                // Simple string, create single product
                $products[] = [
                    'ProductName' => $productData,
                    'Quantity' => $row['Quantity'] ?? 1,
                    'UnitPrice' => $row['UnitPrice'] ?? 0,
                    'LineTotal' => ($row['Quantity'] ?? 1) * ($row['UnitPrice'] ?? 0)
                ];
            }
        }
        
        $salesRecords[] = [
            'OrderID' => $row['OrderID'],
            'OrderDate' => $row['OrderDate'],
            'OrderNumber' => $row['OrderNumber'],
            'TotalAmount' => $row['TotalAmount'],
            'OrderStatus' => $row['OrderStatus'],
            'SalesBy' => $row['SalesBy'],
            'CustomerCode' => $row['CustomerCode'],
            'CustomerName' => $row['CustomerName'],
            'CustomerTel' => $row['CustomerTel'],
            'CustomerAddress' => $row['CustomerAddress'],
            'AssignedSales' => $row['AssignedSales'],
            'Products' => $products
        ];
    }
    
    // Get summary statistics
    $summaryQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(o.Price) as total_sales,
                        COUNT(CASE WHEN DATE(o.DocumentDate) = CURDATE() THEN 1 END) as today_orders,
                        SUM(CASE WHEN DATE(o.DocumentDate) = CURDATE() THEN o.Price ELSE 0 END) as today_sales,
                        COUNT(CASE WHEN MONTH(o.DocumentDate) = MONTH(CURDATE()) AND YEAR(o.DocumentDate) = YEAR(CURDATE()) THEN 1 END) as month_orders,
                        SUM(CASE WHEN MONTH(o.DocumentDate) = MONTH(CURDATE()) AND YEAR(o.DocumentDate) = YEAR(CURDATE()) THEN o.Price ELSE 0 END) as month_sales
                     FROM orders o
                     LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode  
                     WHERE 1=1 {$baseWhere}";
    
    $stmt = $pdo->prepare($summaryQuery);
    $stmt->execute($baseParams);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Format the response
    $response = [
        'success' => true,
        'message' => 'Sales records loaded successfully',
        'data' => [
            'sales_records' => $salesRecords,
            'summary' => [
                'total_orders' => (int)$summary['total_orders'],
                'total_sales' => (float)$summary['total_sales'],
                'today_orders' => (int)$summary['today_orders'],
                'today_sales' => (float)$summary['today_sales'],
                'month_orders' => (int)$summary['month_orders'],
                'month_sales' => (float)$summary['month_sales']
            ]
        ],
        'user' => $currentUser,
        'permissions' => [
            'can_view_all' => $canViewAll
        ],
        'debug_info' => [
            'total_records_found' => count($salesRecords),
            'base_where' => $baseWhere,
            'current_user' => $currentUser
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>