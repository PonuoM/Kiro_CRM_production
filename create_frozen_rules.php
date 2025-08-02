<?php
/**
 * Create Proper FROZEN Rules
 * Define correct business logic for when customers should be FROZEN
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Create a proper FROZEN rules function
    $frozenRulesFunction = '<?php
/**
 * Proper FROZEN Business Rules
 * Define when customers should be marked as FROZEN
 */

function shouldCustomerBeFrozen($customer) {
    $rules = [
        // Rule 1: Customer explicitly requested no contact
        "no_contact_request" => false,  // This would come from call logs
        
        // Rule 2: Failed contact attempts (3+ times) AND low value
        "failed_contact_low_value" => (
            intval($customer["ContactAttempts"]) >= 3 &&
            floatval($customer["TotalPurchase"]) < 2000
        ),
        
        // Rule 3: Wrong/invalid phone number
        "invalid_phone" => false,  // This would come from call logs
        
        // Rule 4: Very old customer (>180 days) with no purchase AND no contact
        "very_old_no_activity" => (
            !empty($customer["LastContactDate"]) &&
            (time() - strtotime($customer["LastContactDate"])) > (180 * 24 * 60 * 60) &&
            floatval($customer["TotalPurchase"]) == 0
        )
    ];
    
    // High-value customers (A/B grade) should NEVER be frozen automatically
    $isHighValue = (
        in_array($customer["CustomerGrade"], ["A", "B"]) ||
        floatval($customer["TotalPurchase"]) >= 5000
    );
    
    if ($isHighValue) {
        return [
            "should_freeze" => false,
            "reason" => "High-value customer - manual review required",
            "rules_triggered" => array_keys(array_filter($rules))
        ];
    }
    
    // Check if any rule is triggered
    $triggeredRules = array_keys(array_filter($rules));
    
    return [
        "should_freeze" => !empty($triggeredRules),
        "reason" => !empty($triggeredRules) ? 
            "Rules triggered: " . implode(", ", $triggeredRules) : 
            "No freeze conditions met",
        "rules_triggered" => $triggeredRules
    ];
}

function applyProperFrozenRules($dryRun = true) {
    require_once "config/database.php";
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get all active customers
    $sql = "
        SELECT 
            CustomerCode,
            CustomerName,
            CustomerGrade,
            CustomerTemperature,
            COALESCE(TotalPurchase, 0) as TotalPurchase,
            COALESCE(ContactAttempts, 0) as ContactAttempts,
            LastContactDate,
            CustomerStatus
        FROM customers 
        WHERE CustomerStatus != \"ลูกค้าใหม่\"
        AND CustomerTemperature != \"FROZEN\"
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [
        "total_checked" => count($customers),
        "should_freeze" => 0,
        "high_value_protected" => 0,
        "details" => []
    ];
    
    foreach ($customers as $customer) {
        $analysis = shouldCustomerBeFrozen($customer);
        
        if ($analysis["should_freeze"]) {
            $results["should_freeze"]++;
            
            if (!$dryRun) {
                // Apply the freeze
                $updateSql = "
                    UPDATE customers 
                    SET CustomerTemperature = \"FROZEN\",
                        ModifiedDate = NOW(),
                        ModifiedBy = \"proper_frozen_rules\"
                    WHERE CustomerCode = ?
                ";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$customer["CustomerCode"]]);
            }
        } else if (strpos($analysis["reason"], "High-value") !== false) {
            $results["high_value_protected"]++;
        }
        
        if ($analysis["should_freeze"] || !empty($analysis["rules_triggered"])) {
            $results["details"][] = [
                "customer_code" => $customer["CustomerCode"],
                "customer_name" => $customer["CustomerName"],
                "grade" => $customer["CustomerGrade"],
                "total_purchase" => $customer["TotalPurchase"],
                "should_freeze" => $analysis["should_freeze"],
                "reason" => $analysis["reason"],
                "rules_triggered" => $analysis["rules_triggered"]
            ];
        }
    }
    
    return $results;
}
?>';
    
    // Save the function
    file_put_contents('/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/ProperFrozenRules.php', $frozenRulesFunction);
    
    // Create a database table for FROZEN reasons if it doesn\'t exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS frozen_reasons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_code VARCHAR(50) NOT NULL,
            reason_type ENUM('no_contact_request', 'failed_contact_low_value', 'invalid_phone', 'very_old_no_activity', 'manual') NOT NULL,
            reason_details TEXT,
            created_by VARCHAR(100),
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer_code (customer_code),
            INDEX idx_reason_type (reason_type)
        ) ENGINE=InnoDB COMMENT='Track reasons why customers are marked as FROZEN'
    ";
    
    $pdo->exec($createTableSql);
    
    // Test the new rules (dry run)
    include '/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/ProperFrozenRules.php';
    $testResults = applyProperFrozenRules(true);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Proper FROZEN rules created successfully',
        'rules_created' => [
            'no_contact_request' => 'Customer explicitly requested no contact',
            'failed_contact_low_value' => 'Failed contact attempts (3+) AND low value (<฿2K)',
            'invalid_phone' => 'Wrong/invalid phone number',
            'very_old_no_activity' => 'Very old customer (>180 days) with no purchase AND no contact',
            'high_value_protection' => 'Grade A/B customers protected from auto-freeze'
        ],
        'files_created' => [
            'includes/ProperFrozenRules.php' => 'Business logic for FROZEN rules',
            'frozen_reasons table' => 'Database table to track FROZEN reasons'
        ],
        'test_results' => $testResults,
        'next_steps' => [
            'Replace current auto-freeze logic with proper rules',
            'Add manual FROZEN reason tracking',
            'Implement call log integration for better decisions'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>