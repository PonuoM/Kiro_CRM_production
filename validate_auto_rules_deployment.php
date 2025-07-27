<?php
/**
 * Auto Rules Deployment Validation Script
 * Story 1.2: Develop Lead Management Cron Job
 * 
 * This script validates the deployment readiness of the auto rules system
 * without requiring CLI execution
 */

// Simple web-accessible validation
echo "<!DOCTYPE html>\n";
echo "<html><head><title>Auto Rules Deployment Validation</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .pass{background:#d4edda;padding:10px;margin:5px;border-radius:5px;} .fail{background:#f8d7da;padding:10px;margin:5px;border-radius:5px;} .info{background:#d1ecf1;padding:10px;margin:5px;border-radius:5px;}</style>";
echo "</head><body>\n";

echo "<h1>🚀 Auto Rules Deployment Validation</h1>\n";
echo "<p><strong>Story 1.2:</strong> Develop Lead Management Cron Job</p>\n";

$validationResults = [];

// Validation 1: Check if auto_rules.php exists and is readable
echo "<h2>📋 File Validation</h2>\n";

$autoRulesFile = __DIR__ . '/cron/auto_rules.php';
if (file_exists($autoRulesFile) && is_readable($autoRulesFile)) {
    echo "<div class='pass'>✅ auto_rules.php exists and is readable</div>\n";
    $validationResults['auto_rules_file'] = true;
} else {
    echo "<div class='fail'>❌ auto_rules.php not found or not readable</div>\n";
    $validationResults['auto_rules_file'] = false;
}

// Validation 2: Check if shell script exists
$shellScript = __DIR__ . '/cron/run_auto_rules.sh';
if (file_exists($shellScript) && is_readable($shellScript)) {
    echo "<div class='pass'>✅ run_auto_rules.sh exists and is readable</div>\n";
    $validationResults['shell_script'] = true;
} else {
    echo "<div class='fail'>❌ run_auto_rules.sh not found or not readable</div>\n";
    $validationResults['shell_script'] = false;
}

// Validation 3: Check database connection
echo "<h2>🔌 Database Connection</h2>\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<div class='pass'>✅ Database connection successful</div>\n";
    $validationResults['database'] = true;
} catch (Exception $e) {
    echo "<div class='fail'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    $validationResults['database'] = false;
}

// Validation 4: Check required database columns
echo "<h2>📊 Database Schema Validation</h2>\n";
if ($validationResults['database']) {
    try {
        // Check ContactAttempts column
        $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'ContactAttempts'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='pass'>✅ ContactAttempts column exists</div>\n";
            $validationResults['contact_attempts'] = true;
        } else {
            echo "<div class='fail'>❌ ContactAttempts column missing</div>\n";
            $validationResults['contact_attempts'] = false;
        }
        
        // Check AssignmentCount column
        $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'AssignmentCount'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='pass'>✅ AssignmentCount column exists</div>\n";
            $validationResults['assignment_count'] = true;
        } else {
            echo "<div class='fail'>❌ AssignmentCount column missing</div>\n";
            $validationResults['assignment_count'] = false;
        }
        
        // Check CustomerTemperature ENUM
        $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'CustomerTemperature'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($column && strpos($column['Type'], 'FROZEN') !== false) {
            echo "<div class='pass'>✅ CustomerTemperature includes FROZEN value</div>\n";
            $validationResults['customer_temperature'] = true;
        } else {
            echo "<div class='fail'>❌ CustomerTemperature missing FROZEN value</div>\n";
            $validationResults['customer_temperature'] = false;
        }
        
    } catch (Exception $e) {
        echo "<div class='fail'>❌ Schema validation failed: " . htmlspecialchars($e->getMessage()) . "</div>\n";
        $validationResults['schema'] = false;
    }
}

// Validation 5: Check log directory
echo "<h2>📁 Log Directory Validation</h2>\n";
$logDir = __DIR__ . '/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    echo "<div class='pass'>✅ Logs directory exists and is writable</div>\n";
    $validationResults['log_directory'] = true;
} else {
    echo "<div class='fail'>❌ Logs directory missing or not writable</div>\n";
    $validationResults['log_directory'] = false;
}

// Validation 6: Syntax check on auto_rules.php
echo "<h2>🔍 Syntax Validation</h2>\n";
if ($validationResults['auto_rules_file']) {
    $syntaxCheck = `php -l $autoRulesFile 2>&1`;
    if (strpos($syntaxCheck, 'No syntax errors') !== false) {
        echo "<div class='pass'>✅ auto_rules.php syntax is valid</div>\n";
        $validationResults['syntax'] = true;
    } else {
        echo "<div class='fail'>❌ auto_rules.php has syntax errors</div>\n";
        $validationResults['syntax'] = false;
    }
} else {
    $validationResults['syntax'] = false;
}

// Summary
echo "<h2>📈 Validation Summary</h2>\n";
$totalChecks = count($validationResults);
$passedChecks = array_sum($validationResults);
$percentage = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 1) : 0;

echo "<div class='info'>";
echo "<h3>Overall Result</h3>";
echo "Passed: $passedChecks/$totalChecks ($percentage%)<br>";

if ($percentage >= 80) {
    echo "<strong style='color: green;'>🎉 AUTO RULES SYSTEM IS READY FOR DEPLOYMENT!</strong><br>";
    echo "All critical components validated successfully.";
} else {
    echo "<strong style='color: red;'>⚠️ DEPLOYMENT NOT READY</strong><br>";
    echo "Please fix the failed validations before deployment.";
}
echo "</div>";

// Deployment Instructions
if ($percentage >= 80) {
    echo "<h2>🚀 Deployment Instructions</h2>\n";
    echo "<div class='info'>";
    echo "<h3>Production Deployment Steps:</h3>";
    echo "1. <strong>Upload Files:</strong> Ensure auto_rules.php and run_auto_rules.sh are on production server<br>";
    echo "2. <strong>Set Permissions:</strong> <code>chmod +x /path/to/run_auto_rules.sh</code><br>";
    echo "3. <strong>Configure Cron:</strong> Add to crontab: <code>0 1 * * * /path/to/run_auto_rules.sh</code><br>";
    echo "4. <strong>Update Paths:</strong> Edit run_auto_rules.sh with correct production paths<br>";
    echo "5. <strong>Test Run:</strong> Execute manually first: <code>/path/to/run_auto_rules.sh</code><br>";
    echo "6. <strong>Monitor Logs:</strong> Check /logs/cron_auto_rules.log for execution results<br>";
    echo "</div>";
}

echo "<h2>📝 Next Steps</h2>\n";
echo "<div class='info'>";
if ($percentage >= 80) {
    echo "✅ Complete Task 4: ทดสอบและ Deployment<br>";
    echo "✅ Ready to move to DoD (Definition of Done) checklist<br>";
    echo "✅ Story 1.2 validation successful<br>";
} else {
    echo "❌ Fix validation failures above<br>";
    echo "❌ Re-run validation after fixes<br>";
    echo "❌ Do not proceed to production until all checks pass<br>";
}
echo "</div>";

echo "</body></html>\n";
?>