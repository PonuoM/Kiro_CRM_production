<?php
/**
 * Quick Test for Story 1.3 Fixes
 * Tests the corrected assignment count logic
 */

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Quick Test - Story 1.3 Fixes</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;}</style>";
echo "</head><body>\n";

echo "<h1>🧪 Quick Test - Story 1.3 Fixes</h1>\n";
echo "<p><strong>Testing:</strong> Assignment Count Logic Corrections</p>\n";

$results = [];

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/SalesHistory.php';
    
    $salesHistory = new SalesHistory();
    
    echo "<div class='pass'>✅ Classes loaded successfully</div>\n";
    
    // Test 1: Method existence
    echo "<h2>🔧 Method Validation</h2>\n";
    
    $methods = ['incrementAssignmentCount', 'getAssignmentCount', 'resetAssignmentCount'];
    foreach ($methods as $method) {
        if (method_exists($salesHistory, $method)) {
            echo "<div class='pass'>✅ Method {$method}() exists</div>\n";
            $results["method_{$method}"] = true;
        } else {
            echo "<div class='fail'>❌ Method {$method}() missing</div>\n";
            $results["method_{$method}"] = false;
        }
    }
    
    // Test 2: Database schema
    echo "<h2>📊 Database Schema</h2>\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'AssignmentCount'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='pass'>✅ AssignmentCount column exists</div>\n";
        $results['schema'] = true;
    } else {
        echo "<div class='fail'>❌ AssignmentCount column missing</div>\n";
        $results['schema'] = false;
    }
    
    // Test 3: Basic functionality test (if safe)
    echo "<h2>🧪 Basic Functionality</h2>\n";
    
    // Check if we can safely test with existing data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers LIMIT 1");
    $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($customerCount > 0) {
        echo "<div class='info'>ℹ️ Found {$customerCount} customers in database - functionality validation possible</div>\n";
        $results['data_available'] = true;
        
        // Test getAssignmentCount with existing customer
        $stmt = $pdo->query("SELECT CustomerCode FROM customers LIMIT 1");
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            try {
                $count = $salesHistory->getAssignmentCount($customer['CustomerCode']);
                echo "<div class='pass'>✅ getAssignmentCount() working: Customer {$customer['CustomerCode']} has count {$count}</div>\n";
                $results['get_count_working'] = true;
            } catch (Exception $e) {
                echo "<div class='fail'>❌ getAssignmentCount() error: " . $e->getMessage() . "</div>\n";
                $results['get_count_working'] = false;
            }
        }
    } else {
        echo "<div class='info'>ℹ️ No customer data available for functionality testing</div>\n";
        $results['data_available'] = false;
    }
    
    // Test 4: Code integration check
    echo "<h2>🔗 Integration Check</h2>\n";
    
    $salesHistoryCode = file_get_contents(__DIR__ . '/includes/SalesHistory.php');
    $assignApiCode = file_get_contents(__DIR__ . '/api/sales/assign.php');
    
    $integrationChecks = [
        'incrementAssignmentCount in createSalesAssignment' => strpos($salesHistoryCode, 'incrementAssignmentCount($customerCode)') !== false,
        'assignment_count in API response' => strpos($assignApiCode, 'assignment_count') !== false,
        'getAssignmentCount in API' => strpos($assignApiCode, 'getAssignmentCount') !== false
    ];
    
    foreach ($integrationChecks as $check => $passed) {
        if ($passed) {
            echo "<div class='pass'>✅ {$check}</div>\n";
            $results["integration_{$check}"] = true;
        } else {
            echo "<div class='fail'>❌ {$check}</div>\n";
            $results["integration_{$check}"] = false;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='fail'>❌ Test failed: " . $e->getMessage() . "</div>\n";
    $results['exception'] = false;
}

// Summary
echo "<h2>📈 Test Summary</h2>\n";
$totalTests = count($results);
$passedTests = array_sum($results);
$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "<div class='info'>";
echo "<h3>Overall Result</h3>";
echo "Passed: $passedTests/$totalTests ($percentage%)<br>";

if ($percentage >= 80) {
    echo "<strong style='color: green;'>✅ FIXES LOOK GOOD!</strong><br>";
    echo "Story 1.3 implementation appears to be working correctly.";
} else {
    echo "<strong style='color: red;'>⚠️ ISSUES DETECTED</strong><br>";
    echo "Some components may need additional fixes.";
}
echo "</div>";

// Next steps
echo "<h2>📝 Next Steps</h2>\n";
echo "<div class='info'>";
if ($percentage >= 80) {
    echo "✅ Run the full test suite again to confirm fixes<br>";
    echo "✅ Test assignment operations in the CRM interface<br>";
    echo "✅ Verify API responses include assignment_count field<br>";
    echo "🎯 <strong>Story 1.3 should be ready for production!</strong>";
} else {
    echo "❌ Address the failing components above<br>";
    echo "❌ Re-run validation after fixes<br>";
    echo "❌ Check error logs for additional details";
}
echo "</div>";

echo "</body></html>\n";
?>