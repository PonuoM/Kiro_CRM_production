<?php
/**
 * Test script for Discount functionality in Order system
 * Tests the complete flow: API input -> Database -> Retrieval
 */

// Bypass login checks for testing
define('TESTING_MODE', true);

// Set up basic environment
session_start();
$_SESSION['username'] = 'test_user';
$_SESSION['role'] = 'admin';
$_SESSION['logged_in'] = true;

require_once 'includes/Order.php';

echo "<h1>🧪 ทดสอบระบบส่วนลดในคำสั่งซื้อ</h1>\n";

// Test 1: Create Order with Discount Amount
echo "<h2>Test 1: สร้าง Order พร้อมส่วนลด (บาท)</h2>\n";

$orderModel = new Order();

$testOrderData = [
    'CustomerCode' => 'TEST001',
    'DocumentDate' => date('Y-m-d H:i:s'),
    'PaymentMethod' => 'เงินสด',
    'products' => [
        [
            'code' => 'P001',
            'name' => 'ปุ๋ยทดสอบ',
            'quantity' => 2,
            'price' => 500.00
        ]
    ],
    'discount_amount' => 100.00,
    'discount_percent' => 10.00,
    'discount_remarks' => 'ส่วนลดทดสอบระบบ'
];

echo "ข้อมูลที่จะทดสอบ:\n";
echo "<pre>" . print_r($testOrderData, true) . "</pre>\n";

// Validate order data
$validationErrors = $orderModel->validateOrderData($testOrderData);
if (!empty($validationErrors)) {
    echo "<div style='color: red;'>❌ Validation Errors:</div>\n";
    foreach ($validationErrors as $error) {
        echo "<div style='color: red;'>- $error</div>\n";
    }
    exit;
}

echo "✅ ข้อมูล Order ผ่านการตรวจสอบ\n<br>";

// Create the order
$documentNo = $orderModel->createOrder($testOrderData);

if ($documentNo) {
    echo "<div style='color: green;'>✅ สร้าง Order สำเร็จ - DocumentNo: $documentNo</div>\n<br>";
    
    // Test 2: Retrieve the created order to verify discount fields
    echo "<h2>Test 2: ตรวจสอบข้อมูล Order ที่สร้าง</h2>\n";
    
    $createdOrder = $orderModel->findByDocumentNo($documentNo);
    
    if ($createdOrder) {
        echo "<div style='color: green;'>✅ ดึงข้อมูล Order สำเร็จ</div>\n<br>";
        
        echo "<h3>📊 รายละเอียด Order:</h3>\n";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Field</th><th>Value</th></tr>\n";
        echo "<tr><td>DocumentNo</td><td>{$createdOrder['DocumentNo']}</td></tr>\n";
        echo "<tr><td>CustomerCode</td><td>{$createdOrder['CustomerCode']}</td></tr>\n";
        echo "<tr><td>Products</td><td>{$createdOrder['Products']}</td></tr>\n";
        echo "<tr><td>Quantity</td><td>{$createdOrder['Quantity']}</td></tr>\n";
        echo "<tr><td>Price</td><td>{$createdOrder['Price']}</td></tr>\n";
        echo "<tr><td><strong>DiscountAmount</strong></td><td><strong>{$createdOrder['DiscountAmount']}</strong></td></tr>\n";
        echo "<tr><td><strong>DiscountPercent</strong></td><td><strong>{$createdOrder['DiscountPercent']}</strong></td></tr>\n";
        echo "<tr><td><strong>DiscountRemarks</strong></td><td><strong>{$createdOrder['DiscountRemarks']}</strong></td></tr>\n";
        echo "<tr><td>SubtotalAmount</td><td>{$createdOrder['SubtotalAmount']}</td></tr>\n";
        echo "<tr><td>CreatedDate</td><td>{$createdOrder['CreatedDate']}</td></tr>\n";
        echo "</table>\n<br>";
        
        // Test 3: Verify discount calculations
        echo "<h2>Test 3: ตรวจสอบการคำนวณส่วนลด</h2>\n";
        
        $expectedDiscountAmount = 100.00;
        $expectedDiscountPercent = 10.00;
        $expectedDiscountRemarks = 'ส่วนลดทดสอบระบบ';
        
        $actualDiscountAmount = (float)$createdOrder['DiscountAmount'];
        $actualDiscountPercent = (float)$createdOrder['DiscountPercent'];
        $actualDiscountRemarks = $createdOrder['DiscountRemarks'];
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Test</th><th>Expected</th><th>Actual</th><th>Result</th></tr>\n";
        
        // Test discount amount
        $discountAmountTest = ($actualDiscountAmount == $expectedDiscountAmount);
        echo "<tr><td>Discount Amount</td><td>$expectedDiscountAmount</td><td>$actualDiscountAmount</td>";
        echo "<td style='color: " . ($discountAmountTest ? 'green' : 'red') . ";'>" . ($discountAmountTest ? '✅ PASS' : '❌ FAIL') . "</td></tr>\n";
        
        // Test discount percent
        $discountPercentTest = ($actualDiscountPercent == $expectedDiscountPercent);
        echo "<tr><td>Discount Percent</td><td>$expectedDiscountPercent</td><td>$actualDiscountPercent</td>";
        echo "<td style='color: " . ($discountPercentTest ? 'green' : 'red') . ";'>" . ($discountPercentTest ? '✅ PASS' : '❌ FAIL') . "</td></tr>\n";
        
        // Test discount remarks
        $discountRemarksTest = ($actualDiscountRemarks == $expectedDiscountRemarks);
        echo "<tr><td>Discount Remarks</td><td>$expectedDiscountRemarks</td><td>$actualDiscountRemarks</td>";
        echo "<td style='color: " . ($discountRemarksTest ? 'green' : 'red') . ";'>" . ($discountRemarksTest ? '✅ PASS' : '❌ FAIL') . "</td></tr>\n";
        
        echo "</table>\n<br>";
        
        // Overall test result
        $allTestsPass = $discountAmountTest && $discountPercentTest && $discountRemarksTest;
        
        if ($allTestsPass) {
            echo "<div style='color: green; font-size: 18px; font-weight: bold;'>🎉 ทุกการทดสอบผ่าน! ระบบส่วนลดทำงานถูกต้อง</div>\n";
        } else {
            echo "<div style='color: red; font-size: 18px; font-weight: bold;'>❌ การทดสอบไม่ผ่าน! ตรวจสอบระบบส่วนลด</div>\n";
        }
        
    } else {
        echo "<div style='color: red;'>❌ ไม่สามารถดึงข้อมูล Order ที่สร้างได้</div>\n";
    }
    
} else {
    echo "<div style='color: red;'>❌ ไม่สามารถสร้าง Order ได้</div>\n";
    
    // Show last error
    $error_info = $orderModel->getLastError();
    if ($error_info) {
        echo "<div style='color: red;'>DB Error: " . print_r($error_info, true) . "</div>\n";
    }
}

echo "<hr>\n";
echo "<h2>📋 สรุปการทดสอบ</h2>\n";
echo "<p>🔧 ส่วนประกอบที่ทดสอบ:</p>\n";
echo "<ul>\n";
echo "<li>✅ Order.php Model - การสร้าง Order พร้อม Discount Fields</li>\n";
echo "<li>✅ Database Schema - Discount Columns ใน orders table</li>\n";
echo "<li>✅ Data Validation - การตรวจสอบข้อมูล Discount</li>\n";
echo "<li>✅ Data Persistence - การบันทึกและดึงข้อมูล Discount</li>\n";
echo "</ul>\n";

echo "<p><strong>ข้อแนะนำ:</strong> หากการทดสอบผ่านทุกข้อ แสดงว่าระบบพร้อมใช้งาน สามารถทดสอบผ่าน UI ได้แล้ว</p>\n";
?>