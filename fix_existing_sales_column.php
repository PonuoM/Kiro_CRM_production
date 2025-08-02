<?php
/**
 * Fix Existing Sales Column - แก้ไขลูกค้าใน ตะกร้ารอ ที่ยังมี Sales
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>🔧 Fix Sales Column</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>body{font-family:Arial,sans-serif;padding:15px;} .section{margin:15px 0;padding:15px;border:2px solid #ddd;border-radius:8px;background:white;}</style>";
echo "</head><body>";

echo "<h1>🔧 Fix Sales Column in ตะกร้ารอ</h1>";
echo "<p>แก้ไขลูกค้าใน ตะกร้ารอ ที่ยังมี Sales Column</p>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // ตรวจสอบลูกค้าใน ตะกร้ารอ ที่ยังมี Sales
    echo "<div class='section'>";
    echo "<h3>📋 ลูกค้าใน ตะกร้ารอ ที่ยังมี Sales</h3>";
    
    $sql = "
        SELECT CustomerCode, CustomerName, CartStatus, Sales, CustomerTemperature, ModifiedBy, ModifiedDate
        FROM customers 
        WHERE CartStatus IN ('ตะกร้ารอ', 'ตะกร้าแจก') 
        AND Sales IS NOT NULL 
        AND Sales != ''
        ORDER BY CartStatus, CustomerCode
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $problematicCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($problematicCustomers)) {
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>รหัส</th><th>ชื่อ</th><th>CartStatus</th><th>Sales</th><th>Temperature</th><th>ModifiedBy</th><th>ModifiedDate</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($problematicCustomers as $customer) {
            $rowClass = $customer['CartStatus'] === 'ตะกร้ารอ' ? 'table-warning' : 'table-info';
            
            echo "<tr class='$rowClass'>";
            echo "<td><strong>{$customer['CustomerCode']}</strong></td>";
            echo "<td>" . substr($customer['CustomerName'], 0, 20) . "...</td>";
            echo "<td><strong>{$customer['CartStatus']}</strong></td>";
            echo "<td><strong style='color:red;'>{$customer['Sales']}</strong></td>";
            echo "<td>{$customer['CustomerTemperature']}</td>";
            echo "<td>{$customer['ModifiedBy']}</td>";
            echo "<td>{$customer['ModifiedDate']}</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
        
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ ปัญหาที่พบ:</h4>";
        echo "<ul>";
        echo "<li><strong>จำนวนลูกค้าที่มีปัญหา:</strong> " . count($problematicCustomers) . " ราย</li>";
        echo "<li><strong>ปัญหา:</strong> ลูกค้าใน ตะกร้ารอ/ตะกร้าแจก ยังมี Sales column</li>";
        echo "<li><strong>ผลกระทบ:</strong> Frontend ยังแสดงลูกค้าใน user นั้นๆ อยู่</li>";
        echo "</ul>";
        echo "</div>";
        
        // แก้ไขปัญหา
        echo "<h4>🔧 กำลังแก้ไขปัญหา...</h4>";
        
        $fixCount = 0;
        
        foreach ($problematicCustomers as $customer) {
            $updateSql = "
                UPDATE customers 
                SET Sales = NULL,
                    ModifiedDate = NOW(),
                    ModifiedBy = 'sales_column_fix'
                WHERE CustomerCode = ?
            ";
            
            $updateStmt = $pdo->prepare($updateSql);
            
            if ($updateStmt->execute([$customer['CustomerCode']])) {
                $fixCount++;
                echo "<p>✅ แก้ไข {$customer['CustomerCode']} - ลบ Sales '{$customer['Sales']}'</p>";
            } else {
                echo "<p>❌ ไม่สามารถแก้ไข {$customer['CustomerCode']} ได้</p>";
            }
        }
        
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ แก้ไขเสร็จสิ้น!</h4>";
        echo "<ul>";
        echo "<li><strong>จำนวนที่แก้ไข:</strong> $fixCount / " . count($problematicCustomers) . " ราย</li>";
        echo "<li><strong>ผลลัพธ์:</strong> ลูกค้าใน ตะกร้ารอ/ตะกร้าแจก จะไม่แสดงใน Frontend ของ Sales แล้ว</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ ไม่พบปัญหา!</h4>";
        echo "<p>ไม่มีลูกค้าใน ตะกร้ารอ/ตะกร้าแจก ที่ยังมี Sales column</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // ตรวจสอบผลลัพธ์หลังแก้ไข
    echo "<div class='section'>";
    echo "<h3>🔍 ตรวจสอบผลลัพธ์หลังแก้ไข</h3>";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $remainingProblems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($remainingProblems)) {
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ แก้ไขสำเร็จ!</h4>";
        echo "<p>ไม่มีลูกค้าใน ตะกร้ารอ/ตะกร้าแจก ที่ยังมี Sales column แล้ว</p>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>";
        echo "<h4>⚠️ ยังมีปัญหาเหลืออยู่</h4>";
        echo "<p>ยังมีลูกค้า " . count($remainingProblems) . " รายที่ยังมีปัญหา</p>";
        echo "</div>";
    }
    
    // แสดงสถิติสุดท้าย
    echo "<h4>📊 สถิติสุดท้าย</h4>";
    
    $statsSql = "
        SELECT 
            CartStatus,
            COUNT(*) as CustomerCount,
            SUM(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 ELSE 0 END) as WithSales
        FROM customers 
        WHERE CartStatus IN ('กำลังดูแล', 'ตะกร้ารอ', 'ตะกร้าแจก')
        GROUP BY CartStatus
        ORDER BY CartStatus
    ";
    
    $stmt = $pdo->prepare($statsSql);
    $stmt->execute();
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table class='table table-sm table-bordered'>";
    echo "<thead><tr><th>CartStatus</th><th>จำนวนลูกค้า</th><th>ที่มี Sales</th><th>สถานะ</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($stats as $stat) {
        $status = $stat['WithSales'] == 0 ? '✅ ถูกต้อง' : '⚠️ มีปัญหา';
        $rowClass = $stat['WithSales'] == 0 ? 'table-success' : 'table-warning';
        
        echo "<tr class='$rowClass'>";
        echo "<td><strong>{$stat['CartStatus']}</strong></td>";
        echo "<td>{$stat['CustomerCount']} ราย</td>";
        echo "<td><strong>{$stat['WithSales']} ราย</strong></td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<div class='section'>";
echo "<h3>💡 สรุปการแก้ไข</h3>";
echo "<div class='alert alert-info'>";
echo "<h4>🎯 ที่ทำไป:</h4>";
echo "<ol>";
echo "<li>แก้ไข Auto Rules ให้ clear Sales column เมื่อย้ายลูกค้า</li>";
echo "<li>แก้ไขลูกค้าที่มีอยู่แล้วใน ตะกร้ารอ/ตะกร้าแจก</li>";
echo "<li>ตรวจสอบว่าไม่มีปัญหาเหลืออยู่</li>";
echo "</ol>";

echo "<h4>🔮 ผลลัพธ์:</h4>";
echo "<ul>";
echo "<li>✅ ลูกค้าใน ตะกร้ารอ/ตะกร้าแจก จะไม่แสดงใน Frontend ของ Sales</li>";
echo "<li>✅ Auto Rules ใหม่จะ clear Sales column อัตโนมัติ</li>";
echo "<li>✅ ปัญหา Frontend แสดงลูกค้าผิดจะหายไป</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>