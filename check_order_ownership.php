<?php
/**
 * Check Order Ownership - ตรวจสอบความเป็นเจ้าของ order
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>\n<html><head><title>🔍 Check Order Ownership</title>";
echo "<style>body{font-family:Arial;margin:20px;} .table{border-collapse:collapse;width:100%;} .table td,.table th{border:1px solid #ddd;padding:8px;} .table th{background:#f2f2f2;} .highlight{background:#ffffcc;} .success{background:#d4edda;} .warning{background:#fff3cd;}</style>";
echo "</head><body>";

echo "<h1>🔍 Check Order Ownership</h1>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // ตรวจสอบทุก orders และความสัมพันธ์กับ customer
    echo "<h2>📋 All Orders with Customer Assignment</h2>";
    $query = "SELECT 
                o.id,
                o.DocumentNo,
                o.DocumentDate,
                o.OrderBy as OrderCreatedBy,
                o.CreatedBy,
                o.CustomerCode,
                c.CustomerName,
                c.Sales as CustomerAssignedTo,
                CASE 
                    WHEN o.OrderBy IS NOT NULL THEN o.OrderBy
                    WHEN o.CreatedBy IS NOT NULL THEN o.CreatedBy
                    ELSE c.Sales
                END as DeterminedOwner,
                CASE 
                    WHEN o.OrderBy = 'sales01' OR c.Sales = 'sales01' THEN 'YES'
                    ELSE 'NO'
                END as ShowForSales01
              FROM orders o
              LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
              ORDER BY o.DocumentDate DESC";
    
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table'>";
    echo "<tr>";
    echo "<th>Order ID</th>";
    echo "<th>Document No</th>";
    echo "<th>Date</th>";
    echo "<th>OrderBy</th>";
    echo "<th>CreatedBy</th>";
    echo "<th>Customer</th>";
    echo "<th>Customer Assigned To</th>";
    echo "<th>Determined Owner</th>";
    echo "<th>Show for sales01?</th>";
    echo "</tr>";
    
    foreach ($results as $row) {
        $showClass = ($row['ShowForSales01'] === 'YES') ? 'success' : '';
        $highlightClass = ($row['DocumentNo'] === 'TEST-ORD-003') ? 'highlight' : '';
        $className = $highlightClass ?: $showClass;
        
        echo "<tr class='{$className}'>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['DocumentNo']}</strong></td>";
        echo "<td>" . date('d/m/Y', strtotime($row['DocumentDate'])) . "</td>";
        echo "<td>" . ($row['OrderCreatedBy'] ?: '-') . "</td>";
        echo "<td>" . ($row['CreatedBy'] ?: '-') . "</td>";
        echo "<td>{$row['CustomerCode']}<br><small>{$row['CustomerName']}</small></td>";
        echo "<td>" . ($row['CustomerAssignedTo'] ?: '-') . "</td>";
        echo "<td><strong>{$row['DeterminedOwner']}</strong></td>";
        echo "<td>";
        if ($row['ShowForSales01'] === 'YES') {
            echo "<span style='color: green; font-weight: bold;'>✅ YES</span>";
        } else {
            echo "<span style='color: red;'>❌ NO</span>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // สรุปสำหรับ sales01
    echo "<h2>📊 Summary for sales01</h2>";
    $sales01Orders = array_filter($results, function($row) {
        return $row['ShowForSales01'] === 'YES';
    });
    
    echo "<div class='success' style='padding: 15px; border-radius: 5px;'>";
    echo "<h3>Orders that should show for sales01:</h3>";
    echo "<p><strong>Total:</strong> " . count($sales01Orders) . " orders</p>";
    echo "<ul>";
    foreach ($sales01Orders as $order) {
        $reason = '';
        if ($order['OrderCreatedBy'] === 'sales01') {
            $reason = ' (OrderBy = sales01)';
        } elseif ($order['CustomerAssignedTo'] === 'sales01') {
            $reason = ' (Customer assigned to sales01)';
        }
        echo "<li><strong>{$order['DocumentNo']}</strong> - Owner: {$order['DeterminedOwner']}{$reason}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // ตรวจสอบเฉพาะ TEST-ORD-003
    $testOrder = array_filter($results, function($row) {
        return $row['DocumentNo'] === 'TEST-ORD-003';
    });
    
    if (!empty($testOrder)) {
        $testOrder = array_values($testOrder)[0];
        echo "<h2>🎯 Specific Analysis: TEST-ORD-003</h2>";
        echo "<div class='warning' style='padding: 15px; border-radius: 5px;'>";
        echo "<h3>TEST-ORD-003 Analysis:</h3>";
        echo "<ul>";
        echo "<li><strong>OrderBy:</strong> " . ($testOrder['OrderCreatedBy'] ?: 'NULL') . "</li>";
        echo "<li><strong>CreatedBy:</strong> " . ($testOrder['CreatedBy'] ?: 'NULL') . "</li>";
        echo "<li><strong>Customer Assigned To:</strong> " . ($testOrder['CustomerAssignedTo'] ?: 'NULL') . "</li>";
        echo "<li><strong>Determined Owner:</strong> {$testOrder['DeterminedOwner']}</li>";
        echo "<li><strong>Should show for sales01:</strong> " . ($testOrder['ShowForSales01'] === 'YES' ? '✅ YES' : '❌ NO') . "</li>";
        echo "</ul>";
        
        if ($testOrder['ShowForSales01'] === 'YES') {
            echo "<p><strong>✅ Conclusion:</strong> Order TEST-ORD-003 correctly appears in sales01's list because:</p>";
            if ($testOrder['OrderCreatedBy'] === 'sales01') {
                echo "<p>• OrderBy field = 'sales01'</p>";
            }
            if ($testOrder['CustomerAssignedTo'] === 'sales01') {
                echo "<p>• Customer is assigned to sales01</p>";
            }
        } else {
            echo "<p><strong>❌ Problem:</strong> Order TEST-ORD-003 should NOT appear in sales01's list</p>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 15px; background: #f8d7da; border-radius: 5px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>