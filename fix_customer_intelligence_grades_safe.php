<?php
/**
 * Fix Customer Intelligence Grades - Safe Version
 * แก้ไข Grade calculation และ sync TotalPurchase จาก orders table
 * Version ที่ปลอดภัย - ตรวจสอบ columns ก่อนใช้งาน
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 แก้ไข Customer Intelligence Grades (Safe Version)</h2>\n";
    echo "<p>กำลังดำเนินการ...</p>\n";
    flush();
    
    // 1. ตรวจสอบ columns ในตาราง customers
    echo "<h3>1. ตรวจสอบโครงสร้างตาราง</h3>\n";
    
    $columnsSql = "SHOW COLUMNS FROM customers";
    $columnsStmt = $pdo->prepare($columnsSql);
    $columnsStmt->execute();
    $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasGrade = false;
    $hasTotalPurchase = false;
    $hasLastPurchaseDate = false;
    $hasGradeCalculatedDate = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'CustomerGrade') $hasGrade = true;
        if ($column['Field'] === 'TotalPurchase') $hasTotalPurchase = true;
        if ($column['Field'] === 'LastPurchaseDate') $hasLastPurchaseDate = true;
        if ($column['Field'] === 'GradeCalculatedDate') $hasGradeCalculatedDate = true;
    }
    
    echo "<p>Columns ที่พบ:</p>\n";
    echo "<ul>\n";
    echo "<li>CustomerGrade: " . ($hasGrade ? '✅' : '❌') . "</li>\n";
    echo "<li>TotalPurchase: " . ($hasTotalPurchase ? '✅' : '❌') . "</li>\n";
    echo "<li>LastPurchaseDate: " . ($hasLastPurchaseDate ? '✅' : '❌') . "</li>\n";
    echo "<li>GradeCalculatedDate: " . ($hasGradeCalculatedDate ? '✅' : '❌') . "</li>\n";
    echo "</ul>\n";
    flush();
    
    // 2. เพิ่ม columns ที่ขาดหายไป
    $needsColumns = [];
    if (!$hasGrade) $needsColumns[] = "ADD COLUMN CustomerGrade ENUM('A', 'B', 'C', 'D') NULL COMMENT 'Customer Grade based on purchase amount'";
    if (!$hasTotalPurchase) $needsColumns[] = "ADD COLUMN TotalPurchase DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total purchase amount for grading'";
    if (!$hasLastPurchaseDate) $needsColumns[] = "ADD COLUMN LastPurchaseDate DATE NULL COMMENT 'Last purchase date'";
    if (!$hasGradeCalculatedDate) $needsColumns[] = "ADD COLUMN GradeCalculatedDate DATETIME NULL COMMENT 'When grade was last calculated'";
    
    if (count($needsColumns) > 0) {
        echo "<h3>2. เพิ่ม Columns ที่ขาดหายไป</h3>\n";
        
        $alterSql = "ALTER TABLE customers " . implode(", ", $needsColumns);
        
        try {
            $alterStmt = $pdo->prepare($alterSql);
            $alterStmt->execute();
            echo "<p>✅ เพิ่ม columns เรียบร้อยแล้ว</p>\n";
            flush();
            
            // อัปเดตสถานะ flags
            $hasGrade = true;
            $hasTotalPurchase = true;
            $hasLastPurchaseDate = true;
            $hasGradeCalculatedDate = true;
            
        } catch (Exception $e) {
            echo "<p>❌ Error adding columns: " . $e->getMessage() . "</p>\n";
            throw $e;
        }
    } else {
        echo "<h3>2. ตาราง customers มี columns ครบถ้วนแล้ว</h3>\n";
        echo "<p>✅ พร้อมดำเนินการต่อ</p>\n";
    }
    flush();
    
    // 3. อัปเดต TotalPurchase จาก orders table
    echo "<h3>3. อัปเดต TotalPurchase จาก orders table</h3>\n";
    
    // สร้าง SQL สำหรับ update โดยตรวจสอบ columns ที่มี
    $updateFields = ["TotalPurchase = COALESCE((
        SELECT SUM(TotalAmount) 
        FROM orders o 
        WHERE o.CustomerCode = c.CustomerCode 
        AND o.OrderStatus IN ('completed', 'paid', 'pending')
    ), 0)"];
    
    if ($hasLastPurchaseDate) {
        $updateFields[] = "LastPurchaseDate = (
            SELECT MAX(OrderDate) 
            FROM orders o 
            WHERE o.CustomerCode = c.CustomerCode
        )";
    }
    
    $updateTotalSql = "
        UPDATE customers c
        SET " . implode(", ", $updateFields) . "
        WHERE c.CustomerCode IS NOT NULL
    ";
    
    try {
        $updateStmt = $pdo->prepare($updateTotalSql);
        $updateStmt->execute();
        $affectedRows = $updateStmt->rowCount();
        
        echo "<p>✅ อัปเดต TotalPurchase สำหรับ {$affectedRows} ลูกค้า</p>\n";
        flush();
    } catch (Exception $e) {
        echo "<p>❌ Error updating TotalPurchase: " . $e->getMessage() . "</p>\n";
        throw $e;
    }
    
    // 4. อัปเดต Grade ทุกลูกค้า
    echo "<h3>4. อัปเดต Customer Grade</h3>\n";
    
    $gradeFields = [
        "CustomerGrade = CASE 
            WHEN TotalPurchase >= 10000 THEN 'A'
            WHEN TotalPurchase >= 5000 THEN 'B'
            WHEN TotalPurchase >= 2000 THEN 'C'
            ELSE 'D'
        END"
    ];
    
    if ($hasGradeCalculatedDate) {
        $gradeFields[] = "GradeCalculatedDate = NOW()";
    }
    
    $gradeUpdateSql = "
        UPDATE customers 
        SET " . implode(", ", $gradeFields) . "
        WHERE CustomerCode IS NOT NULL
    ";
    
    try {
        $gradeStmt = $pdo->prepare($gradeUpdateSql);
        $gradeStmt->execute();
        $gradeRows = $gradeStmt->rowCount();
        
        echo "<p>✅ อัปเดต Grade สำหรับ {$gradeRows} ลูกค้า</p>\n";
        flush();
    } catch (Exception $e) {
        echo "<p>❌ Error updating grades: " . $e->getMessage() . "</p>\n";
        throw $e;
    }
    
    // 5. ตรวจสอบ CUST003
    echo "<h3>5. ตรวจสอบ CUST003</h3>\n";
    
    $cust003Fields = ["CustomerCode", "CustomerName", "TotalPurchase", "CustomerGrade"];
    if ($hasGradeCalculatedDate) $cust003Fields[] = "GradeCalculatedDate";
    if ($hasLastPurchaseDate) $cust003Fields[] = "LastPurchaseDate";
    
    $cust003Sql = "SELECT " . implode(", ", $cust003Fields) . " 
                   FROM customers WHERE CustomerCode = 'CUST003'";
    
    try {
        $cust003Stmt = $pdo->prepare($cust003Sql);
        $cust003Stmt->execute();
        $cust003 = $cust003Stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cust003) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>Field</th><th>Value</th></tr>\n";
            foreach ($cust003 as $field => $value) {
                echo "<tr><td>{$field}</td><td>{$value}</td></tr>\n";
            }
            echo "</table>\n";
            
            if ($cust003['CustomerGrade'] === 'A') {
                echo "<p style='color: green;'>🎉 <strong>สำเร็จ!</strong> CUST003 ได้ Grade A แล้ว</p>\n";
            } else {
                echo "<p style='color: red;'>❌ <strong>ยังมีปัญหา</strong> CUST003 ยังไม่ได้ Grade A (ได้ Grade {$cust003['CustomerGrade']})</p>\n";
            }
        } else {
            echo "<p>❌ ไม่พบ CUST003</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking CUST003: " . $e->getMessage() . "</p>\n";
    }
    flush();
    
    // 6. แสดง Grade distribution
    echo "<h3>6. Grade Distribution</h3>\n";
    
    try {
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
    } catch (Exception $e) {
        echo "<p>❌ Error getting distribution: " . $e->getMessage() . "</p>\n";
    }
    
    // 7. แสดง Grade A customers
    echo "<h3>7. Grade A Customers (Top 10)</h3>\n";
    
    try {
        $gradeASql = "SELECT CustomerCode, CustomerName, TotalPurchase";
        if ($hasGradeCalculatedDate) $gradeASql .= ", GradeCalculatedDate";
        $gradeASql .= " FROM customers 
                      WHERE CustomerGrade = 'A' 
                      ORDER BY TotalPurchase DESC 
                      LIMIT 10";
        
        $gradeAStmt = $pdo->prepare($gradeASql);
        $gradeAStmt->execute();
        $gradeACustomers = $gradeAStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($gradeACustomers) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>CustomerCode</th><th>CustomerName</th><th>TotalPurchase</th>";
            if ($hasGradeCalculatedDate) echo "<th>GradeCalculatedDate</th>";
            echo "</tr>\n";
            
            foreach ($gradeACustomers as $customer) {
                $highlight = ($customer['CustomerCode'] === 'CUST003') ? 'style="background-color: #ffeb3b;"' : '';
                echo "<tr {$highlight}>\n";
                echo "<td>{$customer['CustomerCode']}</td>\n";
                echo "<td>{$customer['CustomerName']}</td>\n";
                echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>\n";
                if ($hasGradeCalculatedDate) echo "<td>{$customer['GradeCalculatedDate']}</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p>ไม่พบลูกค้า Grade A</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error getting Grade A customers: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>✅ เสร็จสิ้น</h3>\n";
    echo "<p>Grade calculation ได้รับการแก้ไขแล้ว</p>\n";
    echo "<p><a href='pages/customer_intelligence.php'>🔗 ไปที่ Customer Intelligence</a></p>\n";
    echo "<p><a href='test_customer_intelligence_fixes.php'>🧪 ทดสอบระบบ</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>กรุณาตรวจสอบการเชื่อมต่อฐานข้อมูลและโครงสร้างตาราง</p>\n";
}
?>