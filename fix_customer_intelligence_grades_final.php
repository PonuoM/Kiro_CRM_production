<?php
/**
 * Fix Customer Intelligence Grades - Final Version
 * แก้ไข Grade calculation และ sync TotalPurchase จาก orders table
 * ใช้ Price * Quantity แทน TotalAmount
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 แก้ไข Customer Intelligence Grades (Final Version)</h2>\n";
    echo "<p>กำลังดำเนินการ...</p>\n";
    flush();
    
    // 1. ตรวจสอบโครงสร้างตาราง
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
    
    echo "<p>Intelligence Columns:</p>\n";
    echo "<ul>\n";
    echo "<li>CustomerGrade: " . ($hasGrade ? '✅' : '❌') . "</li>\n";
    echo "<li>TotalPurchase: " . ($hasTotalPurchase ? '✅' : '❌') . "</li>\n";
    echo "<li>LastPurchaseDate: " . ($hasLastPurchaseDate ? '✅' : '❌') . "</li>\n";
    echo "<li>GradeCalculatedDate: " . ($hasGradeCalculatedDate ? '✅' : '❌') . "</li>\n";
    echo "</ul>\n";
    flush();
    
    // ตรวจสอบ orders table columns
    $orderColumnsSql = "SHOW COLUMNS FROM orders";
    $orderColumnsStmt = $pdo->prepare($orderColumnsSql);
    $orderColumnsStmt->execute();
    $orderColumns = $orderColumnsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasPrice = false;
    $hasQuantity = false;
    $hasDocumentDate = false;
    
    foreach ($orderColumns as $column) {
        if ($column['Field'] === 'Price') $hasPrice = true;
        if ($column['Field'] === 'Quantity') $hasQuantity = true;
        if ($column['Field'] === 'DocumentDate') $hasDocumentDate = true;
    }
    
    echo "<p>Orders Table Columns:</p>\n";
    echo "<ul>\n";
    echo "<li>Price: " . ($hasPrice ? '✅' : '❌') . "</li>\n";
    echo "<li>Quantity: " . ($hasQuantity ? '✅' : '❌') . "</li>\n";
    echo "<li>DocumentDate: " . ($hasDocumentDate ? '✅' : '❌') . "</li>\n";
    echo "</ul>\n";
    flush();
    
    if (!$hasPrice || !$hasQuantity) {
        throw new Exception("Orders table ไม่มี Price หรือ Quantity columns");
    }
    
    // 2. อัปเดต TotalPurchase จาก orders table ด้วย Price * Quantity
    echo "<h3>2. อัปเดต TotalPurchase จาก orders table</h3>\n";
    echo "<p>ใช้สูตร: SUM(Price * Quantity)</p>\n";
    
    $updateFields = ["TotalPurchase = COALESCE((
        SELECT SUM(Price * Quantity) 
        FROM orders o 
        WHERE o.CustomerCode = c.CustomerCode
    ), 0)"];
    
    if ($hasLastPurchaseDate && $hasDocumentDate) {
        $updateFields[] = "LastPurchaseDate = (
            SELECT MAX(DATE(DocumentDate)) 
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
    
    // 3. อัปเดต Grade ทุกลูกค้า
    echo "<h3>3. อัปเดต Customer Grade</h3>\n";
    
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
    
    // 4. ตรวจสอบ CUST003
    echo "<h3>4. ตรวจสอบ CUST003</h3>\n";
    
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
            echo "<tr><th>Field</th><th>Value</th><th>Status</th></tr>\n";
            foreach ($cust003 as $field => $value) {
                $status = '✅';
                if ($field === 'CustomerGrade' && $value !== 'A' && $cust003['TotalPurchase'] >= 10000) {
                    $status = '❌';
                }
                echo "<tr><td>{$field}</td><td>{$value}</td><td>{$status}</td></tr>\n";
            }
            echo "</table>\n";
            
            if ($cust003['CustomerGrade'] === 'A' && $cust003['TotalPurchase'] >= 10000) {
                echo "<p style='color: green; font-size: 1.2em;'>🎉 <strong>สำเร็จ!</strong> CUST003 ได้ Grade A แล้ว</p>\n";
            } else {
                echo "<p style='color: red;'>❌ <strong>ยังมีปัญหา</strong> CUST003 ยังไม่ได้ Grade A</p>\n";
                echo "<p>TotalPurchase: ฿" . number_format($cust003['TotalPurchase'], 2) . " (ควรเป็น A ถ้า >= ฿10,000)</p>\n";
                echo "<p>CustomerGrade: {$cust003['CustomerGrade']} (ควรเป็น A)</p>\n";
            }
        } else {
            echo "<p>❌ ไม่พบ CUST003</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking CUST003: " . $e->getMessage() . "</p>\n";
    }
    flush();
    
    // 5. แสดงการคำนวณ CUST003 จาก orders
    echo "<h3>5. ตรวจสอบการคำนวณจาก orders ของ CUST003</h3>\n";
    
    try {
        $orderCalcSql = "SELECT 
                            DocumentNo,
                            Price,
                            Quantity,
                            (Price * Quantity) as LineTotal
                         FROM orders 
                         WHERE CustomerCode = 'CUST003' 
                         ORDER BY DocumentNo";
        
        $orderCalcStmt = $pdo->prepare($orderCalcSql);
        $orderCalcStmt->execute();
        $orderCalcs = $orderCalcStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($orderCalcs) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>DocumentNo</th><th>Price</th><th>Quantity</th><th>LineTotal</th></tr>\n";
            
            $grandTotal = 0;
            foreach ($orderCalcs as $calc) {
                echo "<tr>\n";
                echo "<td>{$calc['DocumentNo']}</td>\n";
                echo "<td style='text-align: right;'>฿" . number_format($calc['Price'], 2) . "</td>\n";
                echo "<td style='text-align: right;'>" . number_format($calc['Quantity'], 2) . "</td>\n";
                echo "<td style='text-align: right;'>฿" . number_format($calc['LineTotal'], 2) . "</td>\n";
                echo "</tr>\n";
                $grandTotal += $calc['LineTotal'];
            }
            
            echo "<tr style='background: #f8f9fa; font-weight: bold;'>\n";
            echo "<td colspan='3'>GRAND TOTAL</td>\n";
            echo "<td style='text-align: right; color: blue;'>฿" . number_format($grandTotal, 2) . "</td>\n";
            echo "</tr>\n";
            echo "</table>\n";
            
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<p><strong>📊 การคำนวณ CUST003:</strong></p>\n";
            echo "<p>ยอดรวมจาก orders: <strong>฿" . number_format($grandTotal, 2) . "</strong></p>\n";
            echo "<p>ควรได้ Grade: <strong>" . ($grandTotal >= 10000 ? 'A' : ($grandTotal >= 5000 ? 'B' : ($grandTotal >= 2000 ? 'C' : 'D'))) . "</strong></p>\n";
            echo "</div>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error calculating orders: " . $e->getMessage() . "</p>\n";
    }
    
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
        echo "<tr><th>Grade</th><th>Count</th><th>Min Purchase</th><th>Max Purchase</th><th>Avg Purchase</th><th>Logic Check</th></tr>\n";
        
        foreach ($distribution as $grade) {
            // ตรวจสอบ logic
            $logicCheck = '✅';
            if ($grade['CustomerGrade'] === 'A' && $grade['min_purchase'] < 10000) $logicCheck = '❌';
            elseif ($grade['CustomerGrade'] === 'B' && ($grade['min_purchase'] < 5000 || $grade['max_purchase'] >= 10000)) $logicCheck = '❌';
            elseif ($grade['CustomerGrade'] === 'C' && ($grade['min_purchase'] < 2000 || $grade['max_purchase'] >= 5000)) $logicCheck = '❌';
            elseif ($grade['CustomerGrade'] === 'D' && $grade['max_purchase'] >= 2000) $logicCheck = '❌';
            
            echo "<tr>\n";
            echo "<td><strong>{$grade['CustomerGrade']}</strong></td>\n";
            echo "<td>{$grade['count']}</td>\n";
            echo "<td>฿" . number_format($grade['min_purchase'], 2) . "</td>\n";
            echo "<td>฿" . number_format($grade['max_purchase'], 2) . "</td>\n";
            echo "<td>฿" . number_format($grade['avg_purchase'], 2) . "</td>\n";
            echo "<td>{$logicCheck}</td>\n";
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
            
            // ตรวจสอบ CUST003
            $cust003InGradeA = false;
            foreach ($gradeACustomers as $customer) {
                if ($customer['CustomerCode'] === 'CUST003') {
                    $cust003InGradeA = true;
                    break;
                }
            }
            
            if ($cust003InGradeA) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
                echo "<p><strong>🎉 SUCCESS!</strong> CUST003 อยู่ใน Grade A แล้ว!</p>\n";
                echo "</div>\n";
            }
            
        } else {
            echo "<p>ไม่พบลูกค้า Grade A</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error getting Grade A customers: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>✅ เสร็จสิ้น</h3>\n";
    echo "<p>Grade calculation ได้รับการแก้ไขแล้ว โดยใช้ Price * Quantity จาก orders table</p>\n";
    echo "<div style='margin: 20px 0;'>\n";
    echo "<a href='pages/customer_detail.php?code=CUST003' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👤 ดู CUST003</a>\n";
    echo "<a href='pages/customer_intelligence.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 Customer Intelligence</a>\n";
    echo "<a href='test_customer_intelligence_fixes.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 ทดสอบระบบ</a>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-size: 1.2em;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>กรุณาตรวจสอบการเชื่อมต่อฐานข้อมูลและโครงสร้างตาราง</p>\n";
    echo "<p><a href='check_orders_table_structure.php'>🔍 ตรวจสอบโครงสร้าง orders table</a></p>\n";
}
?>