<?php
// Utility functions file
// This file contains common functions used throughout the application
// Updated: 2025-07-21 11:20:00 - Added function_exists checks to prevent redeclaration
// Force update: Applied heredoc syntax to admin pages JavaScript

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * URL Helper Functions for Path Management
 */

/**
 * Generate base URL with path
 * @param string $path
 * @return string
 */
function baseUrl($path = '') {
    return BASE_URL . ltrim($path, '/');
}

/**
 * Generate pages URL
 * @param string $path
 * @return string
 */
function pageUrl($path = '') {
    return PAGES_URL . ltrim($path, '/');
}

/**
 * Generate API URL
 * @param string $path
 * @return string
 */
function apiUrl($path = '') {
    return API_URL . ltrim($path, '/');
}

/**
 * Generate admin URL
 * @param string $path
 * @return string
 */
function adminUrl($path = '') {
    return ADMIN_URL . ltrim($path, '/');
}

/**
 * Generate assets URL
 * @param string $path
 * @return string
 */
function assetUrl($path = '') {
    return ASSETS_URL . ltrim($path, '/');
}

/**
 * Generate unique customer code
 * @return string
 */
function generateCustomerCode() {
    $prefix = 'CUS';
    $timestamp = date('YmdHis');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $timestamp . $random;
}

/**
 * Generate unique document number for orders
 * @return string
 */
function generateDocumentNo() {
    $prefix = 'DOC';
    $timestamp = date('YmdHis');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $prefix . $timestamp . $random;
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

/**
 * Validate phone number format
 * @param string $phone
 * @return bool
 */
function validatePhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if phone number is between 9-10 digits
    return strlen($phone) >= 9 && strlen($phone) <= 10;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 * @param string $required_role
 * @return bool
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? '';
    
    // Admin has access to everything
    if ($user_role === 'Admin') {
        return true;
    }
    
    // Check specific role
    return $user_role === $required_role;
}

/**
 * Redirect to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Use relative path for compatibility
        header('Location: ../pages/login.php');
        exit();
    }
}

/**
 * Log activity
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    $log_entry = date('Y-m-d H:i:s') . " - " . $action;
    if (!empty($details)) {
        $log_entry .= " - " . $details;
    }
    $log_entry .= "\n";
    
    error_log($log_entry, 3, __DIR__ . '/../logs/activity.log');
}

/**
 * Format Thai date
 * @param string $date
 * @return string
 */
function formatThaiDate($date) {
    if (empty($date)) return '';
    
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $thai_months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543; // Convert to Buddhist year
    
    return $day . ' ' . $month . ' ' . $year;
}

/**
 * Send JSON response
 * @param array $data
 * @param int $status_code
 */
function sendJsonResponse($data, $status_code = 200) {
    // Clear any existing output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    
    // Ensure data can be JSON encoded
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        $json = json_encode(['success' => false, 'message' => 'JSON encoding error']);
    }
    
    echo $json;
    exit();
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 * @return string
 */
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current username
 * @return string|null
 */
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Set user session data
 * @param array $user_data
 */
function setUserSession($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['username'] = $user_data['Username'];
    $_SESSION['user_role'] = $user_data['Role'];
    $_SESSION['first_name'] = $user_data['FirstName'];
    $_SESSION['last_name'] = $user_data['LastName'];
    $_SESSION['company_code'] = $user_data['CompanyCode'];
}

/**
 * Clear user session
 */
function clearUserSession() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['user_role']);
    unset($_SESSION['first_name']);
    unset($_SESSION['last_name']);
    unset($_SESSION['company_code']);
    unset($_SESSION['csrf_token']);
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate required fields
 * @param array $data
 * @param array $required_fields
 * @return array Array of missing fields
 */
function validateRequiredFields($data, $required_fields) {
    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }
    return $missing;
}

/**
 * Get database instance
 * @return Database
 */
function getDB() {
    return Database::getInstance();
}

/**
 * Execute database query with error handling
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbQuery($sql, $params = []) {
    try {
        return getDB()->query($sql, $params);
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute database query and return single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function dbQueryOne($sql, $params = []) {
    try {
        return getDB()->queryOne($sql, $params);
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute database insert/update/delete
 * @param string $sql
 * @param array $params
 * @return bool
 */
function dbExecute($sql, $params = []) {
    try {
        return getDB()->execute($sql, $params);
    } catch (Exception $e) {
        error_log("Database execute error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update customer CartStatus with audit trail
 * @param string $customer_code
 * @param string $new_cart_status
 * @param string $reason
 * @param string $changed_by
 * @return bool
 */
function updateCustomerCartStatus($customer_code, $new_cart_status, $reason = '', $changed_by = 'SYSTEM') {
    try {
        $db = getDB();
        
        // Get current status for audit trail
        $current_customer = dbQueryOne(
            "SELECT CartStatus FROM customers WHERE CustomerCode = ?", 
            [$customer_code]
        );
        
        if (!$current_customer) {
            error_log("Customer not found: " . $customer_code);
            return false;
        }
        
        $old_cart_status = $current_customer['CartStatus'];
        
        // Skip if status is already the same
        if ($old_cart_status === $new_cart_status) {
            return true;
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Update customer status
            $update_sql = "
                UPDATE customers 
                SET CartStatus = ?, 
                    ModifiedDate = NOW(), 
                    ModifiedBy = ?
                WHERE CustomerCode = ?
            ";
            
            $success = $db->execute($update_sql, [$new_cart_status, $changed_by, $customer_code]);
            
            if ($success) {
                // Log the change in audit trail
                logCustomerAuditTrail($customer_code, 'CartStatus', $old_cart_status, $new_cart_status, $reason, $changed_by);
                
                // Commit transaction
                $db->commit();
                return true;
            } else {
                $db->rollback();
                return false;
            }
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error updating customer cart status: " . $e->getMessage());
        return false;
    }
}

/**
 * Update customer status (CustomerStatus) with audit trail
 * @param string $customer_code
 * @param string $new_customer_status
 * @param string $reason
 * @param string $changed_by
 * @return bool
 */
function updateCustomerStatus($customer_code, $new_customer_status, $reason = '', $changed_by = 'SYSTEM') {
    try {
        $db = getDB();
        
        // Get current status for audit trail
        $current_customer = dbQueryOne(
            "SELECT CustomerStatus FROM customers WHERE CustomerCode = ?", 
            [$customer_code]
        );
        
        if (!$current_customer) {
            error_log("Customer not found: " . $customer_code);
            return false;
        }
        
        $old_customer_status = $current_customer['CustomerStatus'];
        
        // Skip if status is already the same
        if ($old_customer_status === $new_customer_status) {
            return true;
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Update customer status
            $update_sql = "
                UPDATE customers 
                SET CustomerStatus = ?, 
                    ModifiedDate = NOW(), 
                    ModifiedBy = ?
                WHERE CustomerCode = ?
            ";
            
            $success = $db->execute($update_sql, [$new_customer_status, $changed_by, $customer_code]);
            
            if ($success) {
                // Log the change in audit trail
                logCustomerAuditTrail($customer_code, 'CustomerStatus', $old_customer_status, $new_customer_status, $reason, $changed_by);
                
                // Commit transaction
                $db->commit();
                return true;
            } else {
                $db->rollback();
                return false;
            }
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error updating customer status: " . $e->getMessage());
        return false;
    }
}

/**
 * Log customer audit trail for status changes
 * @param string $customer_code
 * @param string $field_name
 * @param string $old_value
 * @param string $new_value
 * @param string $reason
 * @param string $changed_by
 */
function logCustomerAuditTrail($customer_code, $field_name, $old_value, $new_value, $reason = '', $changed_by = 'SYSTEM') {
    $audit_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'customer_code' => $customer_code,
        'field' => $field_name,
        'old_value' => $old_value,
        'new_value' => $new_value,
        'reason' => $reason,
        'changed_by' => $changed_by
    ];
    
    $log_message = json_encode($audit_entry, JSON_UNESCAPED_UNICODE);
    
    // Log to activity log
    logActivity('Customer Audit Trail', $log_message);
    
    // Also log to a separate audit log file for better tracking
    $audit_log_entry = date('Y-m-d H:i:s') . " | " . $customer_code . " | " . $field_name . 
                      " | " . $old_value . " → " . $new_value . " | " . $reason . " | " . $changed_by . "\n";
    
    error_log($audit_log_entry, 3, __DIR__ . '/../logs/audit_trail.log');
}

/**
 * Get customer audit trail history
 * @param string $customer_code
 * @param int $limit
 * @return array
 */
function getCustomerAuditTrail($customer_code, $limit = 50) {
    $audit_trail = [];
    $log_file = __DIR__ . '/../logs/audit_trail.log';
    
    if (!file_exists($log_file)) {
        return $audit_trail;
    }
    
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Show newest first
    
    $count = 0;
    foreach ($lines as $line) {
        if ($count >= $limit) break;
        
        if (strpos($line, $customer_code) !== false) {
            $parts = explode(' | ', $line);
            if (count($parts) >= 6) {
                $audit_trail[] = [
                    'timestamp' => $parts[0],
                    'customer_code' => $parts[1],
                    'field' => $parts[2],
                    'change' => $parts[3],
                    'reason' => $parts[4],
                    'changed_by' => $parts[5]
                ];
                $count++;
            }
        }
    }
    
    return $audit_trail;
}

/**
 * Validate CartStatus value
 * @param string $cart_status
 * @return bool
 */
function validateCartStatus($cart_status) {
    $valid_statuses = ['ตะกร้าแจก', 'ตะกร้ารอ', 'กำลังดูแล'];
    return in_array($cart_status, $valid_statuses);
}

/**
 * Validate CustomerStatus value
 * @param string $customer_status
 * @return bool
 */
function validateCustomerStatus($customer_status) {
    $valid_statuses = ['ลูกค้าใหม่', 'ลูกค้าติดตาม', 'ลูกค้าเก่า'];
    return in_array($customer_status, $valid_statuses);
}

/**
 * Get customers by CartStatus
 * @param string $cart_status
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getCustomersByCartStatus($cart_status, $limit = 50, $offset = 0) {
    if (!validateCartStatus($cart_status)) {
        return [];
    }
    
    $sql = "
        SELECT CustomerCode, CustomerName, CustomerTel, CustomerStatus, 
               CartStatus, Sales, AssignDate, CreatedDate, ModifiedDate
        FROM customers 
        WHERE CartStatus = ?
        ORDER BY ModifiedDate DESC, CreatedDate DESC
        LIMIT ? OFFSET ?
    ";
    
    return dbQuery($sql, [$cart_status, $limit, $offset]) ?: [];
}

/**
 * Get customers by CustomerStatus
 * @param string $customer_status
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getCustomersByStatus($customer_status, $limit = 50, $offset = 0) {
    if (!validateCustomerStatus($customer_status)) {
        return [];
    }
    
    $sql = "
        SELECT CustomerCode, CustomerName, CustomerTel, CustomerStatus, 
               CartStatus, Sales, AssignDate, CreatedDate, ModifiedDate
        FROM customers 
        WHERE CustomerStatus = ?
        ORDER BY ModifiedDate DESC, CreatedDate DESC
        LIMIT ? OFFSET ?
    ";
    
    return dbQuery($sql, [$customer_status, $limit, $offset]) ?: [];
}

/**
 * Count customers by status
 * @param string $status_field Either 'CustomerStatus' or 'CartStatus'
 * @param string $status_value
 * @return int
 */
function countCustomersByStatus($status_field, $status_value) {
    if (!in_array($status_field, ['CustomerStatus', 'CartStatus'])) {
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count FROM customers WHERE {$status_field} = ?";
    $result = dbQueryOne($sql, [$status_value]);
    
    return $result ? (int)$result['count'] : 0;
}

/**
 * Enhanced error logging for cron jobs
 * @param string $job_name
 * @param string $error_message
 * @param array $context
 */
function logCronError($job_name, $error_message, $context = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'job' => $job_name,
        'error' => $error_message,
        'context' => $context,
        'server' => $_SERVER['SERVER_NAME'] ?? 'CLI',
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true)
    ];
    
    $log_message = json_encode($log_entry, JSON_UNESCAPED_UNICODE);
    
    // Log to general error log
    error_log("CRON ERROR: " . $log_message);
    
    // Log to specific cron error log
    error_log($log_message . "\n", 3, __DIR__ . '/../logs/cron_errors.log');
    
    // Also log to activity log
    logActivity('Cron Job Error', $job_name . ': ' . $error_message);
}

/**
 * Log successful cron job execution
 * @param string $job_name
 * @param array $stats
 * @param float $execution_time
 */
function logCronSuccess($job_name, $stats = [], $execution_time = 0) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'job' => $job_name,
        'status' => 'success',
        'stats' => $stats,
        'execution_time' => $execution_time,
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true)
    ];
    
    $log_message = json_encode($log_entry, JSON_UNESCAPED_UNICODE);
    
    // Log to cron success log
    error_log($log_message . "\n", 3, __DIR__ . '/../logs/cron_success.log');
    
    // Also log to activity log
    logActivity('Cron Job Success', $job_name . ': ' . json_encode($stats, JSON_UNESCAPED_UNICODE));
}

/**
 * Format bytes to human readable format
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>