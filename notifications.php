<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

// Mark all as read
if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE user_notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
    header('Location: ' . BASE_URL . '/notifications.php');
    exit;
}

// Mark single as read and redirect
if (isset($_GET['read'])) {
    $nid = (int)$_GET['read'];
    $pdo->prepare("UPDATE user_notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$nid, $userId]);
    $redirect = $_GET['redirect'] ?? '';
    if ($redirect) {
        header('Location: ' . $redirect);
    } else {
        header('Location: ' . BASE_URL . '/notifications.php');
    }
    exit;
}

$pageTitle = 'Notifications';
$page      = max(1, (int)($_GET['page'] ?? 1));
$limit     = 20;
$offset    = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id=?");
$countStmt->execute([$userId]);
$total = $countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $limit));

$stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id=? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = getUnreadNotificationCount($pdo, $userId);

$typeIcons = [
    'lead_assigned'   => ['icon' => 'bi-person-check',    'color' => 'text-primary',   'bg' => 'rgba(24,78,119,0.1)'],
    'lead_reassigned' => ['icon' => 'bi-arrow-left-right','color' => 'text-warning',   'bg' => 'rgba(255,193,7,0.1)'],
    'account_created' => ['icon' => 'bi-person-plus',     'color' => 'text-success',   'bg' => 'rgba(40,167,69,0.1)'],
    'password_reset'  => ['icon' => 'bi-key',             'color' => 'text-danger',    'bg' => 'rgba(220,53,69,0.1)'],
    'default'         => ['icon' => 'bi-info-circle',     'color' => 'text-secondary', 'bg' => 'rgba(108,117,125,0.1)'],
];

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Notifications</h1>
        <p class="text-muted small mb-0">
            <?= $unreadCount > 0 ? "<span class='fw-semibold text-primary'>$unreadCount unread</span> notification" . ($unreadCount!==1?'s':'') : 'All caught up!' ?>
        </p>
    </div>
    <?php if ($unreadCount > 0): ?>
    <a href="?mark_all=1" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-check2-all me-1"></i>Mark All as Read
    </a>
    <?php endif; ?>
</div>

<div class="card p-0">
    <?php if (empty($notifications)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bell-slash text-muted" style="font-size:3rem;"></i>
        <p class="fs-5 fw-semibold mt-3 mb-1">No notifications yet</p>
        <p class="text-muted small">You'll be notified when leads are assigned to you or when actions are taken on your account.</p>
    </div>
    <?php else: ?>
    <ul class="list-group list-group-flush">
        <?php foreach ($notifications as $n):
            $t = $typeIcons[$n['type']] ?? $typeIcons['default'];
        ?>
        <li class="list-group-item px-4 py-3 <?= !$n['is_read'] ? 'bg-light' : '' ?>">
            <div class="d-flex gap-3 align-items-start">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1"
                     style="width:42px;height:42px;background:<?= $t['bg'] ?>;">
                    <i class="bi <?= $t['icon'] ?> <?= $t['color'] ?> fs-6"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold <?= !$n['is_read'] ? 'text-dark' : 'text-muted' ?>"><?= htmlspecialchars($n['title']) ?></span>
                        <span class="small text-muted"><?= timeAgo($n['created_at']) ?></span>
                    </div>
                    <p class="small mb-1 text-muted"><?= htmlspecialchars($n['message']) ?></p>
                    <?php if (!empty($n['link'])): ?>
                    <a href="notifications.php?read=<?= $n['id'] ?>&redirect=<?= urlencode($n['link']) ?>" class="small text-primary text-decoration-none">
                        <i class="bi bi-arrow-right me-1"></i>View
                    </a>
                    <?php endif; ?>
                </div>
                <?php if (!$n['is_read']): ?>
                <a href="?read=<?= $n['id'] ?>" class="btn btn-sm btn-light border flex-shrink-0" title="Mark as read">
                    <i class="bi bi-check-lg"></i>
                </a>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white py-3 text-center">
        <nav>
            <ul class="pagination justify-content-center mb-0 pagination-sm">
                <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?page=<?= $page-1 ?>">«</a></li>
                <?php for ($i=1;$i<=$totalPages;$i++): ?>
                <li class="page-item <?= $page==$i?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?page=<?= $page+1 ?>">»</a></li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
