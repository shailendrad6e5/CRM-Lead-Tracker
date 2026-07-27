<?php
require_once __DIR__ . '/config.php';

// Credentials come from server environment variables or an ignored local
// configuration file. Browser-controlled request headers never choose them.
$localConfig = [];
$localConfigPath = __DIR__ . '/local_config.php';
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

$host = (string)(getenv('CRM_DB_HOST') ?: ($localConfig['host'] ?? ''));
$db   = (string)(getenv('CRM_DB_NAME') ?: ($localConfig['database'] ?? ''));
$user = (string)(getenv('CRM_DB_USER') ?: ($localConfig['username'] ?? ''));
$pass = (string)(getenv('CRM_DB_PASSWORD') ?: ($localConfig['password'] ?? ''));

if ($host === '' || $db === '' || $user === '') {
    error_log('CRM database configuration is incomplete.');
    die('Database configuration is incomplete. Please contact the administrator.');
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
