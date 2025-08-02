<?php
/**
 * Step by Step Debug - หาจุดที่ error 500 เกิดขึ้น
 */

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Starting page...<br>";

try {
    echo "Step 2: Starting session...<br>";
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    echo "Step 2: ✅ Session started<br>";

    echo "Step 3: Including permissions.php...<br>";
    require_once __DIR__ . '/../includes/permissions.php';
    echo "Step 3: ✅ permissions.php included<br>";

    echo "Step 4: Checking if user is logged in...<br>";
    $isLoggedIn = Permissions::isLoggedIn();
    echo "Step 4: Is logged in = " . ($isLoggedIn ? 'YES' : 'NO') . "<br>";

    if (!$isLoggedIn) {
        echo "❌ User not logged in - this would cause redirect<br>";
        echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
        exit;
    }

    echo "Step 5: Getting current user info...<br>";
    $currentUser = Permissions::getCurrentUser();
    $currentRole = Permissions::getCurrentRole();
    echo "Step 5: Current user = {$currentUser}, Role = {$currentRole}<br>";

    echo "Step 6: Checking call_history permission...<br>";
    $hasPermission = Permissions::hasPermission('call_history');
    echo "Step 6: Has permission = " . ($hasPermission ? 'YES' : 'NO') . "<br>";

    if (!$hasPermission) {
        echo "❌ User lacks call_history permission<br>";
        exit;
    }

    echo "Step 7: Including functions.php...<br>";
    require_once __DIR__ . '/../includes/functions.php';
    echo "Step 7: ✅ functions.php included<br>";

    echo "Step 8: Including main_layout.php...<br>";
    require_once __DIR__ . '/../includes/main_layout.php';
    echo "Step 8: ✅ main_layout.php included<br>";

    echo "Step 9: Getting menu items...<br>";
    $menuItems = Permissions::getMenuItems();
    echo "Step 9: ✅ Menu items retrieved (" . count($menuItems) . " items)<br>";

    echo "Step 10: Setting up page variables...<br>";
    $pageTitle = "ประวัติการโทร";
    $testCustomerCode = $_GET['customer'] ?? 'CUS20240115103012345';
    echo "Step 10: ✅ Variables set - Customer: {$testCustomerCode}<br>";

    echo "<hr>";
    echo "🎉 ALL STEPS PASSED! The issue is NOT in the basic setup.<br>";
    echo "The error might be in:<br>";
    echo "1. The HTML/JavaScript content<br>";
    echo "2. The renderMainLayout() function<br>";
    echo "3. Memory or execution limits<br>";
    echo "<br>";
    echo "Let's test renderMainLayout()...<br>";
    
    echo "Step 11: Testing renderMainLayout()...<br>";
    
    // Set globals
    $GLOBALS['currentUser'] = $currentUser;
    $GLOBALS['currentRole'] = $currentRole; 
    $GLOBALS['menuItems'] = $menuItems;
    
    // Simple content test
    $simpleContent = "<h1>Test Content</h1><p>This is a test.</p>";
    $simpleCSS = "<style>body { background: #f0f0f0; }</style>";
    $simpleJS = "<script>console.log('Test JS loaded');</script>";
    
    echo renderMainLayout($pageTitle, $simpleContent, $simpleCSS, $simpleJS);
    
} catch (Error $e) {
    echo "❌ FATAL ERROR at step: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "❌ EXCEPTION at step: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>