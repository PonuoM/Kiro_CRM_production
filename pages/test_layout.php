<?php
// Simple test for main_layout.php issues
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simple login check
if (!isset($_SESSION['user_id'])) {
    echo "❌ Not logged in - redirecting to login.php<br>";
    // header('Location: login.php');
    // exit;
} else {
    echo "✅ Session found - user_id: " . $_SESSION['user_id'] . "<br>";
}

echo "📂 Testing main_layout.php include...<br>";

try {
    require_once '../includes/main_layout.php';
    echo "✅ main_layout.php loaded successfully<br>";
    
    // Test basic variables
    $user_name = $_SESSION['username'] ?? 'Unknown';
    $user_role = $_SESSION['user_role'] ?? 'Unknown';
    
    echo "👤 User: " . htmlspecialchars($user_name) . "<br>";
    echo "🔑 Role: " . htmlspecialchars($user_role) . "<br>";
    
    // Test renderMainLayout function
    if (function_exists('renderMainLayout')) {
        echo "✅ renderMainLayout function exists<br>";
    } else {
        echo "❌ renderMainLayout function not found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error loading main_layout: " . $e->getMessage() . "<br>";
} catch (Error $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "<br>";
}

echo "🏁 Test completed<br>";
?>