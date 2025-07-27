<?php
/**
 * Fix Missing AssignDate in Database
 * Updates AssignDate for customers that have Sales assigned but missing AssignDate
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "🔧 เริ่มต้นการแก้ไข AssignDate ที่ขาดหาย\n";
    echo "เวลา: " . date('Y-m-d H:i:s') . "\n\n";
    
    // ตรวจสอบลูกค้าที่มี Sales แต่ไม่มี AssignDate
    $checkSql = "SELECT CustomerCode, CustomerName, Sales, CreatedDate, AssignDate 
                FROM customers 
                WHERE Sales IS NOT NULL 
                AND Sales != '' 
                AND (AssignDate IS NULL OR AssignDate = '')
                ORDER BY CreatedDate DESC";
    
    $stmt = $pdo->prepare($checkSql);
    $stmt->execute();
    $customersToFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 ลูกค้าที่ต้องแก้ไข: " . count($customersToFix) . " รายการ\n\n";
    
    if (count($customersToFix) === 0) {
        echo "✅ ไม่มีข้อมูลที่ต้องแก้ไข\n";
        exit;
    }
    
    // แสดงตัวอย่างข้อมูลที่จะแก้ไข
    echo "📋 ตัวอย่างข้อมูลที่จะแก้ไข:\n";
    foreach (array_slice($customersToFix, 0, 5) as $customer) {
        echo "- {$customer['CustomerName']} (Sales: {$customer['Sales']}, CreatedDate: {$customer['CreatedDate']})\n";
    }
    
    if (count($customersToFix) > 5) {
        echo "... และอีก " . (count($customersToFix) - 5) . " รายการ\n";
    }
    echo "\n";
    
    // เริ่มการอัปเดต
    $pdo->beginTransaction();
    
    $updateSql = "UPDATE customers 
                  SET AssignDate = CreatedDate,
                      ModifiedDate = NOW(),
                      ModifiedBy = 'system_fix'
                  WHERE CustomerCode = ?";
    
    $updateStmt = $pdo->prepare($updateSql);
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($customersToFix as $customer) {
        try {
            $updateStmt->execute([$customer['CustomerCode']]);
            $successCount++;
            
            if ($successCount <= 5) {
                echo "✅ อัปเดต: {$customer['CustomerName']} -> AssignDate = {$customer['CreatedDate']}\n";
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ ข้อผิดพลาด: {$customer['CustomerCode']} - {$e->getMessage()}\n";
        }
    }
    
    if ($successCount > 5) {
        echo "... อัปเดตสำเร็จอีก " . ($successCount - 5) . " รายการ\n";
    }
    
    // Commit การเปลี่ยนแปลง
    $pdo->commit();
    
    echo "\n📈 สรุปผลการแก้ไข:\n";
    echo "✅ สำเร็จ: {$successCount} รายการ\n";
    echo "❌ ผิดพลาด: {$errorCount} รายการ\n";
    
    // ตรวจสอบผลลัพธ์หลังการแก้ไข
    echo "\n🔍 ตรวจสอบผลลัพธ์:\n";
    
    $verifyStmt = $pdo->prepare($checkSql);
    $verifyStmt->execute();
    $remainingIssues = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "ลูกค้าที่ยังมีปัญหา: " . count($remainingIssues) . " รายการ\n";
    
    if (count($remainingIssues) === 0) {
        echo "🎉 แก้ไขสำเร็จทั้งหมด!\n";
    }
    
    // สถิติหลังการแก้ไข
    $statsSql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' THEN 1 END) as assigned_customers,
                    COUNT(CASE WHEN Sales IS NOT NULL AND Sales != '' AND AssignDate IS NOT NULL THEN 1 END) as customers_with_assign_date,
                    COUNT(CASE WHEN Sales IS NULL OR Sales = '' THEN 1 END) as unassigned_customers
                FROM customers";
    
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n📊 สถิติหลังการแก้ไข:\n";
    echo "- ลูกค้าทั้งหมด: {$stats['total_customers']}\n";
    echo "- ลูกค้าที่มี Sales: {$stats['assigned_customers']}\n";
    echo "- ลูกค้าที่มี AssignDate: {$stats['customers_with_assign_date']}\n";
    echo "- ลูกค้าที่ไม่มี Sales: {$stats['unassigned_customers']}\n";
    
    echo "\n✅ การแก้ไขเสร็จสิ้น\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "\n";
    echo "ไฟล์: " . $e->getFile() . " บรรทัด: " . $e->getLine() . "\n";
}
?>