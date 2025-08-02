<?php
/**
 * Fix CartStatus Logic
 * Fix logical inconsistencies in CartStatus and Sales assignments
 */

require_once 'config/database.php';

header('Content-Type: application/json');

if ($_GET['action'] ?? '' === 'fix_cartstatus') {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Fix 1: ลูกค้าที่มี Sales แล้วแต่ CartStatus ยัง "ตะกร้าแจก"
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET CartStatus = 'ลูกค้าแจกแล้ว', 
                ModifiedDate = NOW(),
                ModifiedBy = 'system_fix'
            WHERE CartStatus = 'ตะกร้าแจก' 
            AND Sales IS NOT NULL 
            AND Sales != ''
        ");
        $stmt->execute();
        $updated1 = $stmt->rowCount();
        
        // Fix 2: ลูกค้าที่ CartStatus = "ลูกค้าแจกแล้ว" แต่ไม่มี Sales - เปลี่ยนกลับเป็น "ตะกร้าแจก"
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET CartStatus = 'ตะกร้าแจก',
                ModifiedDate = NOW(),
                ModifiedBy = 'system_fix'
            WHERE CartStatus = 'ลูกค้าแจกแล้ว' 
            AND (Sales IS NULL OR Sales = '')
        ");
        $stmt->execute();
        $updated2 = $stmt->rowCount();
        
        echo json_encode([
            'status' => 'success',
            'updated' => $updated1 + $updated2,
            'details' => [
                'moved_to_assigned' => $updated1,
                'moved_back_to_basket' => $updated2
            ],
            'message' => "Fixed CartStatus for {$updated1} assigned customers and {$updated2} unassigned customers"
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Default response
echo json_encode([
    'status' => 'error',
    'error' => 'Invalid action'
]);
?>