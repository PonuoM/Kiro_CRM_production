<?php
/**
 * Debug Customer Grade Calculation Issue
 * วิเคราะห์ปัญหาการคำนวณ Grade ของลูกค้า CUST003
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Customer Grade Issue</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .amount { text-align: right; font-weight: bold; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        .highlight { background: #fff3cd; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Customer Grade Calculation Issue</h1>
        <p><strong>ปัญหา:</strong> CUST003 มียอดซื้อ ฿1,905,572.16 แต่แสดงเป็น Grade D แทนที่จะเป็น Grade A</p>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 1. ตรวจสอบข้อมูล CUST003
            echo '<div class="section info">';
            echo '<h3>1. ข้อมูลลูกค้า CUST003</h3>';
            
            $sql = "SELECT CustomerCode, CustomerName, CustomerGrade, TotalPurchase, 
                           GradeCalculatedDate, LastPurchaseDate 
                    FROM customers 
                    WHERE CustomerCode = 'CUST003'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th><th>Expected</th></tr>';
                echo '<tr><td>CustomerCode</td><td>' . $customer['CustomerCode'] . '</td><td>CUST003</td></tr>';
                echo '<tr><td>CustomerName</td><td>' . $customer['CustomerName'] . '</td><td>-</td></tr>';
                echo '<tr class="' . ($customer['CustomerGrade'] === 'D' ? 'error' : 'success') . '">';
                echo '<td>CustomerGrade</td><td>' . $customer['CustomerGrade'] . '</td><td>A (if TotalPurchase >= 10,000)</td></tr>';
                echo '<tr class="' . ($customer['TotalPurchase'] >= 1000000 ? 'highlight' : '') . '">';
                echo '<td>TotalPurchase</td><td class="amount">฿' . number_format($customer['TotalPurchase'], 2) . '</td><td>Should be >= ฿10,000 for Grade A</td></tr>';
                echo '<tr><td>GradeCalculatedDate</td><td>' . $customer['GradeCalculatedDate'] . '</td><td>-</td></tr>';
                echo '<tr><td>LastPurchaseDate</td><td>' . $customer['LastPurchaseDate'] . '</td><td>-</td></tr>';
                echo '</table>';
                
                // คำนวณ Grade ที่ถูกต้อง
                $correctGrade = 'D';
                if ($customer['TotalPurchase'] >= 10000) $correctGrade = 'A';
                elseif ($customer['TotalPurchase'] >= 5000) $correctGrade = 'B';
                elseif ($customer['TotalPurchase'] >= 2000) $correctGrade = 'C';
                
                echo '<div class="' . ($correctGrade === $customer['CustomerGrade'] ? 'success' : 'error') . '">';
                echo '<p><strong>Grade ที่ควรจะเป็น:</strong> ' . $correctGrade . '</p>';
                echo '<p><strong>Grade ปัจจุบัน:</strong> ' . $customer['CustomerGrade'] . '</p>';
                if ($correctGrade !== $customer['CustomerGrade']) {
                    echo '<p><strong>❌ ไม่ตรงกัน!</strong> ต้องแก้ไข</p>';
                } else {
                    echo '<p><strong>✅ ถูกต้อง</strong></p>';
                }
                echo '</div>';
            } else {
                echo '<p class="error">❌ ไม่พบลูกค้า CUST003</p>';
            }
            echo '</div>';
            
            // 2. ตรวจสอบ Orders ของ CUST003
            echo '<div class="section info">';
            echo '<h3>2. Orders ของ CUST003</h3>';
            
            $orderSql = "SELECT DocumentNo, OrderDate, TotalAmount, OrderStatus, CreatedDate 
                         FROM orders 
                         WHERE CustomerCode = 'CUST003' 
                         ORDER BY OrderDate DESC";
            
            $orderStmt = $pdo->prepare($orderSql);
            $orderStmt->execute();
            $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($orders) {
                echo '<table>';
                echo '<tr><th>DocumentNo</th><th>OrderDate</th><th>TotalAmount</th><th>OrderStatus</th><th>CreatedDate</th></tr>';
                $totalFromOrders = 0;
                foreach ($orders as $order) {
                    echo '<tr>';
                    echo '<td>' . $order['DocumentNo'] . '</td>';
                    echo '<td>' . $order['OrderDate'] . '</td>';
                    echo '<td class="amount">฿' . number_format($order['TotalAmount'], 2) . '</td>';
                    echo '<td>' . $order['OrderStatus'] . '</td>';
                    echo '<td>' . $order['CreatedDate'] . '</td>';
                    echo '</tr>';
                    $totalFromOrders += $order['TotalAmount'];
                }
                echo '</table>';
                
                echo '<div class="highlight">';
                echo '<p><strong>รวมยอด Orders:</strong> ฿' . number_format($totalFromOrders, 2) . '</p>';
                echo '<p><strong>TotalPurchase ใน customers table:</strong> ฿' . number_format($customer['TotalPurchase'], 2) . '</p>';
                if ($totalFromOrders != $customer['TotalPurchase']) {
                    echo '<p class="error"><strong>❌ ไม่ตรงกัน!</strong> อาจมีปัญหาการ sync ข้อมูล</p>';
                } else {
                    echo '<p class="success"><strong>✅ ตรงกัน</strong></p>';
                }
                echo '</div>';
            } else {
                echo '<p>❌ ไม่พบ Orders ของ CUST003</p>';
            }
            echo '</div>';
            
            // 3. ทดสอบ Grade Calculation Function
            echo '<div class="section info">';
            echo '<h3>3. ทดสอบ Grade Calculation Function</h3>';
            
            $testAmounts = [
                $customer['TotalPurchase'],
                1905572.16,
                10000,
                9999.99,
                5000,
                4999.99,
                2000,
                1999.99,
                0
            ];
            
            echo '<table>';
            echo '<tr><th>Amount</th><th>Expected Grade</th><th>Function Result</th><th>Status</th></tr>';
            
            foreach ($testAmounts as $amount) {
                // คำนวณ Grade ด้วย logic
                $expectedGrade = 'D';
                if ($amount >= 10000) $expectedGrade = 'A';
                elseif ($amount >= 5000) $expectedGrade = 'B';
                elseif ($amount >= 2000) $expectedGrade = 'C';
                
                // เรียกใช้ Function ใน Database
                $funcSql = "SELECT CalculateCustomerGrade(?) as grade";
                $funcStmt = $pdo->prepare($funcSql);
                $funcStmt->execute([$amount]);
                $funcResult = $funcStmt->fetch(PDO::FETCH_ASSOC);
                $actualGrade = $funcResult['grade'];
                
                $status = ($expectedGrade === $actualGrade) ? '✅' : '❌';
                $rowClass = ($expectedGrade === $actualGrade) ? 'success' : 'error';
                
                echo '<tr class="' . $rowClass . '">';
                echo '<td class="amount">฿' . number_format($amount, 2) . '</td>';
                echo '<td>' . $expectedGrade . '</td>';
                echo '<td>' . $actualGrade . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            // 4. ตรวจสอบ Stored Procedure
            echo '<div class="section info">';
            echo '<h3>4. ทดสอบ UpdateCustomerGrade Stored Procedure</h3>';
            
            // เรียกใช้ Stored Procedure
            try {
                $procSql = "CALL UpdateCustomerGrade('CUST003')";
                $procStmt = $pdo->prepare($procSql);
                $procStmt->execute();
                
                echo '<p class="success">✅ เรียกใช้ UpdateCustomerGrade('CUST003') สำเร็จ</p>';
                
                // ตรวจสอบผลลัพธ์
                $checkSql = "SELECT CustomerGrade, TotalPurchase, GradeCalculatedDate 
                            FROM customers 
                            WHERE CustomerCode = 'CUST003'";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute();
                $updatedCustomer = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>Field</th><th>Before</th><th>After</th><th>Status</th></tr>';
                echo '<tr>';
                echo '<td>CustomerGrade</td>';
                echo '<td>' . $customer['CustomerGrade'] . '</td>';
                echo '<td>' . $updatedCustomer['CustomerGrade'] . '</td>';
                echo '<td>' . ($updatedCustomer['CustomerGrade'] === 'A' ? '✅' : '❌') . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>TotalPurchase</td>';
                echo '<td class="amount">฿' . number_format($customer['TotalPurchase'], 2) . '</td>';
                echo '<td class="amount">฿' . number_format($updatedCustomer['TotalPurchase'], 2) . '</td>';
                echo '<td>' . ($updatedCustomer['TotalPurchase'] >= 10000 ? '✅' : '❌') . '</td>';
                echo '</tr>';
                echo '<tr>';
                echo '<td>GradeCalculatedDate</td>';
                echo '<td>' . $customer['GradeCalculatedDate'] . '</td>';
                echo '<td>' . $updatedCustomer['GradeCalculatedDate'] . '</td>';
                echo '<td>✅</td>';
                echo '</tr>';
                echo '</table>';
                
                if ($updatedCustomer['CustomerGrade'] === 'A') {
                    echo '<div class="success">';
                    echo '<p><strong>🎉 แก้ไขสำเร็จ!</strong> CUST003 ได้ Grade A แล้ว</p>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<p><strong>❌ ยังมีปัญหา</strong> Grade ยังไม่เป็น A</p>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<p class="error">❌ Error: ' . $e->getMessage() . '</p>';
            }
            echo '</div>';
            
            // 5. ตรวจสอบลูกค้าคนอื่นๆ
            echo '<div class="section info">';
            echo '<h3>5. ตรวจสอบลูกค้าที่มี TotalPurchase สูง</h3>';
            
            $highValueSql = "SELECT CustomerCode, CustomerName, CustomerGrade, TotalPurchase, GradeCalculatedDate 
                            FROM customers 
                            WHERE TotalPurchase >= 10000 
                            ORDER BY TotalPurchase DESC 
                            LIMIT 10";
            
            $highValueStmt = $pdo->prepare($highValueSql);
            $highValueStmt->execute();
            $highValueCustomers = $highValueStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($highValueCustomers) {
                echo '<table>';
                echo '<tr><th>CustomerCode</th><th>CustomerName</th><th>TotalPurchase</th><th>Grade</th><th>Should be</th><th>Status</th></tr>';
                
                foreach ($highValueCustomers as $cust) {
                    $shouldBe = 'A'; // เพราะ >= 10,000
                    $status = ($cust['CustomerGrade'] === $shouldBe) ? '✅' : '❌';
                    $rowClass = ($cust['CustomerGrade'] === $shouldBe) ? 'success' : 'error';
                    
                    echo '<tr class="' . $rowClass . '">';
                    echo '<td>' . $cust['CustomerCode'] . '</td>';
                    echo '<td>' . $cust['CustomerName'] . '</td>';
                    echo '<td class="amount">฿' . number_format($cust['TotalPurchase'], 2) . '</td>';
                    echo '<td>' . $cust['CustomerGrade'] . '</td>';
                    echo '<td>' . $shouldBe . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p>ไม่พบลูกค้าที่มี TotalPurchase >= 10,000</p>';
            }
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="section error">';
            echo '<h3>❌ Error</h3>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        ?>
        
        <div class="section info">
            <h3>6. สรุปและแนวทางแก้ไข</h3>
            <ul>
                <li><strong>ปัญหาหลัก:</strong> Grade calculation logic อาจไม่ได้ทำงานหรือ TotalPurchase ไม่ถูกต้อง</li>
                <li><strong>วิธีแก้:</strong> 
                    <ol>
                        <li>ตรวจสอบว่า CalculateCustomerGrade function ทำงานถูกต้อง</li>
                        <li>ตรวจสอบ UpdateCustomerGrade stored procedure</li>
                        <li>Sync TotalPurchase จาก orders table</li>
                        <li>อัปเดต Grade ทุกลูกค้าใหม่</li>
                    </ol>
                </li>
                <li><strong>ผลลัพธ์:</strong> หลังจากรัน UpdateCustomerGrade แล้ว Grade ควรถูกต้อง</li>
            </ul>
        </div>
        
        <hr>
        <p><strong>📝 หมายเหตุ:</strong> ไฟล์นี้จะถูกลบหลังจากแก้ไขปัญหาเสร็จแล้ว</p>
    </div>
</body>
</html>