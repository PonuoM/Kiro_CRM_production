<?php
/**
 * Sales Records API
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
    require_once '../../config/database.php';
    require_once '../../includes/permissions.php';
    
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
    
    // Get sales records with customer information
    $salesQuery = "SELECT 
                    o.OrderID,
                    o.OrderDate,
                    o.OrderNumber,
                    o.TotalAmount,
                    o.OrderStatus,
                    COALESCE(o.OrderBy, o.CreatedBy) as SalesBy,
                    c.CustomerCode,
                    c.CustomerName,
                    c.CustomerTel,
                    c.CustomerAddress,
                    c.Sales as AssignedSales,
                    p.ProductName,
                    oi.Quantity,
                    oi.UnitPrice,
                    oi.LineTotal
                   FROM orders o
                   LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                   LEFT JOIN order_items oi ON o.OrderID = oi.OrderID
                   LEFT JOIN products p ON oi.ProductID = p.ProductID
                   WHERE o.OrderStatus != 'ยกเลิก' {$baseWhere}
                   ORDER BY o.OrderDate DESC, o.OrderID DESC";
    
    $stmt = $pdo->prepare($salesQuery);
    $stmt->execute($baseParams);
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by OrderID and aggregate products
    $groupedSales = [];
    foreach ($salesData as $row) {
        $orderId = $row['OrderID'];
        
        if (!isset($groupedSales[$orderId])) {
            $groupedSales[$orderId] = [
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
                'Products' => []
            ];
        }
        
        if ($row['ProductName']) {
            $groupedSales[$orderId]['Products'][] = [
                'ProductName' => $row['ProductName'],
                'Quantity' => $row['Quantity'],
                'UnitPrice' => $row['UnitPrice'],
                'LineTotal' => $row['LineTotal']
            ];
        }
    }
    
    // Convert to indexed array
    $salesRecords = array_values($groupedSales);
    
    // Get summary statistics
    $summaryQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(o.TotalAmount) as total_sales,
                        COUNT(CASE WHEN DATE(o.OrderDate) = CURDATE() THEN 1 END) as today_orders,
                        SUM(CASE WHEN DATE(o.OrderDate) = CURDATE() THEN o.TotalAmount ELSE 0 END) as today_sales,
                        COUNT(CASE WHEN MONTH(o.OrderDate) = MONTH(CURDATE()) AND YEAR(o.OrderDate) = YEAR(CURDATE()) THEN 1 END) as month_orders,
                        SUM(CASE WHEN MONTH(o.OrderDate) = MONTH(CURDATE()) AND YEAR(o.OrderDate) = YEAR(CURDATE()) THEN o.TotalAmount ELSE 0 END) as month_sales
                     FROM orders o
                     LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode  
                     WHERE o.OrderStatus != 'ยกเลิก' {$baseWhere}";
    
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