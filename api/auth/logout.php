<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

try {
    // Get current username for logging
    $username = $_SESSION['username'] ?? 'Unknown';
    
    // Clear all session data
    $_SESSION = array();
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Redirect to simple login page to avoid redirect loops
    header('Location: ../../pages/login.php');
    exit;
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    // If error, still redirect to simple login
    header('Location: ../../pages/login.php');
    exit;
}
?>