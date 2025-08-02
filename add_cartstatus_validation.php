<?php
/**
 * Add CartStatus Validation to APIs
 * Add validation rules to prevent inconsistent data
 */

header('Content-Type: application/json');

try {
    $validationRules = [];
    
    // 1. Create CartStatus validation helper class
    $validationClass = '<?php
/**
 * CartStatus Validation Helper
 * Ensures CartStatus consistency across the application
 */

class CartStatusValidator {
    
    /**
     * Validate and fix CartStatus based on Sales field
     */
    public static function validateAndFix($customerData) {
        $hasSales = !empty($customerData["Sales"]);
        $currentStatus = $customerData["CartStatus"] ?? "";
        
        // Determine correct CartStatus
        if ($hasSales) {
            $correctStatus = "ลูกค้าแจกแล้ว";
        } else {
            // Default to waiting basket if no status set
            $correctStatus = $currentStatus ?: "ตะกร้ารอ";
            // But if marked as assigned without sales, move to distribution
            if ($currentStatus === "ลูกค้าแจกแล้ว") {
                $correctStatus = "ตะกร้าแจก";
            }
        }
        
        return [
            "is_valid" => ($currentStatus === $correctStatus),
            "current_status" => $currentStatus,
            "correct_status" => $correctStatus,
            "needs_update" => ($currentStatus !== $correctStatus)
        ];
    }
    
    /**
     * Get SQL for updating customer with correct CartStatus
     */
    public static function getUpdateSQL($customerCode, $salesUser = null) {
        if (!empty($salesUser)) {
            return [
                "sql" => "UPDATE customers SET Sales = ?, CartStatus = \'ลูกค้าแจกแล้ว\', ModifiedDate = NOW() WHERE CustomerCode = ?",
                "params" => [$salesUser, $customerCode]
            ];
        } else {
            return [
                "sql" => "UPDATE customers SET Sales = NULL, CartStatus = \'ตะกร้าแจก\', ModifiedDate = NOW() WHERE CustomerCode = ?", 
                "params" => [$customerCode]
            ];
        }
    }
    
    /**
     * Validate before assignment
     */
    public static function validateBeforeAssignment($customerCodes, $salesUser) {
        $errors = [];
        
        if (empty($customerCodes)) {
            $errors[] = "No customers selected";
        }
        
        if (empty($salesUser)) {
            $errors[] = "No sales user specified";
        }
        
        return [
            "is_valid" => empty($errors),
            "errors" => $errors
        ];
    }
}
?>';
    
    // Write validation class
    file_put_contents('/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/CartStatusValidator.php', $validationClass);
    $validationRules[] = "✅ Created CartStatusValidator helper class";
    
    // 2. Add validation to existing APIs (示例)
    $apiUpdates = [
        '/api/distribution/basket.php' => 'Add validation to assignment endpoints',
        '/api/customers/update.php' => 'Add validation to customer updates', 
        '/includes/Customer.php' => 'Add validation to Customer model'
    ];
    
    foreach ($apiUpdates as $file => $description) {
        if (file_exists('/mnt/c/xampp/htdocs/Kiro_CRM_production' . $file)) {
            $validationRules[] = "📝 Identified: $file - $description";
        }
    }
    
    // 3. Create auto-fix function
    $autoFixFunction = '<?php
/**
 * Auto-fix CartStatus inconsistencies
 * Run this function periodically to maintain data consistency
 */

require_once "config/database.php";
require_once "includes/CartStatusValidator.php";

function autoFixCartStatus($dryRun = false) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $fixes = [];
        
        // Find inconsistent records
        $stmt = $pdo->prepare("
            SELECT CustomerCode, CustomerName, Sales, CartStatus 
            FROM customers 
            WHERE 
                (Sales IS NOT NULL AND Sales != \'\' AND CartStatus != \'ลูกค้าแจกแล้ว\') OR
                ((Sales IS NULL OR Sales = \'\') AND CartStatus = \'ลูกค้าแจกแล้ว\')
        ");
        $stmt->execute();
        $inconsistent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$dryRun) {
            $pdo->beginTransaction();
        }
        
        foreach ($inconsistent as $customer) {
            $validation = CartStatusValidator::validateAndFix($customer);
            
            if ($validation["needs_update"]) {
                $fixes[] = [
                    "customer_code" => $customer["CustomerCode"],
                    "customer_name" => $customer["CustomerName"], 
                    "old_status" => $validation["current_status"],
                    "new_status" => $validation["correct_status"],
                    "has_sales" => !empty($customer["Sales"])
                ];
                
                if (!$dryRun) {
                    $updateSQL = CartStatusValidator::getUpdateSQL(
                        $customer["CustomerCode"], 
                        $customer["Sales"]
                    );
                    
                    $stmt = $pdo->prepare($updateSQL["sql"]);
                    $stmt->execute($updateSQL["params"]);
                }
            }
        }
        
        if (!$dryRun) {
            $pdo->commit();
        }
        
        return [
            "status" => "success",
            "fixed_count" => count($fixes),
            "fixes" => $fixes,
            "dry_run" => $dryRun
        ];
        
    } catch (Exception $e) {
        if (!$dryRun && isset($pdo)) {
            $pdo->rollBack();
        }
        
        return [
            "status" => "error", 
            "error" => $e->getMessage()
        ];
    }
}
?>';
    
    // Write auto-fix function
    file_put_contents('/mnt/c/xampp/htdocs/Kiro_CRM_production/includes/CartStatusAutoFix.php', $autoFixFunction);
    $validationRules[] = "✅ Created CartStatusAutoFix utility";
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Validation rules added successfully',
        'details' => $validationRules,
        'files_created' => [
            'includes/CartStatusValidator.php' => 'Validation helper class',
            'includes/CartStatusAutoFix.php' => 'Auto-fix utility function'
        ],
        'next_steps' => [
            'Integrate CartStatusValidator into existing APIs',
            'Add validation calls to customer update endpoints', 
            'Set up scheduled auto-fix execution'
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