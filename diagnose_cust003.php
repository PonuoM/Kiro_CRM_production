<?php
/**
 * Diagnose CUST003 Issue
 * ตรวจสอบปัญหาเฉพาะ CUST003
 */

// Security check
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'kiro_debug_2024') {
    http_response_code(403);
    die("Access Denied");
}

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $result = [
        'timestamp' => date('Y-m-d H:i:s'),
        'customer_data' => null,
        'orders_count' => 0,
        'orders_data' => [],
        'totals' => [],
        'diagnosis' => []
    ];
    
    // 1. Get customer data
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE CustomerCode = ?");
    $stmt->execute(['CUST003']);
    $result['customer_data'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Get all orders for CUST003
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE CustomerCode = ? ORDER BY DocumentDate DESC");
    $stmt->execute(['CUST003']);
    $result['orders_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['orders_count'] = count($result['orders_data']);
    
    // 3. Calculate totals from different fields
    $result['totals']['price_sum'] = 0;
    $result['totals']['totalamount_sum'] = 0;
    $result['totals']['valid_price_orders'] = 0;
    $result['totals']['valid_totalamount_orders'] = 0;
    
    foreach ($result['orders_data'] as $order) {
        if ($order['Price'] !== null && $order['Price'] > 0) {
            $result['totals']['price_sum'] += $order['Price'];
            $result['totals']['valid_price_orders']++;
        }
        if ($order['TotalAmount'] !== null && $order['TotalAmount'] > 0) {
            $result['totals']['totalamount_sum'] += $order['TotalAmount'];
            $result['totals']['valid_totalamount_orders']++;
        }
    }
    
    // 4. Test the SQL calculation directly
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(Price), 0) as calculated_total 
        FROM orders 
        WHERE CustomerCode = ? 
        AND Price IS NOT NULL 
        AND Price > 0
    ");
    $stmt->execute(['CUST003']);
    $calc_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $result['totals']['sql_calculated'] = $calc_result['calculated_total'];
    
    // 5. Diagnosis
    if ($result['orders_count'] == 0) {
        $result['diagnosis'][] = "No orders found for CUST003";
    } else {
        $result['diagnosis'][] = "Found {$result['orders_count']} orders";
    }
    
    if ($result['totals']['price_sum'] >= 810000) {
        $result['diagnosis'][] = "Price sum (" . number_format($result['totals']['price_sum'], 2) . ") qualifies for Grade A";
    } else {
        $result['diagnosis'][] = "Price sum (" . number_format($result['totals']['price_sum'], 2) . ") does NOT qualify for Grade A (need ≥810,000)";
    }
    
    if ($result['totals']['totalamount_sum'] >= 810000) {
        $result['diagnosis'][] = "TotalAmount sum (" . number_format($result['totals']['totalamount_sum'], 2) . ") qualifies for Grade A";
    } else {
        $result['diagnosis'][] = "TotalAmount sum (" . number_format($result['totals']['totalamount_sum'], 2) . ") does NOT qualify for Grade A";
    }
    
    if ($result['customer_data']) {
        $current_total = $result['customer_data']['TotalPurchase'];
        if ($current_total != $result['totals']['sql_calculated']) {
            $result['diagnosis'][] = "TotalPurchase mismatch: stored=" . number_format($current_total, 2) . ", calculated=" . number_format($result['totals']['sql_calculated'], 2);
        } else {
            $result['diagnosis'][] = "TotalPurchase matches calculated value";
        }
    }
    
    // 6. Check for data issue
    if ($result['totals']['price_sum'] < 100000 && $result['orders_count'] > 0) {
        $result['diagnosis'][] = "POSSIBLE ISSUE: Low total despite having orders - check data quality";
    }
    
    // Format numbers for display
    $result['totals']['price_sum_formatted'] = number_format($result['totals']['price_sum'], 2);
    $result['totals']['totalamount_sum_formatted'] = number_format($result['totals']['totalamount_sum'], 2);
    $result['totals']['sql_calculated_formatted'] = number_format($result['totals']['sql_calculated'], 2);
    
    if ($result['customer_data']) {
        $result['customer_data']['TotalPurchase_formatted'] = number_format($result['customer_data']['TotalPurchase'], 2);
    }
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>