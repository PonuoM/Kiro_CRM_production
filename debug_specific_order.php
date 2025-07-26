<?php
/**
 * Debug Specific Order - ตรวจสอบ order TEST-ORD-003
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<!DOCTYPE html>\n<html><head><title>🔍 Debug Order TEST-ORD-003</title>";
echo "<style>body{font-family:Arial;margin:20px;} .table{border-collapse:collapse;width:100%;} .table td,.table th{border:1px solid #ddd;padding:8px;} .table th{background:#f2f2f2;} .highlight{background:#ffffcc;} .error{color:red;}</style>";
echo "</head><body>";

echo "<h1>🔍 Debug Order: TEST-ORD-003</h1>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // ตรวจสอบ order TEST-ORD-003 โดยตรง
    echo "<h2>📋 Order Details from Database</h2>";
    $orderQuery = "SELECT * FROM orders WHERE DocumentNo = 'TEST-ORD-003'";
    $stmt = $pdo->query($orderQuery);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<table class='table'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($order as $field => $value) {
            $highlight = ($field === 'OrderBy' || $field === 'CreatedBy') ? 'highlight' : '';
            echo "<tr class='{$highlight}'>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$value}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Order TEST-ORD-003 not found!</p>";
    }
    
    // ตรวจสอบ customer ที่เกี่ยวข้อง
    if ($order && $order['CustomerCode']) {
        echo "<h2>👥 Customer Details</h2>";
        $customerQuery = "SELECT CustomerCode, CustomerName, CustomerTel, Sales FROM customers WHERE CustomerCode = ?";
        $stmt = $pdo->prepare($customerQuery);
        $stmt->execute([$order['CustomerCode']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo "<table class='table'>";
            foreach ($customer as $field => $value) {
                $highlight = ($field === 'Sales') ? 'highlight' : '';
                echo "<tr class='{$highlight}'>";
                echo "<td><strong>{$field}</strong></td>";
                echo "<td>{$value}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // แสดงผลการ JOIN แบบเดียวกับ API
    echo "<h2>🔗 JOIN Query Result (Like API)</h2>";
    $joinQuery = "SELECT 
                    o.id as OrderID,
                    o.DocumentDate as OrderDate,
                    o.DocumentNo as OrderNumber,
                    o.Price as TotalAmount,
                    COALESCE(o.OrderBy, o.CreatedBy) as SalesBy,
                    o.OrderBy as OriginalOrderBy,
                    o.CreatedBy as OriginalCreatedBy,
                    c.CustomerCode,
                    c.CustomerName,
                    c.CustomerTel,
                    c.Sales as AssignedSales
                   FROM orders o
                   LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                   WHERE o.DocumentNo = 'TEST-ORD-003'";
    
    $stmt = $pdo->query($joinQuery);
    $joinResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($joinResult) {
        echo "<table class='table'>";
        foreach ($joinResult as $field => $value) {
            $highlight = (strpos($field, 'Sales') !== false || strpos($field, 'OrderBy') !== false || strpos($field, 'CreatedBy') !== false) ? 'highlight' : '';
            echo "<tr class='{$highlight}'>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$value}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // ตรวจสอบการ filter สำหรับ sales01
    echo "<h2>🎯 Filter Test for sales01</h2>";
    $filterQuery = "SELECT 
                    o.id as OrderID,
                    o.DocumentNo as OrderNumber,
                    COALESCE(o.OrderBy, o.CreatedBy) as SalesBy,
                    c.Sales as AssignedSales,
                    CASE 
                        WHEN o.OrderBy = 'sales01' THEN 'Match OrderBy'
                        WHEN c.Sales = 'sales01' THEN 'Match AssignedSales'
                        ELSE 'No Match'
                    END as MatchReason
                   FROM orders o
                   LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                   WHERE o.DocumentNo = 'TEST-ORD-003'";
    
    $stmt = $pdo->query($filterQuery);
    $filterResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($filterResult) {
        echo "<table class='table'>";
        foreach ($filterResult as $field => $value) {
            echo "<tr>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$value}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // แสดงการ query ที่ใช้ใน API
    echo "<h2>📝 API Filter Logic Test</h2>";
    echo "<p><strong>Current Logic:</strong> <code>WHERE (o.OrderBy = 'sales01' OR c.Sales = 'sales01')</code></p>";
    
    $apiTestQuery = "SELECT 
                    o.DocumentNo,
                    o.OrderBy,
                    c.Sales as CustomerSales,
                    CASE 
                        WHEN (o.OrderBy = 'sales01' OR c.Sales = 'sales01') THEN 'INCLUDED'
                        ELSE 'EXCLUDED'
                    END as FilterResult
                   FROM orders o
                   LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                   WHERE o.DocumentNo = 'TEST-ORD-003'";
    
    $stmt = $pdo->query($apiTestQuery);
    $apiTest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($apiTest) {
        echo "<table class='table'>";
        foreach ($apiTest as $field => $value) {
            $highlight = ($field === 'FilterResult') ? 'highlight' : '';
            echo "<tr class='{$highlight}'>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$value}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($apiTest['FilterResult'] === 'INCLUDED') {
            echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>✅ Analysis:</strong> Order TEST-ORD-003 is correctly included for sales01 because:<br>";
            if ($apiTest['OrderBy'] === 'sales01') {
                echo "- OrderBy = 'sales01' ✅<br>";
            }
            if ($apiTest['CustomerSales'] === 'sales01') {
                echo "- Customer assigned to sales01 ✅<br>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>❌ Problem:</strong> Order should NOT be included for sales01<br>";
            echo "- OrderBy = '{$apiTest['OrderBy']}'<br>";
            echo "- Customer Sales = '{$apiTest['CustomerSales']}'<br>";
            echo "</div>";
        }
    }
    
    echo "<h2>🔧 Recommended Actions</h2>";
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "<p>ถ้า order นี้ไม่ควรแสดงสำหรับ sales01 ให้ตรวจสอบ:</p>";
    echo "<ul>";
    echo "<li>1. ข้อมูล OrderBy ในตาราง orders</li>";
    echo "<li>2. ข้อมูล Sales ในตาราง customers</li>";
    echo "<li>3. ตรวจสอบว่าข้อมูลถูกต้องหรือไม่</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>