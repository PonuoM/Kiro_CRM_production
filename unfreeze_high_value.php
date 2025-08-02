<?php
/**
 * Unfreeze High-Value Customers
 * Remove FROZEN status from Grade A/B customers
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $pdo->beginTransaction();
    
    // Unfreeze high-value customers (Grade A/B or TotalPurchase >= 5000)
    $unfreezeSql = "
        UPDATE customers 
        SET CustomerTemperature = 'WARM',
            ModifiedDate = NOW(),
            ModifiedBy = 'unfreeze_high_value_system'
        WHERE CustomerTemperature = 'FROZEN'
        AND (
            CustomerGrade IN ('A', 'B') 
            OR COALESCE(TotalPurchase, 0) >= 5000
        )
        AND COALESCE(ContactAttempts, 0) < 3
    ";
    
    $stmt = $pdo->prepare($unfreezeSql);
    $stmt->execute();
    $unfrozenCount = $stmt->rowCount();
    
    // Get breakdown by grade
    $statsSql = "
        SELECT 
            CustomerGrade,
            COUNT(*) as count
        FROM customers 
        WHERE ModifiedBy = 'unfreeze_high_value_system'
        AND DATE(ModifiedDate) = CURDATE()
        GROUP BY CustomerGrade
    ";
    
    $stmt = $pdo->prepare($statsSql);
    $stmt->execute();
    $gradeBreakdown = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gradeBreakdown[$row['CustomerGrade']] = $row['count'];
    }
    
    // Log the action
    $logSql = "
        INSERT INTO customer_activity_log 
        (customer_code, customer_name, action, details, changed_by, change_timestamp)
        SELECT 
            CustomerCode,
            CustomerName,
            'unfreeze_high_value',
            JSON_OBJECT(
                'previous_temperature', 'FROZEN',
                'new_temperature', 'WARM',
                'reason', 'High-value customer should not be FROZEN',
                'grade', CustomerGrade,
                'total_purchase', COALESCE(TotalPurchase, 0)
            ),
            'unfreeze_high_value_system',
            NOW()
        FROM customers 
        WHERE ModifiedBy = 'unfreeze_high_value_system'
        AND DATE(ModifiedDate) = CURDATE()
    ";
    
    try {
        $stmt = $pdo->prepare($logSql);
        $stmt->execute();
    } catch (Exception $e) {
        // Log table might not exist, continue anyway
    }
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'High-value customers unfrozen successfully',
        'unfrozen_count' => $unfrozenCount,
        'grade_breakdown' => $gradeBreakdown,
        'criteria' => [
            'target' => 'Grade A/B customers or TotalPurchase >= 5000',
            'condition' => 'ContactAttempts < 3',
            'action' => 'Changed from FROZEN to WARM'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>