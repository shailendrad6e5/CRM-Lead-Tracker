<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn() && hasRole('admin')) {
    header('Location: ' . BASE_URL . '/team.php');
    exit;
}

if (!isset($_SESSION)) {
    session_start();
}
$_SESSION['error'] = 'Access Denied. Public registration is disabled.';
header('Location: ' . BASE_URL . '/login.php');
exit;
?>
