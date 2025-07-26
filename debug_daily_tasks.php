<?php
/**
 * Debug Daily Tasks Data - ตรวจสอบข้อมูล tasks และ customers
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/permissions.php';

echo "<h2>🔍 Debug Daily Tasks Data</h2>";

// Check session
echo "<h3>1. Session Information</h3>";
echo "Current User: <strong>" . ($_SESSION['username'] ?? 'Not set') . "</strong><br>";
echo "User Role: <strong>" . ($_SESSION['user_role'] ?? 'Not set') . "</strong><br>";
echo "User ID: <strong>" . ($_SESSION['user_id'] ?? 'Not set') . "</strong><br>";

if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: red;'>❌ Please login first: <a href='simple_login_test.php'>Login Here</a></div>";
    exit;
}

// Get database connection
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<br>✅ Database connected<br>";
} catch (Exception $e) {
    echo "<br>❌ Database error: " . $e->getMessage() . "<br>";
    exit;
}

// Check permissions
$currentUser = Permissions::getCurrentUser();
$canViewAll = Permissions::canViewAllData();

echo "<h3>2. Permission Check</h3>";
echo "Current User (from Permissions): <strong>$currentUser</strong><br>";
echo "Can View All Data: " . ($canViewAll ? "✅ YES (Admin/Supervisor)" : "❌ NO (Sales only)") . "<br>";

// Check customers data for sales01
echo "<h3>3. Customers Data for Sales01</h3>";
$stmt = $pdo->prepare("SELECT CustomerCode, CustomerName, CustomerTel, CustomerStatus, CartStatus, Sales FROM customers WHERE Sales = 'sales01' ORDER BY CustomerCode");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found <strong>" . count($customers) . "</strong> customers for sales01:<br>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Code</th><th>Name</th><th>Tel</th><th>Status</th><th>Cart Status</th><th>Sales</th></tr>";

$careTakingCount = 0;
foreach ($customers as $customer) {
    $bgColor = $customer['CartStatus'] === 'กำลังดูแล' ? '#e8f5e8' : '#fff';
    if ($customer['CartStatus'] === 'กำลังดูแล') $careTakingCount++;
    
    echo "<tr style='background: $bgColor;'>";
    echo "<td>{$customer['CustomerCode']}</td>";
    echo "<td>{$customer['CustomerName']}</td>";
    echo "<td>{$customer['CustomerTel']}</td>";
    echo "<td>{$customer['CustomerStatus']}</td>";
    echo "<td><strong>{$customer['CartStatus']}</strong></td>";
    echo "<td>{$customer['Sales']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='background: #e8f5e8; padding: 10px; margin: 10px 0;'>";
echo "📊 <strong>Summary for Sales01:</strong><br>";
echo "- Total customers: " . count($customers) . "<br>";
echo "- Currently taking care (กำลังดูแล): <strong>$careTakingCount</strong><br>";
echo "</div>";

// Check tasks data
echo "<h3>4. Tasks Data</h3>";
$today = date('Y-m-d');
echo "Today's date: <strong>$today</strong><br>";

// Check all tasks
$stmt = $pdo->prepare("SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales FROM tasks t LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode ORDER BY t.FollowupDate DESC");
$stmt->execute();
$allTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total tasks in database: <strong>" . count($allTasks) . "</strong><br>";

if (count($allTasks) > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Customer Code</th><th>Customer Name</th><th>Follow Date</th><th>Remarks</th><th>Status</th><th>Created By</th><th>Customer Sales</th>";
    echo "</tr>";
    
    $todayTasksCount = 0;
    $sales01TasksCount = 0;
    
    foreach ($allTasks as $task) {
        $isToday = date('Y-m-d', strtotime($task['FollowupDate'])) === $today;
        $isSales01 = ($task['Sales'] === 'sales01' || $task['CreatedBy'] === 'sales01');
        
        if ($isToday) $todayTasksCount++;
        if ($isSales01) $sales01TasksCount++;
        
        $bgColor = '#fff';
        if ($isToday && $isSales01) $bgColor = '#ffffcc'; // Yellow for today + sales01
        elseif ($isToday) $bgColor = '#e8f5e8'; // Green for today
        elseif ($isSales01) $bgColor = '#f0f0ff'; // Light blue for sales01
        
        echo "<tr style='background: $bgColor;'>";
        echo "<td>{$task['id']}</td>";
        echo "<td>{$task['CustomerCode']}</td>";
        echo "<td>" . ($task['CustomerName'] ?? 'N/A') . "</td>";
        echo "<td><strong>" . date('d/m/Y H:i', strtotime($task['FollowupDate'])) . "</strong></td>";
        echo "<td>" . substr($task['Remarks'] ?? '', 0, 50) . "...</td>";
        echo "<td>{$task['Status']}</td>";
        echo "<td>{$task['CreatedBy']}</td>";
        echo "<td>" . ($task['Sales'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
    echo "📋 <strong>Tasks Analysis:</strong><br>";
    echo "- Today's tasks (any user): <strong>$todayTasksCount</strong><br>";
    echo "- Sales01 related tasks (any date): <strong>$sales01TasksCount</strong><br>";
    echo "</div>";
}

// Simulate API call
echo "<h3>5. Simulate API Call (same as daily_tasks_demo.php)</h3>";
$apiSql = "SELECT t.*, c.CustomerName, c.CustomerTel, c.Sales 
           FROM tasks t 
           LEFT JOIN customers c ON t.CustomerCode = c.CustomerCode 
           WHERE DATE(t.FollowupDate) = ?";

$apiParams = [$today];

// Add user filter for Sales role (same logic as API)
if (!$canViewAll) {
    $apiSql .= " AND (t.CreatedBy = ? OR c.Sales = ? OR t.CreatedBy IS NULL)";
    $apiParams[] = $currentUser;
    $apiParams[] = $currentUser;
}

$apiSql .= " ORDER BY t.FollowupDate ASC";

echo "🔍 <strong>API Query:</strong><br>";
echo "<code>$apiSql</code><br>";
echo "📝 <strong>Parameters:</strong> " . implode(', ', array_map(function($p) { return "'$p'"; }, $apiParams)) . "<br>";

$stmt = $pdo->prepare($apiSql);
$stmt->execute($apiParams);
$apiTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📊 <strong>API Result:</strong> Found <strong>" . count($apiTasks) . "</strong> tasks<br>";

if (count($apiTasks) > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Customer</th><th>Follow Date</th><th>Remarks</th><th>Status</th><th>Customer Sales</th></tr>";
    foreach ($apiTasks as $task) {
        echo "<tr>";
        echo "<td><strong>{$task['CustomerName']}</strong><br><small>{$task['CustomerCode']}</small></td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($task['FollowupDate'])) . "</td>";
        echo "<td>" . substr($task['Remarks'] ?? '', 0, 40) . "...</td>";
        echo "<td>{$task['Status']}</td>";
        echo "<td>" . ($task['Sales'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; color: #721c24;'>";
    echo "❌ <strong>No tasks found for today matching the criteria!</strong><br>";
    echo "This explains why only 2 items show in daily_tasks_demo.php<br>";
    echo "</div>";
}

// Recommendations
echo "<h3>6. 💡 Analysis & Recommendations</h3>";

if (count($apiTasks) < $careTakingCount) {
    echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-left: 4px solid #ffc107;'>";
    echo "<strong>🔍 พบปัญหา:</strong><br>";
    echo "- Customers 'กำลังดูแล' by sales01: <strong>$careTakingCount รายการ</strong><br>";
    echo "- Tasks แสดงในหน้า daily_tasks: <strong>" . count($apiTasks) . " รายการ</strong><br>";
    echo "<br><strong>สาเหตุที่เป็นไปได้:</strong><br>";
    echo "1. <strong>ไม่มี Tasks สำหรับวันนี้:</strong> Tasks ใน database มี FollowupDate ไม่ใช่วันนี้<br>";
    echo "2. <strong>Permission Filter:</strong> API filter ใช้ (t.CreatedBy = sales01 OR c.Sales = sales01)<br>";
    echo "3. <strong>Missing Tasks:</strong> ลูกค้า 'กำลังดูแล' ไม่มี tasks ที่ต้องติดตาม<br>";
    echo "<br><strong>แก้ไข:</strong><br>";
    echo "- สร้าง tasks เพิ่มเติมสำหรับลูกค้า 'กำลังดูแล'<br>";
    echo "- ตรวจสอบ FollowupDate ใน tasks table<br>";
    echo "- อาจต้องแสดง customers 'กำลังดูแล' แทน tasks<br>";
    echo "</div>";
}

echo "<h3>7. 🚀 Quick Fix Options</h3>";
echo "<a href='?create_tasks=1' style='background: #28a745; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Create Today Tasks</a> ";
echo "<a href='?show_customers=1' style='background: #17a2b8; color: white; padding: 10px; text-decoration: none; margin: 5px;'>Show Taking Care Customers</a> ";

// Quick fix - create tasks
if (isset($_GET['create_tasks'])) {
    echo "<h4>🔧 Creating Today Tasks for Taking Care Customers</h4>";
    
    $stmt = $pdo->prepare("SELECT CustomerCode, CustomerName FROM customers WHERE Sales = 'sales01' AND CartStatus = 'กำลังดูแล' LIMIT 10");
    $stmt->execute();
    $careCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $created = 0;
    foreach ($careCustomers as $customer) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (CustomerCode, FollowupDate, Remarks, Status, CreatedBy, CreatedDate) VALUES (?, ?, ?, 'รอดำเนินการ', 'sales01', NOW())");
            $followupTime = date('Y-m-d H:i:s', strtotime('today +' . rand(8, 17) . ' hours'));
            $stmt->execute([
                $customer['CustomerCode'],
                $followupTime,
                "TEST: ติดตามลูกค้า {$customer['CustomerName']} - ตรวจสอบความต้องการ"
            ]);
            $created++;
            echo "✅ Created task for {$customer['CustomerCode']}<br>";
        } catch (Exception $e) {
            echo "❌ Failed for {$customer['CustomerCode']}: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
    echo "✅ Created <strong>$created</strong> tasks for today!<br>";
    echo "<a href='debug_daily_tasks.php'>🔄 Refresh to see results</a><br>";
    echo "<a href='pages/daily_tasks_demo.php'>📅 Go to Daily Tasks Page</a>";
    echo "</div>";
}

// Show customers instead
if (isset($_GET['show_customers'])) {
    echo "<h4>👥 Alternative: Show Taking Care Customers</h4>";
    echo "<p>Instead of tasks, show customers that need attention:</p>";
    
    $stmt = $pdo->prepare("SELECT CustomerCode, CustomerName, CustomerTel, CustomerStatus, CreatedDate FROM customers WHERE Sales = 'sales01' AND CartStatus = 'กำลังดูแล' ORDER BY CreatedDate DESC");
    $stmt->execute();
    $careCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'><th>Customer</th><th>Phone</th><th>Status</th><th>Created</th><th>Action</th></tr>";
    foreach ($careCustomers as $customer) {
        echo "<tr>";
        echo "<td><strong>{$customer['CustomerName']}</strong><br><small>{$customer['CustomerCode']}</small></td>";
        echo "<td>{$customer['CustomerTel']}</td>";
        echo "<td>{$customer['CustomerStatus']}</td>";
        echo "<td>" . date('d/m/Y', strtotime($customer['CreatedDate'])) . "</td>";
        echo "<td><a href='tel:{$customer['CustomerTel']}' style='color: green;'>📞 Call</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #cff4fc; padding: 10px; margin: 10px 0;'>";
    echo "💡 <strong>Suggestion:</strong> Modify daily_tasks_demo.php to show 'กำลังดูแล' customers instead of tasks<br>";
    echo "This would show all $careTakingCount customers that need attention today.";
    echo "</div>";
}
?>