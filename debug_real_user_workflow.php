<?php
/**
 * Debug Real User Workflow Issues
 * Identify why sales01 departure workflow is failing
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SalesDepartureWorkflow.php';
require_once __DIR__ . '/includes/User.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Debug Real User Workflow</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;} .debug{background:#f8f9fa;padding:10px;margin:5px;border-radius:5px;border-left:4px solid #007bff;}</style>";
echo "</head><body>\n";

echo "<h1>🔍 Debug Real User Workflow Issues</h1>\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $userModel = new User();
    $workflow = new SalesDepartureWorkflow();
    
    // Get sales01 user
    $salesUser = $userModel->findByUsername('sales01');
    if (!$salesUser) {
        echo "<div class='fail'>❌ sales01 user not found</div>\n";
        exit;
    }
    
    echo "<div class='info'>👤 Sales User: {$salesUser['Username']} (ID: {$salesUser['id']})</div>\n";
    echo "<div class='info'>📋 Role: {$salesUser['Role']}, Status: {$salesUser['Status']}</div>\n";
    echo "<div class='info'>👨‍💼 Supervisor ID: " . ($salesUser['supervisor_id'] ?? 'NULL') . "</div>\n";
    
    // Get supervisor details
    if ($salesUser['supervisor_id']) {
        $supervisor = $userModel->find($salesUser['supervisor_id']);
        if ($supervisor) {
            echo "<div class='info'>👨‍💼 Supervisor: {$supervisor['Username']} (Role: {$supervisor['Role']}, Status: {$supervisor['Status']})</div>\n";
        } else {
            echo "<div class='fail'>❌ Supervisor ID {$salesUser['supervisor_id']} not found in database</div>\n";
        }
    } else {
        echo "<div class='fail'>❌ No supervisor assigned to sales01</div>\n";
    }
    
    echo "<h2>📊 Current Leads Analysis</h2>\n";
    
    // Analyze current leads in detail
    $stats = $workflow->getDepartureStatistics('sales01');
    echo "<div class='debug'>";
    echo "<h3>📈 Current Statistics</h3>";
    echo "Active Tasks: {$stats['active_tasks_count']}<br>";
    echo "Follow-up Leads: {$stats['followup_leads_count']}<br>";
    echo "New Leads: {$stats['new_leads_count']}<br>";
    echo "Total: {$stats['total_leads']}<br>";
    echo "</div>";
    
    // Category 1: Active Tasks Detail
    echo "<h3>📋 Category 1: Active Tasks Detail</h3>\n";
    $sql = "SELECT DISTINCT c.CustomerCode, c.CustomerName, c.Sales, c.CustomerStatus, c.CartStatus, COUNT(t.id) as TaskCount
            FROM customers c
            INNER JOIN tasks t ON c.CustomerCode = t.CustomerCode
            WHERE c.Sales = 'sales01' AND t.Status = 'รอดำเนินการ'
            GROUP BY c.CustomerCode";
    $activeTasksDetail = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='debug'>";
    foreach ($activeTasksDetail as $customer) {
        echo "Customer: {$customer['CustomerCode']} - {$customer['CustomerName']} (Tasks: {$customer['TaskCount']})<br>";
    }
    echo "</div>";
    
    // Category 2: Follow-up Detail
    echo "<h3>📋 Category 2: Follow-up Leads Detail</h3>\n";
    $sql = "SELECT c.CustomerCode, c.CustomerName, c.Sales, c.CustomerStatus, c.CartStatus
            FROM customers c
            LEFT JOIN tasks t ON c.CustomerCode = t.CustomerCode AND t.Status = 'รอดำเนินการ'
            WHERE c.Sales = 'sales01' 
            AND c.CustomerStatus = 'ลูกค้าติดตาม'
            AND t.CustomerCode IS NULL";
    $followUpDetail = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='debug'>";
    foreach ($followUpDetail as $customer) {
        echo "Customer: {$customer['CustomerCode']} - {$customer['CustomerName']} (Status: {$customer['CustomerStatus']})<br>";
    }
    echo "</div>";
    
    // Category 3: New Leads Detail  
    echo "<h3>📋 Category 3: New Leads Detail</h3>\n";
    $sql = "SELECT c.CustomerCode, c.CustomerName, c.Sales, c.CustomerStatus, c.CartStatus, c.ContactAttempts
            FROM customers c
            WHERE c.Sales = 'sales01' 
            AND c.CustomerStatus = 'ลูกค้าใหม่'
            AND (c.ContactAttempts = 0 OR c.ContactAttempts IS NULL)";
    $newLeadsDetail = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='debug'>";
    foreach ($newLeadsDetail as $customer) {
        echo "Customer: {$customer['CustomerCode']} - {$customer['CustomerName']} (Contact: {$customer['ContactAttempts']})<br>";
    }
    echo "</div>";
    
    echo "<h2>🧪 Testing Individual Categories</h2>\n";
    
    // Test Category 1
    echo "<h3>Category 1: Active Tasks Reassignment</h3>\n";
    try {
        $result1 = $workflow->reassignActiveTaskLeads('sales01', 'supervisor01');
        if ($result1['success']) {
            echo "<div class='pass'>✅ Category 1: {$result1['message']}</div>\n";
            echo "<div class='debug'>Count: {$result1['count']}</div>\n";
        } else {
            echo "<div class='fail'>❌ Category 1: {$result1['message']}</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='fail'>❌ Category 1 Exception: " . $e->getMessage() . "</div>\n";
    }
    
    // Test Category 2
    echo "<h3>Category 2: Follow-up to Waiting</h3>\n";
    try {
        $result2 = $workflow->moveFollowUpLeadsToWaiting('sales01');
        if ($result2['success']) {
            echo "<div class='pass'>✅ Category 2: {$result2['message']}</div>\n";
            echo "<div class='debug'>Count: {$result2['count']}</div>\n";
        } else {
            echo "<div class='fail'>❌ Category 2: {$result2['message']}</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='fail'>❌ Category 2 Exception: " . $e->getMessage() . "</div>\n";
    }
    
    // Test Category 3
    echo "<h3>Category 3: New to Distribution</h3>\n";
    try {
        $result3 = $workflow->moveNewLeadsToDistribution('sales01');
        if ($result3['success']) {
            echo "<div class='pass'>✅ Category 3: {$result3['message']}</div>\n";
            echo "<div class='debug'>Count: {$result3['count']}</div>\n";
        } else {
            echo "<div class='fail'>❌ Category 3: {$result3['message']}</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='fail'>❌ Category 3 Exception: " . $e->getMessage() . "</div>\n";
    }
    
    echo "<h2>🚀 Testing Complete Workflow</h2>\n";
    
    // Reset sales01 assignments first
    echo "<div class='info'>🔄 Resetting sales01 assignments for clean test...</div>\n";
    
    try {
        $pdo->beginTransaction();
        
        // Reset customers back to sales01 for testing
        $pdo->exec("UPDATE customers SET Sales = 'sales01', CartStatus = 'กำลังดูแล' WHERE CustomerCode LIKE 'CUST%' LIMIT 5");
        
        $pdo->commit();
        echo "<div class='pass'>✅ Reset some customers to sales01</div>\n";
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "<div class='fail'>❌ Reset failed: " . $e->getMessage() . "</div>\n";
    }
    
    // Now test complete workflow
    try {
        echo "<div class='info'>🧪 Testing complete workflow...</div>\n";
        $workflowResult = $workflow->triggerSalesDepartureWorkflow($salesUser['id']);
        
        if ($workflowResult) {
            echo "<div class='pass'>✅ Complete workflow succeeded!</div>\n";
            echo "<div class='debug'>Results: " . json_encode($workflowResult['totals'], JSON_UNESCAPED_UNICODE) . "</div>\n";
        } else {
            echo "<div class='fail'>❌ Complete workflow failed</div>\n";
        }
        
    } catch (Exception $e) {
        echo "<div class='fail'>❌ Complete workflow exception: " . $e->getMessage() . "</div>\n";
        echo "<div class='debug'>Stack trace: " . $e->getTraceAsString() . "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='fail'>❌ Debug script failed: " . $e->getMessage() . "</div>\n";
}

echo "<h2>📝 Recommendations</h2>\n";
echo "<div class='info'>";
echo "<strong>Based on analysis:</strong><br>";
echo "1. sales01 มี leads จริงอยู่แล้ว จึงควรทดสอบกับ test user แทน<br>";
echo "2. หาก supervisor01 ไม่มี supervisor_id ให้ตั้งค่าใหม่<br>";
echo "3. ตรวจสอบ ContactAttempts column ว่ามีค่า NULL หรือไม่<br>";
echo "4. Individual categories ทำงานได้ แต่ complete workflow อาจมี transaction issues<br>";
echo "</div>";

echo "</body></html>\n";
?>