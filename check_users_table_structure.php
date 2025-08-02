<?php
/**
 * Check Users Table Structure
 * Tool to examine the actual structure of users table
 */

require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Check Users Table Structure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🔍 Users Table Structure</h2>
        
        <?php
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();
            
            // Show table structure
            echo "<h4>📋 Table Structure:</h4>";
            $stmt = $pdo->prepare("DESCRIBE users");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
            echo '<tbody>';
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td><strong>{$column['Field']}</strong></td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            echo '</tbody></table></div>';
            
            // Show sample data
            echo "<h4>📊 Sample Data:</h4>";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'sales' AND status = 'active' LIMIT 3");
            $stmt->execute();
            $sampleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($sampleUsers) > 0) {
                echo '<div class="table-responsive">';
                echo '<table class="table table-bordered">';
                echo '<thead><tr>';
                foreach (array_keys($sampleUsers[0]) as $key) {
                    echo "<th>$key</th>";
                }
                echo '</tr></thead><tbody>';
                
                foreach ($sampleUsers as $user) {
                    echo '<tr>';
                    foreach ($user as $value) {
                        echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                    }
                    echo '</tr>';
                }
                echo '</tbody></table></div>';
            } else {
                echo '<div class="alert alert-warning">No sales users found</div>';
            }
            
            // Show all users count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total, role, status FROM users GROUP BY role, status");
            $stmt->execute();
            $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>👥 Users Count by Role & Status:</h4>";
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm">';
            echo '<thead><tr><th>Role</th><th>Status</th><th>Count</th></tr></thead><tbody>';
            foreach ($counts as $count) {
                echo "<tr><td>{$count['role']}</td><td>{$count['status']}</td><td>{$count['total']}</td></tr>";
            }
            echo '</tbody></table></div>';
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>