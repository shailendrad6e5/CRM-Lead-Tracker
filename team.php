<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();
requireRole(['admin']); // Only admins can manage team

$pageTitle = 'Team Management';

// ── Build filter/search query ─────────────────────────────────────────────────
$search    = trim($_GET['search']     ?? '');
$fRole     = $_GET['role']            ?? '';
$fDept     = $_GET['department']      ?? '';
$fStatus   = $_GET['status']          ?? '';
$orderBy   = in_array($_GET['sort'] ?? '', ['name','email','role','department','last_login','created_at']) ? $_GET['sort'] : 'created_at';
$orderDir  = strtolower($_GET['dir'] ?? '') === 'asc' ? 'ASC' : 'DESC';

$where  = '1=1';
$params = [];

if (!empty($search)) {
    $where .= ' AND (name LIKE ? OR email LIKE ? OR job_title LIKE ? OR department LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if (!empty($fRole) && in_array($fRole, ['admin','manager','sales_rep'])) {
    $where .= ' AND role = ?';
    $params[] = $fRole;
}
if (!empty($fDept)) {
    $where .= ' AND department LIKE ?';
    $params[] = "%$fDept%";
}
if (!empty($fStatus) && in_array($fStatus, ['active','inactive','suspended'])) {
    $where .= ' AND status = ?';
    $params[] = $fStatus;
}

// Pagination
$limit  = 15;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalUsers / $limit));

$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id) as assigned_leads_count 
    FROM users u 
    WHERE $where 
    ORDER BY $orderBy $orderDir LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Fetch distinct departments for filter dropdown
$deptStmt = $pdo->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

// Stats
$roleStatsStmt = $pdo->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$roleStats = [];
foreach ($roleStatsStmt->fetchAll() as $r) { $roleStats[$r['role']] = $r['cnt']; }

$statusStatsStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM users GROUP BY status");
$statusStats = [];
foreach ($statusStatsStmt->fetchAll() as $r) { $statusStats[$r['status']] = $r['cnt']; }

$filterParams = http_build_query(array_filter([
    'search'     => $search,
    'role'       => $fRole,
    'department' => $fDept,
    'status'     => $fStatus,
    'sort'       => $orderBy,
    'dir'        => strtolower($orderDir),
]));

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0">Team Management</h1>
        <p class="text-muted small mb-0">Manage your CRM team members, roles, and access</p>
    </div>
    <a href="<?= BASE_URL ?>/team/create.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add Team Member
    </a>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Total Users', 'value' => $totalUsers, 'icon' => 'bi-people-fill', 'color' => 'text-primary', 'bg' => 'rgba(24,78,119,0.1)'],
        ['label' => 'Admins', 'value' => $roleStats['admin'] ?? 0, 'icon' => 'bi-shield-fill', 'color' => 'text-danger', 'bg' => 'rgba(220,53,69,0.1)'],
        ['label' => 'Managers', 'value' => $roleStats['manager'] ?? 0, 'icon' => 'bi-person-badge-fill', 'color' => 'text-warning', 'bg' => 'rgba(253,126,20,0.1)'],
        ['label' => 'Sales Reps', 'value' => $roleStats['sales_rep'] ?? 0, 'icon' => 'bi-person-lines-fill', 'color' => 'text-success', 'bg' => 'rgba(52,160,164,0.1)'],
        ['label' => 'Active', 'value' => $statusStats['active'] ?? 0, 'icon' => 'bi-check-circle-fill', 'color' => 'text-success', 'bg' => 'rgba(25,135,84,0.1)'],
        ['label' => 'Suspended', 'value' => $statusStats['suspended'] ?? 0, 'icon' => 'bi-slash-circle-fill', 'color' => 'text-danger', 'bg' => 'rgba(220,53,69,0.1)'],
    ];
    foreach ($statCards as $card): ?>
    <div class="col-6 col-md-2">
        <div class="card p-0 h-100">
            <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-2" style="width:40px;height:40px;background:<?= $card['bg'] ?>;">
                    <i class="bi <?= $card['icon'] ?> <?= $card['color'] ?> fs-5"></i>
                </div>
                <div class="fw-bold fs-4 lh-1"><?= $card['value'] ?></div>
                <div class="text-muted small"><?= $card['label'] ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card p-0 mb-4">
    <div class="card-header">
        <form action="" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Name, email, title..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Role</label>
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <option value="admin"     <?= $fRole==='admin'?'selected':'' ?>>Admin</option>
                    <option value="manager"   <?= $fRole==='manager'?'selected':'' ?>>Manager</option>
                    <option value="sales_rep" <?= $fRole==='sales_rep'?'selected':'' ?>>Sales Rep</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Department</label>
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept) ?>" <?= $fDept===$dept?'selected':'' ?>><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= $fStatus==='active'?'selected':'' ?>>Active</option>
                    <option value="inactive" <?= $fStatus==='inactive'?'selected':'' ?>>Inactive</option>
                    <option value="suspended" <?= $fStatus==='suspended'?'selected':'' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <div class="w-100 d-flex flex-column">
                    <label class="form-label d-none d-md-block mb-1">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter me-1"></i>Filter</button>
                        <a href="team.php" class="btn btn-outline-secondary" title="Clear"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Team Table -->
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <i class="bi bi-people text-muted" style="font-size:3rem;"></i>
            <p class="fs-5 fw-semibold mt-3 mb-1">No team members found</p>
            <p class="text-muted small">Try adjusting your search or <a href="team/create.php">add a new member</a>.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th class="d-none d-md-table-cell">Role</th>
                        <th class="d-none d-lg-table-cell">Department</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th class="d-none d-lg-table-cell">Last Login</th>
                        <th class="d-none d-xl-table-cell">Joined</th>
                        <th class="text-end">Assigned Leads</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <?= getUserAvatarHtml($u, 'md') ?>
                            <div>
                                <div class="fw-semibold"><?= htmlspecialchars($u['name']) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                                <?php if (!empty($u['job_title'])): ?>
                                <div class="small text-muted fst-italic"><?= htmlspecialchars($u['job_title']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge <?= getRoleBadgeClass($u['role']) ?>"><?= getRoleLabel($u['role']) ?></span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span class="small"><?= htmlspecialchars($u['department'] ?? '—') ?></span>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="d-flex align-items-center gap-1">
                            <span class="status-dot <?= getUserStatusClass($u['status'] ?? 'active') ?>"></span>
                            <span class="small"><?= ucfirst($u['status'] ?? 'active') ?></span>
                        </span>
                    </td>
                    <td class="d-none d-lg-table-cell">
                        <span class="small text-muted"><?= !empty($u['last_login']) ? timeAgo($u['last_login']) : 'Never' ?></span>
                    </td>
                    <td class="d-none d-xl-table-cell">
                        <span class="small text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                    </td>
                    <td class="text-end fw-semibold text-muted">
                        <?= (int)$u['assigned_leads_count'] ?>
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end">
                            <a href="team/edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-light text-primary border" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            <button type="button" class="btn btn-sm btn-light border"
                                    title="<?= ($u['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' ?>"
                                    onclick="toggleStatus(<?= $u['id'] ?>, '<?= ($u['status'] ?? 'active') === 'active' ? 'inactive' : 'active' ?>')">
                                <i class="bi <?= ($u['status'] ?? 'active') === 'active' ? 'bi-slash-circle text-warning' : 'bi-check-circle text-success' ?>"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light border text-info" title="Reset Password"
                                    onclick="resetPassword(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                <i class="bi bi-key"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light text-danger border" title="Delete"
                                    onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php else: ?>
                            <span class="badge bg-light text-muted border small py-1 px-2">You</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted"><?= $totalUsers ?> member<?= $totalUsers !== 1 ? 's' : '' ?> found<?= !empty($filterParams) ? ' (filtered)' : '' ?></small>
                <nav>
                    <ul class="pagination mb-0 pagination-sm">
                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                            <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $page-1 ?>">«</a>
                        </li>
                        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                        <li class="page-item <?= $page==$i?'active':'' ?>">
                            <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                            <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $page+1 ?>">»</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="actionForm" method="POST" action="team/action.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="user_id" id="actionUserId">
    <input type="hidden" name="value" id="actionValue">
</form>

<script>
function toggleStatus(userId, newStatus) {
    const label = newStatus === 'inactive' ? 'deactivate' : 'activate';
    if (!confirm('Are you sure you want to ' + label + ' this user?')) return;
    document.getElementById('actionType').value   = 'toggle_status';
    document.getElementById('actionUserId').value = userId;
    document.getElementById('actionValue').value  = newStatus;
    document.getElementById('actionForm').submit();
}

function deleteUser(userId, name) {
    if (!confirm('Delete "' + name + '"? This action cannot be undone.')) return;
    document.getElementById('actionType').value   = 'delete';
    document.getElementById('actionUserId').value = userId;
    document.getElementById('actionForm').submit();
}

function resetPassword(userId, name) {
    const newPass = prompt('Set new password for "' + name + '" (minimum 8 characters):');
    if (!newPass) return;
    if (newPass.length < 8) { alert('Password must be at least 8 characters.'); return; }
    document.getElementById('actionType').value   = 'reset_password';
    document.getElementById('actionUserId').value = userId;
    document.getElementById('actionValue').value  = newPass;
    document.getElementById('actionForm').submit();
}
</script>

<?php include 'includes/footer.php'; ?>
