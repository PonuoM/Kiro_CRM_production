<?php
/**
 * Role-Based Access Control System
 * CRM System Permissions Management
 */

class Permissions {
    
    // Role permissions matrix
    private static $rolePermissions = [
        'superadmin' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => true,
            'user_management' => true,
            'manage_users' => true,
            'manage_roles' => true,
            'system_settings' => true,
            'import_customers' => true,
            'call_history' => true,
            'order_history' => true,
            'sales_performance' => true,
            'daily_tasks' => true,
            'view_all_data' => true,
            'manage_customers' => true,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => true,
            'waiting_basket' => true,
            'supervisor_dashboard' => true,
            'intelligence_system' => true,
            'bulk_operations' => true,
            'advanced_reports' => true
        ],
        'admin' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => true,
            'user_management' => true,
            'manage_users' => true,
            'manage_roles' => false,
            'system_settings' => false,
            'import_customers' => true,
            'call_history' => true,
            'order_history' => true,
            'sales_performance' => true,
            'daily_tasks' => true,
            'view_all_data' => true,
            'manage_customers' => true,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => true,
            'waiting_basket' => true,
            'supervisor_dashboard' => false,
            'intelligence_system' => true,
            'bulk_operations' => false,
            'advanced_reports' => false
        ],
        'supervisor' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => true,
            'user_management' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'system_settings' => false,
            'import_customers' => false,
            'call_history' => true,
            'order_history' => true,
            'sales_performance' => true,
            'daily_tasks' => true,
            'view_all_data' => true,
            'manage_customers' => true,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => false,
            'waiting_basket' => false,
            'supervisor_dashboard' => true,
            'intelligence_system' => true,
            'bulk_operations' => false,
            'advanced_reports' => true
        ],
        'manager' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => true,
            'user_management' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'system_settings' => false,
            'import_customers' => false,
            'call_history' => true,
            'order_history' => true,
            'sales_performance' => true,
            'daily_tasks' => true,
            'view_all_data' => true,
            'manage_customers' => true,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => false,
            'waiting_basket' => false,
            'supervisor_dashboard' => false,
            'intelligence_system' => false,
            'bulk_operations' => false,
            'advanced_reports' => false
        ],
        'sales' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => false,
            'user_management' => false,
            'manage_users' => false,
            'manage_roles' => false,
            'system_settings' => false,
            'import_customers' => false,
            'call_history' => true,
            'order_history' => false,
            'sales_performance' => false,
            'daily_tasks' => true,
            'view_all_data' => false,  // Only assigned customers
            'manage_customers' => false,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => false,
            'waiting_basket' => false,
            'supervisor_dashboard' => false,
            'intelligence_system' => false,
            'bulk_operations' => false,
            'advanced_reports' => false
        ]
    ];
    
    /**
     * Check if current user has permission
     */
    public static function hasPermission($permission) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        $role = strtolower($_SESSION['user_role']);
        return self::$rolePermissions[$role][$permission] ?? false;
    }
    
    /**
     * Require specific permission or redirect
     */
    public static function requirePermission($permission, $redirectUrl = null) {
        if (!self::hasPermission($permission)) {
            if ($redirectUrl === null) {
                // Detect if we're in admin directory
                if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
                    $redirectUrl = '../login.php';
                } else {
                    $redirectUrl = 'login.php';
                }
            }
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function requireLogin($redirectUrl = null) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            if ($redirectUrl === null) {
                // Detect if we're in admin directory
                if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
                    $redirectUrl = '../login.php';
                } else {
                    $redirectUrl = 'login.php';
                }
            }
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentRole() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return strtolower($_SESSION['user_role'] ?? '');
    }
    
    /**
     * Get current username
     */
    public static function getCurrentUser() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['username'] ?? '';
    }
    
    /**
     * Check if user can view all data or only assigned
     */
    public static function canViewAllData() {
        return self::hasPermission('view_all_data');
    }
    
    /**
     * Get SQL filter for customer data based on role
     */
    public static function getCustomerFilter() {
        if (self::canViewAllData()) {
            return ''; // No filter - can see all
        } else {
            $username = self::getCurrentUser();
            return "AND (Sales = '$username' OR Sales IS NULL)";
        }
    }
    
    /**
     * Get available menu items based on role
     */
    public static function getMenuItems() {
        $items = [];
        
        if (self::hasPermission('dashboard')) {
            $items[] = ['url' => 'dashboard.php', 'title' => 'แดชบอร์ด', 'icon' => '📊'];
        }
        
        if (self::hasPermission('customer_list')) {
            $items[] = ['url' => 'customer_list_demo.php', 'title' => 'รายชื่อลูกค้า', 'icon' => '👥'];
        }
        
        if (self::hasPermission('daily_tasks')) {
            $items[] = ['url' => 'daily_tasks_demo.php', 'title' => 'งานประจำวัน', 'icon' => '📅'];
        }
        
        if (self::hasPermission('call_history')) {
            $items[] = ['url' => 'call_history_demo.php', 'title' => 'ประวัติการโทร', 'icon' => '📞'];
        }
        
        if (self::hasPermission('order_history')) {
            $items[] = ['url' => 'order_history_demo.php', 'title' => 'ประวัติคำสั่งซื้อ', 'icon' => '🛒'];
        }
        
        if (self::hasPermission('sales_performance')) {
            $items[] = ['url' => 'sales_performance.php', 'title' => 'ประสิทธิภาพการขาย', 'icon' => '📈'];
        }
        
        if (self::hasPermission('user_management')) {
            $items[] = ['url' => 'admin/user_management.php', 'title' => 'จัดการผู้ใช้', 'icon' => '👤'];
        }
        
        if (self::hasPermission('import_customers')) {
            $items[] = ['url' => 'admin/import_customers.php', 'title' => 'นำเข้าลูกค้า', 'icon' => '📥'];
        }
        
        // Phase 2: SuperAdmin and Admin Features
        if (self::hasPermission('distribution_basket')) {
            $items[] = ['url' => 'admin/distribution_basket.php', 'title' => 'ตะกร้าแจกลูกค้า', 'icon' => '📦'];
        }
        
        if (self::hasPermission('waiting_basket')) {
            $items[] = ['url' => 'admin/waiting_basket.php', 'title' => 'ตะกร้ารอ', 'icon' => '⏳'];
        }
        
        if (self::hasPermission('supervisor_dashboard')) {
            $items[] = ['url' => 'admin/supervisor_dashboard.php', 'title' => 'แดชบอร์ดผู้ควบคุม', 'icon' => '📊'];
        }
        
        if (self::hasPermission('intelligence_system')) {
            $items[] = ['url' => 'admin/intelligence_system.php', 'title' => 'ระบบวิเคราะห์ลูกค้า', 'icon' => '🧠'];
        }
        
        if (self::hasPermission('advanced_reports')) {
            $items[] = ['url' => 'admin/advanced_reports.php', 'title' => 'รายงานขั้นสูง', 'icon' => '📈'];
        }
        
        return $items;
    }
}