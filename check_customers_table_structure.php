<?php
/**
 * Check Customers Table Structure
 * ตรวจสอบโครงสร้างตาราง customers
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>🔍 ตรวจสอบโครงสร้างตาราง customers</h2>\n";
    echo "<p>กำลังตรวจสอบ...</p>\n";
    flush();
    
    // 1. ตรวจสอบ columns ในตาราง customers
    echo "<h3>1. Columns ในตาราง customers</h3>\n";
    
    $sql = "SHOW COLUMNS FROM customers";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    
    $hasGrade = false;
    $hasTotalPurchase = false;
    $hasLastPurchaseDate = false;
    $hasGradeCalculatedDate = false;
    
    foreach ($columns as $column) {
        echo "<tr>\n";
        echo "<td>{$column['Field']}</td>\n";
        echo "<td>{$column['Type']}</td>\n";
        echo "<td>{$column['Null']}</td>\n";
        echo "<td>{$column['Key']}</td>\n";
        echo "<td>{$column['Default']}</td>\n";
        echo "<td>{$column['Extra']}</td>\n";
        echo "</tr>\n";
        
        // ตรวจสอบ columns ที่สำคัญ
        if ($column['Field'] === 'CustomerGrade') $hasGrade = true;
        if ($column['Field'] === 'TotalPurchase') $hasTotalPurchase = true;
        if ($column['Field'] === 'LastPurchaseDate') $hasLastPurchaseDate = true;
        if ($column['Field'] === 'GradeCalculatedDate') $hasGradeCalculatedDate = true;
    }
    echo "</table>\n";
    
    // 2. ตรวจสอบ columns ที่จำเป็น
    echo "<h3>2. ตรวจสอบ Intelligence Columns</h3>\n";
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Column</th><th>Required</th><th>Exists</th><th>Status</th></tr>\n";
    
    $requiredColumns = [
        'CustomerGrade' => $hasGrade,
        'TotalPurchase' => $hasTotalPurchase,
        'LastPurchaseDate' => $hasLastPurchaseDate,
        'GradeCalculatedDate' => $hasGradeCalculatedDate
    ];
    
    foreach ($requiredColumns as $column => $exists) {
        $status = $exists ? '✅' : '❌';
        $rowClass = $exists ? 'style="background: #d4edda;"' : 'style="background: #f8d7da;"';
        echo "<tr {$rowClass}>\n";
        echo "<td>{$column}</td>\n";
        echo "<td>Yes</td>\n";
        echo "<td>" . ($exists ? 'Yes' : 'No') . "</td>\n";
        echo "<td>{$status}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 3. ตรวจสอบ orders table
    echo "<h3>3. ตรวจสอบตาราง orders</h3>\n";
    
    try {
        $orderSql = "SHOW COLUMNS FROM orders";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute();
        $orderColumns = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ ตาราง orders มีอยู่</p>\n";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th>Field</th><th>Type</th></tr>\n";
        
        foreach ($orderColumns as $column) {
            echo "<tr><td>{$column['Field']}</td><td>{$column['Type']}</td></tr>\n";
        }
        echo "</table>\n";
        
        // ตรวจสอบข้อมูล sample
        $sampleSql = "SELECT COUNT(*) as count FROM orders";
        $sampleStmt = $pdo->prepare($sampleSql);
        $sampleStmt->execute();
        $orderCount = $sampleStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>จำนวน orders: {$orderCount['count']} รายการ</p>\n";
        
    } catch (Exception $e) {
        echo "<p>❌ ตาราง orders ไม่มีอยู่หรือเกิดข้อผิดพลาด: " . $e->getMessage() . "</p>\n";
    }
    
    // 4. สร้างคำสั่ง SQL สำหรับเพิ่ม columns ที่ขาดหายไป
    echo "<h3>4. SQL Commands สำหรับแก้ไข</h3>\n";
    
    $missingColumns = [];
    if (!$hasGrade) $missingColumns[] = "ADD COLUMN CustomerGrade ENUM('A', 'B', 'C', 'D') NULL COMMENT 'Customer Grade based on purchase amount'";
    if (!$hasTotalPurchase) $missingColumns[] = "ADD COLUMN TotalPurchase DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total purchase amount for grading'";
    if (!$hasLastPurchaseDate) $missingColumns[] = "ADD COLUMN LastPurchaseDate DATE NULL COMMENT 'Last purchase date'";
    if (!$hasGradeCalculatedDate) $missingColumns[] = "ADD COLUMN GradeCalculatedDate DATETIME NULL COMMENT 'When grade was last calculated'";
    
    if (count($missingColumns) > 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>⚠️ ต้องเพิ่ม Columns ต่อไปนี้:</h4>\n";
        echo "<pre>\n";
        echo "ALTER TABLE customers \n";
        echo implode(",\n", $missingColumns) . ";\n";
        echo "</pre>\n";
        echo "</div>\n";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<h4>✅ ตาราง customers มี columns ครบถ้วนแล้ว</h4>\n";
        echo "</div>\n";
    }
    
    // 5. ตรวจสอบข้อมูล CUST003
    echo "<h3>5. ตรวจสอบข้อมูล CUST003</h3>\n";
    
    try {
        $cust003Sql = "SELECT CustomerCode, CustomerName, CustomerStatus";
        if ($hasGrade) $cust003Sql .= ", CustomerGrade";
        if ($hasTotalPurchase) $cust003Sql .= ", TotalPurchase";
        if ($hasGradeCalculatedDate) $cust003Sql .= ", GradeCalculatedDate";
        $cust003Sql .= " FROM customers WHERE CustomerCode = 'CUST003'";
        
        $cust003Stmt = $pdo->prepare($cust003Sql);
        $cust003Stmt->execute();
        $cust003 = $cust003Stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cust003) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            foreach ($cust003 as $field => $value) {
                echo "<tr><td>{$field}</td><td>{$value}</td></tr>\n";
            }
            echo "</table>\n";
        } else {
            echo "<p>❌ ไม่พบ CUST003</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>✅ การตรวจสอบเสร็จสิ้น</h3>\n";
    
    if (count($missingColumns) > 0) {
        echo "<p><strong>ขั้นตอนต่อไป:</strong></p>\n";
        echo "<ol>\n";
        echo "<li>รัน SQL commands ข้างต้นเพื่อเพิ่ม columns ที่ขาดหายไป</li>\n";
        echo "<li>รัน fix_customer_intelligence_grades.php อีกครั้ง</li>\n";
        echo "<li>ทดสอบระบบใหม่</li>\n";
        echo "</ol>\n";
    } else {
        echo "<p>พร้อมใช้งาน Intelligence System แล้ว!</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>