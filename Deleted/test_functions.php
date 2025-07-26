<?php
// Test file to check function redeclaration
echo "Testing function availability...\n";

if (function_exists('sanitizeInput')) {
    echo "sanitizeInput() exists\n";
} else {
    echo "sanitizeInput() does not exist\n";
}

if (function_exists('generateCSRFToken')) {
    echo "generateCSRFToken() exists\n";
} else {
    echo "generateCSRFToken() does not exist\n";
}

// Try to include functions.php
try {
    require_once 'includes/functions.php';
    echo "functions.php included successfully\n";
} catch (Exception $e) {
    echo "Error including functions.php: " . $e->getMessage() . "\n";
}

echo "Test completed\n";
?>