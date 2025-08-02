<?php
/**
 * Login 500 Error Diagnostic Tool
 * ระบุสาเหตุที่แน่นอนของ 500 Internal Server Error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>\n<html><head><meta charset='UTF-8'><title>🔧 Login 500 Diagnostic</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body style='padding:20px;'>";

echo "<h1>🔧 Login 500 Error Diagnostic</h1>";
echo "<p>ระบุสาเหตุที่แน่นอนของ 500 Internal Server Error ใน login API</p>";

// เริ่มต้น session ให้พร้อม
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<div class='alert alert-info'>🔄 กำลังตรวจสอบแต่ละขั้นตอน...</div>";

$diagnostics = [];
$fatal_error = false;

// 1. ทดสอบ File Existence
echo "<h3>1. 📁 ตรวจสอบไฟล์ที่จำเป็น</h3>";

$required_files = [
    'api/auth/login.php' => 'Login API Endpoint',
    'includes/functions.php' => 'Core Functions',
    'includes/User.php' => 'User Model',
    'includes/BaseModel.php' => 'Base Model',
    'config/database.php' => 'Database Config',
    'config/config.php' => 'Main Config'
];

foreach ($required_files as $file => $desc) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    
    echo "<div class='alert " . ($exists && $readable ? 'alert-success' : 'alert-danger') . "'>";
    echo "<strong>$desc:</strong> $file ";
    
    if ($exists && $readable) {
        echo "✅ OK";
        $diagnostics[$file] = 'OK';
    } else {
        echo "❌ " . (!$exists ? 'ไม่พบไฟล์' : 'อ่านไม่ได้');
        $diagnostics[$file] = 'FAILED';
        $fatal_error = true;
    }
    echo "</div>";
}

// 2. ทดสอบ Database Connection
echo "<h3>2. 🗄️ ทดสอบ Database</h3>";

try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='alert alert-success'>✅ Database เชื่อมต่อสำเร็จ</div>";
    $diagnostics['database'] = 'OK';
    
    // ตรวจสอบตาราง users
    $users_exists = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    if ($users_exists) {
        echo "<div class='alert alert-success'>✅ ตาราง users พบ</div>";
        $diagnostics['users_table'] = 'OK';
        
        // ตรวจสอบ structure
        $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['id', 'Username', 'Password', 'Role', 'Status'];
        $missing_columns = array_diff($required_columns, $columns);
        
        if (empty($missing_columns)) {
            echo "<div class='alert alert-success'>✅ โครงสร้างตาราง users ถูกต้อง</div>";
            $diagnostics['users_structure'] = 'OK';
        } else {
            echo "<div class='alert alert-danger'>❌ ขาดคอลัมน์: " . implode(', ', $missing_columns) . "</div>";
            $diagnostics['users_structure'] = 'FAILED - Missing: ' . implode(', ', $missing_columns);
            $fatal_error = true;
        }
    } else {
        echo "<div class='alert alert-danger'>❌ ไม่พบตาราง users</div>";
        $diagnostics['users_table'] = 'FAILED';
        $fatal_error = true;
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Database Error: " . $e->getMessage() . "</div>";
    $diagnostics['database'] = 'FAILED: ' . $e->getMessage();
    $fatal_error = true;
}

// 3. ทดสอบ Functions Loading
echo "<h3>3. ⚙️ ทดสอบ Functions</h3>";

try {
    require_once 'includes/functions.php';
    echo "<div class='alert alert-success'>✅ functions.php โหลดสำเร็จ</div>";
    $diagnostics['functions'] = 'OK';
    
    // ตรวจสอบ required functions
    $required_functions = ['sendJsonResponse', 'verifyCSRFToken', 'setUserSession', 'logActivity', 'verifyPassword'];
    $missing_functions = [];
    
    foreach ($required_functions as $func) {
        if (!function_exists($func)) {
            $missing_functions[] = $func;
        }
    }
    
    if (empty($missing_functions)) {
        echo "<div class='alert alert-success'>✅ ฟังก์ชันที่จำเป็นครบถ้วน</div>";
        $diagnostics['required_functions'] = 'OK';
    } else {
        echo "<div class='alert alert-danger'>❌ ขาดฟังก์ชัน: " . implode(', ', $missing_functions) . "</div>";
        $diagnostics['required_functions'] = 'FAILED - Missing: ' . implode(', ', $missing_functions);
        $fatal_error = true;
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Functions Error: " . $e->getMessage() . "</div>";
    $diagnostics['functions'] = 'FAILED: ' . $e->getMessage();
    $fatal_error = true;
}

// 4. ทดสอบ User Model
echo "<h3>4. 👤 ทดสอบ User Model</h3>";

try {
    require_once 'includes/User.php';
    $userModel = new User();
    echo "<div class='alert alert-success'>✅ User Model สร้างสำเร็จ</div>";
    $diagnostics['user_model'] = 'OK';
    
    // ทดสอบ method ที่จำเป็น
    if (method_exists($userModel, 'authenticate')) {
        echo "<div class='alert alert-success'>✅ Method authenticate() พบ</div>";
        $diagnostics['authenticate_method'] = 'OK';
    } else {
        echo "<div class='alert alert-danger'>❌ ไม่พบ method authenticate()</div>";
        $diagnostics['authenticate_method'] = 'FAILED';
        $fatal_error = true;
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ User Model Error: " . $e->getMessage() . "</div>";
    $diagnostics['user_model'] = 'FAILED: ' . $e->getMessage();
    $fatal_error = true;
}

// 5. ทดสอบ API Simulation
echo "<h3>5. 🧪 จำลองการเรียก API</h3>";

if (!$fatal_error) {
    try {
        // จำลอง JSON input
        $test_input = [
            'username' => 'test_user',
            'password' => 'test_pass',
            'csrf_token' => 'test_token'
        ];
        
        echo "<div class='alert alert-info'>กำลังจำลองการประมวลผล JSON...</div>";
        
        // ทดสอบ JSON encoding/decoding
        $json_string = json_encode($test_input);
        $decoded = json_decode($json_string, true);
        
        if ($decoded && is_array($decoded)) {
            echo "<div class='alert alert-success'>✅ JSON processing ทำงานได้</div>";
            $diagnostics['json_processing'] = 'OK';
        } else {
            echo "<div class='alert alert-danger'>❌ JSON processing มีปัญหา</div>";
            $diagnostics['json_processing'] = 'FAILED';
        }
        
        // ทดสอบ session
        $_SESSION['csrf_token'] = 'test_session_token';
        if (session_id()) {
            echo "<div class='alert alert-success'>✅ Session ทำงานได้</div>";
            $diagnostics['session'] = 'OK';
        } else {
            echo "<div class='alert alert-warning'>⚠️ Session อาจมีปัญหา</div>";
            $diagnostics['session'] = 'WARNING';
        }
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>❌ API Simulation Error: " . $e->getMessage() . "</div>";
        $diagnostics['api_simulation'] = 'FAILED: ' . $e->getMessage();
    }
} else {
    echo "<div class='alert alert-warning'>⚠️ ข้าม API simulation เนื่องจากมี fatal error</div>";
    $diagnostics['api_simulation'] = 'SKIPPED';
}

// 6. ทดสอบ Error Reporting ใน API
echo "<h3>6. 🚨 ทดสอบ Error Handling</h3>";

try {
    // อ่าน login.php และตรวจสอบ error handling
    $login_content = file_get_contents('api/auth/login.php');
    
    if (strpos($login_content, 'ini_set(\'display_errors\', 0)') !== false) {
        echo "<div class='alert alert-warning'>⚠️ API ปิด error display - อาจซ่อนข้อผิดพลาด</div>";
        $diagnostics['error_display'] = 'DISABLED';
    } else {
        echo "<div class='alert alert-info'>ℹ️ Error display ไม่ได้ปิด</div>";
        $diagnostics['error_display'] = 'ENABLED';
    }
    
    if (strpos($login_content, 'ob_start()') !== false) {
        echo "<div class='alert alert-info'>ℹ️ API ใช้ output buffering</div>";
        $diagnostics['output_buffering'] = 'YES';
    } else {
        echo "<div class='alert alert-warning'>⚠️ API ไม่ใช้ output buffering</div>";
        $diagnostics['output_buffering'] = 'NO';
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ ไม่สามารถอ่าน login.php: " . $e->getMessage() . "</div>";
    $diagnostics['login_file_read'] = 'FAILED';
}

// 7. สรุปผล
echo "<h3>7. 📊 สรุปการวินิจฉัย</h3>";

$all_ok = true;
$warnings = 0;
$errors = 0;

foreach ($diagnostics as $test => $result) {
    if (strpos($result, 'FAILED') !== false) {
        $errors++;
        $all_ok = false;
    } elseif (strpos($result, 'WARNING') !== false || strpos($result, 'DISABLED') !== false) {
        $warnings++;
    }
}

echo "<div class='row'>";
echo "<div class='col-md-4'>";
echo "<div class='card text-center " . ($errors == 0 ? 'bg-success' : 'bg-danger') . " text-white'>";
echo "<div class='card-body'>";
echo "<h5>Errors</h5>";
echo "<div class='display-6'>$errors</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='card text-center bg-warning text-dark'>";
echo "<div class='card-body'>";
echo "<h5>Warnings</h5>";
echo "<div class='display-6'>$warnings</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "<div class='col-md-4'>";
echo "<div class='card text-center " . ($all_ok ? 'bg-success' : 'bg-secondary') . " text-white'>";
echo "<div class='card-body'>";
echo "<h5>Status</h5>";
echo "<div class='display-6'>" . ($all_ok ? '✅' : '❌') . "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

// 8. แนะนำการแก้ไข
echo "<h3>8. 💡 แนะนำการแก้ไข</h3>";

if ($errors > 0) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>🚨 พบปัญหาร้ายแรง - ต้องแก้ไขก่อน:</h5>";
    echo "<ul>";
    foreach ($diagnostics as $test => $result) {
        if (strpos($result, 'FAILED') !== false) {
            echo "<li><strong>$test:</strong> $result</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='alert alert-success'>✅ ไม่พบปัญหาร้ายแรง - API น่าจะทำงานได้</div>";
}

if ($warnings > 0) {
    echo "<div class='alert alert-warning'>";
    echo "<h5>⚠️ ข้อควรระวัง:</h5>";
    echo "<ul>";
    foreach ($diagnostics as $test => $result) {
        if (strpos($result, 'WARNING') !== false || strpos($result, 'DISABLED') !== false) {
            echo "<li><strong>$test:</strong> $result</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
}

// 9. ขั้นตอนการทดสอบ
echo "<h3>9. 🔬 ขั้นตอนทดสอบ</h3>";

if ($errors == 0) {
    echo "<div class='alert alert-info'>";
    echo "<h5>🧪 ทดสอบ API แบบ Manual:</h5>";
    echo "<ol>";
    echo "<li><strong>เปิด Developer Tools</strong> ใน browser (F12)</li>";
    echo "<li><strong>ไปที่แท็บ Network</strong></li>";
    echo "<li><strong>ลองล็อกอิน</strong> ผ่านหน้าเว็บปกติ</li>";
    echo "<li><strong>ดูใน Network tab</strong> ว่า API ส่งอะไรกลับมา</li>";
    echo "<li><strong>ตรวจสอบ Response</strong> ว่าเป็น JSON หรือ HTML error</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='alert alert-success'>";
    echo "<h5>✅ หรือทดสอบด้วยเครื่องมือ:</h5>";
    echo "<p><a href='test_simple_login.php' class='btn btn-primary' target='_blank'>🧪 ทดสอบ Login แบบง่าย</a></p>";
    echo "</div>";
}

echo "<div class='text-center mt-4'>";
echo "<h4>📋 รายงานการวินิจฉัย:</h4>";
echo "<table class='table table-sm table-bordered'>";
echo "<thead><tr><th>Component</th><th>Status</th></tr></thead>";
echo "<tbody>";
foreach ($diagnostics as $test => $result) {
    $class = 'table-success';
    if (strpos($result, 'FAILED') !== false) $class = 'table-danger';
    elseif (strpos($result, 'WARNING') !== false || strpos($result, 'DISABLED') !== false) $class = 'table-warning';
    
    echo "<tr class='$class'><td>$test</td><td>$result</td></tr>";
}
echo "</tbody></table>";
echo "</div>";

echo "</body></html>";
?>