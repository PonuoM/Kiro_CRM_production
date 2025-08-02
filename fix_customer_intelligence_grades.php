<?php
/**
 * Fix Customer Intelligence Grades
 * แก้ไข Grade calculation และ sync TotalPurchase จาก orders table
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 แก้ไข Customer Intelligence Grades</h2>\n";
    echo "<p>กำลังดำเนินการ...</p>\n";
    flush();
    
    // 1. Update TotalPurchase จาก orders table
    echo "<h3>1. อัปเดต TotalPurchase จาก orders table</h3>\n";
    
    $updateTotalSql = "
        UPDATE customers c
        SET TotalPurchase = COALESCE((
            SELECT SUM(TotalAmount) 
            FROM orders o 
            WHERE o.CustomerCode = c.CustomerCode 
            AND o.OrderStatus IN ('completed', 'paid', 'pending')
        ), 0),
        LastPurchaseDate = (
            SELECT MAX(OrderDate) 
            FROM orders o 
            WHERE o.CustomerCode = c.CustomerCode
        )
        WHERE c.CustomerCode IS NOT NULL
    ";
    
    $updateStmt = $pdo->prepare($updateTotalSql);
    $updateStmt->execute();
    $affectedRows = $updateStmt->rowCount();
    
    echo "<p>✅ อัปเดต TotalPurchase สำหรับ {$affectedRows} ลูกค้า</p>\n";
    flush();
    
    // 2. อัปเดต Grade ทุกลูกค้า
    echo "<h3>2. อัปเดต Customer Grade</h3>\n";
    
    $gradeUpdateSql = "
        UPDATE customers 
        SET 
            CustomerGrade = CASE 
                WHEN TotalPurchase >= 10000 THEN 'A'
                WHEN TotalPurchase >= 5000 THEN 'B'
                WHEN TotalPurchase >= 2000 THEN 'C'
                ELSE 'D'
            END,
            GradeCalculatedDate = NOW()
        WHERE CustomerCode IS NOT NULL
    ";
    
    $gradeStmt = $pdo->prepare($gradeUpdateSql);
    $gradeStmt->execute();
    $gradeRows = $gradeStmt->rowCount();
    
    echo "<p>✅ อัปเดต Grade สำหรับ {$gradeRows} ลูกค้า</p>\n";
    flush();
    
    // 3. ตรวจสอบ CUST003
    echo "<h3>3. ตรวจสอบ CUST003</h3>\n";
    
    $cust003Sql = "SELECT CustomerCode, CustomerName, TotalPurchase, CustomerGrade, GradeCalculatedDate 
                   FROM customers WHERE CustomerCode = 'CUST003'";
    $cust003Stmt = $pdo->prepare($cust003Sql);
    $cust003Stmt->execute();
    $cust003 = $cust003Stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cust003) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Field</th><th>Value</th></tr>\n";
        echo "<tr><td>CustomerCode</td><td>{$cust003['CustomerCode']}</td></tr>\n";
        echo "<tr><td>CustomerName</td><td>{$cust003['CustomerName']}</td></tr>\n";
        echo "<tr><td>TotalPurchase</td><td>฿" . number_format($cust003['TotalPurchase'], 2) . "</td></tr>\n";
        echo "<tr><td>CustomerGrade</td><td><strong>{$cust003['CustomerGrade']}</strong></td></tr>\n";
        echo "<tr><td>GradeCalculatedDate</td><td>{$cust003['GradeCalculatedDate']}</td></tr>\n";
        echo "</table>\n";
        
        if ($cust003['CustomerGrade'] === 'A') {
            echo "<p style='color: green;'>🎉 <strong>สำเร็จ!</strong> CUST003 ได้ Grade A แล้ว</p>\n";
        } else {
            echo "<p style='color: red;'>❌ <strong>ยังมีปัญหา</strong> CUST003 ยังไม่ได้ Grade A</p>\n";
        }
    }
    flush();
    
    // 4. แสดง Grade distribution
    echo "<h3>4. Grade Distribution</h3>\n";
    
    $distSql = "SELECT CustomerGrade, COUNT(*) as count, 
                       MIN(TotalPurchase) as min_purchase,
                       MAX(TotalPurchase) as max_purchase,
                       AVG(TotalPurchase) as avg_purchase
                FROM customers 
                WHERE CustomerGrade IS NOT NULL
                GROUP BY CustomerGrade 
                ORDER BY CustomerGrade";
    
    $distStmt = $pdo->prepare($distSql);
    $distStmt->execute();
    $distribution = $distStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Grade</th><th>Count</th><th>Min Purchase</th><th>Max Purchase</th><th>Avg Purchase</th></tr>\n";
    
    foreach ($distribution as $grade) {
        echo "<tr>\n";
        echo "<td><strong>{$grade['CustomerGrade']}</strong></td>\n";
        echo "<td>{$grade['count']}</td>\n";
        echo "<td>฿" . number_format($grade['min_purchase'], 2) . "</td>\n";
        echo "<td>฿" . number_format($grade['max_purchase'], 2) . "</td>\n";
        echo "<td>฿" . number_format($grade['avg_purchase'], 2) . "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 5. แสดง Grade A customers
    echo "<h3>5. Grade A Customers (Top 10)</h3>\n";
    
    $gradeASql = "SELECT CustomerCode, CustomerName, TotalPurchase, GradeCalculatedDate
                  FROM customers 
                  WHERE CustomerGrade = 'A' 
                  ORDER BY TotalPurchase DESC 
                  LIMIT 10";
    
    $gradeAStmt = $pdo->prepare($gradeASql);
    $gradeAStmt->execute();
    $gradeACustomers = $gradeAStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($gradeACustomers) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>CustomerCode</th><th>CustomerName</th><th>TotalPurchase</th><th>GradeCalculatedDate</th></tr>\n";
        
        foreach ($gradeACustomers as $customer) {
            $highlight = ($customer['CustomerCode'] === 'CUST003') ? 'style="background-color: #ffeb3b;"' : '';
            echo "<tr {$highlight}>\n";
            echo "<td>{$customer['CustomerCode']}</td>\n";
            echo "<td>{$customer['CustomerName']}</td>\n";
            echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>\n";
            echo "<td>{$customer['GradeCalculatedDate']}</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>ไม่พบลูกค้า Grade A</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>✅ เสร็จสิ้น</h3>\n";
    echo "<p>Grade calculation ได้รับการแก้ไขแล้ว</p>\n";
    echo "<p><a href='pages/customer_intelligence.php'>🔗 ไปที่ Customer Intelligence</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>