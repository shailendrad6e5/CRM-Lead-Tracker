<?php
// Shared helper functions — reusable across all pages

/**
 * Returns the CSS badge class for a lead status.
 */
function getStatusBadgeClass(string $status): string {
    $map = [
        'New'           => 'status-new',
        'Contacted'     => 'status-contacted',
        'Qualified'     => 'status-qualified',
        'Proposal Sent' => 'status-proposal',
        'Won'           => 'status-won',
        'Lost'          => 'status-lost',
    ];
    return $map[$status] ?? 'bg-secondary';
}

/**
 * Returns the CSS badge class for a lead priority.
 */
function getPriorityBadgeClass(string $priority): string {
    $map = [
        'High'   => 'priority-high',
        'Medium' => 'priority-medium',
        'Low'    => 'priority-low',
    ];
    return $map[$priority] ?? 'bg-secondary';
}

/**
 * Returns a Bootstrap icon class for a lead source.
 */
function getSourceIcon(string $source): string {
    $map = [
        'Website'        => 'bi-globe',
        'Referral'       => 'bi-people',
        'Cold Call'      => 'bi-telephone-outbound',
        'Email Campaign' => 'bi-envelope-at',
    ];
    return $map[$source] ?? 'bi-three-dots';
}

/**
 * Returns a human-readable "time ago" string.
 */
function timeAgo(?string $datetime): string {
    if (empty($datetime)) return 'Unknown';
    try {
        $now  = new DateTime();
        $then = new DateTime($datetime);
        $diff = $now->diff($then);

        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    } catch (Exception $e) {
        return 'Unknown';
    }
}

/**
 * Log a lead activity to the lead_activities table.
 */
function logLeadActivity(PDO $pdo, int $lead_id, int $user_id, string $action, string $description = ''): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO lead_activities (lead_id, user_id, action, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$lead_id, $user_id, $action, $description]);
    } catch (Exception $e) {
        // Silently fail — activity logging should never break core functionality
    }
}

/**
 * Computes the follow-up state (Completed, Today, Overdue, Upcoming, None)
 */
function computeFollowUpState(?string $date, ?string $time, string $status): string {
    if ($status === 'Completed') return 'Completed';
    if (empty($date)) return 'None';

    $targetStr = $date;
    if (!empty($time)) {
        $targetStr .= ' ' . $time;
    } else {
        $targetStr .= ' 23:59:59';
    }

    $targetTime = strtotime($targetStr);
    $now = time();
    $todayStart = strtotime('today');
    $todayEnd = strtotime('tomorrow') - 1;

    if ($targetTime < $todayStart) {
        return 'Overdue';
    } elseif ($targetTime >= $todayStart && $targetTime <= $todayEnd) {
        if (!empty($time) && $targetTime < $now) {
             return 'Overdue';
        }
        return 'Today';
    } else {
        return 'Upcoming';
    }
}

/**
 * Returns the CSS class for Follow-up state badges
 */
function getFollowUpStateBadgeClass(string $state): string {
    $map = [
        'Completed' => 'followup-completed',
        'Today'     => 'followup-today',
        'Overdue'   => 'followup-overdue',
        'Upcoming'  => 'followup-upcoming',
        'None'      => 'bg-secondary text-white'
    ];
    return $map[$state] ?? 'bg-secondary text-white';
}

// ── NEW: RBAC & Team Management Helpers ──────────────────────────────────────

/**
 * Returns CSS badge class for user role.
 */
function getRoleBadgeClass(string $role): string {
    $map = [
        'admin'     => 'role-admin',
        'manager'   => 'role-manager',
        'sales_rep' => 'role-sales-rep',
    ];
    return $map[$role] ?? 'bg-secondary';
}

/**
 * Returns human-readable role label.
 */
function getRoleLabel(string $role): string {
    $map = [
        'admin'     => 'Admin',
        'manager'   => 'Manager',
        'sales_rep' => 'Sales Rep',
    ];
    return $map[$role] ?? ucfirst($role);
}

/**
 * Returns CSS class for user account status.
 */
function getUserStatusClass(string $status): string {
    $map = [
        'active'   => 'user-status-active',
        'inactive' => 'user-status-inactive',
        'away'     => 'user-status-away',
    ];
    return $map[$status] ?? 'bg-secondary';
}

/**
 * Generates an avatar HTML element — either an image or initials circle.
 * @param array  $user  User row with 'name' and optional 'avatar'
 * @param string $size  'sm' (32px), 'md' (42px), 'lg' (80px), 'xl' (120px)
 */
function getUserAvatarHtml(array $user, string $size = 'md'): string {
    $sizes = ['sm' => '32px', 'md' => '42px', 'lg' => '80px', 'xl' => '120px'];
    $fontSizes = ['sm' => '13px', 'md' => '16px', 'lg' => '28px', 'xl' => '42px'];
    $px = $sizes[$size] ?? '42px';
    $fs = $fontSizes[$size] ?? '16px';

    $name    = htmlspecialchars($user['name'] ?? 'U');
    $initials = strtoupper(substr($user['name'] ?? 'U', 0, 1));
    if (str_contains($user['name'] ?? '', ' ')) {
        $parts = explode(' ', trim($user['name'] ?? ''));
        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }

    if (!empty($user['avatar']) && file_exists(__DIR__ . '/../assets/avatars/' . $user['avatar'])) {
        return "<img src=\"" . BASE_URL . "/assets/avatars/" . htmlspecialchars($user['avatar']) . "\" 
                     alt=\"{$name}\" title=\"{$name}\"
                     class=\"rounded-circle object-fit-cover\" 
                     style=\"width:{$px};height:{$px};\">";
    }

    // Color based on user id for consistent colors
    $colors = ['#184E77','#1E6091','#1A759F','#168AAD','#34A0A4','#52B69A','#76C893'];
    $colorIdx = crc32($user['name'] ?? '') % count($colors);
    if ($colorIdx < 0) $colorIdx = abs($colorIdx);
    $color = $colors[$colorIdx];

    return "<div class=\"rounded-circle d-inline-flex align-items-center justify-content-center fw-semibold text-white flex-shrink-0\" 
                 title=\"{$name}\"
                 style=\"width:{$px};height:{$px};background:{$color};font-size:{$fs};\">
                {$initials}
            </div>";
}

/**
 * Send an in-app notification to a user.
 */
function sendNotification(PDO $pdo, int $to_user_id, string $type, string $title, string $message, string $link = ''): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$to_user_id, $type, $title, $message, $link]);
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * Get unread notification count for a user.
 */
function getUnreadNotificationCount(PDO $pdo, int $user_id): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Fetch all team members (users) for assignment dropdowns.
 * Returns id, name, role, department.
 */
function getTeamMembers(PDO $pdo, bool $activeOnly = true): array {
    try {
        $where = $activeOnly ? "WHERE status = 'active'" : '';
        $stmt = $pdo->query("SELECT id, name, role, department, avatar FROM users {$where} ORDER BY name ASC");
        return $stmt->fetchAll() ?: [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Log assignment to lead_assignments table.
 */
function logLeadAssignment(PDO $pdo, int $lead_id, int $assigned_to, int $assigned_by, string $notes = ''): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO lead_assignments (lead_id, assigned_to, assigned_by, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$lead_id, $assigned_to, $assigned_by, $notes]);
    } catch (Exception $e) {
        // Silently fail
    }
}
/**
 * Log user action to user_activities table.
 */
function logUserActivity(PDO $pdo, ?int $user_id, string $action_type, string $description = ''): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO user_activities (user_id, action_type, description) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action_type, $description]);
    } catch (Exception $e) {
        // Silently fail
    }
}
?>
