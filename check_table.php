<?php
/**
 * Check Table Structure
 * ตรวจสอบโครงสร้างตารางและ columns ที่มีอยู่
 */

// Security check
if (php_sapi_name() !== 'cli' && (!isset($_GET['admin_key']) || $_GET['admin_key'] !== 'kiro_check_2024')) {
    http_response_code(403);
    die("Access Denied: This script requires admin authorization.");
}

// Include database config
require_once __DIR__ . '/config/database.php';

// HTML output for web interface
$isWebMode = php_sapi_name() !== 'cli';
if ($isWebMode) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Table Structure Check</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;margin:10px 0;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.missing{color:red;font-weight:bold;}.exists{color:green;}</style>";
    echo "</head><body>";
}

function outputMessage($message, $type = 'info') {
    global $isWebMode;
    
    if ($isWebMode) {
        $color = $type === 'error' ? 'red' : ($type === 'success' ? 'green' : 'black');
        echo "<p style='color:$color;'>$message</p>\n";
        flush();
    } else {
        echo "[" . strtoupper($type) . "] $message\n";
    }
}

function outputTable($headers, $data, $title = '') {
    global $isWebMode;
    
    if ($title) {
        outputMessage("<strong>$title</strong>");
    }
    
    if ($isWebMode) {
        echo "<table>\n<tr>";
        foreach ($headers as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>\n";
        
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $cell) {
                echo "<td>$cell</td>";
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
        flush();
    } else {
        // CLI output
        $format = "| %-20s | %-15s | %-20s | %-10s | %-50s |\n";
        echo str_repeat('-', 120) . "\n";
        printf($format, ...$headers);
        echo str_repeat('-', 120) . "\n";
        
        foreach ($data as $row) {
            printf($format, ...array_map(function($cell) {
                return substr($cell, 0, 20);
            }, $row));
        }
        echo str_repeat('-', 120) . "\n";
    }
}

try {
    outputMessage("🔍 Table Structure Check Started", 'info');
    outputMessage("Time: " . date('Y-m-d H:i:s'), 'info');
    
    // Initialize database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Tables to check
    $tablesToCheck = ['customers', 'orders', 'call_logs', 'order_items'];
    
    // Required columns for Customer Intelligence
    $requiredColumns = [
        'customers' => [
            'CustomerCode', 'CustomerName', 'CustomerStatus', 'CustomerTel',
            'Grade', 'CustomerGrade', // Check both possible names
            'CustomerTemperature', 'Temperature', // Check both possible names  
            'TotalPurchase', 'AssignmentCount', 'ContactAttempts',
            'GradeUpdated', 'GradeCalculatedDate', // Check both possible names
            'TemperatureUpdated', 'Sales'
        ],
        'orders' => [
            'CustomerCode', 'OrderCode', 'DocumentNo', 'DocumentDate', 
            'Price', 'TotalAmount', 'SubtotalAmount', 'Subtotal_amount2',
            'OrderStatus'
        ],
        'call_logs' => [
            'CustomerCode', 'CallDate', 'TalkStatus', 'CallResult', 
            'CallDuration'
        ],
        'order_items' => [
            'CustomerCode', 'OrderCode', 'ProductName', 'Quantity',
            'UnitPrice', 'SubtotalAmount'
        ]
    ];
    
    foreach ($tablesToCheck as $tableName) {
        outputMessage("📋 Checking table: $tableName", 'info');
        
        try {
            // Check if table exists
            $checkTableSql = "SHOW TABLES LIKE ?";
            $stmt = $pdo->prepare($checkTableSql);
            $stmt->execute([$tableName]);
            
            if ($stmt->rowCount() === 0) {
                outputMessage("❌ Table '$tableName' does NOT exist", 'error');
                continue;
            }
            
            // Get table structure
            $describeSql = "DESCRIBE $tableName";
            $stmt = $pdo->query($describeSql);
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            outputMessage("✅ Table '$tableName' exists with " . count($columns) . " columns", 'success');
            
            // Display table structure
            $headers = ['Field', 'Type', 'Null', 'Key', 'Default'];
            $tableData = [];
            foreach ($columns as $column) {
                $tableData[] = [
                    $column['Field'],
                    $column['Type'],
                    $column['Null'],
                    $column['Key'],
                    $column['Default'] ?? 'NULL'
                ];
            }
            outputTable($headers, $tableData, "Structure of table '$tableName'");
            
            // Check required columns
            if (isset($requiredColumns[$tableName])) {
                outputMessage("🔍 Checking required columns for '$tableName':", 'info');
                
                $existingColumns = array_column($columns, 'Field');
                $missing = [];
                $found = [];
                
                foreach ($requiredColumns[$tableName] as $reqColumn) {
                    if (in_array($reqColumn, $existingColumns)) {
                        $found[] = $reqColumn;
                    } else {
                        $missing[] = $reqColumn;
                    }
                }
                
                if (!empty($found)) {
                    if ($isWebMode) {
                        echo "<p><span class='exists'>✅ Found columns:</span> " . implode(', ', $found) . "</p>";
                    } else {
                        outputMessage("✅ Found columns: " . implode(', ', $found), 'success');
                    }
                }
                
                if (!empty($missing)) {
                    if ($isWebMode) {
                        echo "<p><span class='missing'>❌ Missing columns:</span> " . implode(', ', $missing) . "</p>";
                    } else {
                        outputMessage("❌ Missing columns: " . implode(', ', $missing), 'error');
                    }
                }
            }
            
        } catch (Exception $e) {
            outputMessage("❌ Error checking table '$tableName': " . $e->getMessage(), 'error');
        }
        
        outputMessage("", 'info'); // Empty line
    }
    
    // Summary
    outputMessage("📊 Summary & Recommendations", 'info');
    
    // Check for common column name variations
    $customersSql = "DESCRIBE customers";
    $stmt = $pdo->query($customersSql);
    $customerColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    outputMessage("Customer table analysis:", 'info');
    
    // Grade column check
    $gradeColumn = null;
    if (in_array('Grade', $customerColumns)) {
        $gradeColumn = 'Grade';
    } elseif (in_array('CustomerGrade', $customerColumns)) {
        $gradeColumn = 'CustomerGrade';
    }
    
    if ($gradeColumn) {
        outputMessage("✅ Grade column found: '$gradeColumn'", 'success');
    } else {
        outputMessage("❌ No Grade column found (checked: Grade, CustomerGrade)", 'error');
    }
    
    // Temperature column check
    $tempColumn = null;
    if (in_array('CustomerTemperature', $customerColumns)) {
        $tempColumn = 'CustomerTemperature';
    } elseif (in_array('Temperature', $customerColumns)) {
        $tempColumn = 'Temperature';
    }
    
    if ($tempColumn) {
        outputMessage("✅ Temperature column found: '$tempColumn'", 'success');
    } else {
        outputMessage("❌ No Temperature column found (checked: CustomerTemperature, Temperature)", 'error');
    }
    
    // Recommendations
    outputMessage("🔧 Recommendations:", 'info');
    
    if (!$gradeColumn && !$tempColumn) {
        outputMessage("1. Run database migration to add missing columns", 'error');
        outputMessage("2. Check database/migration_v2.0.sql", 'error');
    } elseif (!$gradeColumn || !$tempColumn) {
        outputMessage("1. Add missing column(s) to customers table", 'error');
    } else {
        outputMessage("1. Update customer intelligence script to use correct column names", 'success');
        outputMessage("2. Ready to run fix_customer_intelligence_complete.php", 'success');
    }
    
    outputMessage("🎉 Table Structure Check Completed", 'success');
    
} catch (Exception $e) {
    outputMessage("💥 FATAL ERROR: " . $e->getMessage(), 'error');
    exit(1);
}

if ($isWebMode) {
    echo "</body></html>";
}

?>