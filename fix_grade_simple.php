<?php
/**
 * Simple Grade Fix
 * แก้ไข Grade อย่างง่าย
 */

// Security check
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'kiro_simple_fix_2024') {
    http_response_code(403);
    die("Access Denied");
}

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    echo "<h2>🔧 Simple Grade Fix</h2>";
    echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h3>1. Current Status Check</h3>";
    
    // Check current grades
    $checkSql = "SELECT 
                    CustomerCode, 
                    CustomerName, 
                    TotalPurchase, 
                    CustomerGrade,
                    CASE 
                        WHEN TotalPurchase >= 810000 THEN 'A'
                        WHEN TotalPurchase >= 85000 THEN 'B' 
                        WHEN TotalPurchase >= 2000 THEN 'C'
                        ELSE 'D'
                    END as correct_grade
                 FROM customers 
                 WHERE CustomerCode IN ('CUST003', 'CUST001', 'CUST002')
                 ORDER BY TotalPurchase DESC";
    
    $stmt = $pdo->query($checkSql);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Code</th><th>Name</th><th>TotalPurchase</th><th>Current</th><th>Should Be</th><th>Correct?</th></tr>";
    
    $needsUpdate = [];
    foreach ($customers as $customer) {
        $isCorrect = $customer['CustomerGrade'] === $customer['correct_grade'];
        if (!$isCorrect) {
            $needsUpdate[] = $customer;
        }
        
        echo "<tr>";
        echo "<td>{$customer['CustomerCode']}</td>";
        echo "<td>" . substr($customer['CustomerName'], 0, 10) . "</td>";
        echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>";
        echo "<td>{$customer['CustomerGrade']}</td>";
        echo "<td>{$customer['correct_grade']}</td>";
        echo "<td>" . ($isCorrect ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Grade Update Process</h3>";
    
    if (empty($needsUpdate)) {
        echo "<p style='color:green;'>✅ All grades are already correct!</p>";
    } else {
        echo "<p>Found " . count($needsUpdate) . " customers needing grade updates:</p>";
        
        foreach ($needsUpdate as $customer) {
            echo "<p>Updating {$customer['CustomerCode']}: {$customer['CustomerGrade']} → {$customer['correct_grade']}</p>";
            
            try {
                $updateSql = "UPDATE customers SET CustomerGrade = ? WHERE CustomerCode = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$customer['correct_grade'], $customer['CustomerCode']]);
                
                echo "<p style='color:green;'>✅ {$customer['CustomerCode']} updated successfully</p>";
                
            } catch (Exception $e) {
                echo "<p style='color:red;'>❌ {$customer['CustomerCode']} update failed: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<h3>3. Verification</h3>";
        
        // Re-check after update
        $stmt = $pdo->query($checkSql);
        $afterCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Code</th><th>Name</th><th>TotalPurchase</th><th>New Grade</th><th>Should Be</th><th>Correct?</th></tr>";
        
        $stillWrong = 0;
        foreach ($afterCustomers as $customer) {
            $isCorrect = $customer['CustomerGrade'] === $customer['correct_grade'];
            if (!$isCorrect) $stillWrong++;
            
            echo "<tr>";
            echo "<td>{$customer['CustomerCode']}</td>";
            echo "<td>" . substr($customer['CustomerName'], 0, 10) . "</td>";
            echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>";
            echo "<td>{$customer['CustomerGrade']}</td>";
            echo "<td>{$customer['correct_grade']}</td>";
            echo "<td>" . ($isCorrect ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($stillWrong === 0) {
            echo "<p style='color:green;'>🎉 All grades are now correct!</p>";
        } else {
            echo "<p style='color:red;'>⚠️ $stillWrong customers still have wrong grades</p>";
        }
    }
    
    echo "<h3>4. All Customers Grade Check</h3>";
    
    // Update all customers
    try {
        $massUpdateSql = "
            UPDATE customers 
            SET CustomerGrade = CASE 
                WHEN TotalPurchase >= 810000 THEN 'A'
                WHEN TotalPurchase >= 85000 THEN 'B' 
                WHEN TotalPurchase >= 2000 THEN 'C'
                ELSE 'D'
            END
            WHERE CustomerCode IS NOT NULL
        ";
        
        $massStmt = $pdo->prepare($massUpdateSql);
        $massStmt->execute();
        $affectedRows = $massStmt->rowCount();
        
        echo "<p style='color:green;'>✅ Mass grade update completed. Processed: $affectedRows customers</p>";
        
        // Final verification
        $finalCheckSql = "SELECT CustomerGrade, COUNT(*) as count FROM customers GROUP BY CustomerGrade ORDER BY CustomerGrade";
        $stmt = $pdo->query($finalCheckSql);
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Final Grade Distribution:</h4>";
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Grade</th><th>Count</th></tr>";
        foreach ($distribution as $dist) {
            echo "<tr><td>{$dist['CustomerGrade']}</td><td>{$dist['count']}</td></tr>";
        }
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Mass update failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>💥 FATAL ERROR: " . $e->getMessage() . "</p>";
}
?>