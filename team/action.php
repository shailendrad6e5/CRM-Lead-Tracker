<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();
requireRole(['admin']);
verifyCsrfToken();

$action   = $_POST['action']  ?? '';
$targetId = (int)($_POST['user_id'] ?? 0);
$value    = $_POST['value']   ?? '';

if (!$targetId || $targetId === (int)$_SESSION['user_id']) {
    $_SESSION['error'] = 'Invalid action or cannot modify your own account.';
    header('Location: ' . BASE_URL . '/team.php');
    exit;
}

// Fetch target user
$tStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$tStmt->execute([$targetId]);
$targetUser = $tStmt->fetch();

if (!$targetUser) {
    $_SESSION['error'] = 'User not found.';
    header('Location: ' . BASE_URL . '/team.php');
    exit;
}

switch ($action) {
    case 'toggle_status':
        $newStatus = in_array($value, ['active','inactive','suspended'], true) ? $value : 'active';
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$newStatus, $targetId]);
        logUserActivity($pdo, $_SESSION['user_id'], 'User Status Changed', "Changed status of {$targetUser['email']} to {$newStatus}.");
        $label = ucfirst($newStatus);
        $_SESSION['success'] = "User '{$targetUser['name']}' set to {$label}.";
        break;

    case 'delete':
        // Check if user has assigned leads
        $leadCheck = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE assigned_to = ?');
        $leadCheck->execute([$targetId]);
        if ($leadCheck->fetchColumn() > 0) {
            $_SESSION['error'] = 'Cannot delete. Please reassign or remove all assigned leads first.';
            break;
        }

        try {
            $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $deleteStmt->execute([$targetId]);
            if ($deleteStmt->rowCount() < 1) {
                throw new RuntimeException('User deletion affected no rows.');
            }

            deleteAvatarFile($targetUser['avatar'] ?? null);
            logUserActivity($pdo, $_SESSION['user_id'], 'User Deleted', "Deleted user account: {$targetUser['email']}");
            $_SESSION['success'] = "User '{$targetUser['name']}' deleted.";
        } catch (Throwable $e) {
            error_log('User deletion failed: ' . $e->getMessage());
            $_SESSION['error'] = 'The user could not be deleted.';
        }
        break;

    case 'reset_password':
        if (strlen($value) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            break;
        }
        $hashed = password_hash($value, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = ?, requires_password_change = 1 WHERE id = ?')->execute([$hashed, $targetId]);
        logUserActivity($pdo, $_SESSION['user_id'], 'Password Reset', "Reset password for {$targetUser['email']}");
        $_SESSION['success'] = "Password reset for '{$targetUser['name']}'. They will be forced to change it on next login.";
        break;

    default:
        $_SESSION['error'] = 'Unknown action.';
}

header('Location: ' . BASE_URL . '/team.php');
exit;
?>
