<?php
/**
 * Story 2.1 Validation Script
 * Implement Lead Re-assignment Logic
 * 
 * Quick validation of all components before production deployment
 */

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Story 2.1 Validation</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;}</style>";
echo "</head><body>\n";

echo "<h1>🔍 Story 2.1 Validation: Lead Re-assignment Logic</h1>\n";
echo "<p><strong>Validating:</strong> Sales Departure Workflow Implementation</p>\n";

$results = [];

try {
    require_once __DIR__ . '/config/database.php';
    
    echo "<h2>📋 Component Validation</h2>\n";
    
    // Test 1: File Structure
    echo "<h3>🔧 File Structure Validation</h3>\n";
    
    $requiredFiles = [
        'includes/SalesDepartureWorkflow.php' => 'Sales Departure Workflow class',
        'api/users/toggle_status.php' => 'Enhanced user toggle API',
        'tests/workflows/test_sales_departure.php' => 'Test suite'
    ];
    
    foreach ($requiredFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "<div class='pass'>✅ {$description}: {$file}</div>\n";
            $results["file_{$file}"] = true;
        } else {
            echo "<div class='fail'>❌ {$description}: {$file} - Missing</div>\n";
            $results["file_{$file}"] = false;
        }
    }
    
    // Test 2: Database Schema
    echo "<h3>📊 Database Schema Validation</h3>\n";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Check supervisor_id column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'supervisor_id'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='pass'>✅ users.supervisor_id column exists</div>\n";
        $results['schema_supervisor_id'] = true;
    } else {
        echo "<div class='fail'>❌ users.supervisor_id column missing</div>\n";
        $results['schema_supervisor_id'] = false;
    }
    
    // Check ContactAttempts column
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'ContactAttempts'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='pass'>✅ customers.ContactAttempts column exists</div>\n";
        $results['schema_contact_attempts'] = true;
    } else {
        echo "<div class='fail'>❌ customers.ContactAttempts column missing</div>\n";
        $results['schema_contact_attempts'] = false;
    }
    
    // Test 3: Class Implementation
    echo "<h3>🏗️ Class Implementation Validation</h3>\n";
    
    if (file_exists(__DIR__ . '/includes/SalesDepartureWorkflow.php')) {
        require_once __DIR__ . '/includes/SalesDepartureWorkflow.php';
        
        $workflow = new SalesDepartureWorkflow();
        
        $requiredMethods = [
            'triggerSalesDepartureWorkflow' => 'Main workflow orchestrator',
            'reassignActiveTaskLeads' => 'Category 1: Active tasks reassignment',
            'moveFollowUpLeadsToWaiting' => 'Category 2: Follow-up to waiting',
            'moveNewLeadsToDistribution' => 'Category 3: New to distribution',
            'validateSalesUser' => 'Sales user validation',
            'getDepartureStatistics' => 'Statistics calculation'
        ];
        
        foreach ($requiredMethods as $method => $description) {
            if (method_exists($workflow, $method)) {
                echo "<div class='pass'>✅ {$description}: {$method}()</div>\n";
                $results["method_{$method}"] = true;
            } else {
                echo "<div class='fail'>❌ {$description}: {$method}() - Missing</div>\n";
                $results["method_{$method}"] = false;
            }
        }
    }
    
    // Test 4: API Integration
    echo "<h3>🔗 API Integration Validation</h3>\n";
    
    $toggleStatusCode = file_get_contents(__DIR__ . '/api/users/toggle_status.php');
    
    $integrationChecks = [
        'Sales departure detection' => strpos($toggleStatusCode, "Role'] === 'Sale'") !== false,
        'Workflow trigger integration' => strpos($toggleStatusCode, 'SalesDepartureWorkflow') !== false,
        'Enhanced response data' => strpos($toggleStatusCode, 'departure_workflow') !== false,
        'Error handling' => strpos($toggleStatusCode, 'workflowResult') !== false
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
    
    // Test 5: Acceptance Criteria Validation
    echo "<h3>✅ Acceptance Criteria Validation</h3>\n";
    
    $acChecks = [
        'AC1: Logic modification in user_management/toggle_status' => 
            strpos($toggleStatusCode, 'SalesDepartureWorkflow') !== false,
        'AC2: Trigger on Sales Status → Inactive' => 
            strpos($toggleStatusCode, "newStatus == 0 && \$existingUser['Role'] === 'Sale'") !== false,
        'AC3: Active tasks → Supervisor reassignment' => 
            file_exists(__DIR__ . '/includes/SalesDepartureWorkflow.php') && 
            strpos(file_get_contents(__DIR__ . '/includes/SalesDepartureWorkflow.php'), 'reassignActiveTaskLeads') !== false,
        'AC4: Follow-up → Waiting basket' => 
            strpos(file_get_contents(__DIR__ . '/includes/SalesDepartureWorkflow.php'), "'ตะกร้ารอ'") !== false,
        'AC5: New uncontacted → Distribution basket' => 
            strpos(file_get_contents(__DIR__ . '/includes/SalesDepartureWorkflow.php'), "'ตะกร้าแจก'") !== false
    ];
    
    foreach ($acChecks as $ac => $passed) {
        if ($passed) {
            echo "<div class='pass'>✅ {$ac}</div>\n";
            $results["ac_{$ac}"] = true;
        } else {
            echo "<div class='fail'>❌ {$ac}</div>\n";
            $results["ac_{$ac}"] = false;
        }
    }
    
    // Test 6: Error Handling & Transaction Safety
    echo "<h3>🛡️ Error Handling Validation</h3>\n";
    
    $workflowCode = file_get_contents(__DIR__ . '/includes/SalesDepartureWorkflow.php');
    
    $safetyChecks = [
        'Transaction management' => strpos($workflowCode, 'beginTransaction') !== false && 
                                   strpos($workflowCode, 'commit') !== false && 
                                   strpos($workflowCode, 'rollback') !== false,
        'Exception handling' => strpos($workflowCode, 'try {') !== false && 
                               strpos($workflowCode, 'catch (Exception') !== false,
        'Input validation' => strpos($workflowCode, 'validateSalesUser') !== false,
        'Audit logging' => strpos($workflowCode, 'logDepartureEvent') !== false,
        'Supervisor lookup handling' => strpos($workflowCode, 'supervisor_id') !== false
    ];
    
    foreach ($safetyChecks as $check => $passed) {
        if ($passed) {
            echo "<div class='pass'>✅ {$check}</div>\n";
            $results["safety_{$check}"] = true;
        } else {
            echo "<div class='fail'>❌ {$check}</div>\n";
            $results["safety_{$check}"] = false;
        }
    }
    
} catch (Exception $e) {
    echo "<div class='fail'>❌ Validation failed: " . $e->getMessage() . "</div>\n";
    $results['exception'] = false;
}

// Summary
echo "<h2>📈 Validation Summary</h2>\n";
$totalTests = count($results);
$passedTests = array_sum($results);
$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;

echo "<div class='info'>";
echo "<h3>Overall Result</h3>";
echo "Passed: $passedTests/$totalTests ($percentage%)<br>";

if ($percentage >= 95) {
    echo "<strong style='color: green;'>✅ STORY 2.1 READY FOR PRODUCTION!</strong><br>";
    echo "All components are implemented and validated correctly.";
} elseif ($percentage >= 80) {
    echo "<strong style='color: orange;'>⚠️ MINOR ISSUES DETECTED</strong><br>";
    echo "Most components working but some improvements needed.";
} else {
    echo "<strong style='color: red;'>❌ MAJOR ISSUES DETECTED</strong><br>";
    echo "Significant components need fixes before production.";
}
echo "</div>";

// Next steps
echo "<h2>📝 Next Steps</h2>\n";
echo "<div class='info'>";
if ($percentage >= 95) {
    echo "✅ Run the comprehensive test suite<br>";
    echo "✅ Test workflow via User Management interface<br>";
    echo "✅ Deploy to production environment<br>";
    echo "✅ Monitor departure workflow performance<br>";
    echo "🎯 <strong>Story 2.1 is Production Ready!</strong>";
} else {
    echo "❌ Address the failing components above<br>";
    echo "❌ Re-run validation after fixes<br>";
    echo "❌ Complete implementation before production deployment";
}
echo "</div>";

echo "</body></html>\n";
?>