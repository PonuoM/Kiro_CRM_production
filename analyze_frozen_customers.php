<?php
/**
 * Analyze FROZEN Customer Issues
 * Check why high-grade customers are marked as FROZEN
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧊 Analyze FROZEN Customer Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .issue { background-color: #fff3cd; border-left: 4px solid #ff6b6b; }
        .correct { background-color: #d4edda; border-left: 4px solid #28a745; }
        .frozen-logic { background-color: #e3f2fd; border-left: 4px solid #2196F3; }
        .high-value-frozen { background-color: #ffebee; border-left: 4px solid #f44336; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🧊 FROZEN Customer Analysis</h2>
        <p class="text-muted">วิเคราะห์ลูกค้า FROZEN และแก้ไขปัญหา Grade A/B ที่ไม่ควร FROZEN</p>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // FROZEN meaning explanation
            echo '<div class="alert frozen-logic">';
            echo '<h6>❄️ FROZEN ความหมายแท้จริง:</h6>';
            echo '<p><strong>"ลูกค้าที่เราจะไม่ติดต่อกลับไปอีก"</strong></p>';
            echo '<ul>';
            echo '<li>โทรหาแล้วลูกค้าแจ้งว่าไม่ต้องติดต่อกลับมาอีก</li>';
            echo '<li>ติดต่อมากกว่า 3 ครั้งแล้วไม่สามารถติดต่อได้</li>';
            echo '<li>ติดต่อแล้วเบอร์ผิด</li>';
            echo '<li><strong class="text-danger">❌ ไม่ควรมี Grade A/B เป็น FROZEN</strong></li>';
            echo '</ul>';
            echo '</div>';
            
            // 1. Check FROZEN customers by grade
            echo '<h4>📊 FROZEN Customers by Grade</h4>';
            
            $gradeSql = "
                SELECT 
                    CustomerGrade,
                    COUNT(*) as count,
                    AVG(COALESCE(TotalPurchase, 0)) as avg_purchase,
                    COUNT(CASE WHEN COALESCE(TotalPurchase, 0) >= 10000 THEN 1 END) as should_be_a,
                    COUNT(CASE WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN 1 END) as should_be_b
                FROM customers 
                WHERE CustomerTemperature = 'FROZEN'
                GROUP BY CustomerGrade
                ORDER BY CustomerGrade
            ";
            
            $stmt = $pdo->prepare($gradeSql);
            $stmt->execute();
            $gradeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalFrozen = array_sum(array_column($gradeStats, 'count'));
            $highValueFrozen = 0;
            
            echo '<div class="table-responsive mb-4">';
            echo '<table class="table table-striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Grade</th>';
            echo '<th>FROZEN Count</th>';
            echo '<th>Avg Purchase</th>';
            echo '<th>Should be A (≥฿10K)</th>';
            echo '<th>Should be B (≥฿5K)</th>';
            echo '<th>Status</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($gradeStats as $grade) {
                $isHighValue = ($grade['CustomerGrade'] === 'A' || $grade['CustomerGrade'] === 'B');
                $statusClass = $isHighValue ? 'table-danger' : 'table-secondary';
                $statusText = $isHighValue ? '🚨 ไม่ควร FROZEN' : '✅ ปกติ';
                
                if ($isHighValue) {
                    $highValueFrozen += $grade['count'];
                }
                
                echo "<tr class=\"$statusClass\">";
                echo "<td><span class=\"badge bg-primary\">{$grade['CustomerGrade']}</span></td>";
                echo "<td><strong>{$grade['count']}</strong></td>";
                echo "<td>฿" . number_format($grade['avg_purchase']) . "</td>";
                echo "<td>{$grade['should_be_a']}</td>";
                echo "<td>{$grade['should_be_b']}</td>";
                echo "<td>$statusText</td>";
                echo "</tr>";
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            
            if ($highValueFrozen > 0) {
                echo '<div class="alert issue">';
                echo "<h6>🚨 พบปัญหา: มี $highValueFrozen ลูกค้า Grade A/B ที่เป็น FROZEN (ไม่ควรเกิดขึ้น)</h6>";
                echo '</div>';
            }
            
            // 2. Analyze what causes FROZEN
            echo '<h4>🔍 FROZEN Causes Analysis</h4>';
            
            $causesSql = "
                SELECT 
                    COUNT(*) as total_frozen,
                    COUNT(CASE WHEN COALESCE(ContactAttempts, 0) >= 3 THEN 1 END) as high_attempts,
                    COUNT(CASE WHEN DATEDIFF(CURDATE(), COALESCE(LastContactDate, CreatedDate)) > 90 THEN 1 END) as old_no_contact,
                    COUNT(CASE WHEN Sales IS NULL THEN 1 END) as no_sales_assigned,
                    COUNT(CASE WHEN CustomerStatus = 'ในตระกร้า' THEN 1 END) as in_basket,
                    COUNT(CASE WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN 1 END) as high_value_frozen
                FROM customers 
                WHERE CustomerTemperature = 'FROZEN'
            ";
            
            $stmt = $pdo->prepare($causesSql);
            $stmt->execute();
            $causes = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="row">';
            
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header"><h6>📊 FROZEN Statistics</h6></div>';
            echo '<div class="card-body">';
            echo '<ul class="list-unstyled">';
            echo '<li><strong>Total FROZEN:</strong> ' . $causes['total_frozen'] . '</li>';
            echo '<li><strong>High Contact Attempts (≥3):</strong> ' . $causes['high_attempts'] . '</li>';
            echo '<li><strong>Old No Contact (>90 days):</strong> ' . $causes['old_no_contact'] . '</li>';
            echo '<li><strong>No Sales Assigned:</strong> ' . $causes['no_sales_assigned'] . '</li>';
            echo '<li><strong>In Basket Status:</strong> ' . $causes['in_basket'] . '</li>';
            echo '<li class="text-danger"><strong>High Value FROZEN (≥฿5K):</strong> ' . $causes['high_value_frozen'] . '</li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header"><h6>⚙️ Current FROZEN Logic Issues</h6></div>';
            echo '<div class="card-body">';
            echo '<p class="text-danger"><strong>ปัญหาปัจจุบัน:</strong></p>';
            echo '<ul>';
            echo '<li>Auto-system กำหนด FROZEN ตาม time-based rules</li>';
            echo '<li>ไม่พิจารณา customer value (TotalPurchase)</li>';
            echo '<li>ลูกค้า Grade A/B ถูก FROZEN โดยไม่สมควร</li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // 3. Show high-value customers that are FROZEN
            echo '<h4>🚨 High-Value Customers That Are FROZEN</h4>';
            
            $highValueSql = "
                SELECT 
                    CustomerCode,
                    CustomerName,
                    CustomerGrade,
                    COALESCE(TotalPurchase, 0) as TotalPurchase,
                    CustomerStatus,
                    COALESCE(ContactAttempts, 0) as ContactAttempts,
                    LastContactDate,
                    DATEDIFF(CURDATE(), COALESCE(LastContactDate, CreatedDate)) as days_since_contact,
                    Sales
                FROM customers 
                WHERE CustomerTemperature = 'FROZEN'
                AND (
                    CustomerGrade IN ('A', 'B') 
                    OR COALESCE(TotalPurchase, 0) >= 5000
                )
                ORDER BY TotalPurchase DESC
                LIMIT 20
            ";
            
            $stmt = $pdo->prepare($highValueSql);
            $stmt->execute();
            $highValueFrozen = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($highValueFrozen)) {
                echo '<div class="alert high-value-frozen">';
                echo '<h6>🚨 ลูกค้าที่มีมูลค่าสูงแต่ถูกทำให้ FROZEN</h6>';
                echo '</div>';
                
                echo '<div class="table-responsive mb-4">';
                echo '<table class="table table-striped">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Customer Code</th>';
                echo '<th>Name</th>';
                echo '<th>Grade</th>';
                echo '<th>Purchase</th>';
                echo '<th>Status</th>';
                echo '<th>Contact Attempts</th>';
                echo '<th>Days Since Contact</th>';
                echo '<th>Sales</th>';
                echo '<th>Should Unfreeze?</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($highValueFrozen as $customer) {
                    $shouldUnfreeze = ($customer['TotalPurchase'] >= 5000 && $customer['ContactAttempts'] < 3);
                    $unfreezeClass = $shouldUnfreeze ? 'text-success' : 'text-warning';
                    $unfreezeText = $shouldUnfreeze ? '✅ ใช่' : '⚠️ ตรวจสอบ';
                    
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($customer['CustomerCode']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($customer['CustomerName']) . '</td>';
                    echo '<td><span class="badge bg-primary">' . $customer['CustomerGrade'] . '</span></td>';
                    echo '<td><strong>฿' . number_format($customer['TotalPurchase']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($customer['CustomerStatus']) . '</td>';
                    echo '<td>' . $customer['ContactAttempts'] . '</td>';
                    echo '<td>' . $customer['days_since_contact'] . ' วัน</td>';
                    echo '<td>' . htmlspecialchars($customer['Sales'] ?: 'ไม่มี') . '</td>';
                    echo '<td class="' . $unfreezeClass . '">' . $unfreezeText . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-success">✅ ไม่พบลูกค้ามูลค่าสูงที่ถูก FROZEN</div>';
            }
            
            // 4. Solutions
            echo '<h4>✅ แก้ไขปัญหา FROZEN</h4>';
            
            echo '<div class="row">';
            
            // Solution 1: Unfreeze high-value customers
            echo '<div class="col-md-4">';
            echo '<div class="alert correct">';
            echo '<h6>🔥 Unfreeze High-Value Customers</h6>';
            echo '<p>ยกเลิก FROZEN สำหรับลูกค้า Grade A/B ที่มีมูลค่าสูง</p>';
            echo '<button class="btn btn-success btn-sm" onclick="unfreezeHighValue()">Unfreeze A/B</button>';
            echo '</div>';
            echo '</div>';
            
            // Solution 2: Fix FROZEN logic
            echo '<div class="col-md-4">';
            echo '<div class="alert correct">';
            echo '<h6>⚙️ Fix FROZEN Logic</h6>';
            echo '<p>แก้ไข logic ให้พิจารณา customer value</p>';
            echo '<button class="btn btn-warning btn-sm" onclick="fixFrozenLogic()">Fix Logic</button>';
            echo '</div>';
            echo '</div>';
            
            // Solution 3: Create proper FROZEN rules
            echo '<div class="col-md-4">';
            echo '<div class="alert correct">';
            echo '<h6>📋 Create Proper Rules</h6>';
            echo '<p>สร้างกฎการ FROZEN ที่ถูกต้อง</p>';
            echo '<button class="btn btn-info btn-sm" onclick="createFrozenRules()">Create Rules</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            echo '<div id="actionResults" class="mt-3"></div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <script>
        function showResult(message, type = 'info') {
            document.getElementById('actionResults').innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        function unfreezeHighValue() {
            if (!confirm('Unfreeze all Grade A/B customers? This will change their temperature to WARM.')) return;
            
            showResult('🔄 Unfreezing high-value customers...', 'info');
            
            fetch('unfreeze_high_value.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Unfrozen:</strong> ${data.unfrozen_count} customers<br><strong>Grades:</strong> ${Object.entries(data.grade_breakdown).map(([grade, count]) => `${grade}: ${count}`).join(', ')}`, 'success');
                        setTimeout(() => location.reload(), 3000);
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function fixFrozenLogic() {
            showResult('🔄 Fixing FROZEN logic...', 'info');
            
            fetch('fix_frozen_logic.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Updated files:</strong><ul>${data.updated_files.map(file => `<li>${file}</li>`).join('')}</ul>`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function createFrozenRules() {
            showResult('🔄 Creating proper FROZEN rules...', 'info');
            
            fetch('create_frozen_rules.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Rules created:</strong><ul>${Object.entries(data.rules_created).map(([rule, desc]) => `<li><strong>${rule}:</strong> ${desc}</li>`).join('')}</ul>`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }
    </script>
</body>
</html>