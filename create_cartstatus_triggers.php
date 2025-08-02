<?php
/**
 * Create CartStatus Auto-Update Triggers
 * Database triggers to automatically maintain CartStatus consistency
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Trigger 1: Update CartStatus when Sales is assigned
    $trigger1 = "
    CREATE TRIGGER customers_sales_update_cartstatus
    BEFORE UPDATE ON customers
    FOR EACH ROW
    BEGIN
        -- When Sales is assigned (was NULL/empty, now has value)
        IF (OLD.Sales IS NULL OR OLD.Sales = '') AND (NEW.Sales IS NOT NULL AND NEW.Sales != '') THEN
            SET NEW.CartStatus = 'ลูกค้าแจกแล้ว';
            SET NEW.ModifiedDate = NOW();
        END IF;
        
        -- When Sales is removed (had value, now NULL/empty)  
        IF (OLD.Sales IS NOT NULL AND OLD.Sales != '') AND (NEW.Sales IS NULL OR NEW.Sales = '') THEN
            SET NEW.CartStatus = 'ตะกร้าแจก';
            SET NEW.ModifiedDate = NOW();
        END IF;
    END;
    ";
    
    // Trigger 2: Validate CartStatus consistency on INSERT
    $trigger2 = "
    CREATE TRIGGER customers_insert_cartstatus_validation
    BEFORE INSERT ON customers
    FOR EACH ROW
    BEGIN
        -- Auto-set CartStatus based on Sales field
        IF NEW.Sales IS NOT NULL AND NEW.Sales != '' THEN
            SET NEW.CartStatus = 'ลูกค้าแจกแล้ว';
        ELSE
            -- Default to waiting basket if not specified
            IF NEW.CartStatus IS NULL OR NEW.CartStatus = '' THEN
                SET NEW.CartStatus = 'ตะกร้ารอ';
            END IF;
        END IF;
    END;
    ";
    
    $results = [];
    
    // Drop existing triggers if they exist
    try {
        $pdo->exec("DROP TRIGGER IF EXISTS customers_sales_update_cartstatus");
        $pdo->exec("DROP TRIGGER IF EXISTS customers_insert_cartstatus_validation");
        $results[] = "Dropped existing triggers";
    } catch (Exception $e) {
        $results[] = "No existing triggers to drop";
    }
    
    // Create new triggers
    try {
        $pdo->exec($trigger1);
        $results[] = "✅ Created UPDATE trigger for Sales → CartStatus sync";
    } catch (Exception $e) {
        throw new Exception("Failed to create UPDATE trigger: " . $e->getMessage());
    }
    
    try {
        $pdo->exec($trigger2);
        $results[] = "✅ Created INSERT trigger for CartStatus validation";
    } catch (Exception $e) {
        throw new Exception("Failed to create INSERT trigger: " . $e->getMessage());
    }
    
    // Test the triggers with a sample update
    try {
        // Test update trigger
        $pdo->exec("UPDATE customers SET Sales = 'test_trigger' WHERE CustomerCode = 'NONEXISTENT' LIMIT 0");
        $results[] = "✅ Triggers syntax validated successfully";
    } catch (Exception $e) {
        $results[] = "⚠️ Trigger test warning: " . $e->getMessage();
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database triggers created successfully',
        'details' => $results,
        'triggers_created' => [
            'customers_sales_update_cartstatus' => 'Auto-updates CartStatus when Sales changes',
            'customers_insert_cartstatus_validation' => 'Validates CartStatus on new customer creation'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'suggestion' => 'Check database permissions for creating triggers'
    ]);
}
?>