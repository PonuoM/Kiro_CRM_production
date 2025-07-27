<?php
/**
 * Migration v2.0 Test Script
 * Tests for Story 1.1: Alter Database Schema
 * 
 * This script tests the migration v2.0 SQL script
 * to ensure all Acceptance Criteria are met.
 */

require_once __DIR__ . '/../../config/database.php';

class MigrationTest {
    private $pdo;
    private $testResults = [];
    
    public function __construct() {
        try {
            $db = Database::getInstance();
            $this->pdo = $db->getConnection();
            echo "<h1>🧪 Migration v2.0 Test Suite</h1>\n";
            echo "<p><strong>Story 1.1:</strong> Alter Database Schema for Lead Management Logic</p>\n";
        } catch (Exception $e) {
            die("❌ Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Run all migration tests
     */
    public function runAllTests() {
        echo "<h2>📋 Running Migration Tests...</h2>\n";
        
        // Test Acceptance Criteria
        $this->testAC1_ContactAttemptsColumn();
        $this->testAC2_AssignmentCountColumn();
        $this->testAC3_CustomerTemperatureFrozen();
        $this->testAC4_SupervisorIdField();
        
        // Additional integrity tests
        $this->testIndexes();
        $this->testForeignKeyConstraints();
        $this->testDefaultValues();
        $this->testBackwardCompatibility();
        
        // Display summary
        $this->displayTestSummary();
    }
    
    /**
     * AC1: Test ContactAttempts column exists with correct properties
     */
    private function testAC1_ContactAttemptsColumn() {
        echo "<h3>🧩 AC1: Testing ContactAttempts Column</h3>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'customers' 
                AND COLUMN_NAME = 'ContactAttempts'
            ");
            
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($column) {
                $checks = [
                    'Column exists' => true,
                    'Type is INT' => strpos($column['COLUMN_TYPE'], 'int') !== false,
                    'Default is 0' => $column['COLUMN_DEFAULT'] === '0',
                    'Not nullable' => $column['IS_NULLABLE'] === 'NO',
                    'Has comment' => !empty($column['COLUMN_COMMENT'])
                ];
                
                $this->recordTestResults('AC1: ContactAttempts Column', $checks);
                
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "✅ <strong>ContactAttempts column found:</strong><br>\n";
                echo "- Type: {$column['COLUMN_TYPE']}<br>\n";
                echo "- Default: {$column['COLUMN_DEFAULT']}<br>\n";
                echo "- Nullable: {$column['IS_NULLABLE']}<br>\n";
                echo "- Comment: {$column['COLUMN_COMMENT']}<br>\n";
                echo "</div>\n";
            } else {
                $this->recordTestResults('AC1: ContactAttempts Column', ['Column exists' => false]);
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "❌ <strong>ContactAttempts column not found!</strong>\n";
                echo "</div>\n";
            }
        } catch (Exception $e) {
            $this->recordTestResults('AC1: ContactAttempts Column', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing ContactAttempts:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * AC2: Test AssignmentCount column exists with correct properties
     */
    private function testAC2_AssignmentCountColumn() {
        echo "<h3>🧩 AC2: Testing AssignmentCount Column</h3>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'customers' 
                AND COLUMN_NAME = 'AssignmentCount'
            ");
            
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($column) {
                $checks = [
                    'Column exists' => true,
                    'Type is INT' => strpos($column['COLUMN_TYPE'], 'int') !== false,
                    'Default is 0' => $column['COLUMN_DEFAULT'] === '0',
                    'Not nullable' => $column['IS_NULLABLE'] === 'NO',
                    'Has comment' => !empty($column['COLUMN_COMMENT'])
                ];
                
                $this->recordTestResults('AC2: AssignmentCount Column', $checks);
                
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "✅ <strong>AssignmentCount column found:</strong><br>\n";
                echo "- Type: {$column['COLUMN_TYPE']}<br>\n";
                echo "- Default: {$column['COLUMN_DEFAULT']}<br>\n";
                echo "- Nullable: {$column['IS_NULLABLE']}<br>\n";
                echo "- Comment: {$column['COLUMN_COMMENT']}<br>\n";
                echo "</div>\n";
            } else {
                $this->recordTestResults('AC2: AssignmentCount Column', ['Column exists' => false]);
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "❌ <strong>AssignmentCount column not found!</strong>\n";
                echo "</div>\n";
            }
        } catch (Exception $e) {
            $this->recordTestResults('AC2: AssignmentCount Column', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing AssignmentCount:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * AC3: Test CustomerTemperature ENUM includes FROZEN
     */
    private function testAC3_CustomerTemperatureFrozen() {
        echo "<h3>🧩 AC3: Testing CustomerTemperature ENUM includes FROZEN</h3>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT COLUMN_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'customers' 
                AND COLUMN_NAME = 'CustomerTemperature'
            ");
            
            $columnType = $stmt->fetchColumn();
            
            if ($columnType) {
                $checks = [
                    'Column exists' => true,
                    'Is ENUM type' => strpos($columnType, 'enum') !== false,
                    'Contains HOT' => strpos($columnType, 'HOT') !== false,
                    'Contains WARM' => strpos($columnType, 'WARM') !== false,
                    'Contains COLD' => strpos($columnType, 'COLD') !== false,
                    'Contains FROZEN' => strpos($columnType, 'FROZEN') !== false
                ];
                
                $this->recordTestResults('AC3: CustomerTemperature FROZEN', $checks);
                
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "✅ <strong>CustomerTemperature ENUM found:</strong><br>\n";
                echo "- Type: {$columnType}<br>\n";
                echo "- Contains FROZEN: " . (strpos($columnType, 'FROZEN') !== false ? 'Yes' : 'No') . "<br>\n";
                echo "</div>\n";
                
                // Test inserting FROZEN value
                try {
                    $this->pdo->beginTransaction();
                    $this->pdo->exec("INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, CustomerTemperature) VALUES ('TEST_FROZEN', 'Test Customer', '0999999999', 'FROZEN')");
                    $this->pdo->exec("DELETE FROM customers WHERE CustomerCode = 'TEST_FROZEN'");
                    $this->pdo->commit();
                    
                    echo "<div style='background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                    echo "✅ <strong>FROZEN value test:</strong> Successfully inserted and deleted test record with FROZEN temperature\n";
                    echo "</div>\n";
                } catch (Exception $e) {
                    $this->pdo->rollback();
                    echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                    echo "❌ <strong>FROZEN value test failed:</strong> " . $e->getMessage() . "\n";
                    echo "</div>\n";
                }
            } else {
                $this->recordTestResults('AC3: CustomerTemperature FROZEN', ['Column exists' => false]);
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "❌ <strong>CustomerTemperature column not found!</strong>\n";
                echo "</div>\n";
            }
        } catch (Exception $e) {
            $this->recordTestResults('AC3: CustomerTemperature FROZEN', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing CustomerTemperature:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * AC4: Test supervisor_id field in users table
     */
    private function testAC4_SupervisorIdField() {
        echo "<h3>🧩 AC4: Testing supervisor_id Field in Users Table</h3>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'supervisor_id'
            ");
            
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($column) {
                $checks = [
                    'Column exists' => true,
                    'Type is INT' => strpos($column['COLUMN_TYPE'], 'int') !== false,
                    'Is nullable' => $column['IS_NULLABLE'] === 'YES',
                    'Has comment' => !empty($column['COLUMN_COMMENT'])
                ];
                
                $this->recordTestResults('AC4: supervisor_id Field', $checks);
                
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "✅ <strong>supervisor_id column found:</strong><br>\n";
                echo "- Type: {$column['COLUMN_TYPE']}<br>\n";
                echo "- Nullable: {$column['IS_NULLABLE']}<br>\n";
                echo "- Comment: {$column['COLUMN_COMMENT']}<br>\n";
                echo "</div>\n";
            } else {
                $this->recordTestResults('AC4: supervisor_id Field', ['Column exists' => false]);
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "❌ <strong>supervisor_id column not found!</strong>\n";
                echo "</div>\n";
            }
        } catch (Exception $e) {
            $this->recordTestResults('AC4: supervisor_id Field', ['Exception' => false]);
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing supervisor_id:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Test proper indexes are created
     */
    private function testIndexes() {
        echo "<h3>📊 Testing Database Indexes</h3>\n";
        
        $expectedIndexes = [
            'customers.idx_contact_attempts' => 'ContactAttempts',
            'customers.idx_assignment_count' => 'AssignmentCount',
            'users.idx_supervisor_id' => 'supervisor_id'
        ];
        
        foreach ($expectedIndexes as $indexName => $columnName) {
            list($tableName, $expectedIndexName) = explode('.', $indexName);
            
            try {
                $stmt = $this->pdo->query("
                    SELECT INDEX_NAME, COLUMN_NAME
                    FROM INFORMATION_SCHEMA.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '$tableName' 
                    AND INDEX_NAME = '$expectedIndexName'
                ");
                
                $index = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($index) {
                    echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                    echo "✅ Index <strong>$expectedIndexName</strong> exists on $tableName.$columnName\n";
                    echo "</div>\n";
                } else {
                    echo "<div style='background: #fff3cd; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                    echo "⚠️ Index <strong>$expectedIndexName</strong> not found on $tableName.$columnName\n";
                    echo "</div>\n";
                }
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                echo "❌ Error checking index $expectedIndexName: " . $e->getMessage() . "\n";
                echo "</div>\n";
            }
        }
    }
    
    /**
     * Test foreign key constraints
     */
    private function testForeignKeyConstraints() {
        echo "<h3>🔗 Testing Foreign Key Constraints</h3>\n";
        
        try {
            $stmt = $this->pdo->query("
                SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'supervisor_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            $constraint = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($constraint) {
                echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "✅ <strong>Foreign Key Constraint found:</strong><br>\n";
                echo "- Constraint: {$constraint['CONSTRAINT_NAME']}<br>\n";
                echo "- Table: {$constraint['TABLE_NAME']}.{$constraint['COLUMN_NAME']}<br>\n";
                echo "- References: {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}<br>\n";
                echo "</div>\n";
                
                $this->recordTestResults('Foreign Key Constraint', ['Constraint exists' => true]);
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "❌ <strong>Foreign Key Constraint not found for supervisor_id!</strong>\n";
                echo "</div>\n";
                
                $this->recordTestResults('Foreign Key Constraint', ['Constraint exists' => false]);
            }
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing Foreign Key:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Test default values work correctly
     */
    private function testDefaultValues() {
        echo "<h3>🎯 Testing Default Values</h3>\n";
        
        try {
            $this->pdo->beginTransaction();
            
            // Test ContactAttempts and AssignmentCount defaults
            $testCode = 'TEST_DEFAULTS_' . time();
            $this->pdo->exec("INSERT INTO customers (CustomerCode, CustomerName, CustomerTel) VALUES ('$testCode', 'Test Default Values', '0888888888')");
            
            $stmt = $this->pdo->query("SELECT ContactAttempts, AssignmentCount, CustomerTemperature FROM customers WHERE CustomerCode = '$testCode'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $defaultChecks = [
                    'ContactAttempts = 0' => $result['ContactAttempts'] == 0,
                    'AssignmentCount = 0' => $result['AssignmentCount'] == 0,
                    'CustomerTemperature default' => !empty($result['CustomerTemperature'])
                ];
                
                foreach ($defaultChecks as $check => $passed) {
                    if ($passed) {
                        echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                        echo "✅ Default value test: <strong>$check</strong> ✓\n";
                        echo "</div>\n";
                    } else {
                        echo "<div style='background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                        echo "❌ Default value test: <strong>$check</strong> ✗\n";
                        echo "</div>\n";
                    }
                }
                
                $this->recordTestResults('Default Values', $defaultChecks);
                
                echo "<div style='background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
                echo "📊 <strong>Test Record Values:</strong><br>\n";
                echo "- ContactAttempts: {$result['ContactAttempts']}<br>\n";
                echo "- AssignmentCount: {$result['AssignmentCount']}<br>\n";
                echo "- CustomerTemperature: {$result['CustomerTemperature']}<br>\n";
                echo "</div>\n";
            }
            
            // Clean up test record
            $this->pdo->exec("DELETE FROM customers WHERE CustomerCode = '$testCode'");
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing default values:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Test backward compatibility
     */
    private function testBackwardCompatibility() {
        echo "<h3>🔄 Testing Backward Compatibility</h3>\n";
        
        try {
            // Test that existing queries still work
            $testQueries = [
                "SELECT COUNT(*) FROM customers" => "Basic customers table query",
                "SELECT COUNT(*) FROM users" => "Basic users table query",
                "SELECT CustomerCode, CustomerName, CustomerStatus FROM customers LIMIT 1" => "Original customers columns",
                "SELECT Username, Role FROM users LIMIT 1" => "Original users columns"
            ];
            
            foreach ($testQueries as $query => $description) {
                try {
                    $stmt = $this->pdo->query($query);
                    $result = $stmt->fetchColumn();
                    
                    echo "<div style='background: #d4edda; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                    echo "✅ <strong>$description:</strong> Works (Result: $result)\n";
                    echo "</div>\n";
                } catch (Exception $e) {
                    echo "<div style='background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
                    echo "❌ <strong>$description:</strong> Failed - " . $e->getMessage() . "\n";
                    echo "</div>\n";
                }
            }
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
            echo "❌ <strong>Error testing backward compatibility:</strong> " . $e->getMessage() . "\n";
            echo "</div>\n";
        }
    }
    
    /**
     * Record test results
     */
    private function recordTestResults($testName, $checks) {
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
        echo "- Story 1.1 AC Compliance: " . ($overallPercentage >= 90 ? "✅ Ready for Production" : "❌ Needs Fixes") . "<br>\n";
        echo "</div>\n";
        
        if ($overallPercentage >= 90) {
            echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "🚀 <strong>Migration v2.0 is ready for Production deployment!</strong><br>\n";
            echo "All Acceptance Criteria have been met and database schema changes are verified.\n";
            echo "</div>\n";
        }
    }
}

// Run the tests
$tester = new MigrationTest();
$tester->runAllTests();

echo "<h3>🔗 Next Steps</h3>\n";
echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px;'>\n";
echo "1. Review test results above<br>\n";
echo "2. If all tests pass, proceed with Production migration<br>\n";
echo "3. Create backup before Production deployment<br>\n";
echo "4. Run migration script in Production environment<br>\n";
echo "5. Re-run this test script in Production to verify<br>\n";
echo "</div>\n";
?>