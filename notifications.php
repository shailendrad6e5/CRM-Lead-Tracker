<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = (string)($_POST['action'] ?? '');
    $fallback = BASE_URL . '/notifications.php';

    if ($action === 'mark_all') {
        $pdo->prepare("UPDATE user_notifications SET is_read=1 WHERE user_id=?")->execute([$userId]);
        header('Location: ' . $fallback);
        exit;
    }

    if ($action === 'read') {
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        $notificationStmt = $pdo->prepare("SELECT link FROM user_notifications WHERE id=? AND user_id=?");
        $notificationStmt->execute([$notificationId, $userId]);
        $notification = $notificationStmt->fetch();

        if ($notification) {
            $pdo->prepare("UPDATE user_notifications SET is_read=1 WHERE id=? AND user_id=?")
                ->execute([$notificationId, $userId]);
        }

        $redirect = $fallback;
        if ($notification && isset($_POST['open_notification'])) {
            $redirect = getSafeInternalRedirect((string)$notification['link'], $fallback);
        }

        header('Location: ' . $redirect);
        exit;
    }

    $_SESSION['error'] = 'Invalid notification action.';
    header('Location: ' . $fallback);
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
    'leads_bulk_assigned' => ['icon' => 'bi-people',      'color' => 'text-primary',   'bg' => 'rgba(24,78,119,0.1)'],
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
    <form method="POST" action="<?= BASE_URL ?>/notifications.php" class="m-0">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="mark_all">
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-check2-all me-1"></i>Mark All as Read
        </button>
    </form>
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
                    <form method="POST" action="<?= BASE_URL ?>/notifications.php" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="read">
                        <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                        <input type="hidden" name="open_notification" value="1">
                        <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">
                            <i class="bi bi-arrow-right me-1"></i>View
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php if (!$n['is_read']): ?>
                <form method="POST" action="<?= BASE_URL ?>/notifications.php" class="m-0 flex-shrink-0">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="read">
                    <input type="hidden" name="notification_id" value="<?= (int)$n['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-light border" title="Mark as read">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>
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
