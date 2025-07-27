<?php
/**
 * Debug Assignment Creation Issues
 * Detailed debugging for createSalesAssignment method
 */

// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Debug Assignment Creation</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .status{padding:10px;margin:5px 0;border-radius:5px;} .pass{background:#d4edda;} .fail{background:#f8d7da;} .info{background:#d1ecf1;} .debug{background:#fff3cd;}</style>";
echo "</head><body>\n";

echo "<h1>🔍 Debug Assignment Creation</h1>\n";

try {
    // Database connection
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<div class='status pass'>✅ Database connection successful</div>\n";
    
    // Load SalesHistory
    require_once 'includes/SalesHistory.php';
    $salesHistory = new SalesHistory();
    echo "<div class='status pass'>✅ SalesHistory class loaded</div>\n";
    
    // Create test data
    echo "<h2>📋 Setting up test data</h2>\n";
    
    $testCustomerCode = 'DEBUG_ASSIGN_001';
    $testSalesUser = 'debug_sales_001';
    
    // Create test customer
    $pdo->exec("DELETE FROM customers WHERE CustomerCode = '$testCustomerCode'");
    $insertCustomerSQL = "INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, AssignmentCount, CreatedDate, CreatedBy) VALUES (?, ?, ?, 0, NOW(), 'debug_test')";
    $stmt = $pdo->prepare($insertCustomerSQL);
    $stmt->execute([$testCustomerCode, 'Debug Assignment Test', '0900000001']);
    echo "<div class='status pass'>✅ Test customer created: $testCustomerCode</div>\n";
    
    // Create test sales user
    $pdo->exec("DELETE FROM users WHERE Username = '$testSalesUser'");
    $insertUserSQL = "INSERT INTO users (Username, Password, FirstName, LastName, Role, Status, CreatedDate, CreatedBy) VALUES (?, ?, 'Debug', 'Sales', 'Sale', 1, NOW(), 'debug_test')";
    $stmt = $pdo->prepare($insertUserSQL);
    $stmt->execute([$testSalesUser, password_hash('debug123', PASSWORD_DEFAULT)]);
    echo "<div class='status pass'>✅ Test sales user created: $testSalesUser</div>\n";
    
    // Test validation manually
    echo "<h2>🔍 Testing Validation Logic</h2>\n";
    
    $testData = [
        'CustomerCode' => $testCustomerCode,
        'SaleName' => $testSalesUser,
        'StartDate' => date('Y-m-d H:i:s')
    ];
    
    echo "<div class='status debug'>🔍 Validation data: " . json_encode($testData) . "</div>\n";
    
    $validationErrors = $salesHistory->validateAssignmentData($testData);
    
    if (empty($validationErrors)) {
        echo "<div class='status pass'>✅ Validation passed</div>\n";
    } else {
        echo "<div class='status fail'>❌ Validation failed:</div>\n";
        foreach ($validationErrors as $error) {
            echo "<div class='status fail'>- $error</div>\n";
        }
    }
    
    // Test individual steps of assignment creation
    echo "<h2>🧪 Testing Assignment Creation Steps</h2>\n";
    
    // Step 1: Check current count
    $beforeCount = $salesHistory->getAssignmentCount($testCustomerCode);
    echo "<div class='status info'>📊 Before assignment: Count = $beforeCount</div>\n";
    
    // Step 2: Test transaction handling
    echo "<div class='status debug'>🔍 Testing transaction...</div>\n";
    
    try {
        $salesHistory->beginTransaction();
        echo "<div class='status pass'>✅ Transaction started</div>\n";
        
        // Step 3: End current assignment (should work even if none exists)
        $endResult = $salesHistory->endCurrentAssignment($testCustomerCode);
        echo "<div class='status info'>📋 End current assignment result: " . ($endResult ? 'true' : 'false') . "</div>\n";
        
        // Step 4: Insert new assignment
        $assignmentData = [
            'CustomerCode' => $testCustomerCode,
            'SaleName' => $testSalesUser,
            'StartDate' => date('Y-m-d H:i:s'),
            'AssignBy' => 'debug_test',
            'CreatedBy' => 'debug_test'
        ];
        
        echo "<div class='status debug'>🔍 Assignment data: " . json_encode($assignmentData) . "</div>\n";
        
        // Test direct insert
        $insertSQL = "INSERT INTO sales_histories (CustomerCode, SaleName, StartDate, AssignBy, CreatedBy) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertSQL);
        $insertResult = $stmt->execute([
            $assignmentData['CustomerCode'],
            $assignmentData['SaleName'], 
            $assignmentData['StartDate'],
            $assignmentData['AssignBy'],
            $assignmentData['CreatedBy']
        ]);
        
        if ($insertResult) {
            $assignmentId = $pdo->lastInsertId();
            echo "<div class='status pass'>✅ Assignment inserted: ID = $assignmentId</div>\n";
            
            // Step 5: Update customer
            $updateCustomerSQL = "UPDATE customers SET Sales = ?, AssignDate = ? WHERE CustomerCode = ?";
            $stmt = $pdo->prepare($updateCustomerSQL);
            $customerUpdateResult = $stmt->execute([
                $assignmentData['SaleName'],
                $assignmentData['StartDate'],
                $assignmentData['CustomerCode']
            ]);
            
            if ($customerUpdateResult) {
                echo "<div class='status pass'>✅ Customer updated</div>\n";
                
                // Step 6: Increment count
                $countResult = $salesHistory->incrementAssignmentCount($testCustomerCode);
                if ($countResult) {
                    echo "<div class='status pass'>✅ Assignment count incremented</div>\n";
                    
                    $afterCount = $salesHistory->getAssignmentCount($testCustomerCode);
                    echo "<div class='status info'>📊 After assignment: Count = $afterCount</div>\n";
                    
                    if ($afterCount > $beforeCount) {
                        echo "<div class='status pass'>🎉 Assignment creation simulation successful: $beforeCount → $afterCount</div>\n";
                    }
                } else {
                    echo "<div class='status fail'>❌ Failed to increment count</div>\n";
                }
            } else {
                echo "<div class='status fail'>❌ Failed to update customer</div>\n";
            }
        } else {
            echo "<div class='status fail'>❌ Failed to insert assignment</div>\n";
        }
        
        $salesHistory->commit();
        echo "<div class='status pass'>✅ Transaction committed</div>\n";
        
    } catch (Exception $e) {
        $salesHistory->rollback();
        echo "<div class='status fail'>❌ Transaction failed: " . $e->getMessage() . "</div>\n";
    }
    
    // Now test the actual method
    echo "<h2>🎯 Testing Actual createSalesAssignment Method</h2>\n";
    
    // Clean up first
    $pdo->exec("DELETE FROM sales_histories WHERE CustomerCode = '$testCustomerCode'");
    $pdo->exec("UPDATE customers SET Sales = NULL, AssignDate = NULL, AssignmentCount = 0 WHERE CustomerCode = '$testCustomerCode'");
    
    $preMethodCount = $salesHistory->getAssignmentCount($testCustomerCode);
    echo "<div class='status info'>📊 Before method call: Count = $preMethodCount</div>\n";
    
    // Test the actual method
    $assignmentId = $salesHistory->createSalesAssignment($testCustomerCode, $testSalesUser, 'debug_test');
    
    if ($assignmentId) {
        echo "<div class='status pass'>🎉 createSalesAssignment() succeeded: ID = $assignmentId</div>\n";
        
        $postMethodCount = $salesHistory->getAssignmentCount($testCustomerCode);
        echo "<div class='status info'>📊 After method call: Count = $postMethodCount</div>\n";
        
        if ($postMethodCount > $preMethodCount) {
            echo "<div class='status pass'>✅ Method properly incremented count: $preMethodCount → $postMethodCount</div>\n";
        } else {
            echo "<div class='status fail'>❌ Method did not increment count</div>\n";
        }
        
        // Verify assignment exists
        $checkAssignmentSQL = "SELECT * FROM sales_histories WHERE id = ?";
        $stmt = $pdo->prepare($checkAssignmentSQL);
        $stmt->execute([$assignmentId]);
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($assignment) {
            echo "<div class='status pass'>✅ Assignment record verified in database</div>\n";
            echo "<div class='status debug'>🔍 Assignment details: " . json_encode($assignment) . "</div>\n";
        }
        
        // Verify customer update
        $checkCustomerSQL = "SELECT Sales, AssignDate, AssignmentCount FROM customers WHERE CustomerCode = ?";
        $stmt = $pdo->prepare($checkCustomerSQL);
        $stmt->execute([$testCustomerCode]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo "<div class='status pass'>✅ Customer record verified</div>\n";
            echo "<div class='status debug'>🔍 Customer details: " . json_encode($customer) . "</div>\n";
        }
        
    } else {
        echo "<div class='status fail'>❌ createSalesAssignment() failed</div>\n";
        
        // Check error logs
        $errorFile = ini_get('error_log');
        if ($errorFile && file_exists($errorFile)) {
            $lastErrors = tail($errorFile, 5);
            echo "<div class='status debug'>🔍 Recent error log entries:</div>\n";
            foreach ($lastErrors as $error) {
                if (strpos($error, 'Assignment') !== false) {
                    echo "<div class='status debug'>- $error</div>\n";
                }
            }
        }
    }
    
    // Cleanup
    echo "<h2>🧹 Cleanup</h2>\n";
    $pdo->exec("DELETE FROM customers WHERE CustomerCode = '$testCustomerCode'");
    $pdo->exec("DELETE FROM users WHERE Username = '$testSalesUser'");
    $pdo->exec("DELETE FROM sales_histories WHERE CustomerCode = '$testCustomerCode'");
    echo "<div class='status pass'>✅ Test data cleaned up</div>\n";
    
} catch (Exception $e) {
    echo "<div class='status fail'>❌ Error: " . $e->getMessage() . "</div>\n";
    echo "<div class='status debug'>Stack trace: " . $e->getTraceAsString() . "</div>\n";
}

// Helper function to read last n lines of a file
function tail($filename, $lines = 10) {
    $handle = fopen($filename, "r");
    $linecounter = $lines;
    $pos = -2;
    $beginning = false;
    $text = array();
    
    while ($linecounter > 0) {
        $t = " ";
        while ($t != "\n") {
            if(fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true; 
                break; 
            }
            $t = fgetc($handle);
            $pos --;
        }
        $linecounter --;
        if ($beginning) {
            rewind($handle);
        }
        $text[$lines-$linecounter-1] = fgets($handle);
        if ($beginning) break;
    }
    fclose ($handle);
    return array_reverse($text);
}

echo "</body></html>\n";
?>