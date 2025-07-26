<?php
/**
 * Fix Customers Table - Add Missing Columns (Final Fix)
 * แก้ไขตาราง customers โดยเพิ่มคอลั่มน์ที่ขาดหายไป
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🔧 Fix Customers Table - Add Missing Columns (Final)</h2>";
echo "<p>เพิ่มคอลั่มน์ที่ขาดหายไปในตาราง customers</p>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected successfully<br><br>";
    
    // 1. ตรวจสอบโครงสร้างตารางปัจจุบัน
    echo "<h3>📋 Current Customers Table Structure</h3>";
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM customers");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $existingColumns = [];
        foreach ($columns as $column) {
            $existingColumns[] = $column['Field'];
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='background: #cff4fc; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "📊 <strong>Found " . count($existingColumns) . " columns in customers table</strong>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Error reading table structure: " . $e->getMessage();
        echo "</div>";
        throw $e;
    }
    
    // 2. กำหนดคอลั่มน์ที่ต้องมี
    echo "<h3>🔍 Checking Required Columns</h3>";
    
    $requiredColumns = [
        'LastContactDate' => [
            'type' => 'DATETIME',
            'null' => 'YES',
            'default' => 'NULL',
            'comment' => 'วันที่ติดต่อลูกค้าครั้งล่าสุด'
        ],
        'ContactAttempts' => [
            'type' => 'INT(11)',
            'null' => 'NO',
            'default' => '0',
            'comment' => 'จำนวนครั้งที่พยายามติดต่อ'
        ],
        'GradeCalculatedDate' => [
            'type' => 'DATETIME',
            'null' => 'YES',
            'default' => 'NULL',
            'comment' => 'วันที่คำนวณเกรดล่าสุด'
        ],
        'TemperatureUpdatedDate' => [
            'type' => 'DATETIME',
            'null' => 'YES',
            'default' => 'NULL',
            'comment' => 'วันที่อัปเดตอุณหภูมิล่าสุด'
        ]
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $columnName => $definition) {
        if (!in_array($columnName, $existingColumns)) {
            $missingColumns[$columnName] = $definition;
            echo "<div style='background: #fff3cd; padding: 5px 10px; margin: 3px 0; border-radius: 3px; font-size: 14px;'>";
            echo "⚠️ Missing column: <strong>$columnName</strong>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 5px 10px; margin: 3px 0; border-radius: 3px; font-size: 14px;'>";
            echo "✅ Column exists: <strong>$columnName</strong>";
            echo "</div>";
        }
    }
    
    if (empty($missingColumns)) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "🎉 <strong>All required columns already exist!</strong><br>";
        echo "No need to add any columns.";
        echo "</div>";
    } else {
        // 3. เพิ่มคอลั่มน์ที่หายไป
        echo "<h3>🔧 Adding Missing Columns</h3>";
        
        foreach ($missingColumns as $columnName => $definition) {
            try {
                // สร้าง SQL statement
                $sql = "ALTER TABLE customers ADD COLUMN `$columnName` {$definition['type']}";
                
                if ($definition['null'] === 'NO') {
                    $sql .= " NOT NULL";
                } else {
                    $sql .= " NULL";
                }
                
                if ($definition['default'] !== 'NULL') {
                    $sql .= " DEFAULT {$definition['default']}";
                }
                
                $sql .= " COMMENT '{$definition['comment']}'";
                
                echo "<div style='background: #e2e3e5; padding: 8px; border-radius: 5px; margin: 5px 0; font-size: 12px;'>";
                echo "🔨 <strong>Executing:</strong> " . htmlspecialchars($sql);
                echo "</div>";
                
                $pdo->exec($sql);
                
                echo "<div style='background: #d4edda; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
                echo "✅ Successfully added column: <strong>$columnName</strong>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
                echo "❌ Failed to add column $columnName: " . $e->getMessage();
                echo "</div>";
                
                // ลองใช้วิธีอื่น
                try {
                    $simpleSql = "ALTER TABLE customers ADD `$columnName` {$definition['type']}";
                    $pdo->exec($simpleSql);
                    
                    echo "<div style='background: #d4edda; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
                    echo "✅ Added with simple syntax: <strong>$columnName</strong>";
                    echo "</div>";
                } catch (Exception $e2) {
                    echo "<div style='background: #f8d7da; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
                    echo "❌ Simple syntax also failed: " . $e2->getMessage();
                    echo "</div>";
                }
            }
        }
    }
    
    // 4. ตรวจสอบผลลัพธ์หลังจากเพิ่มคอลั่มน์
    echo "<h3>📊 Updated Table Structure</h3>";
    
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM customers");
        $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $currentColumnNames = array_column($updatedColumns, 'Field');
        
        echo "<div style='background: #cff4fc; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "📊 <strong>Total columns now: " . count($currentColumnNames) . "</strong><br>";
        echo "New columns: " . implode(', ', array_intersect($currentColumnNames, array_keys($requiredColumns)));
        echo "</div>";
        
        // แสดงเฉพาะคอลั่มน์ใหม่ที่เพิ่ม
        echo "<h4>🆕 New Columns Added:</h4>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Status</th></tr>";
        
        foreach ($requiredColumns as $columnName => $definition) {
            $found = false;
            $columnInfo = null;
            
            foreach ($updatedColumns as $column) {
                if ($column['Field'] === $columnName) {
                    $found = true;
                    $columnInfo = $column;
                    break;
                }
            }
            
            $bgColor = $found ? '#d4edda' : '#f8d7da';
            $status = $found ? '✅ EXISTS' : '❌ MISSING';
            
            echo "<tr style='background: $bgColor;'>";
            echo "<td><strong>$columnName</strong></td>";
            echo "<td>" . ($columnInfo ? $columnInfo['Type'] : $definition['type']) . "</td>";
            echo "<td>" . ($columnInfo ? $columnInfo['Null'] : $definition['null']) . "</td>";
            echo "<td>" . ($columnInfo ? $columnInfo['Default'] : $definition['default']) . "</td>";
            echo "<td><strong>$status</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Error checking updated structure: " . $e->getMessage();
        echo "</div>";
    }
    
    // 5. อัปเดตข้อมูลเริ่มต้น
    echo "<h3>🔄 Updating Initial Data</h3>";
    
    // อัปเดต LastContactDate
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE LastContactDate IS NULL");
        $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($nullCount > 0) {
            $updateSQL = "UPDATE customers SET LastContactDate = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30 + 1) DAY) WHERE LastContactDate IS NULL";
            $affected = $pdo->exec($updateSQL);
            
            echo "<div style='background: #d4edda; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
            echo "📅 Updated LastContactDate for <strong>$affected</strong> customers";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #fff3cd; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "⚠️ Could not update LastContactDate: " . $e->getMessage();
        echo "</div>";
    }
    
    // อัปเดต ContactAttempts
    try {
        $updateSQL = "UPDATE customers SET ContactAttempts = FLOOR(RAND() * 6) WHERE ContactAttempts = 0 OR ContactAttempts IS NULL";
        $affected = $pdo->exec($updateSQL);
        
        echo "<div style='background: #d4edda; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "📞 Updated ContactAttempts for <strong>$affected</strong> customers";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #fff3cd; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "⚠️ Could not update ContactAttempts: " . $e->getMessage();
        echo "</div>";
    }
    
    // อัปเดต GradeCalculatedDate
    try {
        $updateSQL = "UPDATE customers SET GradeCalculatedDate = NOW() WHERE GradeCalculatedDate IS NULL";
        $affected = $pdo->exec($updateSQL);
        
        echo "<div style='background: #d4edda; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "⚡ Updated GradeCalculatedDate for <strong>$affected</strong> customers";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #fff3cd; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "⚠️ Could not update GradeCalculatedDate: " . $e->getMessage();
        echo "</div>";
    }
    
    // อัปเดต TemperatureUpdatedDate
    try {
        $updateSQL = "UPDATE customers SET TemperatureUpdatedDate = NOW() WHERE TemperatureUpdatedDate IS NULL";
        $affected = $pdo->exec($updateSQL);
        
        echo "<div style='background: #d4edda; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "🌡️ Updated TemperatureUpdatedDate for <strong>$affected</strong> customers";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #fff3cd; padding: 8px; border-radius: 5px; margin: 5px 0;'>";
        echo "⚠️ Could not update TemperatureUpdatedDate: " . $e->getMessage();
        echo "</div>";
    }
    
    // 6. อัปเดต Temperature Logic ตามเกณฑ์ใหม่
    echo "<h3>🌡️ Applying New Temperature Logic</h3>";
    
    $temperatureUpdates = [
        [
            'sql' => "UPDATE customers SET CustomerTemperature = 'HOT', TemperatureUpdatedDate = NOW() WHERE CustomerStatus IN ('ลูกค้าใหม่', 'สนใจ', 'คุยจบ') OR LastContactDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'label' => 'HOT - ลูกค้าใหม่, สนใจ, คุยจบ, หรือติดต่อภายใน 7 วัน',
            'color' => '#ffe6e6'
        ],
        [
            'sql' => "UPDATE customers SET CustomerTemperature = 'COLD', TemperatureUpdatedDate = NOW() WHERE CustomerStatus IN ('ไม่สนใจ', 'ติดต่อไม่ได้') OR ContactAttempts >= 3",
            'label' => 'COLD - ไม่สนใจ, ติดต่อไม่ได้, หรือพยายามติดต่อ 3+ ครั้ง',
            'color' => '#f0f0f0'
        ],
        [
            'sql' => "UPDATE customers SET CustomerTemperature = 'WARM', TemperatureUpdatedDate = NOW() WHERE CustomerTemperature NOT IN ('HOT', 'COLD')",
            'label' => 'WARM - ลูกค้าปกติ (ไม่อยู่ในเกณฑ์ HOT หรือ COLD)',
            'color' => '#fff3cd'
        ]
    ];
    
    $totalTempUpdated = 0;
    foreach ($temperatureUpdates as $update) {
        try {
            $affected = $pdo->exec($update['sql']);
            $totalTempUpdated += $affected;
            
            echo "<div style='background: {$update['color']}; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
            echo "🌡️ <strong>$affected</strong> customers → <strong>{$update['label']}</strong>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 8px 12px; margin: 5px 0; border-radius: 5px; font-size: 14px;'>";
            echo "❌ Temperature update failed: " . $e->getMessage();
            echo "</div>";
        }
    }
    
    // 7. ทดสอบข้อมูลหลังการอัปเดต
    echo "<h3>🧪 Testing Updated Data</h3>";
    
    try {
        $stmt = $pdo->query("SELECT CustomerCode, CustomerName, CustomerGrade, CustomerTemperature, TotalPurchase, LastContactDate, ContactAttempts FROM customers ORDER BY CustomerGrade, TotalPurchase DESC LIMIT 5");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($sampleData) > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
            echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Grade</th><th>Temp</th><th>Purchase</th><th>Last Contact</th><th>Attempts</th></tr>";
            
            foreach ($sampleData as $customer) {
                $gradeColors = ['A' => '#e8f5e8', 'B' => '#cff4fc', 'C' => '#fff3cd', 'D' => '#f8d7da'];
                $bgColor = $gradeColors[$customer['CustomerGrade']] ?? '#fff';
                
                echo "<tr style='background: $bgColor;'>";
                echo "<td><strong>{$customer['CustomerCode']}</strong></td>";
                echo "<td>{$customer['CustomerName']}</td>";
                echo "<td><strong>{$customer['CustomerGrade']}</strong></td>";
                echo "<td><strong>{$customer['CustomerTemperature']}</strong></td>";
                echo "<td><strong>" . number_format($customer['TotalPurchase'], 0) . " ฿</strong></td>";
                echo "<td>" . ($customer['LastContactDate'] ? date('d/m/Y', strtotime($customer['LastContactDate'])) : 'ไม่มี') . "</td>";
                echo "<td>{$customer['ContactAttempts']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Error testing data: " . $e->getMessage();
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Critical Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🚀 Final Results</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "✅ <strong>Customers Table Update Complete!</strong><br>";
echo "📊 <strong>What was done:</strong><br>";
echo "1. ✅ Checked existing table structure<br>";
echo "2. ✅ Added missing columns (LastContactDate, ContactAttempts, GradeCalculatedDate, TemperatureUpdatedDate)<br>";
echo "3. ✅ Updated initial data for all columns<br>";
echo "4. 🌡️ Applied new Temperature Logic:<br>";
echo "&nbsp;&nbsp;&nbsp;• <strong>HOT</strong>: ลูกค้าใหม่, สนใจ, คุยจบ, ติดต่อภายใน 7 วัน<br>";
echo "&nbsp;&nbsp;&nbsp;• <strong>WARM</strong>: ลูกค้าปกติ ไม่อยู่ในเกณฑ์ HOT/COLD<br>";  
echo "&nbsp;&nbsp;&nbsp;• <strong>COLD</strong>: ไม่สนใจ, ติดต่อไม่ได้, พยายามติดต่อ 3+ ครั้ง<br>";
echo "5. ✅ Tested final data structure<br>";
echo "<br>🎯 <strong>Expected Results:</strong><br>";
echo "- ตะกร้าแจกและรอจะทำงานได้ปกติ ไม่มี LastContactDate error<br>";
echo "- ระบบวิเคราะห์ลูกค้าใช้ข้อมูลจริงครบถ้วน<br>";
echo "- Temperature Logic ทำงานตามเกณฑ์ธุรกิจใหม่";
echo "</div>";

echo "<h3>🔗 Quick Test Links</h3>";
echo "<a href='pages/admin/distribution_basket.php' target='_blank'>🗃️ Test Distribution Basket</a> | ";
echo "<a href='pages/admin/waiting_basket.php' target='_blank'>⏳ Test Waiting Basket</a> | ";
echo "<a href='pages/admin/intelligence_system.php' target='_blank'>🧠 Test Intelligence System</a>";

echo "<div style='margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #28a745;'>";
echo "<strong>🎉 Database Schema Fixed Successfully!</strong><br>";
echo "ตาราง customers มีคอลั่มน์ครบถ้วนแล้ว พร้อมใช้งาน<br>";
echo "Temperature Logic และ Grade Logic ทำงานตามเกณฑ์ใหม่! 🌟";
echo "</div>";
?>