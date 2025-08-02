<?php
/**
 * Test Cart Status Fix
 * Test the cart status separation functionality
 */

// Set headers for testing
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Cart Status Fix</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>
    <h1>Test Cart Status Separation Fix</h1>
    
    <?php
    try {
        require_once 'config/database.php';
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        echo '<div class="section success">';
        echo '<h3>✅ Database Connection Successful</h3>';
        echo '</div>';
        
        // Test 1: Check if CartStatus column exists
        echo '<div class="section info">';
        echo '<h3>Test 1: Check CartStatus Column</h3>';
        $checkSql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = 'customers' 
                     AND COLUMN_NAME = 'CartStatus'";
        $result = $pdo->query($checkSql)->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo '<p>✅ CartStatus column exists</p>';
            
            // Show column definition
            $definitionSql = "SELECT COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT 
                             FROM INFORMATION_SCHEMA.COLUMNS 
                             WHERE TABLE_SCHEMA = DATABASE() 
                             AND TABLE_NAME = 'customers' 
                             AND COLUMN_NAME = 'CartStatus'";
            $definition = $pdo->query($definitionSql)->fetch(PDO::FETCH_ASSOC);
            echo '<p>Column Type: ' . $definition['COLUMN_TYPE'] . '</p>';
            echo '<p>Default: ' . $definition['COLUMN_DEFAULT'] . '</p>';
            echo '<p>Comment: ' . $definition['COLUMN_COMMENT'] . '</p>';
        } else {
            echo '<p>❌ CartStatus column not found!</p>';
            echo '<p><strong>Action required:</strong> Run the database migration script</p>';
        }
        echo '</div>';
        
        // Test 2: Check CartStatus distribution
        echo '<div class="section info">';
        echo '<h3>Test 2: CartStatus Distribution</h3>';
        try {
            $distributionSql = "SELECT CartStatus, COUNT(*) as Count FROM customers GROUP BY CartStatus";
            $stmt = $pdo->query($distributionSql);
            echo '<ul>';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<li>' . htmlspecialchars($row['CartStatus']) . ': ' . $row['Count'] . ' customers</li>';
            }
            echo '</ul>';
        } catch (Exception $e) {
            echo '<p>❌ Error querying CartStatus: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // Test 3: Test Waiting Basket API
        echo '<div class="section info">';
        echo '<h3>Test 3: Waiting Basket API</h3>';
        try {
            $waitingSql = "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้ารอ'";
            $waitingResult = $pdo->query($waitingSql)->fetch(PDO::FETCH_ASSOC);
            echo '<p>✅ Waiting basket customers: ' . $waitingResult['count'] . '</p>';
        } catch (Exception $e) {
            echo '<p>❌ Error querying waiting basket: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // Test 4: Test Distribution Basket API
        echo '<div class="section info">';
        echo '<h3>Test 4: Distribution Basket API</h3>';
        try {
            $distributionSql = "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ตะกร้าแจก'";
            $distributionResult = $pdo->query($distributionSql)->fetch(PDO::FETCH_ASSOC);
            echo '<p>✅ Distribution basket customers: ' . $distributionResult['count'] . '</p>';
        } catch (Exception $e) {
            echo '<p>❌ Error querying distribution basket: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // Test 5: Test Assigned Customers
        echo '<div class="section info">';
        echo '<h3>Test 5: Assigned Customers</h3>';
        try {
            $assignedSql = "SELECT COUNT(*) as count FROM customers WHERE CartStatus = 'ลูกค้าแจกแล้ว'";
            $assignedResult = $pdo->query($assignedSql)->fetch(PDO::FETCH_ASSOC);
            echo '<p>✅ Assigned customers: ' . $assignedResult['count'] . '</p>';
        } catch (Exception $e) {
            echo '<p>❌ Error querying assigned customers: ' . $e->getMessage() . '</p>';
        }
        echo '</div>';
        
        // Test 6: Sample data from each basket
        echo '<div class="section info">';
        echo '<h3>Test 6: Sample Data from Each Basket</h3>';
        
        $basketTypes = ['ตะกร้ารอ', 'ตะกร้าแจก', 'ลูกค้าแจกแล้ว'];
        foreach ($basketTypes as $basketType) {
            echo '<h4>' . htmlspecialchars($basketType) . '</h4>';
            try {
                $sampleSql = "SELECT CustomerCode, CustomerName, Sales 
                             FROM customers 
                             WHERE CartStatus = ? 
                             LIMIT 3";
                $stmt = $pdo->prepare($sampleSql);
                $stmt->execute([$basketType]);
                
                echo '<ul>';
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo '<li>' . htmlspecialchars($row['CustomerCode']) . ' - ' . 
                         htmlspecialchars($row['CustomerName']) . 
                         ' (Sales: ' . htmlspecialchars($row['Sales'] ?: 'None') . ')</li>';
                }
                echo '</ul>';
            } catch (Exception $e) {
                echo '<p>❌ Error querying ' . htmlspecialchars($basketType) . ': ' . $e->getMessage() . '</p>';
            }
        }
        echo '</div>';
        
        echo '<div class="section success">';
        echo '<h3>🎉 Cart Status Separation Test Completed</h3>';
        echo '<p>Check the results above to verify that the cart status separation is working correctly.</p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section error">';
        echo '<h3>❌ Database Connection Error</h3>';
        echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    ?>
    
    <div class="section info">
        <h3>Manual Testing Instructions</h3>
        <ol>
            <li>Go to <strong>ตะกร้ารอ</strong> (Waiting Basket) page and verify only customers with CartStatus = 'ตะกร้ารอ' are displayed</li>
            <li>Go to <strong>ตะกร้าแจก</strong> (Distribution Basket) page and verify only customers with CartStatus = 'ตะกร้าแจก' are displayed</li>
            <li>Try moving customers from waiting basket to distribution basket</li>
            <li>Try assigning customers from distribution basket to sales users</li>
            <li>Verify that assigned customers show CartStatus = 'ลูกค้าแจกแล้ว'</li>
        </ol>
    </div>
</body>
</html>