<?php
/**
 * Debug User Validation Issues
 * Check why user validation is failing
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Debug User Validation</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .status{padding:10px;margin:5px 0;border-radius:5px;} .pass{background:#d4edda;} .fail{background:#f8d7da;} .info{background:#d1ecf1;} .debug{background:#fff3cd;}</style>";
echo "</head><body>\n";

echo "<h1>🔍 Debug User Validation</h1>\n";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<div class='status pass'>✅ Database connection successful</div>\n";
    
    $testSalesUser = 'debug_sales_001';
    
    echo "<h2>👤 Check Test User Details</h2>\n";
    
    // Check if user exists
    $checkUserSQL = "SELECT * FROM users WHERE Username = ?";
    $stmt = $pdo->prepare($checkUserSQL);
    $stmt->execute([$testSalesUser]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<div class='status pass'>✅ Test user exists</div>\n";
        echo "<div class='status debug'>🔍 User details:</div>\n";
        foreach ($user as $key => $value) {
            if ($key !== 'Password') {
                echo "<div class='status info'>- $key: $value</div>\n";
            }
        }
    } else {
        echo "<div class='status fail'>❌ Test user does not exist</div>\n";
        
        // Create the user
        echo "<div class='status info'>🔧 Creating test user...</div>\n";
        $insertUserSQL = "INSERT INTO users (Username, Password, FirstName, LastName, Role, Status, CreatedDate, CreatedBy) VALUES (?, ?, 'Debug', 'Sales', 'Sale', 1, NOW(), 'debug_test')";
        $stmt = $pdo->prepare($insertUserSQL);
        $result = $stmt->execute([$testSalesUser, password_hash('debug123', PASSWORD_DEFAULT)]);
        
        if ($result) {
            echo "<div class='status pass'>✅ Test user created successfully</div>\n";
            
            // Fetch the created user
            $stmt = $pdo->prepare($checkUserSQL);
            $stmt->execute([$testSalesUser]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<div class='status debug'>🔍 Created user details:</div>\n";
            foreach ($user as $key => $value) {
                if ($key !== 'Password') {
                    echo "<div class='status info'>- $key: $value</div>\n";
                }
            }
        } else {
            echo "<div class='status fail'>❌ Failed to create test user</div>\n";
        }
    }
    
    echo "<h2>🔍 Test Validation Query</h2>\n";
    
    // Test the exact validation query from SalesHistory
    $validationSQL = "SELECT * FROM users WHERE Username = ? AND Role IN ('Sale', 'Supervisor') AND Status = 1";
    echo "<div class='status debug'>🔍 Validation SQL: $validationSQL</div>\n";
    echo "<div class='status debug'>🔍 Parameter: $testSalesUser</div>\n";
    
    $stmt = $pdo->prepare($validationSQL);
    $stmt->execute([$testSalesUser]);
    $validationResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($validationResult) {
        echo "<div class='status pass'>✅ Validation query succeeded</div>\n";
        echo "<div class='status debug'>🔍 Validation result:</div>\n";
        foreach ($validationResult as $key => $value) {
            if ($key !== 'Password') {
                echo "<div class='status info'>- $key: $value</div>\n";
            }
        }
    } else {
        echo "<div class='status fail'>❌ Validation query failed</div>\n";
        
        // Debug: check different conditions
        echo "<div class='status debug'>🔍 Debugging validation conditions:</div>\n";
        
        // Check username only
        $stmt = $pdo->prepare("SELECT Username, Role, Status FROM users WHERE Username = ?");
        $stmt->execute([$testSalesUser]);
        $userCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userCheck) {
            echo "<div class='status info'>📋 User found with Username: {$userCheck['Username']}, Role: {$userCheck['Role']}, Status: {$userCheck['Status']}</div>\n";
            
            // Check role condition
            if (in_array($userCheck['Role'], ['Sale', 'Supervisor'])) {
                echo "<div class='status pass'>✅ Role condition met: {$userCheck['Role']}</div>\n";
            } else {
                echo "<div class='status fail'>❌ Role condition failed: {$userCheck['Role']} not in [Sale, Supervisor]</div>\n";
            }
            
            // Check status condition
            if ($userCheck['Status'] == 1) {
                echo "<div class='status pass'>✅ Status condition met: {$userCheck['Status']}</div>\n";
            } else {
                echo "<div class='status fail'>❌ Status condition failed: {$userCheck['Status']} != 1</div>\n";
            }
        } else {
            echo "<div class='status fail'>❌ User not found with username: $testSalesUser</div>\n";
        }
    }
    
    echo "<h2>🧪 Test SalesHistory Validation</h2>\n";
    
    require_once 'includes/SalesHistory.php';
    $salesHistory = new SalesHistory();
    
    $testData = [
        'CustomerCode' => 'DEBUG_ASSIGN_001',
        'SaleName' => $testSalesUser
    ];
    
    echo "<div class='status debug'>🔍 Testing validation with data: " . json_encode($testData) . "</div>\n";
    
    $validationErrors = $salesHistory->validateAssignmentData($testData);
    
    if (empty($validationErrors)) {
        echo "<div class='status pass'>✅ SalesHistory validation passed</div>\n";
    } else {
        echo "<div class='status fail'>❌ SalesHistory validation failed:</div>\n";
        foreach ($validationErrors as $error) {
            echo "<div class='status fail'>- $error</div>\n";
        }
    }
    
    // Clean up
    echo "<h2>🧹 Cleanup</h2>\n";
    $pdo->prepare("DELETE FROM users WHERE Username = ? AND CreatedBy = 'debug_test'")->execute([$testSalesUser]);
    echo "<div class='status pass'>✅ Test user cleaned up</div>\n";
    
} catch (Exception $e) {
    echo "<div class='status fail'>❌ Error: " . $e->getMessage() . "</div>\n";
    echo "<div class='status debug'>Stack trace: " . $e->getTraceAsString() . "</div>\n";
}

echo "</body></html>\n";
?>