<?php
/**
 * Debug Assignment Count Issues
 * Check database schema and test basic operations
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Debug Assignment Count</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .status{padding:10px;margin:5px 0;border-radius:5px;} .pass{background:#d4edda;} .fail{background:#f8d7da;} .info{background:#d1ecf1;}</style>";
echo "</head><body>\n";

echo "<h1>🔍 Debug Assignment Count Issues</h1>\n";

try {
    // Test database connection
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<div class='status pass'>✅ Database connection successful</div>\n";
    
    // Check customers table structure
    echo "<h2>📊 Database Schema Check</h2>\n";
    $stmt = $pdo->query("DESCRIBE customers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasAssignmentCount = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'AssignmentCount') {
            $hasAssignmentCount = true;
            echo "<div class='status pass'>✅ AssignmentCount column found: {$column['Type']}, Default: {$column['Default']}, Null: {$column['Null']}</div>\n";
            break;
        }
    }
    
    if (!$hasAssignmentCount) {
        echo "<div class='status fail'>❌ AssignmentCount column NOT FOUND!</div>\n";
        echo "<div class='status info'>💡 Need to add AssignmentCount column to customers table</div>\n";
        
        // Add the missing column
        echo "<h3>🔧 Adding AssignmentCount Column</h3>\n";
        $addColumnSQL = "ALTER TABLE customers ADD COLUMN AssignmentCount INT(11) DEFAULT 0 NOT NULL";
        
        try {
            $pdo->exec($addColumnSQL);
            echo "<div class='status pass'>✅ AssignmentCount column added successfully</div>\n";
        } catch (Exception $e) {
            echo "<div class='status fail'>❌ Failed to add column: " . $e->getMessage() . "</div>\n";
        }
    }
    
    // Test SalesHistory class
    echo "<h2>🧪 SalesHistory Class Test</h2>\n";
    require_once 'includes/SalesHistory.php';
    $salesHistory = new SalesHistory();
    echo "<div class='status pass'>✅ SalesHistory class loaded</div>\n";
    
    // Test customer creation
    echo "<h2>👤 Test Customer Operations</h2>\n";
    
    // Create a test customer if not exists
    $testCustomerCode = 'DEBUG_TEST_001';
    $checkCustomerSQL = "SELECT CustomerCode, AssignmentCount FROM customers WHERE CustomerCode = ?";
    $stmt = $pdo->prepare($checkCustomerSQL);
    $stmt->execute([$testCustomerCode]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        // Create test customer
        $insertSQL = "INSERT INTO customers (CustomerCode, CustomerName, CustomerTel, AssignmentCount, CreatedDate, CreatedBy) VALUES (?, ?, ?, 0, NOW(), 'debug_test')";
        $stmt = $pdo->prepare($insertSQL);
        $stmt->execute([$testCustomerCode, 'Debug Test Customer', '0900000000']);
        echo "<div class='status pass'>✅ Test customer created: $testCustomerCode</div>\n";
        
        $customer = ['CustomerCode' => $testCustomerCode, 'AssignmentCount' => 0];
    } else {
        echo "<div class='status info'>ℹ️ Test customer exists: {$customer['CustomerCode']}, Count: {$customer['AssignmentCount']}</div>\n";
    }
    
    // Test incrementAssignmentCount
    echo "<h2>🔢 Test Assignment Count Operations</h2>\n";
    
    $beforeCount = $salesHistory->getAssignmentCount($testCustomerCode);
    echo "<div class='status info'>📊 Before increment: Count = $beforeCount</div>\n";
    
    $incrementResult = $salesHistory->incrementAssignmentCount($testCustomerCode);
    if ($incrementResult) {
        echo "<div class='status pass'>✅ incrementAssignmentCount() executed successfully</div>\n";
    } else {
        echo "<div class='status fail'>❌ incrementAssignmentCount() failed</div>\n";
    }
    
    $afterCount = $salesHistory->getAssignmentCount($testCustomerCode);
    echo "<div class='status info'>📊 After increment: Count = $afterCount</div>\n";
    
    if ($afterCount > $beforeCount) {
        echo "<div class='status pass'>✅ Assignment count incremented correctly: $beforeCount → $afterCount</div>\n";
    } else {
        echo "<div class='status fail'>❌ Assignment count did not increment: $beforeCount → $afterCount</div>\n";
        
        // Debug the SQL execution
        echo "<h3>🔍 SQL Debug</h3>\n";
        $debugSQL = "UPDATE customers SET AssignmentCount = COALESCE(AssignmentCount, 0) + 1, ModifiedDate = NOW(), ModifiedBy = 'debug' WHERE CustomerCode = ?";
        echo "<div class='status info'>SQL: $debugSQL</div>\n";
        echo "<div class='status info'>Parameter: $testCustomerCode</div>\n";
        
        $stmt = $pdo->prepare($debugSQL);
        $result = $stmt->execute([$testCustomerCode]);
        $rowCount = $stmt->rowCount();
        
        echo "<div class='status info'>Execute result: " . ($result ? 'true' : 'false') . "</div>\n";
        echo "<div class='status info'>Rows affected: $rowCount</div>\n";
        
        if ($rowCount > 0) {
            $finalCount = $salesHistory->getAssignmentCount($testCustomerCode);
            echo "<div class='status info'>📊 Final count after debug: $finalCount</div>\n";
        }
    }
    
    // Test createSalesAssignment integration
    echo "<h2>🎯 Test Assignment Creation Integration</h2>\n";
    
    // Create test sales user if not exists
    $testSalesUser = 'debug_sales_user';
    $checkUserSQL = "SELECT Username FROM users WHERE Username = ?";
    $stmt = $pdo->prepare($checkUserSQL);
    $stmt->execute([$testSalesUser]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $insertUserSQL = "INSERT INTO users (Username, Password, FirstName, LastName, Role, Status, CreatedDate, CreatedBy) VALUES (?, ?, 'Debug', 'Sales', 'Sale', 1, NOW(), 'debug_test')";
        $stmt = $pdo->prepare($insertUserSQL);
        $stmt->execute([$testSalesUser, password_hash('debug123', PASSWORD_DEFAULT)]);
        echo "<div class='status pass'>✅ Test sales user created: $testSalesUser</div>\n";
    } else {
        echo "<div class='status info'>ℹ️ Test sales user exists: $testSalesUser</div>\n";
    }
    
    $preAssignmentCount = $salesHistory->getAssignmentCount($testCustomerCode);
    echo "<div class='status info'>📊 Before assignment: Count = $preAssignmentCount</div>\n";
    
    $assignmentId = $salesHistory->createSalesAssignment($testCustomerCode, $testSalesUser, 'debug_test');
    
    if ($assignmentId) {
        echo "<div class='status pass'>✅ Assignment created successfully: ID = $assignmentId</div>\n";
        
        $postAssignmentCount = $salesHistory->getAssignmentCount($testCustomerCode);
        echo "<div class='status info'>📊 After assignment: Count = $postAssignmentCount</div>\n";
        
        if ($postAssignmentCount > $preAssignmentCount) {
            echo "<div class='status pass'>✅ Assignment creation incremented count: $preAssignmentCount → $postAssignmentCount</div>\n";
        } else {
            echo "<div class='status fail'>❌ Assignment creation did not increment count</div>\n";
        }
    } else {
        echo "<div class='status fail'>❌ Assignment creation failed</div>\n";
    }
    
    // Cleanup
    echo "<h2>🧹 Cleanup</h2>\n";
    $pdo->prepare("DELETE FROM customers WHERE CustomerCode = ?")->execute([$testCustomerCode]);
    $pdo->prepare("DELETE FROM users WHERE Username = ?")->execute([$testSalesUser]);
    $pdo->prepare("DELETE FROM sales_histories WHERE CustomerCode = ?")->execute([$testCustomerCode]);
    echo "<div class='status pass'>✅ Test data cleaned up</div>\n";
    
} catch (Exception $e) {
    echo "<div class='status fail'>❌ Error: " . $e->getMessage() . "</div>\n";
    echo "<div class='status info'>Stack trace: " . $e->getTraceAsString() . "</div>\n";
}

echo "</body></html>\n";
?>