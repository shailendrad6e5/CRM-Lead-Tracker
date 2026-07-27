<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle    = 'My Leads';
$userId       = (int)$_SESSION['user_id'];

// ── Filters ──────────────────────────────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$fStatus  = $_GET['status']        ?? '';
$fPriority= $_GET['priority']      ?? '';
$orderBy  = in_array($_GET['sort'] ?? '', ['name','company','status','priority','created_at','followup_date']) ? $_GET['sort'] : 'created_at';
$orderDir = strtolower($_GET['dir'] ?? '') === 'asc' ? 'ASC' : 'DESC';
$limit    = 15;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $limit;

// Always filter by current user's leads
$where  = 'l.assigned_to = ?';
$params = [$userId];

if (!empty($search)) {
    $where .= ' AND (l.name LIKE ? OR l.company LIKE ? OR l.email LIKE ?)';
    $params = array_merge($params, ["%$search%","%$search%","%$search%"]);
}
if (!empty($fStatus) && in_array($fStatus, ['New','Contacted','Qualified','Proposal Sent','Won','Lost'])) {
    $where .= ' AND l.status = ?';
    $params[] = $fStatus;
}
if (!empty($fPriority) && in_array($fPriority, ['Low','Medium','High'])) {
    $where .= ' AND l.priority = ?';
    $params[] = $fPriority;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE $where");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

$stmt = $pdo->prepare("SELECT l.* FROM leads l WHERE $where ORDER BY $orderBy $orderDir LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Quick stats for this user
$statsStmt = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(status='Won') as won,
    SUM(status='Lost') as lost,
    SUM(status='New') as new_leads,
    SUM(followup_date IS NOT NULL AND followup_status='Pending') as pending_followups
    FROM leads WHERE assigned_to = ?");
$statsStmt->execute([$userId]);
$myStats = $statsStmt->fetch();

include 'includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0">My Leads</h1>
        <p class="text-muted small mb-0">Leads assigned to you — <span class="fw-semibold text-primary"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span></p>
    </div>
    <a href="<?= BASE_URL ?>/leads/add.php" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Add Lead
    </a>
</div>

<!-- My Stats -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label'=>'Total Assigned','value'=>$myStats['total']??0,       'icon'=>'bi-people',        'color'=>'text-primary',   'bg'=>'rgba(24,78,119,0.1)'],
        ['label'=>'New',           'value'=>$myStats['new_leads']??0,    'icon'=>'bi-star',          'color'=>'text-info',      'bg'=>'rgba(23,162,184,0.1)'],
        ['label'=>'Won',           'value'=>$myStats['won']??0,          'icon'=>'bi-trophy',        'color'=>'text-success',   'bg'=>'rgba(40,167,69,0.1)'],
        ['label'=>'Follow-ups',    'value'=>$myStats['pending_followups']??0, 'icon'=>'bi-calendar-check','color'=>'text-warning','bg'=>'rgba(255,193,7,0.1)'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-3">
        <div class="card p-0">
            <div class="card-body d-flex align-items-center gap-3" style="padding:16px 20px !important;">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:<?= $c['bg'] ?>;">
                    <i class="bi <?= $c['icon'] ?> <?= $c['color'] ?> fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold fs-4 lh-1"><?= $c['value'] ?></div>
                    <div class="text-muted small"><?= $c['label'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card p-0 mb-4">
    <div class="card-header">
        <form action="" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Name, company, email..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $s): ?>
                    <option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="High"   <?= $fPriority==='High'  ?'selected':'' ?>>High</option>
                    <option value="Medium" <?= $fPriority==='Medium'?'selected':'' ?>>Medium</option>
                    <option value="Low"    <?= $fPriority==='Low'   ?'selected':'' ?>>Low</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <div class="w-100 d-flex flex-column">
                    <label class="form-label d-none d-md-block mb-1">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter me-1"></i>Filter</button>
                        <a href="my_leads.php" class="btn btn-outline-secondary" title="Clear"><i class="bi bi-x-lg"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <?php if (empty($leads)): ?>
        <div class="text-center py-5">
            <i class="bi bi-person-lines-fill text-muted" style="font-size:3rem;"></i>
            <p class="fs-5 fw-semibold mt-3 mb-1">No leads assigned to you yet</p>
            <p class="text-muted small">Your assigned leads will appear here. <a href="<?= BASE_URL ?>/leads/add.php">Add your first lead</a>.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th class="d-none d-md-table-cell">Company</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Priority</th>
                        <th class="d-none d-lg-table-cell">Follow-up</th>
                        <th class="d-none d-md-table-cell">Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($leads as $lead): ?>
                <tr onclick="if(!event.target.closest('a') && !event.target.closest('button')) window.location='<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>';" style="cursor:pointer;">
                    <td>
                        <div class="fw-semibold"><?= htmlspecialchars($lead['name']) ?></div>
                        <?php
                        $fStatus2 = $lead['followup_status'] ?? 'Pending';
                        $fState2  = computeFollowUpState($lead['followup_date'], $lead['followup_time'], $fStatus2);
                        if ($fState2 !== 'None' && $fState2 !== 'Completed'):
                            $fClass2 = getFollowUpStateBadgeClass($fState2);
                        ?>
                        <span class="badge <?= $fClass2 ?>" style="font-size:10px;"><i class="bi bi-calendar-check me-1"></i><?= $fState2 ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell"><span class="small text-muted"><?= htmlspecialchars($lead['company'] ?? '—') ?></span></td>
                    <td><span class="badge <?= getStatusBadgeClass($lead['status']) ?>"><?= $lead['status'] ?></span></td>
                    <td class="d-none d-lg-table-cell"><span class="badge <?= getPriorityBadgeClass($lead['priority']) ?>"><?= $lead['priority'] ?></span></td>
                    <td class="d-none d-lg-table-cell">
                        <span class="small text-muted"><?= !empty($lead['followup_date']) ? date('M d', strtotime($lead['followup_date'])) : '—' ?></span>
                    </td>
                    <td class="d-none d-md-table-cell"><span class="small"><?= date('M d, Y', strtotime($lead['created_at'])) ?></span></td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-primary border me-1" title="View"><i class="bi bi-eye"></i></a>
                        <a href="<?= BASE_URL ?>/leads/edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-warning border" title="Edit"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-white border-top py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <small class="text-muted"><?= $totalRecords ?> lead<?= $totalRecords!==1?'s':'' ?></small>
                <nav>
                    <ul class="pagination mb-0 pagination-sm">
                        <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($fStatus) ?>">«</a></li>
                        <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
                        <li class="page-item <?= $page==$i?'active':'' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($fStatus) ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($fStatus) ?>">»</a></li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
