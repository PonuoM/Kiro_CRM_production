<?php
/**
 * Test Script for Sales Records Fix
 * ทดสอบการแก้ไขปัญหาการแสดงผลรายการขายตาม user
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set test session for different users
$test_users = [
    ['id' => 7, 'username' => 'sales01', 'role' => 'Sales'],
    ['id' => 8, 'username' => 'sales02', 'role' => 'Sales'],
    ['id' => 1, 'username' => 'admin', 'role' => 'Admin']
];

echo "<!DOCTYPE html>\n<html><head><title>🧪 Sales Records Test</title>";
echo "<style>body{font-family:Arial;margin:20px;} .user{border:1px solid #ddd;margin:20px 0;padding:15px;} .table{border-collapse:collapse;width:100%;} .table td,.table th{border:1px solid #ddd;padding:8px;} .table th{background:#f2f2f2;} .highlight{background:#ffffcc;} .error{color:red;} .success{color:green;}</style>";
echo "</head><body>";

echo "<h1>🧪 Sales Records Fix Test</h1>";
echo "<p>ทดสอบการแก้ไขปัญหาการแสดงผลรายการขายตาม user</p>";

require_once 'config/database.php';
require_once 'includes/permissions.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check total orders in database
    $totalQuery = "SELECT COUNT(*) as total, COUNT(DISTINCT OrderBy) as unique_users FROM orders";
    $stmt = $pdo->query($totalQuery);
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='user highlight'>";
    echo "<h3>📊 Database Summary</h3>";
    echo "<p><strong>Total Orders:</strong> {$totals['total']}</p>";
    echo "<p><strong>Unique OrderBy Users:</strong> {$totals['unique_users']}</p>";
    echo "</div>";
    
    // Test each user
    foreach ($test_users as $user) {
        echo "<div class='user'>";
        echo "<h3>👤 Test User: {$user['username']} ({$user['role']})</h3>";
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        $currentUser = Permissions::getCurrentUser();
        $canViewAll = Permissions::canViewAllData();
        
        echo "<p><strong>Current User:</strong> {$currentUser}</p>";
        echo "<p><strong>Can View All:</strong> " . ($canViewAll ? 'YES' : 'NO') . "</p>";
        
        // Base query for sales records with permissions
        $baseWhere = '';
        $baseParams = [];
        
        if (!$canViewAll) {
            $baseWhere = " AND (o.OrderBy = ? OR c.Sales = ?)";
            $baseParams = [$currentUser, $currentUser];
        }
        
        // Get sales records using the fixed query
        $salesQuery = "SELECT 
                        o.id as OrderID,
                        o.DocumentDate as OrderDate,
                        o.DocumentNo as OrderNumber,
                        o.Price as TotalAmount,
                        COALESCE(o.OrderBy, o.CreatedBy) as SalesBy,
                        c.CustomerCode,
                        c.CustomerName,
                        c.Sales as AssignedSales
                       FROM orders o
                       LEFT JOIN customers c ON o.CustomerCode = c.CustomerCode
                       WHERE 1=1 {$baseWhere}
                       ORDER BY o.DocumentDate DESC";
        
        $stmt = $pdo->prepare($salesQuery);
        $stmt->execute($baseParams);
        $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Records Found:</strong> " . count($salesData) . "</p>";
        
        if (count($salesData) > 0) {
            echo "<table class='table'>";
            echo "<tr><th>Order ID</th><th>Date</th><th>Document No</th><th>Amount</th><th>Sales By</th><th>Customer</th><th>Assigned Sales</th></tr>";
            
            foreach ($salesData as $row) {
                $highlight = ($row['SalesBy'] === $currentUser || $row['AssignedSales'] === $currentUser) ? 'highlight' : '';
                echo "<tr class='{$highlight}'>";
                echo "<td>{$row['OrderID']}</td>";
                echo "<td>" . date('d/m/Y', strtotime($row['OrderDate'])) . "</td>";
                echo "<td>{$row['OrderNumber']}</td>";
                echo "<td>฿" . number_format($row['TotalAmount'], 2) . "</td>";
                echo "<td><strong>{$row['SalesBy']}</strong></td>";
                echo "<td>{$row['CustomerName']}</td>";
                echo "<td>{$row['AssignedSales']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No records found for this user</p>";
        }
        
        echo "</div>";
    }
    
    // Test API endpoint
    echo "<div class='user highlight'>";
    echo "<h3>🔗 API Test Links</h3>";
    echo "<p>คุณสามารถทดสอบ API ได้ที่:</p>";
    echo "<ul>";
    echo "<li><a href='api/sales/sales_records_fixed.php' target='_blank'>Fixed Sales Records API</a></li>";
    echo "<li><a href='api/sales/sales_records.php' target='_blank'>Original Sales Records API</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div style='margin-top: 30px; padding: 15px; background: #e8f5e8; border-radius: 5px;'>";
echo "<h3>✅ Fix Summary</h3>";
echo "<p><strong>Changes Made:</strong></p>";
echo "<ul>";
echo "<li>แก้ไข <code>o.CreatedBy</code> เป็น <code>o.OrderBy</code> ใน WHERE condition</li>";
echo "<li>เพิ่ม <code>COALESCE(o.OrderBy, o.CreatedBy)</code> เพื่อความยืดหยุ่น</li>";
echo "<li>แก้ไขทั้งใน <code>sales_records.php</code> และ <code>sales_records_fixed.php</code></li>";
echo "</ul>";
echo "<p><strong>Expected Result:</strong> แต่ละ user จะเห็นเฉพาะ orders ที่ OrderBy = username ของตน</p>";
echo "</div>";

echo "</body></html>";
?>