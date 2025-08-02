<?php
/**
 * Simplified Role-Based Access Control System - 3 Roles Only
 * CRM System Permissions Management
 * Admin > Supervisor > Sales hierarchy
 */

class Permissions {
    
    // Simplified role permissions matrix - 3 roles only
    private static $rolePermissions = [
        'admin' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => true,
            'user_management' => true,
            'manage_users' => true,
            'system_settings' => true,
            'import_customers' => true,
            'call_history' => true,
            'order_history' => true,
            'sales_performance' => true,
            'daily_tasks' => true,
            'view_all_data' => true,  // See all teams and data
            'manage_customers' => true,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => true,
            'waiting_basket' => true,
            'supervisor_dashboard' => true,  // Can see supervisor tools
            'intelligence_system' => true,
            'bulk_operations' => true,
            'advanced_reports' => true
        ],
        'supervisor' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => true,
            'user_management' => false,  // Cannot manage users
            'manage_users' => false,
            'system_settings' => false,
            'import_customers' => false,
            'call_history' => true,
            'order_history' => true,
            'sales_performance' => true,
            'daily_tasks' => true,
            'view_all_data' => false,  // Only see own team data
            'view_team_data' => true,  // Can see team members' data
            'manage_customers' => true,
            'create_call_log' => true,
            'create_task' => true,
            'create_order' => true,
            'distribution_basket' => false,
            'waiting_basket' => false,
            'supervisor_dashboard' => true,  // Can see supervisor tools
            'intelligence_system' => true,
            'bulk_operations' => false,
            'advanced_reports' => true
        ],
        'sales' => [
            'dashboard' => true,
            'customer_list' => true,
            'customer_detail' => true,
            'customer_edit' => false,  // Cannot edit customer info
            'user_management' => false,
            'manage_users' => false,
            'system_settings' => false,
            'import_customers' => false,
            'call_history' => true,
            'order_history' => false,  // Cannot see order history
            'sales_performance' => false,  // Cannot see performance reports
            'daily_tasks' => true,
            'view_all_data' => false,  // Only see assigned customers
            'view_team_data' => false,  // Cannot see team data
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
        
        // Check for user_role first, then fall back to role
        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
        
        if (!$userRole) {
            return false;
        }
        
        $role = strtolower($userRole);
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
     * Check if user is logged in (without redirect)
     */
    public static function isLoggedIn() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Check if user is logged in and redirect if not
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
     * Get current user information
     */
    public static function getCurrentUser() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['username'] ?? 'Unknown';
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentRole() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_role'] ?? 'Unknown';
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check if current user can view all data (Admin only)
     */
    public static function canViewAllData() {
        return self::hasPermission('view_all_data');
    }
    
    /**
     * Check if current user can view team data (Admin + Supervisor)
     */
    public static function canViewTeamData() {
        return self::hasPermission('view_all_data') || self::hasPermission('view_team_data');
    }
    
    /**
     * Get team members for supervisor (if current user is supervisor)
     * Returns array of user IDs that report to current user
     */
    public static function getTeamMembers() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $currentUserId = $_SESSION['user_id'] ?? null;
        $currentRole = strtolower($_SESSION['user_role'] ?? '');
        
        if (!$currentUserId) {
            return [];
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            
            if ($currentRole === 'admin') {
                // Admin can see all users
                $stmt = $db->getConnection()->prepare("
                    SELECT id, username, role 
                    FROM users 
                    WHERE status = 1 AND id != ?
                    ORDER BY role, username
                ");
                $stmt->execute([$currentUserId]);
                return $stmt->fetchAll();
                
            } elseif ($currentRole === 'supervisor') {
                // Supervisor can see their team members
                $stmt = $db->getConnection()->prepare("
                    SELECT id, username, role 
                    FROM users 
                    WHERE supervisor_id = ? AND status = 1
                    ORDER BY username
                ");
                $stmt->execute([$currentUserId]);
                return $stmt->fetchAll();
                
            } else {
                // Sales can only see themselves
                return [];
            }
            
        } catch (Exception $e) {
            error_log("Error getting team members: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get customers based on user role and team hierarchy
     */
    public static function getAccessibleCustomerIds() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $currentUserId = $_SESSION['user_id'] ?? null;
        $currentRole = strtolower($_SESSION['user_role'] ?? '');
        $currentUsername = $_SESSION['username'] ?? '';
        
        if (!$currentUserId) {
            return [];
        }
        
        try {
            require_once __DIR__ . '/../config/database.php';
            $db = Database::getInstance();
            
            if ($currentRole === 'admin') {
                // Admin can see all customers
                $stmt = $db->getConnection()->query("SELECT CustomerCode FROM customers");
                return array_column($stmt->fetchAll(), 'CustomerCode');
                
            } elseif ($currentRole === 'supervisor') {
                // Supervisor can see customers assigned to their team members + themselves
                $teamMembers = self::getTeamMembers();
                $teamUsernames = array_column($teamMembers, 'username');
                $teamUsernames[] = $currentUsername; // Include supervisor's own customers
                
                if (empty($teamUsernames)) {
                    return [];
                }
                
                $placeholders = str_repeat('?,', count($teamUsernames) - 1) . '?';
                $stmt = $db->getConnection()->prepare("
                    SELECT CustomerCode 
                    FROM customers 
                    WHERE Sales IN ($placeholders)
                ");
                $stmt->execute($teamUsernames);
                return array_column($stmt->fetchAll(), 'CustomerCode');
                
            } else {
                // Sales can only see customers assigned to them
                $stmt = $db->getConnection()->prepare("
                    SELECT CustomerCode 
                    FROM customers 
                    WHERE Sales = ?
                ");
                $stmt->execute([$currentUsername]);
                return array_column($stmt->fetchAll(), 'CustomerCode');
            }
            
        } catch (Exception $e) {
            error_log("Error getting accessible customers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get menu items based on user role
     */
    public static function getMenuItems() {
        $role = strtolower(self::getCurrentRole());
        $menuItems = [];
        
        // Dashboard - everyone can see
        if (self::hasPermission('dashboard')) {
            $menuItems[] = ['url' => 'dashboard.php', 'title' => 'แดชบอร์ด', 'icon' => 'fas fa-tachometer-alt'];
        }
        
        // Sales records - everyone can see (but different data)  
        if (self::hasPermission('customer_list')) {
            $menuItems[] = ['url' => 'customer_list_dynamic.php', 'title' => 'รายการขาย', 'icon' => 'fas fa-chart-line'];
        }
        
        if (self::hasPermission('order_history')) {
            $menuItems[] = ['url' => 'order_history_demo.php', 'title' => 'ประวัติคำสั่งซื้อ', 'icon' => 'fas fa-shopping-cart'];
        }
        
        // งานประจำวัน - ถูกลบออกแล้ว เนื่องจากมีข้อมูลเดียวกันในแดชบอร์ด
        // if (self::hasPermission('daily_tasks')) {
        //     $menuItems[] = ['url' => 'daily_tasks_demo.php', 'title' => 'งานประจำวัน', 'icon' => 'fas fa-tasks'];
        // }
        
        // Call History - ถูกลบออกตามคำสั่ง (2025-07-29)
        // if (self::hasPermission('call_history')) {
        //     $menuItems[] = ['url' => 'call_history_selector.php', 'title' => 'ประวัติการโทร', 'icon' => 'fas fa-phone'];
        // }
        
        // Admin tools - only admin
        if (self::hasPermission('import_customers')) {
            $menuItems[] = ['url' => 'admin/enhanced_import_customers_with_preview.php', 'title' => 'นำเข้าลูกค้า', 'icon' => 'fas fa-file-import'];
        }
        
        if (self::hasPermission('user_management')) {
            $menuItems[] = ['url' => 'admin/user_management.php', 'title' => 'จัดการผู้ใช้งาน', 'icon' => 'fas fa-users-cog'];
        }
        
        if (self::hasPermission('distribution_basket')) {
            $menuItems[] = ['url' => 'admin/distribution_basket.php', 'title' => 'ตะกร้าแจกลูกค้า', 'icon' => 'fas fa-inbox'];
        }
        
        if (self::hasPermission('waiting_basket')) {
            $menuItems[] = ['url' => 'admin/waiting_basket.php', 'title' => 'ตะกร้ารอ', 'icon' => 'fas fa-hourglass-half'];
        }
        
        // Supervisor + Admin tools
        if (self::hasPermission('supervisor_dashboard')) {
            $menuItems[] = ['url' => 'admin/supervisor_dashboard.php', 'title' => 'แดชบอร์ดหัวหน้า', 'icon' => 'fas fa-chart-bar'];
        }
        
        if (self::hasPermission('intelligence_system')) {
            $menuItems[] = ['url' => 'admin/intelligence_system.php', 'title' => 'ระบบวิเคราะห์ลูกค้า', 'icon' => 'fas fa-brain'];
        }
        
        // Reports - supervisor and admin
        if (self::hasPermission('sales_performance')) {
            $menuItems[] = ['url' => 'sales_performance.php', 'title' => 'รายงานประสิทธิภาพการขาย', 'icon' => 'fas fa-chart-line'];
        }
        
        return $menuItems;
    }
}
?>