<?php
// CRM Lead Tracker - Global Configuration

// Base URL for the application
define('BASE_URL', 'http://crmleadtracker.free.nf');

// Session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
?>
