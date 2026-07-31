<?php
// ── Session Configuration ────────────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_start();

require_once __DIR__ . '/csrf.php';

// ── Basic Auth ───────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

/**
 * Reload the authenticated account from the database once per request.
 * This immediately applies suspension, deletion, role, and profile changes.
 */
function refreshAuthenticatedUser(): bool {
    static $validatedUserId = null;

    if (!isLoggedIn()) {
        return false;
    }

    $sessionUserId = (int)$_SESSION['user_id'];
    if ($validatedUserId === $sessionUserId) {
        return true;
    }

    global $pdo;
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, role, department, job_title, avatar, status, requires_password_change
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$sessionUserId]);
    $user = $stmt->fetch();

    if (!$user || ($user['status'] ?? 'inactive') !== 'active') {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['error'] = 'Your account is unavailable or has been disabled.';
        return false;
    }

    $_SESSION['user_id']         = (int)$user['id'];
    $_SESSION['user_name']       = $user['name'];
    $_SESSION['user_role']       = $user['role'] ?? 'sales_rep';
    $_SESSION['user_department'] = $user['department'] ?? '';
    $_SESSION['user_job_title']  = $user['job_title'] ?? '';
    $_SESSION['user_avatar']     = $user['avatar'] ?? '';
    $_SESSION['requires_password_change'] = (int)($user['requires_password_change'] ?? 0);
    $validatedUserId = $sessionUserId;

    return true;
}

function requireLogin(): void {
    if (!refreshAuthenticatedUser()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    // Force password change on first login if required
    if (!empty($_SESSION['requires_password_change'])) {
        $currentFile = basename($_SERVER['SCRIPT_FILENAME']);
        if ($currentFile !== 'change_password.php' && $currentFile !== 'logout.php') {
            header('Location: ' . BASE_URL . '/change_password.php');
            exit;
        }
    }
}

// ── Role Helpers ─────────────────────────────────────────────────────────────

/**
 * Returns the current user's role from session.
 */
function getUserRole(): string {
    return $_SESSION['user_role'] ?? 'sales_rep';
}

/**
 * Returns true if current user has the given role.
 */
function hasRole(string $role): bool {
    return getUserRole() === $role;
}

/**
 * Returns true if current user has ANY of the given roles.
 */
function hasAnyRole(array $roles): bool {
    return in_array(getUserRole(), $roles, true);
}

/**
 * Redirect with error if user doesn't have required role(s).
 */
function requireRole(array $roles): void {
    requireLogin();
    if (!hasAnyRole($roles)) {
        $_SESSION['error'] = "You don't have permission to access that page.";
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// ── Permission Checks ────────────────────────────────────────────────────────

function isAdmin(): bool {
    return getUserRole() === 'admin';
}

function isManager(): bool {
    return getUserRole() === 'manager';
}

function isSalesRep(): bool {
    return getUserRole() === 'sales_rep';
}

/**
 * Admin and Manager can assign leads to others.
 */
function canAssignLeads(): bool {
    return hasAnyRole(['admin', 'manager']);
}

/**
 * Only Admin can manage users (create, edit, delete, reset password).
 */
function canManageUsers(): bool {
    return isAdmin();
}

/**
 * Check if current user can view this lead.
 * Admins and Managers see all. Sales rep only sees their own.
 */
function canViewLead(array $lead): bool {
    if (hasAnyRole(['admin', 'manager'])) return true;
    return (int)($lead['assigned_to'] ?? 0) === (int)$_SESSION['user_id'];
}

/**
 * Check if current user can edit this lead.
 */
function canEditLead(array $lead): bool {
    if (hasAnyRole(['admin', 'manager'])) return true;
    return (int)($lead['assigned_to'] ?? 0) === (int)$_SESSION['user_id'];
}

/**
 * Check if current user can delete this lead.
 * Admin and Manager: any lead. Sales rep: none.
 */
function canDeleteLead(array $lead): bool {
    if (isAdmin()) return true;
    if (isManager()) return true;
    return false;
}

/**
 * Check if current user can delete a user account.
 */
function canDeleteUser(array $targetUser): bool {
    if (!isAdmin()) return false;
    // Cannot delete yourself
    if ((int)$targetUser['id'] === (int)$_SESSION['user_id']) return false;
    return true;
}
?>
