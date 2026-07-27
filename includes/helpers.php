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
?>
