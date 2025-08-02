<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $result = $conn->query("SELECT CustomerCode, CustomerName FROM customers LIMIT 3");
    
    echo "<h3>🔗 URL สำหรับทดสอบการสร้าง Order</h3>";
    
    if ($result->rowCount() > 0) {
        echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
        echo "<h4>เลือกลูกค้าใดลูกค้าหนึ่งเพื่อทดสอบ:</h4>";
        
        while ($customer = $result->fetch(PDO::FETCH_ASSOC)) {
            $customerCode = urlencode($customer['CustomerCode']);
            $url = "http://localhost/Kiro_CRM_production/pages/customer_detail.php?code={$customerCode}";
            
            echo "<div style='margin: 10px 0; padding: 10px; background: white; border: 1px solid #ddd;'>";
            echo "<strong>ลูกค้า:</strong> " . htmlspecialchars($customer['CustomerName']) . "<br>";
            echo "<strong>URL:</strong> <a href='{$url}' target='_blank' style='color: #007bff;'>{$url}</a>";
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        echo "<p style='color: #dc3545;'>❌ ไม่มีลูกค้าในระบบ</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>