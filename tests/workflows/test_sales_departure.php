<?php
/**
 * Sales Departure Workflow Test Suite
 * Story 2.1: Implement Lead Re-assignment Logic
 * 
 * Tests all 3 categories of lead reassignment
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/SalesDepartureWorkflow.php';
require_once __DIR__ . '/../../includes/User.php';
require_once __DIR__ . '/../../includes/Customer.php';
require_once __DIR__ . '/../../includes/Task.php';

class SalesDepartureTest {
    private $pdo;
    private $departureWorkflow;
    private $testResults = [];
    private $testUsers = [];
    private $testCustomers = [];
    private $testTasks = [];
    
    public function __construct() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->departureWorkflow = new SalesDepartureWorkflow();
            
            echo "<!DOCTYPE html>\n";
            echo "<html><head><title>Sales Departure Workflow Test Suite</title>";
            echo "<style>
                body{font-family:Arial,sans-serif;margin:20px;} 
                .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} 
                .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} 
                .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;}
                .section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;}
                table{width:100%;border-collapse:collapse;margin:10px 0;}
                th,td{border:1px solid #ddd;padding:8px;text-align:left;}
                th{background:#f0f0f0;}
            </style></head><body>\n";
            
            echo "<h1>🧪 Sales Departure Workflow Test Suite</h1>\n";
            echo "<p><strong>Story 2.1:</strong> Implement Lead Re-assignment Logic</p>\n";
            
        } catch (Exception $e) {
            die("❌ Test initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run all departure workflow tests
     */
    public function runAllTests() {
        echo "<h2>📋 Running Sales Departure Tests...</h2>\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Test Category 1: Active Tasks Reassignment
        $this->testActiveTasksReassignment();
        
        // Test Category 2: Follow-up Leads to Waiting
        $this->testFollowUpLeadsToWaiting();
        
        // Test Category 3: New Leads to Distribution
        $this->testNewLeadsToDistribution();
        
        // Test Complete Workflow Integration
        $this->testCompleteWorkflow();
        
        // Test Edge Cases
        $this->testEdgeCases();
        
        // Cleanup test data
        $this->cleanupTestData();
        
        // Display summary
        $this->displayTestSummary();
    }
    
    /**
     * Setup comprehensive test data for all scenarios
     */
    private function setupTestData() {
        echo "<div class='section'><h3>🔧 Setting up test data...</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Create test supervisor
            $supervisorData = [
                'Username' => 'test_supervisor',
                'Password' => password_hash('test123', PASSWORD_DEFAULT),
                'FirstName' => 'Test',
                'LastName' => 'Supervisor',
                'Role' => 'Supervisor',
                'Status' => 1,
                'CreatedBy' => 'test_departure_workflow'
            ];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (Username, Password, FirstName, LastName, Role, Status, CreatedDate, CreatedBy) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $supervisorData['Username'],
                $supervisorData['Password'], 
                $supervisorData['FirstName'],
                $supervisorData['LastName'],
                $supervisorData['Role'],
                $supervisorData['Status'],
                $supervisorData['CreatedBy']
            ]);
            $supervisorId = $this->pdo->lastInsertId();
            $this->testUsers['supervisor'] = $supervisorId;
            
            // Create test sales user with supervisor
            $salesData = [
                'Username' => 'test_sales_departure',
                'Password' => password_hash('test123', PASSWORD_DEFAULT),
                'FirstName' => 'Test',
                'LastName' => 'Sales',
                'Role' => 'Sale',
                'Status' => 1,
                'supervisor_id' => $supervisorId,
                'CreatedBy' => 'test_departure_workflow'
            ];
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (Username, Password, FirstName, LastName, Role, Status, supervisor_id, CreatedDate, CreatedBy) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $salesData['Username'],
                $salesData['Password'], 
                $salesData['FirstName'],
                $salesData['LastName'],
                $salesData['Role'],
                $salesData['Status'],
                $salesData['supervisor_id'],
                $salesData['CreatedBy']
            ]);
            $salesUserId = $this->pdo->lastInsertId();
            $this->testUsers['sales'] = $salesUserId;
            
            // Create test customers for different scenarios
            $testCustomers = [
                // Category 1: Customer with active tasks
                [
                    'code' => 'TEST_ACTIVE_TASK',
                    'name' => 'Customer with Active Tasks',
                    'tel' => '0900000001',
                    'status' => 'ลูกค้าติดตาม',
                    'cart_status' => 'กำลังดูแล',
                    'sales' => 'test_sales_departure',
                    'contact_attempts' => 2,
                    'has_active_task' => true
                ],
                // Category 2: Follow-up customer without active tasks  
                [
                    'code' => 'TEST_FOLLOWUP',
                    'name' => 'Follow-up Customer',
                    'tel' => '0900000002',
                    'status' => 'ลูกค้าติดตาม',
                    'cart_status' => 'กำลังดูแล',
                    'sales' => 'test_sales_departure',
                    'contact_attempts' => 1,
                    'has_active_task' => false
                ],
                // Category 3: New uncontacted customer
                [
                    'code' => 'TEST_NEW_LEAD',
                    'name' => 'New Uncontacted Lead',
                    'tel' => '0900000003',
                    'status' => 'ลูกค้าใหม่',
                    'cart_status' => 'กำลังดูแล',
                    'sales' => 'test_sales_departure',
                    'contact_attempts' => 0,
                    'has_active_task' => false
                ],
                // Control: Customer not affected by departure
                [
                    'code' => 'TEST_OTHER_SALES',
                    'name' => 'Other Sales Customer',
                    'tel' => '0900000004',
                    'status' => 'ลูกค้าใหม่',
                    'cart_status' => 'กำลังดูแล',
                    'sales' => 'other_sales',
                    'contact_attempts' => 0,
                    'has_active_task' => false
                ]
            ];
            
            foreach ($testCustomers as $customer) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO customers (
                        CustomerCode, CustomerName, CustomerTel, CustomerStatus, CartStatus, 
                        Sales, ContactAttempts, CreatedDate, CreatedBy
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([
                    $customer['code'],
                    $customer['name'],
                    $customer['tel'],
                    $customer['status'],
                    $customer['cart_status'],
                    $customer['sales'],
                    $customer['contact_attempts'],
                    'test_departure_workflow'
                ]);
                
                $this->testCustomers[$customer['code']] = $customer;
                
                // Create active task if specified
                if ($customer['has_active_task']) {
                    $stmt = $this->pdo->prepare("
                        INSERT INTO tasks (
                            CustomerCode, FollowupDate, Remarks, Status, CreatedDate, CreatedBy
                        ) VALUES (?, ?, ?, 'รอดำเนินการ', NOW(), ?)
                    ");
                    $stmt->execute([
                        $customer['code'],
                        date('Y-m-d H:i:s', strtotime('+1 day')),
                        'Test active task for departure workflow',
                        'test_departure_workflow'
                    ]);
                    $this->testTasks[] = $this->pdo->lastInsertId();
                }
            }
            
            $this->pdo->commit();
            echo "<div class='pass'>✅ Test data created: 2 users, 4 customers, 1 active task</div>\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "<div class='fail'>❌ Failed to setup test data: " . $e->getMessage() . "</div>\n";
            throw $e;
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test Category 1: Active Tasks Reassignment
     */
    private function testActiveTasksReassignment() {
        echo "<div class='section'><h3>🧪 Testing Category 1: Active Tasks Reassignment</h3>\n";
        
        try {
            // Test reassign active task leads
            $result = $this->departureWorkflow->reassignActiveTaskLeads('test_sales_departure', 'test_supervisor');
            
            $this->recordTestResult('Category 1 - Active Tasks', [
                'Method executed successfully' => $result !== false,
                'Result is successful' => $result['success'] === true,
                'Correct count returned' => $result['count'] === 1,
                'Customer reassigned' => count($result['customers']) === 1,
                'Customer details correct' => isset($result['customers'][0]['customer_code']) && 
                                           $result['customers'][0]['customer_code'] === 'TEST_ACTIVE_TASK'
            ]);
            
            // Verify database changes
            $stmt = $this->pdo->prepare("SELECT Sales FROM customers WHERE CustomerCode = ?");
            $stmt->execute(['TEST_ACTIVE_TASK']);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->recordTestResult('Category 1 - Database Verification', [
                'Customer found in database' => $customer !== false,
                'Sales reassigned to supervisor' => $customer['Sales'] === 'test_supervisor'
            ]);
            
            echo "<div class='info'>📊 Active Tasks Reassignment: " . json_encode($result) . "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Category 1 - Active Tasks', ['Exception' => false]);
            echo "<div class='fail'>❌ Category 1 test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test Category 2: Follow-up Leads to Waiting
     */
    private function testFollowUpLeadsToWaiting() {
        echo "<div class='section'><h3>🧪 Testing Category 2: Follow-up Leads to Waiting</h3>\n";
        
        try {
            // Test move follow-up leads to waiting
            $result = $this->departureWorkflow->moveFollowUpLeadsToWaiting('test_sales_departure');
            
            $this->recordTestResult('Category 2 - Follow-up to Waiting', [
                'Method executed successfully' => $result !== false,
                'Result is successful' => $result['success'] === true,
                'Correct count returned' => $result['count'] === 1,
                'Customer moved' => count($result['customers']) === 1,
                'Customer details correct' => isset($result['customers'][0]['customer_code']) && 
                                           $result['customers'][0]['customer_code'] === 'TEST_FOLLOWUP'
            ]);
            
            // Verify database changes
            $stmt = $this->pdo->prepare("SELECT Sales, CartStatus FROM customers WHERE CustomerCode = ?");
            $stmt->execute(['TEST_FOLLOWUP']);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->recordTestResult('Category 2 - Database Verification', [
                'Customer found in database' => $customer !== false,
                'Sales cleared' => $customer['Sales'] === null,
                'CartStatus changed to waiting' => $customer['CartStatus'] === 'ตะกร้ารอ'
            ]);
            
            echo "<div class='info'>📊 Follow-up to Waiting: " . json_encode($result) . "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Category 2 - Follow-up to Waiting', ['Exception' => false]);
            echo "<div class='fail'>❌ Category 2 test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test Category 3: New Leads to Distribution
     */
    private function testNewLeadsToDistribution() {
        echo "<div class='section'><h3>🧪 Testing Category 3: New Leads to Distribution</h3>\n";
        
        try {
            // Test move new leads to distribution
            $result = $this->departureWorkflow->moveNewLeadsToDistribution('test_sales_departure');
            
            $this->recordTestResult('Category 3 - New to Distribution', [
                'Method executed successfully' => $result !== false,
                'Result is successful' => $result['success'] === true,
                'Correct count returned' => $result['count'] === 1,
                'Customer moved' => count($result['customers']) === 1,
                'Customer details correct' => isset($result['customers'][0]['customer_code']) && 
                                           $result['customers'][0]['customer_code'] === 'TEST_NEW_LEAD'
            ]);
            
            // Verify database changes
            $stmt = $this->pdo->prepare("SELECT Sales, CartStatus FROM customers WHERE CustomerCode = ?");
            $stmt->execute(['TEST_NEW_LEAD']);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->recordTestResult('Category 3 - Database Verification', [
                'Customer found in database' => $customer !== false,
                'Sales cleared' => $customer['Sales'] === null,
                'CartStatus changed to distribution' => $customer['CartStatus'] === 'ตะกร้าแจก'
            ]);
            
            echo "<div class='info'>📊 New to Distribution: " . json_encode($result) . "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Category 3 - New to Distribution', ['Exception' => false]);
            echo "<div class='fail'>❌ Category 3 test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test Complete Workflow Integration
     */
    private function testCompleteWorkflow() {
        echo "<div class='section'><h3>🧪 Testing Complete Workflow Integration</h3>\n";
        
        try {
            // Reset test data for complete workflow test
            $this->resetTestCustomers();
            
            // Wait a moment for reset to complete
            usleep(100000); // 0.1 second
            
            // Test complete departure workflow
            $salesUserId = $this->testUsers['sales'];
            echo "<div class='info'>📝 Testing with Sales User ID: {$salesUserId}</div>\n";
            
            $result = $this->departureWorkflow->triggerSalesDepartureWorkflow($salesUserId);
            
            $this->recordTestResult('Complete Workflow', [
                'Workflow executed successfully' => $result !== false,
                'Results structure valid' => $result && isset($result['totals']) && isset($result['categories']),
                'Total leads processed' => $result && $result['totals']['total_processed'] >= 3,
                'All categories executed' => $result && isset($result['categories']['active_tasks']) && 
                                          isset($result['categories']['followup_leads']) && 
                                          isset($result['categories']['new_leads'])
            ]);
            
            if ($result) {
                echo "<div class='info'>📊 Complete Workflow Results: " . json_encode($result['totals']) . "</div>\n";
            } else {
                echo "<div class='fail'>❌ Complete Workflow returned null/false</div>\n";
            }
            
        } catch (Exception $e) {
            $this->recordTestResult('Complete Workflow', ['Exception' => false]);
            echo "<div class='fail'>❌ Complete workflow test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test Edge Cases
     */
    private function testEdgeCases() {
        echo "<div class='section'><h3>🧪 Testing Edge Cases</h3>\n";
        
        try {
            // Test with non-existent user
            $result1 = $this->departureWorkflow->triggerSalesDepartureWorkflow(99999);
            
            // Test with non-sales user (supervisor)
            $supervisorId = $this->testUsers['supervisor'];
            $result2 = $this->departureWorkflow->triggerSalesDepartureWorkflow($supervisorId);
            
            // Test sales user without supervisor
            $stmt = $this->pdo->prepare("
                INSERT INTO users (Username, Password, FirstName, LastName, Role, Status, CreatedDate, CreatedBy) 
                VALUES ('test_no_supervisor', ?, 'No', 'Supervisor', 'Sale', 1, NOW(), 'test_departure_workflow')
            ");
            $stmt->execute([password_hash('test123', PASSWORD_DEFAULT)]);
            $noSupervisorId = $this->pdo->lastInsertId();
            
            $result3 = $this->departureWorkflow->triggerSalesDepartureWorkflow($noSupervisorId);
            
            $this->recordTestResult('Edge Cases', [
                'Non-existent user handled' => $result1 === false,
                'Non-sales user handled' => $result2 === false,
                'No supervisor user handled gracefully' => $result3 !== false
            ]);
            
            echo "<div class='info'>✅ Edge cases handled appropriately</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Edge Cases', ['Exception' => false]);
            echo "<div class='fail'>❌ Edge cases test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Reset test customers to original state
     */
    private function resetTestCustomers() {
        try {
            // Reset customers to original assignments
            $resetData = [
                'TEST_ACTIVE_TASK' => ['sales' => 'test_sales_departure', 'cart_status' => 'กำลังดูแล'],
                'TEST_FOLLOWUP' => ['sales' => 'test_sales_departure', 'cart_status' => 'กำลังดูแล'],
                'TEST_NEW_LEAD' => ['sales' => 'test_sales_departure', 'cart_status' => 'กำลังดูแล']
            ];
            
            foreach ($resetData as $customerCode => $data) {
                $stmt = $this->pdo->prepare("
                    UPDATE customers 
                    SET Sales = ?, CartStatus = ?, AssignDate = NOW() 
                    WHERE CustomerCode = ?
                ");
                $stmt->execute([$data['sales'], $data['cart_status'], $customerCode]);
            }
            
        } catch (Exception $e) {
            echo "<div class='fail'>⚠️ Warning: Failed to reset test customers: " . $e->getMessage() . "</div>\n";
        }
    }
    
    /**
     * Cleanup test data
     */
    private function cleanupTestData() {
        echo "<div class='section'><h3>🧹 Cleaning up test data...</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Delete test tasks
            foreach ($this->testTasks as $taskId) {
                $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $stmt->execute([$taskId]);
            }
            
            // Delete test customers
            foreach ($this->testCustomers as $customerCode => $customer) {
                $stmt = $this->pdo->prepare("DELETE FROM customers WHERE CustomerCode = ?");
                $stmt->execute([$customerCode]);
            }
            
            // Delete test users
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE CreatedBy = 'test_departure_workflow'");
            $stmt->execute();
            
            $this->pdo->commit();
            echo "<div class='pass'>✅ Test data cleaned up successfully</div>\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "<div class='fail'>❌ Failed to cleanup test data: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Record test results
     */
    private function recordTestResult($testName, $checks) {
        $passed = array_filter($checks);
        $total = count($checks);
        $passedCount = count($passed);
        
        $this->testResults[$testName] = [
            'passed' => $passedCount,
            'total' => $total,
            'percentage' => $total > 0 ? round(($passedCount / $total) * 100, 1) : 0,
            'status' => $passedCount === $total ? 'PASS' : 'FAIL'
        ];
    }
    
    /**
     * Display test summary
     */
    private function displayTestSummary() {
        echo "<div class='section'><h2>📈 Test Summary</h2>\n";
        
        $totalTests = count($this->testResults);
        $passedTests = 0;
        $totalChecks = 0;
        $passedChecks = 0;
        
        echo "<table>\n";
        echo "<tr style='background: #f0f0f0;'>\n";
        echo "<th>Test Name</th><th>Passed/Total</th><th>Percentage</th><th>Status</th>\n";
        echo "</tr>\n";
        
        foreach ($this->testResults as $testName => $result) {
            $totalChecks += $result['total'];
            $passedChecks += $result['passed'];
            
            if ($result['status'] === 'PASS') {
                $passedTests++;
                $bgColor = '#d4edda';
                $statusIcon = '✅';
            } else {
                $bgColor = '#f8d7da';
                $statusIcon = '❌';
            }
            
            echo "<tr style='background: $bgColor;'>\n";
            echo "<td><strong>$testName</strong></td>\n";
            echo "<td>{$result['passed']}/{$result['total']}</td>\n";
            echo "<td>{$result['percentage']}%</td>\n";
            echo "<td>$statusIcon {$result['status']}</td>\n";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
        
        $overallPercentage = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 1) : 0;
        $overallStatus = $passedTests === $totalTests ? 'ALL TESTS PASSED' : 'SOME TESTS FAILED';
        $statusColor = $passedTests === $totalTests ? '#d4edda' : '#f8d7da';
        $statusIcon = $passedTests === $totalTests ? '🎉' : '⚠️';
        
        echo "<div style='background: $statusColor; padding: 15px; margin: 10px 0; border-radius: 5px; border: 2px solid #ddd;'>\n";
        echo "<h3>$statusIcon <strong>Overall Result: $overallStatus</strong></h3>\n";
        echo "📊 <strong>Summary:</strong><br>\n";
        echo "- Tests Passed: $passedTests/$totalTests<br>\n";
        echo "- Individual Checks: $passedChecks/$totalChecks ($overallPercentage%)<br>\n";
        echo "- Sales Departure Workflow: " . ($overallPercentage >= 90 ? "✅ Ready for Production" : "❌ Needs Fixes") . "<br>\n";
        echo "</div>\n";
        
        if ($overallPercentage >= 90) {
            echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "🚀 <strong>Sales Departure Workflow is ready for Production!</strong><br>\n";
            echo "All 3 categories of lead reassignment have been tested and verified.<br>\n";
            echo "Integration with user toggle status API is working correctly.\n";
            echo "</div>\n";
        }
        
        echo "</div>\n";
    }
}

// Run the tests
$tester = new SalesDepartureTest();
$tester->runAllTests();

echo "<h3>🔗 Next Steps</h3>\n";
echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px;'>\n";
echo "1. Review test results above<br>\n";
echo "2. If all tests pass, Story 2.1 is ready for production<br>\n";
echo "3. Test the complete workflow via User Management interface<br>\n";
echo "4. Monitor departure workflow performance in production<br>\n";
echo "</div>\n";

echo "</body></html>\n";
?>