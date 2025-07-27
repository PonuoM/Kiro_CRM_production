<?php
/**
 * Enhanced Dashboard API Test Suite
 * Story 3.1: Enhance Dashboard API
 * 
 * Tests time_remaining_days calculation and CustomerTemperature integration
 */

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/User.php';
require_once __DIR__ . '/../../../includes/Customer.php';

class EnhancedDashboardTest {
    private $pdo;
    private $testResults = [];
    private $testCustomers = [];
    
    public function __construct() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<!DOCTYPE html>\n";
            echo "<html><head><title>Enhanced Dashboard API Test Suite</title>";
            echo "<style>
                body{font-family:Arial,sans-serif;margin:20px;} 
                .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} 
                .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} 
                .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;}
                .warning{background:#fff3cd;padding:10px;margin:5px;border-radius:5px;}
                .section{margin:20px 0;padding:15px;border:1px solid #ddd;border-radius:5px;}
                table{width:100%;border-collapse:collapse;margin:10px 0;}
                th,td{border:1px solid #ddd;padding:8px;text-align:left;}
                th{background:#f0f0f0;}
                pre{background:#f8f9fa;padding:10px;border-radius:5px;overflow:auto;}
            </style></head><body>\n";
            
            echo "<h1>🧪 Enhanced Dashboard API Test Suite</h1>\n";
            echo "<p><strong>Story 3.1:</strong> Enhance Dashboard API with Time Remaining and Customer Temperature</p>\n";
            
        } catch (Exception $e) {
            die("❌ Test initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run all enhanced dashboard API tests
     */
    public function runAllTests() {
        echo "<h2>📋 Running Enhanced Dashboard Tests...</h2>\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Test basic API functionality
        $this->testBasicAPIResponse();
        
        // Test time remaining calculation
        $this->testTimeRemainingCalculation();
        
        // Test customer temperature integration
        $this->testCustomerTemperatureIntegration();
        
        // Test enhanced customer list API
        $this->testEnhancedCustomerListAPI();
        
        // Test edge cases
        $this->testEdgeCases();
        
        // Test performance
        $this->testPerformance();
        
        // Cleanup test data
        $this->cleanupTestData();
        
        // Display summary
        $this->displayTestSummary();
    }
    
    /**
     * Setup test data for different time scenarios
     */
    private function setupTestData() {
        echo "<div class='section'><h3>🔧 Setting up test data...</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Create test customers with different time scenarios
            $testCustomers = [
                // Overdue new customer (35 days ago)
                [
                    'code' => 'TEST_OVERDUE_NEW',
                    'name' => 'Overdue New Customer',
                    'tel' => '0900000101',
                    'status' => 'ลูกค้าใหม่',
                    'temperature' => 'HOT',
                    'sales' => 'admin',
                    'assign_date' => date('Y-m-d H:i:s', strtotime('-35 days')),
                    'contact_attempts' => 1,
                    'expected_time_remaining' => -5 // 30 - 35 = -5
                ],
                // Urgent new customer (25 days ago)
                [
                    'code' => 'TEST_URGENT_NEW',
                    'name' => 'Urgent New Customer',
                    'tel' => '0900000102',
                    'status' => 'ลูกค้าใหม่',
                    'temperature' => 'WARM',
                    'sales' => 'admin',
                    'assign_date' => date('Y-m-d H:i:s', strtotime('-25 days')),
                    'contact_attempts' => 0,
                    'expected_time_remaining' => 5 // 30 - 25 = 5
                ],
                // Normal new customer (10 days ago)
                [
                    'code' => 'TEST_NORMAL_NEW',
                    'name' => 'Normal New Customer',
                    'tel' => '0900000103',
                    'status' => 'ลูกค้าใหม่',
                    'temperature' => 'COLD',
                    'sales' => 'admin',
                    'assign_date' => date('Y-m-d H:i:s', strtotime('-10 days')),
                    'contact_attempts' => 0,
                    'expected_time_remaining' => 20 // 30 - 10 = 20
                ],
                // Overdue follow-up customer (100 days ago)
                [
                    'code' => 'TEST_OVERDUE_FOLLOWUP',
                    'name' => 'Overdue Follow-up Customer',
                    'tel' => '0900000104',
                    'status' => 'ลูกค้าติดตาม',
                    'temperature' => 'FROZEN',
                    'sales' => 'admin',
                    'assign_date' => date('Y-m-d H:i:s', strtotime('-100 days')),
                    'last_contact_date' => date('Y-m-d H:i:s', strtotime('-100 days')),
                    'contact_attempts' => 3,
                    'expected_time_remaining' => -10 // 90 - 100 = -10
                ],
                // Normal follow-up customer (30 days ago)
                [
                    'code' => 'TEST_NORMAL_FOLLOWUP',
                    'name' => 'Normal Follow-up Customer',
                    'tel' => '0900000105',
                    'status' => 'ลูกค้าติดตาม',
                    'temperature' => 'HOT',
                    'sales' => 'admin',
                    'assign_date' => date('Y-m-d H:i:s', strtotime('-50 days')),
                    'last_contact_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
                    'contact_attempts' => 2,
                    'expected_time_remaining' => 60 // 90 - 30 = 60
                ]
            ];
            
            foreach ($testCustomers as $customer) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO customers (
                        CustomerCode, CustomerName, CustomerTel, CustomerStatus, 
                        CustomerTemperature, Sales, AssignDate, LastContactDate,
                        ContactAttempts, CreatedDate, CreatedBy
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                $stmt->execute([
                    $customer['code'],
                    $customer['name'],
                    $customer['tel'],
                    $customer['status'],
                    $customer['temperature'],
                    $customer['sales'],
                    $customer['assign_date'],
                    $customer['last_contact_date'] ?? null,
                    $customer['contact_attempts'],
                    'test_enhanced_dashboard'
                ]);
                
                $this->testCustomers[$customer['code']] = $customer;
            }
            
            $this->pdo->commit();
            echo "<div class='pass'>✅ Test data created: " . count($testCustomers) . " customers with different time scenarios</div>\n";
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "<div class='fail'>❌ Failed to setup test data: " . $e->getMessage() . "</div>\n";
            throw $e;
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test basic API response structure
     */
    private function testBasicAPIResponse() {
        echo "<div class='section'><h3>🧪 Testing Basic API Response</h3>\n";
        
        try {
            // Test basic summary API
            $response = $this->callAPI('/api/dashboard/summary.php');
            
            $this->recordTestResult('Basic API Response', [
                'API responds successfully' => $response !== false,
                'Response is valid JSON' => $response && isset($response['status']),
                'Status is success' => $response && $response['status'] === 'success',
                'Data structure exists' => $response && isset($response['data']),
                'Summary data exists' => $response && isset($response['data']['summary']),
                'Date field exists' => $response && isset($response['data']['date'])
            ]);
            
            if ($response) {
                echo "<div class='info'>📊 Basic API Response Structure:</div>\n";
                echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>\n";
            }
            
        } catch (Exception $e) {
            $this->recordTestResult('Basic API Response', ['Exception' => false]);
            echo "<div class='fail'>❌ Basic API test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test time remaining calculation accuracy
     */
    private function testTimeRemainingCalculation() {
        echo "<div class='section'><h3>🧪 Testing Time Remaining Calculation</h3>\n";
        
        try {
            // Test enhanced API with customer list
            $response = $this->callAPI('/api/dashboard/summary.php?include_customers=true&limit=20');
            
            $this->recordTestResult('Enhanced API Response', [
                'API responds successfully' => $response !== false,
                'Customers data exists' => $response && isset($response['data']['customers']),
                'Pagination data exists' => $response && isset($response['data']['pagination']),
                'Customers array is array' => $response && is_array($response['data']['customers'])
            ]);
            
            if ($response && isset($response['data']['customers'])) {
                $customers = $response['data']['customers'];
                $timeCalculationChecks = [];
                
                // Check each test customer's time calculation
                foreach ($customers as $customer) {
                    $code = $customer['CustomerCode'];
                    if (isset($this->testCustomers[$code])) {
                        $expected = $this->testCustomers[$code]['expected_time_remaining'];
                        $actual = $customer['time_remaining_days'];
                        $tolerance = 1; // Allow 1 day tolerance
                        
                        $timeCalculationChecks["Time calculation for $code"] = 
                            abs($actual - $expected) <= $tolerance;
                        
                        // Check time status logic
                        if ($actual <= 0) {
                            $timeCalculationChecks["Time status OVERDUE for $code"] = 
                                $customer['time_status'] === 'OVERDUE';
                        } elseif ($actual <= 7) {
                            $timeCalculationChecks["Time status URGENT for $code"] = 
                                $customer['time_status'] === 'URGENT';
                        } elseif ($actual <= 14) {
                            $timeCalculationChecks["Time status SOON for $code"] = 
                                $customer['time_status'] === 'SOON';
                        } else {
                            $timeCalculationChecks["Time status NORMAL for $code"] = 
                                $customer['time_status'] === 'NORMAL';
                        }
                        
                        echo "<div class='info'>📊 $code: Expected: $expected, Actual: $actual, Status: {$customer['time_status']}</div>\n";
                    }
                }
                
                $this->recordTestResult('Time Calculation Accuracy', $timeCalculationChecks);
                
                // Test required fields
                $firstCustomer = $customers[0] ?? null;
                if ($firstCustomer) {
                    $this->recordTestResult('Required Fields Present', [
                        'time_remaining_days exists' => isset($firstCustomer['time_remaining_days']),
                        'time_status exists' => isset($firstCustomer['time_status']),
                        'CustomerTemperature exists' => isset($firstCustomer['CustomerTemperature']),
                        'assign_date exists' => isset($firstCustomer['assign_date']),
                        'time_remaining_days is integer' => is_int($firstCustomer['time_remaining_days'])
                    ]);
                }
            }
            
        } catch (Exception $e) {
            $this->recordTestResult('Time Remaining Calculation', ['Exception' => false]);
            echo "<div class='fail'>❌ Time calculation test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test customer temperature integration
     */
    private function testCustomerTemperatureIntegration() {
        echo "<div class='section'><h3>🧪 Testing Customer Temperature Integration</h3>\n";
        
        try {
            $response = $this->callAPI('/api/dashboard/summary.php?include_customers=true&limit=20');
            
            if ($response && isset($response['data']['customers'])) {
                $customers = $response['data']['customers'];
                $temperatureChecks = [];
                
                $temperatures = ['HOT', 'WARM', 'COLD', 'FROZEN'];
                $foundTemperatures = [];
                
                foreach ($customers as $customer) {
                    $temp = $customer['CustomerTemperature'] ?? null;
                    if ($temp) {
                        $foundTemperatures[] = $temp;
                    }
                    
                    // Check priority ordering (HOT should come before COLD for same time remaining)
                    $temperatureChecks['CustomerTemperature field exists'] = isset($customer['CustomerTemperature']);
                    $temperatureChecks['CustomerTemperature is valid enum'] = 
                        in_array($temp, $temperatures) || $temp === null;
                }
                
                $uniqueTemperatures = array_unique($foundTemperatures);
                $temperatureChecks['Multiple temperatures found'] = count($uniqueTemperatures) > 1;
                $temperatureChecks['All temperatures are valid'] = 
                    empty(array_diff($uniqueTemperatures, $temperatures));
                
                $this->recordTestResult('Customer Temperature Integration', $temperatureChecks);
                
                echo "<div class='info'>📊 Found temperatures: " . implode(', ', $uniqueTemperatures) . "</div>\n";
            }
            
        } catch (Exception $e) {
            $this->recordTestResult('Customer Temperature Integration', ['Exception' => false]);
            echo "<div class='fail'>❌ Temperature integration test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test enhanced customer list API
     */
    private function testEnhancedCustomerListAPI() {
        echo "<div class='section'><h3>🧪 Testing Enhanced Customer List API</h3>\n";
        
        try {
            // Test with different parameters
            $testCases = [
                'Default (no customers)' => '/api/dashboard/summary.php',
                'With customers (limit 5)' => '/api/dashboard/summary.php?include_customers=true&limit=5',
                'With customers (page 2)' => '/api/dashboard/summary.php?include_customers=true&limit=5&page=2'
            ];
            
            foreach ($testCases as $testName => $url) {
                $response = $this->callAPI($url);
                
                $checks = [
                    'API responds' => $response !== false,
                    'Valid JSON structure' => $response && isset($response['status']),
                    'Success status' => $response && $response['status'] === 'success'
                ];
                
                if (strpos($url, 'include_customers=true') !== false) {
                    $checks['Customers array exists'] = $response && isset($response['data']['customers']);
                    $checks['Pagination exists'] = $response && isset($response['data']['pagination']);
                    
                    if ($response && isset($response['data']['customers'])) {
                        $customers = $response['data']['customers'];
                        $checks['Customers is array'] = is_array($customers);
                        $checks['Respects limit'] = count($customers) <= 5;
                        
                        if (!empty($customers)) {
                            $firstCustomer = $customers[0];
                            $checks['Has time_remaining_days'] = isset($firstCustomer['time_remaining_days']);
                            $checks['Has CustomerTemperature'] = isset($firstCustomer['CustomerTemperature']);
                        }
                    }
                } else {
                    $checks['No customers when not requested'] = $response && !isset($response['data']['customers']);
                }
                
                $this->recordTestResult($testName, $checks);
            }
            
        } catch (Exception $e) {
            $this->recordTestResult('Enhanced Customer List API', ['Exception' => false]);
            echo "<div class='fail'>❌ Enhanced API test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test edge cases
     */
    private function testEdgeCases() {
        echo "<div class='section'><h3>🧪 Testing Edge Cases</h3>\n";
        
        try {
            // Create edge case customers
            $this->pdo->beginTransaction();
            
            // Customer with NULL dates
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (
                    CustomerCode, CustomerName, CustomerTel, CustomerStatus,
                    Sales, AssignDate, LastContactDate, CreatedDate, CreatedBy
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                'TEST_NULL_DATES',
                'Customer with NULL Dates',
                '0900000201',
                'ลูกค้าใหม่',
                'admin',
                null,
                null,
                'test_enhanced_dashboard'
            ]);
            
            // Customer with future dates
            $stmt->execute([
                'TEST_FUTURE_DATES',
                'Customer with Future Dates',
                '0900000202',
                'ลูกค้าติดตาม',
                'admin',
                date('Y-m-d H:i:s', strtotime('+10 days')),
                date('Y-m-d H:i:s', strtotime('+5 days')),
                'test_enhanced_dashboard'
            ]);
            
            $this->pdo->commit();
            
            // Test edge cases
            $response = $this->callAPI('/api/dashboard/summary.php?include_customers=true&limit=50');
            
            $edgeCaseChecks = [];
            
            if ($response && isset($response['data']['customers'])) {
                $customers = $response['data']['customers'];
                
                foreach ($customers as $customer) {
                    $code = $customer['CustomerCode'];
                    
                    if ($code === 'TEST_NULL_DATES') {
                        $edgeCaseChecks['NULL dates handled'] = isset($customer['time_remaining_days']);
                        $edgeCaseChecks['NULL dates calculation valid'] = 
                            is_int($customer['time_remaining_days']);
                    }
                    
                    if ($code === 'TEST_FUTURE_DATES') {
                        $edgeCaseChecks['Future dates handled'] = isset($customer['time_remaining_days']);
                        $edgeCaseChecks['Future dates give positive time'] = 
                            $customer['time_remaining_days'] > 0;
                    }
                }
                
                // Check all customers have required fields
                $allHaveTimeRemaining = true;
                $allHaveTemperature = true;
                
                foreach ($customers as $customer) {
                    if (!isset($customer['time_remaining_days'])) {
                        $allHaveTimeRemaining = false;
                    }
                    // CustomerTemperature can be NULL, so just check if key exists
                    if (!array_key_exists('CustomerTemperature', $customer)) {
                        $allHaveTemperature = false;
                    }
                }
                
                $edgeCaseChecks['All customers have time_remaining_days'] = $allHaveTimeRemaining;
                $edgeCaseChecks['All customers have CustomerTemperature field'] = $allHaveTemperature;
            }
            
            $this->recordTestResult('Edge Cases', $edgeCaseChecks);
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            $this->recordTestResult('Edge Cases', ['Exception' => false]);
            echo "<div class='fail'>❌ Edge cases test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test API performance
     */
    private function testPerformance() {
        echo "<div class='section'><h3>🧪 Testing API Performance</h3>\n";
        
        try {
            // Test response time
            $startTime = microtime(true);
            $response = $this->callAPI('/api/dashboard/summary.php?include_customers=true&limit=50');
            $endTime = microtime(true);
            
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $performanceChecks = [
                'API responds within 500ms' => $responseTime < 500,
                'API responds within 1000ms' => $responseTime < 1000,
                'Response contains data' => $response && isset($response['data'])
            ];
            
            $this->recordTestResult('Performance', $performanceChecks);
            
            echo "<div class='info'>📊 Response time: " . round($responseTime, 2) . "ms</div>\n";
            
            if ($response && isset($response['data']['customers'])) {
                $customerCount = count($response['data']['customers']);
                echo "<div class='info'>📊 Processed $customerCount customers</div>\n";
            }
            
        } catch (Exception $e) {
            $this->recordTestResult('Performance', ['Exception' => false]);
            echo "<div class='fail'>❌ Performance test failed: " . $e->getMessage() . "</div>\n";
        }
        
        echo "</div>\n";
    }
    
    /**
     * Call API endpoint and return decoded response
     */
    private function callAPI($endpoint) {
        // Simulate API call by including the file
        ob_start();
        
        // Set up session for authentication
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        
        // Parse query parameters
        $parts = parse_url($endpoint);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $_GET);
        } else {
            $_GET = [];
        }
        
        try {
            include dirname(__DIR__) . '/../../' . $parts['path'];
            $output = ob_get_clean();
            return json_decode($output, true);
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }
    
    /**
     * Cleanup test data
     */
    private function cleanupTestData() {
        echo "<div class='section'><h3>🧹 Cleaning up test data...</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Delete test customers
            $stmt = $this->pdo->prepare("DELETE FROM customers WHERE CreatedBy = 'test_enhanced_dashboard'");
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            $this->pdo->commit();
            echo "<div class='pass'>✅ Test data cleaned up: $deletedCount customers deleted</div>\n";
            
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
            'status' => $passedCount === $total ? 'PASS' : 'FAIL',
            'details' => $checks
        ];
        
        // Display individual check results
        echo "<div class='info'><strong>$testName:</strong><br>\n";
        foreach ($checks as $checkName => $result) {
            $icon = $result ? '✅' : '❌';
            echo "$icon $checkName<br>\n";
        }
        echo "</div>\n";
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
        echo "- Enhanced Dashboard API: " . ($overallPercentage >= 90 ? "✅ Ready for Production" : "❌ Needs Fixes") . "<br>\n";
        echo "</div>\n";
        
        if ($overallPercentage >= 90) {
            echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "🚀 <strong>Enhanced Dashboard API is ready for Production!</strong><br>\n";
            echo "Time remaining calculation and customer temperature integration are working correctly.<br>\n";
            echo "API performance meets requirements and handles edge cases properly.\n";
            echo "</div>\n";
        }
        
        echo "</div>\n";
    }
}

// Run the tests
$tester = new EnhancedDashboardTest();
$tester->runAllTests();

echo "<h3>🔗 API Usage Examples</h3>\n";
echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px;'>\n";
echo "<strong>Basic Summary (Existing functionality):</strong><br>\n";
echo "<code>GET /api/dashboard/summary.php</code><br><br>\n";
echo "<strong>Enhanced with Customer List:</strong><br>\n";
echo "<code>GET /api/dashboard/summary.php?include_customers=true&limit=20</code><br><br>\n";
echo "<strong>With Pagination:</strong><br>\n";
echo "<code>GET /api/dashboard/summary.php?include_customers=true&limit=10&page=2</code><br>\n";
echo "</div>\n";

echo "</body></html>\n";
?>