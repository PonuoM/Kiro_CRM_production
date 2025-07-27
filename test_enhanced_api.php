<?php
// test_enhanced_api.php
// ทดสอบ Enhanced API สำหรับ Story 3.2

session_start();

// Simple auth bypass for testing
if (!isset($_SESSION['user_login'])) {
    $_SESSION['user_login'] = 'test_user';
    $_SESSION['user_role'] = 'sales';
}

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Enhanced API Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f2f2f2;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";
echo "</head><body>";

echo "<h1>🚀 Enhanced API Test</h1>";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Test 1: ตรวจสอบ Current API
    echo "<h2>📋 Test 1: Current Dashboard API</h2>";
    
    ob_start();
    include 'api/dashboard/summary.php';
    $currentApiOutput = ob_get_clean();
    
    echo "<h3>Current API Output:</h3>";
    echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;'>";
    echo htmlspecialchars($currentApiOutput);
    echo "</pre>";
    
    // Test 2: ทดสอบ Enhanced API Query โดยตรง
    echo "<h2>🔬 Test 2: Enhanced API Query (Direct)</h2>";
    
    $sql = "SELECT 
        CustomerCode, CustomerName, CustomerTel, CustomerStatus,
        CustomerTemperature, CustomerGrade,
        AssignDate, CreatedDate, LastContactDate,
        CASE 
            WHEN CustomerStatus = 'ลูกค้าใหม่' THEN 
                DATEDIFF(DATE_ADD(COALESCE(AssignDate, CreatedDate), INTERVAL 30 DAY), CURDATE())
            WHEN CustomerStatus IN ('ลูกค้าติดตาม', 'ลูกค้าเก่า') THEN 
                DATEDIFF(DATE_ADD(COALESCE(LastContactDate, AssignDate, CreatedDate), INTERVAL 90 DAY), CURDATE())
            ELSE 
                DATEDIFF(DATE_ADD(COALESCE(AssignDate, CreatedDate), INTERVAL 30 DAY), CURDATE())
        END as time_remaining_days,
        CASE 
            WHEN DATEDIFF(DATE_ADD(COALESCE(AssignDate, CreatedDate), INTERVAL 30 DAY), CURDATE()) <= 0 THEN 'OVERDUE'
            WHEN DATEDIFF(DATE_ADD(COALESCE(AssignDate, CreatedDate), INTERVAL 30 DAY), CURDATE()) <= 7 THEN 'URGENT'
            WHEN DATEDIFF(DATE_ADD(COALESCE(AssignDate, CreatedDate), INTERVAL 30 DAY), CURDATE()) <= 14 THEN 'SOON'
            ELSE 'NORMAL'
        END as time_status
        FROM customers 
        WHERE CustomerStatus IS NOT NULL
        ORDER BY 
            CASE WHEN CustomerTemperature = 'HOT' THEN 1 ELSE 2 END,
            time_remaining_days ASC
        LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $enhancedCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($enhancedCustomers) {
        echo "<p class='success'>✅ Enhanced Query ทำงานได้! พบ " . count($enhancedCustomers) . " รายการ</p>";
        
        echo "<table>";
        echo "<tr><th>รหัส</th><th>ชื่อ</th><th>สถานะ</th><th>Temperature</th><th>Grade</th><th>วันที่เหลือ</th><th>Time Status</th></tr>";
        
        foreach ($enhancedCustomers as $customer) {
            $tempColor = '';
            switch($customer['CustomerTemperature']) {
                case 'HOT': $tempColor = 'color:red;font-weight:bold;'; break;
                case 'WARM': $tempColor = 'color:orange;'; break;
                case 'COLD': $tempColor = 'color:blue;'; break;
                case 'FROZEN': $tempColor = 'color:gray;'; break;
            }
            
            $daysColor = '';
            if ($customer['time_remaining_days'] <= 0) $daysColor = 'color:red;font-weight:bold;';
            elseif ($customer['time_remaining_days'] <= 5) $daysColor = 'color:orange;';
            elseif ($customer['time_remaining_days'] <= 14) $daysColor = 'color:blue;';
            
            echo "<tr>";
            echo "<td>" . $customer['CustomerCode'] . "</td>";
            echo "<td>" . $customer['CustomerName'] . "</td>";
            echo "<td>" . $customer['CustomerStatus'] . "</td>";
            echo "<td style='$tempColor'>" . $customer['CustomerTemperature'] . "</td>";
            echo "<td>" . $customer['CustomerGrade'] . "</td>";
            echo "<td style='$daysColor'>" . $customer['time_remaining_days'] . " วัน</td>";
            echo "<td>" . $customer['time_status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Enhanced Query ไม่ได้ผลลัพธ์</p>";
    }
    
    // Test 3: ทดสอบ Enhanced API แบบ JSON
    echo "<h2>📊 Test 3: Enhanced API JSON Response</h2>";
    
    $enhancedResponse = [
        'status' => 'success',
        'data' => [
            'summary' => [
                'total_customers' => count($enhancedCustomers),
                'hot_customers' => count(array_filter($enhancedCustomers, function($c) { return $c['CustomerTemperature'] === 'HOT'; })),
                'urgent_customers' => count(array_filter($enhancedCustomers, function($c) { return $c['time_remaining_days'] <= 5; })),
                'overdue_customers' => count(array_filter($enhancedCustomers, function($c) { return $c['time_remaining_days'] <= 0; }))
            ],
            'customers' => $enhancedCustomers
        ],
        'message' => 'Enhanced API test successful'
    ];
    
    echo "<h3>Enhanced JSON Response:</h3>";
    echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;max-height:300px;'>";
    echo json_encode($enhancedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
    
    // Test 4: ทดสอบ Priority Logic
    echo "<h2>🎯 Test 4: Priority Logic Test</h2>";
    
    $priorityTest = [];
    foreach ($enhancedCustomers as $customer) {
        $isHot = $customer['CustomerTemperature'] === 'HOT';
        $isUrgent = $customer['time_remaining_days'] <= 5;
        $isOverdue = $customer['time_remaining_days'] <= 0;
        
        $priority = 'NORMAL';
        if ($isHot) $priority = 'HOT';
        elseif ($isOverdue) $priority = 'OVERDUE';
        elseif ($isUrgent) $priority = 'URGENT';
        
        $priorityTest[] = [
            'code' => $customer['CustomerCode'],
            'name' => $customer['CustomerName'],
            'temperature' => $customer['CustomerTemperature'],
            'days_remaining' => $customer['time_remaining_days'],
            'priority' => $priority,
            'css_class' => $isHot ? 'row-hot' : ($isUrgent ? 'row-urgent' : 'row-normal')
        ];
    }
    
    echo "<table>";
    echo "<tr><th>รหัส</th><th>ชื่อ</th><th>Temperature</th><th>วันที่เหลือ</th><th>Priority</th><th>CSS Class</th></tr>";
    
    foreach ($priorityTest as $test) {
        $rowStyle = '';
        switch($test['priority']) {
            case 'HOT': $rowStyle = 'background-color:#ffe6e6;'; break;
            case 'OVERDUE': $rowStyle = 'background-color:#ffcccc;'; break;
            case 'URGENT': $rowStyle = 'background-color:#fff3e0;'; break;
        }
        
        echo "<tr style='$rowStyle'>";
        echo "<td>" . $test['code'] . "</td>";
        echo "<td>" . $test['name'] . "</td>";
        echo "<td>" . $test['temperature'] . "</td>";
        echo "<td>" . $test['days_remaining'] . "</td>";
        echo "<td><strong>" . $test['priority'] . "</strong></td>";
        echo "<td><code>" . $test['css_class'] . "</code></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 5: URL Test Links
    echo "<h2>🔗 Test 5: API URLs for Testing</h2>";
    echo "<ul>";
    echo "<li><a href='api/dashboard/summary.php' target='_blank'>Current API</a></li>";
    echo "<li><a href='api/dashboard/summary.php?include_customers=true' target='_blank'>Enhanced API (ถ้ามี)</a></li>";
    echo "<li><a href='api/customers/list-simple.php' target='_blank'>Customer List API</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Database Error:</h2>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>📍 URL สำหรับทดสอบ:</strong> <a href='test_enhanced_api.php'>test_enhanced_api.php</a></p>";
echo "<p><strong>⏰ เวลาทดสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>