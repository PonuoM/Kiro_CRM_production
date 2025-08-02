<?php
echo "<h2>🔍 ตรวจสอบ Apache Error Log</h2>";

echo "<h3>1. อ่าน Error Log ล่าสุด</h3>";

$logPaths = [
    '/var/log/apache2/error.log',
    '/xampp/apache/logs/error.log', 
    '/opt/lampp/logs/error_log',
    dirname(__FILE__) . '/logs/php_errors.log',
    dirname(__FILE__) . '/error_log',
    ini_get('error_log')
];

$logFound = false;
$logContent = '';

foreach ($logPaths as $path) {
    if (file_exists($path) && is_readable($path)) {
        echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0;'>";
        echo "✅ พบ Log ที่: <code>$path</code>";
        echo "</div>";
        
        // อ่าน 50 บรรทัดล่าสุด
        $lines = file($path);
        if ($lines) {
            $recentLines = array_slice($lines, -50);
            $logContent = implode('', $recentLines);
            $logFound = true;
            break;
        }
    }
}

if (!$logFound) {
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "❌ ไม่พบ Error Log ที่ตำแหน่งมาตรฐาน<br>";
    echo "ลอง: <code>find / -name \"*error*log*\" 2>/dev/null</code>";
    echo "</div>";
} else {
    echo "<h3>2. Log ล่าสุด (50 บรรทัด)</h3>";
    echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace; height: 400px; overflow-y: scroll; font-size: 12px;'>";
    
    // กรอง log ที่เกี่ยวกับ Order
    $lines = explode("\n", $logContent);
    $orderRelatedLines = [];
    
    foreach ($lines as $line) {
        if (stripos($line, 'ORDER') !== false || 
            stripos($line, 'FRONTEND') !== false || 
            stripos($line, 'CALCULATION') !== false ||
            stripos($line, 'MAPPING') !== false ||
            stripos($line, 'VALUE SELECTION') !== false) {
            $orderRelatedLines[] = htmlspecialchars($line);
        }
    }
    
    if (!empty($orderRelatedLines)) {
        echo "<strong>🎯 Order Related Logs:</strong><br><br>";
        foreach ($orderRelatedLines as $line) {
            echo $line . "<br>";
        }
        echo "<br><hr><br>";
    }
    
    // แสดง log ทั้งหมด
    echo "<strong>📋 Full Recent Log:</strong><br><br>";
    echo nl2br(htmlspecialchars($logContent));
    echo "</div>";
}

// สร้าง debug tool สำหรับตรวจสอบการส่งข้อมูล
echo "<h3>3. Advanced Debug Tool</h3>";
echo "<div style='background: #333; color: #0f0; padding: 15px; font-family: monospace;'>";
echo "<pre>";
echo "// วางใน Console เพื่อดูข้อมูลครบถ้วน
function fullDebug() {
    console.log('🔍 FULL ORDER DEBUG');
    
    // ดูค่าใน element
    const subtotalEl = document.getElementById('subtotal-amount');
    console.log('📊 SUBTOTAL ELEMENT:');
    console.log('- Element:', subtotalEl);
    console.log('- Value:', subtotalEl?.value);
    console.log('- Type:', typeof subtotalEl?.value);
    console.log('- Empty check:', subtotalEl?.value === '');
    console.log('- Null check:', subtotalEl?.value === null);
    console.log('- Undefined check:', subtotalEl?.value === undefined);
    
    // ดู products ที่จะส่งไป
    const products = [];
    const productRows = document.querySelectorAll('.product-row, [class*=\"product\"]');
    
    document.querySelectorAll('[name=\"product_quantity[]\"]').forEach((qtyEl, i) => {
        const priceEl = document.querySelectorAll('[name=\"product_price[]\"]')[i];
        const nameEl = document.querySelectorAll('[name=\"product_name[]\"]')[i];
        
        products.push({
            name: nameEl?.value || 'Unknown',
            quantity: parseFloat(qtyEl.value || 0),
            price: parseFloat(priceEl?.value || 0),
            total: parseFloat(qtyEl.value || 0) * parseFloat(priceEl?.value || 0)
        });
    });
    
    console.log('📦 PRODUCTS TO SEND:');
    products.forEach((p, i) => {
        console.log(`\${i+1}. \${p.name}: \${p.quantity} × \${p.price} = \${p.total}`);
    });
    
    const calculatedTotal = products.reduce((sum, p) => sum + p.total, 0);
    console.log('🧮 CALCULATED TOTAL:', calculatedTotal.toFixed(2));
    console.log('📺 DISPLAYED TOTAL:', subtotalEl?.value);
    console.log('❓ WHY DIFFERENT?', calculatedTotal.toFixed(2) !== subtotalEl?.value ? 'YES - THIS IS THE PROBLEM!' : 'NO');
}

fullDebug();";
echo "</pre>";
echo "</div>";

echo "<h3>4. Force Fix Test</h3>";
echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
echo "<p><strong>💡 ทดสอบบังคับ:</strong></p>";
echo "<pre style='background: #333; color: #0f0; padding: 10px; font-family: monospace;'>";
echo "// วางใน Console เพื่อบังคับให้ products array คำนวณเป็น 260
// แก้ราคาใน products array ให้ตรงกับที่แสดง

const productPrices = document.querySelectorAll('[name=\"product_price[]\"]');
productPrices.forEach((priceEl, i) => {
    if (i === 0) priceEl.value = '150'; // ปรับให้รวมเป็น 260
    if (i === 1) priceEl.value = '110';
});

// ตรวจสอบผลรวม
let newTotal = 0;
document.querySelectorAll('[name=\"product_quantity[]\"]').forEach((qtyEl, i) => {
    const priceEl = document.querySelectorAll('[name=\"product_price[]\"]')[i];
    newTotal += parseFloat(qtyEl.value || 0) * parseFloat(priceEl?.value || 0);
});

console.log('🎯 NEW CALCULATED TOTAL:', newTotal);
console.log('Should be 260:', newTotal === 260 ? '✅ YES' : '❌ NO');

// แล้วลอง Submit
";
echo "</pre>";
echo "</div>";
?>

<style>
pre { font-size: 12px; line-height: 1.3; }
</style>