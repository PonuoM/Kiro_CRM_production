<?php
// Production Configuration File for DirectAdmin
// This file should be used in production environment
// Copy this file to config.php for production deployment

// Application settings
define('APP_NAME', 'CRM System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');

// Security settings for production
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Enable for HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour session timeout

// Session name (change for security)
ini_set('session.name', 'CRM_SESSION');

// Path Constants for URL Management
define('BASE_URL', '/crm_system/Kiro_CRM_production/');
define('PAGES_URL', BASE_URL . 'pages/');
define('API_URL', BASE_URL . 'api/');
define('ADMIN_URL', BASE_URL . 'pages/admin/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Error reporting (ENABLED for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// File upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['csv']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Pagination settings
define('RECORDS_PER_PAGE', 20);

// Auto rules settings
define('NEW_CUSTOMER_DAYS_LIMIT', 30);
define('FOLLOW_CUSTOMER_MONTHS_LIMIT', 3);
define('OLD_CUSTOMER_MONTHS_LIMIT', 3);

// Logging settings
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'ERROR'); // ERROR, WARNING, INFO, DEBUG
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB per log file

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 300); // 5 minutes

// Rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100); // requests per minute
define('RATE_LIMIT_WINDOW', 60); // seconds

// Email settings (if needed for notifications)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'CRM System');

// Backup settings
define('BACKUP_ENABLED', true);
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);

/**
 * Production logging function
 * @param string $level
 * @param string $message
 * @param array $context
 */
function logMessage($level, $message, $context = []) {
    if (!defined('LOG_LEVEL')) return;
    
    $levels = ['ERROR' => 1, 'WARNING' => 2, 'INFO' => 3, 'DEBUG' => 4];
    $currentLevel = $levels[LOG_LEVEL] ?? 1;
    $messageLevel = $levels[$level] ?? 1;
    
    if ($messageLevel > $currentLevel) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
    
    $logFile = LOG_PATH . 'application.log';
    
    // Check if log directory exists and is writable
    if (!is_dir(LOG_PATH)) {
        @mkdir(LOG_PATH, 0755, true);
    }
    
    if (!is_writable(LOG_PATH)) {
        // Fallback to error_log if directory not writable
        error_log("CRM LOG [{$level}] {$message}");
        return;
    }
    
    // Check log file size and rotate if necessary
    if (file_exists($logFile) && filesize($logFile) > LOG_MAX_SIZE) {
        @rename($logFile, LOG_PATH . 'application_' . date('Y-m-d_H-i-s') . '.log');
    }
    
    // Try to write to log file, fallback to error_log if fails
    if (@file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        error_log("CRM LOG [{$level}] {$message}");
    }
}

// sanitizeInput() function is defined in includes/functions.php
// Updated: 2025-07-21 11:20:00 - Function redeclaration fix complete

// CSRF functions are defined in includes/functions.php

/**
 * Rate limiting function
 * @param string $identifier
 * @return bool
 */
function checkRateLimit($identifier) {
    if (!RATE_LIMIT_ENABLED) return true;
    
    $key = 'rate_limit_' . md5($identifier);
    $file = sys_get_temp_dir() . '/' . $key;
    
    $now = time();
    $requests = [];
    
    if (file_exists($file)) {
        $data = file_get_contents($file);
        $requests = json_decode($data, true) ?: [];
    }
    
    // Remove old requests
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < RATE_LIMIT_WINDOW;
    });
    
    // Check if limit exceeded
    if (count($requests) >= RATE_LIMIT_REQUESTS) {
        return false;
    }
    
    // Add current request
    $requests[] = $now;
    file_put_contents($file, json_encode($requests), LOCK_EX);
    
    return true;
}

/**
 * Create necessary directories
 */
function createDirectories() {
    $dirs = [
        LOG_PATH,
        UPLOAD_PATH,
        BACKUP_PATH
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Environment check for production
 */
function checkProductionEnvironment() {
    $checks = [];
    
    // Check PHP version
    $checks['php_version'] = version_compare(PHP_VERSION, '7.4.0', '>=');
    
    // Check required extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
    foreach ($required_extensions as $ext) {
        $checks["ext_{$ext}"] = extension_loaded($ext);
    }
    
    // Check directory permissions
    $checks['logs_writable'] = is_writable(LOG_PATH);
    $checks['uploads_writable'] = is_writable(UPLOAD_PATH);
    
    // Check HTTPS
    $checks['https_enabled'] = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    
    return $checks;
}

// Initialize production environment
createDirectories();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log application start
logMessage('INFO', 'Application started', ['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);

// Set error handler for production
set_error_handler(function($severity, $message, $file, $line) {
    logMessage('ERROR', "PHP Error: {$message} in {$file} on line {$line}", ['severity' => $severity]);
    return true;
});

// Set exception handler for production
set_exception_handler(function($exception) {
    logMessage('ERROR', 'Uncaught Exception: ' . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    // Show generic error page
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
});
?>