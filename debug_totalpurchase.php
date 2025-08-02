<?php
/**
 * Debug TotalPurchase Calculation Issue
 * ตรวจสอบปัญหาการคำนวณ TotalPurchase
 */

// Security check
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'kiro_debug_2024') {
    http_response_code(403);
    die("Access Denied");
}

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Debug TotalPurchase</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.error{color:red;}.success{color:green;}.warning{color:orange;}</style>";
echo "</head><body>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔍 Debug TotalPurchase Calculation</h2>";
    echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
    
    // 1. Check orders table structure
    echo "<h3>📋 Orders Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>" . ($col['Default'] ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    // 2. Check sample orders data
    echo "<h3>📊 Sample Orders Data</h3>";
    $stmt = $pdo->query("SELECT CustomerCode, OrderCode, DocumentDate, Price, TotalAmount, OrderStatus FROM orders LIMIT 10");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "<p class='error'>❌ No orders found in database!</p>";
    } else {
        echo "<table><tr><th>CustomerCode</th><th>OrderCode</th><th>DocumentDate</th><th>Price</th><th>TotalAmount</th><th>Status</th></tr>";
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['CustomerCode']}</td>";
            echo "<td>{$order['OrderCode']}</td>";
            echo "<td>{$order['DocumentDate']}</td>";
            echo "<td>฿" . number_format($order['Price'] ?? 0, 2) . "</td>";
            echo "<td>฿" . number_format($order['TotalAmount'] ?? 0, 2) . "</td>";
            echo "<td>{$order['OrderStatus']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Check CUST003 specifically
    echo "<h3>🎯 CUST003 Orders Analysis</h3>";
    $stmt = $pdo->prepare("SELECT OrderCode, DocumentDate, Price, TotalAmount, OrderStatus FROM orders WHERE CustomerCode = ? ORDER BY DocumentDate DESC");
    $stmt->execute(['CUST003']);
    $cust003Orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cust003Orders)) {
        echo "<p class='error'>❌ No orders found for CUST003!</p>";
    } else {
        echo "<table><tr><th>OrderCode</th><th>DocumentDate</th><th>Price</th><th>TotalAmount</th><th>Status</th></tr>";
        $totalPrice = 0;
        $totalAmount = 0;
        foreach ($cust003Orders as $order) {
            echo "<tr>";
            echo "<td>{$order['OrderCode']}</td>";
            echo "<td>{$order['DocumentDate']}</td>";
            echo "<td>฿" . number_format($order['Price'] ?? 0, 2) . "</td>";
            echo "<td>฿" . number_format($order['TotalAmount'] ?? 0, 2) . "</td>";
            echo "<td>{$order['OrderStatus']}</td>";
            echo "</tr>";
            $totalPrice += $order['Price'] ?? 0;
            $totalAmount += $order['TotalAmount'] ?? 0;
        }
        echo "<tr style='background-color:#f9f9f9;font-weight:bold;'>";
        echo "<td>TOTAL</td><td>-</td>";
        echo "<td>฿" . number_format($totalPrice, 2) . "</td>";
        echo "<td>฿" . number_format($totalAmount, 2) . "</td>";
        echo "<td>-</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<div style='margin:20px 0;padding:15px;background-color:#e8f4fd;border:1px solid #bee5eb;'>";
        echo "<h4>💡 Analysis</h4>";
        echo "<p><strong>Orders found:</strong> " . count($cust003Orders) . " orders</p>";
        echo "<p><strong>Total from Price:</strong> ฿" . number_format($totalPrice, 2) . "</p>";
        echo "<p><strong>Total from TotalAmount:</strong> ฿" . number_format($totalAmount, 2) . "</p>";
        echo "<p><strong>Expected:</strong> ฿904,891.17 (from requirements)</p>";
        if ($totalPrice >= 810000) {
            echo "<p class='success'>✅ Should be Grade A (≥฿810,000)</p>";
        } else {
            echo "<p class='warning'>⚠️ Current total doesn't meet Grade A criteria</p>";
        }
        echo "</div>";
    }
    
    // 4. Check current customer data
    echo "<h3>👤 Current Customer Data</h3>";
    $stmt = $pdo->prepare("SELECT CustomerCode, CustomerName, TotalPurchase, CustomerGrade, CustomerTemperature FROM customers WHERE CustomerCode = ?");
    $stmt->execute(['CUST003']);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "<table><tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>CustomerCode</td><td>{$customer['CustomerCode']}</td></tr>";
        echo "<tr><td>CustomerName</td><td>{$customer['CustomerName']}</td></tr>";
        echo "<tr><td>TotalPurchase</td><td>฿" . number_format($customer['TotalPurchase'], 2) . "</td></tr>";
        echo "<tr><td>CustomerGrade</td><td>{$customer['CustomerGrade']}</td></tr>";
        echo "<tr><td>CustomerTemperature</td><td>{$customer['CustomerTemperature']}</td></tr>";
        echo "</table>";
    }
    
    // 5. Test the SQL query that's failing
    echo "<h3>🧪 Test TotalPurchase Update Query</h3>";
    try {
        $testSql = "
            SELECT 
                c.CustomerCode,
                c.TotalPurchase as current_total,
                COALESCE((
                    SELECT SUM(o.Price) 
                    FROM orders o 
                    WHERE o.CustomerCode = c.CustomerCode 
                    AND o.Price IS NOT NULL
                    AND o.Price > 0
                ), 0) as calculated_total
            FROM customers c 
            WHERE c.CustomerCode = 'CUST003'
        ";
        
        $stmt = $pdo->query($testSql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<table><tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>CustomerCode</td><td>{$result['CustomerCode']}</td></tr>";
            echo "<tr><td>Current Total</td><td>฿" . number_format($result['current_total'], 2) . "</td></tr>";
            echo "<tr><td>Calculated Total</td><td>฿" . number_format($result['calculated_total'], 2) . "</td></tr>";
            echo "</table>";
            
            if ($result['current_total'] != $result['calculated_total']) {
                echo "<p class='warning'>⚠️ Mismatch detected! Update needed.</p>";
            } else {
                echo "<p class='success'>✅ Values match</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Query test failed: " . $e->getMessage() . "</p>";
    }
    
    // 6. Check if there are any orders at all
    echo "<h3>📈 Overall Statistics</h3>";
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE Price IS NOT NULL AND Price > 0");
    $stats['valid_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT CustomerCode) as count FROM orders WHERE Price IS NOT NULL AND Price > 0");
    $stats['customers_with_orders'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = $stmt->fetchColumn();
    
    echo "<table><tr><th>Metric</th><th>Value</th></tr>";
    foreach ($stats as $key => $value) {
        echo "<tr><td>" . ucwords(str_replace('_', ' ', $key)) . "</td><td>{$value}</td></tr>";
    }
    echo "</table>";
    
    if ($stats['total_orders'] == 0) {
        echo "<div style='margin:20px 0;padding:15px;background-color:#f8d7da;border:1px solid #f5c6cb;'>";
        echo "<h4>🚨 Critical Issue</h4>";
        echo "<p>No orders found in database! This explains why TotalPurchase updates are failing.</p>";
        echo "<p>Solution: Check if orders table has data or if there's a different table name.</p>";
        echo "</div>";
    } elseif ($stats['valid_orders'] == 0) {
        echo "<div style='margin:20px 0;padding:15px;background-color:#fff3cd;border:1px solid #ffeaa7;'>";
        echo "<h4>⚠️ Data Issue</h4>";
        echo "<p>Orders exist but no valid Price values found!</p>";
        echo "<p>Check if Price column has correct data or if a different field should be used.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>💥 ERROR: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>