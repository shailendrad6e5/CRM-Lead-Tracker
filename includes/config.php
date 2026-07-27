<?php
// CRM Lead Tracker - Global Configuration

define('SITE_TITLE', 'Lead Management CRM');
date_default_timezone_set('Asia/Kolkata');

// Prefer an explicit server-side environment setting. The server address
// fallback is not controlled by the browser's Host header.
$configuredEnvironment = strtolower((string)(getenv('CRM_APP_ENV') ?: ''));
if (!in_array($configuredEnvironment, ['local', 'production'], true)) {
    $serverAddress = $_SERVER['SERVER_ADDR'] ?? '';
    $configuredEnvironment = (
        PHP_SAPI === 'cli'
        || in_array($serverAddress, ['127.0.0.1', '::1'], true)
    ) ? 'local' : 'production';
}
define('APP_ENV', $configuredEnvironment);

function isLocalEnvironment(): bool {
    return APP_ENV === 'local';
}

// Base URL for the application dynamically determined based on file location
$docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'));
$appRoot = str_replace('\\', '/', dirname(__DIR__));
$baseUrl = str_replace($docRoot, '', $appRoot);
// Ensure we don't end up with just a slash if it's the root itself
$baseUrl = ($baseUrl === '/') ? '' : $baseUrl;
define('BASE_URL', $baseUrl);

// Session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

$isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
if ($isHttps) {
    ini_set('session.cookie_secure', 1);
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if ($isHttps && !isLocalEnvironment()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
?>
