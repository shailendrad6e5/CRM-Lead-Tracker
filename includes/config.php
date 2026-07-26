<?php
// CRM Lead Tracker - Global Configuration

define('SITE_TITLE', 'Lead Management CRM');
date_default_timezone_set('Asia/Kolkata');

// Base URL for the application
if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1')) {
    define('BASE_URL', '/CRM-Lead-Tracker');
} else {
    define('BASE_URL', '');
}

// Session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
?>
