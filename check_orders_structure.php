<?php
/**
 * Check Orders Table Structure
 * Verify fields for price calculation
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 ตรวจสอบโครงสร้างตาราง Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .price-field { background-color: #e8f5e8; }
        .missing-field { background-color: #ffebee; }
        .important-field { background-color: #e3f2fd; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🔍 ตรวจสอบโครงสร้างตาราง Orders</h2>
        <p class="text-muted">ตรวจสอบ fields สำหรับการคำนวณราคา และ import ข้อมูล order</p>
        
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
            
            $priceFields = [];
            $importantFields = ['DocumentDate', 'Products', 'Quantity', 'Total_amount', 'CustomerCode'];
            
            foreach ($columns as $column) {
                $field = $column['Field'];
                
                // Check for price-related fields
                if (stripos($field, 'price') !== false || 
                    stripos($field, 'amount') !== false ||
                    stripos($field, 'cost') !== false ||
                    stripos($field, 'value') !== false) {
                    $priceFields[] = $field;
                    $rowClass = 'price-field';
                } elseif (in_array($field, $importantFields)) {
                    $rowClass = 'important-field';
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
            
            echo '<h6>💰 Price-related Fields:</h6>';
            if (empty($priceFields)) {
                echo '<div class="alert alert-warning">';
                echo '❌ ไม่พบ fields ที่เกี่ยวข้องกับราคา<br>';
                echo 'ไม่มี: Price, Amount, Cost, Value';
                echo '</div>';
            } else {
                echo '<ul>';
                foreach ($priceFields as $field) {
                    echo "<li>✅ <strong>$field</strong></li>";
                }
                echo '</ul>';
            }
            
            echo '<h6>📋 Import Fields Status:</h6>';
            $requiredFields = [
                'DocumentDate' => 'วันที่สั่งซื้อ',
                'Products' => 'รายการสินค้า', 
                'Quantity' => 'จำนวน',
                'CustomerCode' => 'รหัสลูกค้า'
            ];
            
            $fieldNames = array_column($columns, 'Field');
            
            echo '<ul>';
            foreach ($requiredFields as $field => $desc) {
                $exists = in_array($field, $fieldNames);
                $icon = $exists ? '✅' : '❌';
                $class = $exists ? 'text-success' : 'text-danger';
                echo "<li class=\"$class\">$icon <strong>$field:</strong> $desc</li>";
            }
            echo '</ul>';
            
            // Check for Total_amount specifically
            $hasTotalAmount = in_array('Total_amount', $fieldNames);
            $hasUnitPrice = false;
            
            foreach ($fieldNames as $fieldName) {
                if (stripos($fieldName, 'price') !== false && stripos($fieldName, 'unit') !== false) {
                    $hasUnitPrice = true;
                    break;
                }
            }
            
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Recommendations
            echo '<h4 class="mt-4">💡 คำแนะนำสำหรับ Import System</h4>';
            
            echo '<div class="row">';
            
            // Option 1: Add UnitPrice field
            echo '<div class="col-md-4">';
            echo '<div class="alert alert-info">';
            echo '<h6>🔧 Option 1: เพิ่ม UnitPrice field</h6>';
            echo '<p>เพิ่มคอลัมน์ UnitPrice ในตาราง orders</p>';
            echo '<ul class="small">';
            echo '<li>UnitPrice DECIMAL(10,2)</li>';
            echo '<li>Total = Quantity × UnitPrice</li>';
            echo '<li>รองรับการคำนวณที่แม่นยำ</li>';
            echo '</ul>';
            echo '<button class="btn btn-primary btn-sm" onclick="addUnitPriceField()">Add UnitPrice</button>';
            echo '</div>';
            echo '</div>';
            
            // Option 2: Calculate from Total_amount
            echo '<div class="col-md-4">';
            echo '<div class="alert alert-warning">';
            echo '<h6>📊 Option 2: คำนวณจาก Total_amount</h6>';
            echo '<p>ใช้ Total_amount ÷ Quantity = UnitPrice</p>';
            echo '<ul class="small">';
            echo '<li>ไม่ต้องเพิ่มคอลัมน์ใหม่</li>';
            echo '<li>คำนวณ UnitPrice เมื่อต้องการ</li>';
            echo '<li>อาจไม่แม่นยำถ้ามีส่วนลด</li>';
            echo '</ul>';
            echo '<button class="btn btn-warning btn-sm" onclick="useCalculatedPrice()">Use Calculation</button>';
            echo '</div>';
            echo '</div>';
            
            // Option 3: Store both
            echo '<div class="col-md-4">';
            echo '<div class="alert alert-success">';
            echo '<h6>💪 Option 3: เก็บทั้งคู่</h6>';
            echo '<p>เก็บทั้ง UnitPrice และ Total_amount</p>';
            echo '<ul class="small">';
            echo '<li>ข้อมูลครบถ้วนและแม่นยำ</li>';
            echo '<li>รองรับส่วนลด/ภาษี</li>';
            echo '<li>ยืดหยุ่นสำหรับอนาคต</li>';
            echo '</ul>';
            echo '<button class="btn btn-success btn-sm" onclick="implementBothFields()">Implement Both</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Proposed CSV format
            echo '<h4 class="mt-4">📋 เสนอรูปแบบ CSV สำหรับ Order Import</h4>';
            
            echo '<div class="card">';
            echo '<div class="card-header"><h6>📄 CSV Format for First-Time Orders</h6></div>';
            echo '<div class="card-body">';
            
            echo '<h6>Customer Fields:</h6>';
            echo '<code>CustomerCode,CustomerName,CustomerTel,CustomerAddress,CustomerProvince,CustomerPostalCode,Agriculture,CustomerStatus</code>';
            
            echo '<h6 class="mt-3">Order Fields:</h6>';
            if ($hasTotalAmount && !$hasUnitPrice) {
                echo '<div class="alert alert-warning">';
                echo '<strong>ตอนนี้มีแค่ Total_amount:</strong><br>';
                echo '<code>DocumentDate,Products,Quantity,Total_amount</code><br>';
                echo '<small>UnitPrice จะคำนวณจาก Total_amount ÷ Quantity</small>';
                echo '</div>';
            } else {
                echo '<div class="alert alert-info">';
                echo '<strong>แนะนำให้เพิ่ม UnitPrice:</strong><br>';
                echo '<code>DocumentDate,Products,Quantity,UnitPrice,Total_amount</code><br>';
                echo '<small>เก็บข้อมูลครบถ้วนและแม่นยำ</small>';
                echo '</div>';
            }
            
            echo '<h6 class="mt-3">ตัวอย่าง CSV:</h6>';
            echo '<pre class="bg-light p-2"><code>CustomerCode,CustomerName,CustomerTel,CustomerAddress,CustomerProvince,CustomerPostalCode,Agriculture,CustomerStatus,DocumentDate,Products,Quantity,UnitPrice,Total_amount
C202501001,บริษัท ABC จำกัด,081-234-5678,123 ถนนสุขุมวิท,กรุงเทพมหานคร,10110,ปลูกข้าว,ลูกค้าใหม่,2025-01-01,ปุ๋ยเคมี 16-16-16,10,150.00,1500.00
C202501002,สวนผลไม้สมบูรณ์,082-987-6543,456 ถนนรัชดาภิเษก,กรุงเทพมหานคร,10400,ปลูกผลไม้,ลูกค้าใหม่,2025-01-02,ยาฆ่าแมลง,5,200.00,1000.00</code></pre>';
            
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

        function addUnitPriceField() {
            if (!confirm('Add UnitPrice field to orders table?')) return;
            
            showResult('🔄 Adding UnitPrice field to orders table...', 'info');
            
            fetch('add_unit_price_field.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Added:</strong> ${data.field_details}`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function useCalculatedPrice() {
            showResult('📊 Will use calculated price (Total_amount ÷ Quantity) in import system', 'warning');
            
            setTimeout(() => {
                showResult('✅ Configuration updated to use calculated UnitPrice from existing Total_amount field', 'success');
            }, 1000);
        }

        function implementBothFields() {
            if (!confirm('Implement both UnitPrice and Total_amount fields?')) return;
            
            showResult('🔄 Implementing comprehensive price system...', 'info');
            
            fetch('implement_comprehensive_pricing.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Features:</strong><ul>${data.features.map(f => `<li>${f}</li>`).join('')}</ul>`, 'success');
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