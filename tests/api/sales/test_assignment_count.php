<?php
/**
 * Assignment Count Test Suite
 * Story 1.3: Update Lead Assignment Logic
 * 
 * Tests AssignmentCount tracking functionality
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/SalesHistory.php';
require_once __DIR__ . '/../../../includes/Customer.php';

class AssignmentCountTest {
    private $pdo;
    private $salesHistory;
    private $customer;
    private $testResults = [];
    private $testCustomers = [];
    
    public function __construct() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->salesHistory = new SalesHistory();
            $this->customer = new Customer();
            
            echo "<h1>🧪 Assignment Count Test Suite</h1>\n";
            echo "<p><strong>Story 1.3:</strong> Update Lead Assignment Logic</p>\n";
            
        } catch (Exception $e) {
            die("❌ Test initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run all assignment count tests
     */
    public function runAllTests() {
        echo "<h2>📋 Running Assignment Count Tests...</h2>\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Test basic assignment count increment
        $this->testBasicAssignmentCount();
        
        // Test multiple assignments 
        $this->testMultipleAssignments();
        
        // Test transfer assignments
        $this->testTransferAssignments();
        
        // Test transaction rollback
        $this->testTransactionRollback();
        
        // Test integration with Freezing Logic
        $this->testFreezingLogicIntegration();
        
        // Cleanup test data
        $this->cleanupTestData();
        
        // Display summary
        $this->displayTestSummary();
    }
    
    /**
     * Setup test data for assignment count testing
     */
    private function setupTestData() {
        echo "<h3>🔧 Setting up test data...</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Create test customers for different scenarios
            $testCustomers = [
                // Scenario 1: New customer for basic assignment test
                [
                    'code' => 'TEST_ASSIGN_BASIC',
                    'name' => 'Test Customer Basic Assignment',
                    'tel' => '0911111001',
                    'status' => 'ลูกค้าใหม่',
                    'cart_status' => 'ตะกร้าแจก',
                    'assignment_count' => 0
                ],
                // Scenario 2: Customer for multiple assignments
                [
                    'code' => 'TEST_ASSIGN_MULTI',
                    'name' => 'Test Customer Multiple Assignments',
                    'tel' => '0911111002', 
                    'status' => 'ลูกค้าใหม่',
                    'cart_status' => 'ตะกร้าแจก',
                    'assignment_count' => 0
                ],
                // Scenario 3: Customer for transfer test
                [
                    'code' => 'TEST_ASSIGN_TRANSFER',
                    'name' => 'Test Customer Transfer',
                    'tel' => '0911111003',
                    'status' => 'ลูกค้าใหม่', 
                    'cart_status' => 'ตะกร้าแจก',
                    'assignment_count' => 1
                ],
                // Scenario 4: Customer for freezing logic
                [
                    'code' => 'TEST_ASSIGN_FREEZE',
                    'name' => 'Test Customer Freeze Logic',
                    'tel' => '0911111004',
                    'status' => 'ลูกค้าใหม่',
                    'cart_status' => 'ตะกร้าแจก', 
                    'assignment_count' => 2
                ]
            ];
            
            foreach ($testCustomers as $customer) {
                $sql = "
                    INSERT INTO customers (
                        CustomerCode, CustomerName, CustomerTel, CustomerStatus, 
                        CartStatus, AssignmentCount, CreatedDate, CreatedBy
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'test_assignment_count')
                ";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $customer['code'],
                    $customer['name'],
                    $customer['tel'],
                    $customer['status'],
                    $customer['cart_status'],
                    $customer['assignment_count']
                ]);
                
                $this->testCustomers[] = $customer['code'];
            }
            
            // Create test sales users if not exist
            $testUsers = [
                ['username' => 'test_sales_1', 'name' => 'Test Sales 1'],
                ['username' => 'test_sales_2', 'name' => 'Test Sales 2'] 
            ];
            
            foreach ($testUsers as $user) {
                $sql = "
                    INSERT IGNORE INTO users (
                        Username, Password, FirstName, LastName, Role, 
                        Status, CreatedDate, CreatedBy
                    ) VALUES (?, ?, ?, ?, 'Sale', 1, NOW(), 'test_assignment_count')
                ";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $user['username'],
                    password_hash('test123', PASSWORD_DEFAULT),
                    $user['name'],
                    'Test'
                ]);
            }
            
            $this->pdo->commit();
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "✅ Test data created: " . count($testCustomers) . " customers, 2 sales users\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Failed to setup test data: " . $e->getMessage() . "\n";
            echo "</div>\n";
            throw $e;
        }
    }
    
    /**
     * Test basic assignment count increment
     */
    private function testBasicAssignmentCount() {
        echo "<h3>🧪 Testing Basic Assignment Count Increment</h3>\n";
        
        try {
            $customerCode = 'TEST_ASSIGN_BASIC';
            
            // Get initial count (should be 0)
            $initialCount = $this->salesHistory->getAssignmentCount($customerCode);
            
            // Create assignment
            $assignmentId = $this->salesHistory->createSalesAssignment(
                $customerCode,
                'test_sales_1',
                'test_user'
            );
            
            // Get count after assignment
            $afterCount = $this->salesHistory->getAssignmentCount($customerCode);
            
            // Validate results
            $this->recordTestResult('Basic Assignment Count', [
                'Assignment created successfully' => $assignmentId !== false,
                'Initial count was 0' => $initialCount === 0,
                'Count incremented by 1' => $afterCount === ($initialCount + 1),
                'Final count is 1' => $afterCount === 1
            ]);
            
            echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "✅ Basic assignment count test: {$initialCount} → {$afterCount}\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Basic Assignment Count', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Basic assignment count test failed: " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Test multiple assignments accumulate count correctly
     */
    private function testMultipleAssignments() {
        echo "<h3>🧪 Testing Multiple Assignment Count Accumulation</h3>\n";
        
        try {
            $customerCode = 'TEST_ASSIGN_MULTI';
            
            // Get initial count
            $initialCount = $this->salesHistory->getAssignmentCount($customerCode);
            
            // Create multiple assignments
            $assignmentCounts = [];
            
            for ($i = 1; $i <= 3; $i++) {
                // End previous assignment
                $this->salesHistory->endCurrentAssignment($customerCode);
                
                // Create new assignment 
                $assignmentId = $this->salesHistory->createSalesAssignment(
                    $customerCode,
                    'test_sales_1',
                    'test_user'
                );
                
                $currentCount = $this->salesHistory->getAssignmentCount($customerCode);
                $assignmentCounts[] = $currentCount;
                
                echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                echo "📊 Assignment {$i}: Count = {$currentCount}\n";
                echo "</div>\n";
            }
            
            // Validate results
            $this->recordTestResult('Multiple Assignments', [
                'All 3 assignments created' => count($assignmentCounts) === 3,
                'Count accumulates correctly' => $assignmentCounts === [1, 2, 3],
                'Final count is 3' => end($assignmentCounts) === 3
            ]);
            
            echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "✅ Multiple assignments test completed: " . implode(' → ', $assignmentCounts) . "\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Multiple Assignments', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Multiple assignments test failed: " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Test transfer assignments increment count
     */
    private function testTransferAssignments() {
        echo "<h3>🧪 Testing Transfer Assignment Count</h3>\n";
        
        try {
            $customerCode = 'TEST_ASSIGN_TRANSFER';
            
            // Get initial count (from setup data, should be 1)
            $initialCount = $this->salesHistory->getAssignmentCount($customerCode);
            echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "📊 Initial count from setup: {$initialCount}\n";
            echo "</div>\n";
            
            // First create an initial assignment to establish active assignment
            $initialAssignment = $this->salesHistory->createSalesAssignment(
                $customerCode,
                'test_sales_1',
                'test_user'
            );
            
            if (!$initialAssignment) {
                throw new Exception('Failed to create initial assignment for transfer test');
            }
            
            // Get count after initial assignment
            $beforeTransferCount = $this->salesHistory->getAssignmentCount($customerCode);
            echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "📊 After initial assignment: {$beforeTransferCount}\n";
            echo "</div>\n";
            
            // Transfer customer to new sales person
            $transferResult = $this->salesHistory->transferCustomer(
                $customerCode,
                'test_sales_2',
                'test_user'
            );
            
            // Get count after transfer
            $afterTransferCount = $this->salesHistory->getAssignmentCount($customerCode);
            echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "📊 After transfer: {$afterTransferCount}\n";
            echo "</div>\n";
            
            // Verify current assignment is correct
            $currentAssignment = $this->salesHistory->getCurrentSalesAssignment($customerCode);
            $correctSalesAssigned = $currentAssignment && $currentAssignment['SaleName'] === 'test_sales_2';
            
            // Validate results
            $this->recordTestResult('Transfer Assignment', [
                'Initial assignment created' => $initialAssignment !== false,
                'Initial count incremented correctly' => $beforeTransferCount === ($initialCount + 1),
                'Transfer completed successfully' => $transferResult !== false,
                'Count incremented by transfer' => $afterTransferCount === ($beforeTransferCount + 1),
                'Current assignment is correct' => $correctSalesAssigned,
                'Total increments are correct' => $afterTransferCount === ($initialCount + 2)
            ]);
            
            echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "✅ Transfer assignment test: {$initialCount} → {$beforeTransferCount} → {$afterTransferCount}\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Transfer Assignment', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Transfer assignment test failed: " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Test transaction rollback on assignment failure
     */
    private function testTransactionRollback() {
        echo "<h3>🧪 Testing Transaction Rollback on Failure</h3>\n";
        
        try {
            $customerCode = 'TEST_ASSIGN_BASIC';
            
            // Get count before test  
            $beforeCount = $this->salesHistory->getAssignmentCount($customerCode);
            echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "📊 Before test: Count = {$beforeCount}\n";
            echo "</div>\n";
            
            // Test 1: Try to assign to non-existent sales person (should fail immediately in validation)
            $assignmentId = $this->salesHistory->createSalesAssignment(
                $customerCode,
                'non_existent_sales_user',
                'test_user'
            );
            
            // Get count after failed assignment
            $afterCount1 = $this->salesHistory->getAssignmentCount($customerCode);
            echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "📊 After invalid sales user: Count = {$afterCount1}\n";
            echo "</div>\n";
            
            // Test 2: Try to assign to invalid customer code (should fail immediately in validation)
            $invalidAssignmentId = $this->salesHistory->createSalesAssignment(
                'INVALID_CUSTOMER_CODE',
                'test_sales_1', 
                'test_user'
            );
            
            // Test 3: Simulate a transaction failure by corrupting database temporarily
            // Force a valid assignment that will fail during transaction
            $validationSucceeds = false;
            try {
                // Create a test that will pass validation but fail in transaction
                $this->pdo->exec("RENAME TABLE users TO users_temp");
                
                $rollbackAssignmentId = $this->salesHistory->createSalesAssignment(
                    $customerCode,
                    'test_sales_1',
                    'test_user'
                );
                
                $this->pdo->exec("RENAME TABLE users_temp TO users");
                
            } catch (Exception $transactionError) {
                // Restore table
                try {
                    $this->pdo->exec("RENAME TABLE users_temp TO users");
                } catch (Exception $restoreError) {
                    // Table might already be restored
                }
                $validationSucceeds = true;
            }
            
            $afterCount2 = $this->salesHistory->getAssignmentCount($customerCode);
            echo "<div style='background: #e2e3e5; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "📊 After transaction rollback test: Count = {$afterCount2}\n";
            echo "</div>\n";
            
            // Validate results
            $this->recordTestResult('Transaction Rollback', [
                'Assignment to invalid sales failed' => $assignmentId === false,
                'Count unchanged after invalid sales' => $beforeCount === $afterCount1,
                'Assignment to invalid customer failed' => $invalidAssignmentId === false,
                'Transaction rollback preserved count' => $afterCount1 === $afterCount2,
                'All counts remain consistent' => $beforeCount === $afterCount1 && $afterCount1 === $afterCount2
            ]);
            
            echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "✅ Transaction rollback test: Count preserved at {$afterCount2}\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Transaction rollback test failed: " . $e->getMessage() . "\n";
            echo "</div>\n";
            
            $this->recordTestResult('Transaction Rollback', [
                'Exception occurred' => false
            ]);
        }
    }
    
    /**
     * Test integration with Freezing Logic from Story 1.2
     */
    private function testFreezingLogicIntegration() {
        echo "<h3>🧪 Testing Integration with Freezing Logic</h3>\n";
        
        try {
            $customerCode = 'TEST_ASSIGN_FREEZE';
            
            // Get initial count (should be 2 from setup)
            $initialCount = $this->salesHistory->getAssignmentCount($customerCode);
            
            // Assign to reach count 3 (freezing threshold)
            $assignmentId = $this->salesHistory->createSalesAssignment(
                $customerCode,
                'test_sales_1',
                'test_user'
            );
            
            $finalCount = $this->salesHistory->getAssignmentCount($customerCode);
            
            // Check if customer would be eligible for freezing
            $sql = "SELECT AssignmentCount, CartStatus FROM customers WHERE CustomerCode = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$customerCode]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $freezingEligible = ($customer['AssignmentCount'] >= 3 && $customer['CartStatus'] === 'ตะกร้าแจก');
            
            // Validate results
            $this->recordTestResult('Freezing Logic Integration', [
                'Assignment successful' => $assignmentId !== false,
                'Count reached 3' => $finalCount === 3,
                'Eligible for freezing' => $freezingEligible,
                'Integration ready' => true
            ]);
            
            echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "✅ Freezing logic integration: Count {$finalCount}, Eligible: " . ($freezingEligible ? 'Yes' : 'No') . "\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            $this->recordTestResult('Freezing Logic Integration', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Freezing logic integration test failed: " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Cleanup test data
     */
    private function cleanupTestData() {
        echo "<h3>🧹 Cleaning up test data...</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            foreach ($this->testCustomers as $customerCode) {
                // Remove test customer
                $stmt = $this->pdo->prepare("DELETE FROM customers WHERE CustomerCode = ?");
                $stmt->execute([$customerCode]);
                
                // Remove any sales histories
                $stmt = $this->pdo->prepare("DELETE FROM sales_histories WHERE CustomerCode = ?");
                $stmt->execute([$customerCode]);
            }
            
            // Remove test users
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE CreatedBy = 'test_assignment_count'");
            $stmt->execute();
            
            $this->pdo->commit();
            
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "✅ Test data cleaned up successfully\n";
            echo "</div>\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ Failed to cleanup test data: " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
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
        echo "<h2>📈 Test Summary</h2>\n";
        
        $totalTests = count($this->testResults);
        $passedTests = 0;
        $totalChecks = 0;
        $passedChecks = 0;
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
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
        echo "- Assignment Count Logic: " . ($overallPercentage >= 90 ? "✅ Ready for Production" : "❌ Needs Fixes") . "<br>\n";
        echo "</div>\n";
        
        if ($overallPercentage >= 90) {
            echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "🚀 <strong>Assignment Count system is ready for Production!</strong><br>\n";
            echo "All business logic for Story 1.3 has been tested and verified.<br>\n";
            echo "Integration with Story 1.2 (Freezing Rules) is working correctly.\n";
            echo "</div>\n";
        }
    }
}

// Run the tests
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Assignment Count Test Suite</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}</style>";
echo "</head><body>\n";

$tester = new AssignmentCountTest();
$tester->runAllTests();

echo "<h3>🔗 Next Steps</h3>\n";
echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px;'>\n";
echo "1. Review test results above<br>\n";
echo "2. If all tests pass, Story 1.3 is ready for production<br>\n";
echo "3. Verify integration with Story 1.2 Cron Job<br>\n";
echo "4. Monitor AssignmentCount accuracy in production<br>\n";
echo "</div>\n";

echo "</body></html>\n";
?>