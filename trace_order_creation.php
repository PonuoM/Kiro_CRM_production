<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "<h2>🔍 Trace Order Creation Flow</h2>";
    
    // 1. เปิด Debug Mode ใน API
    echo "<h3>1. เปิด Debug Logging</h3>";
    echo "<div style='background: #e6f3ff; padding: 15px; border-left: 4px solid #0066cc;'>";
    echo "<p>Debug logs จะถูกเขียนใน error_log ของ Apache/PHP</p>";
    echo "<p><strong>Location:</strong> ตรวจสอบที่ <code>/var/log/apache2/error.log</code> หรือ <code>error_log</code> ใน project</p>";
    echo "</div>";
    
    // 2. แสดงวิธีการ Monitor
    echo "<h3>2. วิธีการ Monitor Real-time</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
    echo "<h4>📋 ขั้นตอนการตรวจสอบ:</h4>";
    echo "<ol>";
    echo "<li><strong>เปิด Browser DevTools (F12)</strong></li>";
    echo "<li><strong>ไปที่ Network Tab</strong></li>";
    echo "<li><strong>ใส่ Filter:</strong> <code>create.php</code></li>";
    echo "<li><strong>ลองสร้าง Order</strong> ใหม่</li>";
    echo "<li><strong>คลิกที่ Request</strong> ใน Network Tab</li>";
    echo "<li><strong>ดู Request Payload</strong> ว่าส่งอะไรไป</li>";
    echo "<li><strong>ดู Response</strong> ว่า API ตอบอะไรกลับ</li>";
    echo "</ol>";
    echo "</div>";
    
    // 3. สร้าง Test Case
    echo "<h3>3. Test Case สำหรับทดสอบ</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-left: 4px solid #28a745;'>";
    echo "<h4>🧪 Test Case:</h4>";
    echo "<ul>";
    echo "<li><strong>สินค้า:</strong> ปุ๋ยเคมี 2 รายการ</li>";
    echo "<li><strong>จำนวน:</strong> 2 หน่วย</li>";
    echo "<li><strong>ยอดรวม:</strong> 535 บาท</li>";
    echo "<li><strong>ส่วนลด:</strong> 35 บาท (6.54%)</li>";
    echo "<li><strong>ยอดสุทธิ:</strong> 500 บาท</li>";
    echo "</ul>";
    echo "</div>";
    
    // 4. แสดง Expected vs Actual
    echo "<h3>4. Expected vs Actual Values</h3>";
    
    // ดึงข้อมูล Order ล่าสุด
    $result = $conn->query("
        SELECT * FROM orders 
        ORDER BY CreatedDate DESC 
        LIMIT 1
    ");
    
    if ($result->rowCount() > 0) {
        $latestOrder = $result->fetch(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Field</th><th>Expected (Test Case)</th><th>Actual (Database)</th><th>Status</th>";
        echo "</tr>";
        
        $expectations = [
            'Quantity' => 2,
            'SubtotalAmount' => 535.00,
            'DiscountAmount' => 35.00,
            'DiscountPercent' => 6.54,
            'Price' => 500.00
        ];
        
        foreach ($expectations as $field => $expected) {
            $actual = $latestOrder[$field];
            $status = (abs($actual - $expected) < 0.01) ? '✅ ตรง' : '❌ ผิด';
            $bgColor = (abs($actual - $expected) < 0.01) ? 'background: #d4edda;' : 'background: #f8d7da;';
            
            echo "<tr style='{$bgColor}'>";
            echo "<td><strong>{$field}</strong></td>";
            echo "<td>{$expected}</td>";
            echo "<td>{$actual}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<div style='margin: 15px 0; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107;'>";
        echo "<strong>📊 Analysis:</strong><br>";
        echo "Document No: <strong>{$latestOrder['DocumentNo']}</strong><br>";
        echo "Created: <strong>{$latestOrder['CreatedDate']}</strong><br>";
        echo "Products: <strong>" . htmlspecialchars($latestOrder['Products']) . "</strong>";
        echo "</div>";
    }
    
    // 5. แสดงวิธีการ Debug API
    echo "<h3>5. Debug API Request</h3>";
    echo "<div style='background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545;'>";
    echo "<h4>🚨 หาสาเหตุข้อมูลผิด:</h4>";
    echo "<p><strong>JavaScript Console Command:</strong></p>";
    echo "<pre style='background: #333; color: white; padding: 10px; overflow-x: auto;'>";
echo "// วางใน Console เมื่ออยู่ในหน้าสร้าง Order
console.log('=== ORDER FORM VALUES ===');
console.log('Total Quantity:', document.getElementById('total-quantity')?.value);
console.log('Subtotal Amount:', document.getElementById('subtotal-amount')?.value);
console.log('Discount Amount:', document.getElementById('discount-amount')?.value);
console.log('Discount Percent:', document.getElementById('discount-percent')?.value);
console.log('Total Amount:', document.getElementById('total-amount')?.value);

// Override submitOrder function to see data being sent
const originalSubmitOrder = window.customerDetail?.submitOrder;
if (originalSubmitOrder) {
    window.customerDetail.submitOrder = function() {
        console.log('=== INTERCEPTED ORDER SUBMISSION ===');
        const data = {
            total_quantity: document.getElementById('total-quantity')?.value,
            subtotal_amount: document.getElementById('subtotal-amount')?.value,
            discount_amount: document.getElementById('discount-amount')?.value,
            discount_percent: document.getElementById('discount-percent')?.value,
            total_amount: document.getElementById('total-amount')?.value
        };
        console.log('Data being sent:', data);
        return originalSubmitOrder.call(this);
    };
}";
    echo "</pre>";
    echo "</div>";
    
    // 6. Real-time Log Viewer
    echo "<h3>6. Real-time Debug Log</h3>";
    echo "<div id='debug-log' style='background: #333; color: #0f0; padding: 15px; font-family: monospace; height: 200px; overflow-y: scroll; border: 1px solid #ccc;'>";
    echo "Waiting for debug logs...\n";
    echo "Create an order to see real-time debug information.\n";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>

<script>
// Auto-refresh debug log (if logs were written to a file)
let debugLogDiv = document.getElementById('debug-log');

// Monitor console for debug info
const originalConsoleLog = console.log;
console.log = function() {
    originalConsoleLog.apply(console, arguments);
    
    // Also display in our debug log
    const args = Array.from(arguments);
    const message = args.map(arg => 
        typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
    ).join(' ');
    
    debugLogDiv.innerHTML += message + '\n';
    debugLogDiv.scrollTop = debugLogDiv.scrollHeight;
};

console.log('🔍 Debug tracer loaded - ready to monitor order creation');

// Auto-scroll to bottom
setInterval(() => {
    debugLogDiv.scrollTop = debugLogDiv.scrollHeight;
}, 1000);
</script>

<style>
table {
    font-size: 14px;
}

pre {
    font-size: 12px;
    line-height: 1.4;
}

#debug-log {
    font-size: 12px;
    line-height: 1.3;
}
</style>