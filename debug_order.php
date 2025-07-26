<?php
session_start();
// Fake login for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['role'] = 'admin';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Order System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/customer-detail.css">
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        .debug-section { margin: 20px 0; padding: 15px; background: #f5f5f5; border-radius: 8px; }
        .debug-log { background: white; padding: 10px; border-radius: 4px; font-family: monospace; max-height: 300px; overflow-y: auto; }
        .test-modal { display: block !important; position: relative !important; background: white; border: 1px solid #ddd; padding: 20px; }
    </style>
</head>
<body>
    <h1>🐛 Debug Order System</h1>
    
    <div class="debug-section">
        <h3>1. Test Products API Direct</h3>
        <button onclick="testDirectAPI()">Test API</button>
        <div id="api-result" class="debug-log"></div>
    </div>
    
    <div class="debug-section">
        <h3>2. Test Modal (Always Visible)</h3>
        
        <!-- Test Modal -->
        <div class="test-modal">
            <h4>สร้างคำสั่งซื้อ</h4>
            <form id="test-order-form">
                <div class="form-group">
                    <label for="test-document-date">วันที่เอกสาร</label>
                    <input type="date" id="test-document-date" value="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label>รายการสินค้า</label>
                    <div id="test-products-container">
                        <div class="product-row" data-product-index="0">
                            <div class="form-row">
                                <div class="form-group" style="position: relative;">
                                    <label>เลือกสินค้า</label>
                                    <input type="text" id="test-product-search" class="product-search" 
                                           placeholder="พิมพ์เพื่อค้นหาสินค้า..." 
                                           autocomplete="off">
                                    <div class="product-suggestions" style="display: none;"></div>
                                    <input type="hidden" name="product_code[]" value="" required>
                                    <input type="hidden" name="product_name[]" value="">
                                </div>
                                <div class="form-group">
                                    <label>จำนวน</label>
                                    <input type="number" name="product_quantity[]" min="1" step="1" required placeholder="1">
                                </div>
                                <div class="form-group">
                                    <label>ราคาต่อหน่วย</label>
                                    <input type="number" name="product_price[]" min="0" step="0.01" required placeholder="0.00">
                                    <small class="standard-price-hint"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="debug-section">
        <h3>3. Console Logs</h3>
        <div id="console-log" class="debug-log"></div>
        <button onclick="clearConsole()">Clear Console</button>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Global variables for testing
        let productIndex = 1;
        let productsData = [];
        
        // Override console.log to capture logs
        const originalLog = console.log;
        const logDiv = document.getElementById('console-log');
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            const message = args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
            ).join(' ');
            logDiv.innerHTML += '<div>' + new Date().toLocaleTimeString() + ': ' + message + '</div>';
            logDiv.scrollTop = logDiv.scrollHeight;
        };

        function clearConsole() {
            logDiv.innerHTML = '';
        }

        // Test API directly
        async function testDirectAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = 'Testing API...';
            
            try {
                const response = await fetch('api/products/list.php');
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <strong>Status:</strong> ${response.status}<br>
                    <strong>Success:</strong> ${data.success}<br>
                    <strong>Count:</strong> ${data.total_count || 0}<br>
                    <strong>Data:</strong><br>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
                
                if (data.success) {
                    productsData = data.data;
                    console.log('✅ API test successful, loaded', productsData.length, 'products');
                    initializeTestAutocomplete();
                } else {
                    console.log('❌ API test failed:', data.message);
                }
            } catch (error) {
                resultDiv.innerHTML = `<span style="color: red;">Error: ${error.message}</span>`;
                console.log('❌ API test error:', error.message);
            }
        }

        // Initialize autocomplete for test
        function initializeTestAutocomplete() {
            const input = document.getElementById('test-product-search');
            if (!input) {
                console.log('❌ Test input not found');
                return;
            }
            
            console.log('🔧 Setting up test autocomplete...');
            
            input.addEventListener('input', function() {
                console.log('📝 Input event fired:', this.value);
                searchProducts(this);
            });
            
            input.addEventListener('focus', function() {
                console.log('🎯 Focus event fired');
                showProductSuggestions(this);
            });
            
            input.addEventListener('blur', function() {
                console.log('👋 Blur event fired');
                setTimeout(() => {
                    const suggestions = this.parentElement.querySelector('.product-suggestions');
                    if (suggestions) suggestions.style.display = 'none';
                }, 200);
            });
            
            console.log('✅ Test autocomplete initialized');
        }

        // Search products function
        function searchProducts(inputElement) {
            let query = inputElement.value.toLowerCase().trim();
            const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
            
            // Check if user is modifying a previously selected product
            const selectedProduct = inputElement.getAttribute('data-selected-product');
            const originalFormat = selectedProduct ? `${selectedProduct.toLowerCase()} - ` : null;
            
            // If user is typing and it doesn't match the original selected format, clear selection
            if (selectedProduct && !query.startsWith(originalFormat)) {
                console.log('🔄 User is changing selected product, clearing selection');
                inputElement.removeAttribute('data-selected-product');
                
                // Clear hidden fields
                const productRow = inputElement.closest('.product-row');
                if (productRow) {
                    const codeField = productRow.querySelector('input[name="product_code[]"]');
                    const nameField = productRow.querySelector('input[name="product_name[]"]');
                    if (codeField) codeField.value = '';
                    if (nameField) nameField.value = '';
                    
                    // Clear price hint
                    const priceHint = productRow.querySelector('.standard-price-hint');
                    if (priceHint) priceHint.textContent = '';
                }
            }
            
            // If the input contains " - " (selected product format), extract just the code for searching
            if (query.includes(' - ')) {
                const parts = query.split(' - ');
                query = parts[0]; // Use only the product code part
                console.log('🔍 Detected selected product format, using code only:', query);
            }
            
            console.log('🔍 Searching with query:', query, 'Available products:', productsData.length);
            
            if (query.length < 1) {
                suggestions.style.display = 'none';
                return;
            }
            
            const filteredProducts = productsData.filter(product => 
                product.product_code.toLowerCase().includes(query) ||
                product.product_name.toLowerCase().includes(query)
            );
            
            console.log('📋 Filtered results:', filteredProducts.length);
            showSuggestions(inputElement, filteredProducts);
        }

        // Show product suggestions
        function showProductSuggestions(inputElement) {
            console.log('💡 Showing suggestions, products available:', productsData.length);
            if (inputElement.value.trim().length > 0) {
                searchProducts(inputElement);
            } else {
                showSuggestions(inputElement, productsData);
            }
        }

        // Show suggestions
        function showSuggestions(inputElement, products) {
            const suggestions = inputElement.parentElement.querySelector('.product-suggestions');
            console.log('📋 Displaying', products.length, 'suggestions');
            
            if (products.length === 0) {
                suggestions.innerHTML = '<div class="product-suggestion-item">ไม่พบสินค้า</div>';
                suggestions.style.display = 'block';
                return;
            }
            
            suggestions.innerHTML = '';
            
            // Group by category
            const categories = {};
            products.forEach(product => {
                if (!categories[product.category]) {
                    categories[product.category] = [];
                }
                categories[product.category].push(product);
            });
            
            // Display grouped suggestions
            Object.keys(categories).sort().forEach(category => {
                categories[category].forEach(product => {
                    const item = document.createElement('div');
                    item.className = 'product-suggestion-item';
                    item.innerHTML = `
                        <div class="suggestion-code">${product.product_code}</div>
                        <div class="suggestion-name">${product.product_name}</div>
                        <div class="suggestion-price">${parseFloat(product.standard_price || 0).toFixed(2)} บาท</div>
                    `;
                    
                    item.addEventListener('click', () => {
                        console.log('✅ Product selected:', product.product_code);
                        inputElement.value = `${product.product_code} - ${product.product_name}`;
                        
                        // Mark input as having a selected product
                        inputElement.setAttribute('data-selected-product', product.product_code);
                        
                        // Update hidden fields if they exist
                        const productRow = inputElement.closest('.product-row');
                        if (productRow) {
                            const codeField = productRow.querySelector('input[name="product_code[]"]');
                            const nameField = productRow.querySelector('input[name="product_name[]"]');
                            if (codeField) codeField.value = product.product_code;
                            if (nameField) nameField.value = product.product_name;
                            
                            // Show price hint
                            const priceHint = productRow.querySelector('.standard-price-hint');
                            if (priceHint) {
                                priceHint.textContent = `ราคามาตรฐาน: ${parseFloat(product.standard_price || 0).toFixed(2)} บาท/${product.unit || 'ชิ้น'}`;
                            }
                        }
                        
                        suggestions.style.display = 'none';
                    });
                    
                    suggestions.appendChild(item);
                });
            });
            
            suggestions.style.display = 'block';
            console.log('✅ Suggestions displayed');
        }

        // Auto-test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Debug page loaded');
            testDirectAPI();
        });
    </script>
</body>
</html>