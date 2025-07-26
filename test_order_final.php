<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Order System - Final</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/customer-detail.css">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 5px; }
        .result { margin: 10px 0; padding: 10px; background: white; border-radius: 3px; }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        .form-group { margin: 10px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🧪 Final Order System Test</h1>
    
    <div class="test-section">
        <h3>1. Test Order Creation with Discount</h3>
        <form id="test-order-form">
            <div class="form-group">
                <label>Customer Code:</label>
                <input type="text" id="customer-code" value="TEST007" required>
            </div>
            
            <div class="form-group">
                <label>Document Date:</label>
                <input type="date" id="document-date" value="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Payment Method:</label>
                <select id="payment-method">
                    <option value="">เลือกวิธีการชำระเงิน</option>
                    <option value="เงินสด">เงินสด</option>
                    <option value="โอนเงิน">โอนเงิน</option>
                    <option value="เช็ค">เช็ค</option>
                    <option value="บัตรเครดิต">บัตรเครดิต</option>
                </select>
            </div>
            
            <h4>Products</h4>
            <div class="form-group">
                <label>Product 1 - Code:</label>
                <input type="text" id="product1-code" value="F001" required>
            </div>
            <div class="form-group">
                <label>Product 1 - Name:</label>
                <input type="text" id="product1-name" value="ปุ๋ยเคมี 16-16-16" required>
            </div>
            <div class="form-group">
                <label>Product 1 - Quantity:</label>
                <input type="number" id="product1-quantity" value="2" min="1" required>
            </div>
            <div class="form-group">
                <label>Product 1 - Price:</label>
                <input type="number" id="product1-price" value="18.50" step="0.01" min="0" required>
            </div>
            
            <h4>Discount</h4>
            <div class="form-group">
                <label>Discount Amount (บาท):</label>
                <input type="number" id="discount-amount" value="5.00" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Discount Percent (%):</label>
                <input type="number" id="discount-percent" value="0" step="0.01" min="0" max="100">
            </div>
            <div class="form-group">
                <label>Discount Remarks:</label>
                <input type="text" id="discount-remarks" value="โปรโมชั่นพิเศษ">
            </div>
            
            <button type="submit" class="btn">Create Test Order</button>
            <button type="button" class="btn" onclick="testDebug()" style="background: #28a745; margin-left: 10px;">Test Debug Endpoint</button>
        </form>
        
        <div id="order-result" class="result"></div>
    </div>

    <script>
        document.getElementById('test-order-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const resultDiv = document.getElementById('order-result');
            resultDiv.innerHTML = 'Creating order...';
            
            // Collect form data
            const orderData = {
                CustomerCode: document.getElementById('customer-code').value,
                DocumentDate: document.getElementById('document-date').value,
                PaymentMethod: document.getElementById('payment-method').value,
                products: [
                    {
                        code: document.getElementById('product1-code').value,
                        name: document.getElementById('product1-name').value,
                        quantity: parseFloat(document.getElementById('product1-quantity').value),
                        price: parseFloat(document.getElementById('product1-price').value)
                    }
                ],
                discount_amount: parseFloat(document.getElementById('discount-amount').value) || 0,
                discount_percent: parseFloat(document.getElementById('discount-percent').value) || 0,
                discount_remarks: document.getElementById('discount-remarks').value || ''
            };
            
            console.log('Sending order data:', orderData);
            
            try {
                const response = await fetch('api/orders/test_create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error('Invalid JSON response: ' + responseText.substring(0, 200));
                }
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            ✅ Order created successfully!<br>
                            Document No: ${result.data.DocumentNo}<br>
                            Message: ${result.message}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">
                            ❌ Order creation failed:<br>
                            ${result.message}<br>
                            ${result.errors ? result.errors.join('<br>') : ''}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Full error:', error);
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        });
        
        // Debug endpoint test function
        async function testDebug() {
            const resultDiv = document.getElementById('order-result');
            resultDiv.innerHTML = 'Testing debug endpoint...';
            
            try {
                const response = await fetch('api/orders/debug.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({test: 'debug'})
                });
                
                const responseText = await response.text();
                console.log('Debug response:', responseText);
                
                try {
                    const result = JSON.parse(responseText);
                    resultDiv.innerHTML = `
                        <div class="success">
                            ✅ Debug endpoint working!<br>
                            <pre style="text-align: left; font-size: 12px; max-height: 300px; overflow-y: auto;">${JSON.stringify(result, null, 2)}</pre>
                        </div>
                    `;
                } catch (parseError) {
                    resultDiv.innerHTML = `
                        <div class="error">
                            ❌ Debug endpoint returned invalid JSON:<br>
                            <pre style="text-align: left; font-size: 12px; max-height: 300px; overflow-y: auto;">${responseText}</pre>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Debug endpoint failed: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>