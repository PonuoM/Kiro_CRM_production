<?php
/**
 * Script to generate correct password hashes for the CRM system
 * Run this once to get the correct hashes, then update the database
 */

echo "<h2>🔐 Password Hash Generator for CRM System</h2>\n";

$passwords = [
    'admin' => 'admin123',
    'supervisor' => 'supervisor123', 
    'sale1' => 'sale123'
];

echo "<h3>Generated Hashes:</h3>\n";
echo "<pre>\n";

foreach ($passwords as $user => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "User: {$user}\n";
    echo "Password: {$password}\n";
    echo "Hash: {$hash}\n";
    echo "SQL: UPDATE users SET Password = '{$hash}' WHERE Username = '{$user}';\n";
    echo str_repeat('-', 80) . "\n";
}

echo "</pre>\n";

echo "<h3>🔍 Verification Test:</h3>\n";
echo "<pre>\n";

foreach ($passwords as $user => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $verify = password_verify($password, $hash);
    echo "User: {$user} | Password: {$password} | Verify: " . ($verify ? '✅ PASS' : '❌ FAIL') . "\n";
}

echo "</pre>\n";

echo "<h3>📋 Complete SQL Update Script:</h3>\n";
echo "<textarea rows='8' cols='100' style='font-family: monospace;'>\n";

foreach ($passwords as $user => $password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "UPDATE users SET Password = '{$hash}' WHERE Username = '{$user}';\n";
}

echo "</textarea>\n";

echo "<h3>⚠️ Important Notes:</h3>\n";
echo "<ul>\n";
echo "<li>Copy the SQL commands above and run them in your database</li>\n";
echo "<li>After running, delete this file for security</li>\n";
echo "<li>Test login with: admin/admin123, supervisor/supervisor123, sale1/sale123</li>\n";
echo "</ul>\n";
?>