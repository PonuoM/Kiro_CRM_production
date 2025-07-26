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

// Check if customer code provided
$customerCode = $_GET['customer'] ?? $_GET['customer_code'] ?? '';
if (empty($customerCode)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Customer code is required'
    ]);
    exit;
}

try {
    require_once '../../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get sales history (if sales_histories table exists)
    $salesHistory = [];
    try {
        $sql = "SELECT * FROM sales_histories WHERE CustomerCode = ? ORDER BY StartDate DESC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customerCode]);
        $salesHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Sales histories table might not exist, return empty array
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $salesHistory,
        'count' => count($salesHistory),
        'message' => count($salesHistory) === 0 ? 'No sales history found' : 'Sales history loaded successfully'
    ], JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_PRETTY_PRINT);
}
?>