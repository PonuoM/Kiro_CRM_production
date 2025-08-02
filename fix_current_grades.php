<?php
/**
 * Fix Current Grade Issues
 * Correct customer grades based on TotalPurchase amounts
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $pdo->beginTransaction();
    
    // Fix grades based on TotalPurchase
    $fixSql = "
        UPDATE customers 
        SET CustomerGrade = CASE 
            WHEN COALESCE(TotalPurchase, 0) >= 10000 THEN 'A'
            WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN 'B'
            WHEN COALESCE(TotalPurchase, 0) >= 2000 THEN 'C'
            ELSE 'D'
        END,
        GradeCalculatedDate = NOW(),
        ModifiedDate = NOW(),
        ModifiedBy = 'grade_fix_system'
        WHERE CustomerGrade != CASE 
            WHEN COALESCE(TotalPurchase, 0) >= 10000 THEN 'A'
            WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN 'B'
            WHEN COALESCE(TotalPurchase, 0) >= 2000 THEN 'C'
            ELSE 'D'
        END
    ";
    
    $stmt = $pdo->prepare($fixSql);
    $stmt->execute();
    $fixedCount = $stmt->rowCount();
    
    // Get breakdown of changes
    $statsSql = "
        SELECT 
            CustomerGrade,
            COUNT(*) as count
        FROM customers
        WHERE ModifiedBy = 'grade_fix_system'
        AND DATE(ModifiedDate) = CURDATE()
        GROUP BY CustomerGrade
    ";
    
    $stmt = $pdo->prepare($statsSql);
    $stmt->execute();
    $gradeChanges = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gradeChanges[$row['CustomerGrade']] = $row['count'];
    }
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Customer grades fixed successfully',
        'fixed_count' => $fixedCount,
        'grade_changes' => $gradeChanges,
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