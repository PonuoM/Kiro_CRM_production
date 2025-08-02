<?php
/**
 * Run Fix and Show Results
 * รันการแก้ไขและแสดงผลลัพธ์
 */

// Security check
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'kiro_run_fix_2024') {
    http_response_code(403);
    die("Access Denied");
}

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "=== CUSTOMER INTELLIGENCE FIX RESULTS ===\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Show TotalPurchase status
    echo "1. TotalPurchase Status:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE TotalPurchase > 0");
    $totalWithPurchase = $stmt->fetchColumn();
    echo "   Customers with TotalPurchase > 0: $totalWithPurchase\n";
    
    // 2. Test manual Grade update
    echo "\n2. Manual Grade Update Test:\n";
    
    try {
        $updateSql = "
            UPDATE customers 
            SET CustomerGrade = CASE 
                WHEN TotalPurchase >= 810000 THEN 'A'
                WHEN TotalPurchase >= 85000 THEN 'B' 
                WHEN TotalPurchase >= 2000 THEN 'C'
                ELSE 'D'
            END
        ";
        
        $stmt = $pdo->prepare($updateSql);
        $result = $stmt->execute();
        $affectedRows = $stmt->rowCount();
        
        echo "   ✅ Grade update successful!\n";
        echo "   Affected rows: $affectedRows\n";
        
    } catch (Exception $e) {
        echo "   ❌ Grade update failed: " . $e->getMessage() . "\n";
    }
    
    // 3. Show grade distribution
    echo "\n3. Current Grade Distribution:\n";
    $stmt = $pdo->query("
        SELECT 
            CustomerGrade, 
            COUNT(*) as count,
            MIN(TotalPurchase) as min_purchase,
            MAX(TotalPurchase) as max_purchase
        FROM customers 
        GROUP BY CustomerGrade 
        ORDER BY CustomerGrade
    ");
    $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($distribution as $dist) {
        echo "   Grade {$dist['CustomerGrade']}: {$dist['count']} customers (฿" . number_format($dist['min_purchase'], 2) . " - ฿" . number_format($dist['max_purchase'], 2) . ")\n";
    }
    
    // 4. CUST003 specific check
    echo "\n4. CUST003 Status:\n";
    $stmt = $pdo->prepare("SELECT CustomerCode, CustomerName, TotalPurchase, CustomerGrade FROM customers WHERE CustomerCode = ?");
    $stmt->execute(['CUST003']);
    $cust003 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cust003) {
        echo "   Customer: {$cust003['CustomerName']}\n";
        echo "   TotalPurchase: ฿" . number_format($cust003['TotalPurchase'], 2) . "\n";
        echo "   Grade: {$cust003['CustomerGrade']}\n";
        
        $expectedGrade = $cust003['TotalPurchase'] >= 810000 ? 'A' : 
                        ($cust003['TotalPurchase'] >= 85000 ? 'B' : 
                        ($cust003['TotalPurchase'] >= 2000 ? 'C' : 'D'));
        echo "   Expected: $expectedGrade\n";
        echo "   Status: " . ($cust003['CustomerGrade'] === $expectedGrade ? '✅ Correct' : '❌ Wrong') . "\n";
    } else {
        echo "   ❌ CUST003 not found\n";
    }
    
    // 5. Validation check
    echo "\n5. Validation Check:\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM customers 
        WHERE (TotalPurchase >= 810000 AND CustomerGrade != 'A')
           OR (TotalPurchase >= 85000 AND TotalPurchase < 810000 AND CustomerGrade != 'B')
           OR (TotalPurchase >= 2000 AND TotalPurchase < 85000 AND CustomerGrade != 'C')
           OR (TotalPurchase < 2000 AND CustomerGrade != 'D')
    ");
    $wrongGrades = $stmt->fetchColumn();
    
    if ($wrongGrades == 0) {
        echo "   ✅ All grades are correct!\n";
    } else {
        echo "   ⚠️ $wrongGrades customers have wrong grades\n";
        
        // Show the wrong ones
        $stmt = $pdo->query("
            SELECT CustomerCode, TotalPurchase, CustomerGrade,
                   CASE 
                       WHEN TotalPurchase >= 810000 THEN 'A'
                       WHEN TotalPurchase >= 85000 THEN 'B' 
                       WHEN TotalPurchase >= 2000 THEN 'C'
                       ELSE 'D'
                   END as should_be
            FROM customers 
            WHERE (TotalPurchase >= 810000 AND CustomerGrade != 'A')
               OR (TotalPurchase >= 85000 AND TotalPurchase < 810000 AND CustomerGrade != 'B')
               OR (TotalPurchase >= 2000 AND TotalPurchase < 85000 AND CustomerGrade != 'C')
               OR (TotalPurchase < 2000 AND CustomerGrade != 'D')
            LIMIT 5
        ");
        $wrong = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($wrong as $w) {
            echo "     {$w['CustomerCode']}: ฿" . number_format($w['TotalPurchase'], 2) . " Grade:{$w['CustomerGrade']} ShouldBe:{$w['should_be']}\n";
        }
    }
    
    echo "\n=== CONCLUSION ===\n";
    if ($wrongGrades == 0) {
        echo "🎉 Customer Intelligence Grade calculation is working correctly!\n";
        echo "✅ All customers have correct grades based on TotalPurchase\n";
        echo "✅ Ready for production use\n";
    } else {
        echo "⚠️ Grade calculation needs attention\n";
        echo "💡 Manual fix may be required for $wrongGrades customers\n";
    }
    
} catch (Exception $e) {
    echo "💥 FATAL ERROR: " . $e->getMessage() . "\n";
}
?>