<?php
require_once 'config.php';

$host = 'localhost';
$db   = 'crm_lead_tracker';
$user = 'root';
$pass = 'MyNewPass123';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a real app, log the error rather than echoing it.
    die("Database connection failed: " . $e->getMessage());
}
?>
