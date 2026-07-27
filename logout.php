<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';

session_start();

if (isset($_SESSION['user_id'])) {
    logUserActivity($pdo, $_SESSION['user_id'], 'Logout', 'User logged out manually.');
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "/login.php");
exit;
?>
