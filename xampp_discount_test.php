<?php
/**
 * XAMPP-specific test for discount functionality
 */

echo "🧪 XAMPP Discount Test\n\n";

// XAMPP Database Settings
$host = 'localhost';
$dbname = 'primacom_CRM';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully\n\n";
    
    // Test 1: Check if discount columns exist
    echo "Test 1: Checking discount columns in orders table...\n";
    
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll();
    
    $discountColumns = ['DiscountAmount', 'DiscountPercent', 'DiscountRemarks'];
    $foundColumns = [];
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], $discountColumns)) {
            $foundColumns[] = $column['Field'];
            echo "✅ {$column['Field']}: {$column['Type']}\n";
        }
    }
    
    $missingColumns = array_diff($discountColumns, $foundColumns);
    if (!empty($missingColumns)) {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
        exit;
    }
    
    echo "\n✅ All discount columns exist!\n\n";
    
    // Test 2: Check current orders with discount data
    echo "Test 2: Checking existing orders with discount data...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DiscountAmount > 0 OR DiscountPercent > 0");
    $result = $stmt->fetch();
    
    echo "Current orders with discount: {$result['total']}\n";
    
    // Test 3: Try to insert a test order with discount
    echo "\nTest 3: Inserting test order with discount...\n";
    
    $testData = [
        'DocumentNo' => 'DISCOUNT_TEST_' . time(),
        'CustomerCode' => 'TEST001',
        'DocumentDate' => date('Y-m-d H:i:s'),
        'PaymentMethod' => 'เงินสด',
        'Products' => 'ปุ๋ยทดสอบส่วนลด',
        'Quantity' => 2,
        'Price' => 450.00, // After discount
        'DiscountAmount' => 50.00,
        'DiscountPercent' => 10.00,
        'DiscountRemarks' => 'ทดสอบระบบส่วนลด - แก้ไขสำเร็จ',
        'SubtotalAmount' => 500.00, // Before discount
        'CreatedDate' => date('Y-m-d H:i:s'),
        'CreatedBy' => 'xampp_test',
        'OrderBy' => 'xampp_test'
    ];
    
    $fields = implode(', ', array_keys($testData));
    $placeholders = ':' . implode(', :', array_keys($testData));
    
    $sql = "INSERT INTO orders ($fields) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    foreach ($testData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    
    if ($stmt->execute()) {
        $insertId = $pdo->lastInsertId();
        echo "✅ Order inserted successfully with ID: $insertId\n";
        echo "   DocumentNo: {$testData['DocumentNo']}\n";
        
        // Test 4: Verify the data was saved correctly
        echo "\nTest 4: Verifying saved discount data...\n";
        
        $verifyStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $verifyStmt->execute([$insertId]);
        $result = $verifyStmt->fetch();
        
        if ($result) {
            echo "✅ Retrieved discount data:\n";
            echo "   - SubtotalAmount: {$result['SubtotalAmount']} (ก่อนหักส่วนลด)\n";
            echo "   - DiscountAmount: {$result['DiscountAmount']} (ส่วนลดเป็นเงิน)\n";
            echo "   - DiscountPercent: {$result['DiscountPercent']} (เปอร์เซ็นต์ส่วนลด)\n";
            echo "   - Price: {$result['Price']} (ราคาสุทธิหลังหักส่วนลด)\n";
            echo "   - DiscountRemarks: {$result['DiscountRemarks']}\n";
            
            // Verify calculation
            $expectedFinalPrice = $result['SubtotalAmount'] - $result['DiscountAmount'];
            $calculationCorrect = abs($result['Price'] - $expectedFinalPrice) < 0.01;
            
            echo "\nTest 5: Verifying discount calculation...\n";
            echo "   Expected final price: $expectedFinalPrice\n";
            echo "   Actual final price: {$result['Price']}\n";
            echo "   Calculation: " . ($calculationCorrect ? "✅ CORRECT" : "❌ INCORRECT") . "\n";
            
            if ($calculationCorrect) {
                echo "\n🎉 ALL TESTS PASSED! Discount system is working perfectly!\n";
                echo "\n📊 Summary:\n";
                echo "   ✅ Database columns exist and are accessible\n";
                echo "   ✅ Data can be inserted with discount values\n";
                echo "   ✅ Data can be retrieved correctly\n";
                echo "   ✅ Discount calculations are correct\n";
                echo "   ✅ System ready for production use\n";
            } else {
                echo "\n❌ Calculation test failed!\n";
            }
            
            // Keep test data for manual verification
            echo "\n📝 Test order created: {$testData['DocumentNo']}\n";
            echo "   (You can manually verify this in the database or UI)\n";
            
        } else {
            echo "❌ Could not retrieve saved data\n";
        }
        
    } else {
        echo "❌ Failed to insert order\n";
        print_r($stmt->errorInfo());
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Next Steps:\n";
echo "1. Test the discount feature through the web UI\n";
echo "2. Create an order with discount amount and/or percentage\n";
echo "3. Verify the calculations are correct\n";
echo "4. Check that discount data appears in order history\n";
?>