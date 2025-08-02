<?php
/**
 * Strategy Analysis - หว่านแหหาสาเหตุจริง
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🎯 Strategy Analysis - หาสาเหตุจริงของ Error 500</h1>";
echo "<hr>";

echo "<h2>📋 ข้อเท็จจริงที่รู้แน่นอน</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ไฟล์</th><th>สถานะ</th><th>หมายเหตุ</th></tr>";
echo "<tr><td>call_history_demo_no_auth.php</td><td style='background: #e6ffe6;'>✅ ทำงานได้</td><td>ไม่ต้อง authentication</td></tr>";
echo "<tr><td>call_history_demo.php</td><td style='background: #ffe6e6;'>❌ Error 500</td><td>ต้อง authentication</td></tr>";
echo "<tr><td>Dynamic Sidebar</td><td style='background: #e6ffe6;'>✅ ทำงานได้</td><td>ใน main_layout.php</td></tr>";
echo "<tr><td>Login System</td><td style='background: #e6ffe6;'>✅ ทำงานได้</td><td>Session มี user_id</td></tr>";
echo "</table>";

echo "<h2>🧪 สมมติฐานที่เป็นไปได้</h2>";
echo "<ol>";
echo "<li><strong>File Permission Issue:</strong> ไฟล์อาจไม่มีสิทธิ์อ่าน</li>";
echo "<li><strong>Path Resolution Issue:</strong> path ของ require_once อาจผิด</li>";
echo "<li><strong>Memory/Resource Issue:</strong> ไฟล์ใหญ่เกินไป</li>";
echo "<li><strong>Authentication Flow Issue:</strong> มีบางส่วนใน auth ที่ติดขัด</li>";
echo "<li><strong>Content Length Issue:</strong> เนื้อหาเยอะเกินไปทำให้ timeout</li>";
echo "</ol>";

echo "<h2>🔬 ทดสอบแต่ละสมมติฐาน</h2>";

// Test 1: File Permission
echo "<h3>Test 1: File Permission</h3>";
$file_path = __DIR__ . '/pages/call_history_demo.php';
if (file_exists($file_path)) {
    $perms = fileperms($file_path);
    echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "<br>";
    echo "Readable: " . (is_readable($file_path) ? "YES" : "NO") . "<br>";
    echo "File size: " . filesize($file_path) . " bytes<br>";
} else {
    echo "❌ File not found<br>";
}

// Test 2: Compare file sizes
echo "<h3>Test 2: File Size Comparison</h3>";
$auth_file = __DIR__ . '/pages/call_history_demo.php';
$no_auth_file = __DIR__ . '/pages/call_history_demo_no_auth.php';

if (file_exists($auth_file) && file_exists($no_auth_file)) {
    $auth_size = filesize($auth_file);
    $no_auth_size = filesize($no_auth_file);
    echo "Auth version: $auth_size bytes<br>";
    echo "No-auth version: $no_auth_size bytes<br>";
    echo "Difference: " . ($auth_size - $no_auth_size) . " bytes<br>";
    
    if ($auth_size > $no_auth_size * 1.5) {
        echo "⚠️ Auth version significantly larger<br>";
    }
}

// Test 3: Check server limits
echo "<h3>Test 3: Server Limits</h3>";
echo "Max execution time: " . ini_get('max_execution_time') . "s<br>";
echo "Memory limit: " . ini_get('memory_limit') . "<br>";
echo "Max input time: " . ini_get('max_input_time') . "s<br>";
echo "Upload max filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "Post max size: " . ini_get('post_max_size') . "<br>";

// Test 4: Try minimal auth version
echo "<h3>Test 4: Creating Minimal Auth Version</h3>";
$minimal_content = '<?php
// Minimal auth version for testing
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . "/../includes/permissions.php";
require_once __DIR__ . "/../includes/main_layout.php";

Permissions::requireLogin();

if (!Permissions::hasPermission("call_history")) {
    echo "No permission"; exit;
}

$pageTitle = "ประวัติการโทร";
$user_name = Permissions::getCurrentUser();
$user_role = Permissions::getCurrentRole();
$menuItems = Permissions::getMenuItems();

$GLOBALS["currentUser"] = $user_name;
$GLOBALS["currentRole"] = $user_role;
$GLOBALS["menuItems"] = $menuItems;

$content = "<h1>Minimal Test Page</h1><p>This is a minimal auth test.</p>";
$css = "<style>body { margin: 20px; }</style>";
$js = "<script>console.log(\"Minimal JS loaded\");</script>";

echo renderMainLayout($pageTitle, $content, $css, $js);
?>';

$minimal_file = __DIR__ . '/call_history_minimal_auth.php';
file_put_contents($minimal_file, $minimal_content);
echo "✅ Created minimal auth version: <a href='call_history_minimal_auth.php' target='_blank'>Test Here</a><br>";

// Test 5: Check for hidden characters
echo "<h3>Test 5: Check for Hidden Characters</h3>";
if (file_exists($auth_file)) {
    $content = file_get_contents($auth_file);
    $length = strlen($content);
    $mb_length = mb_strlen($content, 'UTF-8');
    echo "Byte length: $length<br>";
    echo "Character length: $mb_length<br>";
    
    if ($length != $mb_length) {
        echo "⚠️ Contains multibyte characters<br>";
    }
    
    // Check for BOM
    if (substr($content, 0, 3) == "\xEF\xBB\xBF") {
        echo "⚠️ Contains UTF-8 BOM<br>";
    }
    
    // Check for null bytes
    if (strpos($content, "\0") !== false) {
        echo "⚠️ Contains null bytes<br>";
    }
}

echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li>Test the minimal auth version first</li>";
echo "<li>If minimal works, gradually add complexity</li>";
echo "<li>If minimal fails, the issue is in the basic auth flow</li>";
echo "<li>Compare working vs non-working versions line by line</li>";
echo "</ol>";

echo "<hr>";
echo "Analysis completed at: " . date('Y-m-d H:i:s');
?>