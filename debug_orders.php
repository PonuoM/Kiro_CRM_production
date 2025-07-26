<?php
/**
 * Debug Orders - Check actual data in tables
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set test session
$_SESSION['user_id'] = 7;
$_SESSION['username'] = 'sales01';
$_SESSION['user_role'] = 'Sales';

require_once 'config/database.php';
require_once 'includes/permissions.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>🔍 Orders Debug</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        .table td, .table th { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
        .table th { background: #f2f2f2; }
        .section { margin: 30px 0; }
        .data { background: #f9f9f9; padding: 10px; margin: 10px 0; }
        .highlight { background: #ffffcc; }
    </style>
</head>
<body>

<h1>🔍 Orders & Customers Debug</h1>

<?php
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $currentUser = Permissions::getCurrentUser();
    $canViewAll = Permissions::canViewAllData();
    
    echo "<div class='data'>";
    echo "<h3>Session Info:</h3>";
    echo "<p><strong>Current User:</strong> {$currentUser}</p>";
    echo "<p><strong>Can View All:</strong> " . ($canViewAll ? 'YES' : 'NO') . "</p>";
    echo "</div>";
    
    // Check orders table
    echo "<div class='section'>";
    echo "<h2>📋 Orders Table (All Records)</h2>";
    
    $ordersQuery = "SELECT id, DocumentNo, CustomerCode, DocumentDate, Price, CreatedBy, Products FROM orders ORDER BY DocumentDate DESC";
    $stmt = $pdo->query($ordersQuery);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total Orders:</strong> " . count($orders) . "</p>";
    
    if (!empty($orders)) {
        echo "<table class='table'>";
        echo "<thead><tr><th>ID</th><th>DocumentNo</th><th>CustomerCode</th><th>DocumentDate</th><th>Price</th><th>CreatedBy</th><th>Products</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($orders as $order) {
            $highlight = ($order['CreatedBy'] == $currentUser) ? 'highlight' : '';
            echo "<tr class='{$highlight}'>";
            echo "<td>" . htmlspecialchars($order['id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['DocumentNo']) . "</td>";
            echo "<td>" . htmlspecialchars($order['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($order['DocumentDate']) . "</td>";
            echo "<td>" . number_format($order['Price'], 2) . " ฿</td>";
            echo "<td>" . htmlspecialchars($order['CreatedBy']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($order['Products'], 0, 30)) . "...</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    echo "</div>";
    
    // Check customers table  
    echo "<div class='section'>";
    echo "<h2>👥 Customers Table (Sales Assignment)</h2>";
    
    $customersQuery = "SELECT CustomerCode, CustomerName, CustomerTel, Sales FROM customers ORDER BY CustomerCode";
    $stmt = $pdo->query($customersQuery);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total Customers:</strong> " . count($customers) . "</p>";
    
    if (!empty($customers)) {
        echo "<table class='table'>";
        echo "<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>CustomerTel</th><th>Sales</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($customers as $customer) {
            $highlight = ($customer['Sales'] == $currentUser) ? 'highlight' : '';
            echo "<tr class='{$highlight}'>";
            echo "<td>" . htmlspecialchars($customer['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['CustomerTel']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['Sales']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    echo "</div>";
    
    // Check what user can see
    echo "<div class='section'>";
    echo "<h2>🔗 What User '{$currentUser}' Can See</h2>";
    
    // Permission logic
    $baseWhere = '';
    $baseParams = [];
    
    if (!$canViewAll) {
        $baseWhere = " AND (o.CreatedBy = ? OR c.Sales = ?)";
        $baseParams = [$currentUser, $currentUser];
    }
    
    echo "<div class='data'>";
    echo "<h4>Permission Rules:</h4>";
    echo "<p><strong>Can View All:</strong> " . ($canViewAll ? 'YES' : 'NO') . "</p>";
    echo "<p><strong>WHERE Clause:</strong> " . ($baseWhere ?: 'No restrictions (admin)') . "</p>";
    echo "<p><strong>Parameters:</strong> [" . implode(', ', $baseParams) . "]</p>";
    echo "</div>";
    
    $visibleQuery = "SELECT 
                        o.id as OrderID,
                        o.DocumentDate as OrderDate,
                        o.DocumentNo as OrderNumber,
                        o.Price as TotalAmount,
                        o.CreatedBy as SalesBy,
                        c.CustomerCode,
                        c.CustomerName,
                        c.CustomerTel,
                        c.Sales as AssignedSales,
                        o.Products
                       FROM orders o
                       LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                       WHERE 1=1 {$baseWhere}
                       ORDER BY o.DocumentDate DESC";
    
    $stmt = $pdo->prepare($visibleQuery);
    $stmt->execute($baseParams);
    $visibleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Records User Can See:</strong> " . count($visibleData) . "</p>";
    
    if (!empty($visibleData)) {
        echo "<table class='table'>";
        echo "<thead><tr>";
        echo "<th>OrderID</th><th>Date</th><th>OrderNumber</th><th>Amount</th>";
        echo "<th>CustomerCode</th><th>CustomerName</th><th>Tel</th>";
        echo "<th>CreatedBy</th><th>AssignedTo</th><th>Reason</th>";
        echo "</tr></thead>";
        echo "<tbody>";
        
        foreach ($visibleData as $row) {
            $reason = '';
            if ($row['SalesBy'] == $currentUser) $reason .= 'Created by user ';
            if ($row['AssignedSales'] == $currentUser) $reason .= 'Assigned to user ';
            if ($canViewAll) $reason = 'Admin can see all';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['OrderID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['OrderDate']) . "</td>";
            echo "<td>" . htmlspecialchars($row['OrderNumber']) . "</td>";
            echo "<td>" . number_format($row['TotalAmount'], 2) . " ฿</td>";
            echo "<td>" . htmlspecialchars($row['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['CustomerTel']) . "</td>";
            echo "<td>" . htmlspecialchars($row['SalesBy']) . "</td>";
            echo "<td>" . htmlspecialchars($row['AssignedSales']) . "</td>";
            echo "<td><small>{$reason}</small></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red;background:#ffe6e6;padding:10px;margin:10px 0'>";
    echo "<h3>❌ Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<div class="data">
<h3>💡 Analysis:</h3>
<p><strong>Highlighted rows (yellow)</strong> = Records the current user should be able to see</p>
<p><strong>Check</strong> if the "Records User Can See" matches what appears in the actual page</p>
</div>

</body>
</html>