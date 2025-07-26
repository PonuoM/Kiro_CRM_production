<?php
session_start();
require_once 'includes/functions.php';

// Only allow logged in users
if (!isLoggedIn()) {
    header('Location: pages/login.php');
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Order System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        button { background: #007bff; color: white; border: none; padding: 10px 20px; margin: 5px; cursor: pointer; }
        #result { margin-top: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>Debug Order System</h1>";
echo "<p class='info'>User: {$_SESSION['username']} ({$_SESSION['user_role']})</p>";

echo "<h2>Test Buttons</h2>";
echo "<button onclick=\"testProducts()\">Test Products API</button>";
echo "<button onclick=\"testOrderCreation()\">Test Order Creation</button>";
echo "<button onclick=\"showDiscountTest()\">Show Discount Test</button>";

echo "<div id='result'></div>";

echo "<script>
async function testProducts() {
    document.getElementById('result').innerHTML = 'Testing Products API...';
    
    try {
        const response = await fetch('api/products/list.php');
        const data = await response.json();
        
        document.getElementById('result').innerHTML = `
            <h3>Products API Test Result:</h3>
            <p><strong>Status:</strong> ${response.status}</p>
            <p><strong>Response:</strong></p>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    } catch (error) {
        document.getElementById('result').innerHTML = `
            <h3>Products API Test Error:</h3>
            <p class='error'>Error: ${error.message}</p>
        `;
    }
}

async function testOrderCreation() {
    document.getElementById('result').innerHTML = 'Testing Order Creation...';
    
    const orderData = {
        CustomerCode: 'TEST011',
        DocumentDate: new Date().toISOString().split('T')[0],
        PaymentMethod: 'เงินสด',
        products: [
            {
                code: 'F001',
                name: 'ปุ๋ยเคมี 16-16-16',
                quantity: 2,
                price: 18.50
            }
        ],
        discount_amount: 5.00,
        discount_percent: 0,
        discount_remarks: 'ทดสอบระบบ'
    };
    
    try {
        const response = await fetch('api/orders/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        
        document.getElementById('result').innerHTML = `
            <h3>Order Creation Test Result:</h3>
            <p><strong>Status:</strong> ${response.status}</p>
            <p><strong>Request Data:</strong></p>
            <pre>${JSON.stringify(orderData, null, 2)}</pre>
            <p><strong>Response:</strong></p>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        `;
    } catch (error) {
        document.getElementById('result').innerHTML = `
            <h3>Order Creation Test Error:</h3>
            <p class='error'>Error: ${error.message}</p>
        `;
    }
}

function showDiscountTest() {
    document.getElementById('result').innerHTML = `
        <h3>Discount Calculation Test:</h3>
        <div style='margin: 20px 0;'>
            <p><strong>Scenario 1:</strong> Subtotal 100 บาท, Discount 10%</p>
            <p>Expected: Discount Amount = 10 บาท, Final Total = 90 บาท</p>
            
            <p><strong>Scenario 2:</strong> Subtotal 100 บาท, Discount Amount = 15 บาท</p>
            <p>Expected: Discount Percent = 15%, Final Total = 85 บาท</p>
            
            <p><strong>JavaScript Functions Available:</strong></p>
            <p>- calculateDiscountFromPercent(): คำนวณจาก %</p>
            <p>- calculateFinalTotal(): คำนวณยอดสุทธิ</p>
        </div>
    `;
}
</script>";

echo "</body></html>";
?>