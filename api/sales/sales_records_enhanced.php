<?php
/**
 * Enhanced Sales Records API with Month and Product Filtering
 * Supports: month=YYYY-MM, product=ProductName or Category
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
    
    // Parse filter parameters
    $monthFilter = $_GET['month'] ?? null;  // Format: YYYY-MM
    $productFilter = $_GET['product'] ?? null;  // Product name or category
    
    // Base query conditions
    $whereConditions = ['1=1'];
    $params = [];
    
    // User permissions
    if (!$canViewAll) {
        $whereConditions[] = "(o.OrderBy = ? OR c.Sales = ?)";
        $params[] = $currentUser;
        $params[] = $currentUser;
    }
    
    // Month filter
    if ($monthFilter && preg_match('/^\d{4}-\d{2}$/', $monthFilter)) {
        $whereConditions[] = "DATE_FORMAT(o.DocumentDate, '%Y-%m') = ?";
        $params[] = $monthFilter;
    }
    
    // Product filter based on product_code
    if ($productFilter) {
        // Check if it's a category filter
        if ($productFilter === 'FER') {
            $whereConditions[] = "o.Products LIKE 'FER-%'";
        } elseif ($productFilter === 'BIO') {
            $whereConditions[] = "o.Products LIKE 'BIO-%'";
        } else {
            // Specific product code filter
            $whereConditions[] = "o.Products = ?";
            $params[] = $productFilter;
        }
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Enhanced sales query with Products table JOIN
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
                    o.Price as UnitPrice,
                    p.product_code,
                    p.product_name as ProductNameFromTable,
                    p.category
                   FROM orders o
                   LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                   LEFT JOIN products p ON p.product_code = o.Products
                   WHERE {$whereClause}
                   ORDER BY o.DocumentDate DESC";
    
    $stmt = $pdo->prepare($salesQuery);
    $stmt->execute($params);
    $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each record with enhanced product information
    $salesRecords = [];
    foreach ($salesData as $row) {
        $products = [];
        
        // Use Products table data if available, otherwise fallback to original
        if ($row['product_code']) {
            $products[] = [
                'ProductCode' => $row['product_code'],
                'ProductName' => $row['ProductNameFromTable'] ?? $row['Products'],
                'ProductType' => $row['category'],
                'Quantity' => $row['Quantity'] ?? 1,
                'UnitPrice' => $row['UnitPrice'] ?? 0,
                'LineTotal' => ($row['Quantity'] ?? 1) * ($row['UnitPrice'] ?? 0)
            ];
        } else {
            // Fallback: Try to parse original Products field
            if ($row['Products']) {
                $productData = $row['Products'];
                
                // Check if it's JSON
                $decoded = json_decode($productData, true);
                if ($decoded && is_array($decoded)) {
                    foreach ($decoded as $product) {
                        $products[] = [
                            'ProductCode' => 'UNK',
                            'ProductName' => $product['name'] ?? $product['ProductName'] ?? 'สินค้า',
                            'ProductType' => 'Other',
                            'Quantity' => $product['quantity'] ?? $product['Quantity'] ?? $row['Quantity'] ?? 1,
                            'UnitPrice' => $product['price'] ?? $product['UnitPrice'] ?? $row['UnitPrice'] ?? 0,
                            'LineTotal' => ($product['quantity'] ?? 1) * ($product['price'] ?? $row['UnitPrice'] ?? 0)
                        ];
                    }
                } else {
                    // Simple string, create single product
                    $products[] = [
                        'ProductCode' => 'UNK',
                        'ProductName' => $productData,
                        'ProductType' => 'Other',
                        'Quantity' => $row['Quantity'] ?? 1,
                        'UnitPrice' => $row['UnitPrice'] ?? 0,
                        'LineTotal' => ($row['Quantity'] ?? 1) * ($row['UnitPrice'] ?? 0)
                    ];
                }
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
    
    // Enhanced summary statistics with filters
    $summaryQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(o.Price) as total_sales,
                        COUNT(CASE WHEN DATE(o.DocumentDate) = CURDATE() THEN 1 END) as today_orders,
                        SUM(CASE WHEN DATE(o.DocumentDate) = CURDATE() THEN o.Price ELSE 0 END) as today_sales,
                        COUNT(CASE WHEN MONTH(o.DocumentDate) = MONTH(CURDATE()) AND YEAR(o.DocumentDate) = YEAR(CURDATE()) THEN 1 END) as month_orders,
                        SUM(CASE WHEN MONTH(o.DocumentDate) = MONTH(CURDATE()) AND YEAR(o.DocumentDate) = YEAR(CURDATE()) THEN o.Price ELSE 0 END) as month_sales
                     FROM orders o
                     LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode  
                     WHERE {$whereClause}";
    
    $stmt = $pdo->prepare($summaryQuery);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get available products for filter dropdown
    $productsQuery = "SELECT DISTINCT product_code, product_name 
                      FROM products 
                      WHERE product_code IS NOT NULL AND product_code != '' 
                      ORDER BY product_code";
    $stmt = $pdo->prepare($productsQuery);
    $stmt->execute();
    $availableProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate product category statistics based on product_code
    $productStats = [
        'fertilizer_count' => 0,  // FER products
        'bio_count' => 0,         // BIO products
        'other_count' => 0,
        'total_product_quantity' => 0,
        'total_orders' => count($salesRecords),
        'total_sales_amount' => 0
    ];
    
    foreach ($salesRecords as $record) {
        // Add to total sales amount
        $productStats['total_sales_amount'] += (float)$record['TotalAmount'];
        
        foreach ($record['Products'] as $product) {
            $productCode = $product['ProductCode'] ?? '';
            $quantity = (int)$product['Quantity'];
            
            $productStats['total_product_quantity'] += $quantity;
            
            // Classify by product code (FER- or BIO-)
            if (strpos($productCode, 'FER-') === 0) {
                $productStats['fertilizer_count'] += $quantity;
            } elseif (strpos($productCode, 'BIO-') === 0) {
                $productStats['bio_count'] += $quantity;
            } else {
                $productStats['other_count'] += $quantity;
            }
        }
    }
    
    // Format the enhanced response
    $response = [
        'success' => true,
        'message' => 'Enhanced sales records loaded successfully',
        'data' => [
            'sales_records' => $salesRecords,
            'summary' => [
                'total_orders' => (int)$summary['total_orders'],
                'total_sales' => (float)$summary['total_sales'],
                'today_orders' => (int)$summary['today_orders'],
                'today_sales' => (float)$summary['today_sales'],
                'month_orders' => (int)$summary['month_orders'],
                'month_sales' => (float)$summary['month_sales']
            ],
            'product_stats' => $productStats,
            'available_products' => $availableProducts
        ],
        'filters' => [
            'month' => $monthFilter,
            'product' => $productFilter,
            'applied' => !empty($monthFilter) || !empty($productFilter)
        ],
        'user' => $currentUser,
        'permissions' => [
            'can_view_all' => $canViewAll
        ],
        'debug_info' => [
            'total_records_found' => count($salesRecords),
            'where_clause' => $whereClause,
            'params_count' => count($params),
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
        'line' => __LINE__,
        'filters' => [
            'month' => $_GET['month'] ?? null,
            'product' => $_GET['product'] ?? null
        ]
    ], JSON_PRETTY_PRINT);
}
?>