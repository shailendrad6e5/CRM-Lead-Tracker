<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

requireLogin();

// Sales Reps can only see their assigned leads — redirect them to My Leads
if (isSalesRep()) {
    header('Location: ' . BASE_URL . '/my_leads.php');
    exit;
}

$pageTitle = 'All Leads';

// ── Single Delete ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['bulk_action'])) {
    verifyCsrfToken();
    $id   = (int)$_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success'] = "Lead deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete lead.";
    }
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

// ── Bulk Actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'], $_POST['selected_ids'])) {
    verifyCsrfToken();
    $action      = $_POST['bulk_action'];
    $selectedRaw = $_POST['selected_ids'] ?? [];
    $selectedIds = array_filter(array_map('intval', (array)$selectedRaw));

    if (!empty($selectedIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM leads WHERE id IN ($placeholders)");
            $stmt->execute($selectedIds);
            $count = $stmt->rowCount();
            $_SESSION['success'] = "$count lead(s) deleted.";
        } elseif (in_array($action, ['New','Contacted','Qualified','Proposal Sent','Won','Lost'])) {
            $stmt = $pdo->prepare("UPDATE leads SET status=? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$action], $selectedIds));
            $count = $stmt->rowCount();
            $_SESSION['success'] = "$count lead(s) updated to '$action'.";
        }
    }
    header("Location: " . BASE_URL . "/leads/list.php");
    exit;
}

// Build query with filters and search
$where  = "1=1";
$params = [];

$search   = trim($_GET['search']   ?? '');
$fStatus  = $_GET['status']        ?? '';
$fPriority= $_GET['priority']      ?? '';
$fSource  = $_GET['source']        ?? '';
$fFollowUp= $_GET['followup']      ?? '';
$fDateFrom= $_GET['date_from']     ?? '';
$fDateTo  = $_GET['date_to']       ?? '';
$fAssigned= (int)($_GET['assigned_to'] ?? 0);

if (!empty($search)) {
    $where   .= " AND (l.name LIKE ? OR l.company LIKE ? OR l.email LIKE ? OR l.phone LIKE ?)";
    $params   = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if (!empty($fStatus)) {
    $where   .= " AND l.status = ?";
    $params[] = $fStatus;
}
if (!empty($fPriority)) {
    $where   .= " AND l.priority = ?";
    $params[] = $fPriority;
}
if (!empty($fSource)) {
    $where   .= " AND l.source = ?";
    $params[] = $fSource;
}
if ($fAssigned > 0 && canAssignLeads()) {
    $where   .= " AND l.assigned_to = ?";
    $params[] = $fAssigned;
}
if (!empty($fFollowUp)) {
    if ($fFollowUp === 'Today') {
        $where .= " AND l.followup_date = CURRENT_DATE() AND l.followup_status != 'Completed'";
    } elseif ($fFollowUp === 'Overdue') {
        $where .= " AND l.followup_date < CURRENT_DATE() AND l.followup_status != 'Completed'";
    } elseif ($fFollowUp === 'Upcoming') {
        $where .= " AND l.followup_date > CURRENT_DATE() AND l.followup_status != 'Completed'";
    } elseif ($fFollowUp === 'Completed') {
        $where .= " AND l.followup_status = 'Completed'";
    }
}
if (!empty($fDateFrom)) {
    $where   .= " AND DATE(l.created_at) >= ?";
    $params[] = $fDateFrom;
}
if (!empty($fDateTo)) {
    $where   .= " AND DATE(l.created_at) <= ?";
    $params[] = $fDateTo;
}

// Whitelist sort columns
$allowedSorts = ['created_at', 'name', 'company', 'status', 'priority'];
$orderBy  = in_array($_GET['sort'] ?? '', $allowedSorts) ? 'l.'.$_GET['sort'] : 'l.created_at';
$orderDir = (strtolower($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';
$nextDir  = ($orderDir === 'ASC') ? 'desc' : 'asc';

// Rows per page
$allowedLimits = [10, 25, 50, 100];
$limitRaw = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$limit = in_array($limitRaw, $allowedLimits) ? $limitRaw : 10;

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Build filter query string for pagination links
$filterParams = http_build_query(array_filter([
    'search'    => $search,
    'status'    => $fStatus,
    'priority'  => $fPriority,
    'source'    => $fSource,
    'followup'  => $fFollowUp,
    'date_from' => $fDateFrom,
    'date_to'   => $fDateTo,
    'assigned_to' => $fAssigned ?: '',
    'sort'      => $orderBy,
    'dir'       => strtolower($orderDir),
    'per_page'  => $limit !== 10 ? $limit : '',
]));

// Total count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE $where");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Fetch data with assigned user info
$sql  = "SELECT l.*, u.name as assigned_name, u.role as assigned_role, u.avatar as assigned_avatar FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE $where ORDER BY $orderBy $orderDir LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Fetch team for filter
$teamMembers = canAssignLeads() ? getTeamMembers($pdo) : [];

include '../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-0 text-gray-800">Leads</h1>
        <p class="text-muted small mb-0">Manage and track your potential customers</p>
    </div>
    <div class="d-flex gap-2">
        <a href="export.php?<?= htmlspecialchars($filterParams) ?>" class="btn btn-outline-success">
            <i class="bi bi-download me-2"></i>Export CSV
        </a>
        <a href="add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Lead
        </a>
    </div>
</div>

<div class="card p-0 mb-4">
    <div class="card-header bg-light">
        <form action="" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
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
                        <?php foreach(['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $s): ?>
                        <option value="<?= $s ?>" <?= $fStatus===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Priority</label>
                    <select name="priority" class="form-select">
                        <option value="">All Priorities</option>
                        <?php foreach(['High','Medium','Low'] as $p): ?>
                        <option value="<?= $p ?>" <?= $fPriority===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Source</label>
                    <select name="source" class="form-select">
                        <option value="">All Sources</option>
                        <?php foreach(['Website','Referral','Cold Call','Email Campaign','Other'] as $src): ?>
                        <option value="<?= $src ?>" <?= $fSource===$src?'selected':'' ?>><?= $src ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">Per Page</label>
                    <select name="per_page" class="form-select">
                        <?php foreach([10,25,50,100] as $pp): ?>
                        <option value="<?= $pp ?>" <?= $limit===$pp?'selected':'' ?>><?= $pp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <div class="w-100 d-flex flex-column">
                        <label class="form-label d-none d-md-block mb-1">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter me-1"></i>Filter</button>
                            <a href="list.php" class="btn btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Date range row -->
            <div class="row g-2 mt-1">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($fDateFrom) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($fDateTo) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Follow-up</label>
                    <select name="followup" class="form-select">
                        <option value="">All Follow-ups</option>
                        <option value="Overdue" <?= $fFollowUp==='Overdue'?'selected':'' ?>>Overdue</option>
                        <option value="Today" <?= $fFollowUp==='Today'?'selected':'' ?>>Today</option>
                        <option value="Upcoming" <?= $fFollowUp==='Upcoming'?'selected':'' ?>>Upcoming</option>
                        <option value="Completed" <?= $fFollowUp==='Completed'?'selected':'' ?>>Completed</option>
                    </select>
                </div>
                <?php if (canAssignLeads() && !empty($teamMembers)): ?>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Assigned To</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">All Members</option>
                        <?php foreach ($teamMembers as $tm): ?>
                        <option value="<?= $tm['id'] ?>" <?= $fAssigned===$tm['id']?'selected':'' ?>><?= htmlspecialchars($tm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2 d-flex align-items-end">
                    <small class="text-muted"><?= $totalRecords ?> lead<?= $totalRecords!=1?'s':'' ?> found<?= !empty($filterParams) ? ' (filtered)' : '' ?></small>
                </div>
            </div>
        </form>
    </div>
                <div class="card-body p-0">
        <form id="bulkForm" method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="selectAll" class="form-check-input" title="Select all">
                        </th>
                        <?php
                        function sortLink($col, $label, $currentSort, $currentDir, $filterParams, $extraClass = '') {
                            $dir = ($currentSort === $col && $currentDir === 'ASC') ? 'desc' : 'asc';
                            $icon = ($currentSort === $col) ? ($currentDir === 'ASC' ? 'bi-sort-up' : 'bi-sort-down') : 'bi-arrow-down-up';
                            $qs = $filterParams ? $filterParams . '&' : '';
                            return "<th class=\"{$extraClass}\"><a href=\"?{$qs}sort={$col}&dir={$dir}\" class=\"text-decoration-none text-dark fw-semibold\">{$label} <i class=\"bi {$icon} small ms-1\"></i></a></th>";
                        }
                        echo sortLink('name',       'Name',     $orderBy, $orderDir, $filterParams);
                        echo sortLink('company',    'Company',  $orderBy, $orderDir, $filterParams, 'd-none d-md-table-cell');
                        ?>
                        <th>Contact</th>
                        <?php
                        echo sortLink('status',   'Status',   $orderBy, $orderDir, $filterParams);
                        echo sortLink('priority', 'Priority', $orderBy, $orderDir, $filterParams, 'd-none d-lg-table-cell');
                        echo sortLink('created_at','Date',    $orderBy, $orderDir, $filterParams, 'd-none d-md-table-cell');
                        ?>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="fs-5 fw-semibold mt-3 mb-1">No leads found</p>
                                <p class="text-muted small mb-3">Try adjusting your search or filters, or add a new lead.</p>
                                <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-2"></i>Add First Lead</a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($leads as $lead): ?>
                        <tr onclick="if(!event.target.closest('a') && !event.target.closest('button') && !event.target.closest('input')) window.location='view.php?id=<?= $lead['id'] ?>';" style="cursor: pointer;">
                            <td>
                                <input type="checkbox" name="selected_ids[]" value="<?= $lead['id'] ?>" class="form-check-input lead-checkbox">
                            </td>
                            <td>
                                <a href="view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none d-block"><?= htmlspecialchars($lead['name']) ?></a>
                                <?php
                                $fStatus = $lead['followup_status'] ?? 'Pending';
                                $fState  = computeFollowUpState($lead['followup_date'], $lead['followup_time'], $fStatus);
                                if ($fState !== 'None' && $fState !== 'Completed'):
                                    $fClass = getFollowUpStateBadgeClass($fState);
                                    $fText  = $fState === 'Overdue' ? 'Overdue' : ($fState === 'Today' ? 'Today' : 'Upcoming');
                                ?>
                                <span class="badge <?= $fClass ?>" style="font-size:10px;"><i class="bi bi-calendar-check me-1"></i><?= $fText ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="small text-muted"><i class="bi bi-building me-1"></i><?= htmlspecialchars($lead['company'] ?? 'N/A') ?></div>
                            </td>
                            <td>
                                <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($lead['email'] ?? 'N/A') ?></div>
                                <div class="small"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($lead['phone'] ?? 'N/A') ?></div>
                            </td>
                            <td><span class="badge <?= getStatusBadgeClass($lead['status']) ?>"><?= $lead['status'] ?></span></td>
                            <td class="d-none d-lg-table-cell"><span class="badge <?= getPriorityBadgeClass($lead['priority']) ?>"><?= $lead['priority'] ?></span></td>
                            <td class="d-none d-xl-table-cell">
                                <?php if (!empty($lead['assigned_name'])): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <?= getUserAvatarHtml(['name'=>$lead['assigned_name'],'avatar'=>$lead['assigned_avatar']??''], 'sm') ?>
                                    <span class="small text-truncate" style="max-width:100px;"><?= htmlspecialchars($lead['assigned_name']) ?></span>
                                </div>
                                <?php else: ?>
                                <span class="small text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell"><span class="small"><?= date('M d, Y', strtotime($lead['created_at'])) ?></span></td>
                            <td class="text-end">
                                <a href="view.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-primary border me-1" title="View"><i class="bi bi-eye"></i></a>
                                <a href="edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-light text-warning border me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if (canDeleteLead($lead)): ?>
                                <button type="button" class="btn btn-sm btn-light text-danger border" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $lead['id'] ?>" data-url="<?= BASE_URL ?>/leads/list.php">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Bulk Action Bar (hidden by default) -->
        <div id="bulkActionBar" class="d-none border-top bg-light p-3 d-flex align-items-center gap-3">
            <span class="fw-medium text-muted small" id="bulkCount">0 selected</span>
            <div class="vr"></div>
            <select class="form-select form-select-sm" id="bulkStatusSelect" style="width:160px;">
                <option value="">Change Status...</option>
                <?php foreach(['New','Contacted','Qualified','Proposal Sent','Won','Lost'] as $s): ?>
                <option value="<?= $s ?>"><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="doBulkAction('status')" class="btn btn-sm btn-outline-primary"><i class="bi bi-check2-all me-1"></i>Apply</button>
            <div class="vr"></div>
            <button type="button" onclick="doBulkAction('delete')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete Selected</button>
            <button type="button" id="bulkCancelBtn" class="btn btn-sm btn-light ms-auto"><i class="bi bi-x"></i> Deselect All</button>
        </div>
        </form>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="card-footer bg-white border-top py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Showing <?= $offset+1 ?>–<?= min($offset+$limit, $totalRecords) ?> of <?= $totalRecords ?> leads</small>
            <nav>
                <ul class="pagination mb-0 pagination-sm">
                    <li class="page-item <?= ($page<=1)?'disabled':'' ?>">
                        <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $page-1 ?>">«</a>
                    </li>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage   = min($totalPages, $page + 2);
                    if ($startPage > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?= ($page==$i)?'active':'' ?>">
                        <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php
                    endfor;
                    if ($endPage < $totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    ?>
                    <li class="page-item <?= ($page>=$totalPages)?'disabled':'' ?>">
                        <a class="page-link" href="?<?= $filterParams ? $filterParams.'&' : '' ?>page=<?= $page+1 ?>">»</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
