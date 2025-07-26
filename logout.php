<?php
/**
 * Logout Page
 * Destroys session and redirects to login
 */

session_start();
session_destroy();

// Redirect to login page
header('Location: pages/login.php');
exit;
?>