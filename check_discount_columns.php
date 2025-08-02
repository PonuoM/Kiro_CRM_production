<?php
/**
 * Check Orders Table for Discount Columns
 * Verify if discount fields exist in database
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 ตรวจสอบ Discount Columns</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .missing-column { background-color: #ffebee; }
        .existing-column { background-color: #e8f5e8; }
        .important-column { background-color: #e3f2fd; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🔍 ตรวจสอบ Discount Columns ในตาราง Orders</h2>
        <p class="text-muted">ตรวจสอบว่ามี DiscountAmount, DiscountPercent, DiscountRemarks ในตาราง orders หรือไม่</p>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Get orders table structure
            $stmt = $pdo->query("DESCRIBE orders");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="row">';
            
            // Show current orders table structure
            echo '<div class="col-md-8">';
            echo '<div class="card">';
            echo '<div class="card-header"><h5>🗄️ โครงสร้างตาราง Orders ปัจจุบัน</h5></div>';
            echo '<div class="card-body">';
            echo '<div style="max-height: 500px; overflow-y: auto;">';
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
            echo '<tbody>';
            
            $discountFields = [];
            $hasDiscountAmount = false;
            $hasDiscountPercent = false;
            $hasDiscountRemarks = false;
            
            foreach ($columns as $column) {
                $field = $column['Field'];
                
                // Check for discount-related fields
                if (stripos($field, 'discount') !== false) {
                    $discountFields[] = $field;
                    $rowClass = 'existing-column';
                    
                    if ($field === 'DiscountAmount') $hasDiscountAmount = true;
                    if ($field === 'DiscountPercent') $hasDiscountPercent = true;
                    if ($field === 'DiscountRemarks') $hasDiscountRemarks = true;
                } else {
                    $rowClass = '';
                }
                
                echo "<tr class=\"$rowClass\">";
                echo "<td><strong>$field</strong></td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // Analysis panel
            echo '<div class="col-md-4">';
            echo '<div class="card">';
            echo '<div class="card-header"><h5>📊 การวิเคราะห์</h5></div>';
            echo '<div class="card-body">';
            
            echo '<h6>💰 Discount Fields Status:</h6>';
            $requiredDiscountFields = [
                'DiscountAmount' => 'จำนวนส่วนลด (บาท)',
                'DiscountPercent' => 'เปอร์เซ็นต์ส่วนลด (%)', 
                'DiscountRemarks' => 'หมายเหตุส่วนลด'
            ];
            
            echo '<ul>';
            foreach ($requiredDiscountFields as $field => $desc) {
                $exists = in_array($field, $discountFields);
                $icon = $exists ? '✅' : '❌';
                $class = $exists ? 'text-success' : 'text-danger';
                echo "<li class=\"$class\">$icon <strong>$field:</strong> $desc</li>";
            }
            echo '</ul>';
            
            if (!empty($discountFields)) {
                echo '<h6 class="mt-3">✅ Discount Fields ที่พบ:</h6>';
                echo '<ul>';
                foreach ($discountFields as $field) {
                    echo "<li class=\"text-success\">✅ <strong>$field</strong></li>";
                }
                echo '</ul>';
            }
            
            echo '<h6 class="mt-3">🔧 การแก้ไข:</h6>';
            $missingFields = [];
            if (!$hasDiscountAmount) $missingFields[] = 'DiscountAmount DECIMAL(10,2) DEFAULT 0.00';
            if (!$hasDiscountPercent) $missingFields[] = 'DiscountPercent DECIMAL(5,2) DEFAULT 0.00';
            if (!$hasDiscountRemarks) $missingFields[] = 'DiscountRemarks TEXT';
            
            if (!empty($missingFields)) {
                echo '<div class="alert alert-warning">';
                echo '<strong>⚠️ ต้องเพิ่ม Columns:</strong><br>';
                foreach ($missingFields as $field) {
                    echo "• $field<br>";
                }
                echo '</div>';
                
                echo '<button class="btn btn-danger" onclick="addDiscountColumns()">🔧 เพิ่ม Discount Columns</button>';
            } else {
                echo '<div class="alert alert-success">';
                echo '<strong>✅ Discount Columns ครบถ้วนแล้ว!</strong>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Test current order data
            echo '<h4 class="mt-4">📋 ทดสอบข้อมูล Orders ปัจจุบัน</h4>';
            
            echo '<div class="card">';
            echo '<div class="card-header"><h6>🔍 ตัวอย่างข้อมูล Orders (5 รายการล่าสุด)</h6></div>';
            echo '<div class="card-body">';
            
            // Get sample order data
            $sampleSql = "SELECT DocumentNo, CustomerCode, Products, Quantity, Price, SubtotalAmount";
            if ($hasDiscountAmount) $sampleSql .= ", DiscountAmount";
            if ($hasDiscountPercent) $sampleSql .= ", DiscountPercent";
            if ($hasDiscountRemarks) $sampleSql .= ", DiscountRemarks";
            $sampleSql .= " FROM orders ORDER BY CreatedDate DESC LIMIT 5";
            
            $stmt = $pdo->prepare($sampleSql);
            $stmt->execute();
            $sampleOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sampleOrders)) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-sm table-striped">';
                echo '<thead class="table-dark"><tr>';
                
                // Dynamic headers based on available columns
                $headers = ['DocumentNo', 'CustomerCode', 'Products', 'Quantity', 'Price', 'SubtotalAmount'];
                if ($hasDiscountAmount) $headers[] = 'DiscountAmount';
                if ($hasDiscountPercent) $headers[] = 'DiscountPercent';
                if ($hasDiscountRemarks) $headers[] = 'DiscountRemarks';
                
                foreach ($headers as $header) {
                    echo "<th>$header</th>";
                }
                echo '</tr></thead><tbody>';
                
                foreach ($sampleOrders as $order) {
                    echo '<tr>';
                    foreach ($headers as $header) {
                        $value = $order[$header] ?? '-';
                        if (in_array($header, ['DiscountAmount', 'DiscountPercent']) && $value == 0) {
                            echo "<td class=\"text-warning\">$value</td>";
                        } else {
                            echo "<td>$value</td>";
                        }
                    }
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            } else {
                echo '<div class="alert alert-info">ไม่มีข้อมูล Orders</div>';
            }
            
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

        function addDiscountColumns() {
            if (!confirm('เพิ่ม Discount Columns ในตาราง orders?\n\nจะเพิ่ม:\n- DiscountAmount DECIMAL(10,2)\n- DiscountPercent DECIMAL(5,2)\n- DiscountRemarks TEXT')) return;
            
            showResult('🔄 กำลังเพิ่ม Discount Columns...', 'info');
            
            fetch('add_discount_columns.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({action: 'add_columns'})
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showResult(`✅ ${data.message}`, 'success');
                    setTimeout(() => location.reload(), 2000);
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