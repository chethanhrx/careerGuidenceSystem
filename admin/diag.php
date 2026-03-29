<?php
// Temporary diagnostic file — DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "<br><br>";

echo "<h2>Testing config.php load...</h2>";
try {
    require_once 'config.php';
    echo "✅ config.php loaded OK<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DB_NAME: " . DB_NAME . "<br>";
    echo "conn status: " . ($conn ? '✅ Connected' : '❌ Not connected') . "<br>";
    if ($conn) {
        echo "mysqli ping: " . ($conn->ping() ? '✅ OK' : '❌ Failed') . "<br>";
    }
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Testing admin/index.php require...</h2>";
echo "SESSION status: " . session_status() . " (1=none, 2=active)<br>";

echo "<h2>Done</h2>";
