<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-3 sticky-top">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-light d-md-none me-3">
            <i class="bi bi-list fs-5"></i>
        </button>

        <h4 class="m-0 fw-semibold d-none d-md-block"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></h4>

        <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

            <!-- Notifications Bell -->
            <?php
            $unreadCount = isLoggedIn() ? getUnreadNotificationCount($pdo, $_SESSION['user_id']) : 0;
            ?>
            <div class="dropdown">
                <a href="#" class="btn btn-light position-relative border rounded-circle d-flex align-items-center justify-content-center"
                   style="width:38px;height:38px;"
                   data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell fs-6"></i>
                    <?php if ($unreadCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 p-0" style="width:320px;">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <span class="fw-semibold small">Notifications</span>
                        <?php if ($unreadCount > 0): ?>
                        <a href="<?= BASE_URL ?>/notifications.php?mark_all=1" class="small text-primary text-decoration-none">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <?php
                    $notifStmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                    $notifStmt->execute([$_SESSION['user_id'] ?? 0]);
                    $recentNotifs = $notifStmt->fetchAll();
                    ?>
                    <?php if (empty($recentNotifs)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-bell-slash d-block fs-3 mb-1"></i>No notifications
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentNotifs as $n): ?>
                    <a href="<?= BASE_URL ?>/notifications.php?read=<?= $n['id'] ?><?= !empty($n['link']) ? '&redirect=' . urlencode($n['link']) : '' ?>"
                       class="d-flex gap-2 px-3 py-2 text-decoration-none <?= $n['is_read'] ? 'text-muted' : 'bg-light' ?> border-bottom">
                        <div class="flex-shrink-0 mt-1">
                            <i class="bi <?= $n['type']==='lead_assigned'?'bi-person-check text-primary':($n['type']==='password_reset'?'bi-key text-warning':'bi-info-circle text-success') ?> fs-6"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="small fw-semibold text-dark text-truncate"><?= htmlspecialchars($n['title']) ?></div>
                            <div class="small text-muted text-truncate"><?= htmlspecialchars($n['message']) ?></div>
                            <div style="font-size:10px;" class="text-muted"><?= timeAgo($n['created_at']) ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/notifications.php" class="d-block text-center small py-2 text-primary text-decoration-none border-top">
                        View all notifications
                    </a>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-dark p-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php
                    $navUser = ['name' => $_SESSION['user_name'] ?? 'User', 'avatar' => $_SESSION['user_avatar'] ?? ''];
                    echo getUserAvatarHtml($navUser, 'sm');
                    ?>
                    <div class="d-none d-md-block">
                        <div class="fw-medium lh-1" style="font-size:14px;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                        <div class="text-muted lh-1" style="font-size:11px;"><?= getRoleLabel(getUserRole()) ?></div>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li>
                        <div class="px-3 py-2 border-bottom">
                            <div class="fw-semibold small"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                            <span class="badge <?= getRoleBadgeClass(getUserRole()) ?>" style="font-size:10px;"><?= getRoleLabel(getUserRole()) ?></span>
                        </div>
                    </li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/my_leads.php"><i class="bi bi-person-lines-fill me-2"></i>My Leads</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person-circle me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/notifications.php"><i class="bi bi-bell me-2"></i>Notifications <?= $unreadCount > 0 ? "<span class='badge bg-danger rounded-pill'>$unreadCount</span>" : '' ?></a></li>
                    <?php if (isAdmin()): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>/team.php"><i class="bi bi-people-fill me-2"></i>Team Management</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
