<?php
/**
 * CRM System - Production Entry Point
 * 
 * This file serves as the main entry point for the CRM system.
 * It redirects users to the appropriate page based on authentication status.
 */

// Start session
session_start();

// Redirect to login page if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit;
}

// If authenticated, redirect to dashboard
header('Location: pages/dashboard.php');
exit;
?>