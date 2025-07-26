<?php
/**
 * Manual Intelligence System Setup
 * Run this script directly if web setup fails
 */

require_once '../config/database.php';

header('Content-Type: text/plain');

echo "=== Customer Intelligence System Setup ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "✅ Database connection successful\n\n";
    
    $executedStatements = 0;
    $failedStatements = 0;
    $errors = [];
    
    // Step 1: Add Intelligence columns
    echo "Step 1: Adding Intelligence columns...\n";
    
    $intelligenceColumns = [
        'CustomerGrade' => "ENUM('A', 'B', 'C', 'D') NULL COMMENT 'Customer Grade based on purchase amount'",
        'TotalPurchase' => "DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Total purchase amount for grading'",
        'LastPurchaseDate' => "DATE NULL COMMENT 'Last purchase date'",
        'GradeCalculatedDate' => "DATETIME NULL COMMENT 'When grade was last calculated'",
        'CustomerTemperature' => "ENUM('HOT', 'WARM', 'COLD') DEFAULT 'WARM' COMMENT 'Customer interaction temperature'",
        'LastContactDate' => "DATE NULL COMMENT 'Last contact date for temperature calculation'",
        'ContactAttempts' => "INT DEFAULT 0 COMMENT 'Number of contact attempts'",
        'TemperatureUpdatedDate' => "DATETIME NULL COMMENT 'When temperature was last updated'"
    ];
    
    foreach ($intelligenceColumns as $columnName => $columnDefinition) {
        echo "  - Checking column: {$columnName}... ";
        
        try {
            // Check if column exists
            $checkSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'customers' 
                       AND COLUMN_NAME = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$columnName]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] == 0) {
                // Column doesn't exist, add it
                $addSql = "ALTER TABLE customers ADD COLUMN {$columnName} {$columnDefinition}";
                $pdo->exec($addSql);
                echo "ADDED\n";
                $executedStatements++;
            } else {
                echo "EXISTS\n";
                $executedStatements++;
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $failedStatements++;
            $errors[] = "Column {$columnName}: " . $e->getMessage();
        }
    }
    
    // Step 2: Add indexes
    echo "\nStep 2: Adding indexes...\n";
    
    $indexes = [
        'idx_customer_grade' => 'CustomerGrade',
        'idx_customer_temperature' => 'CustomerTemperature',
        'idx_total_purchase' => 'TotalPurchase',
        'idx_last_contact' => 'LastContactDate'
    ];
    
    foreach ($indexes as $indexName => $columnName) {
        echo "  - Checking index: {$indexName}... ";
        
        try {
            // Check if index exists
            $checkIndexSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = 'customers' 
                            AND INDEX_NAME = ?";
            $checkIndexStmt = $pdo->prepare($checkIndexSql);
            $checkIndexStmt->execute([$indexName]);
            $indexResult = $checkIndexStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($indexResult['count'] == 0) {
                // Index doesn't exist, create it
                $createIndexSql = "CREATE INDEX {$indexName} ON customers({$columnName})";
                $pdo->exec($createIndexSql);
                echo "CREATED\n";
                $executedStatements++;
            } else {
                echo "EXISTS\n";
                $executedStatements++;
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $failedStatements++;
            $errors[] = "Index {$indexName}: " . $e->getMessage();
        }
    }
    
    // Step 3: Initialize default values
    echo "\nStep 3: Initializing default values...\n";
    
    try {
        $updateSql = "UPDATE customers 
                      SET 
                          CustomerGrade = COALESCE(CustomerGrade, 'D'),
                          TotalPurchase = COALESCE(TotalPurchase, 0.00),
                          CustomerTemperature = COALESCE(CustomerTemperature, 'WARM'),
                          ContactAttempts = COALESCE(ContactAttempts, 0),
                          GradeCalculatedDate = COALESCE(GradeCalculatedDate, NOW()),
                          TemperatureUpdatedDate = COALESCE(TemperatureUpdatedDate, NOW())
                      WHERE CustomerGrade IS NULL OR CustomerTemperature IS NULL";
        
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute();
        $affectedRows = $stmt->rowCount();
        
        echo "  - Updated {$affectedRows} customer records with default values\n";
        $executedStatements++;
    } catch (Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
        $failedStatements++;
        $errors[] = "Default values: " . $e->getMessage();
    }
    
    // Step 4: Create summary view
    echo "\nStep 4: Creating summary view...\n";
    
    try {
        $viewSql = "CREATE OR REPLACE VIEW customer_intelligence_summary AS
                    SELECT 
                        COALESCE(CustomerGrade, 'D') as CustomerGrade,
                        COALESCE(CustomerTemperature, 'WARM') as CustomerTemperature,
                        COUNT(*) as customer_count,
                        AVG(COALESCE(TotalPurchase, 0)) as avg_purchase,
                        SUM(COALESCE(TotalPurchase, 0)) as total_revenue
                    FROM customers 
                    GROUP BY COALESCE(CustomerGrade, 'D'), COALESCE(CustomerTemperature, 'WARM')
                    ORDER BY CustomerGrade, CustomerTemperature";
        
        $pdo->exec($viewSql);
        echo "  - Summary view created successfully\n";
        $executedStatements++;
    } catch (Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
        $failedStatements++;
        $errors[] = "Summary view: " . $e->getMessage();
    }
    
    // Final verification
    echo "\nStep 5: Verification...\n";
    
    try {
        $verifyColumns = $pdo->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                                       WHERE TABLE_SCHEMA = DATABASE() 
                                       AND TABLE_NAME = 'customers' 
                                       AND COLUMN_NAME IN ('CustomerGrade', 'CustomerTemperature', 'TotalPurchase')");
        $verifyColumns->execute();
        $columnCount = $verifyColumns->fetch(PDO::FETCH_ASSOC);
        
        $verifyView = $pdo->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.VIEWS 
                                   WHERE TABLE_SCHEMA = DATABASE() 
                                   AND TABLE_NAME = 'customer_intelligence_summary'");
        $verifyView->execute();
        $viewCount = $verifyView->fetch(PDO::FETCH_ASSOC);
        
        echo "  - Intelligence columns found: " . $columnCount['count'] . "/8\n";
        echo "  - Summary view exists: " . ($viewCount['count'] > 0 ? 'YES' : 'NO') . "\n";
        
        // Test the system
        $testQuery = $pdo->prepare("SELECT 
                                   COUNT(*) as total_customers,
                                   COUNT(DISTINCT CustomerGrade) as grade_types,
                                   COUNT(DISTINCT CustomerTemperature) as temp_types
                                   FROM customers");
        $testQuery->execute();
        $testResult = $testQuery->fetch(PDO::FETCH_ASSOC);
        
        echo "  - Total customers: " . $testResult['total_customers'] . "\n";
        echo "  - Grade types: " . $testResult['grade_types'] . "\n";
        echo "  - Temperature types: " . $testResult['temp_types'] . "\n";
        
    } catch (Exception $e) {
        echo "  - Verification failed: " . $e->getMessage() . "\n";
        $errors[] = "Verification: " . $e->getMessage();
    }
    
    // Summary
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "SETUP SUMMARY\n";
    echo str_repeat("=", 50) . "\n";
    echo "✅ Executed statements: {$executedStatements}\n";
    echo "❌ Failed statements: {$failedStatements}\n";
    
    if (!empty($errors)) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }
    
    if ($failedStatements == 0) {
        echo "\n🎉 Intelligence System setup completed successfully!\n";
        echo "You can now use the Customer Intelligence features.\n";
    } else {
        echo "\n⚠️ Setup completed with errors. Some features may not work correctly.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "\n";
    echo "Setup failed. Please check your database configuration.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Setup completed at: " . date('Y-m-d H:i:s') . "\n";
?>