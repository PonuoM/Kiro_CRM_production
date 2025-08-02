<?php
/**
 * Password Reset Tool
 * เครื่องมือสำหรับ reset password users
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// เริ่ม session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>🔐 Password Reset Tool</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body style='padding:20px;'>";

echo "<h1>🔐 Password Reset Tool</h1>";
echo "<p>เครื่องมือสำหรับ reset password ของ users ในระบบ</p>";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        require_once 'config/database.php';
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        if ($_POST['action'] === 'reset_password') {
            $userId = $_POST['user_id'];
            $newPassword = $_POST['new_password'];
            $hashMethod = $_POST['hash_method'];
            
            // เตรียม password ตาม method ที่เลือก
            $hashedPassword = $newPassword;
            switch ($hashMethod) {
                case 'plain':
                    $hashedPassword = $newPassword;
                    break;
                case 'md5':
                    $hashedPassword = md5($newPassword);
                    break;
                case 'sha1':
                    $hashedPassword = sha1($newPassword);
                    break;
                case 'password_hash':
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    break;
            }
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET Password = ?, ModifiedDate = NOW() WHERE id = ?");
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result) {
                echo "<div class='alert alert-success'>";
                echo "<h5>✅ Password Reset สำเร็จ!</h5>";
                echo "<p>User ID: $userId ได้รับ password ใหม่แล้ว</p>";
                echo "<p><strong>New Password:</strong> $newPassword</p>";
                echo "<p><strong>Hash Method:</strong> $hashMethod</p>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-danger'>❌ Password Reset ล้มเหลว</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// Display users and current password info
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='alert alert-info'>";
    echo "<h5>👥 Users ในระบบและ Password Info:</h5>";
    echo "</div>";
    
    $users = $pdo->query("
        SELECT id, Username, Password, 
               CONCAT(IFNULL(FirstName, ''), ' ', IFNULL(LastName, '')) as FullName,
               Role, Status
        FROM users 
        WHERE Status = 1 
        ORDER BY Role, Username
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "<table class='table table-bordered'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Username</th>";
        echo "<th>Full Name</th>";
        echo "<th>Role</th>";
        echo "<th>Password Info</th>";
        echo "<th>Actions</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($users as $user) {
            $passwordInfo = '';
            $passwordLength = strlen($user['Password']);
            
            // วิเคราะห์รูปแบบ password
            if ($passwordLength == 32 && ctype_xdigit($user['Password'])) {
                $passwordInfo = "<span class='badge bg-warning'>MD5 Hash (32 chars)</span>";
            } elseif ($passwordLength == 40 && ctype_xdigit($user['Password'])) {
                $passwordInfo = "<span class='badge bg-info'>SHA1 Hash (40 chars)</span>";
            } elseif ($passwordLength >= 60 && strpos($user['Password'], '$') !== false) {
                $passwordInfo = "<span class='badge bg-success'>password_hash</span>";
            } else {
                $passwordInfo = "<span class='badge bg-secondary'>Plain Text ($passwordLength chars)</span>";
            }
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td><strong>{$user['Username']}</strong></td>";
            echo "<td>{$user['FullName']}</td>";
            echo "<td><span class='badge bg-primary'>{$user['Role']}</span></td>";
            echo "<td>$passwordInfo</td>";
            echo "<td>";
            echo "<button class='btn btn-sm btn-warning' onclick='showResetForm({$user['id']}, \"{$user['Username']}\")'>🔐 Reset Password</button>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Database Error: " . $e->getMessage() . "</div>";
}

// Password Reset Form (Hidden)
echo "<div id='resetForm' class='card mt-4' style='display:none;'>";
echo "<div class='card-header bg-warning text-dark'>";
echo "<h5>🔐 Reset Password</h5>";
echo "</div>";
echo "<div class='card-body'>";

echo "<form method='POST'>";
echo "<input type='hidden' name='action' value='reset_password'>";
echo "<input type='hidden' name='user_id' id='reset_user_id'>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>User:</label>";
echo "<input type='text' id='reset_username' class='form-control' readonly>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>New Password:</label>";
echo "<input type='text' name='new_password' class='form-control' placeholder='ใส่ password ใหม่' required>";
echo "<div class='form-text'>ใส่ password ที่ต้องการใหม่</div>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>Hash Method:</label>";
echo "<select name='hash_method' class='form-control' required>";
echo "<option value='plain'>Plain Text (ไม่แฮช)</option>";
echo "<option value='md5'>MD5 Hash</option>";
echo "<option value='sha1'>SHA1 Hash</option>";
echo "<option value='password_hash' selected>password_hash (แนะนำ)</option>";
echo "</select>";
echo "<div class='form-text'>เลือกวิธีการเก็บ password</div>";
echo "</div>";

echo "<button type='submit' class='btn btn-warning'>🔐 Reset Password</button>";
echo "<button type='button' class='btn btn-secondary ms-2' onclick='hideResetForm()'>Cancel</button>";
echo "</form>";

echo "</div>";
echo "</div>";

// Quick Reset Suggestions
echo "<div class='alert alert-success mt-4'>";
echo "<h5>💡 Quick Password Reset Suggestions:</h5>";
echo "<p>สำหรับการทดสอบ แนะนำให้ใช้:</p>";
echo "<ul>";
echo "<li><strong>admin/admin123</strong> - สำหรับ admin user</li>";
echo "<li><strong>sales01/sales123</strong> - สำหรับ sales users</li>";
echo "<li><strong>supervisor/super123</strong> - สำหรับ supervisor users</li>";
echo "</ul>";
echo "<p>หรือตั้ง password เป็น <strong>username เดียวกัน</strong> เพื่อง่ายต่อการจำ</p>";
echo "</div>";

// JavaScript
echo "<script>";
echo "
function showResetForm(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').value = username;
    document.getElementById('resetForm').style.display = 'block';
    document.getElementById('resetForm').scrollIntoView();
}

function hideResetForm() {
    document.getElementById('resetForm').style.display = 'none';
}
";
echo "</script>";

echo "</body></html>";
?>