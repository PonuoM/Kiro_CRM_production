<!DOCTYPE html>
<html>
<head>
    <title>Tasks Table Check</title>
    <style>
        body { font-family: monospace; white-space: pre-wrap; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<?php
// Check tasks table structure and data
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='section'>";
    echo "=== TASKS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE tasks");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($structure);
    echo "</div>";
    
    echo "<div class='section'>";
    echo "=== TASKS TABLE DATA (LIMIT 5) ===\n";
    $stmt = $pdo->query("SELECT * FROM tasks LIMIT 5");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($data);
    echo "</div>";
    
    echo "<div class='section'>";
    echo "=== TASKS COUNT ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tasks");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total tasks: " . $count['total'] . "\n";
    echo "</div>";
    
    echo "<div class='section'>";
    echo "=== TODAY'S TASKS ===\n";
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE DATE(FollowupDate) = ? LIMIT 5");
    $stmt->execute([$today]);
    $todayTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($todayTasks);
    echo "</div>";
    
    echo "<div class='section'>";
    echo "=== SALES USERS ===\n";
    $stmt = $pdo->query("SELECT Username, FirstName, LastName, Role FROM users WHERE Role = 'Sales'");
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($sales);
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section' style='color: red;'>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "</div>";
}
?>
</body>
</html>