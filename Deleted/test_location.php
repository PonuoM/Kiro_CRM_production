<?php
echo "<h2>Location Test</h2>";
echo "Script location: " . __FILE__ . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Working directory: " . getcwd() . "<br><br>";

echo "<h3>Expected files in this directory:</h3>";
$expected = [
    'config/database.php',
    'config/config.php', 
    'includes/functions.php',
    'includes/BaseModel.php',
    'sql/production_setup.sql',
    'pages/login.php',
    'index.php'
];

foreach($expected as $file) {
    if(file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

echo "<h3>Directory listing:</h3>";
$files = scandir('.');
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        if(is_dir($file)) {
            echo "📁 $file/<br>";
        } else {
            echo "📄 $file<br>";
        }
    }
}
?>