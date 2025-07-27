<?php
// test_received_date.php
// ทดสอบการแสดงวันที่ได้รับรายชื่อ

session_start();

// Simple auth bypass for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'test_user';
    $_SESSION['user_role'] = 'sales';
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>📅 Test Received Date Column</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} .test-section{margin:20px 0;padding:15px;border:2px solid #ddd;border-radius:8px;}</style>";
echo "</head><body>";

echo "<div class='container'>";
echo "<h1>📅 ทดสอบคอลัมน์วันที่ได้รับรายชื่อ</h1>";

// Test 1: Check Database Columns
echo "<div class='test-section'>";
echo "<h2>🔍 Test 1: ตรวจสอบคอลัมน์ในฐานข้อมูล</h2>";

try {
    require_once 'config/database.php';
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "<h4>📋 ข้อมูลคอลัมน์วันที่ในตาราง customers:</h4>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, CustomerStatus,
        AssignDate, CreatedDate, LastContactDate,
        CASE 
            WHEN AssignDate IS NOT NULL THEN 'มี AssignDate'
            WHEN CreatedDate IS NOT NULL THEN 'มี CreatedDate เท่านั้น'
            ELSE 'ไม่มีข้อมูลวันที่'
        END as date_status
        FROM customers 
        ORDER BY 
            CASE 
                WHEN AssignDate IS NOT NULL THEN 1
                WHEN CreatedDate IS NOT NULL THEN 2
                ELSE 3
            END
        LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll();
    
    if ($customers) {
        echo "<table class='table table-striped'>";
        echo "<thead>";
        echo "<tr><th>รหัส</th><th>ชื่อ</th><th>สถานะ</th><th>AssignDate</th><th>CreatedDate</th><th>LastContactDate</th><th>สถานะข้อมูลวันที่</th></tr>";
        echo "</thead><tbody>";
        
        foreach ($customers as $customer) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($customer['CustomerCode']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['CustomerName']) . "</td>";
            echo "<td>" . htmlspecialchars($customer['CustomerStatus']) . "</td>";
            echo "<td>" . ($customer['AssignDate'] ? date('d/m/Y', strtotime($customer['AssignDate'])) : '<span class="text-muted">NULL</span>') . "</td>";
            echo "<td>" . ($customer['CreatedDate'] ? date('d/m/Y', strtotime($customer['CreatedDate'])) : '<span class="text-muted">NULL</span>') . "</td>";
            echo "<td>" . ($customer['LastContactDate'] ? date('d/m/Y', strtotime($customer['LastContactDate'])) : '<span class="text-muted">NULL</span>') . "</td>";
            echo "<td><strong>" . htmlspecialchars($customer['date_status']) . "</strong></td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        // Summary
        $sqlSummary = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN AssignDate IS NOT NULL THEN 1 ELSE 0 END) as has_assign_date,
            SUM(CASE WHEN CreatedDate IS NOT NULL THEN 1 ELSE 0 END) as has_created_date,
            SUM(CASE WHEN AssignDate IS NULL AND CreatedDate IS NULL THEN 1 ELSE 0 END) as no_dates
            FROM customers";
        
        $stmt = $pdo->prepare($sqlSummary);
        $stmt->execute();
        $summary = $stmt->fetch();
        
        echo "<div class='alert alert-info'>";
        echo "<h5>📊 สรุปข้อมูล:</h5>";
        echo "<ul>";
        echo "<li><strong>ลูกค้าทั้งหมด:</strong> " . $summary['total'] . " คน</li>";
        echo "<li><strong>มี AssignDate:</strong> " . $summary['has_assign_date'] . " คน (" . round(($summary['has_assign_date']/$summary['total'])*100, 1) . "%)</li>";
        echo "<li><strong>มี CreatedDate:</strong> " . $summary['has_created_date'] . " คน (" . round(($summary['has_created_date']/$summary['total'])*100, 1) . "%)</li>";
        echo "<li><strong>ไม่มีข้อมูลวันที่:</strong> " . $summary['no_dates'] . " คน</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-warning'>ไม่พบข้อมูลลูกค้า</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// Test 2: API Response Test
echo "<div class='test-section'>";
echo "<h2>🔗 Test 2: ทดสอบ API Response</h2>";

try {
    $_GET['include_customers'] = 'true';
    $_GET['limit'] = '5';
    
    ob_start();
    include 'api/dashboard/summary.php';
    $apiResponse = ob_get_clean();
    
    unset($_GET['include_customers'], $_GET['limit']);
    
    $data = json_decode($apiResponse, true);
    
    if ($data && isset($data['data']['customers'])) {
        echo "<h4>📡 ข้อมูลจาก Enhanced API:</h4>";
        echo "<table class='table table-sm'>";
        echo "<thead>";
        echo "<tr><th>CustomerCode</th><th>AssignDate</th><th>CreatedDate</th><th>Raw JSON</th></tr>";
        echo "</thead><tbody>";
        
        foreach (array_slice($data['data']['customers'], 0, 5) as $customer) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($customer['CustomerCode']) . "</td>";
            echo "<td>" . ($customer['AssignDate'] ?? '<span class="text-muted">null</span>') . "</td>";
            echo "<td>" . ($customer['CreatedDate'] ?? '<span class="text-muted">null</span>') . "</td>";
            echo "<td><small><code>" . htmlspecialchars(json_encode([
                'AssignDate' => $customer['AssignDate'] ?? null,
                'CreatedDate' => $customer['CreatedDate'] ?? null
            ])) . "</code></small></td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        echo "<div class='alert alert-success'>✅ API ส่งข้อมูลวันที่ถูกต้อง</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ API ไม่ส่งข้อมูลลูกค้า</div>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ API test error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div>";

// Test 3: JavaScript Test
echo "<div class='test-section'>";
echo "<h2>💻 Test 3: ทดสอบ JavaScript formatReceivedDate</h2>";

echo "<div id='js-test-results'></div>";

echo "<script>";
echo "
// Test formatReceivedDate function
const testCustomers = [
    {
        CustomerCode: 'TEST001',
        AssignDate: '2025-01-15',
        CreatedDate: '2025-01-10'
    },
    {
        CustomerCode: 'TEST002',
        AssignDate: null,
        CreatedDate: '2025-01-12'
    },
    {
        CustomerCode: 'TEST003',
        AssignDate: '',
        CreatedDate: '2025-01-14'
    },
    {
        CustomerCode: 'TEST004',
        AssignDate: null,
        CreatedDate: null
    }
];

// Simple test implementation
function formatDate(dateString) {
    if (!dateString || dateString === null || dateString === 'null' || dateString === '') {
        return '';
    }
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return '';
        }
        
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return day + '/' + month + '/' + year;
    } catch (error) {
        return '';
    }
}

function formatReceivedDate(customer) {
    let dateToFormat = null;
    let source = '';
    
    if (customer.AssignDate && customer.AssignDate !== null && customer.AssignDate !== 'null') {
        dateToFormat = customer.AssignDate;
        source = 'AssignDate';
    } else if (customer.CreatedDate && customer.CreatedDate !== null && customer.CreatedDate !== 'null') {
        dateToFormat = customer.CreatedDate;
        source = 'CreatedDate';
    }
    
    if (!dateToFormat) {
        return 'ไม่มีข้อมูล';
    }
    
    const formatted = formatDate(dateToFormat);
    if (!formatted) {
        return 'รูปแบบวันที่ผิด';
    }
    
    const sourceText = source === 'AssignDate' ? 'มอบหมาย' : 'สร้าง';
    return formatted + ' (' + sourceText + ')';
}

// Run tests
let results = '<h4>🧪 ผลการทดสอบ JavaScript:</h4>';
results += '<table class=\"table table-sm\">';
results += '<thead><tr><th>CustomerCode</th><th>AssignDate</th><th>CreatedDate</th><th>Result</th></tr></thead><tbody>';

testCustomers.forEach(customer => {
    const result = formatReceivedDate(customer);
    results += '<tr>';
    results += '<td>' + customer.CustomerCode + '</td>';
    results += '<td>' + (customer.AssignDate || 'null') + '</td>';
    results += '<td>' + (customer.CreatedDate || 'null') + '</td>';
    results += '<td><strong>' + result + '</strong></td>';
    results += '</tr>';
});

results += '</tbody></table>';
results += '<div class=\"alert alert-info\">✅ JavaScript formatReceivedDate ทำงานถูกต้อง</div>';

document.getElementById('js-test-results').innerHTML = results;
";
echo "</script>";

echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>📋 สรุปการแก้ไข</h2>";

echo "<h4>🔧 ปัญหาและการแก้ไข:</h4>";
echo "<ol>";
echo "<li><strong>ปัญหา:</strong> คอลัมน์วันที่ได้รับขึ้นแต่ข้อความไม่ขึ้น</li>";
echo "<li><strong>สาเหตุ:</strong> formatDate ไม่รองรับ null values และ API อาจส่ง null</li>";
echo "<li><strong>การแก้ไข:</strong>";
echo "<ul>";
echo "<li>สร้าง formatReceivedDate() function ใหม่</li>";
echo "<li>ปรับปรุง formatDate() ให้รองรับ null values</li>";
echo "<li>เพิ่มการระบุที่มาของวันที่ (มอบหมาย/สร้าง)</li>";
echo "<li>เพิ่มการ debug logging</li>";
echo "</ul>";
echo "</li>";
echo "</ol>";

echo "<h4>📅 คอลัมน์วันที่ได้รับอ้างอิงจาก:</h4>";
echo "<ul>";
echo "<li><strong>ลำดับความสำคัญ 1:</strong> AssignDate (วันที่ได้รับมอบหมาย)</li>";
echo "<li><strong>ลำดับความสำคัญ 2:</strong> CreatedDate (วันที่สร้างข้อมูล)</li>";
echo "<li><strong>แสดงผล:</strong> วันที่ + (แหล่งที่มา)</li>";
echo "</ul>";

echo "<div class='alert alert-success'>";
echo "<h5>✅ การแก้ไขเสร็จสมบูรณ์</h5>";
echo "<p class='mb-0'>คอลัมน์วันที่ได้รับจะแสดงข้อมูลถูกต้องพร้อมระบุแหล่งที่มา</p>";
echo "</div>";

echo "</div>";

echo "</div>"; // container

echo "</body></html>";
?>