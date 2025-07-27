<?php
/**
 * Test Sales Departure Workflow with Real Users
 * Use existing users: admin/admin123, supervisor01/supervisor123, sales01/sale123
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/SalesDepartureWorkflow.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Customer.php';

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Real Users Departure Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;} .warning{background:#fff3cd;padding:10px;margin:5px;border-radius:5px;}</style>";
echo "</head><body>\n";

echo "<h1>🧪 Test Sales Departure with Real Users</h1>\n";
echo "<p><strong>Testing with:</strong> admin/admin123, supervisor01/supervisor123, sales01/sale123</p>\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $userModel = new User();
    $customerModel = new Customer();
    $workflow = new SalesDepartureWorkflow();
    
    echo "<h2>👥 User Verification</h2>\n";
    
    // Check existing users
    $testUsers = ['admin', 'supervisor01', 'sales01'];
    $foundUsers = [];
    
    foreach ($testUsers as $username) {
        $user = $userModel->findByUsername($username);
        if ($user) {
            echo "<div class='pass'>✅ Found user: {$username} (Role: {$user['Role']}, Status: {$user['Status']})</div>\n";
            $foundUsers[$username] = $user;
        } else {
            echo "<div class='fail'>❌ User not found: {$username}</div>\n";
        }
    }
    
    if (!isset($foundUsers['sales01'])) {
        echo "<div class='fail'>❌ Cannot proceed without sales01 user</div>\n";
        exit;
    }
    
    $salesUser = $foundUsers['sales01'];
    echo "<h2>📊 Sales User Analysis</h2>\n";
    echo "<div class='info'>Sales User: {$salesUser['Username']} (ID: {$salesUser['id']})</div>\n";
    echo "<div class='info'>Supervisor ID: " . ($salesUser['supervisor_id'] ?? 'Not Set') . "</div>\n";
    
    // Get departure statistics before any changes
    $stats = $workflow->getDepartureStatistics($salesUser['Username']);
    echo "<div class='info'>Current Leads: Active Tasks: {$stats['active_tasks_count']}, Follow-up: {$stats['followup_leads_count']}, New: {$stats['new_leads_count']}, Total: {$stats['total_leads']}</div>\n";
    
    if ($stats['total_leads'] == 0) {
        echo "<div class='warning'>⚠️ No leads assigned to sales01. Creating test leads...</div>\n";
        
        // Create test customers for sales01
        $testCustomers = [
            [
                'code' => 'REAL_TEST_ACTIVE',
                'name' => 'Real Test Active Task Customer',
                'tel' => '0911111111',
                'status' => 'ลูกค้าติดตาม',
                'cart_status' => 'กำลังดูแล',
                'sales' => 'sales01',
                'contact_attempts' => 2,
                'create_task' => true
            ],
            [
                'code' => 'REAL_TEST_FOLLOWUP',
                'name' => 'Real Test Follow-up Customer',
                'tel' => '0911111112',
                'status' => 'ลูกค้าติดตาม',
                'cart_status' => 'กำลังดูแล',
                'sales' => 'sales01',
                'contact_attempts' => 1,
                'create_task' => false
            ],
            [
                'code' => 'REAL_TEST_NEW',
                'name' => 'Real Test New Customer',
                'tel' => '0911111113',
                'status' => 'ลูกค้าใหม่',
                'cart_status' => 'กำลังดูแล',
                'sales' => 'sales01',
                'contact_attempts' => 0,
                'create_task' => false
            ]
        ];
        
        try {
            $pdo->beginTransaction();
            
            foreach ($testCustomers as $customer) {
                // Check if customer already exists
                $existing = $customerModel->findByCode($customer['code']);
                if (!$existing) {
                    $customerData = [
                        'CustomerCode' => $customer['code'],
                        'CustomerName' => $customer['name'],
                        'CustomerTel' => $customer['tel'],
                        'CustomerStatus' => $customer['status'],
                        'CartStatus' => $customer['cart_status'],
                        'Sales' => $customer['sales'],
                        'ContactAttempts' => $customer['contact_attempts'],
                        'CreatedBy' => 'test_real_users'
                    ];
                    
                    $customerId = $customerModel->createCustomer($customerData);
                    echo "<div class='pass'>✅ Created test customer: {$customer['code']}</div>\n";
                    
                    // Create task if needed
                    if ($customer['create_task']) {
                        $stmt = $pdo->prepare("
                            INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy) 
                            VALUES (?, ?, 'Real test active task', 'รอดำเนินการ', 'test_real_users')
                        ");
                        $stmt->execute([
                            $customer['code'],
                            date('Y-m-d H:i:s', strtotime('+1 day'))
                        ]);
                        echo "<div class='pass'>✅ Created active task for: {$customer['code']}</div>\n";
                    }
                } else {
                    echo "<div class='info'>ℹ️ Customer already exists: {$customer['code']}</div>\n";
                }
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<div class='fail'>❌ Failed to create test customers: " . $e->getMessage() . "</div>\n";
            exit;
        }
        
        // Get updated statistics
        $stats = $workflow->getDepartureStatistics($salesUser['Username']);
        echo "<div class='info'>Updated Leads: Active Tasks: {$stats['active_tasks_count']}, Follow-up: {$stats['followup_leads_count']}, New: {$stats['new_leads_count']}, Total: {$stats['total_leads']}</div>\n";
    }
    
    echo "<h2>🚀 Testing Departure Workflow</h2>\n";
    
    // Test the departure workflow
    $result = $workflow->triggerSalesDepartureWorkflow($salesUser['id']);
    
    if ($result) {
        echo "<div class='pass'>✅ Departure workflow executed successfully</div>\n";
        echo "<div class='info'>📊 Results: " . json_encode($result['totals'], JSON_UNESCAPED_UNICODE) . "</div>\n";
        
        // Show detailed results
        echo "<h3>📋 Detailed Results</h3>\n";
        foreach ($result['categories'] as $category => $categoryResult) {
            $status = $categoryResult['success'] ? '✅' : '❌';
            $count = $categoryResult['count'];
            $message = $categoryResult['message'];
            echo "<div class='info'>{$status} {$category}: {$count} leads - {$message}</div>\n";
        }
        
    } else {
        echo "<div class='fail'>❌ Departure workflow failed</div>\n";
    }
    
    echo "<h2>🔄 User Status Test via API</h2>\n";
    
    // Test toggle status API (simulation)
    echo "<div class='info'>📝 To test complete flow:</div>\n";
    echo "<div class='info'>1. Login as admin (admin/admin123)</div>\n";
    echo "<div class='info'>2. Go to User Management page</div>\n";
    echo "<div class='info'>3. Toggle sales01 status to inactive</div>\n";
    echo "<div class='info'>4. Check API response for departure_workflow data</div>\n";
    
    echo "<h2>🧹 Cleanup Option</h2>\n";
    echo "<div class='warning'>⚠️ Test customers created with prefix 'REAL_TEST_*'</div>\n";
    echo "<div class='info'>To cleanup, run: DELETE FROM customers WHERE CustomerCode LIKE 'REAL_TEST_%'</div>\n";
    echo "<div class='info'>To cleanup tasks: DELETE FROM tasks WHERE CreatedBy = 'test_real_users'</div>\n";
    
    // Show cleanup button
    if (isset($_GET['cleanup']) && $_GET['cleanup'] == 'true') {
        try {
            $pdo->beginTransaction();
            
            // Delete test tasks
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE CreatedBy = 'test_real_users'");
            $stmt->execute();
            $taskCount = $stmt->rowCount();
            
            // Delete test customers
            $stmt = $pdo->prepare("DELETE FROM customers WHERE CustomerCode LIKE 'REAL_TEST_%'");
            $stmt->execute();
            $customerCount = $stmt->rowCount();
            
            $pdo->commit();
            
            echo "<div class='pass'>✅ Cleanup completed: {$customerCount} customers, {$taskCount} tasks deleted</div>\n";
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<div class='fail'>❌ Cleanup failed: " . $e->getMessage() . "</div>\n";
        }
    } else {
        echo "<div style='margin: 20px 0;'>";
        echo "<a href='?cleanup=true' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗑️ Cleanup Test Data</a>";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='fail'>❌ Test failed: " . $e->getMessage() . "</div>\n";
}

echo "<h2>📝 Manual Testing Steps</h2>\n";
echo "<div class='info'>";
echo "<strong>Complete Manual Test:</strong><br>";
echo "1. เข้าสู่ระบบด้วย admin/admin123<br>";
echo "2. ไป Admin → User Management<br>";
echo "3. หา sales01 แล้วกด Toggle Status เป็น Inactive<br>";
echo "4. ตรวจสอบ API Response ว่ามี departure_workflow data<br>";
echo "5. ตรวจสอบใน database ว่า leads ถูกโอนย้ายถูกต้อง<br>";
echo "6. ตรวจสอบ customers table: Sales column และ CartStatus<br>";
echo "</div>";

echo "</body></html>\n";
?>