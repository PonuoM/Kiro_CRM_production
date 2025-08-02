<?php
/**
 * Debug order calculation issues
 */

require_once 'config/database.php';

echo "🔍 Debug Order Calculation\n\n";

try {
    // Use direct PDO connection for XAMPP
    $host = 'localhost';
    $dbname = 'primacom_CRM';  
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database\n\n";
    
    // Check the problematic order
    $documentNo = 'DOC202507311109361444';
    
    echo "🔍 Checking Order: $documentNo\n\n";
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE DocumentNo = ?");
    $stmt->execute([$documentNo]);
    $order = $stmt->fetch();
    
    if ($order) {
        echo "📊 Order Details:\n";
        echo "----------------\n";
        echo "Products: {$order['Products']}\n";
        echo "Quantity: {$order['Quantity']}\n";
        echo "Price (in database): {$order['Price']}\n";
        echo "DiscountAmount: {$order['DiscountAmount']}\n";
        echo "DiscountPercent: {$order['DiscountPercent']}\n";
        echo "SubtotalAmount: {$order['SubtotalAmount']}\n";
        echo "ProductsDetail: {$order['ProductsDetail']}\n";
        
        echo "\n🧮 Calculation Analysis:\n";
        echo "------------------------\n";
        
        // Parse ProductsDetail if exists
        if (!empty($order['ProductsDetail'])) {
            $products = json_decode($order['ProductsDetail'], true);
            if ($products) {
                echo "Products from JSON:\n";
                $totalFromProducts = 0;
                foreach ($products as $product) {
                    $subtotal = $product['quantity'] * $product['price'];
                    $totalFromProducts += $subtotal;
                    echo "- {$product['name']}: {$product['quantity']} x {$product['price']} = {$subtotal}\n";
                }
                echo "Total from products: {$totalFromProducts}\n";
            }
        }
        
        // Manual calculation
        $subtotal = (float)$order['SubtotalAmount'];
        $discountAmount = (float)$order['DiscountAmount'];
        $discountPercent = (float)$order['DiscountPercent'];
        $currentPrice = (float)$order['Price'];
        
        echo "\nManual Calculations:\n";
        echo "Subtotal: {$subtotal}\n";
        echo "Discount Amount: {$discountAmount}\n";
        echo "Expected Final Price: " . ($subtotal - $discountAmount) . "\n";
        echo "Actual Price in DB: {$currentPrice}\n";
        
        echo "Expected Discount %: " . ($subtotal > 0 ? round(($discountAmount / $subtotal) * 100, 2) : 0) . "%\n";
        echo "Actual Discount %: {$discountPercent}%\n";
        
        // Check for issues
        $expectedFinalPrice = $subtotal - $discountAmount;
        $expectedDiscountPercent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;
        
        echo "\n🚨 Issues Found:\n";
        echo "----------------\n";
        
        if (is_null($order['SubtotalAmount'])) {
            echo "❌ SubtotalAmount is NULL!\n";
        }
        
        if (abs($currentPrice - $expectedFinalPrice) > 0.01) {
            echo "❌ Price field mismatch!\n";
            echo "   Expected: {$expectedFinalPrice}\n";
            echo "   Actual: {$currentPrice}\n";
            echo "   Difference: " . ($currentPrice - $expectedFinalPrice) . "\n";
        }
        
        if (abs($discountPercent - $expectedDiscountPercent) > 0.01) {
            echo "❌ DiscountPercent mismatch!\n";
            echo "   Expected: " . round($expectedDiscountPercent, 2) . "%\n";
            echo "   Actual: {$discountPercent}%\n";
        }
        
        // Check if there's a pattern in the wrong calculation
        echo "\n🔍 Reverse Engineering the Wrong Calculation:\n";
        echo "--------------------------------------------\n";
        
        // If 253.32 is the result, what could have happened?
        if (abs($currentPrice - 253.32) < 0.01) {
            echo "The price 253.32 could be from:\n";
            
            // Theory 1: Double discount application
            $theory1 = $subtotal - $discountAmount - ($discountAmount * $discountPercent / 100);
            echo "- Theory 1 (Double discount): {$subtotal} - {$discountAmount} - ({$discountAmount} * {$discountPercent}/100) = {$theory1}\n";
            
            // Theory 2: Percentage applied to already discounted amount
            $theory2 = ($subtotal - $discountAmount) * (1 - $discountPercent/100);
            echo "- Theory 2 (% on discounted): ({$subtotal} - {$discountAmount}) * (1 - {$discountPercent}/100) = {$theory2}\n";
            
            // Theory 3: Some other weird calculation
            $theory3 = $subtotal * (1 - $discountPercent/100) - $discountAmount;
            echo "- Theory 3 (% first, then amount): {$subtotal} * (1 - {$discountPercent}/100) - {$discountAmount} = {$theory3}\n";
        }
        
    } else {
        echo "❌ Order not found!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n📋 Next Steps:\n";
echo "1. Check the JavaScript calculation logic\n";
echo "2. Check the API calculation logic\n";  
echo "3. Check if there's double discount application\n";
echo "4. Fix the SubtotalAmount NULL issue\n";
?>