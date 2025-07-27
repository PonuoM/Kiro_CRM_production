<?php
// test_database_columns.php
// ทดสอบว่า Database มีคอลัมน์ใหม่หรือไม่

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Test</title></head><body>";
echo "<h1>🔍 Database Columns Test</h1>";

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // ทดสอบ 1: ตรวจสอบ structure ของตาราง customers
    echo "<h2>📋 1. ตารางลูกค้า (customers) Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $hasTemperature = false;
    $hasGrade = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'CustomerTemperature') {
            $hasTemperature = true;
        }
        if ($column['Field'] === 'CustomerGrade') {
            $hasGrade = true;
        }
    }
    echo "</table>";
    
    // ทดสอب 2: ตรวจสอบว่ามีคอลัมน์ใหม่หรือไม่
    echo "<h2>✅ 2. Columns Check:</h2>";
    echo "<p>CustomerTemperature: " . ($hasTemperature ? "✅ มีแล้ว" : "❌ ยังไม่มี") . "</p>";
    echo "<p>CustomerGrade: " . ($hasGrade ? "✅ มีแล้ว" : "❌ ยังไม่มี") . "</p>";
    
    // ทดสอบ 3: ตัวอย่างข้อมูลลูกค้า 5 คนแรก
    echo "<h2>📊 3. ตัวอย่างข้อมูลลูกค้า (5 คนแรก):</h2>";
    
    if ($hasTemperature && $hasGrade) {
        $sql = "SELECT CustomerCode, CustomerName, CustomerStatus, CustomerTemperature, CustomerGrade, CreatedDate 
                FROM customers 
                ORDER BY CreatedDate DESC 
                LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($customers) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'><th>รหัส</th><th>ชื่อ</th><th>สถานะ</th><th>Temperature</th><th>Grade</th><th>วันที่สร้าง</th></tr>";
            
            foreach ($customers as $customer) {
                echo "<tr>";
                echo "<td>" . $customer['CustomerCode'] . "</td>";
                echo "<td>" . $customer['CustomerName'] . "</td>";
                echo "<td>" . $customer['CustomerStatus'] . "</td>";
                echo "<td style='color: " . ($customer['CustomerTemperature'] === 'HOT' ? 'red' : 'blue') . ";'>" . $customer['CustomerTemperature'] . "</td>";
                echo "<td>" . $customer['CustomerGrade'] . "</td>";
                echo "<td>" . $customer['CreatedDate'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ ไม่มีข้อมูลลูกค้า</p>";
        }
    } else {
        echo "<p>❌ ไม่สามารถทดสอบข้อมูลได้เพราะยังไม่มีคอลัมน์ที่จำเป็น</p>";
    }
    
    // ทดสอบ 4: ทดสอบ Enhanced API Query
    echo "<h2>🚀 4. ทดสอบ Enhanced API Query:</h2>";
    
    if ($hasTemperature && $hasGrade) {
        try {
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
                END as time_remaining_days
                FROM customers 
                WHERE CustomerStatus IS NOT NULL
                ORDER BY CustomerCode
                LIMIT 3";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $testCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($testCustomers) {
                echo "<p>✅ Enhanced Query ทำงานได้:</p>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background: #f0f0f0;'><th>รหัส</th><th>ชื่อ</th><th>Temperature</th><th>Grade</th><th>วันที่เหลือ</th></tr>";
                
                foreach ($testCustomers as $customer) {
                    $daysColor = 'green';
                    if ($customer['time_remaining_days'] <= 0) $daysColor = 'red';
                    elseif ($customer['time_remaining_days'] <= 5) $daysColor = 'orange';
                    
                    echo "<tr>";
                    echo "<td>" . $customer['CustomerCode'] . "</td>";
                    echo "<td>" . $customer['CustomerName'] . "</td>";
                    echo "<td style='color: " . ($customer['CustomerTemperature'] === 'HOT' ? 'red' : 'blue') . ";'>" . $customer['CustomerTemperature'] . "</td>";
                    echo "<td>" . $customer['CustomerGrade'] . "</td>";
                    echo "<td style='color: $daysColor;'>" . $customer['time_remaining_days'] . " วัน</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>❌ Enhanced Query ไม่ได้ผลลัพธ์</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Enhanced Query Error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Connection Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>📍 URL สำหรับทดสอบ:</strong> <a href='test_database_columns.php'>test_database_columns.php</a></p>";
echo "<p><strong>⏰ เวลาทดสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
?>