<?php
/**
 * Check All Users in Database
 * Show usernames, roles, and test passwords
 */

require_once 'includes/functions.php';

if (!isset($_SESSION)) session_start();

try {
    $db = getDB();
    
    echo "<h2>🔍 ตรวจสอบ Users ทั้งหมดในระบบ</h2>";
    
    $users = $db->query("
        SELECT id, Username, Role, FirstName, LastName, Password, 
               CreatedDate, ModifiedDate 
        FROM users 
        ORDER BY Role, Username
    ");
    
    if ($users) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Role</th><th>ชื่อ-นามสกุล</th>";
        echo "<th>Password Hash</th><th>Test Passwords</th><th>สร้างเมื่อ</th>";
        echo "</tr>";
        
        $test_passwords = ['admin123', 'supervisor123', 'sale123', 'manager123', 'password', '123456'];
        
        foreach ($users as $user) {
            $working_passwords = [];
            
            // Test common passwords
            foreach ($test_passwords as $test_pass) {
                if (password_verify($test_pass, $user['Password'])) {
                    $working_passwords[] = "✅ " . $test_pass;
                }
            }
            
            if (empty($working_passwords)) {
                $working_passwords[] = "❌ ไม่พบรหัสที่ตรงกัน";
            }
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['Username']}</strong></td>";
            echo "<td><span style='background: #e3f2fd; padding: 3px 8px; border-radius: 3px;'>{$user['Role']}</span></td>";
            echo "<td>{$user['FirstName']} {$user['LastName']}</td>";
            echo "<td style='font-family: monospace; font-size: 11px;'>" . substr($user['Password'], 0, 30) . "...</td>";
            echo "<td>" . implode("<br>", $working_passwords) . "</td>";
            echo "<td>" . date('Y-m-d H:i', strtotime($user['CreatedDate'])) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Summary by role
        echo "<h3>📊 สรุปตาม Role</h3>";
        $roles = [];
        foreach ($users as $user) {
            $roles[$user['Role']][] = $user['Username'];
        }
        
        foreach ($roles as $role => $usernames) {
            echo "<div style='margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 4px solid #2196F3;'>";
            echo "<strong>🎯 $role (" . count($usernames) . " users):</strong><br>";
            echo implode(", ", $usernames);
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ ไม่สามารถดึงข้อมูล users ได้</p>";
    }
    
    // Test database connection
    echo "<hr><h3>🔧 Database Connection Status</h3>";
    echo "<p>✅ Database connection: OK</p>";
    echo "<p>📊 Total users found: " . count($users) . "</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ เกิดข้อผิดพลาด:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

// Show common test credentials
echo "<hr><h3>🔑 รหัสผ่านทดสอบทั่วไป:</h3>";
echo "<ul>";
echo "<li><strong>admin123</strong> - สำหรับ Admin role</li>";
echo "<li><strong>supervisor123</strong> - สำหรับ Supervisor role</li>"; 
echo "<li><strong>sale123</strong> - สำหรับ Sales role</li>";
echo "<li><strong>manager123</strong> - สำหรับ Manager role</li>";
echo "</ul>";

echo "<p><a href='pages/login.php'>🔙 กลับไปหน้า Login</a></p>";
?>