<?php
require_once 'config.php';

// Database configuration

// Check if running locally or on the live server
if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')) {
    // Local XAMPP Credentials
    $host = 'localhost';
    $db   = 'crm_lead_tracker';
    $user = 'root';
    $pass = 'MyNewPass123';
} else {
    // Live Server Credentials (configure these for your production environment)
    $host = 'localhost';
    $db   = 'production_db_name';
    $user = 'production_db_user';
    $pass = 'production_db_pass';
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Make sure MySQL returns dates in the same timezone as PHP (IST +05:30)
    $pdo->exec("SET time_zone = '+05:30'");
} catch (\PDOException $e) {
    // Log the error to a file rather than echoing it to visitors
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>
