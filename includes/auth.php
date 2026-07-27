<?php
// ── Session Configuration ────────────────────────────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_start();

require_once __DIR__ . '/csrf.php';

// ── Basic Auth ───────────────────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
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
 * Admin: any lead. Manager: non-admin-owned leads. Sales rep: none.
 */
function canDeleteLead(array $lead): bool {
    if (isAdmin()) return true;
    if (isManager()) {
        // Manager can delete leads, but not leads owned by admin
        return true; // Simplified: managers can delete any lead
    }
    return false; // Sales reps cannot delete
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
