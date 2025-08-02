<?php
/**
 * Check Import Columns Comparison
 * Compare CSV import columns with actual customers table structure
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 ตรวจสอบ Columns การนำเข้าลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .missing { background-color: #ffebee; border-left: 4px solid #f44336; }
        .available { background-color: #e8f5e8; border-left: 4px solid #4caf50; }
        .partial { background-color: #fff3e0; border-left: 4px solid #ff9800; }
        .column-table th { background-color: #f8f9fa; }
        .important-field { background-color: #e3f2fd; }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>🔍 ตรวจสอบ Columns การนำเข้าลูกค้า</h2>
        <p class="text-muted">เปรียบเทียบ columns ที่ระบบรองรับ VS ตาราง customers จริง</p>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Get actual customers table structure
            $tableStructure = $pdo->query("DESCRIBE customers")->fetchAll(PDO::FETCH_ASSOC);
            
            // Import API supported columns (from import.php)
            $importApiColumns = [
                'customer_name' => 'CustomerName',
                'customer_tel' => 'CustomerTel',
                'customer_email' => 'CustomerEmail',
                'customer_address' => 'CustomerAddress',
                'customer_status' => 'CustomerStatus',
                'customer_province' => 'CustomerProvince'
            ];
            
            // Extended columns from CSV processing function
            $extendedColumns = [
                'CallDate', 'CallTime', 'CallStatus', 'CustomerStatus', 'Note', 'Agriculture',
                'LastOrderDate', 'PaymentMethod', 'Products', 'Quantity', 'Price', 
                'CustomerName', 'CustomerTel', 'CustomerAddress', 'CustomerProvince', 'CustomerPostalCode'
            ];
            
            // Create database columns map
            $dbColumns = [];
            foreach ($tableStructure as $column) {
                $dbColumns[$column['Field']] = [
                    'field' => $column['Field'],
                    'type' => $column['Type'],
                    'null' => $column['Null'],
                    'key' => $column['Key'],
                    'default' => $column['Default'],
                    'extra' => $column['Extra']
                ];
            }
            
            echo '<div class="row">';
            
            // Show current import CSV format
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header"><h5>📄 รูปแบบ CSV ปัจจุบัน (หน้า Import)</h5></div>';
            echo '<div class="card-body">';
            echo '<table class="table table-sm column-table">';
            echo '<thead><tr><th>CSV Column</th><th>Maps To</th><th>Status</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($importApiColumns as $csvCol => $dbCol) {
                $exists = isset($dbColumns[$dbCol]);
                $statusClass = $exists ? 'text-success' : 'text-danger';
                $statusIcon = $exists ? '✅' : '❌';
                $statusText = $exists ? 'มีในตาราง' : 'ไม่มีในตาราง';
                
                echo "<tr>";
                echo "<td><code>$csvCol</code></td>";
                echo "<td><code>$dbCol</code></td>";
                echo "<td class=\"$statusClass\">$statusIcon $statusText</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // Show actual database structure
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header"><h5>🗄️ โครงสร้างตาราง customers จริง</h5></div>';
            echo '<div class="card-body">';
            echo '<div style="max-height: 400px; overflow-y: auto;">';
            echo '<table class="table table-sm column-table">';
            echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Import Support</th></tr></thead>';
            echo '<tbody>';
            
            $importedFields = array_values($importApiColumns);
            foreach ($tableStructure as $column) {
                $field = $column['Field'];
                $isImported = in_array($field, $importedFields);
                $importClass = $isImported ? 'text-success' : 'text-warning';
                $importIcon = $isImported ? '✅' : '⚠️';
                $importText = $isImported ? 'รองรับ' : 'ไม่รองรับ';
                
                // Highlight important fields
                $rowClass = '';
                if (in_array($field, ['CustomerCode', 'CustomerName', 'CustomerTel', 'CustomerGrade', 'CustomerTemperature', 'TotalPurchase', 'Sales'])) {
                    $rowClass = 'important-field';
                }
                
                echo "<tr class=\"$rowClass\">";
                echo "<td><strong>$field</strong></td>";
                echo "<td><small>{$column['Type']}</small></td>";
                echo "<td><small>{$column['Null']}</small></td>";
                echo "<td><small>{$column['Key']}</small></td>";
                echo "<td class=\"$importClass\">$importIcon $importText</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Analysis of missing fields
            echo '<h4 class="mt-4">📊 การวิเคราะห์ Fields ที่ขาดหายไป</h4>';
            
            $missingImportant = [];
            $availableButNotUsed = [];
            
            $importantFields = [
                'CustomerGrade' => 'เกรดลูกค้า (A, B, C, D)',
                'CustomerTemperature' => 'ระดับความสนใจ (HOT, WARM, COLD, FROZEN)',
                'TotalPurchase' => 'ยอดซื้อรวม',
                'Sales' => 'พนักงานขายที่ดูแล',
                'ContactAttempts' => 'จำนวนครั้งที่ติดต่อ',
                'LastContactDate' => 'วันที่ติดต่อล่าสุด',
                'CreatedDate' => 'วันที่สร้างข้อมูล',
                'ModifiedDate' => 'วันที่แก้ไขล่าสุด',
                'CreatedBy' => 'ผู้สร้าง',
                'ModifiedBy' => 'ผู้แก้ไข'
            ];
            
            foreach ($importantFields as $field => $description) {
                if (isset($dbColumns[$field]) && !in_array($field, $importedFields)) {
                    $missingImportant[$field] = $description;
                }
            }
            
            echo '<div class="row">';
            
            // Missing important fields
            if (!empty($missingImportant)) {
                echo '<div class="col-md-6">';
                echo '<div class="alert missing">';
                echo '<h6>❌ Fields สำคัญที่ไม่รองรับการ Import</h6>';
                echo '<ul class="mb-0">';
                foreach ($missingImportant as $field => $desc) {
                    echo "<li><strong>$field:</strong> $desc</li>";
                }
                echo '</ul>';
                echo '</div>';
                echo '</div>';
            }
            
            // Summary statistics
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header"><h6>📈 สถิติ</h6></div>';
            echo '<div class="card-body">';
            echo '<ul class="list-unstyled">';
            echo '<li><strong>จำนวน Columns ในตาราง:</strong> ' . count($tableStructure) . '</li>';
            echo '<li><strong>จำนวน Columns ที่รองรับ Import:</strong> ' . count($importApiColumns) . '</li>';
            echo '<li><strong>เปอร์เซ็นต์ที่รองรับ:</strong> ' . round((count($importApiColumns) / count($tableStructure)) * 100, 1) . '%</li>';
            echo '<li class="text-danger"><strong>Fields สำคัญที่ขาด:</strong> ' . count($missingImportant) . '</li>';
            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
            
            // Recommendations
            echo '<h4 class="mt-4">✅ คำแนะนำการปรับปรุง</h4>';
            
            echo '<div class="row">';
            
            echo '<div class="col-md-4">';
            echo '<div class="alert available">';
            echo '<h6>🔧 แก้ไข Import API</h6>';
            echo '<p>เพิ่ม support สำหรับ fields สำคัญ:</p>';
            echo '<ul class="small">';
            echo '<li>CustomerGrade (ควรคำนวณอัตโนมัติ)</li>';
            echo '<li>CustomerTemperature (default: WARM)</li>';
            echo '<li>TotalPurchase (default: 0.00)</li>';
            echo '<li>Sales (อาจใส่ได้จาก CSV)</li>';
            echo '</ul>';
            echo '<button class="btn btn-success btn-sm" onclick="enhanceImportAPI()">Enhance API</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="alert partial">';
            echo '<h6>📋 ปรับปรุงรูปแบบ CSV</h6>';
            echo '<p>เพิ่ม columns ใหม่ในตัวอย่าง CSV:</p>';
            echo '<ul class="small">';
            echo '<li>customer_grade</li>';
            echo '<li>sales_person</li>';
            echo '<li>total_purchase</li>';
            echo '<li>contact_attempts</li>';
            echo '</ul>';
            echo '<button class="btn btn-warning btn-sm" onclick="updateCSVFormat()">Update Format</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="col-md-4">';
            echo '<div class="alert available">';
            echo '<h6>📊 สร้างไฟล์ตัวอย่างใหม่</h6>';
            echo '<p>สร้าง CSV template ที่สมบูรณ์:</p>';
            echo '<ul class="small">';
            echo '<li>รวม fields ทั้งหมดที่สำคัญ</li>';
            echo '<li>ข้อมูลตัวอย่างที่ถูกต้อง</li>';
            echo '<li>คำอธิบายแต่ละ column</li>';
            echo '</ul>';
            echo '<button class="btn btn-info btn-sm" onclick="downloadEnhancedCSV()">Download Enhanced</button>';
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

        function enhanceImportAPI() {
            showResult('🔄 Enhancing import API to support more fields...', 'info');
            
            fetch('enhance_import_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Enhanced fields:</strong><ul>${Object.entries(data.enhanced_fields).map(([field, desc]) => `<li><strong>${field}:</strong> ${desc}</li>`).join('')}</ul>`, 'success');
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function updateCSVFormat() {
            showResult('🔄 Updating CSV format documentation...', 'info');
            
            fetch('update_csv_format.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showResult(`✅ ${data.message}<br><strong>Updated:</strong> ${data.updated_files.join(', ')}`, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showResult(`❌ Error: ${data.error}`, 'danger');
                    }
                })
                .catch(error => {
                    showResult(`❌ Network Error: ${error.message}`, 'danger');
                });
        }

        function downloadEnhancedCSV() {
            showResult('🔄 Generating enhanced CSV template...', 'info');
            
            // Generate enhanced CSV with all important fields
            const csvContent = 
                "customer_name,customer_tel,customer_email,customer_address,customer_province,customer_status,customer_grade,sales_person,total_purchase,contact_attempts,customer_temperature\\n" +
                "บริษัท ABC จำกัด,081-234-5678,abc@company.com,123 ถนนสุขุมวิท แขวงคลองเตย,กรุงเทพมหานคร,ลูกค้าใหม่,A,sales01,15000.00,0,HOT\\n" +
                "ร้าน XYZ,082-987-6543,xyz@shop.com,456 ถนนรัชดาภิเษก แขวงดินแดง,กรุงเทพมหานคร,ลูกค้าติดตาม,B,sales02,8500.00,2,WARM\\n" +
                "สวนผลไม้สมบูรณ์,083-555-1234,fruit@farm.com,789 ถนนพหลโยธิน ตำบลลำลูกกา,ปทุมธานี,ลูกค้าเก่า,C,sales01,3200.00,1,COLD\\n" +
                "ฟาร์มไก่อินทรีย์,084-111-2222,chicken@organic.com,321 ถนนเพชรบุรี แขวงมักกะสัน,กรุงเทพมหานคร,สนใจ,A,sales03,25000.00,0,HOT\\n" +
                "ธุรกิจขนาดเล็ก,085-333-4444,small@business.com,654 ถนนลาดพร้าว แขวงจตุจักร,กรุงเทพมหานคร,คุยจบ,D,sales02,500.00,3,FROZEN";
            
            // Use UTF-8 BOM for Thai language support
            const BOM = "\ufeff";
            const blob = new Blob([BOM + csvContent], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", "customer_import_enhanced_template.csv");
            link.style.visibility = "hidden";
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showResult('✅ Enhanced CSV template downloaded! ไฟล์รวม fields ทั้งหมดที่สำคัญแล้ว', 'success');
        }
    </script>
</body>
</html>