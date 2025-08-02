<?php
/**
 * Complete Customer Intelligence Fix
 * Data Migration Script - แก้ไขระบบ Customer Intelligence
 * 
 * ตามข้อกำหนดใน requirements.md และ design.md
 * - แก้ไข Grade calculation (ใช้เกณฑ์ใหม่: A≥810K, B≥85K, C≥2K, D<2K)
 * - แก้ไข Temperature logic (รวมกฎพิเศษสำหรับ Grade A,B)
 * - ใช้ข้อมูลจาก orders.Price แทน TotalAmount
 * - สร้าง backup ก่อนแก้ไข
 */

// Security: Only CLI or authorized web access
if (php_sapi_name() !== 'cli' && !isset($_GET['admin_key']) || ($_GET['admin_key'] ?? '') !== 'kiro_intelligence_fix_2024') {
    http_response_code(403);
    die("Access Denied: This script requires admin authorization.");
}

// Set execution parameters
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 1800); // 30 minutes
set_time_limit(1800);

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/customer_intelligence.php';

// HTML output for web interface
$isWebMode = php_sapi_name() !== 'cli';
if ($isWebMode) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Customer Intelligence Fix</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;}h2{color:#333;}h3{color:#666;}.success{color:green;}.error{color:red;}.warning{color:orange;}.info{color:blue;}table{border-collapse:collapse;margin:10px 0;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>";
    echo "</head><body>";
}

/**
 * Output message with appropriate formatting
 */
function outputMessage($message, $type = 'info') {
    global $isWebMode;
    
    if ($isWebMode) {
        $class = $type;
        echo "<p class='$class'>$message</p>\n";
        flush();
    } else {
        $prefix = strtoupper($type);
        echo "[$prefix] $message\n";
    }
}

/**
 * Output table data
 */
function outputTable($headers, $data, $title = '') {
    global $isWebMode;
    
    if ($title) {
        outputMessage("<strong>$title</strong>", 'info');
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
        // CLI table output
        $line = '+' . str_repeat('-', 80) . "+\n";
        echo $line;
        foreach ($headers as $header) {
            printf("| %-20s", $header);
        }
        echo "|\n$line";
        
        foreach ($data as $row) {
            foreach ($row as $cell) {
                printf("| %-20s", substr($cell, 0, 20));
            }
            echo "|\n";
        }
        echo $line;
    }
}

try {
    outputMessage("🔧 Customer Intelligence Complete Fix Started", 'info');
    outputMessage("Time: " . date('Y-m-d H:i:s'), 'info');
    
    // Initialize database
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    $intelligence = new CustomerIntelligence($pdo);
    
    $stats = [
        'start_time' => microtime(true),
        'backup_created' => false,
        'customers_processed' => 0,
        'grade_changes' => 0,
        'temperature_changes' => 0,
        'errors' => 0
    ];
    
    // ============================================================================
    // STEP 1: CREATE BACKUP
    // ============================================================================
    outputMessage("📋 Step 1: Creating Backup Tables", 'info');
    
    try {
        // Create backup timestamp
        $backupSuffix = date('Ymd_His');
        
        // Backup customers table
        $backupSql = "CREATE TABLE customers_backup_{$backupSuffix} AS SELECT * FROM customers";
        $pdo->exec($backupSql);
        
        // Count backup records
        $countSql = "SELECT COUNT(*) as count FROM customers_backup_{$backupSuffix}";
        $countStmt = $pdo->query($countSql);
        $backupCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stats['backup_created'] = true;
        outputMessage("✅ Backup created: customers_backup_{$backupSuffix} ({$backupCount} records)", 'success');
        
    } catch (Exception $e) {
        outputMessage("❌ Backup creation failed: " . $e->getMessage(), 'error');
        throw new Exception("Cannot proceed without backup: " . $e->getMessage());
    }
    
    // ============================================================================
    // STEP 2: ANALYZE CURRENT DATA
    // ============================================================================
    outputMessage("📊 Step 2: Current Data Analysis", 'info');
    
    // Current Grade distribution
    $currentGradeSql = "SELECT 
                          COALESCE(CustomerGrade, 'NULL') as Grade,
                          COUNT(*) as count,
                          MIN(TotalPurchase) as min_purchase,
                          MAX(TotalPurchase) as max_purchase,
                          AVG(TotalPurchase) as avg_purchase
                        FROM customers 
                        GROUP BY CustomerGrade 
                        ORDER BY CustomerGrade";
    
    $stmt = $pdo->query($currentGradeSql);
    $currentGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $gradeHeaders = ['Grade', 'Count', 'Min Purchase', 'Max Purchase', 'Avg Purchase'];
    $gradeData = [];
    foreach ($currentGrades as $grade) {
        $gradeData[] = [
            $grade['Grade'],
            $grade['count'],
            '฿' . number_format($grade['min_purchase'], 2),
            '฿' . number_format($grade['max_purchase'], 2),
            '฿' . number_format($grade['avg_purchase'], 2)
        ];
    }
    outputTable($gradeHeaders, $gradeData, "Current Grade Distribution (BEFORE Fix)");
    
    // Current Temperature distribution
    $currentTempSql = "SELECT 
                         COALESCE(CustomerTemperature, 'NULL') as Temperature,
                         COUNT(*) as count
                       FROM customers 
                       GROUP BY CustomerTemperature 
                       ORDER BY CustomerTemperature";
    
    $stmt = $pdo->query($currentTempSql);
    $currentTemps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tempHeaders = ['Temperature', 'Count'];
    $tempData = [];
    foreach ($currentTemps as $temp) {
        $tempData[] = [$temp['Temperature'], $temp['count']];
    }
    outputTable($tempHeaders, $tempData, "Current Temperature Distribution (BEFORE Fix)");
    
    // ============================================================================
    // STEP 3: UPDATE TOTAL PURCHASE AMOUNTS
    // ============================================================================
    outputMessage("💰 Step 3: Updating Total Purchase Amounts", 'info');
    
    try {
        // Update TotalPurchase from orders.Price (correct field as per requirements)
        $updateTotalSql = "
            UPDATE customers c
            SET TotalPurchase = COALESCE((
                SELECT SUM(o.Price) 
                FROM orders o 
                WHERE o.CustomerCode = c.CustomerCode 
                AND o.Price IS NOT NULL
                AND o.Price > 0
            ), 0)
            WHERE c.CustomerCode IS NOT NULL
        ";
        
        $updateStmt = $pdo->prepare($updateTotalSql);
        $updateStmt->execute();
        $totalUpdated = $updateStmt->rowCount();
        
        outputMessage("✅ Updated TotalPurchase for {$totalUpdated} customers", 'success');
        
    } catch (Exception $e) {
        outputMessage("❌ TotalPurchase update failed: " . $e->getMessage(), 'error');
        throw $e;
    }
    
    // ============================================================================
    // STEP 4: UPDATE ALL CUSTOMER GRADES (NEW CRITERIA)
    // ============================================================================
    outputMessage("🏆 Step 4: Updating Customer Grades (New Criteria)", 'info');
    outputMessage("New Grade Criteria: A≥฿810,000 | B≥฿85,000 | C≥฿2,000 | D<฿2,000", 'info');
    
    try {
        // Update Grades with correct criteria from requirements.md
        // Force update by adding a dummy condition to ensure rowCount works
        $gradeUpdateSql = "
            UPDATE customers 
            SET 
                CustomerGrade = CASE 
                    WHEN TotalPurchase >= 810000 THEN 'A'    -- VIP Customer
                    WHEN TotalPurchase >= 85000 THEN 'B'     -- Premium Customer  
                    WHEN TotalPurchase >= 2000 THEN 'C'      -- Regular Customer
                    ELSE 'D'                                  -- New Customer
                END,
                TotalPurchase = TotalPurchase  -- Force update to get correct rowCount
            WHERE CustomerCode IS NOT NULL
        ";
        
        $gradeStmt = $pdo->prepare($gradeUpdateSql);
        $gradeStmt->execute();
        $gradeUpdated = $gradeStmt->rowCount();
        
        outputMessage("✅ Updated Grade for {$gradeUpdated} customers", 'success');
        
        // Count grade changes by comparing with backup
        $changesSql = "
            SELECT 
                b.CustomerGrade as old_grade,
                c.CustomerGrade as new_grade,
                COUNT(*) as count
            FROM customers c
            INNER JOIN customers_backup_{$backupSuffix} b ON c.CustomerCode = b.CustomerCode
            WHERE COALESCE(b.CustomerGrade, '') != COALESCE(c.CustomerGrade, '')
            GROUP BY b.CustomerGrade, c.CustomerGrade
            ORDER BY b.CustomerGrade, c.CustomerGrade
        ";
        
        $changesStmt = $pdo->query($changesSql);
        $gradeChanges = $changesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($gradeChanges)) {
            $changeHeaders = ['Old Grade', 'New Grade', 'Count'];
            $changeData = [];
            $totalGradeChanges = 0;
            foreach ($gradeChanges as $change) {
                $changeData[] = [
                    $change['old_grade'] ?: 'NULL',
                    $change['new_grade'] ?: 'NULL',
                    $change['count']
                ];
                $totalGradeChanges += $change['count'];
            }
            outputTable($changeHeaders, $changeData, "Grade Changes Summary");
            $stats['grade_changes'] = $totalGradeChanges;
        }
        
    } catch (Exception $e) {
        outputMessage("❌ Grade update failed: " . $e->getMessage(), 'error');
        throw $e;
    }
    
    // ============================================================================
    // STEP 5: UPDATE CUSTOMER TEMPERATURES (NEW LOGIC)
    // ============================================================================
    outputMessage("🌡️ Step 5: Updating Customer Temperatures (New Logic)", 'info');
    outputMessage("Special Rule: Grade A,B customers with high purchase (>฿50,000) will not be FROZEN", 'warning');
    
    try {
        // Step 5a: Fix Grade A,B customers that are FROZEN
        $unfreezeHighValueSql = "
            UPDATE customers 
            SET CustomerTemperature = 'WARM'
            WHERE CustomerGrade IN ('A', 'B') 
            AND CustomerTemperature = 'FROZEN'
            AND TotalPurchase > 50000
        ";
        
        $unfreezeStmt = $pdo->prepare($unfreezeHighValueSql);
        $unfreezeStmt->execute();
        $unfrozenCount = $unfreezeStmt->rowCount();
        
        if ($unfrozenCount > 0) {
            outputMessage("✅ Unfroze {$unfrozenCount} high-value customers (Grade A,B)", 'success');
        }
        
        // Step 5b: Get all customers for individual temperature calculation
        $customersSql = "SELECT CustomerCode FROM customers ORDER BY CustomerCode";
        $customersStmt = $pdo->query($customersSql);
        $allCustomers = $customersStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $processedCount = 0;
        $temperatureChanges = 0;
        
        foreach ($allCustomers as $customerCode) {
            try {
                // Get current temperature
                $currentTempSql = "SELECT CustomerTemperature FROM customers WHERE CustomerCode = ?";
                $currentTempStmt = $pdo->prepare($currentTempSql);
                $currentTempStmt->execute([$customerCode]);
                $currentTemp = $currentTempStmt->fetchColumn();
                
                // Calculate new temperature using our intelligence system
                $newTemperature = $intelligence->calculateCustomerTemperature($customerCode);
                
                // Update if different
                if ($currentTemp !== $newTemperature) {
                    $updateTempSql = "
                        UPDATE customers 
                        SET CustomerTemperature = ?
                        WHERE CustomerCode = ?
                    ";
                    $updateTempStmt = $pdo->prepare($updateTempSql);
                    $updateTempStmt->execute([$newTemperature, $customerCode]);
                    $temperatureChanges++;
                }
                
                $processedCount++;
                
                // Progress indicator
                if ($processedCount % 100 === 0) {
                    outputMessage("Processed {$processedCount} customers for temperature calculation...", 'info');
                }
                
            } catch (Exception $e) {
                $stats['errors']++;
                outputMessage("Error processing customer {$customerCode}: " . $e->getMessage(), 'error');
            }
        }
        
        $stats['customers_processed'] = $processedCount;
        $stats['temperature_changes'] = $temperatureChanges;
        
        outputMessage("✅ Processed {$processedCount} customers for temperature calculation", 'success');
        outputMessage("✅ Temperature changed for {$temperatureChanges} customers", 'success');
        
    } catch (Exception $e) {
        outputMessage("❌ Temperature update failed: " . $e->getMessage(), 'error');
        throw $e;
    }
    
    // ============================================================================
    // STEP 6: VERIFY SPECIFIC CASE (CUST003)
    // ============================================================================
    outputMessage("🔍 Step 6: Verifying Specific Cases", 'info');
    
    // Check CUST003 as mentioned in requirements
    $verifySql = "
        SELECT 
            CustomerCode, 
            CustomerName, 
            TotalPurchase, 
            CustomerGrade as Grade, 
            CustomerTemperature
        FROM customers 
        WHERE CustomerCode = 'CUST003' 
        OR TotalPurchase >= 800000
        ORDER BY TotalPurchase DESC
        LIMIT 10
    ";
    
    $verifyStmt = $pdo->query($verifySql);
    $verifyResults = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($verifyResults)) {
        $verifyHeaders = ['Customer Code', 'Name', 'Total Purchase', 'Grade', 'Temperature'];
        $verifyData = [];
        foreach ($verifyResults as $customer) {
            $verifyData[] = [
                $customer['CustomerCode'],
                substr($customer['CustomerName'], 0, 20),
                '฿' . number_format($customer['TotalPurchase'], 2),
                $customer['Grade'],
                $customer['CustomerTemperature']
            ];
        }
        outputTable($verifyHeaders, $verifyData, "High-Value Customers Verification");
        
        // Special check for CUST003
        foreach ($verifyResults as $customer) {
            if ($customer['CustomerCode'] === 'CUST003') {
                if ($customer['Grade'] === 'A') {
                    outputMessage("🎉 SUCCESS: CUST003 now has Grade A (฿" . number_format($customer['TotalPurchase'], 2) . ")", 'success');
                } else {
                    outputMessage("⚠️ ISSUE: CUST003 has Grade {$customer['Grade']} instead of A", 'warning');
                }
                break;
            }
        }
    }
    
    // ============================================================================
    // STEP 7: FINAL ANALYSIS
    // ============================================================================
    outputMessage("📈 Step 7: Final Analysis", 'info');
    
    // New Grade distribution
    $stmt = $pdo->query($currentGradeSql);
    $newGrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $newGradeData = [];
    foreach ($newGrades as $grade) {
        $newGradeData[] = [
            $grade['Grade'],
            $grade['count'],
            '฿' . number_format($grade['min_purchase'], 2),
            '฿' . number_format($grade['max_purchase'], 2),
            '฿' . number_format($grade['avg_purchase'], 2)
        ];
    }
    outputTable($gradeHeaders, $newGradeData, "New Grade Distribution (AFTER Fix)");
    
    // New Temperature distribution
    $stmt = $pdo->query($currentTempSql);
    $newTemps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $newTempData = [];
    foreach ($newTemps as $temp) {
        $newTempData[] = [$temp['Temperature'], $temp['count']];
    }
    outputTable($tempHeaders, $newTempData, "New Temperature Distribution (AFTER Fix)");
    
    // ============================================================================
    // STEP 8: EXECUTION SUMMARY
    // ============================================================================
    $stats['execution_time'] = round(microtime(true) - $stats['start_time'], 2);
    $stats['memory_usage'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    
    outputMessage("📋 Execution Summary", 'info');
    
    $summaryHeaders = ['Metric', 'Value'];
    $summaryData = [
        ['Execution Time', $stats['execution_time'] . ' seconds'],
        ['Memory Usage', $stats['memory_usage'] . ' MB'],
        ['Backup Created', $stats['backup_created'] ? 'Yes' : 'No'],
        ['Customers Processed', $stats['customers_processed']],
        ['Grade Changes', $stats['grade_changes']],
        ['Temperature Changes', $stats['temperature_changes']],
        ['Errors', $stats['errors']]
    ];
    outputTable($summaryHeaders, $summaryData, "Fix Statistics");
    
    // ============================================================================
    // STEP 9: ROLLBACK INSTRUCTIONS
    // ============================================================================
    outputMessage("🔄 Rollback Instructions", 'warning');
    outputMessage("If you need to rollback this fix, run the following SQL:", 'warning');
    
    $rollbackSql = "-- ROLLBACK CUSTOMER INTELLIGENCE FIX
UPDATE customers c 
SET CustomerGrade = (SELECT CustomerGrade FROM customers_backup_{$backupSuffix} b WHERE b.CustomerCode = c.CustomerCode),
    CustomerTemperature = (SELECT CustomerTemperature FROM customers_backup_{$backupSuffix} b WHERE b.CustomerCode = c.CustomerCode),
    TotalPurchase = (SELECT TotalPurchase FROM customers_backup_{$backupSuffix} b WHERE b.CustomerCode = c.CustomerCode);";
    
    if ($isWebMode) {
        echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ddd;'>" . htmlspecialchars($rollbackSql) . "</pre>";
    } else {
        echo "\n" . $rollbackSql . "\n";
    }
    
    outputMessage("🎉 Customer Intelligence Fix Completed Successfully!", 'success');
    outputMessage("Next Steps:", 'info');
    outputMessage("1. Test the customer intelligence system with real data", 'info');
    outputMessage("2. Verify that new orders automatically update grades", 'info');
    outputMessage("3. Check that call logs update temperatures correctly", 'info');
    outputMessage("4. Update the cron job to use the new logic", 'info');
    
} catch (Exception $e) {
    $stats['errors']++;
    outputMessage("💥 FATAL ERROR: " . $e->getMessage(), 'error');
    outputMessage("Fix process aborted. Check the error above and try again.", 'error');
    
    if ($isWebMode) {
        echo "<h3 style='color:red;'>Process Failed</h3>";
        echo "<p>The fix process encountered a fatal error and was stopped to prevent data corruption.</p>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    } else {
        echo "\n=== PROCESS FAILED ===\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    exit(1);
}

if ($isWebMode) {
    echo "</body></html>";
}

?>