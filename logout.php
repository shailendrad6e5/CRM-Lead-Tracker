<?php
require_once 'includes/config.php';

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: " . BASE_URL . "/login.php");
exit;
?>
