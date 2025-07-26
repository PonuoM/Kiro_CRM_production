<?php
session_start();

// Mock session for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 'admin';
    $_SESSION['role'] = 'Admin';
    $_SESSION['username'] = 'admin';
}

header('Content-Type: application/json');

echo "<h3>Full API Logic Test</h3>";

$statuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];

foreach($statuses as $customerStatus) {
    echo "<h4>Testing: $customerStatus</h4>";
    
    try {
        require_once 'config/database.php';
        require_once 'includes/permissions.php';
        
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        $unassigned = false;
        $search = '';
        $currentUser = Permissions::getCurrentUser();
        $canViewAll = Permissions::canViewAllData();
        
        // Same logic as API
        $sql = "SELECT 
                    CustomerCode,
                    CustomerName,
                    CustomerTel,
                    CustomerStatus,
                    CustomerGrade,
                    CustomerTemperature,
                    TotalPurchase,
                    LastContactDate,
                    Sales,
                    CreatedDate,
                    CustomerProvince,
                    ModifiedDate
                FROM customers WHERE 1=1";
        $params = [];
        
        // Add status filter
        if ($customerStatus !== 'all') {
            $sql .= " AND CustomerStatus = ?";
            $params[] = $customerStatus;
        }
        
        // Add user filter for Sales role
        if (!$canViewAll) {
            $sql .= " AND Sales = ?";
            $params[] = $currentUser;
        }
        
        $sql .= " ORDER BY 
                    CASE CustomerGrade WHEN 'A' THEN 1 WHEN 'B' THEN 2 WHEN 'C' THEN 3 ELSE 4 END,
                    CASE CustomerTemperature WHEN 'HOT' THEN 1 WHEN 'WARM' THEN 2 ELSE 3 END,
                    CreatedDate DESC 
                LIMIT 50";
        
        echo "SQL: " . $sql . "<br>";
        echo "Params: " . json_encode($params) . "<br>";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Found " . count($customers) . " customers<br>";
        
        if (count($customers) > 0) {
            echo "First customer: " . $customers[0]['CustomerName'] . "<br>";
            echo "Sample JSON: <pre>" . json_encode(array_slice($customers, 0, 2), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        }
        
    } catch(Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}
?>