<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    require_once '../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');      // Today
    $salesFilter = $_GET['sales_name'] ?? '';
    
    // Get sales performance data
    $sql = "SELECT 
                u.Username as sales_name,
                CONCAT(u.FirstName, ' ', u.LastName) as full_name,
                COUNT(DISTINCT c.CustomerCode) as assigned_customers,
                COUNT(DISTINCT CASE WHEN c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า') THEN c.CustomerCode END) as converted_customers,
                COUNT(DISTINCT o.OrderCode) as total_orders,
                COALESCE(SUM(CAST(REPLACE(REPLACE(o.TotalAmount, ',', ''), '฿', '') AS DECIMAL(10,2))), 0) as total_sales,
                CASE 
                    WHEN COUNT(DISTINCT o.OrderCode) > 0 THEN 
                        COALESCE(SUM(CAST(REPLACE(REPLACE(o.TotalAmount, ',', ''), '฿', '') AS DECIMAL(10,2))), 0) / COUNT(DISTINCT o.OrderCode)
                    ELSE 0 
                END as avg_sales,
                CASE 
                    WHEN COUNT(DISTINCT c.CustomerCode) > 0 THEN 
                        (COUNT(DISTINCT CASE WHEN c.CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า') THEN c.CustomerCode END) * 100.0 / COUNT(DISTINCT c.CustomerCode))
                    ELSE 0 
                END as conversion_rate
            FROM users u
            LEFT JOIN customers c ON u.Username = c.Sales
            LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode 
                AND DATE(o.OrderDate) BETWEEN ? AND ?
            WHERE u.Role = 'Sales' OR u.Role = 'Supervisor'";
    
    $params = [$dateFrom, $dateTo];
    
    // Add sales filter if specified
    if (!empty($salesFilter)) {
        $sql .= " AND u.Username = ?";
        $params[] = $salesFilter;
    }
    
    $sql .= " GROUP BY u.id, u.Username, u.FirstName, u.LastName
              ORDER BY total_sales DESC, conversion_rate DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary statistics
    $totalSales = array_sum(array_column($performance, 'total_sales'));
    $totalOrders = array_sum(array_column($performance, 'total_orders'));
    $avgConversionRate = count($performance) > 0 ? array_sum(array_column($performance, 'conversion_rate')) / count($performance) : 0;
    $activeSales = count($performance);
    
    // Format data to match JavaScript expectations
    $formattedPerformance = array_map(function($row) {
        return [
            'SaleName' => $row['sales_name'],
            'SalesFullName' => $row['full_name'],
            'TotalCustomers' => (int)$row['assigned_customers'],
            'ConvertedCustomers' => (int)$row['converted_customers'],
            'TotalOrders' => (int)$row['total_orders'],
            'TotalSales' => (float)$row['total_sales'],
            'AverageSales' => (float)$row['avg_sales'],
            'ConversionRate' => (float)$row['conversion_rate']
        ];
    }, $performance);
    
    echo json_encode([
        'status' => 'success',
        'data' => $formattedPerformance,
        'summary' => [
            'total_sales' => $totalSales,
            'total_orders' => $totalOrders,
            'avg_conversion_rate' => round($avgConversionRate, 1),
            'active_sales' => $activeSales
        ],
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sales_name' => $salesFilter
        ],
        'message' => 'Sales performance data loaded successfully'
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>