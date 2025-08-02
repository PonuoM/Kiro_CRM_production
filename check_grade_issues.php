<?php
/**
 * Check Customer Grade Issues
 * Find customers with incorrect grades based on purchase amounts
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Check Customer Grade Issues</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .issue { background-color: #fff3cd; border-left: 4px solid #ff6b6b; }
        .correct { background-color: #d4edda; border-left: 4px solid #28a745; }
        .high-value { background-color: #e3f2fd; border-left: 4px solid #2196F3; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🔍 Customer Grade Issues Analysis</h2>
        <p class="text-muted">ตรวจสอบลูกค้าที่มียอดขายเกินเกณฑ์แต่ยัง Grade D</p>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Grade criteria (from intelligence-safe.php)
            $gradeCriteria = [
                'A' => 10000,  // VIP Customer
                'B' => 5000,   // Premium Customer
                'C' => 2000,   // Regular Customer
                'D' => 0       // New Customer
            ];
            
            echo '<div class="row mb-4">';
            echo '<div class="col-md-12">';
            echo '<div class="alert alert-info">';
            echo '<h6>📊 Grade Criteria:</h6>';
            echo '<ul class="mb-0">';
            echo '<li><strong>Grade A:</strong> ≥ ฿' . number_format($gradeCriteria['A']) . ' (VIP Customer)</li>';
            echo '<li><strong>Grade B:</strong> ≥ ฿' . number_format($gradeCriteria['B']) . ' (Premium Customer)</li>';
            echo '<li><strong>Grade C:</strong> ≥ ฿' . number_format($gradeCriteria['C']) . ' (Regular Customer)</li>';
            echo '<li><strong>Grade D:</strong> < ฿' . number_format($gradeCriteria['C']) . ' (New Customer)</li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // 1. Find customers with high purchase but Grade D
            echo '<h4>❌ Problem: High Purchase Amount but Grade D</h4>';
            
            $sql = "
                SELECT 
                    CustomerCode, 
                    CustomerName, 
                    CustomerGrade, 
                    COALESCE(TotalPurchase, 0) as TotalPurchase,
                    CustomerStatus,
                    CustomerTemperature,
                    GradeCalculatedDate,
                    ModifiedDate,
                    ModifiedBy,
                    CASE 
                        WHEN COALESCE(TotalPurchase, 0) >= 10000 THEN 'A'
                        WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN 'B'
                        WHEN COALESCE(TotalPurchase, 0) >= 2000 THEN 'C'
                        ELSE 'D'
                    END as correct_grade
                FROM customers 
                WHERE COALESCE(TotalPurchase, 0) > 2000 
                AND COALESCE(CustomerGrade, 'D') = 'D'
                ORDER BY TotalPurchase DESC
                LIMIT 20
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($issues)) {
                echo '<div class="alert alert-success">✅ ไม่พบปัญหา: ทุก Grade D มียอดขาย < ฿2,000</div>';
            } else {
                echo '<div class="alert issue">';
                echo '<h6>🚨 พบปัญหา: ' . count($issues) . ' ลูกค้ามียอดขายเกินเกณฑ์แต่ยัง Grade D</h6>';
                echo '</div>';
                
                echo '<div class="table-responsive mb-4">';
                echo '<table class="table table-striped">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Customer Code</th>';
                echo '<th>Customer Name</th>';
                echo '<th>Current Grade</th>';
                echo '<th>Purchase Amount</th>';
                echo '<th>Correct Grade</th>';
                echo '<th>Status</th>';
                echo '<th>Temperature</th>';
                echo '<th>Last Modified</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($issues as $issue) {
                    $purchaseAmount = floatval($issue['TotalPurchase']);
                    $currentGrade = $issue['CustomerGrade'] ?: 'D';
                    $correctGrade = $issue['correct_grade'];
                    
                    echo '<tr>';
                    echo '<td><code>' . htmlspecialchars($issue['CustomerCode']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($issue['CustomerName']) . '</td>';
                    echo '<td><span class="badge bg-danger">' . $currentGrade . '</span></td>';
                    echo '<td><strong>฿' . number_format($purchaseAmount) . '</strong></td>';
                    echo '<td><span class="badge bg-success">' . $correctGrade . '</span></td>';
                    echo '<td>' . htmlspecialchars($issue['CustomerStatus']) . '</td>';
                    echo '<td>' . htmlspecialchars($issue['CustomerTemperature']) . '</td>';
                    echo '<td>' . ($issue['ModifiedDate'] ? date('Y-m-d', strtotime($issue['ModifiedDate'])) : 'N/A') . '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
                echo '</div>';
            }
            
            // 2. Root cause analysis
            echo '<h4>🔍 Root Cause Analysis</h4>';
            
            // Check auto-update logic conflicts
            $autoUpdateSql = "
                SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN CustomerStatus = 'ในตระกร้า' OR CustomerTemperature = 'FROZEN' THEN 1 END) as forced_d_by_status,
                    COUNT(CASE WHEN COALESCE(TotalPurchase, 0) > 2000 AND (CustomerStatus = 'ในตระกร้า' OR CustomerTemperature = 'FROZEN') THEN 1 END) as high_purchase_forced_d
                FROM customers 
                WHERE COALESCE(CustomerGrade, 'D') = 'D'
            ";
            
            $stmt = $pdo->prepare($autoUpdateSql);
            $stmt->execute();
            $rootCause = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<div class="row">';
            
            // Cause 1: Auto-update logic overriding purchase-based grades
            echo '<div class="col-md-6">';
            echo '<div class="alert issue">';
            echo '<h6>❌ Possible Cause 1: Auto-Update Logic Override</h6>';
            echo '<p>System auto-update (production_auto_system.php) กำหนด Grade ตาม Status/Temperature โดยไม่พิจารณา TotalPurchase:</p>';
            echo '<pre style="font-size: 11px;">CustomerGrade = CASE 
    WHEN CustomerStatus = \'ในตระกร้า\' OR CustomerTemperature = \'FROZEN\' THEN \'D\'
    WHEN CustomerStatus = \'ลูกค้าใหม่\' AND CustomerTemperature IN (\'HOT\', \'WARM\') THEN \'A\'
    ...
END</pre>';
            echo '<p><strong>Impact:</strong> ' . $rootCause['high_purchase_forced_d'] . ' ลูกค้ายอดสูงถูกบังคับเป็น Grade D</p>';
            echo '</div>';
            echo '</div>';
            
            // Cause 2: Missing TotalPurchase calculation
            echo '<div class="col-md-6">';
            echo '<div class="alert issue">';
            echo '<h6>❌ Possible Cause 2: TotalPurchase Not Updated</h6>';
            
            $missingPurchaseSql = "
                SELECT COUNT(*) as customers_with_zero_purchase
                FROM customers 
                WHERE COALESCE(TotalPurchase, 0) = 0 
                AND CustomerStatus != 'ลูกค้าใหม่'
            ";
            $stmt = $pdo->prepare($missingPurchaseSql);
            $stmt->execute();
            $missingPurchase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo '<p>TotalPurchase field ไม่ได้รับการอัพเดทจาก order history หรือ sales records</p>';
            echo '<p><strong>Impact:</strong> ' . $missingPurchase['customers_with_zero_purchase'] . ' ลูกค้าเก่าแต่ TotalPurchase = 0</p>';
            echo '<button class="btn btn-warning btn-sm" onclick="calculatePurchases()">🔧 Calculate TotalPurchase</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // 3. Solutions
            echo '<h4>✅ Solutions</h4>';
            
            echo '<div class="row">';
            
            // Solution 1: Fix Grade Logic
            echo '<div class="col-md-4">';
            echo '<div class="alert correct">';
            echo '<h6>🔧 Solution 1: Fix Grade Logic</h6>';
            echo '<p>แก้ไข auto-update logic ให้พิจารณา TotalPurchase ด้วย</p>';
            echo '<button class="btn btn-success btn-sm" onclick="fixGradeLogic()">Apply Fix</button>';
            echo '</div>';
            echo '</div>';
            
            // Solution 2: Update TotalPurchase
            echo '<div class="col-md-4">';
            echo '<div class="alert correct">';
            echo '<h6>📊 Solution 2: Update TotalPurchase</h6>';
            echo '<p>คำนวณ TotalPurchase จาก order history</p>';
            echo '<button class="btn btn-success btn-sm" onclick="updateTotalPurchase()">Update Amounts</button>';
            echo '</div>';
            echo '</div>';
            
            // Solution 3: Manual Grade Fix
            echo '<div class="col-md-4">';
            echo '<div class="alert correct">';
            echo '<h6>🎯 Solution 3: Fix Current Issues</h6>';
            echo '<p>แก้ไข Grade ที่ผิดพลาดในปัจจุบัน</p>';
            echo '<button class="btn btn-success btn-sm" onclick="fixCurrentGrades()">Fix Grades</button>';
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

        function calculatePurchases() {
            showResult('🔄 Calculating TotalPurchase from order history...', 'info');
            
            fetch('calculate_total_purchase.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Updated:</strong> ${data.updated_customers} customers`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function fixGradeLogic() {
            showResult('🔄 Fixing grade calculation logic...', 'info');
            
            fetch('fix_grade_logic.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Details:</strong> ${data.details}`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function updateTotalPurchase() {
            if (!confirm('This will recalculate TotalPurchase for all customers. Continue?')) return;
            
            showResult('🔄 Updating TotalPurchase from sales records...', 'info');
            
            fetch('update_total_purchase.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Updated:</strong> ${data.updated_count} customers<br><strong>Total Revenue:</strong> ฿${data.total_revenue}`, 'success');
                        setTimeout(() => location.reload(), 3000);
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function fixCurrentGrades() {
            if (!confirm('This will fix all incorrect grades based on TotalPurchase. Continue?')) return;
            
            showResult('🔄 Fixing current grade issues...', 'info');
            
            fetch('fix_current_grades.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Fixed:</strong> ${data.fixed_count} customers<br><strong>Details:</strong><ul>${Object.entries(data.grade_changes).map(([grade, count]) => `<li>Changed to Grade ${grade}: ${count} customers</li>`).join('')}</ul>`, 'success');
                        setTimeout(() => location.reload(), 3000);
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