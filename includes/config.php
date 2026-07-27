<?php
// CRM Lead Tracker - Global Configuration

define('SITE_TITLE', 'Lead Management CRM');
date_default_timezone_set('Asia/Kolkata');

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
?>
