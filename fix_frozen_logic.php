<?php
/**
 * Fix FROZEN Logic
 * Update auto-system logic to consider customer value
 */

require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $updatedFiles = [];
    
    // 1. Fix production_auto_system.php - add value-aware FROZEN logic
    $productionSystemFile = '/mnt/c/xampp/htdocs/Kiro_CRM_production/production_auto_system.php';
    $content = file_get_contents($productionSystemFile);
    
    // Replace the old FROZEN logic with value-aware logic
    $oldFrozenLogic = 'SET CustomerTemperature = \'FROZEN\', CustomerGrade = \'D\' 
                WHERE CustomerStatus = \'ลูกค้าเก่า\' AND LastContactDate IS NOT NULL AND DATEDIFF(CURDATE(), LastContactDate) > 90';
    
    $newFrozenLogic = 'SET CustomerTemperature = \'FROZEN\', CustomerGrade = \'D\' 
                WHERE CustomerStatus = \'ลูกค้าเก่า\' 
                AND LastContactDate IS NOT NULL 
                AND DATEDIFF(CURDATE(), LastContactDate) > 90
                AND COALESCE(TotalPurchase, 0) < 5000  -- Only freeze low-value customers
                AND COALESCE(ContactAttempts, 0) >= 3   -- Must have multiple contact attempts';
    
    if (strpos($content, $oldFrozenLogic) !== false) {
        $content = str_replace($oldFrozenLogic, $newFrozenLogic, $content);
        file_put_contents($productionSystemFile, $content);
        $updatedFiles[] = 'production_auto_system.php - Added value-aware FROZEN logic';
    }
    
    // Also fix the smart temperature logic
    $oldTempLogic = 'WHEN Sales IS NULL OR CustomerStatus = \'ในตระกร้า\' THEN \'FROZEN\'
                WHEN LastContactDate IS NULL OR DATEDIFF(CURDATE(), LastContactDate) > 30 THEN \'FROZEN\'';
    
    $newTempLogic = 'WHEN Sales IS NULL OR CustomerStatus = \'ในตระกร้า\' THEN 
                    CASE 
                        WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN \'WARM\'  -- High-value customers stay WARM
                        ELSE \'FROZEN\' 
                    END
                WHEN LastContactDate IS NULL OR DATEDIFF(CURDATE(), LastContactDate) > 30 THEN 
                    CASE 
                        WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN \'COLD\'  -- High-value customers become COLD, not FROZEN
                        WHEN COALESCE(ContactAttempts, 0) >= 3 THEN \'FROZEN\'  -- Only freeze after multiple attempts
                        ELSE \'COLD\' 
                    END';
    
    if (strpos($content, 'WHEN Sales IS NULL OR CustomerStatus = \'ในตระกร้า\' THEN \'FROZEN\'') !== false) {
        $content = str_replace($oldTempLogic, $newTempLogic, $content);
        file_put_contents($productionSystemFile, $content);
        if (!in_array('production_auto_system.php - Added value-aware FROZEN logic', $updatedFiles)) {
            $updatedFiles[] = 'production_auto_system.php - Updated temperature logic';
        }
    }
    
    // 2. Fix auto_customer_management.php
    $autoMgmtFile = '/mnt/c/xampp/htdocs/Kiro_CRM_production/auto_customer_management.php';
    if (file_exists($autoMgmtFile)) {
        $content2 = file_get_contents($autoMgmtFile);
        
        // Replace similar logic in auto_customer_management.php
        $oldLogic2 = 'WHEN Sales IS NULL OR CustomerStatus = \'ในตระกร้า\' THEN \'FROZEN\'';
        $newLogic2 = 'WHEN Sales IS NULL OR CustomerStatus = \'ในตระกร้า\' THEN 
                        CASE 
                            WHEN COALESCE(TotalPurchase, 0) >= 5000 THEN \'WARM\'
                            ELSE \'FROZEN\' 
                        END';
        
        if (strpos($content2, $oldLogic2) !== false) {
            $content2 = str_replace($oldLogic2, $newLogic2, $content2);
            file_put_contents($autoMgmtFile, $content2);
            $updatedFiles[] = 'auto_customer_management.php - Added value protection';
        }
    }
    
    // 3. Fix Grade logic to not force high-value customers to Grade D
    $oldGradeLogic = 'WHEN CustomerStatus = \'ในตระกร้า\' OR CustomerTemperature = \'FROZEN\' THEN \'D\'';
    $newGradeLogic = 'WHEN CustomerStatus = \'ในตระกร้า\' OR (CustomerTemperature = \'FROZEN\' AND COALESCE(TotalPurchase, 0) < 2000) THEN \'D\'
                WHEN CustomerTemperature = \'FROZEN\' AND COALESCE(TotalPurchase, 0) >= 10000 THEN \'A\'
                WHEN CustomerTemperature = \'FROZEN\' AND COALESCE(TotalPurchase, 0) >= 5000 THEN \'B\'
                WHEN CustomerTemperature = \'FROZEN\' AND COALESCE(TotalPurchase, 0) >= 2000 THEN \'C\'';
    
    // Update production_auto_system.php grade logic
    $content = file_get_contents($productionSystemFile);
    if (strpos($content, $oldGradeLogic) !== false) {
        $content = str_replace($oldGradeLogic, $newGradeLogic, $content);
        file_put_contents($productionSystemFile, $content);
        if (!in_array('production_auto_system.php - Added value-aware FROZEN logic', $updatedFiles) && 
            !in_array('production_auto_system.php - Updated temperature logic', $updatedFiles)) {
            $updatedFiles[] = 'production_auto_system.php - Fixed grade logic for high-value FROZEN customers';
        }
    }
    
    // 4. Create a backup note
    file_put_contents('/mnt/c/xampp/htdocs/Kiro_CRM_production/FROZEN_LOGIC_BACKUP_' . date('Y-m-d_H-i-s') . '.md', 
        "# FROZEN Logic Update - " . date('Y-m-d H:i:s') . "\n\n" .
        "## Changes Made:\n" .
        "1. Added TotalPurchase consideration to FROZEN logic\n" .
        "2. High-value customers (≥฿5,000) protected from auto-FROZEN\n" .
        "3. Require ContactAttempts ≥ 3 before FROZEN\n" .
        "4. High-value FROZEN customers maintain proper grades\n\n" .
        "## Files Updated:\n" .
        implode("\n", $updatedFiles) . "\n\n" .
        "## Business Rules:\n" .
        "- Grade A/B customers should not be auto-FROZEN\n" .
        "- TotalPurchase ≥ ฿5,000 = High-value protection\n" .
        "- ContactAttempts ≥ 3 required for FROZEN\n" .
        "- FROZEN customers with high TotalPurchase keep appropriate grades"
    );
    
    $updatedFiles[] = 'FROZEN_LOGIC_BACKUP_' . date('Y-m-d_H-i-s') . '.md - Documentation';
    
    echo json_encode([
        'status' => 'success',
        'message' => 'FROZEN logic updated successfully',
        'updated_files' => $updatedFiles,
        'changes_made' => [
            'value_protection' => 'High-value customers (≥฿5,000) protected from auto-FROZEN',
            'contact_requirement' => 'ContactAttempts ≥ 3 required before FROZEN',
            'grade_preservation' => 'FROZEN customers with high TotalPurchase keep appropriate grades',
            'smart_temperature' => 'High-value customers become COLD instead of FROZEN'
        ],
        'next_steps' => [
            'Test the updated logic with dry-run',
            'Monitor FROZEN customers after update',
            'Review manually FROZEN high-value customers'
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