<?php
/**
 * Debug CartStatus Logic Issues
 * Analyze CartStatus and Sales assignment problems
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug CartStatus Logic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .problem { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .good { background-color: #d1edff; border-left: 4px solid #0066cc; }
        .error { background-color: #f8d7da; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🔍 Debug CartStatus Logic Issues</h2>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // 1. ตรวจสอบปัญหา: ลูกค้าแจกแล้ว แต่ไม่มี Sales
            echo '<h4>❌ Problem 1: ลูกค้าแจกแล้ว แต่ไม่มี Sales</h4>';
            $stmt = $pdo->prepare("
                SELECT CustomerCode, CustomerName, CartStatus, Sales, ModifiedDate 
                FROM customers 
                WHERE CartStatus = 'ลูกค้าแจกแล้ว' AND (Sales IS NULL OR Sales = '')
                ORDER BY ModifiedDate DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $problematicCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($problematicCustomers) > 0) {
                echo '<div class="alert error">';
                echo '<strong>พบ ' . count($problematicCustomers) . ' ลูกค้าที่มีปัญหา:</strong>';
                echo '<div class="table-responsive mt-2">';
                echo '<table class="table table-sm">';
                echo '<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>CartStatus</th><th>Sales</th><th>ModifiedDate</th></tr></thead>';
                foreach ($problematicCustomers as $customer) {
                    echo "<tr>";
                    echo "<td><strong>{$customer['CustomerCode']}</strong></td>";
                    echo "<td>{$customer['CustomerName']}</td>";
                    echo "<td><span class=\"badge bg-warning\">{$customer['CartStatus']}</span></td>";
                    echo "<td><span class=\"text-danger\">" . ($customer['Sales'] ?: 'NULL') . "</span></td>";
                    echo "<td>{$customer['ModifiedDate']}</td>";
                    echo "</tr>";
                }
                echo '</table></div></div>';
            } else {
                echo '<div class="alert alert-success">✅ ไม่พบปัญหานี้</div>';
            }
            
            // 2. ตรวจสอบปัญหา: ลูกค้าที่มี Sales แต่ CartStatus ยัง "ตะกร้าแจก"
            echo '<h4>❌ Problem 2: ลูกค้ามี Sales แต่ CartStatus ยัง "ตะกร้าแจก"</h4>';
            $stmt = $pdo->prepare("
                SELECT CustomerCode, CustomerName, CartStatus, Sales, ModifiedDate 
                FROM customers 
                WHERE CartStatus = 'ตะกร้าแจก' AND Sales IS NOT NULL AND Sales != ''
                ORDER BY ModifiedDate DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $conflictCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($conflictCustomers) > 0) {
                echo '<div class="alert problem">';
                echo '<strong>พบ ' . count($conflictCustomers) . ' ลูกค้าที่ขัดแย้ง:</strong>';
                echo '<div class="table-responsive mt-2">';
                echo '<table class="table table-sm">';
                echo '<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>CartStatus</th><th>Sales</th><th>ModifiedDate</th></tr></thead>';
                foreach ($conflictCustomers as $customer) {
                    echo "<tr>";
                    echo "<td><strong>{$customer['CustomerCode']}</strong></td>";
                    echo "<td>{$customer['CustomerName']}</td>";
                    echo "<td><span class=\"badge bg-info\">{$customer['CartStatus']}</span></td>";
                    echo "<td><span class=\"text-success\">{$customer['Sales']}</span></td>";
                    echo "<td>{$customer['ModifiedDate']}</td>";
                    echo "</tr>";
                }
                echo '</table></div></div>';
            } else {
                echo '<div class="alert alert-success">✅ ไม่พบปัญหานี้</div>';
            }
            
            // 3. สถิติทั้งหมด
            echo '<h4>📊 CartStatus Statistics</h4>';
            $stmt = $pdo->prepare("
                SELECT 
                    CartStatus,
                    COUNT(*) as count,
                    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 END) as with_sales,
                    COUNT(CASE WHEN Sales IS NULL OR Sales = '' THEN 1 END) as without_sales
                FROM customers 
                GROUP BY CartStatus
                ORDER BY count DESC
            ");
            $stmt->execute();
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>CartStatus</th><th>Total</th><th>With Sales</th><th>Without Sales</th><th>Issues</th></tr></thead>';
            foreach ($stats as $stat) {
                $issues = '';
                if ($stat['CartStatus'] === 'ลูกค้าแจกแล้ว' && $stat['without_sales'] > 0) {
                    $issues = "❌ {$stat['without_sales']} missing sales";
                } elseif ($stat['CartStatus'] === 'ตะกร้าแจก' && $stat['with_sales'] > 0) {
                    $issues = "⚠️ {$stat['with_sales']} has sales";
                }
                
                echo "<tr>";
                echo "<td><strong>{$stat['CartStatus']}</strong></td>";
                echo "<td>{$stat['count']}</td>";
                echo "<td>{$stat['with_sales']}</td>";
                echo "<td>{$stat['without_sales']}</td>";
                echo "<td>{$issues}</td>";
                echo "</tr>";
            }
            echo '</table></div>';
            
            // 4. ตรวจสอบ Sales Users ที่ใช้งานได้
            echo '<h4>👥 Active Sales Users</h4>';
            $stmt = $pdo->prepare("
                SELECT Username, FirstName, LastName, Status, 
                       (SELECT COUNT(*) FROM customers WHERE Sales = users.Username) as assigned_count
                FROM users 
                WHERE Role = 'Sales'
                ORDER BY Status DESC, assigned_count DESC
            ");
            $stmt->execute();
            $salesUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Username</th><th>Name</th><th>Status</th><th>Assigned Customers</th><th>API Valid</th></tr></thead>';
            foreach ($salesUsers as $user) {
                $statusClass = $user['Status'] ? 'text-success' : 'text-danger';
                $statusText = $user['Status'] ? 'Active' : 'Inactive';
                $apiValid = $user['Status'] ? '✅' : '❌';
                
                echo "<tr>";
                echo "<td><strong>{$user['Username']}</strong></td>";
                echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
                echo "<td><span class=\"{$statusClass}\">{$statusText}</span></td>";
                echo "<td>{$user['assigned_count']}</td>";
                echo "<td>{$apiValid}</td>";
                echo "</tr>";
            }
            echo '</table></div>';
            
            // 5. แสดงปัญหาที่ต้องแก้ไข
            echo '<h4>🔧 Required Fixes</h4>';
            echo '<div class="row">';
            echo '<div class="col-md-4">';
            echo '<div class="alert alert-warning">';
            echo '<h6>Fix 1: Update CartStatus</h6>';
            echo '<p>ลูกค้าที่มี Sales แล้วต้องเปลี่ยน CartStatus เป็น "ลูกค้าแจกแล้ว"</p>';
            echo '<button class="btn btn-warning btn-sm" onclick="fixCartStatus()">Fix CartStatus</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="alert alert-danger">';
            echo '<h6>Fix 2: Assignment API</h6>';
            echo '<p>แก้ไข API ให้ตรวจสอบ Username ที่ถูกต้อง</p>';
            echo '<button class="btn btn-danger btn-sm" onclick="testAssignment()">Test Assignment</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="alert alert-info">';
            echo '<h6>Fix 3: Logic Update</h6>';
            echo '<p>อัพเดท Logic การแสดงผลในหน้าต่างๆ</p>';
            echo '<button class="btn btn-info btn-sm" onclick="refreshData()">Refresh Data</button>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div id="fixResults" class="mt-3"></div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>

    <script>
        function showResult(message, type = 'info') {
            document.getElementById('fixResults').innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        }

        function fixCartStatus() {
            showResult('🔄 Fixing CartStatus...', 'info');
            
            fetch('fix_cartstatus_logic.php?action=fix_cartstatus')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ Fixed ${data.updated} customers`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function testAssignment() {
            showResult('🔄 Testing assignment...', 'info');
            // Implementation for testing assignment
            showResult('🔧 Assignment test feature coming soon', 'warning');
        }

        function refreshData() {
            location.reload();
        }
    </script>
</body>
</html>