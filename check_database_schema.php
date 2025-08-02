<?php
/**
 * ตรวจสอบโครงสร้าง Database ปัจจุบัน
 */

require_once 'config/database.php';

echo "🔍 Database Schema Analysis\n";
echo "==========================\n\n";

try {
    // XAMPP Database Settings
    $host = 'localhost';
    $dbname = 'primacom_CRM';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: $dbname\n\n";
    
    // 1. ตรวจสอบตาราง orders ปัจจุบัน
    echo "📋 Current Orders Table Structure:\n";
    echo "--------------------------------\n";
    
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll();
    
    $existingColumns = [];
    foreach ($columns as $column) {
        $existingColumns[] = $column['Field'];
        printf("%-20s %-15s %-8s %-8s %-15s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'], 
            $column['Key'], 
            $column['Default'], 
            $column['Extra']
        );
    }
    
    echo "\n";
    
    // 2. ตรวจสอบว่ามี order_items table หรือไม่
    echo "🔍 Checking for order_items table:\n";
    echo "--------------------------------\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'order_items'");
    $orderItemsExists = $stmt->rowCount() > 0;
    
    if ($orderItemsExists) {
        echo "✅ order_items table EXISTS\n";
        
        echo "\n📋 Current Order_Items Table Structure:\n";
        echo "-------------------------------------\n";
        
        $stmt = $pdo->query("DESCRIBE order_items");
        $itemColumns = $stmt->fetchAll();
        
        foreach ($itemColumns as $column) {
            printf("%-20s %-15s %-8s %-8s %-15s %s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key'], 
                $column['Default'], 
                $column['Extra']
            );
        }
    } else {
        echo "❌ order_items table does NOT exist\n";
    }
    
    echo "\n";
    
    // 3. วิเคราะห์ columns ที่ต้องการ
    echo "📊 Required vs Existing Columns Analysis:\n";
    echo "---------------------------------------\n";
    
    // Columns ที่ต้องการใน orders table
    $requiredOrdersColumns = [
        'id' => 'int(11) AUTO_INCREMENT PRIMARY KEY',
        'DocumentNo' => 'varchar(50) NOT NULL UNIQUE',
        'CustomerCode' => 'varchar(50) NOT NULL',
        'DocumentDate' => 'datetime NOT NULL',
        'PaymentMethod' => 'varchar(200)',
        'Products' => 'varchar(500) (สำหรับ backward compatibility)',
        'Quantity' => 'decimal(10,2) (รวมทั้งหมด)',
        'Price' => 'decimal(10,2) (ยอดสุทธิหลังส่วนลด)',
        'OrderBy' => 'varchar(50)',
        'CreatedDate' => 'datetime DEFAULT CURRENT_TIMESTAMP',
        'CreatedBy' => 'varchar(50)',
        'DiscountAmount' => 'decimal(10,2) DEFAULT 0.00',
        'DiscountPercent' => 'decimal(5,2) DEFAULT 0.00',
        'DiscountRemarks' => 'varchar(500)',
        'ProductsDetail' => 'longtext (JSON)',
        'SubtotalAmount' => 'decimal(10,2) DEFAULT 0.00',
        'TotalItems' => 'int DEFAULT 0 (จำนวนรายการสินค้า - ต้องเพิ่มใหม่)'
    ];
    
    echo "Orders Table Analysis:\n";
    foreach ($requiredOrdersColumns as $colName => $description) {
        $exists = in_array($colName, $existingColumns);
        echo ($exists ? "✅" : "❌") . " $colName: $description\n";
    }
    
    echo "\n";
    
    // 4. ตรวจสอบ Case Sensitivity
    echo "🔤 Case Sensitivity Check:\n";
    echo "-------------------------\n";
    
    // ตรวจสอบว่า MySQL เป็น case sensitive หรือไม่
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'lower_case_table_names'");
    $result = $stmt->fetch();
    
    echo "lower_case_table_names: " . ($result['Value'] ?? 'unknown') . "\n";
    
    if ($result['Value'] == '0') {
        echo "⚠️  Case SENSITIVE - ต้องระวังตัวพิมพ์เล็กพิมพ์ใหญ่\n";
    } else {
        echo "✅ Case INSENSITIVE - ไม่ต้องกังวลเรื่องตัวพิมพ์\n";
    }
    
    echo "\n";
    
    // 5. ตัวอย่างข้อมูลปัจจุบัน
    echo "📝 Sample Current Data (last 3 orders):\n";
    echo "--------------------------------------\n";
    
    $stmt = $pdo->query("SELECT DocumentNo, CustomerCode, Products, Quantity, Price, DiscountAmount, SubtotalAmount FROM orders ORDER BY CreatedDate DESC LIMIT 3");
    $samples = $stmt->fetchAll();
    
    if ($samples) {
        printf("%-20s %-12s %-30s %-8s %-8s %-8s %-8s\n", 
            'DocumentNo', 'Customer', 'Products', 'Qty', 'Price', 'Discount', 'Subtotal');
        echo str_repeat('-', 100) . "\n";
        
        foreach ($samples as $sample) {
            printf("%-20s %-12s %-30s %-8s %-8s %-8s %-8s\n", 
                $sample['DocumentNo'], 
                $sample['CustomerCode'], 
                substr($sample['Products'], 0, 30), 
                $sample['Quantity'], 
                $sample['Price'], 
                $sample['DiscountAmount'], 
                $sample['SubtotalAmount']
            );
        }
    } else {
        echo "No data found\n";
    }
    
    echo "\n";
    
    // 6. สรุปและแนะนำ
    echo "📋 Summary & Recommendations:\n";
    echo "============================\n";
    
    $missingColumns = [];
    if (!in_array('TotalItems', $existingColumns)) {
        $missingColumns[] = 'TotalItems';
    }
    
    if (!empty($missingColumns)) {
        echo "❌ Missing columns in orders table:\n";
        foreach ($missingColumns as $col) {
            echo "   - $col\n";
        }
    } else {
        echo "✅ All required columns exist in orders table\n";
    }
    
    if (!$orderItemsExists) {
        echo "❌ order_items table needs to be created\n";
    } else {
        echo "✅ order_items table exists\n";
    }
    
    echo "\n🎯 Next Steps:\n";
    echo "1. Create order_items table if not exists\n";
    echo "2. Add missing columns to orders table\n";
    echo "3. Create migration script to convert existing data\n";
    echo "4. Update API and Models\n";
    echo "5. Test the new structure\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>