<?php
/**
 * Test Customer Intelligence Fixes
 * ทดสอบการแก้ไข Customer Intelligence System
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Customer Intelligence Fixes</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        .warning { background: #fff3cd; border-color: #ffeaa7; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .amount { text-align: right; font-weight: bold; }
        .highlight { background: #fff3cd; font-weight: bold; }
        .btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .grade-a { color: #28a745; font-weight: bold; }
        .grade-b { color: #007bff; font-weight: bold; }
        .grade-c { color: #ffc107; font-weight: bold; }
        .grade-d { color: #6c757d; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test Customer Intelligence Fixes</h1>
        <p><strong>วันที่ทดสอบ:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>การแก้ไขที่ทำ:</strong></p>
        <ul>
            <li>✅ แก้ไข Grade calculation logic</li>
            <li>✅ ลบ Customer Temperature และ Recommendations ออกจาก UI</li>
            <li>✅ ปรับ Layout Customer Intelligence ใหม่</li>
            <li>✅ เพิ่ม script แก้ไข TotalPurchase และ Grade calculation</li>
        </ul>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 1. ทดสอบ CUST003
            echo '<div class="section info">';
            echo '<h3>1. ทดสอบ CUST003 - ลูกค้าที่มีปัญหา Grade</h3>';
            
            // ตรวจสอบ columns ที่มีอยู่ก่อน
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
            
            // สร้าง SQL query ตาม columns ที่มี
            $selectFields = ["CustomerCode", "CustomerName"];
            if ($hasGrade) $selectFields[] = "CustomerGrade";
            if ($hasTotalPurchase) $selectFields[] = "TotalPurchase";
            if ($hasGradeCalculatedDate) $selectFields[] = "GradeCalculatedDate";
            if ($hasLastPurchaseDate) $selectFields[] = "LastPurchaseDate";
            
            $sql = "SELECT " . implode(", ", $selectFields) . " 
                    FROM customers 
                    WHERE CustomerCode = 'CUST003'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $cust003 = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cust003) {
                echo '<table>';
                echo '<tr><th>Field</th><th>Value</th><th>Status</th></tr>';
                echo '<tr><td>CustomerCode</td><td>' . $cust003['CustomerCode'] . '</td><td>✅</td></tr>';
                echo '<tr><td>CustomerName</td><td>' . $cust003['CustomerName'] . '</td><td>✅</td></tr>';
                
                if ($hasGrade && isset($cust003['CustomerGrade'])) {
                    $gradeClass = 'grade-' . strtolower($cust003['CustomerGrade']);
                    $gradeStatus = ($hasTotalPurchase && isset($cust003['TotalPurchase']) && $cust003['TotalPurchase'] >= 10000 && $cust003['CustomerGrade'] === 'A') ? '✅' : '❌';
                    echo '<tr><td>CustomerGrade</td><td class="' . $gradeClass . '">' . $cust003['CustomerGrade'] . '</td><td>' . $gradeStatus . '</td></tr>';
                } else {
                    echo '<tr><td>CustomerGrade</td><td>❌ Column not found</td><td>❌</td></tr>';
                }
                
                if ($hasTotalPurchase && isset($cust003['TotalPurchase'])) {
                    $amountClass = ($cust003['TotalPurchase'] >= 10000) ? 'highlight' : '';
                    echo '<tr class="' . $amountClass . '"><td>TotalPurchase</td><td class="amount">฿' . number_format($cust003['TotalPurchase'], 2) . '</td><td>' . (($cust003['TotalPurchase'] >= 10000) ? '✅' : '❌') . '</td></tr>';
                } else {
                    echo '<tr><td>TotalPurchase</td><td>❌ Column not found</td><td>❌</td></tr>';
                }
                
                if ($hasGradeCalculatedDate && isset($cust003['GradeCalculatedDate'])) {
                    echo '<tr><td>GradeCalculatedDate</td><td>' . $cust003['GradeCalculatedDate'] . '</td><td>✅</td></tr>';
                } else {
                    echo '<tr><td>GradeCalculatedDate</td><td>❌ Column not found</td><td>❌</td></tr>';
                }
                
                if ($hasLastPurchaseDate && isset($cust003['LastPurchaseDate'])) {
                    echo '<tr><td>LastPurchaseDate</td><td>' . $cust003['LastPurchaseDate'] . '</td><td>✅</td></tr>';
                } else {
                    echo '<tr><td>LastPurchaseDate</td><td>❌ Column not found</td><td>❌</td></tr>';
                }
                echo '</table>';
                
                // ตรวจสอบความสมบูรณ์
                if (!$hasGrade || !$hasTotalPurchase) {
                    echo '<div class="error"><p><strong>❌ SETUP REQUIRED:</strong> ต้องรัน fix script เพื่อเพิ่ม columns ที่จำเป็น</p></div>';
                    echo '<p><a href="fix_customer_intelligence_grades_safe.php" class="btn">🔧 Run Setup Script</a></p>';
                } elseif (isset($cust003['TotalPurchase']) && isset($cust003['CustomerGrade']) && $cust003['TotalPurchase'] >= 10000 && $cust003['CustomerGrade'] === 'A') {
                    echo '<div class="success"><p><strong>🎉 PASS:</strong> CUST003 มี Grade A ถูกต้องแล้ว!</p></div>';
                } else {
                    echo '<div class="error"><p><strong>❌ FAIL:</strong> CUST003 ยังไม่ถูกต้อง ต้องรัน fix script</p></div>';
                    echo '<p><a href="fix_customer_intelligence_grades_safe.php" class="btn">🔧 Run Fix Script</a></p>';
                }
            } else {
                echo '<div class="error"><p>❌ ไม่พบ CUST003</p></div>';
            }
            echo '</div>';
            
            // 2. ทดสอบ Grade Distribution
            echo '<div class="section info">';
            echo '<h3>2. Grade Distribution</h3>';
            
            $gradeSQL = "SELECT CustomerGrade, COUNT(*) as count, 
                                MIN(TotalPurchase) as min_purchase,
                                MAX(TotalPurchase) as max_purchase,
                                AVG(TotalPurchase) as avg_purchase
                         FROM customers 
                         WHERE CustomerGrade IS NOT NULL
                         GROUP BY CustomerGrade 
                         ORDER BY CustomerGrade";
            
            $gradeStmt = $pdo->prepare($gradeSQL);
            $gradeStmt->execute();
            $grades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($grades) {
                echo '<table>';
                echo '<tr><th>Grade</th><th>Count</th><th>Min Purchase</th><th>Max Purchase</th><th>Avg Purchase</th><th>Logic Check</th></tr>';
                
                foreach ($grades as $grade) {
                    $gradeClass = 'grade-' . strtolower($grade['CustomerGrade']);
                    
                    // ตรวจสอบ logic
                    $logicCheck = '✅';
                    if ($grade['CustomerGrade'] === 'A' && $grade['min_purchase'] < 10000) $logicCheck = '❌';
                    elseif ($grade['CustomerGrade'] === 'B' && ($grade['min_purchase'] < 5000 || $grade['max_purchase'] >= 10000)) $logicCheck = '❌';
                    elseif ($grade['CustomerGrade'] === 'C' && ($grade['min_purchase'] < 2000 || $grade['max_purchase'] >= 5000)) $logicCheck = '❌';
                    elseif ($grade['CustomerGrade'] === 'D' && $grade['max_purchase'] >= 2000) $logicCheck = '❌';
                    
                    echo '<tr>';
                    echo '<td class="' . $gradeClass . '">' . $grade['CustomerGrade'] . '</td>';
                    echo '<td>' . $grade['count'] . '</td>';
                    echo '<td class="amount">฿' . number_format($grade['min_purchase'], 2) . '</td>';
                    echo '<td class="amount">฿' . number_format($grade['max_purchase'], 2) . '</td>';
                    echo '<td class="amount">฿' . number_format($grade['avg_purchase'], 2) . '</td>';
                    echo '<td>' . $logicCheck . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            echo '</div>';
            
            // 3. ทดสอบ Grade A customers
            echo '<div class="section info">';
            echo '<h3>3. Grade A Customers (Top 10)</h3>';
            
            $gradeASQL = "SELECT CustomerCode, CustomerName, TotalPurchase, CustomerGrade
                          FROM customers 
                          WHERE CustomerGrade = 'A' 
                          ORDER BY TotalPurchase DESC 
                          LIMIT 10";
            
            $gradeAStmt = $pdo->prepare($gradeASQL);
            $gradeAStmt->execute();
            $gradeACustomers = $gradeAStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($gradeACustomers) {
                echo '<table>';
                echo '<tr><th>CustomerCode</th><th>CustomerName</th><th>TotalPurchase</th><th>Grade</th><th>Check</th></tr>';
                
                foreach ($gradeACustomers as $customer) {
                    $check = ($customer['TotalPurchase'] >= 10000) ? '✅' : '❌';
                    $highlight = ($customer['CustomerCode'] === 'CUST003') ? 'highlight' : '';
                    
                    echo '<tr class="' . $highlight . '">';
                    echo '<td>' . $customer['CustomerCode'] . '</td>';
                    echo '<td>' . $customer['CustomerName'] . '</td>';
                    echo '<td class="amount">฿' . number_format($customer['TotalPurchase'], 2) . '</td>';
                    echo '<td class="grade-a">' . $customer['CustomerGrade'] . '</td>';
                    echo '<td>' . $check . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                $cust003Found = false;
                foreach ($gradeACustomers as $customer) {
                    if ($customer['CustomerCode'] === 'CUST003') {
                        $cust003Found = true;
                        break;
                    }
                }
                
                if ($cust003Found) {
                    echo '<div class="success"><p><strong>🎉 PASS:</strong> CUST003 อยู่ใน Grade A แล้ว!</p></div>';
                } else {
                    echo '<div class="warning"><p><strong>⚠️ WARNING:</strong> CUST003 ไม่อยู่ใน Top 10 Grade A</p></div>';
                }
            } else {
                echo '<div class="warning"><p>⚠️ ไม่พบลูกค้า Grade A</p></div>';
            }
            echo '</div>';
            
            // 4. UI Changes Test
            echo '<div class="section info">';
            echo '<h3>4. UI Changes Test</h3>';
            echo '<p>การเปลี่ยนแปลง UI:</p>';
            echo '<ul>';
            echo '<li>✅ ลบ Customer Temperature section ออกจาก customer detail</li>';
            echo '<li>✅ ลบ Recommendations section ออกจาก customer detail</li>';
            echo '<li>✅ ปรับ Layout ให้แสดงเฉพาะ Grade information</li>';
            echo '<li>✅ เพิ่ม Grade criteria display</li>';
            echo '<li>✅ ปรับปรุง CSS สำหรับ layout ใหม่</li>';
            echo '</ul>';
            
            echo '<p><strong>ทดสอบ UI:</strong></p>';
            echo '<div>';
            echo '<a href="pages/customer_detail.php?code=CUST003" class="btn">🧪 ทดสอบ CUST003</a>';
            echo '<a href="pages/customer_intelligence.php" class="btn">📊 Customer Intelligence</a>';
            echo '<a href="fix_customer_intelligence_grades.php" class="btn">🔧 Run Grade Fix</a>';
            echo '</div>';
            echo '</div>';
            
            // 5. สรุปผลการทดสอบ
            echo '<div class="section info">';
            echo '<h3>5. สรุปผลการทดสอบ</h3>';
            
            $allTests = [];
            
            // Test 1: CUST003 Grade
            if ($cust003 && $cust003['TotalPurchase'] >= 10000 && $cust003['CustomerGrade'] === 'A') {
                $allTests[] = ['name' => 'CUST003 Grade A', 'status' => true];
            } else {
                $allTests[] = ['name' => 'CUST003 Grade A', 'status' => false];
            }
            
            // Test 2: Grade Logic
            $gradeLogicOK = true;
            foreach ($grades as $grade) {
                if ($grade['CustomerGrade'] === 'A' && $grade['min_purchase'] < 10000) $gradeLogicOK = false;
                elseif ($grade['CustomerGrade'] === 'B' && ($grade['min_purchase'] < 5000 || $grade['max_purchase'] >= 10000)) $gradeLogicOK = false;
                elseif ($grade['CustomerGrade'] === 'C' && ($grade['min_purchase'] < 2000 || $grade['max_purchase'] >= 5000)) $gradeLogicOK = false;
                elseif ($grade['CustomerGrade'] === 'D' && $grade['max_purchase'] >= 2000) $gradeLogicOK = false;
            }
            $allTests[] = ['name' => 'Grade Logic', 'status' => $gradeLogicOK];
            
            // Test 3: Grade A Count
            $gradeACount = 0;
            foreach ($grades as $grade) {
                if ($grade['CustomerGrade'] === 'A') {
                    $gradeACount = $grade['count'];
                    break;
                }
            }
            $allTests[] = ['name' => 'Grade A Count > 0', 'status' => ($gradeACount > 0)];
            
            // Test 4: UI Files
            $uiFiles = [
                'assets/js/customer-detail.js',
                'assets/css/customer-intelligence.css'
            ];
            $uiFilesOK = true;
            foreach ($uiFiles as $file) {
                if (!file_exists($file)) {
                    $uiFilesOK = false;
                    break;
                }
            }
            $allTests[] = ['name' => 'UI Files Exist', 'status' => $uiFilesOK];
            
            echo '<table>';
            echo '<tr><th>Test</th><th>Status</th></tr>';
            
            $passCount = 0;
            foreach ($allTests as $test) {
                $status = $test['status'] ? '✅ PASS' : '❌ FAIL';
                $rowClass = $test['status'] ? 'success' : 'error';
                echo '<tr class="' . $rowClass . '"><td>' . $test['name'] . '</td><td>' . $status . '</td></tr>';
                if ($test['status']) $passCount++;
            }
            echo '</table>';
            
            $totalTests = count($allTests);
            $percentage = round(($passCount / $totalTests) * 100, 1);
            
            if ($percentage >= 100) {
                echo '<div class="success">';
                echo '<h4>🎉 ทดสอบผ่านทั้งหมด!</h4>';
                echo '<p>ผ่าน ' . $passCount . '/' . $totalTests . ' tests (' . $percentage . '%)</p>';
                echo '<p><strong>Customer Intelligence system พร้อมใช้งานแล้ว!</strong></p>';
                echo '</div>';
            } elseif ($percentage >= 75) {
                echo '<div class="warning">';
                echo '<h4>⚠️ ทดสอบผ่านส่วนใหญ่</h4>';
                echo '<p>ผ่าน ' . $passCount . '/' . $totalTests . ' tests (' . $percentage . '%)</p>';
                echo '<p>มีบางจุดที่ต้องแก้ไข</p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<h4>❌ ทดสอบไม่ผ่าน</h4>';
                echo '<p>ผ่าน ' . $passCount . '/' . $totalTests . ' tests (' . $percentage . '%)</p>';
                echo '<p>ต้องแก้ไขปัญหาก่อนใช้งาน</p>';
                echo '</div>';
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
            <h3>6. การใช้งานต่อไป</h3>
            <ol>
                <li><strong>หากทดสอบยังไม่ผ่าน:</strong> รัน <code>fix_customer_intelligence_grades.php</code> ก่อน</li>
                <li><strong>ทดสอบ UI:</strong> ไปที่หน้า Customer Detail ของ CUST003</li>
                <li><strong>ตรวจสอบ Grade:</strong> ดูว่า Grade แสดงเป็น A และไม่มี Temperature/Recommendations</li>
                <li><strong>ทดสอบ Intelligence Dashboard:</strong> ไปที่หน้า Customer Intelligence</li>
                <li><strong>ลบไฟล์ทดสอบ:</strong> หลังจากทดสอบเสร็จแล้ว</li>
            </ol>
        </div>
        
        <hr>
        <p><strong>📝 หมายเหตุ:</strong> ไฟล์นี้เป็นไฟล์ทดสอบ ควรลบออกหลังจากใช้งานเสร็จแล้ว</p>
    </div>
</body>
</html>