<?php
/**
 * User Model
 * Handles user-related database operations
 */

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel {
    protected $table = 'users';
    
    /**
     * Find user by username
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username) {
        return $this->findOne(['Username' => $username]);
    }
    
    /**
     * Find user by email
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email) {
        return $this->findOne(['Email' => $email]);
    }
    
    /**
     * Create new user
     * @param array $userData
     * @return string|false
     */
    public function createUser($userData) {
        // Hash password before storing
        if (isset($userData['Password'])) {
            $userData['Password'] = hashPassword($userData['Password']);
        }
        
        // Set default values
        $userData['CreatedDate'] = date('Y-m-d H:i:s');
        $userData['CreatedBy'] = getCurrentUsername() ?? 'system';
        $userData['Status'] = 1;
        
        return $this->insert($userData);
    }
    
    /**
     * Update user data
     * @param int $userId
     * @param array $userData
     * @return bool
     */
    public function updateUser($userId, $userData) {
        // Hash password if provided
        if (isset($userData['Password']) && !empty($userData['Password'])) {
            $userData['Password'] = hashPassword($userData['Password']);
        } else {
            // Remove password from update if empty
            unset($userData['Password']);
        }
        
        // Set modified data
        $userData['ModifiedDate'] = date('Y-m-d H:i:s');
        $userData['ModifiedBy'] = getCurrentUsername() ?? 'system';
        
        return $this->update($userId, $userData);
    }
    
    /**
     * Authenticate user
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if ($user && $user['Status'] == 1 && verifyPassword($password, $user['Password'])) {
            // Update last login date
            $this->update($user['id'], [
                'LastLoginDate' => date('Y-m-d H:i:s')
            ]);
            
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get active users by role
     * @param string $role
     * @return array
     */
    public function getUsersByRole($role) {
        return $this->findAll(['Role' => $role, 'Status' => 1], 'FirstName, LastName');
    }
    
    /**
     * Get all active users
     * @return array
     */
    public function getActiveUsers() {
        return $this->findAll(['Status' => 1], 'FirstName, LastName');
    }
    
    /**
     * Check if username exists
     * @param string $username
     * @param int $excludeUserId
     * @return bool
     */
    public function usernameExists($username, $excludeUserId = null) {
        $conditions = ['Username' => $username];
        
        if ($excludeUserId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Username = ? AND id != ?";
            $result = $this->queryOne($sql, [$username, $excludeUserId]);
            return $result && $result['count'] > 0;
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Check if email exists
     * @param string $email
     * @param int $excludeUserId
     * @return bool
     */
    public function emailExists($email, $excludeUserId = null) {
        if (empty($email)) return false;
        
        $conditions = ['Email' => $email];
        
        if ($excludeUserId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE Email = ? AND id != ?";
            $result = $this->queryOne($sql, [$email, $excludeUserId]);
            return $result && $result['count'] > 0;
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Deactivate user
     * @param int $userId
     * @return bool
     */
    public function deactivateUser($userId) {
        return $this->update($userId, [
            'Status' => 0,
            'ModifiedDate' => date('Y-m-d H:i:s'),
            'ModifiedBy' => getCurrentUsername() ?? 'system'
        ]);
    }
    
    /**
     * Activate user
     * @param int $userId
     * @return bool
     */
    public function activateUser($userId) {
        return $this->update($userId, [
            'Status' => 1,
            'ModifiedDate' => date('Y-m-d H:i:s'),
            'ModifiedBy' => getCurrentUsername() ?? 'system'
        ]);
    }
    
    /**
     * Validate user data
     * @param array $userData
     * @param bool $isUpdate
     * @return array Array of validation errors
     */
    public function validateUserData($userData, $isUpdate = false) {
        $errors = [];
        
        // Required fields for new user
        if (!$isUpdate) {
            $required = ['Username', 'Password', 'FirstName', 'LastName', 'Role'];
            $missing = validateRequiredFields($userData, $required);
            if (!empty($missing)) {
                $errors[] = 'ข้อมูลที่จำเป็น: ' . implode(', ', $missing);
            }
        }
        
        // Username validation
        if (isset($userData['Username'])) {
            if (strlen($userData['Username']) < 3) {
                $errors[] = 'Username ต้องมีอย่างน้อย 3 ตัวอักษร';
            }
            
            $excludeId = $isUpdate && isset($userData['id']) ? $userData['id'] : null;
            if ($this->usernameExists($userData['Username'], $excludeId)) {
                $errors[] = 'Username นี้มีอยู่ในระบบแล้ว';
            }
        }
        
        // Password validation (for new users or when password is provided)
        if (isset($userData['Password']) && !empty($userData['Password'])) {
            if (strlen($userData['Password']) < 6) {
                $errors[] = 'Password ต้องมีอย่างน้อย 6 ตัวอักษร';
            }
        }
        
        // Email validation
        if (isset($userData['Email']) && !empty($userData['Email'])) {
            if (!validateEmail($userData['Email'])) {
                $errors[] = 'รูปแบบ Email ไม่ถูกต้อง';
            }
            
            $excludeId = $isUpdate && isset($userData['id']) ? $userData['id'] : null;
            if ($this->emailExists($userData['Email'], $excludeId)) {
                $errors[] = 'Email นี้มีอยู่ในระบบแล้ว';
            }
        }
        
        // Phone validation
        if (isset($userData['Phone']) && !empty($userData['Phone'])) {
            if (!validatePhoneNumber($userData['Phone'])) {
                $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
            }
        }
        
        // Role validation
        if (isset($userData['Role'])) {
            $validRoles = ['Admin', 'Supervisor', 'Sales'];
            if (!in_array($userData['Role'], $validRoles)) {
                $errors[] = 'บทบาทไม่ถูกต้อง';
            }
        }
        
        return $errors;
    }
}
?>