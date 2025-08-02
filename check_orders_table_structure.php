<?php
/**
 * Check Orders Table Structure
 * ตรวจสอบโครงสร้างตาราง orders และข้อมูลตัวอย่าง
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔍 ตรวจสอบโครงสร้างตาราง orders</h2>\n";
    echo "<p>กำลังตรวจสอบ...</p>\n";
    flush();
    
    // 1. ตรวจสอบ columns ในตาราง orders
    echo "<h3>1. Columns ในตาราง orders</h3>\n";
    
    $sql = "SHOW COLUMNS FROM orders";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    $hasPrice = false;
    $hasQuantity = false;
    $hasSubtotalAmount = false;
    $hasSubtotal_amount2 = false;
    $hasTotalAmount = false;
    
    foreach ($columns as $column) {
        echo "<tr>\n";
        echo "<td>{$column['Field']}</td>\n";
        echo "<td>{$column['Type']}</td>\n";
        echo "<td>{$column['Null']}</td>\n";
        echo "<td>{$column['Key']}</td>\n";
        echo "<td>{$column['Default']}</td>\n";
        echo "<td>{$column['Extra']}</td>\n";
        echo "</tr>\n";
        
        // ตรวจสอบ columns ที่สำคัญ
        if ($column['Field'] === 'Price') $hasPrice = true;
        if ($column['Field'] === 'Quantity') $hasQuantity = true;
        if ($column['Field'] === 'SubtotalAmount') $hasSubtotalAmount = true;
        if ($column['Field'] === 'Subtotal_amount2') $hasSubtotal_amount2 = true;
        if ($column['Field'] === 'TotalAmount') $hasTotalAmount = true;
    }
    echo "</table>\n";
    
    // 2. ตรวจสอบ columns ที่เกี่ยวข้องกับยอดเงิน
    echo "<h3>2. ตรวจสอบ Amount Columns</h3>\n";
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Column</th><th>Exists</th><th>Usage</th></tr>\n";
    
    $amountColumns = [
        'Price' => [$hasPrice, 'ราคาต่อหน่วย'],
        'Quantity' => [$hasQuantity, 'จำนวน'],
        'SubtotalAmount' => [$hasSubtotalAmount, 'ยอดรวมก่อนหักส่วนลด'],
        'Subtotal_amount2' => [$hasSubtotal_amount2, 'ยอดรวมจาก frontend'],
        'TotalAmount' => [$hasTotalAmount, 'ยอดรวมสุทธิ']
    ];
    
    foreach ($amountColumns as $column => $data) {
        $exists = $data[0];
        $usage = $data[1];
        $status = $exists ? '✅' : '❌';
        $rowClass = $exists ? 'style="background: #d4edda;"' : 'style="background: #f8d7da;"';
        echo "<tr {$rowClass}>\n";
        echo "<td>{$column}</td>\n";
        echo "<td>{$status}</td>\n";
        echo "<td>{$usage}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 3. ตรวจสอบข้อมูลตัวอย่าง
    echo "<h3>3. ตรวจสอบข้อมูลตัวอย่าง orders</h3>\n";
    
    $sampleSql = "SELECT DocumentNo, CustomerCode, Price, Quantity";
    if ($hasSubtotalAmount) $sampleSql .= ", SubtotalAmount";
    if ($hasSubtotal_amount2) $sampleSql .= ", Subtotal_amount2";
    if ($hasTotalAmount) $sampleSql .= ", TotalAmount";
    $sampleSql .= " FROM orders ORDER BY CreatedDate DESC LIMIT 10";
    
    $sampleStmt = $pdo->prepare($sampleSql);
    $sampleStmt->execute();
    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($samples) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr>";
        foreach (array_keys($samples[0]) as $header) {
            echo "<th>{$header}</th>";
        }
        echo "<th>Calculated (Price * Qty)</th>";
        echo "</tr>\n";
        
        foreach ($samples as $sample) {
            echo "<tr>";
            foreach ($sample as $field => $value) {
                if (in_array($field, ['Price', 'Quantity', 'SubtotalAmount', 'Subtotal_amount2', 'TotalAmount'])) {
                    echo "<td style='text-align: right;'>" . number_format($value, 2) . "</td>";
                } else {
                    echo "<td>{$value}</td>";
                }
            }
            // คำนวณ Price * Quantity
            $calculated = $sample['Price'] * $sample['Quantity'];
            echo "<td style='text-align: right; font-weight: bold;'>" . number_format($calculated, 2) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // 4. ตรวจสอบ orders ของ CUST003
    echo "<h3>4. ตรวจสอบ orders ของ CUST003</h3>\n";
    
    $cust003Sql = "SELECT DocumentNo, CustomerCode, Price, Quantity";
    if ($hasSubtotalAmount) $cust003Sql .= ", SubtotalAmount";
    if ($hasSubtotal_amount2) $cust003Sql .= ", Subtotal_amount2";
    if ($hasTotalAmount) $cust003Sql .= ", TotalAmount";
    $cust003Sql .= " FROM orders WHERE CustomerCode = 'CUST003' ORDER BY CreatedDate DESC";
    
    $cust003Stmt = $pdo->prepare($cust003Sql);
    $cust003Stmt->execute();
    $cust003Orders = $cust003Stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($cust003Orders) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr>";
        foreach (array_keys($cust003Orders[0]) as $header) {
            echo "<th>{$header}</th>";
        }
        echo "<th>Calculated (Price * Qty)</th>";
        echo "</tr>\n";
        
        $totalByPrice = 0;
        $totalBySubtotal = 0;
        $totalBySubtotal2 = 0;
        $totalByTotalAmount = 0;
        
        foreach ($cust003Orders as $order) {
            echo "<tr>";
            foreach ($order as $field => $value) {
                if (in_array($field, ['Price', 'Quantity', 'SubtotalAmount', 'Subtotal_amount2', 'TotalAmount'])) {
                    echo "<td style='text-align: right;'>" . number_format($value, 2) . "</td>";
                } else {
                    echo "<td>{$value}</td>";
                }
            }
            // คำนวณ Price * Quantity
            $calculated = $order['Price'] * $order['Quantity'];
            echo "<td style='text-align: right; font-weight: bold;'>" . number_format($calculated, 2) . "</td>";
            echo "</tr>\n";
            
            // รวมยอด
            $totalByPrice += $calculated;
            if ($hasSubtotalAmount) $totalBySubtotal += $order['SubtotalAmount'];
            if ($hasSubtotal_amount2) $totalBySubtotal2 += $order['Subtotal_amount2'];
            if ($hasTotalAmount) $totalByTotalAmount += $order['TotalAmount'];
        }
        
        // แสดงยอดรวม
        echo "<tr style='background: #f8f9fa; font-weight: bold;'>";
        echo "<td colspan='3'>TOTAL</td>";
        if ($hasSubtotalAmount) echo "<td style='text-align: right;'>" . number_format($totalBySubtotal, 2) . "</td>";
        if ($hasSubtotal_amount2) echo "<td style='text-align: right;'>" . number_format($totalBySubtotal2, 2) . "</td>";
        if ($hasTotalAmount) echo "<td style='text-align: right;'>" . number_format($totalByTotalAmount, 2) . "</td>";
        echo "<td style='text-align: right; color: blue;'>" . number_format($totalByPrice, 2) . "</td>";
        echo "</tr>\n";
        
        echo "</table>\n";
        
        // เปรียบเทียบกับ TotalPurchase ใน customers table
        $customerSql = "SELECT TotalPurchase FROM customers WHERE CustomerCode = 'CUST003'";
        $customerStmt = $pdo->prepare($customerSql);
        $customerStmt->execute();
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>💰 เปรียบเทียบยอดเงิน CUST003</h4>\n";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Source</th><th>Amount</th><th>Should Use?</th></tr>\n";
        echo "<tr><td>TotalPurchase (customers table)</td><td>฿" . number_format($customer['TotalPurchase'], 2) . "</td><td>❌ ต้องอัปเดต</td></tr>\n";
        echo "<tr><td>SUM(Price * Quantity)</td><td>฿" . number_format($totalByPrice, 2) . "</td><td>✅ <strong>ใช้นี้</strong></td></tr>\n";
        if ($hasSubtotalAmount) echo "<tr><td>SUM(SubtotalAmount)</td><td>฿" . number_format($totalBySubtotal, 2) . "</td><td>⚠️ ตรวจสอบ</td></tr>\n";
        if ($hasSubtotal_amount2) echo "<tr><td>SUM(Subtotal_amount2)</td><td>฿" . number_format($totalBySubtotal2, 2) . "</td><td>⚠️ ตรวจสอบ</td></tr>\n";
        echo "</table>\n";
        echo "</div>\n";
        
    } else {
        echo "<p>❌ ไม่พบ orders ของ CUST003</p>\n";
    }
    
    // 5. สร้าง SQL สำหรับอัปเดต TotalPurchase
    echo "<h3>5. SQL Commands ที่ถูกต้อง</h3>\n";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
    echo "<h4>✅ SQL สำหรับอัปเดต TotalPurchase</h4>\n";
    echo "<pre>\n";
    echo "UPDATE customers c\n";
    echo "SET TotalPurchase = COALESCE((\n";
    echo "    SELECT SUM(Price * Quantity)\n";
    echo "    FROM orders o\n";
    echo "    WHERE o.CustomerCode = c.CustomerCode\n";
    echo "), 0),\n";
    echo "LastPurchaseDate = (\n";
    echo "    SELECT MAX(DocumentDate)\n";
    echo "    FROM orders o\n";
    echo "    WHERE o.CustomerCode = c.CustomerCode\n";
    echo ")\n";
    echo "WHERE c.CustomerCode IS NOT NULL;\n";
    echo "</pre>\n";
    echo "</div>\n";
    
    echo "<hr>\n";
    echo "<h3>✅ การตรวจสอบเสร็จสิ้น</h3>\n";
    echo "<p><strong>สรุป:</strong> ต้องใช้ Price * Quantity แทน TotalAmount</p>\n";
    echo "<p><a href='fix_customer_intelligence_grades_final.php'>🔧 รัน Fix Script ที่ถูกต้อง</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>