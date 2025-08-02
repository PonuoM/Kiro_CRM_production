<?php
/**
 * Fix Customer Intelligence Grades - Correct Logic
 * แก้ไข Grade calculation ด้วย logic ที่ถูกต้อง
 * ใช้ SUM(Price) เพราะ Price = ยอดรวมหลังหักส่วนลดแล้ว
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔧 แก้ไข Customer Intelligence Grades (Correct Logic)</h2>\n";
    echo "<p><strong>Logic ที่ถูกต้อง:</strong> Price = ยอดรวมหลังหักส่วนลดแล้ว (ไม่ต้อง × Quantity)</p>\n";
    echo "<p>กำลังดำเนินการ...</p>\n";
    flush();
    
    // 1. ตรวจสอบข้อมูล CUST003 ก่อนแก้ไข
    echo "<h3>1. ข้อมูล CUST003 ก่อนแก้ไข</h3>\n";
    
    $beforeSql = "SELECT CustomerCode, CustomerName, CustomerGrade, TotalPurchase, GradeCalculatedDate 
                  FROM customers WHERE CustomerCode = 'CUST003'";
    $beforeStmt = $pdo->prepare($beforeSql);
    $beforeStmt->execute();
    $before = $beforeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($before) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Field</th><th>Before</th></tr>\n";
        foreach ($before as $field => $value) {
            echo "<tr><td>{$field}</td><td>{$value}</td></tr>\n";
        }
        echo "</table>\n";
    }
    
    // 2. คำนวณยอดที่ถูกต้องจาก orders
    echo "<h3>2. คำนวณยอดที่ถูกต้องจาก orders</h3>\n";
    
    $calcSql = "SELECT 
                    CustomerCode,
                    COUNT(*) as total_orders,
                    SUM(Price) as sum_price,
                    SUM(SubtotalAmount) as sum_subtotal,
                    SUM(Subtotal_amount2) as sum_subtotal2
                FROM orders 
                WHERE CustomerCode = 'CUST003'
                GROUP BY CustomerCode";
    
    $calcStmt = $pdo->prepare($calcSql);
    $calcStmt->execute();
    $calc = $calcStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($calc) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Calculation Method</th><th>Amount</th><th>Recommendation</th></tr>\n";
        echo "<tr><td>SUM(Price)</td><td>฿" . number_format($calc['sum_price'], 2) . "</td><td>✅ <strong>ใช้นี้</strong> (ยอดหลังหักส่วนลด)</td></tr>\n";
        echo "<tr><td>SUM(SubtotalAmount)</td><td>฿" . number_format($calc['sum_subtotal'], 2) . "</td><td>⚠️ ตรวจสอบ (อาจเป็นยอดเดียวกัน)</td></tr>\n";
        echo "<tr><td>SUM(Subtotal_amount2)</td><td>฿" . number_format($calc['sum_subtotal2'], 2) . "</td><td>❌ ไม่ครบ (บางรายการเป็น 0)</td></tr>\n";
        echo "<tr><td>Total Orders</td><td>{$calc['total_orders']} รายการ</td><td>ℹ️ ข้อมูลอ้างอิง</td></tr>\n";
        echo "</table>\n";
        
        // เลือกยอดที่จะใช้
        $correctAmount = $calc['sum_price']; // ใช้ SUM(Price)
        $expectedGrade = 'D';
        if ($correctAmount >= 10000) $expectedGrade = 'A';
        elseif ($correctAmount >= 5000) $expectedGrade = 'B';
        elseif ($correctAmount >= 2000) $expectedGrade = 'C';
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<p><strong>💡 การคำนวณที่ถูกต้อง:</strong></p>\n";
        echo "<p>ยอดที่จะใช้: <strong>฿" . number_format($correctAmount, 2) . "</strong> (จาก SUM(Price))</p>\n";
        echo "<p>Grade ที่ควรได้: <strong>{$expectedGrade}</strong></p>\n";
        echo "</div>\n";
    }
    flush();
    
    // 3. อัปเดต TotalPurchase ด้วย SUM(Price)
    echo "<h3>3. อัปเดต TotalPurchase ด้วย SUM(Price)</h3>\n";
    
    $updateSql = "
        UPDATE customers c
        SET TotalPurchase = COALESCE((
                SELECT SUM(Price) 
                FROM orders o 
                WHERE o.CustomerCode = c.CustomerCode
            ), 0),
            LastPurchaseDate = (
                SELECT MAX(DATE(DocumentDate)) 
                FROM orders o 
                WHERE o.CustomerCode = c.CustomerCode
            )
        WHERE c.CustomerCode IS NOT NULL
    ";
    
    try {
        $updateStmt = $pdo->prepare($updateSql);
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
    
    // 5. ตรวจสอบ CUST003 หลังแก้ไข
    echo "<h3>5. ตรวจสอบ CUST003 หลังแก้ไข</h3>\n";
    
    $afterSql = "SELECT CustomerCode, CustomerName, CustomerGrade, TotalPurchase, GradeCalculatedDate, LastPurchaseDate 
                 FROM customers WHERE CustomerCode = 'CUST003'";
    $afterStmt = $pdo->prepare($afterSql);
    $afterStmt->execute();
    $after = $afterStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($after) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Field</th><th>Before</th><th>After</th><th>Status</th></tr>\n";
        
        foreach ($after as $field => $afterValue) {
            $beforeValue = isset($before[$field]) ? $before[$field] : 'N/A';
            $status = '✅';
            
            if ($field === 'CustomerGrade') {
                if ($afterValue === 'A' && $after['TotalPurchase'] >= 10000) {
                    $status = '🎉 <strong>CORRECT!</strong>';
                } elseif ($afterValue !== 'A' && $after['TotalPurchase'] >= 10000) {
                    $status = '❌ Should be A';
                } else {
                    $status = '✅';
                }
            }
            
            echo "<tr>";
            echo "<td>{$field}</td>";
            echo "<td>{$beforeValue}</td>";
            echo "<td>{$afterValue}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // สรุปผล
        if ($after['CustomerGrade'] === 'A' && $after['TotalPurchase'] >= 10000) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0; text-align: center;'>\n";
            echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>🎉 สำเร็จ!</h4>\n";
            echo "<p style='font-size: 1.2em; margin: 0;'>CUST003 ได้ Grade A ถูกต้องแล้ว!</p>\n";
            echo "<p style='margin: 10px 0 0 0;'>ยอดซื้อ: <strong>฿" . number_format($after['TotalPurchase'], 2) . "</strong></p>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 15px 0;'>\n";
            echo "<h4 style='color: #721c24;'>❌ ยังมีปัญหา</h4>\n";
            echo "<p>CUST003 ควรได้ Grade A แต่ได้ Grade {$after['CustomerGrade']}</p>\n";
            echo "</div>\n";
        }
    }
    flush();
    
    // 6. แสดง Grade Distribution
    echo "<h3>6. Grade Distribution หลังแก้ไข</h3>\n";
    
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
    
    // 7. Grade A Customers
    echo "<h3>7. Grade A Customers</h3>\n";
    
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
            $highlight = ($customer['CustomerCode'] === 'CUST003') ? 'style="background-color: #ffeb3b; font-weight: bold;"' : '';
            echo "<tr {$highlight}>\n";
            echo "<td>{$customer['CustomerCode']}</td>\n";
            echo "<td>{$customer['CustomerName']}</td>\n";
            echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>\n";
            echo "<td>{$customer['GradeCalculatedDate']}</td>\n";
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
            echo "<p><strong>🎉 PERFECT!</strong> CUST003 อยู่ใน Grade A customers แล้ว!</p>\n";
            echo "</div>\n";
        }
    } else {
        echo "<p>ไม่พบลูกค้า Grade A</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>✅ เสร็จสิ้น</h3>\n";
    echo "<p><strong>สรุป:</strong> ใช้ SUM(Price) เพราะ Price = ยอดรวมหลังหักส่วนลดแล้ว</p>\n";
    echo "<div style='margin: 20px 0;'>\n";
    echo "<a href='pages/customer_detail.php?code=CUST003' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>👤 ดู CUST003</a>\n";
    echo "<a href='pages/customer_intelligence.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>📊 Customer Intelligence</a>\n";
    echo "<a href='test_customer_intelligence_fixes.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🧪 ทดสอบระบบ</a>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red; font-size: 1.2em;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<p>กรุณาตรวจสอบการเชื่อมต่อฐานข้อมูลและโครงสร้างตาราง</p>\n";
}
?>