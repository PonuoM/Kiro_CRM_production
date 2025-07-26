<?php
/**
 * CRM System Health Check Endpoint
 * 
 * This file provides a health check endpoint for monitoring
 * the system status and performance metrics.
 */

require_once 'config/database.php';
require_once 'config/config.php';

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'version' => APP_VERSION,
    'environment' => APP_ENV,
    'checks' => []
];

try {
    // Database connection check
    $db = Database::getInstance();
    $health['checks']['database'] = $db->isConnected() ? 'ok' : 'error';
    
    if ($health['checks']['database'] === 'error') {
        $health['status'] = 'error';
    }
    
} catch (Exception $e) {
    $health['checks']['database'] = 'error';
    $health['status'] = 'error';
}

// Check file permissions
$health['checks']['logs_writable'] = is_writable(LOG_PATH) ? 'ok' : 'error';
$health['checks']['uploads_writable'] = is_writable(UPLOAD_PATH) ? 'ok' : 'error';
$health['checks']['backups_writable'] = is_writable(BACKUP_PATH) ? 'ok' : 'error';

// Check PHP version
$health['checks']['php_version'] = version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'warning';

// Check required PHP extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'];
foreach ($required_extensions as $ext) {
    $health['checks']['ext_' . $ext] = extension_loaded($ext) ? 'ok' : 'error';
    if (!extension_loaded($ext)) {
        $health['status'] = 'error';
    }
}

// Check disk space (if available)
if (function_exists('disk_free_space')) {
    $free_space = disk_free_space('.');
    $health['checks']['disk_space'] = ($free_space > 100 * 1024 * 1024) ? 'ok' : 'warning'; // 100MB
    $health['disk_free_mb'] = round($free_space / (1024 * 1024), 2);
}

// Check HTTPS
$health['checks']['https_enabled'] = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
    $_SERVER['SERVER_PORT'] == 443
) ? 'ok' : 'warning';

// Check log file sizes
$log_files = ['application.log', 'php_errors.log', 'cron_success.log', 'cron_errors.log'];
foreach ($log_files as $log_file) {
    $log_path = LOG_PATH . $log_file;
    if (file_exists($log_path)) {
        $size = filesize($log_path);
        $health['log_sizes'][$log_file] = round($size / (1024 * 1024), 2) . 'MB';
        
        // Warning if log file is too large
        if ($size > 50 * 1024 * 1024) { // 50MB
            $health['checks']['log_size_' . $log_file] = 'warning';
        }
    }
}

// Update overall status based on critical checks
$critical_checks = ['database', 'logs_writable', 'uploads_writable'];
foreach ($critical_checks as $check) {
    if (isset($health['checks'][$check]) && $health['checks'][$check] === 'error') {
        $health['status'] = 'error';
    }
}

// Response with appropriate HTTP status
if ($health['status'] === 'error') {
    http_response_code(503); // Service Unavailable
} elseif ($health['status'] === 'warning') {
    http_response_code(200); // OK but with warnings
} else {
    http_response_code(200); // OK
}

echo json_encode($health, JSON_PRETTY_PRINT);
?>