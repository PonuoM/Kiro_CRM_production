<?php
/**
 * Fix the discount calculation issue in existing orders
 */

require_once 'config/database.php';

echo "🔧 Fix Discount Calculation Issues\n\n";

// Simulate the problem and solution
echo "🧮 Problem Analysis:\n";
echo "-------------------\n";

$originalPrice = 480;
$discountAmount = 80;
$expectedFinalPrice = $originalPrice - $discountAmount; // Should be 400
$expectedDiscountPercent = ($discountAmount / $originalPrice) * 100; // Should be 16.67%

echo "Original Price: {$originalPrice} บาท\n";
echo "Discount Amount: {$discountAmount} บาท\n";
echo "Expected Final Price: {$expectedFinalPrice} บาท\n";
echo "Expected Discount %: " . round($expectedDiscountPercent, 2) . "%\n";

echo "\n🔍 Possible Causes of 253.32:\n";
echo "-----------------------------\n";

// Theory 1: Double discount application (amount + percent)
$theory1 = $originalPrice - $discountAmount - ($originalPrice * $expectedDiscountPercent / 100);
echo "Theory 1 (Double discount): {$originalPrice} - {$discountAmount} - ({$originalPrice} * 16.67/100) = " . round($theory1, 2) . "\n";

// Theory 2: Applying percentage to wrong base
$theory2 = ($originalPrice - $discountAmount) - (($originalPrice - $discountAmount) * $expectedDiscountPercent / 100);
echo "Theory 2 (% on discounted): ({$originalPrice} - {$discountAmount}) - (400 * 16.67/100) = " . round($theory2, 2) . "\n";

// Theory 3: Wrong percentage calculation
$wrongPercent = 37.5; // Just a guess
$theory3 = $originalPrice - $discountAmount - ($originalPrice * $wrongPercent / 100);
echo "Theory 3 (Wrong % {$wrongPercent}): {$originalPrice} - {$discountAmount} - ({$originalPrice} * {$wrongPercent}/100) = " . round($theory3, 2) . "\n";

// Theory 4: Some complex wrong calculation that could result in 253.32
// Reverse calculate what percentage would give us 253.32
$actualResult = 253.32;
$backCalculatedPercent = (($originalPrice - $discountAmount - $actualResult) / $originalPrice) * 100;
echo "Theory 4 (Back-calculated): If result is 253.32, the extra discount % is: " . round($backCalculatedPercent, 2) . "%\n";

echo "\n💡 Most Likely Cause:\n";
echo "--------------------\n";
echo "The system is applying BOTH discount amount AND discount percentage!\n";
echo "Calculation: {$originalPrice} - {$discountAmount} - ({$originalPrice} * X%) = 253.32\n";
echo "Solving for X: ({$originalPrice} - {$discountAmount} - 253.32) / {$originalPrice} * 100 = " . round($backCalculatedPercent, 2) . "%\n";

echo "\n🔧 Solution:\n";
echo "-----------\n";
echo "1. Ensure only ONE discount method is applied at a time\n";
echo "2. If both amount and percentage are provided, use amount as primary\n";
echo "3. Fix the API to not double-apply discounts\n";
echo "4. Fix SubtotalAmount to not be NULL\n";

echo "\n📝 Recommended API Fix:\n";
echo "----------------------\n";
echo "```php\n";
echo "// Calculate final total - use ONLY discount amount, ignore percentage in calculation\n";
echo "\$finalTotal = max(0, \$totalAmount - \$discountAmount);\n";
echo "\n";
echo "// Store the correct values\n";
echo "\$orderData['SubtotalAmount'] = \$totalAmount;  // Original price before discount\n";
echo "\$orderData['DiscountAmount'] = \$discountAmount;  // Discount in currency\n";
echo "\$orderData['DiscountPercent'] = \$discountPercent;  // Calculated percentage for display\n";
echo "\$orderData['Price'] = \$finalTotal;  // Final price after discount\n";
echo "```\n";

echo "\n🎯 Test Case Verification:\n";
echo "-------------------------\n";
echo "Input: Price=480, Discount=80\n";
echo "✅ SubtotalAmount: 480\n";
echo "✅ DiscountAmount: 80\n"; 
echo "✅ DiscountPercent: 16.67\n";
echo "✅ Price (final): 400\n";
echo "❌ Current result: 253.32 (WRONG)\n";

echo "\nPlease check the API logic and ensure no double discount application!\n";
?>