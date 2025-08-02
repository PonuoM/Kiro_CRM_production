<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 ตรวจสอบ Orders Table จริง</h2>";
    
    // 1. ตรวจสอบ structure ของ orders
    echo "<h3>1. Orders Table Structure</h3>";
    $result = $conn->query("DESCRIBE orders");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>";
    echo "</tr>";
    
    $columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    echo "<h3>2. Available Columns</h3>";
    echo "<div style='background: #e6f3ff; padding: 10px;'>";
    echo "<strong>Columns found:</strong> " . implode(', ', $columns);
    echo "</div><br>";
    
    // 2. ตรวจสอบข้อมูลตัวอย่าง
    echo "<h3>3. ข้อมูลตัวอย่าง 5 รายการแรก</h3>";
    $columnList = implode(', ', $columns);
    $result = $conn->query("SELECT {$columnList} FROM orders LIMIT 5");
    
    if ($result->rowCount() > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        foreach ($columns as $col) {
            echo "<th>{$col}</th>";
        }
        echo "</tr>";
        
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            foreach ($columns as $col) {
                $value = $row[$col] ?? '';
                if (strlen($value) > 30) {
                    $value = substr($value, 0, 30) . '...';
                }
                echo "<td>{$value}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. ตรวจสอบว่ามี product-related columns อะไรบ้าง
    echo "<h3>4. Product-Related Information</h3>";
    $productColumns = array_filter($columns, function($col) {
        return stripos($col, 'product') !== false || 
               stripos($col, 'item') !== false || 
               stripos($col, 'description') !== false ||
               stripos($col, 'detail') !== false;
    });
    
    if (!empty($productColumns)) {
        echo "<div style='background: #d4edda; padding: 10px;'>";
        echo "<strong>✅ Found product columns:</strong> " . implode(', ', $productColumns);
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px;'>";
        echo "<strong>❌ No obvious product columns found</strong>";
        echo "</div>";
    }
    
    // 4. ตรวจสอบว่าข้อมูลสินค้าอยู่ใน column ไหน
    echo "<h3>5. ค้นหาข้อมูलสินค้าใน Orders</h3>";
    $searchColumns = ['OrderDetails', 'Description', 'Notes', 'Items', 'ProductInfo', 'LineItems'];
    
    foreach ($searchColumns as $searchCol) {
        if (in_array($searchCol, $columns)) {
            echo "<h4>Found column: {$searchCol}</h4>";
            $result = $conn->query("SELECT DocumentNo, {$searchCol} FROM orders WHERE {$searchCol} IS NOT NULL AND {$searchCol} != '' LIMIT 3");
            if ($result->rowCount() > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>DocumentNo</th><th>{$searchCol}</th></tr>";
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>{$row['DocumentNo']}</td>";
                    echo "<td>" . htmlspecialchars(substr($row[$searchCol], 0, 100)) . "</td>";
                    echo "</tr>";
                }
                echo "</table><br>";
            }
        }
    }
    
    // 5. Count total orders
    echo "<h3>6. Order Statistics</h3>";
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    $total = $result->fetch(PDO::FETCH_ASSOC);
    echo "<div style='background: #fff3cd; padding: 10px;'>";
    echo "<strong>📊 Total Orders:</strong> {$total['total']}";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>