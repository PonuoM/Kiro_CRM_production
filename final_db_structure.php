<?php
/**
 * Final Database Structure Checker
 * ตรวจสอบโครงสร้างฐานข้อมูล - แก้ไขแล้ว
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Structure - แก้ไขแล้ว</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #27ae60; margin-top: 30px; }
        h2.view-title { color: #8e44ad; }
        .table-info { background: #ecf0f1; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .view-info { background: #f4ecf7; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #8e44ad; }
        .column-list { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .column { display: inline-block; margin: 3px 8px; padding: 4px 8px; background: #e9ecef; border-radius: 3px; font-size: 12px; }
        .primary-key { background: #d4edda !important; border: 1px solid #c3e6cb; }
        .foreign-key { background: #fff3cd !important; border: 1px solid #ffeaa7; }
        .view-column { background: #e8d5f0 !important; }
        .summary { background: #d1ecf1; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #bee5eb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { flex: 1; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; font-size: 14px; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h1>🗄️ Database Structure Report (แก้ไขแล้ว)</h1>";
    
    // Get database name
    $dbName = $connection->query("SELECT DATABASE() as db_name")->fetch()['db_name'];
    echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูล: <strong>{$dbName}</strong> สำเร็จ</div>";
    
    // Get tables and views using INFORMATION_SCHEMA
    $allObjects = $connection->query("
        SELECT TABLE_NAME, TABLE_TYPE 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = '{$dbName}' 
        ORDER BY TABLE_TYPE DESC, TABLE_NAME
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $tables = [];
    $views = [];
    
    foreach ($allObjects as $object) {
        if ($object['TABLE_TYPE'] === 'BASE TABLE') {
            $tables[] = $object['TABLE_NAME'];
        } elseif ($object['TABLE_TYPE'] === 'VIEW') {
            $views[] = $object['TABLE_NAME'];
        }
    }
    
    $totalTables = count($tables);
    $totalViews = count($views);
    $totalColumns = 0;
    $totalIndexes = 0;
    
    echo "<div class='stats'>
        <div class='stat-box'>
            <div class='stat-number'>{$totalTables}</div>
            <div class='stat-label'>ตาราง</div>
        </div>
        <div class='stat-box'>
            <div class='stat-number'>{$totalViews}</div>
            <div class='stat-label'>Views</div>
        </div>
        <div class='stat-box'>
            <div class='stat-number' id='total-columns'>-</div>
            <div class='stat-label'>คอลัมน์</div>
        </div>
        <div class='stat-box'>
            <div class='stat-number' id='total-indexes'>-</div>
            <div class='stat-label'>ดัชนี</div>
        </div>
    </div>";
    
    // Process Tables
    foreach ($tables as $table) {
        echo "<h2>📋 ตาราง: {$table}</h2>";
        
        // Get table info
        $tableInfo = $connection->query("SHOW TABLE STATUS LIKE '{$table}'")->fetch();
        $rowCount = $tableInfo['Rows'] ?? 0;
        $engine = $tableInfo['Engine'] ?? 'Unknown';
        $collation = $tableInfo['Collation'] ?? 'Unknown';
        
        echo "<div class='table-info'>
            <strong>จำนวนแถว:</strong> {$rowCount} | 
            <strong>Engine:</strong> {$engine} | 
            <strong>Collation:</strong> {$collation}
        </div>";
        
        // Get columns
        $columns = $connection->query("SHOW FULL COLUMNS FROM {$table}")->fetchAll();
        $tableColumnCount = count($columns);
        $totalColumns += $tableColumnCount;
        
        echo "<div class='column-list'>";
        echo "<strong>คอลัมน์ ({$tableColumnCount}):</strong><br>";
        
        foreach ($columns as $column) {
            $field = $column['Field'];
            $type = $column['Type'];
            $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
            $key = $column['Key'];
            $default = $column['Default'] ? "DEFAULT: {$column['Default']}" : '';
            $extra = $column['Extra'] ? $column['Extra'] : '';
            
            $cssClass = 'column';
            if ($key === 'PRI') {
                $cssClass .= ' primary-key';
                $key = 'PK';
            } elseif ($key === 'MUL') {
                $cssClass .= ' foreign-key';
                $key = 'FK';
            }
            
            $columnInfo = "{$field} ({$type}) {$null}";
            if ($key) $columnInfo .= " [{$key}]";
            if ($extra) $columnInfo .= " {$extra}";
            if ($default) $columnInfo .= " {$default}";
            
            echo "<span class='{$cssClass}'>{$columnInfo}</span>";
        }
        echo "</div>";
        
        // Get indexes
        $indexes = $connection->query("SHOW INDEX FROM {$table}")->fetchAll();
        $tableIndexCount = count(array_unique(array_column($indexes, 'Key_name')));
        $totalIndexes += $tableIndexCount;
        
        if (!empty($indexes)) {
            echo "<div class='column-list'>";
            echo "<strong>ดัชนี ({$tableIndexCount}):</strong><br>";
            
            $indexGroups = [];
            foreach ($indexes as $index) {
                $keyName = $index['Key_name'];
                $columnName = $index['Column_name'];
                $unique = $index['Non_unique'] == 0 ? 'UNIQUE' : '';
                
                if (!isset($indexGroups[$keyName])) {
                    $indexGroups[$keyName] = [
                        'columns' => [],
                        'unique' => $unique
                    ];
                }
                $indexGroups[$keyName]['columns'][] = $columnName;
            }
            
            foreach ($indexGroups as $indexName => $indexData) {
                $columns = implode(', ', $indexData['columns']);
                $unique = $indexData['unique'] ? " ({$indexData['unique']})" : '';
                echo "<span class='column'>{$indexName}: {$columns}{$unique}</span>";
            }
            echo "</div>";
        }
        
        // Get foreign keys
        try {
            $foreignKeys = $connection->query("
                SELECT 
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = '{$dbName}' 
                AND TABLE_NAME = '{$table}' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchAll();
            
            if (!empty($foreignKeys)) {
                echo "<div class='column-list'>";
                echo "<strong>Foreign Keys:</strong><br>";
                foreach ($foreignKeys as $fk) {
                    echo "<span class='column foreign-key'>{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</span>";
                }
                echo "</div>";
            }
        } catch (Exception $e) {
            // Ignore foreign key check errors
        }
        
        echo "<hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>";
    }
    
    // Process Views
    if (!empty($views)) {
        echo "<h1 style='color: #8e44ad; margin-top: 40px;'>👁️ Database Views</h1>";
        
        foreach ($views as $view) {
            echo "<h2 class='view-title'>👁️ View: {$view}</h2>";
            
            // Get view columns
            $viewColumns = $connection->query("SHOW FULL COLUMNS FROM {$view}")->fetchAll();
            $viewColumnCount = count($viewColumns);
            $totalColumns += $viewColumnCount;
            
            echo "<div class='view-info'>
                <strong>ประเภท:</strong> Database View | 
                <strong>คอลัมน์:</strong> {$viewColumnCount} คอลัมน์
            </div>";
            
            echo "<div class='column-list'>";
            echo "<strong>คอลัมน์ ({$viewColumnCount}):</strong><br>";
            
            foreach ($viewColumns as $column) {
                $field = $column['Field'];
                $type = $column['Type'];
                $null = $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                $comment = $column['Comment'] ? " -- {$column['Comment']}" : '';
                
                $columnInfo = "{$field} ({$type}) {$null}{$comment}";
                echo "<span class='column view-column'>{$columnInfo}</span>";
            }
            echo "</div>";
            
            // พิเศษ: แสดงความหมายของ customer_intelligence_summary
            if ($view === 'customer_intelligence_summary') {
                echo "<div class='view-info'>
                    <strong>💡 ความหมายของคอลัมน์:</strong><br>
                    • <strong>CustomerGrade:</strong> เกรดลูกค้า (A, B, C, D, F)<br>
                    • <strong>CustomerTemperature:</strong> ระดับความสนใจ (HOT, WARM, COLD, FROZEN)<br>
                    • <strong>customer_count:</strong> จำนวนลูกค้าในแต่ละกลุ่ม<br>
                    • <strong>avg_purchase:</strong> ยอดซื้อเฉลี่ยของกลุ่ม<br>
                    • <strong>total_revenue:</strong> รายได้รวมของกลุ่ม
                </div>";
            }
            
            echo "<hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>";
        }
    }
    
    echo "<div class='summary'>
        <h3>📊 สรุปโครงสร้างฐานข้อมูล</h3>
        <p><strong>ฐานข้อมูล:</strong> {$dbName}</p>
        <p><strong>จำนวนตาราง:</strong> {$totalTables} ตาราง</p>
        <p><strong>จำนวน Views:</strong> {$totalViews} views</p>
        <p><strong>จำนวนคอลัมน์รวม:</strong> {$totalColumns} คอลัมน์</p>
        <p><strong>จำนวนดัชนีรวม:</strong> {$totalIndexes} ดัชนี</p>
        <p><strong>วันที่ตรวจสอบ:</strong> " . date('Y-m-d H:i:s') . "</p>
    </div>";
    
    echo "<script>
        document.getElementById('total-columns').textContent = '{$totalColumns}';
        document.getElementById('total-indexes').textContent = '{$totalIndexes}';
    </script>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . $e->getMessage() . "</div>";
}

echo "</div>
</body>
</html>";
?>