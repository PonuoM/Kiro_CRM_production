<?php
/**
 * Products List API Endpoint
 * Returns list of active products for dropdown selection
 */

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ'], 401);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    // First try database connection
    require_once __DIR__ . '/../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get query parameters
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $active_only = $_GET['active_only'] ?? '1';
    
    // Build SQL query
    $sql = "SELECT 
                product_code,
                product_name,
                category,
                unit,
                standard_price,
                is_active
            FROM products 
            WHERE 1=1";
    
    $params = [];
    
    // Filter by active status
    if ($active_only === '1') {
        $sql .= " AND is_active = 1";
    }
    
    // Filter by category
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    
    // Search by product name or code
    if (!empty($search)) {
        $sql .= " AND (product_name LIKE ? OR product_code LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    $sql .= " ORDER BY category, product_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get categories for dropdown
    $categorySql = "SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category";
    $categoryStmt = $pdo->prepare($categorySql);
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    
    sendJsonResponse([
        'success' => true,
        'data' => $products,
        'categories' => $categories,
        'total_count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Products list error: " . $e->getMessage());
    
    // Return mock data if database fails
    $mockProducts = [
        ['product_code' => 'F001', 'product_name' => 'ปุ๋ยเคมี 16-16-16', 'category' => 'ปุ๋ยเคมี', 'unit' => 'กก', 'standard_price' => '18.50', 'is_active' => 1],
        ['product_code' => 'F002', 'product_name' => 'ปุ๋ยเคมี 15-15-15', 'category' => 'ปุ๋ยเคมี', 'unit' => 'กก', 'standard_price' => '17.50', 'is_active' => 1],
        ['product_code' => 'F003', 'product_name' => 'ปุ๋ยยูเรีย 46-0-0', 'category' => 'ปุ๋ยเคมี', 'unit' => 'กก', 'standard_price' => '16.00', 'is_active' => 1],
        ['product_code' => 'F004', 'product_name' => 'ปุ๋ยโปแตสเซียม 0-0-50', 'category' => 'ปุ๋ยเคมี', 'unit' => 'กก', 'standard_price' => '21.00', 'is_active' => 1],
        ['product_code' => 'F005', 'product_name' => 'ปุ๋ยฟอสเฟต 16-20-0', 'category' => 'ปุ๋ยเคมี', 'unit' => 'กก', 'standard_price' => '19.00', 'is_active' => 1],
        ['product_code' => 'O001', 'product_name' => 'ปุ๋ยหมักมีกากมด', 'category' => 'ปุ๋ยอินทรีย์', 'unit' => 'กก', 'standard_price' => '45.00', 'is_active' => 1],
        ['product_code' => 'O002', 'product_name' => 'ปุ๋ยคอกแกะ', 'category' => 'ปุ๋ยอินทรีย์', 'unit' => 'กก', 'standard_price' => '55.00', 'is_active' => 1],
        ['product_code' => 'O003', 'product_name' => 'ปุ๋ยหมักชีวภาพ', 'category' => 'ปุ๋ยอินทรีย์', 'unit' => 'กก', 'standard_price' => '65.00', 'is_active' => 1],
        ['product_code' => 'O004', 'product_name' => 'ปุ๋ยกากถั่วเหลือง', 'category' => 'ปุ๋ยอินทรีย์', 'unit' => 'กก', 'standard_price' => '35.00', 'is_active' => 1],
        ['product_code' => 'O005', 'product_name' => 'ปุ๋ยมูลไก', 'category' => 'ปุ๋ยอินทรีย์', 'unit' => 'กก', 'standard_price' => '25.00', 'is_active' => 1],
        ['product_code' => 'B001', 'product_name' => 'ปุ๋ยจุลินทรีย์ชนิดผง', 'category' => 'ผลิตภัณฑ์ชีวภาพ', 'unit' => 'กก', 'standard_price' => '150.00', 'is_active' => 1],
        ['product_code' => 'B002', 'product_name' => 'ปุ๋ยจุลินทรีย์ชนิดเหลว', 'category' => 'ผลิตภัณฑ์ชีวภาพ', 'unit' => 'ลิตร', 'standard_price' => '120.00', 'is_active' => 1],
        ['product_code' => 'B003', 'product_name' => 'เอนไซม์ย่อยสลายดิน', 'category' => 'ผลิตภัณฑ์ชีวภาพ', 'unit' => 'กก', 'standard_price' => '180.00', 'is_active' => 1],
        ['product_code' => 'B004', 'product_name' => 'ฮิวมิคแอซิด', 'category' => 'ผลิตภัณฑ์ชีวภาพ', 'unit' => 'กก', 'standard_price' => '220.00', 'is_active' => 1],
        ['product_code' => 'B005', 'product_name' => 'แบคทีเรียตรึงไนโตรเจน', 'category' => 'ผลิตภัณฑ์ชีวภาพ', 'unit' => 'กก', 'standard_price' => '200.00', 'is_active' => 1],
        ['product_code' => 'G001', 'product_name' => 'เสื้อโปโล Company', 'category' => 'ของแถม', 'unit' => 'ตัว', 'standard_price' => '0.00', 'is_active' => 1],
        ['product_code' => 'G002', 'product_name' => 'หมวก Company', 'category' => 'ของแถม', 'unit' => 'ใบ', 'standard_price' => '0.00', 'is_active' => 1],
        ['product_code' => 'G003', 'product_name' => 'ปากกา Company', 'category' => 'ของแถม', 'unit' => 'ด้าม', 'standard_price' => '0.00', 'is_active' => 1],
        ['product_code' => 'G004', 'product_name' => 'แก้วน้ำ Company', 'category' => 'ของแถม', 'unit' => 'ใบ', 'standard_price' => '0.00', 'is_active' => 1],
        ['product_code' => 'G005', 'product_name' => 'พวงกุญแจ Company', 'category' => 'ของแถม', 'unit' => 'อัน', 'standard_price' => '0.00', 'is_active' => 1],
        ['product_code' => 'G006', 'product_name' => 'ถุงผ้า Company', 'category' => 'ของแถม', 'unit' => 'ใบ', 'standard_price' => '0.00', 'is_active' => 1]
    ];
    
    $categories = ['ปุ๋ยเคมี', 'ปุ๋ยอินทรีย์', 'ผลิตภัณฑ์ชีวภาพ', 'ของแถม'];
    
    // Apply filters to mock data
    $filteredProducts = $mockProducts;
    
    if (!empty($category)) {
        $filteredProducts = array_filter($filteredProducts, function($product) use ($category) {
            return $product['category'] === $category;
        });
    }
    
    if (!empty($search)) {
        $filteredProducts = array_filter($filteredProducts, function($product) use ($search) {
            return stripos($product['product_name'], $search) !== false || 
                   stripos($product['product_code'], $search) !== false;
        });
    }
    
    sendJsonResponse([
        'success' => true,
        'data' => array_values($filteredProducts),
        'categories' => $categories,
        'total_count' => count($filteredProducts),
        'note' => 'Using mock data - database connection failed'
    ]);
}
?>