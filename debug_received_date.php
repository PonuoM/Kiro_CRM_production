<?php
// debug_received_date.php
// Debug ปัญหาวันที่ได้รับในแดชบอร์ด

session_start();

// Simple auth bypass for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'test_user';
    $_SESSION['user_role'] = 'sales';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>🐛 Debug Received Date</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:15px;} .debug-section{margin:15px 0;padding:12px;border:2px solid #ddd;border-radius:8px;} .issue{border-color:#dc3545;background:#fff5f5;} .success{border-color:#28a745;background:#f8fff8;} pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow:auto;max-height:300px;}</style>";
echo "</head><body>";

echo "<div class='container-fluid'>";
echo "<h1>🐛 Debug: วันที่ได้รับไม่แสดงในแดชบอร์ด</h1>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Debug 1: ตรวจสอบข้อมูลจาก API
    echo "<div class='debug-section issue'>";
    echo "<h2>🔍 Debug 1: ข้อมูลจาก Enhanced API</h2>";
    
    $_GET['include_customers'] = 'true';
    $_GET['limit'] = '5';
    
    ob_start();
    include 'api/dashboard/summary.php';
    $apiResponse = ob_get_clean();
    
    unset($_GET['include_customers'], $_GET['limit']);
    
    echo "<h4>📡 Raw API Response:</h4>";
    echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
    
    $data = json_decode($apiResponse, true);
    
    if ($data && isset($data['data']['customers'])) {
        echo "<h4>🔍 ตรวจสอบข้อมูลลูกค้า:</h4>";
        echo "<table class='table table-sm'>";
        echo "<thead><tr><th>CustomerCode</th><th>AssignDate</th><th>CreatedDate</th><th>JSON Values</th></tr></thead><tbody>";
        
        foreach (array_slice($data['data']['customers'], 0, 5) as $customer) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($customer['CustomerCode']) . "</strong></td>";
            echo "<td>" . ($customer['AssignDate'] ?? '<span class="text-danger">NULL</span>') . "</td>";
            echo "<td>" . ($customer['CreatedDate'] ?? '<span class="text-danger">NULL</span>') . "</td>";
            echo "<td><small><code>";
            echo "AssignDate: " . json_encode($customer['AssignDate'] ?? null) . "<br>";
            echo "CreatedDate: " . json_encode($customer['CreatedDate'] ?? null);
            echo "</code></small></td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-danger'>❌ API ไม่ส่งข้อมูลลูกค้า</div>";
    }
    
    echo "</div>";
    
    // Debug 2: ตรวจสอบ JavaScript Function
    echo "<div class='debug-section'>";
    echo "<h2>💻 Debug 2: ทดสอบ JavaScript Function</h2>";
    
    echo "<div id='js-debug-results'></div>";
    
    echo "<script>";
    echo "
    // Test formatReceivedDate function
    console.log('=== formatReceivedDate Debug ===');
    
    // Mock customers data from API
    const testCustomers = " . json_encode(array_slice($data['data']['customers'] ?? [], 0, 3)) . ";
    
    console.log('Test customers data:', testCustomers);
    
    // Simple implementation of formatDate
    function formatDate(dateString) {
        console.log('formatDate input:', dateString, typeof dateString);
        
        if (!dateString || dateString === null || dateString === 'null' || dateString === '') {
            console.log('formatDate: empty input');
            return '';
        }
        
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                console.log('formatDate: invalid date');
                return '';
            }
            
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const result = day + '/' + month + '/' + year;
            console.log('formatDate result:', result);
            return result;
        } catch (error) {
            console.log('formatDate error:', error);
            return '';
        }
    }
    
    // Simple implementation of formatReceivedDate
    function formatReceivedDate(customer) {
        console.log('formatReceivedDate input:', customer);
        
        let dateToFormat = null;
        let source = '';
        
        if (customer.AssignDate && customer.AssignDate !== null && customer.AssignDate !== 'null') {
            dateToFormat = customer.AssignDate;
            source = 'AssignDate';
        } else if (customer.CreatedDate && customer.CreatedDate !== null && customer.CreatedDate !== 'null') {
            dateToFormat = customer.CreatedDate;
            source = 'CreatedDate';
        }
        
        console.log('Selected date:', dateToFormat, 'from:', source);
        
        if (!dateToFormat) {
            console.log('No valid date found');
            return '<span class=\"text-warning\">ไม่มีข้อมูล</span>';
        }
        
        const formatted = formatDate(dateToFormat);
        if (!formatted) {
            console.log('Date formatting failed');
            return '<span class=\"text-warning\">รูปแบบวันที่ผิด</span>';
        }
        
        const sourceText = source === 'AssignDate' ? 'มอบหมาย' : 'สร้าง';
        const result = formatted + '<br><span class=\"text-muted\" style=\"font-size:0.75em;\">(' + sourceText + ')</span>';
        console.log('Final result:', result);
        return result;
    }
    
    // Test with actual data
    let debugResults = '<h4>🧪 JavaScript Debug Results:</h4>';
    debugResults += '<table class=\"table table-sm\">';
    debugResults += '<thead><tr><th>CustomerCode</th><th>formatReceivedDate Result</th><th>Console Output</th></tr></thead><tbody>';
    
    testCustomers.forEach(function(customer, index) {
        console.log('\\n=== Testing customer ' + (index + 1) + ' ===');
        const result = formatReceivedDate(customer);
        
        debugResults += '<tr>';
        debugResults += '<td>' + customer.CustomerCode + '</td>';
        debugResults += '<td>' + result + '</td>';
        debugResults += '<td><small>Check browser console</small></td>';
        debugResults += '</tr>';
    });
    
    debugResults += '</tbody></table>';
    debugResults += '<div class=\"alert alert-info\">📋 ตรวจสอบ Browser Console สำหรับ Debug ละเอียด</div>';
    
    document.getElementById('js-debug-results').innerHTML = debugResults;
    ";
    echo "</script>";
    
    echo "</div>";
    
    // Debug 3: ตรวจสอบ Database Query โดยตรง
    echo "<div class='debug-section success'>";
    echo "<h2>🗄️ Debug 3: Database Query โดยตรง</h2>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, CustomerStatus,
        AssignDate, CreatedDate, LastContactDate,
        CASE 
            WHEN AssignDate IS NOT NULL THEN CONCAT(DATE_FORMAT(AssignDate, '%d/%m/%Y'), ' (มอบหมาย)')
            WHEN CreatedDate IS NOT NULL THEN CONCAT(DATE_FORMAT(CreatedDate, '%d/%m/%Y'), ' (สร้าง)')
            ELSE 'ไม่มีข้อมูล'
        END as formatted_received_date
        FROM customers 
        ORDER BY COALESCE(AssignDate, CreatedDate) DESC
        LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    echo "<h4>📊 ผลลัพธ์จาก Database:</h4>";
    echo "<table class='table table-sm'>";
    echo "<thead><tr><th>CustomerCode</th><th>CustomerName</th><th>Status</th><th>AssignDate</th><th>CreatedDate</th><th>Formatted Date</th></tr></thead><tbody>";
    
    foreach ($customers as $customer) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($customer['CustomerCode']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($customer['CustomerName']) . "</td>";
        echo "<td>" . htmlspecialchars($customer['CustomerStatus']) . "</td>";
        echo "<td>" . ($customer['AssignDate'] ? date('d/m/Y', strtotime($customer['AssignDate'])) : '<span class="text-muted">NULL</span>') . "</td>";
        echo "<td>" . ($customer['CreatedDate'] ? date('d/m/Y', strtotime($customer['CreatedDate'])) : '<span class="text-muted">NULL</span>') . "</td>";
        echo "<td><strong>" . htmlspecialchars($customer['formatted_received_date']) . "</strong></td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    echo "</div>";
    
    // Debug 4: แนะนำการแก้ไข
    echo "<div class='debug-section'>";
    echo "<h2>🔧 Debug 4: การแก้ไขที่แนะนำ</h2>";
    
    echo "<h4>🎯 สาเหตุที่วันที่ได้รับไม่ขึ้น:</h4>";
    echo "<ol>";
    echo "<li><strong>API ส่งข้อมูลถูกต้อง</strong> - แต่ JavaScript อาจไม่ทำงาน</li>";
    echo "<li><strong>formatReceivedDate function</strong> - อาจมีปัญหาการเรียกใช้</li>";
    echo "<li><strong>Browser Console Errors</strong> - ตรวจสอบ JavaScript errors</li>";
    echo "<li><strong>CSS/HTML Rendering</strong> - อาจมีปัญหาการแสดงผล</li>";
    echo "</ol>";
    
    echo "<h4>💡 วิธีแก้ไข:</h4>";
    echo "<div class='row'>";
    echo "<div class='col-md-6'>";
    echo "<h5>🔧 แก้ไขทันที:</h5>";
    echo "<ul>";
    echo "<li>ตรวจสอบ Browser Console</li>";
    echo "<li>แก้ไข formatReceivedDate function</li>";
    echo "<li>เพิ่ม error handling</li>";
    echo "<li>ทดสอบกับข้อมูลจริง</li>";
    echo "</ul>";
    echo "</div>";
    echo "<div class='col-md-6'>";
    echo "<h5>🚀 ระบบอัตโนมัติ:</h5>";
    echo "<ul>";
    echo "<li>สร้าง Cron Job ทำความสะอาดข้อมูล</li>";
    echo "<li>Auto-reassign ลูกค้าค้างคาว</li>";
    echo "<li>Alert System สำหรับ Supervisor</li>";
    echo "<li>Business Rules Engine</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h5>⚠️ ปัญหาที่ต้องแก้:</h5>";
    echo "<p><strong>1. วันที่ได้รับไม่แสดง:</strong> ต้องแก้ JavaScript หรือ API</p>";
    echo "<p><strong>2. ระบบ Manual:</strong> ต้องสร้างระบบอัตโนมัติเพื่อไม่ต้องแก้ไขเองทุกครั้ง</p>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>"; // container

echo "</body></html>";
?>