<?php
/**
 * Simple Database Structure Checker
 * ตรวจสอบโครงสร้างฐานข้อมูลแบบง่าย
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Simple Database Check</title>
    <style>
        body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; text-align: center; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #27ae60; margin-top: 30px; }
        .table-info { background: #ecf0f1; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .view-info { background: #f4ecf7; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #8e44ad; }
        .column-list { background: #f8f9fa; padding: 10px; margin: 5px 0; border-radius: 3px; }
        .column { display: inline-block; margin: 3px 8px; padding: 4px 8px; background: #e9ecef; border-radius: 3px; font-size: 12px; }
        .primary-key { background: #d4edda !important; border: 1px solid #c3e6cb; }
        .foreign-key { background: #fff3cd !important; border: 1px solid #ffeaa7; }
        .view-column { background: #e8d5f0 !important; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .debug { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h1>🗄️ Simple Database Structure Check</h1>";
    
    // Get database name
    $dbName = $connection->query("SELECT DATABASE() as db_name")->fetch()['db_name'];
    echo "<div class='success'>✅ เชื่อมต่อฐานข้อมูล: <strong>{$dbName}</strong> สำเร็จ</div>";
    
    // Debug: Show what SHOW TABLES returns
    echo "<div class='debug'><strong>Debug:</strong> ตรวจสอบคำสั่ง SHOW TABLES...</div>";
    
    $tablesRaw = $connection->query("SHOW TABLES")->fetchAll();
    echo "<div class='debug'>ผลลัพธ์ SHOW TABLES: " . count($tablesRaw) . " รายการ</div>";
    
    // Debug: Show raw data
    echo "<div class='debug'>Raw data: " . print_r($tablesRaw, true) . "</div>";
    
    $allTableNames = [];
    foreach ($tablesRaw as $row) {
        // Try different ways to get the table name
        if (isset($row[0]) && !empty($row[0])) {
            $allTableNames[] = $row[0];
        } elseif (isset($row['Tables_in_' . $dbName]) && !empty($row['Tables_in_' . $dbName])) {
            $allTableNames[] = $row['Tables_in_' . $dbName];
        } else {
            // Check all keys in the row
            foreach ($row as $key => $value) {
                if (!empty($value) && is_string($value)) {
                    $allTableNames[] = $value;
                    break;
                }
            }
        }
    }
    
    // Remove duplicates and empty values
    $allTableNames = array_unique(array_filter($allTableNames));
    
    echo "<div class='debug'>รายชื่อที่แยกได้: " . implode(', ', $allTableNames) . "</div>";
    
    $totalTables = 0;
    $totalViews = 0;
    $totalColumns = 0;
    
    foreach ($allTableNames as $tableName) {
        echo "<h2>📋 {$tableName}</h2>";
        
        try {
            // Get table/view info
            $tableStatus = $connection->query("SHOW TABLE STATUS LIKE '{$tableName}'")->fetch();
            
            $isView = false;
            if (!$tableStatus || $tableStatus['Engine'] === null) {
                // Might be a view
                echo "<div class='view-info'>
                    <strong>ประเภท:</strong> Database View (สันนิษฐาน)
                </div>";
                $isView = true;
                $totalViews++;
            } else {
                $rowCount = $tableStatus['Rows'] ?? 0;
                $engine = $tableStatus['Engine'] ?? 'Unknown';
                $collation = $tableStatus['Collation'] ?? 'Unknown';
                
                echo "<div class='table-info'>
                    <strong>ประเภท:</strong> Table | 
                    <strong>จำนวนแถว:</strong> {$rowCount} | 
                    <strong>Engine:</strong> {$engine} | 
                    <strong>Collation:</strong> {$collation}
                </div>";
                $totalTables++;
            }
            
            // Get columns
            $columns = $connection->query("SHOW FULL COLUMNS FROM {$tableName}")->fetchAll();
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
                
                $cssClass = $isView ? 'column view-column' : 'column';
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
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ เกิดข้อผิดพลาดกับ {$tableName}: " . $e->getMessage() . "</div>";
        }
        
        echo "<hr style='margin: 20px 0; border: none; border-top: 1px solid #ddd;'>";
    }
    
    echo "<div class='success'>
        <h3>📊 สรุป</h3>
        <p><strong>ฐานข้อมูล:</strong> {$dbName}</p>
        <p><strong>Tables:</strong> {$totalTables}</p>
        <p><strong>Views:</strong> {$totalViews}</p>
        <p><strong>คอลัมน์รวม:</strong> {$totalColumns}</p>
        <p><strong>รายการทั้งหมด:</strong> " . count($allTableNames) . "</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>เกิดข้อผิดพลาด:</strong> " . $e->getMessage() . "</div>";
}

echo "</div>
</body>
</html>";
?>