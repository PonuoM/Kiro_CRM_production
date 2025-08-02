<?php
echo "<h2>🔍 Debug Input Data จริง</h2>";

echo "<h3>1. JavaScript สำหรับตรวจสอบข้อมูลที่ส่งไป</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console ก่อน Submit Order
const originalFetch = window.fetch;
window.fetch = function(url, options) {
    if (url.includes('create.php')) {
        console.log('🚨 INTERCEPTING API CALL');
        console.log('URL:', url);
        console.log('Options:', options);
        
        if (options.body) {
            try {
                const data = JSON.parse(options.body);
                console.log('📤 REQUEST BODY:');
                console.log('CustomerCode:', data.CustomerCode);
                console.log('products:', data.products);
                console.log('total_quantity:', data.total_quantity, typeof data.total_quantity);
                console.log('subtotal_amount:', data.subtotal_amount, typeof data.subtotal_amount);
                console.log('discount_amount:', data.discount_amount, typeof data.discount_amount);
                console.log('discount_percent:', data.discount_percent, typeof data.discount_percent);
                console.log('total_amount:', data.total_amount, typeof data.total_amount);
                
                // ตรวจสอบว่า subtotal_amount เป็น empty string ไหม
                if (data.subtotal_amount === '' || data.subtotal_amount === null || data.subtotal_amount === undefined) {
                    console.log('🚨 PROBLEM FOUND: subtotal_amount is empty!');
                }
            } catch (e) {
                console.log('Error parsing body:', e);
            }
        }
    }
    return originalFetch.apply(this, arguments);
};

console.log('✅ Fetch interceptor set');";
echo "</pre>";
echo "</div>";

echo "<h3>2. Debug customer-detail.js submitOrder Function</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console เพื่อดู submitOrder function จริง
console.log('📋 CHECKING submitOrder FUNCTION');
if (window.customerDetail && window.customerDetail.submitOrder) {
    console.log('Function found:', typeof window.customerDetail.submitOrder);
    console.log('Function code:', window.customerDetail.submitOrder.toString());
} else {
    console.log('❌ submitOrder function not found');
}

// หา elements จริง
console.log('📋 CHECKING ELEMENTS');
const elements = {
    totalQty: document.getElementById('total-quantity'),
    subtotal: document.getElementById('subtotal-amount'),
    discount: document.getElementById('discount-amount'),
    percent: document.getElementById('discount-percent'),
    total: document.getElementById('total-amount')
};

Object.keys(elements).forEach(key => {
    const el = elements[key];
    if (el) {
        console.log(`✅ \${key}:`, {
            id: el.id,
            value: el.value,
            type: typeof el.value,
            empty: el.value === '',
            null: el.value === null,
            undefined: el.value === undefined
        });
    } else {
        console.log(`❌ \${key}: NOT FOUND`);
    }
});";
echo "</pre>";
echo "</div>";

echo "<h3>3. ตรวจสอบ Apache Error Log</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<p>หา log message เหล่านี้ใน Apache Error Log:</p>";
echo "<pre style='background: #333; color: #fff; padding: 10px;'>";
echo "=== FRONTEND DIRECT MAPPING ===
Frontend Subtotal Amount: [ค่าใดค่าหนึ่ง]

หากเห็น:
- Frontend Subtotal Amount: 260 → ส่งถูกต้อง
- Frontend Subtotal Amount: 376.92 → Frontend ส่งผิด
- Frontend Subtotal Amount: 0 หรือ empty → Element ไม่มีค่า";
echo "</pre>";
echo "</div>";

echo "<h3>4. ตรวจสอบการคำนวณใน JavaScript</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console เพื่อตรวจสอบการคำนวณ
function debugCalculation() {
    console.log('🧮 CALCULATION DEBUG');
    
    const products = document.querySelectorAll('[name=\"product_quantity[]\"]');
    const prices = document.querySelectorAll('[name=\"product_price[]\"]');
    
    let manualTotal = 0;
    products.forEach((qtyEl, i) => {
        const qty = parseFloat(qtyEl.value || 0);
        const price = parseFloat(prices[i]?.value || 0);
        const lineTotal = qty * price;
        manualTotal += lineTotal;
        console.log(`Product \${i+1}: \${qty} × \${price} = \${lineTotal}`);
    });
    
    const displayedSubtotal = document.getElementById('subtotal-amount')?.value;
    
    console.log('📊 TOTALS:');
    console.log('Manual calculation:', manualTotal.toFixed(2));
    console.log('Displayed subtotal:', displayedSubtotal);
    console.log('Match:', Math.abs(parseFloat(displayedSubtotal) - manualTotal) < 0.01);
    
    if (manualTotal === 376.92) {
        console.log('🚨 PROBLEM: JavaScript คำนวณเป็น 376.92');
        console.log('แสดงว่า Frontend คำนวณผิด ไม่ใช่ Backend');
    } else if (parseFloat(displayedSubtotal) === 376.92) {
        console.log('🚨 PROBLEM: Element แสดง 376.92 แต่คำนวณได้ถูก');
        console.log('แสดงว่า JavaScript calculation function มีปัญหา');
    }
}

debugCalculation();";
echo "</pre>";
echo "</div>";

echo "<h3>5. Quick Fix Test</h3>";
echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
echo "<p>ถ้าต้องการทดสอบแก้ไขด่วน:</p>";
echo "<pre style='background: #333; color: #0f0; padding: 10px; font-family: monospace;'>";
echo "// บังคับใส่ค่า 260 ลงใน subtotal-amount
document.getElementById('subtotal-amount').value = '260';

// แล้วลอง Submit ดู
// ถ้าได้ 260 ใน Database แสดงว่า Backend ใช้งานได้แล้ว
// ถ้ายังได้ 376.92 แสดงว่ายังมีจุดอื่นที่คำนวณทับ";
echo "</pre>";
echo "</div>";

require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h3>6. ตรวจสอบ ProductsDetail ใน Database</h3>";
    
    $result = $conn->query("
        SELECT DocumentNo, ProductsDetail, SubtotalAmount, Quantity, Price
        FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 1
    ");
    
    if ($result->rowCount() > 0) {
        $order = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6;'>";
        echo "<strong>Order:</strong> " . $order['DocumentNo'] . "<br>";
        echo "<strong>ProductsDetail:</strong><br>";
        
        if (!empty($order['ProductsDetail'])) {
            $products = json_decode($order['ProductsDetail'], true);
            if ($products) {
                echo "<pre style='background: #333; color: #fff; padding: 10px; font-size: 12px;'>";
                print_r($products);
                echo "</pre>";
                
                $calculatedFromProducts = 0;
                foreach ($products as $product) {
                    $qty = (float)($product['quantity'] ?? 0);
                    $price = (float)($product['price'] ?? 0);
                    $calculatedFromProducts += ($qty * $price);
                }
                
                echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
                echo "<strong>🧮 Analysis:</strong><br>";
                echo "Calculated from ProductsDetail: " . number_format($calculatedFromProducts, 2) . "<br>";
                echo "Database SubtotalAmount: " . $order['SubtotalAmount'] . "<br>";
                
                if (abs($calculatedFromProducts - 376.92) < 0.01) {
                    echo "❌ <strong>สาเหตุ:</strong> Frontend ส่ง products ที่คำนวณเป็น 376.92<br>";
                    echo "📋 <strong>แก้ไข:</strong> ต้องแก้ JavaScript calculation function";
                } elseif (abs($calculatedFromProducts - 260) < 0.01) {
                    echo "✅ ProductsDetail ถูกต้อง (260) แต่ SubtotalAmount ผิด (376.92)<br>";
                    echo "📋 <strong>แก้ไข:</strong> ต้องตรวจสอบ API mapping logic";
                }
                echo "</div>";
            }
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
pre { font-size: 12px; line-height: 1.4; }
</style>