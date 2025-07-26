<?php
/**
 * Create Daily Tasks for Sales01 - Fix "กำลังดูแล" customers showing issue
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

echo "<h2>🚀 Create Daily Tasks for Sales01</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Database connected<br>";
    
    // Get sales01's customers with 'กำลังดูแล' status
    $stmt = $pdo->prepare("SELECT CustomerCode, CustomerName, CustomerTel FROM customers WHERE Sales = 'sales01' AND CartStatus = 'กำลังดูแล' ORDER BY CustomerCode");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Found " . count($customers) . " customers with 'กำลังดูแล' status for sales01</h3>";
    
    if (count($customers) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>Customer Code</th><th>Customer Name</th><th>Phone</th><th>Action</th></tr>";
        
        $today = date('Y-m-d');
        $created = 0;
        $existing = 0;
        
        foreach ($customers as $customer) {
            echo "<tr>";
            echo "<td>{$customer['CustomerCode']}</td>";
            echo "<td>{$customer['CustomerName']}</td>";
            echo "<td>{$customer['CustomerTel']}</td>";
            
            // Check if task already exists for today
            $checkStmt = $pdo->prepare("SELECT id FROM tasks WHERE CustomerCode = ? AND DATE(FollowupDate) = ?");
            $checkStmt->execute([$customer['CustomerCode'], $today]);
            
            if ($checkStmt->rowCount() == 0) {
                // Create task for today
                $hour = rand(9, 16); // Random hour between 9 AM - 4 PM
                $minute = rand(0, 59);
                $followupTime = date('Y-m-d H:i:s', strtotime("today +{$hour} hours +{$minute} minutes"));
                
                $remarks = "ติดตามลูกค้า {$customer['CustomerName']} - ตรวจสอบความต้องการและให้คำปรึกษาสินค้า";
                
                $insertStmt = $pdo->prepare("INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy, CreatedDate) VALUES (?, ?, ?, 'รอดำเนินการ', 'sales01', NOW())");
                $result = $insertStmt->execute([
                    $customer['CustomerCode'],
                    $followupTime,
                    $remarks
                ]);
                
                if ($result) {
                    $created++;
                    echo "<td>✅ <strong>Created task for " . date('H:i', strtotime($followupTime)) . "</strong></td>";
                } else {
                    echo "<td>❌ Failed to create</td>";
                }
            } else {
                $existing++;
                echo "<td>⚠️ Task already exists</td>";
            }
            
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h4>📊 Summary:</h4>";
        echo "- Total customers processed: <strong>" . count($customers) . "</strong><br>";
        echo "- New tasks created: <strong>$created</strong><br>";
        echo "- Tasks already existing: <strong>$existing</strong><br>";
        echo "- Date: <strong>" . date('d/m/Y') . "</strong><br>";
        echo "</div>";
        
        if ($created > 0) {
            echo "<div style='background: #cff4fc; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "🎉 <strong>Success!</strong> Created $created new tasks for today.<br>";
            echo "Now sales01 should see <strong>" . ($created + $existing) . " tasks</strong> in the daily tasks page.<br><br>";
            echo "<a href='pages/daily_tasks_demo.php' style='background: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📅 View Daily Tasks Page</a><br><br>";
            echo "<a href='debug_daily_tasks.php' style='background: #6f42c1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔍 Debug Analysis</a>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "❌ <strong>No customers found</strong> with 'กำลังดูแล' status for sales01.<br>";
        echo "Please check the customer data or run the mock data script first.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "❌ <strong>Database Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<h3>🔗 Quick Links</h3>";
echo "<a href='simple_login_test.php'>🔑 Login Test</a> | ";
echo "<a href='debug_daily_tasks.php'>🔍 Debug Analysis</a> | ";
echo "<a href='pages/daily_tasks_demo.php'>📅 Daily Tasks</a> | ";
echo "<a href='pages/dashboard.php'>📊 Dashboard</a>";
?>