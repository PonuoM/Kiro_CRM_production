<?php
/**
 * Test Grade Calculation
 * ทดสอบการคำนวณ Grade เฉพาะ
 */

// Security check
if (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'kiro_grade_test_2024') {
    http_response_code(403);
    die("Access Denied");
}

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Grade Calculation Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.success{color:green;}.error{color:red;}.warning{color:orange;}.info{color:blue;}</style>";
echo "</head><body>";

try {
    echo "<h2>🧪 Grade Calculation Test</h2>";
    echo "<p>Time: " . date('Y-m-d H:i:s') . "</p>";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Check current customer data BEFORE grade update
    echo "<h3>📊 Current Customer Data (BEFORE Update)</h3>";
    $beforeSql = "SELECT CustomerCode, CustomerName, TotalPurchase, CustomerGrade, 
                         CASE 
                            WHEN TotalPurchase >= 810000 THEN 'A'
                            WHEN TotalPurchase >= 85000 THEN 'B' 
                            WHEN TotalPurchase >= 2000 THEN 'C'
                            ELSE 'D'
                         END as calculated_grade
                  FROM customers 
                  ORDER BY TotalPurchase DESC 
                  LIMIT 10";
    $stmt = $pdo->query($beforeSql);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Customer Code</th><th>Name</th><th>TotalPurchase</th><th>Current Grade</th><th>Should Be</th><th>Status</th></tr>";
    
    $needsUpdate = 0;
    foreach ($customers as $customer) {
        $isCorrect = $customer['CustomerGrade'] === $customer['calculated_grade'];
        if (!$isCorrect) $needsUpdate++;
        
        $statusClass = $isCorrect ? 'success' : 'warning';
        $statusText = $isCorrect ? '✅ Correct' : '⚠️ Needs Update';
        
        echo "<tr>";
        echo "<td>{$customer['CustomerCode']}</td>";
        echo "<td>" . substr($customer['CustomerName'], 0, 15) . "</td>";
        echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>";
        echo "<td>{$customer['CustomerGrade']}</td>";
        echo "<td>{$customer['calculated_grade']}</td>";
        echo "<td class='$statusClass'>$statusText</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Summary:</strong> $needsUpdate customers need grade updates</p>";
    
    if ($needsUpdate > 0) {
        // 2. Test the grade update SQL
        echo "<h3>🔧 Testing Grade Update</h3>";
        
        try {
            $updateSql = "
                UPDATE customers 
                SET CustomerGrade = CASE 
                        WHEN TotalPurchase >= 810000 THEN 'A'
                        WHEN TotalPurchase >= 85000 THEN 'B' 
                        WHEN TotalPurchase >= 2000 THEN 'C'
                        ELSE 'D'
                    END,
                    TotalPurchase = TotalPurchase
                WHERE CustomerCode IS NOT NULL
            ";
            
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute();
            $affectedRows = $updateStmt->rowCount();
            
            echo "<p class='success'>✅ Grade update successful! Affected rows: $affectedRows</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Grade update failed: " . $e->getMessage() . "</p>";
        }
        
        // 3. Check results AFTER update
        echo "<h3>📈 Results AFTER Update</h3>";
        $stmt = $pdo->query($beforeSql);
        $afterCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Customer Code</th><th>Name</th><th>TotalPurchase</th><th>New Grade</th><th>Should Be</th><th>Status</th></tr>";
        
        $stillIncorrect = 0;
        foreach ($afterCustomers as $customer) {
            $isCorrect = $customer['CustomerGrade'] === $customer['calculated_grade'];
            if (!$isCorrect) $stillIncorrect++;
            
            $statusClass = $isCorrect ? 'success' : 'error';
            $statusText = $isCorrect ? '✅ Correct' : '❌ Still Wrong';
            
            echo "<tr>";
            echo "<td>{$customer['CustomerCode']}</td>";
            echo "<td>" . substr($customer['CustomerName'], 0, 15) . "</td>";
            echo "<td>฿" . number_format($customer['TotalPurchase'], 2) . "</td>";
            echo "<td>{$customer['CustomerGrade']}</td>";
            echo "<td>{$customer['calculated_grade']}</td>";
            echo "<td class='$statusClass'>$statusText</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($stillIncorrect === 0) {
            echo "<p class='success'>🎉 All grades are now correct!</p>";
        } else {
            echo "<p class='error'>⚠️ $stillIncorrect customers still have incorrect grades</p>";
        }
        
        // 4. Show grade distribution
        echo "<h3>📊 Grade Distribution</h3>";
        $distSql = "SELECT CustomerGrade, COUNT(*) as count, 
                           MIN(TotalPurchase) as min_purchase,
                           MAX(TotalPurchase) as max_purchase
                    FROM customers 
                    GROUP BY CustomerGrade 
                    ORDER BY CustomerGrade";
        $stmt = $pdo->query($distSql);
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Grade</th><th>Count</th><th>Min Purchase</th><th>Max Purchase</th></tr>";
        foreach ($distribution as $dist) {
            echo "<tr>";
            echo "<td>{$dist['CustomerGrade']}</td>";
            echo "<td>{$dist['count']}</td>";
            echo "<td>฿" . number_format($dist['min_purchase'], 2) . "</td>";
            echo "<td>฿" . number_format($dist['max_purchase'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p class='success'>🎉 All customer grades are already correct!</p>";
    }
    
    // 5. Specific CUST003 check
    echo "<h3>🎯 CUST003 Specific Check</h3>";
    $stmt = $pdo->prepare("SELECT CustomerCode, CustomerName, TotalPurchase, CustomerGrade FROM customers WHERE CustomerCode = ?");
    $stmt->execute(['CUST003']);
    $cust003 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cust003) {
        $expectedGrade = $cust003['TotalPurchase'] >= 810000 ? 'A' : 
                        ($cust003['TotalPurchase'] >= 85000 ? 'B' : 
                        ($cust003['TotalPurchase'] >= 2000 ? 'C' : 'D'));
        
        echo "<p><strong>Customer:</strong> {$cust003['CustomerName']}</p>";
        echo "<p><strong>TotalPurchase:</strong> ฿" . number_format($cust003['TotalPurchase'], 2) . "</p>";
        echo "<p><strong>Current Grade:</strong> {$cust003['CustomerGrade']}</p>";
        echo "<p><strong>Expected Grade:</strong> $expectedGrade</p>";
        
        if ($cust003['CustomerGrade'] === $expectedGrade) {
            echo "<p class='success'>✅ CUST003 Grade is correct!</p>";
        } else {
            echo "<p class='error'>❌ CUST003 Grade is incorrect!</p>";
            echo "<p class='info'>💡 Running individual update for CUST003...</p>";
            
            try {
                $fixSql = "UPDATE customers SET CustomerGrade = ? WHERE CustomerCode = ?";
                $fixStmt = $pdo->prepare($fixSql);
                $fixStmt->execute([$expectedGrade, 'CUST003']);
                echo "<p class='success'>✅ CUST003 Grade updated to: $expectedGrade</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ CUST003 update failed: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ CUST003 not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>💥 FATAL ERROR: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>