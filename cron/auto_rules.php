<?php
/**
 * Auto Rules Engine - Cron Job
 * This script implements automatic customer lifecycle management
 * Should be run daily via cron job
 * 
 * Rules implemented:
 * 1. New customers with no updates for 30 days → move to "ตะกร้าแจก"
 * 2. Follow-up customers with no orders for 3 months → move to "ตะกร้ารอ"
 * 3. Old customers with no repeat purchases for 3 months → move to "ตะกร้ารอ"
 */

require_once __DIR__ . '/../includes/functions.php';

// Start execution
$start_time = microtime(true);
$execution_date = date('Y-m-d H:i:s');

echo "=== Auto Rules Engine Started ===\n";
echo "วันที่รัน: " . $execution_date . "\n";

// Initialize counters
$stats = [
    'new_to_distribute' => 0,
    'followup_to_waiting' => 0,
    'old_to_waiting' => 0,
    'total_processed' => 0,
    'errors' => 0
];

try {
    // Get database connection
    $db = getDB();
    
    // Rule 1: New customers with no updates for 30 days → move to "ตะกร้าแจก"
    echo "\n--- Rule 1: Processing new customers (30 days) ---\n";
    $stats['new_to_distribute'] = processNewCustomersRule($db);
    
    // Rule 2: Follow-up customers with no orders for 3 months → move to "ตะกร้ารอ"
    echo "\n--- Rule 2: Processing follow-up customers (3 months) ---\n";
    $stats['followup_to_waiting'] = processFollowupCustomersRule($db);
    
    // Rule 3: Old customers with no repeat purchases for 3 months → move to "ตะกร้ารอ"
    echo "\n--- Rule 3: Processing old customers (3 months) ---\n";
    $stats['old_to_waiting'] = processOldCustomersRule($db);
    
    // Calculate totals
    $stats['total_processed'] = $stats['new_to_distribute'] + $stats['followup_to_waiting'] + $stats['old_to_waiting'];
    
    // Log successful execution
    $execution_time = round(microtime(true) - $start_time, 2);
    logCronSuccess('Auto Rules Engine', $stats, $execution_time);
    
    echo "\n=== Auto Rules Engine Completed ===\n";
    echo "สรุปผลการทำงาน:\n";
    echo "- ลูกค้าใหม่ย้ายไป 'ตะกร้าแจก': {$stats['new_to_distribute']} คน\n";
    echo "- ลูกค้าติดตามย้ายไป 'ตะกร้ารอ': {$stats['followup_to_waiting']} คน\n";
    echo "- ลูกค้าเก่าย้ายไป 'ตะกร้ารอ': {$stats['old_to_waiting']} คน\n";
    echo "- รวมทั้งหมด: {$stats['total_processed']} คน\n";
    echo "- เวลาที่ใช้: {$execution_time} วินาที\n";
    echo "- หน่วยความจำที่ใช้: " . formatBytes(memory_get_usage(true)) . "\n";
    echo "- หน่วยความจำสูงสุด: " . formatBytes(memory_get_peak_usage(true)) . "\n";
    
} catch (Exception $e) {
    $stats['errors']++;
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Enhanced error logging
    logCronError('Auto Rules Engine', $e->getMessage(), [
        'stats' => $stats,
        'execution_time' => $execution_time,
        'trace' => $e->getTraceAsString()
    ]);
    
    echo "\nError: " . $e->getMessage() . "\n";
    echo "กรุณาตรวจสอบ log files สำหรับรายละเอียดเพิ่มเติม\n";
    exit(1);
}

/**
 * Rule 1: Process new customers with no updates for 30 days
 * Move them to "ตะกร้าแจก" (distribute cart)
 * 
 * @param Database $db
 * @return int Number of customers processed
 */
function processNewCustomersRule($db) {
    $processed = 0;
    
    try {
        // Find new customers with no updates for 30 days
        // Check ModifiedDate or CreatedDate if ModifiedDate is null
        $sql = "
            SELECT CustomerCode, CustomerName, 
                   COALESCE(ModifiedDate, CreatedDate) as LastUpdate
            FROM customers 
            WHERE CustomerStatus = 'ลูกค้าใหม่' 
            AND CartStatus = 'กำลังดูแล'
            AND DATEDIFF(NOW(), COALESCE(ModifiedDate, CreatedDate)) >= 30
        ";
        
        $customers = $db->query($sql);
        
        if (empty($customers)) {
            echo "ไม่พบลูกค้าใหม่ที่ต้องย้าย\n";
            return 0;
        }
        
        echo "พบลูกค้าใหม่ที่ต้องย้าย: " . count($customers) . " คน\n";
        
        // Begin transaction
        $db->beginTransaction();
        
        foreach ($customers as $customer) {
            try {
                // Use enhanced function to update CartStatus with audit trail
                if (updateCustomerCartStatus(
                    $customer['CustomerCode'], 
                    'ตะกร้าแจก', 
                    'AUTO_RULES_30_DAYS: ลูกค้าใหม่ไม่มีการอัปเดต 30 วัน', 
                    'AUTO_RULES'
                )) {
                    $processed++;
                    echo "- ย้าย {$customer['CustomerName']} ({$customer['CustomerCode']}) ไป 'ตะกร้าแจก'\n";
                } else {
                    echo "- ไม่สามารถย้าย {$customer['CustomerName']} ({$customer['CustomerCode']}) ได้\n";
                }
            } catch (Exception $e) {
                echo "Error processing customer {$customer['CustomerCode']}: " . $e->getMessage() . "\n";
                continue;
            }
        }
        
        // Commit transaction
        $db->commit();
        
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->rollback();
        }
        throw new Exception("Rule 1 failed: " . $e->getMessage());
    }
    
    return $processed;
}

/**
 * Rule 2: Process follow-up customers with no orders for 3 months
 * Move them to "ตะกร้ารอ" (waiting cart)
 * 
 * @param Database $db
 * @return int Number of customers processed
 */
function processFollowupCustomersRule($db) {
    $processed = 0;
    
    try {
        // Find follow-up customers with no orders for 3 months
        $sql = "
            SELECT c.CustomerCode, c.CustomerName, c.OrderDate,
                   MAX(o.DocumentDate) as LastOrderDate
            FROM customers c
            LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode
            WHERE c.CustomerStatus = 'ลูกค้าติดตาม' 
            AND c.CartStatus = 'กำลังดูแล'
            AND (
                (c.OrderDate IS NULL AND DATEDIFF(NOW(), c.CreatedDate) >= 90)
                OR 
                (c.OrderDate IS NOT NULL AND DATEDIFF(NOW(), c.OrderDate) >= 90)
            )
            GROUP BY c.CustomerCode, c.CustomerName, c.OrderDate
            HAVING (LastOrderDate IS NULL OR DATEDIFF(NOW(), LastOrderDate) >= 90)
        ";
        
        $customers = $db->query($sql);
        
        if (empty($customers)) {
            echo "ไม่พบลูกค้าติดตามที่ต้องย้าย\n";
            return 0;
        }
        
        echo "พบลูกค้าติดตามที่ต้องย้าย: " . count($customers) . " คน\n";
        
        // Begin transaction
        $db->beginTransaction();
        
        foreach ($customers as $customer) {
            try {
                // Use enhanced function to update CartStatus with audit trail
                if (updateCustomerCartStatus(
                    $customer['CustomerCode'], 
                    'ตะกร้ารอ', 
                    'AUTO_RULES_3_MONTHS_NO_ORDER: ลูกค้าติดตามไม่มีคำสั่งซื้อ 3 เดือน', 
                    'AUTO_RULES'
                )) {
                    $processed++;
                    echo "- ย้าย {$customer['CustomerName']} ({$customer['CustomerCode']}) ไป 'ตะกร้ารอ'\n";
                } else {
                    echo "- ไม่สามารถย้าย {$customer['CustomerName']} ({$customer['CustomerCode']}) ได้\n";
                }
            } catch (Exception $e) {
                echo "Error processing customer {$customer['CustomerCode']}: " . $e->getMessage() . "\n";
                continue;
            }
        }
        
        // Commit transaction
        $db->commit();
        
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->rollback();
        }
        throw new Exception("Rule 2 failed: " . $e->getMessage());
    }
    
    return $processed;
}

/**
 * Rule 3: Process old customers with no repeat purchases for 3 months
 * Move them to "ตะกร้ารอ" (waiting cart)
 * 
 * @param Database $db
 * @return int Number of customers processed
 */
function processOldCustomersRule($db) {
    $processed = 0;
    
    try {
        // Find old customers with no repeat purchases for 3 months
        $sql = "
            SELECT c.CustomerCode, c.CustomerName, c.OrderDate,
                   MAX(o.DocumentDate) as LastOrderDate
            FROM customers c
            LEFT JOIN orders o ON c.CustomerCode = o.CustomerCode
            WHERE c.CustomerStatus = 'ลูกค้าเก่า' 
            AND c.CartStatus = 'กำลังดูแล'
            AND (
                (c.OrderDate IS NOT NULL AND DATEDIFF(NOW(), c.OrderDate) >= 90)
                OR
                (c.OrderDate IS NULL AND DATEDIFF(NOW(), c.CreatedDate) >= 90)
            )
            GROUP BY c.CustomerCode, c.CustomerName, c.OrderDate
            HAVING (LastOrderDate IS NULL OR DATEDIFF(NOW(), LastOrderDate) >= 90)
        ";
        
        $customers = $db->query($sql);
        
        if (empty($customers)) {
            echo "ไม่พบลูกค้าเก่าที่ต้องย้าย\n";
            return 0;
        }
        
        echo "พบลูกค้าเก่าที่ต้องย้าย: " . count($customers) . " คน\n";
        
        // Begin transaction
        $db->beginTransaction();
        
        foreach ($customers as $customer) {
            try {
                // Use enhanced function to update CartStatus with audit trail
                if (updateCustomerCartStatus(
                    $customer['CustomerCode'], 
                    'ตะกร้ารอ', 
                    'AUTO_RULES_3_MONTHS_NO_REPEAT: ลูกค้าเก่าไม่ซื้อซ้ำ 3 เดือน', 
                    'AUTO_RULES'
                )) {
                    $processed++;
                    echo "- ย้าย {$customer['CustomerName']} ({$customer['CustomerCode']}) ไป 'ตะกร้ารอ'\n";
                } else {
                    echo "- ไม่สามารถย้าย {$customer['CustomerName']} ({$customer['CustomerCode']}) ได้\n";
                }
            } catch (Exception $e) {
                echo "Error processing customer {$customer['CustomerCode']}: " . $e->getMessage() . "\n";
                continue;
            }
        }
        
        // Commit transaction
        $db->commit();
        
    } catch (Exception $e) {
        if ($db->getConnection()->inTransaction()) {
            $db->rollback();
        }
        throw new Exception("Rule 3 failed: " . $e->getMessage());
    }
    
    return $processed;
}


?>