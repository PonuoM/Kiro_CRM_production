<?php
/**
 * Test Migration Setup Script
 * Creates a test environment for testing migration v2.0
 * Story 1.1: Alter Database Schema
 */

require_once __DIR__ . '/../config/database.php';

echo "<h1>🧪 Test Migration Setup</h1>\n";
echo "<p>Setting up test environment for migration v2.0...</p>\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // 1. Check current database
    $currentDB = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<div style='background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
    echo "📍 <strong>Current Database:</strong> $currentDB\n";
    echo "</div>\n";
    
    // 2. Check if we have the original schema without migration
    echo "<h2>🔍 Checking Current Schema State</h2>\n";
    
    // Check for migration-specific columns
    $migrationColumns = [
        'customers.ContactAttempts' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'ContactAttempts'",
        'customers.AssignmentCount' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'AssignmentCount'",
        'users.supervisor_id' => "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'supervisor_id'"
    ];
    
    $needsMigration = false;
    
    foreach ($migrationColumns as $column => $checkQuery) {
        $exists = $pdo->query($checkQuery)->fetchColumn();
        
        if ($exists) {
            echo "<div style='background: #fff3cd; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "⚠️ <strong>$column:</strong> Already exists (possibly migrated)\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "❌ <strong>$column:</strong> Missing (needs migration)\n";
            echo "</div>\n";
            $needsMigration = true;
        }
    }
    
    // Check CustomerTemperature ENUM
    $tempEnumQuery = "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'CustomerTemperature'";
    $tempEnum = $pdo->query($tempEnumQuery)->fetchColumn();
    
    if ($tempEnum) {
        $hasFrozen = strpos($tempEnum, 'FROZEN') !== false;
        if ($hasFrozen) {
            echo "<div style='background: #fff3cd; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "⚠️ <strong>CustomerTemperature ENUM:</strong> Already includes FROZEN\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
            echo "❌ <strong>CustomerTemperature ENUM:</strong> Missing FROZEN (needs migration)\n";
            echo "</div>\n";
            $needsMigration = true;
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 5px; margin: 2px 0; border-radius: 3px;'>\n";
        echo "❌ <strong>CustomerTemperature column:</strong> Missing entirely\n";
        echo "</div>\n";
        $needsMigration = true;
    }
    
    // 3. Migration decision
    if ($needsMigration) {
        echo "<h2>🚀 Ready to Test Migration</h2>\n";
        echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
        echo "✅ <strong>Test Environment Ready:</strong><br>\n";
        echo "• Database has missing migration columns<br>\n";
        echo "• Migration v2.0 can be tested<br>\n";
        echo "• Test conditions are ideal<br>\n";
        echo "</div>\n";
        
        echo "<h3>📋 Test Execution Steps</h3>\n";
        echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px;'>\n";
        echo "<strong>1. Run Migration Script:</strong><br>\n";
        echo "Execute: <code>migration_v2.0.sql</code><br><br>\n";
        echo "<strong>2. Run Test Suite:</strong><br>\n";
        echo "Execute: <code>tests/database/migration_test.php</code><br><br>\n";
        echo "<strong>3. Verify Results:</strong><br>\n";
        echo "Check all AC requirements are met<br>\n";
        echo "</div>\n";
        
    } else {
        echo "<h2>🔄 Migration Already Applied</h2>\n";
        echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
        echo "⚠️ <strong>Migration appears to be already applied:</strong><br>\n";
        echo "• All migration columns exist<br>\n";
        echo "• CustomerTemperature includes FROZEN<br>\n";
        echo "• Can still run test suite to verify<br>\n";
        echo "</div>\n";
        
        echo "<h3>📋 Verification Steps</h3>\n";
        echo "<div style='background: #e2e3e5; padding: 10px; border-radius: 5px;'>\n";
        echo "<strong>1. Run Test Suite:</strong><br>\n";
        echo "Execute: <code>tests/database/migration_test.php</code><br><br>\n";
        echo "<strong>2. Verify All ACs:</strong><br>\n";
        echo "Ensure all Acceptance Criteria are met<br>\n";
        echo "</div>\n";
    }
    
    // 4. Show current table structures
    echo "<h2>📊 Current Table Structures</h2>\n";
    
    // Show customers table structure
    echo "<h3>🏪 Customers Table</h3>\n";
    $customersStmt = $pdo->query("DESCRIBE customers");
    $customersColumns = $customersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Comment</th></tr>\n";
    
    foreach ($customersColumns as $column) {
        $bgColor = '#fff';
        if (in_array($column['Field'], ['ContactAttempts', 'AssignmentCount', 'CustomerTemperature'])) {
            $bgColor = '#e8f5e8'; // Highlight migration-related columns
        }
        
        echo "<tr style='background: $bgColor;'>\n";
        echo "<td><strong>{$column['Field']}</strong></td>\n";
        echo "<td>{$column['Type']}</td>\n";
        echo "<td>{$column['Null']}</td>\n";
        echo "<td>{$column['Default']}</td>\n";
        echo "<td>{$column['Extra']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Show users table structure
    echo "<h3>👥 Users Table</h3>\n";
    $usersStmt = $pdo->query("DESCRIBE users");
    $usersColumns = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
    echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Comment</th></tr>\n";
    
    foreach ($usersColumns as $column) {
        $bgColor = '#fff';
        if ($column['Field'] === 'supervisor_id') {
            $bgColor = '#e8f5e8'; // Highlight migration-related columns
        }
        
        echo "<tr style='background: $bgColor;'>\n";
        echo "<td><strong>{$column['Field']}</strong></td>\n";
        echo "<td>{$column['Type']}</td>\n";
        echo "<td>{$column['Null']}</td>\n";
        echo "<td>{$column['Default']}</td>\n";
        echo "<td>{$column['Extra']}</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // 5. Sample data check
    echo "<h2>📈 Sample Data Check</h2>\n";
    
    try {
        $customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        echo "<div style='background: #d1ecf1; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
        echo "📊 <strong>Current Data:</strong><br>\n";
        echo "• Customers: $customerCount records<br>\n";
        echo "• Users: $userCount records<br>\n";
        echo "</div>\n";
        
        if ($customerCount > 0) {
            echo "<h3>📋 Sample Customer Records</h3>\n";
            $sampleStmt = $pdo->query("SELECT CustomerCode, CustomerName, CustomerStatus, CartStatus FROM customers LIMIT 3");
            $sampleCustomers = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
            echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Status</th><th>Cart Status</th></tr>\n";
            
            foreach ($sampleCustomers as $customer) {
                echo "<tr>\n";
                echo "<td>{$customer['CustomerCode']}</td>\n";
                echo "<td>{$customer['CustomerName']}</td>\n";
                echo "<td>{$customer['CustomerStatus']}</td>\n";
                echo "<td>{$customer['CartStatus']}</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>\n";
        echo "❌ <strong>Error checking sample data:</strong> " . $e->getMessage() . "\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>\n";
    echo "❌ <strong>Setup Error:</strong> " . $e->getMessage() . "\n";
    echo "</div>\n";
}

echo "<h3>🔗 Quick Actions</h3>\n";
echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px;'>\n";
echo "<strong>Ready to proceed with:</strong><br>\n";
echo "• <a href='migration_test.php'>🧪 Run Migration Test Suite</a><br>\n";
echo "• Manual SQL execution of migration_v2.0.sql<br>\n";
echo "• Production deployment preparation<br>\n";
echo "</div>\n";
?>